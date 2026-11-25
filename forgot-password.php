<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$sent = false;
if (is_post()) {
    csrf_check();
    $email = strtolower(input('email', 190));
    if (valid_email($email) && rate_limit('reset', 5, 3600)) {
        $user = row('SELECT id, name, email FROM users WHERE email = ?', [$email]);
        if ($user) {
            $token = bin2hex(random_bytes(32));
            q('DELETE FROM password_resets WHERE user_id = ?', [$user['id']]);
            q('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)',
                [$user['id'], hash('sha256', $token), date('Y-m-d H:i:s', time() + 3600)]);
            send_mail($user['email'], 'Reset your password',
                "Hi {$user['name']},\n\nSomeone (hopefully you) asked to reset your password. Use this link within 1 hour:\n"
                . abs_url('/reset-password.php?token=' . $token)
                . "\n\nIf you didn't ask for this, you can ignore this email." . mail_footer());
        }
    }
    // Same message either way, so the form can't be used to find out who has an account.
    $sent = true;
}

layout_start('Forgot password', ['noindex' => true]);
page_banner('Reset your password');
?>
<div class="container sap-narrow">
    <div class="sap-card">
        <?php if ($sent): ?>
            <div class="sap-alert sap-alert-success">If there's an account for that email, we've sent a link to reset the password. Check your inbox (and spam folder).</div>
        <?php else: ?>
            <p>Enter the email you signed up with and we'll send you a link to choose a new password.</p>
            <form method="post" class="sap-form">
                <?= csrf_field() ?>
                <label for="email">Email</label>
                <input id="email" type="email" name="email" required autocomplete="email">
                <button class="primary-btn2 sap-btn-block" type="submit">Send reset link</button>
            </form>
        <?php endif; ?>
        <p class="sap-form-foot"><a href="<?= e(url('/login.php')) ?>">Back to log in</a></p>
    </div>
</div>
<?php layout_end();
