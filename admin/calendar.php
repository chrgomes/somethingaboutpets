<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';

require_admin();
require_once dirname(__DIR__) . '/includes/admin-ui.php';

$view = in_array($_GET['view'] ?? '', ['month', 'week', 'day'], true) ? $_GET['view'] : 'month';
$date = valid_date((string) ($_GET['date'] ?? '')) ? (string) $_GET['date'] : date('Y-m-d');
$d = new DateTimeImmutable($date);
$today = date('Y-m-d');

// Work out the visible range (weeks start on Monday).
if ($view === 'month') {
    $first = $d->modify('first day of this month');
    $start = $first->modify('-' . ((int) $first->format('N') - 1) . ' days');
    $last = $d->modify('last day of this month');
    $end = $last->modify('+' . (7 - (int) $last->format('N')) . ' days');
    $prev = $d->modify('first day of previous month');
    $next = $d->modify('first day of next month');
    $title = $d->format('F Y');
} elseif ($view === 'week') {
    $start = $d->modify('-' . ((int) $d->format('N') - 1) . ' days');
    $end = $start->modify('+6 days');
    $prev = $d->modify('-7 days');
    $next = $d->modify('+7 days');
    $title = $start->format('j M') . ' – ' . $end->format('j M Y');
} else {
    $start = $end = $d;
    $prev = $d->modify('-1 day');
    $next = $d->modify('+1 day');
    $title = $d->format('l j F Y');
}

$showAll = !empty($_GET['all']);
$statuses = $showAll ? ['pending', 'confirmed', 'completed', 'declined', 'cancelled'] : ['pending', 'confirmed', 'completed'];
$bookings = bookings_between($start->format('Y-m-d 00:00:00'), $end->format('Y-m-d 23:59:59'), $statuses);

// Group bookings by day; stays appear on every day they cover.
$byDay = [];
foreach ($bookings as $b) {
    $from = substr($b['start_at'], 0, 10);
    $to = substr($b['end_at'], 0, 10);
    foreach (date_range(max($from, $start->format('Y-m-d')), min($to, $end->format('Y-m-d'))) as $day) {
        $part = $b['kind'] === 'stay' ? ($day === $from ? ($day === $to ? 'one' : 'start') : ($day === $to ? 'end' : 'mid')) : 'one';
        $byDay[$day][] = $b + ['_part' => $part];
    }
}
// Stays first, then by time.
foreach ($byDay as &$list) {
    usort($list, fn($a, $b) => [$a['kind'] === 'stay' ? 0 : 1, $a['start_at']] <=> [$b['kind'] === 'stay' ? 0 : 1, $b['start_at']]);
}
unset($list);

$blocked = [];
foreach (rows('SELECT blocked_date, reason FROM blocked_dates WHERE blocked_date BETWEEN ? AND ?', [$start->format('Y-m-d'), $end->format('Y-m-d')]) as $r) {
    $blocked[$r['blocked_date']] = $r['reason'] ?: 'Blocked';
}
$closed = closed_weekdays();

function cal_link(string $view, string $date, array $extra = []): string
{
    $q = ['view' => $view, 'date' => $date] + $extra;
    if (!empty($_GET['all'])) {
        $q['all'] = 1;
    }
    return url('/admin/calendar.php?' . http_build_query($q));
}

function chip(array $b, bool $long = false): string
{
    $label = $b['kind'] === 'stay'
        ? ($b['_part'] === 'start' || $b['_part'] === 'one' ? 'Sitting · ' : '') . explode(' ', $b['client_name'])[0]
        : date('g:ia', strtotime($b['start_at'])) . ' ' . $b['service_name'] . ' · ' . explode(' ', $b['client_name'])[0];
    if ($b['kind'] === 'stay' && in_array($b['_part'], ['mid', 'end'], true) && !$long) {
        $label = explode(' ', $b['client_name'])[0];
    }
    if ($long && $b['pet_names']) {
        $label .= ' (' . implode(', ', $b['pet_names']) . ')';
    }
    $title = $b['service_name'] . ' – ' . $b['client_name'] . ' – ' . booking_when($b);
    return '<a class="cal-chip cal-' . e($b['status']) . ' cal-kind-' . e($b['kind']) . ' cal-part-' . e($b['_part']) . '" href="'
        . e(url('/admin/booking.php?id=' . (int) $b['id'])) . '" title="' . e($title) . '">' . e($label) . '</a>';
}

