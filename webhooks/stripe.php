<?php
/**
 * Stripe webhook: Stripe calls this when a checkout is paid or a charge is refunded,
 * so payments are recorded even if the client closes the page before returning.
 * Endpoint: https://yourdomain.com/webhooks/stripe.php
 * Events:   checkout.session.completed, checkout.session.async_payment_succeeded, charge.refunded
 */
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';

$payload = (string) file_get_contents('php://input');
if (!stripe_verify_webhook($payload, (string) ($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? ''))) {
    http_response_code(400);
    exit('Bad signature');
}
$event = json_decode($payload, true);
$obj = $event['data']['object'] ?? [];

try {
    switch ($event['type'] ?? '') {
        case 'checkout.session.completed':
        case 'checkout.session.async_payment_succeeded':
            if (($obj['payment_status'] ?? '') === 'paid') {
                $p = row("SELECT * FROM payments WHERE provider = 'stripe' AND provider_ref = ?", [(string) ($obj['id'] ?? '')]);
                if ($p && (int) ($obj['amount_total'] ?? 0) === (int) $p['amount_cents']) {
                    mark_payment_paid((int) $p['id'], (string) ($obj['payment_intent'] ?? ''));
                }
            }
            break;
        case 'charge.refunded':
            if (!empty($obj['refunded'])) {
                q("UPDATE payments SET status = 'refunded', updated_at = ? WHERE provider = 'stripe' AND capture_ref = ? AND status = 'paid'",
                    [now_sql(), (string) ($obj['payment_intent'] ?? '')]);
            }
            break;
    }
} catch (Throwable $e) {
    error_log('[sap stripe webhook] ' . $e->getMessage());
    http_response_code(500);
    exit('Error');
}
http_response_code(200);
echo 'ok';
