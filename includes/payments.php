<?php
declare(strict_types=1);

/*
 * Online payments: Stripe Checkout (main) and PayPal Checkout (second option).
 * Talks to both APIs directly with cURL, so no SDKs need installing on the host.
 * Card details are only ever entered on Stripe's or PayPal's own pages.
 */

function payments_enabled(): bool
{
    return (bool) cfg('payments.enabled') && (stripe_enabled() || paypal_enabled());
}

function stripe_enabled(): bool
{
    return (string) cfg('payments.stripe.secret_key', '') !== '';
}

function paypal_enabled(): bool
{
    return (string) cfg('payments.paypal.client_id', '') !== '' && (string) cfg('payments.paypal.secret', '') !== '';
}

function pay_currency(): string
{
    return strtoupper((string) cfg('payments.currency', 'NZD'));
}

/** The paid payment for a booking, if any. */
function booking_payment(int $bookingId): ?array
{
    return row("SELECT * FROM payments WHERE booking_id = ? AND status IN ('paid','refunded') ORDER BY id DESC LIMIT 1", [$bookingId]);
}

/** 'paid', 'refunded', 'unpaid' or 'none' (nothing to pay). */
function payment_state(array $b): string
{
    $p = booking_payment((int) $b['id']);
    if ($p) {
        return $p['status'];
    }
    return $b['price_cents'] === null || (int) $b['price_cents'] <= 0 ? 'none' : 'unpaid';
}

function payment_badge(string $state): string
{
    $map = ['paid' => ['Paid', 'confirmed'], 'refunded' => ['Refunded', 'cancelled'], 'unpaid' => ['Unpaid', 'pending'], 'none' => ['Price to confirm', 'cancelled']];
    [$label, $cls] = $map[$state] ?? [$state, 'cancelled'];
    return '<span class="sap-badge sap-badge-' . $cls . '">' . e($label) . '</span>';
}

/** Can the client pay this booking online right now? */
function can_pay_online(array $b): bool
{
    return payments_enabled()
        && $b['status'] === 'confirmed'
        && $b['price_cents'] !== null && (int) $b['price_cents'] > 0
        && payment_state($b) === 'unpaid';
}

/* ---------------- HTTP ---------------- */

