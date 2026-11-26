<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';

require_admin();
$filters = [
    'upcoming'  => 'Upcoming',
    'pending'   => 'Awaiting approval',
    'past'      => 'Past',
    'cancelled' => 'Cancelled / declined',
    'all'       => 'All',
];
$f = array_key_exists($_GET['f'] ?? '', $filters) ? $_GET['f'] : 'upcoming';
$now = now_sql();
$where = match ($f) {
    'upcoming'  => ["b.status IN ('pending','confirmed') AND b.end_at >= ?", [$now], 'ASC'],
    'pending'   => ["b.status = 'pending'", [], 'ASC'],
    'past'      => ["(b.end_at < ? AND b.status IN ('confirmed','completed')) OR b.status = 'completed'", [$now], 'DESC'],
    'cancelled' => ["b.status IN ('cancelled','declined')", [], 'DESC'],
    default     => ['1 = 1', [], 'DESC'],
};
$list = rows(
    "SELECT b.*, s.name AS service_name, s.kind, u.name AS client_name FROM bookings b
     JOIN services s ON s.id = b.service_id JOIN users u ON u.id = b.user_id
     WHERE {$where[0]} ORDER BY b.start_at {$where[2]} LIMIT 300",
    $where[1]
);

layout_start('Bookings', ['noindex' => true]);
page_banner('Bookings');
admin_nav('bookings');
?>
<div class="container sap-wide">
    <?= render_flashes() ?>
    <div class="sap-pills">
        <?php foreach ($filters as $k => $lbl): ?>
            <a class="<?= $k === $f ? 'active' : '' ?>" href="<?= e(url('/admin/bookings.php?f=' . $k)) ?>"><?= e($lbl) ?></a>
        <?php endforeach; ?>
    </div>
    <div class="sap-card">
        <?php if (!$list): ?>
            <p class="sap-muted">No bookings here.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="sap-table sap-stack">
                <thead><tr><th>#</th><th>When</th><th>Service</th><th>Client</th><th>Status</th><th>Payment</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($list as $b): ?>
                    <tr>
                        <td><?= (int) $b['id'] ?></td>
                        <td><?= e(booking_when($b)) ?></td>
                        <td><?= e($b['service_name']) ?></td>
                        <td><?= e($b['client_name']) ?></td>
                        <td><?= status_badge($b['status']) ?></td>
                        <td><?= payment_badge(payment_state($b)) ?></td>
                        <td><a href="<?= e(url('/admin/booking.php?id=' . (int) $b['id'])) ?>">Open</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php layout_end();