$return = '/admin/calendar.php?' . http_build_query(['view' => $view, 'date' => $date]);
$feedUrl = abs_url('/admin/feed.php?token=' . calendar_feed_token());

layout_start('Calendar', ['noindex' => true]);
page_banner('Calendar');
admin_nav('calendar');
?>
<div class="container sap-wide sap-cal">
    <?= render_flashes() ?>
    <div class="cal-toolbar">
        <div class="cal-nav">
            <a class="sap-icon-btn cal-arrow" href="<?= e(cal_link($view, $prev->format('Y-m-d'))) ?>" aria-label="Previous"><i class="bi bi-chevron-left"></i></a>
            <a class="cal-today" href="<?= e(cal_link($view, $today)) ?>">Today</a>
            <a class="sap-icon-btn cal-arrow" href="<?= e(cal_link($view, $next->format('Y-m-d'))) ?>" aria-label="Next"><i class="bi bi-chevron-right"></i></a>
            <h2><?= e($title) ?></h2>
        </div>
        <div class="sap-pills cal-views">
            <?php foreach (['month' => 'Month', 'week' => 'Week', 'day' => 'Day'] as $v => $lbl): ?>
                <a class="<?= $v === $view ? 'active' : '' ?>" href="<?= e(cal_link($v, $date)) ?>"><?= $lbl ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="cal-legend">
        <span><i class="cal-dot cal-pending"></i>Awaiting approval</span>
        <span><i class="cal-dot cal-confirmed"></i>Confirmed</span>
        <span><i class="cal-dot cal-completed"></i>Completed</span>
        <span><i class="cal-dot cal-blocked"></i>Blocked / closed</span>
        <a href="<?= e(url('/admin/calendar.php?' . http_build_query(['view' => $view, 'date' => $date] + ($showAll ? [] : ['all' => 1])))) ?>"><?= $showAll ? 'Hide' : 'Show' ?> cancelled</a>
    </div>

    <?php if ($view === 'month'): ?>
        <div class="cal-month" role="grid" aria-label="<?= e($title) ?>">
            <?php foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $wd): ?>
                <div class="cal-head" role="columnheader"><?= $wd ?></div>
            <?php endforeach; ?>
            <?php foreach (date_range($start->format('Y-m-d'), $end->format('Y-m-d')) as $day):
                $items = $byDay[$day] ?? [];
                $isOther = substr($day, 0, 7) !== $d->format('Y-m');
                $isClosed = in_array((int) date('w', strtotime($day)), $closed, true);
                $cls = 'cal-cell' . ($isOther ? ' is-other' : '') . ($day === $today ? ' is-today' : '') . ($day === $date ? ' is-selected' : '')
                    . (isset($blocked[$day]) ? ' is-blocked' : ($isClosed ? ' is-closed' : '')) . ($day < $today ? ' is-past' : '');
            ?>
                <div class="<?= $cls ?>" role="gridcell">
                    <a class="cal-daynum" href="<?= e(cal_link('day', $day)) ?>" data-agenda="<?= e(cal_link('month', $day)) ?>#agenda" aria-label="<?= e(fmt_date($day)) ?>: <?= count($items) ?> booking<?= count($items) === 1 ? '' : 's' ?>"><?= (int) substr($day, 8, 2) ?></a>
                    <?php if (isset($blocked[$day])): ?><span class="cal-note"><?= e($blocked[$day]) ?></span><?php endif; ?>
                    <div class="cal-chips">
                        <?php foreach (array_slice($items, 0, 3) as $b): ?><?= chip($b) ?><?php endforeach; ?>
                        <?php if (count($items) > 3): ?><a class="cal-more" href="<?= e(cal_link('day', $day)) ?>">+<?= count($items) - 3 ?> more</a><?php endif; ?>
                    </div>
                    <?php if ($items): ?>
                        <a class="cal-dots" href="<?= e(cal_link('month', $day)) ?>#agenda" aria-hidden="true" tabindex="-1">
                            <?php foreach (array_slice($items, 0, 4) as $b): ?><i class="cal-dot cal-<?= e($b['status']) ?>"></i><?php endforeach; ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php /* On phones the grid shows dots; the selected day's list appears underneath. */ ?>
        <section class="cal-agenda" id="agenda">
            <h3><?= e(fmt_date($date)) ?></h3>
            <?php $items = $byDay[$date] ?? []; ?>
            <?php if (!$items): ?><p class="sap-muted"><?= isset($blocked[$date]) ? 'Blocked: ' . e($blocked[$date]) : 'Nothing booked.' ?></p><?php endif; ?>
            <?php foreach ($items as $b): ?><?= chip($b, true) ?><?php endforeach; ?>
        </section>

    <?php elseif ($view === 'week'): ?>
        <div class="cal-week">
            <?php foreach (date_range($start->format('Y-m-d'), $end->format('Y-m-d')) as $day):
                $items = $byDay[$day] ?? [];
                $isClosed = in_array((int) date('w', strtotime($day)), $closed, true);
            ?>
                <div class="cal-wcol<?= $day === $today ? ' is-today' : '' ?><?= isset($blocked[$day]) ? ' is-blocked' : ($isClosed ? ' is-closed' : '') ?>">
                    <a class="cal-whead" href="<?= e(cal_link('day', $day)) ?>"><span><?= date('D', strtotime($day)) ?></span><strong><?= (int) substr($day, 8, 2) ?></strong></a>
                    <?php if (isset($blocked[$day])): ?><span class="cal-note"><?= e($blocked[$day]) ?></span><?php elseif ($isClosed && !$items): ?><span class="cal-note">Closed</span><?php endif; ?>
                    <?php foreach ($items as $b): ?><?= chip($b, true) ?><?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <?php $items = $byDay[$date] ?? []; ?>
        <?php if (isset($blocked[$date])): ?><div class="sap-alert sap-alert-info">This day is blocked: <?= e($blocked[$date]) ?></div><?php endif; ?>
        <?php if (!$items): ?>
            <div class="sap-card"><p class="sap-muted">Nothing booked on this day.</p></div>
        <?php endif; ?>
        <div class="cal-day">
            <?php foreach ($items as $b): ?><?= admin_booking_card($b, $return) ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <details class="sap-card cal-feed">
        <summary><i class="bi bi-calendar-plus"></i> Show bookings in Google or Apple Calendar</summary>
        <p>Subscribe to this private link and new bookings appear in your phone's calendar automatically (updates every few hours). Keep it private: anyone with the link can see your bookings.</p>
        <div class="cal-feed-row">
            <input type="text" readonly value="<?= e($feedUrl) ?>" aria-label="Calendar feed link" onclick="this.select()">
            <button type="button" class="primary-btn2" data-copy="<?= e($feedUrl) ?>">Copy</button>
        </div>
        <p class="sap-muted"><strong>Google Calendar:</strong> on a computer, open Other calendars → + → From URL, and paste the link.<br>
        <strong>iPhone:</strong> Settings → Calendar → Accounts → Add Account → Other → Add Subscribed Calendar, and paste the link.</p>
    </details>
</div>
<script>
// On phones, tapping a day shows its bookings under the month grid instead of opening the day view.
document.querySelectorAll('.cal-cell').forEach(function (cell) {
  cell.addEventListener('click', function (ev) {
    if (window.innerWidth >= 768) { return; }
    var link = cell.querySelector('[data-agenda]');
    if (link) { ev.preventDefault(); location.href = link.getAttribute('data-agenda'); }
  });
});
document.querySelectorAll('[data-copy]').forEach(function (b) {
  b.addEventListener('click', function () {
    var t = b.getAttribute('data-copy');
    (navigator.clipboard ? navigator.clipboard.writeText(t) : Promise.reject()).then(function () { b.textContent = 'Copied'; }, function () { b.previousElementSibling.select(); });
  });
});
</script>
<?php layout_end();
