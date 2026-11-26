<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';

$admin = require_admin();
require_once dirname(__DIR__) . '/includes/admin-ui.php';

$now = now_sql();
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$return = '/admin/';

// Today and tomorrow
$todayList = bookings_between("$today 00:00:00", "$today 23:59:59");
$tomorrowList = bookings_between("$tomorrow 00:00:00", "$tomorrow 23:59:59", ['pending', 'confirmed']);

// Waiting for approval
$pending = bookings_between($now, date('Y-m-d 23:59:59', strtotime('+2 years')), ['pending']);

// Alerts
$alerts = [];
$stale = array_filter($pending, fn($b) => strtotime($b['created_at']) < time() - 86400);
if ($stale) {
    $alerts[] = ['warn', count($stale) . ' booking request' . (count($stale) > 1 ? 's have' : ' has') . ' been waiting more than a day.', '/admin/bookings.php?f=pending', 'Review'];
}
$onBlocked = (int) scalar(
    "SELECT COUNT(DISTINCT b.id) FROM bookings b JOIN blocked_dates d
       ON d.blocked_date >= SUBSTR(b.start_at, 1, 10) AND d.blocked_date <= SUBSTR(b.end_at, 1, 10)
     WHERE b.status IN ('pending','confirmed') AND b.end_at >= ?",
    [$now]
);
if ($onBlocked) {
    $alerts[] = ['warn', "$onBlocked upcoming booking" . ($onBlocked > 1 ? 's fall' : ' falls') . ' on a day you blocked off.', '/admin/calendar.php', 'Open calendar'];
}
$toComplete = (int) scalar("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed' AND end_at < ?", [$now]);
if ($toComplete) {
    $alerts[] = ['info', "$toComplete past booking" . ($toComplete > 1 ? 's are' : ' is') . ' still marked confirmed. Mark them completed to keep your numbers right.', '/admin/bookings.php?f=past', 'View'];
}
$unread = (int) scalar('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0');
if ($unread) {
    $alerts[] = ['info', "$unread unread message" . ($unread > 1 ? 's' : '') . ' from the contact form.', '/admin/messages.php', 'Read'];
}
$noPrices = (int) scalar('SELECT COUNT(*) FROM services WHERE active = 1 AND price_cents IS NULL');
if ($noPrices) {
    $alerts[] = ['info', "$noPrices service" . ($noPrices > 1 ? 's show' : ' shows') . ' "Price on request", so earnings can\'t be counted for them.', '/admin/services.php', 'Add prices'];
}

