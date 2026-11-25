<?php
/**
 * One-time installer: creates the database tables and the admin (owner) account.
 *
 * Use:  https://yourdomain.com/install.php?key=YOUR_INSTALL_KEY   (install_key from your config file)
 * Then DELETE this file from the server.
 */
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/setup/schema.php';

header('X-Robots-Tag: noindex');

$key = (string) ($_GET['key'] ?? $_POST['key'] ?? '');
$configuredKey = (string) cfg('app.install_key', '');
$errors = [];
$done = false;

if (!empty(config_all()['_sample'])) {
    exit('No config file found yet. Copy config/config.sample.php to sap-config.php (one folder above public_html) and fill it in first.');
}
if ($configuredKey === '' || $configuredKey === 'change-me-to-a-long-random-string' || strlen($configuredKey) < 16) {
    exit('Set a long random install_key (16+ characters) in your config file first.');
}
if (!hash_equals($configuredKey, $key)) {
    http_response_code(404);
    exit('Not found.');
}

try {
    $pdo = db();
    foreach (schema_statements(db_driver()) as $stmt) {
        $pdo->exec($stmt);
    }
    seed_defaults($pdo);
    $hasAdmin = (bool) scalar("SELECT 1 FROM users WHERE role = 'admin' LIMIT 1");
} catch (Throwable $e) {
    exit('Database error: ' . e($e->getMessage()) . '<br>Check the db settings in your config file.');
}

if ($hasAdmin) {
    exit('Already installed. An admin account exists. Please delete install.php from the server now.');
}

if (is_post()) {
    $name  = input('name', 120);
    $email = strtolower(input('email', 190));
    $pw    = (string) ($_POST['password'] ?? '');
    if ($name === '') {
        $errors[] = 'Enter a name.';
    }
    if (!valid_email($email)) {
        $errors[] = 'Enter a valid email.';
    }
    if ($p = password_problem($pw)) {
        $errors[] = $p;
    }
    if (!$errors) {
        $existing = row('SELECT id FROM users WHERE email = ?', [$email]);
        if ($existing) {
            q("UPDATE users SET role = 'admin', password_hash = ? WHERE id = ?", [password_hash($pw, PASSWORD_DEFAULT), $existing['id']]);
        } else {
            q("INSERT INTO users (role, name, email, password_hash, created_at) VALUES ('admin', ?, ?, ?, ?)",
                [$name, $email, password_hash($pw, PASSWORD_DEFAULT), now_sql()]);
        }
        $done = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Install – Something About Pets</title>
<style>
body{font-family:system-ui,sans-serif;max-width:480px;margin:60px auto;padding:0 20px;color:#1b2a2f}
label{display:block;margin:14px 0 4px;font-weight:600}input{width:100%;padding:10px;border:1px solid #ccc;border-radius:6px;box-sizing:border-box}
button{margin-top:20px;padding:12px 20px;background:#e83a30;color:#fff;border:0;border-radius:6px;font-weight:600;cursor:pointer}
.err{background:#fdecea;color:#8a1c14;padding:10px;border-radius:6px}.ok{background:#e8f6ec;color:#1d5b2c;padding:14px;border-radius:6px}
</style>
</head>
<body>
<h1>Set up Something About Pets</h1>
<?php if ($done): ?>
    <p class="ok"><strong>Done!</strong> Tables are created and your admin account is ready.<br><br>
    1. <strong>Delete install.php from the server now.</strong><br>
    2. <a href="<?= e(url('/login.php')) ?>">Log in</a>, then open <em>Admin → Services</em> to add prices and <em>Admin → Availability</em> to set your hours.</p>
<?php else: ?>
    <p>Database tables are ready. Create the owner (admin) account:</p>
    <?php foreach ($errors as $er): ?><p class="err"><?= e($er) ?></p><?php endforeach; ?>
    <form method="post">
        <input type="hidden" name="key" value="<?= e($key) ?>">
        <label for="n">Your name</label><input id="n" name="name" required value="<?= e($_POST['name'] ?? '') ?>">
        <label for="m">Email</label><input id="m" name="email" type="email" required value="<?= e($_POST['email'] ?? '') ?>">
        <label for="p">Password (8+ characters)</label><input id="p" name="password" type="password" minlength="8" required>
        <button type="submit">Create admin account</button>
    </form>
<?php endif; ?>
</body>
</html>
