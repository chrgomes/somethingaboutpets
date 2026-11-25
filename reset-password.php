<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
$reset = null;
if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $reset = row('SELECT * FROM password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at > ?',
        [hash('sha256', $token), now_sql()]);
}

$error = '';
if ($reset && is_post()) {
    csrf_check();
    $pw = (string) ($_POST['password'] ?? '');
    if ($p = password_problem($pw)) {
        $error = $p;
    } elseif ($pw !== (string) ($_POST['password_confirm'] ?? '')) {
        $error = 'The two passwords don\'t match.';
    } else {
        q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($pw, PASSWORD_DEFAULT), $reset['user_id']]);
        q('UPDATE password_resets SET used_at = ? WHERE id = ?', [now_sql(), $reset['id']]);
        $user = row('SELECT * FROM users WHERE id = ?', [$reset['user_id']]);
        clear_failed_logins($user['email']);
        login_user($user);
        flash('success', 'Your password has been changed.');
        redirect($user['role'] === 'admin' ? '/admin/' : '/account/');
    }
}

layout_start('Choose a new password', ['noindex' => true]);
page_banner('Choose a new password');
?>
<div class="container sap-narrow">
    <div class="sap-card">
        <?php if (!$reset): ?>
            <div class="sap-alert sap-alert-error">This reset link is invalid or has expired.</div>
            <p class="sap-form-foot"><a href="<?= e(url('/forgot-password.php')) ?>">Send a new link</a></p>
        <?php else: ?>
            <?php if ($error): ?><div class="sap-alert sap-alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
            <form method="post" class="sap-form">
                <?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <label for="password">New password <span class="sap-muted">(8+ characters)</span></label>
                <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password">
                <label for="password_confirm">Confirm new password</label>
                <input id="password_confirm" type="password" name="password_confirm" required minlength="8" autocomplete="new-password">
                <button class="primary-btn2 sap-btn-block" type="submit">Save password</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php layout_end();
