<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';

$user = require_login();
$all = rows(
    'SELECT b.*, s.name AS service_name, s.kind FROM bookings b JOIN services s ON s.id = b.service_id
     WHERE b.user_id = ? ORDER BY b.start_at DESC',
    [$user['id']]
);
$now = now_sql();
$upcoming = array_reverse(array_values(array_filter($all, fn($b) => $b['end_at'] >= $now && in_array($b['status'], ACTIVE_STATUSES, true))));
$past = array_values(array_filter($all, fn($b) => !($b['end_at'] >= $now && in_array($b['status'], ACTIVE_STATUSES, true))));
$petCount = (int) scalar('SELECT COUNT(*) FROM pets WHERE user_id = ?', [$user['id']]);

function booking_rows(array $list): void
{
    foreach ($list as $b) {
        $pets = implode(', ', array_map(fn($p) => $p['name'], booking_pets((int) $b['id'])));
        echo '<a class="sap-booking-row" href="' . e(url('/account/booking.php?id=' . (int) $b['id'])) . '">'
            . '<span class="sap-booking-main"><strong>' . e($b['service_name']) . '</strong>'
            . '<span>' . e(booking_when($b)) . '</span>'
            . ($pets ? '<span class="sap-muted">' . e($pets) . '</span>' : '') . '</span>'
            . '<span class="sap-badges">' . status_badge($b['status'])
            . (can_pay_online($b) ? '<span class="sap-badge sap-badge-pending">Pay now</span>' : '') . '</span></a>';
    }
}

layout_start('My account', ['noindex' => true]);
page_banner('Hi, ' . explode(' ', $user['name'])[0] . '!', 'Here are your bookings.');
account_nav('dashboard');
?>
<div class="container sap-wide">
    <?= render_flashes() ?>
    <div class="sap-dash-actions">
        <a class="primary-btn2" href="<?= e(url('/book.php')) ?>">Book a service</a>
        <?php if (!$petCount): ?><a class="sap-link-btn" href="<?= e(url('/account/pets.php?add=1')) ?>">Add your first pet <i class="bi bi-arrow-right"></i></a><?php endif; ?>
    </div>

    <section class="sap-card">
        <h2>Upcoming</h2>
        <?php if ($upcoming): ?>
            <div class="sap-booking-list"><?php booking_rows($upcoming); ?></div>
        <?php else: ?>
            <p class="sap-muted">No upcoming bookings. <a href="<?= e(url('/book.php')) ?>">Book one now</a>.</p>
        <?php endif; ?>
    </section>

    <?php if ($past): ?>
    <section class="sap-card">
        <h2>Past and cancelled</h2>
        <div class="sap-booking-list"><?php booking_rows($past); ?></div>
    </section>
    <?php endif; ?>
</div>
<?php layout_end();
