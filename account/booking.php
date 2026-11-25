<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';

$user = require_login();
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$b = booking_with_details($id);
if (!$b || (int) $b['user_id'] !== (int) $user['id']) {
    http_response_code(404);
    layout_start('Booking not found', ['noindex' => true]);
    page_banner('Booking not found');
    echo '<div class="container sap-narrow"><p><a href="' . e(url('/account/')) . '">Back to my bookings</a></p></div>';
    layout_end();
    exit;
}

if (is_post() && ($_POST['action'] ?? '') === 'cancel') {
    csrf_check();
    if (can_client_cancel($b)) {
        q("UPDATE bookings SET status = 'cancelled', updated_at = ? WHERE id = ?", [now_sql(), $id]);
        notify_business('Booking #' . $id . ' cancelled by client',
            "{$user['name']} cancelled their booking.\n\n" . booking_summary_text($b) . mail_footer(), $user['email']);
        flash('success', 'Your booking has been cancelled.');
    } else {
        flash('error', 'This booking can no longer be cancelled online. Please contact us.');
    }
    redirect('/account/booking.php?id=' . $id);
}

$pets = booking_pets($id);
layout_start('Booking #' . $id, ['noindex' => true]);
page_banner($b['service_name'], booking_when($b));
account_nav('dashboard');
?>
<div class="container sap-narrow">
    <?= render_flashes() ?>
    <div class="sap-card">
        <dl class="sap-dl">
            <dt>Status</dt><dd><?= status_badge($b['status']) ?></dd>
            <dt>Booking</dt><dd>#<?= (int) $b['id'] ?></dd>
            <dt>Service</dt><dd><?= e($b['service_name']) ?></dd>
            <dt>When</dt><dd><?= e(booking_when($b)) ?></dd>
            <dt>Pets</dt><dd><?= e(implode(', ', array_map(fn($p) => $p['name'], $pets)) ?: '-') ?></dd>
            <dt>Price</dt><dd><?= e(money($b['price_cents'] === null ? null : (int) $b['price_cents'])) ?></dd>
            <?php if ($b['notes']): ?><dt>Your notes</dt><dd><?= nl2br(e($b['notes'])) ?></dd><?php endif; ?>
            <?php if ($b['admin_note']): ?><dt>Message from us</dt><dd><?= nl2br(e($b['admin_note'])) ?></dd><?php endif; ?>
        </dl>
        <?php if ($b['status'] === 'pending'): ?>
            <p class="sap-muted">We've received your request and are holding this time for you. You'll get an email once it's confirmed.</p>
        <?php endif; ?>
        <div class="sap-dash-actions">
            <a class="sap-link-btn" href="<?= e(url('/account/')) ?>"><i class="bi bi-arrow-left"></i> All bookings</a>
            <?php if (can_client_cancel($b)): ?>
                <form method="post" data-confirm="Cancel this booking?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $id ?>">
                    <input type="hidden" name="action" value="cancel">
                    <button class="sap-btn-danger" type="submit">Cancel booking</button>
                </form>
            <?php elseif (in_array($b['status'], ACTIVE_STATUSES, true)): ?>
                <p class="sap-muted">Need to change or cancel? It's less than <?= (int) cfg('booking.cancel_notice_hours', 24) ?> hours away, so please <a href="<?= e(url('/#contact')) ?>">contact us</a>.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php layout_end();
