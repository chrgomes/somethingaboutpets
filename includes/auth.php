<?php
declare(strict_types=1);

function current_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    $id = (int) ($_SESSION['uid'] ?? 0);
    $user = $id ? row('SELECT id, role, name, email, phone, address FROM users WHERE id = ?', [$id]) : null;
    if (!$user) {
        unset($_SESSION['uid']);
    }
    return $user;
}

function is_admin(): bool
{
    return (current_user()['role'] ?? '') === 'admin';
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        $next = $_SERVER['REQUEST_URI'] ?? '/account/';
        $base = rtrim((string) cfg('app.base_path', ''), '/');
        if ($base !== '' && str_starts_with($next, $base)) {
            $next = substr($next, strlen($base));
        }
        flash('error', 'Please log in to continue.');
        redirect('/login.php?next=' . rawurlencode($next));
    }
    return $user;
}

function require_admin(): array
{
    $user = require_login();
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        exit('Not allowed.');
    }
    return $user;
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $user['id'];
    q('UPDATE users SET last_login_at = ? WHERE id = ?', [now_sql(), $user['id']]);
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** Too many failed logins for this email or IP in the last 15 minutes? */
function login_throttled(string $email): bool
{
    $since = date('Y-m-d H:i:s', time() - 15 * 60);
    $n = (int) scalar(
        'SELECT COUNT(*) FROM login_attempts WHERE attempted_at > ? AND (email = ? OR ip = ?)',
        [$since, strtolower($email), client_ip()]
    );
    return $n >= 8;
}

function record_failed_login(string $email): void
{
    q('INSERT INTO login_attempts (email, ip, attempted_at) VALUES (?, ?, ?)', [strtolower($email), client_ip(), now_sql()]);
}

function clear_failed_logins(string $email): void
{
    q('DELETE FROM login_attempts WHERE email = ?', [strtolower($email)]);
}

function password_problem(string $pw): ?string
{
    if (mb_strlen($pw) < 8) {
        return 'Password must be at least 8 characters.';
    }
    if (mb_strlen($pw) > 200) {
        return 'Password is too long.';
    }
    return null;
}
