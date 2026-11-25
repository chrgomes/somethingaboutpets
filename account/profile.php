<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';

$user = require_login();
$errors = [];
$v = $user;

if (is_post()) {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'details') {
        $v = [
            'name'    => input('name', 120),
            'email'   => strtolower(input('email', 190)),
            'phone'   => input('phone', 40),
            'address' => input('address', 255),
        ] + $user;
        if ($v['name'] === '') {
            $errors[] = 'Please enter your name.';
        }
        if (!valid_email($v['email'])) {
            $errors[] = 'Please enter a valid email address.';
        } elseif (row('SELECT id FROM users WHERE email = ? AND id <> ?', [$v['email'], $user['id']])) {
            $errors[] = 'Another account already uses that email.';
        }
        if (!$errors) {
            q('UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?',
                [$v['name'], $v['email'], $v['phone'], $v['address'], $user['id']]);
            flash('success', 'Your details have been saved.');
            redirect('/account/profile.php');
        }
    }

    if ($action === 'password') {
        $hash = (string) scalar('SELECT password_hash FROM users WHERE id = ?', [$user['id']]);
        $new = (string) ($_POST['new_password'] ?? '');
        if (!password_verify((string) ($_POST['current_password'] ?? ''), $hash)) {
            $errors[] = 'Your current password is not right.';
        } elseif ($p = password_problem($new)) {
            $errors[] = $p;
        } elseif ($new !== (string) ($_POST['new_password_confirm'] ?? '')) {
            $errors[] = 'The new passwords don\'t match.';
        } else {
            q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            session_regenerate_id(true);
            flash('success', 'Your password has been changed.');
            redirect('/account/profile.php');
        }
    }
}

layout_start('Profile', ['noindex' => true]);
page_banner('Your profile');
if ($user['role'] === 'admin') {
    admin_nav('');
} else {
    account_nav('profile');
}
?>
<div class="container sap-wide">
    <?= render_flashes() ?>
    <?php foreach ($errors as $er): ?><div class="sap-alert sap-alert-error" role="alert"><?= e($er) ?></div><?php endforeach; ?>
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="sap-card">
                <h2>Contact details</h2>
                <form method="post" class="sap-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="details">
                    <label for="name">Full name</label>
                    <input id="name" name="name" required maxlength="120" value="<?= e($v['name']) ?>">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" required maxlength="190" value="<?= e($v['email']) ?>">
                    <label for="phone">Phone</label>
                    <input id="phone" type="tel" name="phone" maxlength="40" value="<?= e($v['phone']) ?>">
                    <label for="address">Address</label>
                    <input id="address" name="address" maxlength="255" value="<?= e($v['address']) ?>">
                    <button class="primary-btn2" type="submit">Save details</button>
                </form>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="sap-card">
                <h2>Change password</h2>
                <form method="post" class="sap-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="password">
                    <label for="current_password">Current password</label>
                    <input id="current_password" type="password" name="current_password" required autocomplete="current-password">
                    <label for="new_password">New password</label>
                    <input id="new_password" type="password" name="new_password" required minlength="8" autocomplete="new-password">
                    <label for="new_password_confirm">Confirm new password</label>
                    <input id="new_password_confirm" type="password" name="new_password_confirm" required minlength="8" autocomplete="new-password">
                    <button class="primary-btn2" type="submit">Change password</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php layout_end();
