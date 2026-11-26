<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$next = safe_next($_GET['next'] ?? $_POST['next'] ?? '', '');
if (current_user()) {
    redirect($next ?: '/account/');
}

$errors = [];
$v = ['name' => '', 'email' => '', 'phone' => '', 'address' => ''];

if (is_post()) {
    csrf_check();
    $v = [
        'name'    => input('name', 120),
        'email'   => strtolower(input('email', 190)),
        'phone'   => input('phone', 40),
        'address' => input('address', 255),
    ];
    $pw  = (string) ($_POST['password'] ?? '');
    $pw2 = (string) ($_POST['password_confirm'] ?? '');

    if (looks_like_bot() || !rate_limit('register', 5, 3600)) {
        $errors[] = 'Please wait a moment and try again.';
    }
    if ($v['name'] === '') {
        $errors[] = 'Please enter your name.';
    }
    if (!valid_email($v['email'])) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($v['phone'] === '') {
        $errors[] = 'Please enter a phone number so we can reach you about your pets.';
    }
    if ($p = password_problem($pw)) {
        $errors[] = $p;
    } elseif ($pw !== $pw2) {
        $errors[] = 'The two passwords don\'t match.';
    }
    if (empty($_POST['agree'])) {
        $errors[] = 'Please agree to the terms and privacy policy.';
    }
    if (!$errors && row('SELECT id FROM users WHERE email = ?', [$v['email']])) {
        $errors[] = 'There is already an account with that email. Try logging in or resetting your password.';
    }

    if (!$errors) {
        q("INSERT INTO users (role, name, email, phone, address, password_hash, created_at) VALUES ('client', ?, ?, ?, ?, ?, ?)",
            [$v['name'], $v['email'], $v['phone'], $v['address'], password_hash($pw, PASSWORD_DEFAULT), now_sql()]);
        $user = row('SELECT * FROM users WHERE email = ?', [$v['email']]);
        login_user($user);
        send_mail($v['email'], 'Welcome to ' . cfg('app.name'),
            "Hi {$v['name']},\n\nThanks for creating an account. You can now add your pets and book online:\n" . abs_url('/book.php') . mail_footer());
        flash('success', 'Welcome! Your account is ready. Add your pets to get started.');
        redirect($next ?: '/account/pets.php?add=1');
    }
}

layout_start('Create an account', ['noindex' => true]);
page_banner('Create your account', 'Book online, keep your pets\' details in one place and see all your bookings.');
?>
<div class="container sap-narrow">
    <div class="sap-card">
        <?php foreach ($errors as $er): ?><div class="sap-alert sap-alert-error" role="alert"><?= e($er) ?></div><?php endforeach; ?>
        <form method="post" class="sap-form">
            <?= csrf_field() ?>
            <?= bot_fields() ?>
            <input type="hidden" name="next" value="<?= e($next) ?>">
            <label for="name">Full name</label>
            <input id="name" name="name" required maxlength="120" autocomplete="name" value="<?= e($v['name']) ?>">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" required maxlength="190" autocomplete="email" value="<?= e($v['email']) ?>">
            <label for="phone">Phone</label>
            <input id="phone" type="tel" name="phone" required maxlength="40" autocomplete="tel" value="<?= e($v['phone']) ?>">
            <label for="address">Address <span class="sap-muted">(where we'll care for your pets)</span></label>
            <input id="address" name="address" maxlength="255" autocomplete="street-address" value="<?= e($v['address']) ?>">
            <label for="password">Password <span class="sap-muted">(8+ characters)</span></label>
            <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password">
            <label for="password_confirm">Confirm password</label>
            <input id="password_confirm" type="password" name="password_confirm" required minlength="8" autocomplete="new-password">
            <label class="sap-check">
                <input type="checkbox" name="agree" value="1" required <?= !empty($_POST['agree']) ? 'checked' : '' ?>>
                I agree to the <a href="<?= e(url('/terms.php')) ?>" target="_blank">terms</a>, <a href="<?= e(url('/payments.php')) ?>" target="_blank">payment policy</a> and <a href="<?= e(url('/privacy.php')) ?>" target="_blank">privacy policy</a>.
            </label>
            <button class="primary-btn2 sap-btn-block" type="submit">Create account</button>
        </form>
        <p class="sap-form-foot">Already have an account? <a href="<?= e(url('/login.php' . ($next ? '?next=' . rawurlencode($next) : ''))) ?>">Log in</a></p>
    </div>
</div>
<?php layout_end();
