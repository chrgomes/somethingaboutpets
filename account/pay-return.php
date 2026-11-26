<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';

// Clients land here after paying on Stripe or PayPal.
$user = require_login();
$provider = (string) ($_GET['provider'] ?? '');
$bookingId = null;

try {
    if ($provider === 'stripe' && preg_match('/^cs_[A-Za-z0-9_]+$/', (string) ($_GET['session_id'] ?? ''))) {
        $bookingId = stripe_confirm_session((string) $_GET['session_id']);
    } elseif ($provider === 'paypal' && preg_match('/^[A-Z0-9]+$/', (string) ($_GET['token'] ?? ''))) {
        $bookingId = paypal_capture((string) $_GET['token']);
    }
} catch (Throwable $e) {
    error_log('[sap pay-return] ' . $e->getMessage());
}

$b = $bookingId ? booking_with_details($bookingId) : null;
if (!$b || (int) $b['user_id'] !== (int) $user['id']) {
    flash('error', 'We couldn\'t confirm that payment. If you were charged, please contact us and we\'ll sort it out.');
    redirect('/account/');
}
if (payment_state($b) === 'paid') {
    flash('success', 'Payment received. Thank you! A receipt is on its way to your email.');
} else {
    flash('error', 'Your payment hasn\'t gone through yet. If you were charged, it will show here shortly.');
}
redirect('/account/booking.php?id=' . (int) $b['id']);
