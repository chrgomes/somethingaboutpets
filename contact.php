<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

if (!is_post()) {
    redirect('/#contact');
}
csrf_check();

$reply = function (bool $ok, string $message, int $status = 200) {
    if (wants_json()) {
        json_response(['ok' => $ok, 'message' => $message], $status);
    }
    flash($ok ? 'success' : 'error', $message);
    redirect('/#contact');
};

// Quietly accept obvious bots so they don't retry.
if (looks_like_bot()) {
    $reply(true, 'Thanks! Your message has been sent.');
}
if (!rate_limit('contact', 5, 600)) {
    $reply(false, 'You have sent a few messages already. Please wait a few minutes and try again.', 429);
}

$name    = input('name', 120);
$email   = input('email', 190);
$subject = input('subject', 190);
$message = input('message', 5000);

$errors = [];
if ($name === '') {
    $errors[] = 'your name';
}
if (!valid_email($email)) {
    $errors[] = 'a valid email address';
}
if (mb_strlen($message) < 5) {
    $errors[] = 'a message';
}
if ($errors) {
    keep_old(compact('name', 'email', 'subject', 'message'));
    $reply(false, 'Please enter ' . implode(', ', $errors) . '.', 422);
}

$userId = null;
$saved = false;
try {
    $userId = current_user()['id'] ?? null;
    q('INSERT INTO contact_messages (user_id, name, email, subject, message, created_at) VALUES (?, ?, ?, ?, ?, ?)',
        [$userId, $name, $email, $subject, $message, now_sql()]);
    $saved = true;
} catch (Throwable $e) {
    error_log('[sap contact] DB save failed: ' . $e->getMessage());
}

$sent = notify_business(
    'Website message: ' . ($subject !== '' ? $subject : 'from ' . $name),
    "New message from the website contact form.\n\nName: $name\nEmail: $email\nSubject: $subject\n\n$message\n\n"
        . ($saved ? 'View all messages: ' . abs_url('/admin/messages.php') : '') . mail_footer(),
    $email
);

if (!$saved && !$sent) {
    keep_old(compact('name', 'email', 'subject', 'message'));
    $reply(false, 'Sorry, your message could not be sent. Please email us at ' . cfg('business.email') . '.', 500);
}
clear_old();
$reply(true, "Thanks, $name! Your message has been sent. We'll get back to you soon.");
