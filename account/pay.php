<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';

$user = require_login();
if (!is_post()) {
    redirect('/account/');
}
csrf_check();

$id = (int) ($_POST['id'] ?? 0);
$provider = (string) ($_POST['provider'] ?? '');
$b = booking_with_details($id);
if (!$b || (int) $b['user_id'] !== (int) $user['id']) {
    redirect('/account/');
}
if (!can_pay_online($b)) {
    flash('error', 'This booking can\'t be paid online right now.');
    redirect('/account/booking.php?id=' . $id);
}
if (!rate_limit('pay', 10, 600)) {
    flash('error', 'Please wait a few minutes before trying again.');
    redirect('/account/booking.php?id=' . $id);
}

try {
    if ($provider === 'stripe' && stripe_enabled()) {
        $to = stripe_start_checkout($b, $user);
    } elseif ($provider === 'paypal' && paypal_enabled()) {
        $to = paypal_start_checkout($b);
    } else {
        throw new RuntimeException('That payment option is not available.');
    }
} catch (Throwable $e) {
    error_log('[sap pay] ' . $e->getMessage());
    flash('error', 'Sorry, we couldn\'t start the payment. Please try again or choose another payment method.');
    redirect('/account/booking.php?id=' . $id);
}
header('Location: ' . $to, true, 303);
exit;
