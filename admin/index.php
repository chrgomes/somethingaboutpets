<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';

$admin = require_admin();
$now = now_sql();
$baseSql = 'SELECT b.*, s.name AS service_name, s.kind, u.name AS client_name FROM bookings b
            JOIN services s ON s.id = b.service_id JOIN users u ON u.id = b.user_id';

$pending = rows("$baseSql WHERE b.status = 'pending' AND b.end_at >= ? ORDER BY b.start_at", [$now]);
$weekEnd = date('Y-m-d 23:59:59', strtotime('+7 days'));
$week = rows("$baseSql WHERE b.status = 'confirmed' AND b.end_at >= ? AND b.start_at <= ? ORDER BY b.start_at", [$now, $weekEnd]);
$stats = [
    'Clients'          => (int) scalar("SELECT COUNT(*) FROM users WHERE role = 'client'"),
    'Upcoming'         => (int) scalar("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed' AND end_at >= ?", [$now]),
    'Awaiting you'     => count($pending),
    'Unread messages'  => (int) scalar('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0'),
];
$noPrices = (int) scalar('SELECT COUNT(*) FROM services WHERE active = 1 AND price_cents IS NULL');

layout_start('Admin', ['noindex' => true]);
page_banner('Admin', 'Welcome back, ' . explode(' ', $admin['name'])[0] . '.');
admin_nav('dashboard');
?>
<div class="container sap-wide">
    <?= render_flashes() ?>
    <?php if ($noPrices): ?>
        <div class="sap-alert sap-alert-info"><?= $noPrices ?> service<?= $noPrices > 1 ? 's show' : ' shows' ?> "Price on request". <a href="<?= e(url('/admin/services.php')) ?>">Add prices</a></div>
    <?php endif; ?>

    <div class="sap-stats">
        <?php foreach ($stats as $label => $n): ?>
            <div class="sap-stat"><span class="sap-stat-n"><?= $n ?></span><span class="sap-stat-l"><?= e($label) ?></span></div>
        <?php endforeach; ?>
    </div>

    <section class="sap-card">
        <h2>Waiting for your approval</h2>
        <?php if (!$pending): ?>
            <p class="sap-muted">Nothing waiting. 🎉</p>
        <?php else: ?>
            <div class="sap-booking-list">
                <?php foreach ($pending as $b): ?>
                    <a class="sap-booking-row" href="<?= e(url('/admin/booking.php?id=' . (int) $b['id'])) ?>">
                        <span class="sap-booking-main"><strong><?= e($b['service_name']) ?> · <?= e($b['client_name']) ?></strong><span><?= e(booking_when($b)) ?></span></span>
                        <?= status_badge($b['status']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="sap-card">
        <h2>Next 7 days</h2>
        <?php if (!$week): ?>
            <p class="sap-muted">No confirmed bookings in the next week.</p>
        <?php else: ?>
            <div class="sap-booking-list">
                <?php foreach ($week as $b): ?>
                    <a class="sap-booking-row" href="<?= e(url('/admin/booking.php?id=' . (int) $b['id'])) ?>">
                        <span class="sap-booking-main"><strong><?= e(booking_when($b)) ?></strong><span><?= e($b['service_name']) ?> · <?= e($b['client_name']) ?></span></span>
                        <?= status_badge($b['status']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php layout_end();
