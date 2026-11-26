<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$next = safe_next($_GET['next'] ?? $_POST['next'] ?? '', '');
if ($u = current_user()) {
    redirect($next ?: ($u['role'] === 'admin' ? '/admin/' : '/account/'));
}

$error = '';
$email = '';
if (is_post()) {
    csrf_check();
    $email = strtolower(input('email', 190));
    $pw = (string) ($_POST['password'] ?? '');
    if (login_throttled($email)) {
        $error = 'Too many attempts. Please wait 15 minutes, or reset your password.';
    } else {
        $user = valid_email($email) ? row('SELECT * FROM users WHERE email = ?', [$email]) : null;
        if ($user && password_verify($pw, $user['password_hash'])) {
            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($pw, PASSWORD_DEFAULT), $user['id']]);
            }
            clear_failed_logins($email);
            login_user($user);
            redirect($next ?: ($user['role'] === 'admin' ? '/admin/' : '/account/'));
        }
        record_failed_login($email);
        $error = 'That email and password don\'t match. Please try again.';
    }
}

layout_start('Log in', ['noindex' => true]);
page_banner('Welcome back', 'Log in to book and manage your pets\' care.');
?>
<div class="container sap-narrow">
    <div class="sap-card">
        <?= render_flashes() ?>
        <?php if ($error): ?><div class="sap-alert sap-alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
        <form method="post" class="sap-form">
            <?= csrf_field() ?>
            <input type="hidden" name="next" value="<?= e($next) ?>">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" required autocomplete="email" value="<?= e($email) ?>">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" required autocomplete="current-password">
            <button class="primary-btn2 sap-btn-block" type="submit">Log in</button>
        </form>
        <p class="sap-form-foot">
            <a href="<?= e(url('/forgot-password.php')) ?>">Forgot your password?</a><br>
            New here? <a href="<?= e(url('/register.php' . ($next ? '?next=' . rawurlencode($next) : ''))) ?>">Create an account</a>
        </p>
    </div>
</div>
<?php layout_end();