// This month vs last month (by booking start date; confirmed + completed)
$monthStart = date('Y-m-01');
$lastStart = date('Y-m-01', strtotime('first day of last month'));
$lastSameDay = date('Y-m-d', min(strtotime('last day of last month'), strtotime('-1 month')));
$recent = rows(
    "SELECT b.start_at, b.price_cents, b.status, s.name AS service_name FROM bookings b JOIN services s ON s.id = b.service_id
     WHERE b.status IN ('confirmed','completed') AND b.start_at >= ?",
    [min($lastStart, date('Y-m-d', strtotime('-90 days'))) . ' 00:00:00']
);
$stat = ['this' => ['n' => 0, 'cents' => 0], 'last' => ['n' => 0, 'cents' => 0]];
$byService = [];
$byWeekday = array_fill_keys(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'], 0);
$since90 = date('Y-m-d', strtotime('-90 days'));
foreach ($recent as $r) {
    $day = substr($r['start_at'], 0, 10);
    if ($day >= $monthStart && $day <= date('Y-m-t')) {
        $stat['this']['n']++;
        $stat['this']['cents'] += (int) $r['price_cents'];
    } elseif ($day >= $lastStart && $day < $monthStart) {
        $stat['last']['n']++;
        $stat['last']['cents'] += (int) $r['price_cents'];
    }
    if ($day >= $since90) {
        $byService[$r['service_name']] = ($byService[$r['service_name']] ?? 0) + 1;
        $byWeekday[date('D', strtotime($day))]++;
    }
}
arsort($byService);
$newClients = (int) scalar("SELECT COUNT(*) FROM users WHERE role = 'client' AND created_at >= ?", ["$monthStart 00:00:00"]);
$newClientsLast = (int) scalar("SELECT COUNT(*) FROM users WHERE role = 'client' AND created_at >= ? AND created_at < ?", ["$lastStart 00:00:00", "$monthStart 00:00:00"]);
$totalClients = (int) scalar("SELECT COUNT(*) FROM users WHERE role = 'client'");

$hour = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

layout_start('Today', ['noindex' => true]);
page_banner($greeting . ', ' . explode(' ', $admin['name'])[0], date('l j F'));
admin_nav('dashboard');
?>
<div class="container sap-wide sap-cal dash">
    <?= render_flashes() ?>

    <?php foreach ($alerts as [$type, $msg, $href, $cta]): ?>
        <div class="sap-alert <?= $type === 'warn' ? 'sap-alert-error' : 'sap-alert-info' ?> dash-alert">
            <span><?= e($msg) ?></span> <a href="<?= e(url($href)) ?>"><?= e($cta) ?></a>
        </div>
    <?php endforeach; ?>

    <div class="row g-4">
        <div class="col-lg-7">
            <section>
                <div class="dash-head">
                    <h2>Today <span class="sap-muted"><?= count($todayList) ?> booking<?= count($todayList) === 1 ? '' : 's' ?></span></h2>
                    <a class="sap-link-btn" href="<?= e(url('/admin/calendar.php?view=day&date=' . $today)) ?>">Day view</a>
                </div>
                <?php if (!$todayList): ?>
                    <div class="sap-card sap-empty dash-empty">
                        <?= pet_mark('pet-mark--empty', true, '') ?>
                        <p>Nothing booked today. Enjoy the quiet!</p>
                    </div>
                <?php endif; ?>
                <div class="cal-day"><?php foreach ($todayList as $b): ?><?= admin_booking_card($b, $return) ?><?php endforeach; ?></div>
            </section>

            <section class="sap-card">
                <div class="dash-head">
                    <h2>Tomorrow</h2>
                    <a class="sap-link-btn" href="<?= e(url('/admin/calendar.php?view=week')) ?>">This week</a>
                </div>
                <?php if (!$tomorrowList): ?><p class="sap-muted">Nothing booked yet.</p><?php endif; ?>
                <div class="sap-booking-list">
                    <?php foreach ($tomorrowList as $b): ?>
                        <a class="sap-booking-row" href="<?= e(url('/admin/booking.php?id=' . (int) $b['id'])) ?>">
                            <span class="sap-booking-main">
                                <strong><?= $b['kind'] === 'stay' ? 'All day' : e(fmt_time($b['start_at'])) ?> · <?= e($b['service_name']) ?></strong>
                                <span><?= e($b['client_name']) ?><?= $b['pet_names'] ? ' · ' . e(implode(', ', $b['pet_names'])) : '' ?></span>
                            </span>
                            <?= status_badge($b['status']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="sap-card">
                <div class="dash-head">
                    <h2>Waiting for approval <span class="sap-muted"><?= count($pending) ?></span></h2>
                    <a class="sap-link-btn" href="<?= e(url('/admin/bookings.php?f=pending')) ?>">All requests</a>
                </div>
                <?php if (!$pending): ?><p class="sap-muted">You're all caught up.</p><?php endif; ?>
                <div class="sap-booking-list">
                    <?php foreach (array_slice($pending, 0, 6) as $b): ?>
                        <div class="sap-booking-row dash-pending">
                            <a class="sap-booking-main" href="<?= e(url('/admin/booking.php?id=' . (int) $b['id'])) ?>">
                                <strong><?= e($b['service_name']) ?> · <?= e($b['client_name']) ?></strong>
                                <span><?= e(booking_when($b)) ?></span>
                            </a>
                            <form method="post" action="<?= e(url('/admin/booking.php')) ?>" class="cal-quick">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                                <input type="hidden" name="notify" value="1">
                                <input type="hidden" name="return" value="<?= e($return) ?>">
                                <button class="primary-btn2" type="submit" name="status" value="confirmed">Confirm</button>
                                <button class="sap-btn-danger" type="submit" name="status" value="declined">Decline</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>

        <div class="col-lg-5">
            <section class="sap-card">
                <h2><?= e(date('F')) ?> so far</h2>
                <div class="dash-kpis">
                    <div class="dash-kpi">
                        <span class="dash-kpi-l">Bookings</span>
                        <span class="dash-kpi-n"><?= $stat['this']['n'] ?></span>
                        <?= admin_delta($stat['this']['n'], $stat['last']['n']) ?>
                    </div>
                    <div class="dash-kpi">
                        <span class="dash-kpi-l">Earnings</span>
                        <span class="dash-kpi-n"><?= e(money($stat['this']['cents'])) ?></span>
                        <?= admin_delta($stat['this']['cents'], $stat['last']['cents'], true) ?>
                    </div>
                    <div class="dash-kpi">
                        <span class="dash-kpi-l">New clients</span>
                        <span class="dash-kpi-n"><?= $newClients ?></span>
                        <?= admin_delta($newClients, $newClientsLast) ?>
                    </div>
                    <div class="dash-kpi">
                        <span class="dash-kpi-l">All clients</span>
                        <span class="dash-kpi-n"><?= $totalClients ?></span>
                        <a class="dash-delta" href="<?= e(url('/admin/clients.php')) ?>">See clients</a>
                    </div>
                </div>
                <p class="sap-muted dash-foot">Confirmed and completed bookings starting this month. Earnings count only services with a price.</p>
            </section>

            <section class="sap-card">
                <h2>Most popular services</h2>
                <p class="sap-muted dash-sub">Last 90 days</p>
                <?= admin_bars($byService) ?>
            </section>

            <section class="sap-card">
                <h2>Busiest days</h2>
                <p class="sap-muted dash-sub">Bookings by day of the week, last 90 days</p>
                <?= array_sum($byWeekday) ? admin_bars($byWeekday) : admin_bars([]) ?>
            </section>
        </div>
    </div>
</div>
<?php layout_end();
