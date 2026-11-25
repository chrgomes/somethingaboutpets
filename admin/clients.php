<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';

require_admin();
$id = (int) ($_GET['id'] ?? 0);

if ($id) {
    $c = row('SELECT * FROM users WHERE id = ?', [$id]);
    if (!$c) {
        flash('error', 'Client not found.');
        redirect('/admin/clients.php');
    }
    $pets = rows('SELECT * FROM pets WHERE user_id = ? ORDER BY name', [$id]);
    $bookings = rows('SELECT b.*, s.name AS service_name, s.kind FROM bookings b JOIN services s ON s.id = b.service_id WHERE b.user_id = ? ORDER BY b.start_at DESC', [$id]);

    layout_start($c['name'], ['noindex' => true]);
    page_banner($c['name'], 'Client since ' . fmt_date($c['created_at']));
    admin_nav('clients');
    ?>
    <div class="container sap-wide">
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="sap-card">
                    <h2>Contact</h2>
                    <dl class="sap-dl">
                        <dt>Email</dt><dd><a href="mailto:<?= e($c['email']) ?>"><?= e($c['email']) ?></a></dd>
                        <dt>Phone</dt><dd><?= $c['phone'] ? '<a href="tel:' . e(preg_replace('/[^0-9+]/', '', $c['phone'])) . '">' . e($c['phone']) . '</a>' : '-' ?></dd>
                        <dt>Address</dt><dd><?= e($c['address'] ?: '-') ?></dd>
                        <dt>Last login</dt><dd><?= $c['last_login_at'] ? e(fmt_datetime($c['last_login_at'])) : '-' ?></dd>
                    </dl>
                </div>
                <div class="sap-card">
                    <h2>Pets</h2>
                    <?php if (!$pets): ?><p class="sap-muted">No pets added.</p><?php endif; ?>
                    <?php foreach ($pets as $p): ?>
                        <div class="sap-pet-mini">
                            <strong><?= e($p['name']) ?></strong> <span class="sap-muted"><?= e(ucfirst($p['species'])) ?><?= $p['breed'] ? ' · ' . e($p['breed']) : '' ?><?= $p['age'] ? ' · ' . e($p['age']) : '' ?></span>
                            <?php if ($p['notes']): ?><p><?= nl2br(e($p['notes'])) ?></p><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="sap-card">
                    <h2>Bookings</h2>
                    <?php if (!$bookings): ?><p class="sap-muted">No bookings yet.</p><?php endif; ?>
                    <div class="sap-booking-list">
                        <?php foreach ($bookings as $b): ?>
                            <a class="sap-booking-row" href="<?= e(url('/admin/booking.php?id=' . (int) $b['id'])) ?>">
                                <span class="sap-booking-main"><strong><?= e($b['service_name']) ?></strong><span><?= e(booking_when($b)) ?></span></span>
                                <?= status_badge($b['status']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    layout_end();
    exit;
}

$search = trim((string) ($_GET['q'] ?? ''));
$params = [];
$sql = "SELECT u.*, (SELECT COUNT(*) FROM pets p WHERE p.user_id = u.id) AS pet_count,
               (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.id) AS booking_count
        FROM users u WHERE u.role = 'client'";
if ($search !== '') {
    $sql .= ' AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
    $like = '%' . addcslashes($search, '%_') . '%';
    $params = [$like, $like, $like];
}
$clients = rows($sql . ' ORDER BY u.name LIMIT 500', $params);

layout_start('Clients', ['noindex' => true]);
page_banner('Clients');
admin_nav('clients');
?>
<div class="container sap-wide">
    <form class="sap-search" method="get">
        <label for="q" class="visually-hidden">Search clients</label>
        <input id="q" name="q" placeholder="Search by name, email or phone" value="<?= e($search) ?>">
        <button class="primary-btn2" type="submit">Search</button>
    </form>
    <div class="sap-card">
        <?php if (!$clients): ?>
            <p class="sap-muted">No clients found.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="sap-table">
                <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Pets</th><th>Bookings</th></tr></thead>
                <tbody>
                <?php foreach ($clients as $c): ?>
                    <tr>
                        <td><a href="<?= e(url('/admin/clients.php?id=' . (int) $c['id'])) ?>"><?= e($c['name']) ?></a></td>
                        <td><?= e($c['email']) ?></td>
                        <td><?= e($c['phone']) ?></td>
                        <td><?= (int) $c['pet_count'] ?></td>
                        <td><?= (int) $c['booking_count'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php layout_end();