/** Small JSON/form HTTP client. Returns [status, decoded body]. */
function pay_http(string $method, string $url, array $opt = []): array
{
    $ch = curl_init($url);
    $headers = $opt['headers'] ?? [];
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);
    if (isset($opt['form'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($opt['form']));
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    } elseif (isset($opt['json'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($opt['json']));
        $headers[] = 'Content-Type: application/json';
    } elseif (isset($opt['raw'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $opt['raw']);
    }
    if (isset($opt['basic'])) {
        curl_setopt($ch, CURLOPT_USERPWD, $opt['basic']);
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($body === false) {
        throw new RuntimeException('Payment provider unreachable: ' . $err);
    }
    $data = json_decode((string) $body, true);
    return [$status, is_array($data) ? $data : []];
}

/* ---------------- Stripe ---------------- */

function stripe_api(string $method, string $path, array $form = []): array
{
    $base = rtrim((string) cfg('payments.stripe.api_base', 'https://api.stripe.com'), '/');
    [$status, $data] = pay_http($method, $base . $path, [
        'basic'   => cfg('payments.stripe.secret_key') . ':',
        'headers' => ['Stripe-Version: 2024-06-20'],
    ] + ($form ? ['form' => $form] : []));
    if ($status >= 400) {
        throw new RuntimeException('Stripe error: ' . ($data['error']['message'] ?? "HTTP $status"));
    }
    return $data;
}

/** Create a Stripe Checkout session for a booking and return the URL to send the client to. */
function stripe_start_checkout(array $b, array $user): string
{
    $amount = (int) $b['price_cents'];
    $pid = start_payment_record($b, 'stripe', $amount);
    $session = stripe_api('POST', '/v1/checkout/sessions', [
        'mode'                 => 'payment',
        'customer_email'       => $user['email'],
        'client_reference_id'  => (string) $b['id'],
        'metadata'             => ['booking_id' => (string) $b['id'], 'payment_id' => (string) $pid],
        'payment_intent_data'  => ['metadata' => ['booking_id' => (string) $b['id']], 'description' => 'Booking #' . $b['id']],
        'line_items'           => [[
            'quantity'   => 1,
            'price_data' => [
                'currency'     => strtolower(pay_currency()),
                'unit_amount'  => $amount,
                'product_data' => ['name' => $b['service_name'] . ' – ' . booking_when($b), 'description' => cfg('app.name') . ' booking #' . $b['id']],
            ],
        ]],
        'success_url' => abs_url('/account/pay-return.php?provider=stripe&session_id={CHECKOUT_SESSION_ID}'),
        'cancel_url'  => abs_url('/account/booking.php?id=' . (int) $b['id'] . '&pay=cancelled'),
    ]);
    q('UPDATE payments SET provider_ref = ?, updated_at = ? WHERE id = ?', [$session['id'], now_sql(), $pid]);
    return (string) $session['url'];
}

/** Check a Checkout session and record the payment if Stripe says it's paid. Returns the booking id. */
function stripe_confirm_session(string $sessionId): ?int
{
    $s = stripe_api('GET', '/v1/checkout/sessions/' . rawurlencode($sessionId));
    $p = row("SELECT * FROM payments WHERE provider = 'stripe' AND provider_ref = ?", [$sessionId]);
    if (!$p) {
        return null;
    }
    if (($s['payment_status'] ?? '') === 'paid' && (int) ($s['amount_total'] ?? 0) === (int) $p['amount_cents']
        && strtoupper((string) ($s['currency'] ?? '')) === strtoupper($p['currency'])) {
        $pi = is_array($s['payment_intent'] ?? null) ? $s['payment_intent']['id'] : (string) ($s['payment_intent'] ?? '');
        mark_payment_paid((int) $p['id'], $pi);
    }
    return (int) $p['booking_id'];
}

/** Verify a Stripe webhook signature (Stripe-Signature header). */
function stripe_verify_webhook(string $payload, string $header, int $tolerance = 300): bool
{
    $secret = (string) cfg('payments.stripe.webhook_secret', '');
    if ($secret === '' || $header === '') {
        return false;
    }
    $t = null;
    $sigs = [];
    foreach (explode(',', $header) as $part) {
        [$k, $v] = array_pad(explode('=', trim($part), 2), 2, '');
        if ($k === 't') {
            $t = (int) $v;
        } elseif ($k === 'v1') {
            $sigs[] = $v;
        }
    }
    if (!$t || !$sigs || abs(time() - $t) > $tolerance) {
        return false;
    }
    $expected = hash_hmac('sha256', $t . '.' . $payload, $secret);
    foreach ($sigs as $sig) {
        if (hash_equals($expected, $sig)) {
            return true;
        }
    }
    return false;
}

/* ---------------- PayPal ---------------- */

function paypal_base(): string
{
    $custom = (string) cfg('payments.paypal.api_base', '');
    if ($custom !== '') {
        return rtrim($custom, '/');
    }
    return cfg('payments.paypal.mode') === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
}

function paypal_token(): string
{
    static $token = null;
    if ($token) {
        return $token;
    }
    [$status, $data] = pay_http('POST', paypal_base() . '/v1/oauth2/token', [
        'basic' => cfg('payments.paypal.client_id') . ':' . cfg('payments.paypal.secret'),
        'form'  => ['grant_type' => 'client_credentials'],
    ]);
    if ($status >= 400 || empty($data['access_token'])) {
        throw new RuntimeException('PayPal login failed. Check the client ID and secret.');
    }
    return $token = (string) $data['access_token'];
}

function paypal_api(string $method, string $path, ?array $json = null, array $extraHeaders = []): array
{
    $opt = ['headers' => array_merge(['Authorization: Bearer ' . paypal_token()], $extraHeaders)];
    if ($json !== null) {
        $opt['json'] = $json;
    }
    [$status, $data] = pay_http($method, paypal_base() . $path, $opt);
    if ($status >= 400) {
        throw new RuntimeException('PayPal error: ' . ($data['message'] ?? $data['details'][0]['description'] ?? "HTTP $status"));
    }
    return $data;
}

function cents_to_decimal(int $cents): string
{
    return number_format($cents / 100, 2, '.', '');
}

/** Create a PayPal order and return the approval URL. */
function paypal_start_checkout(array $b): string
{
    $amount = (int) $b['price_cents'];
    $pid = start_payment_record($b, 'paypal', $amount);
    $order = paypal_api('POST', '/v2/checkout/orders', [
        'intent' => 'CAPTURE',
        'purchase_units' => [[
            'reference_id' => 'booking-' . $b['id'],
            'custom_id'    => (string) $b['id'],
            'description'  => mb_substr($b['service_name'] . ' – ' . booking_when($b), 0, 120),
            'amount'       => ['currency_code' => pay_currency(), 'value' => cents_to_decimal($amount)],
        ]],
        'payment_source' => ['paypal' => ['experience_context' => [
            'brand_name'          => mb_substr((string) cfg('app.name'), 0, 120),
            'shipping_preference' => 'NO_SHIPPING',
            'user_action'         => 'PAY_NOW',
            'return_url'          => abs_url('/account/pay-return.php?provider=paypal'),
            'cancel_url'          => abs_url('/account/booking.php?id=' . (int) $b['id'] . '&pay=cancelled'),
        ]]],
    ], ['PayPal-Request-Id: sap-order-' . $pid]);
    q('UPDATE payments SET provider_ref = ?, updated_at = ? WHERE id = ?', [$order['id'], now_sql(), $pid]);
    foreach ($order['links'] ?? [] as $l) {
        if (in_array($l['rel'] ?? '', ['payer-action', 'approve'], true)) {
            return (string) $l['href'];
        }
    }
    throw new RuntimeException('PayPal did not return an approval link.');
}

/** Capture an approved PayPal order and record the payment. Returns the booking id. */
function paypal_capture(string $orderId): ?int
{
    $p = row("SELECT * FROM payments WHERE provider = 'paypal' AND provider_ref = ?", [$orderId]);
    if (!$p) {
        return null;
    }
    if ($p['status'] === 'paid') {
        return (int) $p['booking_id'];
    }
    $res = paypal_api('POST', '/v2/checkout/orders/' . rawurlencode($orderId) . '/capture', [], ['PayPal-Request-Id: sap-capture-' . $p['id']]);
    $cap = $res['purchase_units'][0]['payments']['captures'][0] ?? [];
    $value = (string) ($cap['amount']['value'] ?? '');
    $cur = strtoupper((string) ($cap['amount']['currency_code'] ?? ''));
    if (($res['status'] ?? '') === 'COMPLETED' && ($cap['status'] ?? '') === 'COMPLETED'
        && $value === cents_to_decimal((int) $p['amount_cents']) && $cur === strtoupper($p['currency'])) {
        mark_payment_paid((int) $p['id'], (string) $cap['id']);
    } else {
        q("UPDATE payments SET status = 'failed', updated_at = ? WHERE id = ?", [now_sql(), $p['id']]);
    }
    return (int) $p['booking_id'];
}

/* ---------------- Records, refunds, emails ---------------- */

function start_payment_record(array $b, string $provider, int $amount): int
{
    q('INSERT INTO payments (booking_id, provider, amount_cents, currency, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
        [$b['id'], $provider, $amount, pay_currency(), 'created', now_sql(), now_sql()]);
    return (int) db()->lastInsertId();
}

/** Mark a payment as paid (only once) and email the client and the business. */
function mark_payment_paid(int $paymentId, string $captureRef = ''): void
{
    $changed = q("UPDATE payments SET status = 'paid', capture_ref = ?, updated_at = ? WHERE id = ? AND status IN ('created','failed')",
        [$captureRef, now_sql(), $paymentId])->rowCount();
    if (!$changed) {
        return; // already recorded (e.g. by the webhook)
    }
    $p = row('SELECT * FROM payments WHERE id = ?', [$paymentId]);
    $b = booking_with_details((int) $p['booking_id']);
    $how = ['stripe' => 'card (Stripe)', 'paypal' => 'PayPal', 'manual' => $p['note'] ?: 'cash / bank transfer'][$p['provider']] ?? $p['provider'];
    send_mail($b['client_email'], 'Payment received for booking #' . $b['id'],
        "Hi " . explode(' ', $b['client_name'])[0] . ",\n\nThanks! We've received your payment of " . money((int) $p['amount_cents'])
        . " $p[currency] by $how.\n\n" . booking_summary_text($b) . "\nYour bookings: " . abs_url('/account/') . mail_footer());
    notify_business('Payment received: booking #' . $b['id'],
        money((int) $p['amount_cents']) . " $p[currency] paid by {$b['client_name']} via $how.\n\n" . booking_summary_text($b)
        . "\n" . abs_url('/admin/booking.php?id=' . $b['id']) . mail_footer());
}

/** Refund a paid payment in full through its provider. */
function refund_payment(array $p): void
{
    if ($p['status'] !== 'paid') {
        throw new RuntimeException('Only paid payments can be refunded.');
    }
    if ($p['provider'] === 'stripe') {
        stripe_api('POST', '/v1/refunds', ['payment_intent' => $p['capture_ref'], 'metadata' => ['booking_id' => (string) $p['booking_id']]]);
    } elseif ($p['provider'] === 'paypal') {
        paypal_api('POST', '/v2/payments/captures/' . rawurlencode($p['capture_ref']) . '/refund', [], ['PayPal-Request-Id: sap-refund-' . $p['id']]);
    }
    // Manual payments: the owner refunds them herself; we just record it.
    q("UPDATE payments SET status = 'refunded', updated_at = ? WHERE id = ?", [now_sql(), $p['id']]);
}
