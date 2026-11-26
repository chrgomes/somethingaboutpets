<?php
declare(strict_types=1);

/** Escape for HTML output. */
function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Site-relative URL, honouring app.base_path. url('/login.php') */
function url(string $path = '/'): string
{
    $base = rtrim((string) cfg('app.base_path', ''), '/');
    return $base . '/' . ltrim($path, '/');
}

/** Absolute URL for emails. */
function abs_url(string $path = '/'): string
{
    $root = rtrim((string) cfg('app.url', ''), '/');
    if ($root === '' && isset($_SERVER['HTTP_HOST'])) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $root = $scheme . '://' . $_SERVER['HTTP_HOST'] . rtrim((string) cfg('app.base_path', ''), '/');
    }
    return $root . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $file = APP_ROOT . '/' . ltrim($path, '/');
    $v = is_file($file) ? (string) filemtime($file) : '1';
    return url($path) . '?v=' . $v;
}

function redirect(string $path): never
{
    if (!preg_match('#^https?://#', $path)) {
        $path = url($path);
    }
    header('Location: ' . $path, true, 303);
    exit;
}

/** Only allow redirects back into this site (prevents open redirects). */
function safe_next(?string $next, string $fallback = '/account/'): string
{
    $next = (string) $next;
    if ($next === '' || !preg_match('#^/[A-Za-z0-9/_\-.?=&%\#]*$#', $next) || str_starts_with($next, '//')) {
        return $fallback;
    }
    return $next;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function wants_json(): bool
{
    return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
        || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $sent = (string) ($_POST['_csrf'] ?? '');
    if ($sent === '' || !hash_equals(csrf_token(), $sent)) {
        if (wants_json()) {
            json_response(['ok' => false, 'message' => 'Your session expired. Please refresh the page and try again.'], 419);
        }
        flash('error', 'Your session expired. Please try again.');
        redirect($_SERVER['HTTP_REFERER'] ?? '/');
    }
}

/* ---------- Flash messages + old input ---------- */

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function render_flashes(): string
{
    $html = '';
    foreach (flashes() as $f) {
        $cls = $f['type'] === 'error' ? 'sap-alert-error' : 'sap-alert-success';
        $html .= '<div class="sap-alert ' . $cls . '" role="alert">' . e($f['message']) . '</div>';
    }
    return $html;
}

function keep_old(array $data): void
{
    $_SESSION['old'] = $data;
}

function old(string $key, string $default = ''): string
{
    return (string) ($_SESSION['old'][$key] ?? $default);
}

function clear_old(): void
{
    unset($_SESSION['old']);
}

/* ---------- Validation helpers ---------- */

function input(string $key, int $max = 255): string
{
    $v = trim((string) ($_POST[$key] ?? ''));
    $v = str_replace("\0", '', $v);
    return mb_substr($v, 0, $max);
}

function valid_email(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL) && !preg_match('/[\r\n]/', $email);
}

/** Simple session-based rate limit. Returns false when the limit is hit. */
function rate_limit(string $bucket, int $max, int $seconds): bool
{
    $now = time();
    $hits = array_filter($_SESSION['rl'][$bucket] ?? [], fn($t) => $t > $now - $seconds);
    if (count($hits) >= $max) {
        $_SESSION['rl'][$bucket] = $hits;
        return false;
    }
    $hits[] = $now;
    $_SESSION['rl'][$bucket] = $hits;
    return true;
}

/** Honeypot + minimum fill time check for public forms. */
function looks_like_bot(): bool
{
    if (trim((string) ($_POST['website'] ?? '')) !== '') {
        return true;
    }
    $started = (int) ($_POST['_ts'] ?? 0);
    return $started > 0 && (time() - $started) < 3;
}

function bot_fields(): string
{
    return '<div class="sap-hp" aria-hidden="true"><label>Leave this empty<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>'
        . '<input type="hidden" name="_ts" value="' . time() . '">';
}

/* ---------- Formatting ---------- */

function money(?int $cents): string
{
    if ($cents === null) {
        return 'Price on request';
    }
    return cfg('booking.currency_symbol', '$') . number_format($cents / 100, 2);
}

function fmt_date(string $dt): string
{
    return date('D j M Y', strtotime($dt));
}

function fmt_time(string $dt): string
{
    return date('g:i a', strtotime($dt));
}

function fmt_datetime(string $dt): string
{
    return date('D j M Y, g:i a', strtotime($dt));
}

function status_badge(string $status): string
{
    $labels = [
        'pending'   => 'Awaiting approval',
        'confirmed' => 'Confirmed',
        'declined'  => 'Declined',
        'cancelled' => 'Cancelled',
        'completed' => 'Completed',
    ];
    return '<span class="sap-badge sap-badge-' . e($status) . '">' . e($labels[$status] ?? ucfirst($status)) . '</span>';
}

function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

/** Every date from $from to $to inclusive ('Y-m-d'), safe across daylight-saving changes. */
function date_range(string $from, string $to): array
{
    $out = [];
    $d = new DateTimeImmutable($from);
    $end = new DateTimeImmutable($to);
    while ($d <= $end && count($out) < 800) {
        $out[] = $d->format('Y-m-d');
        $d = $d->modify('+1 day');
    }
    return $out;
}
