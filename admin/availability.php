<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';

require_admin();
$days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$errors = [];

if (is_post()) {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'hours') {
        $rows = [];
        for ($d = 0; $d <= 6; $d++) {
            $open  = (string) ($_POST['open'][$d] ?? '08:00');
            $close = (string) ($_POST['close'][$d] ?? '18:00');
            $isOpen = !empty($_POST['is_open'][$d]) ? 1 : 0;
            if (!preg_match('/^\d{2}:\d{2}$/', $open) || !preg_match('/^\d{2}:\d{2}$/', $close)) {
                $errors[] = "{$days[$d]}: please enter valid times.";
                continue;
            }
            if ($isOpen && $close <= $open) {
                $errors[] = "{$days[$d]}: closing time must be after opening time.";
                continue;
            }
            $rows[] = [$isOpen, $open, $close, $d];
        }
        if (!$errors) {
            foreach ($rows as $r) {
                q('UPDATE working_hours SET is_open = ?, open_time = ?, close_time = ? WHERE weekday = ?', $r);
            }
            flash('success', 'Working hours saved.');
            redirect('/admin/availability.php');
        }
    }

    if ($action === 'block') {
        $from = (string) ($_POST['from'] ?? '');
        $to = (string) ($_POST['to'] ?? '') ?: $from;
        $reason = input('reason', 120);
        if (!valid_date($from) || !valid_date($to) || $to < $from) {
            $errors[] = 'Please choose a valid date or date range.';
        } elseif ((strtotime($to) - strtotime($from)) / 86400 > 366) {
            $errors[] = 'Please block at most one year at a time.';
        } else {
            $added = 0;
            foreach (date_range($from, $to) as $d) {
                if (!scalar('SELECT 1 FROM blocked_dates WHERE blocked_date = ?', [$d])) {
                    q('INSERT INTO blocked_dates (blocked_date, reason) VALUES (?, ?)', [$d, $reason]);
                    $added++;
                }
            }
            $clash = (int) scalar("SELECT COUNT(*) FROM bookings WHERE status IN ('pending','confirmed') AND start_at <= ? AND end_at >= ?",
                ["$to 23:59:59", "$from 00:00:00"]);
            flash('success', "Blocked $added day" . ($added === 1 ? '' : 's') . '.'
                . ($clash ? " Note: $clash existing booking" . ($clash === 1 ? ' falls' : 's fall') . ' on those days. Blocking doesn\'t cancel them.' : ''));
            redirect('/admin/availability.php');
        }
    }

    if ($action === 'unblock') {
        q('DELETE FROM blocked_dates WHERE id = ?', [(int) ($_POST['block_id'] ?? 0)]);
        flash('success', 'Date unblocked.');
        redirect('/admin/availability.php');
    }
}

$hours = working_hours();
$blocked = rows('SELECT * FROM blocked_dates WHERE blocked_date >= ? ORDER BY blocked_date', [date('Y-m-d')]);

layout_start('Availability', ['noindex' => true]);
page_banner('Availability', 'Set your regular hours and block out days you\'re not available.');
admin_nav('availability');
?>
<div class="container sap-wide">
    <?= render_flashes() ?>
    <?php foreach ($errors as $er): ?><div class="sap-alert sap-alert-error" role="alert"><?= e($er) ?></div><?php endforeach; ?>
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="sap-card">
                <h2>Weekly hours</h2>
                <p class="sap-muted">Walks, visits and pet taxi can be booked inside these hours. In-house sitting can be booked on any day that isn't blocked.</p>
                <form method="post" class="sap-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="hours">
                    <table class="sap-table sap-hours sap-stack">
                        <thead><tr><th>Day</th><th>Open?</th><th>From</th><th>To</th></tr></thead>
                        <tbody>
                        <?php foreach ([1, 2, 3, 4, 5, 6, 0] as $d): $h = $hours[$d] ?? ['is_open' => 0, 'open_time' => '08:00', 'close_time' => '18:00']; ?>
                            <tr>
                                <td><?= e($days[$d]) ?></td>
                                <td><input type="checkbox" name="is_open[<?= $d ?>]" value="1" aria-label="Open on <?= e($days[$d]) ?>" <?= (int) $h['is_open'] ? 'checked' : '' ?>></td>
                                <td><input type="time" name="open[<?= $d ?>]" value="<?= e($h['open_time']) ?>" aria-label="<?= e($days[$d]) ?> opening time"></td>
                                <td><input type="time" name="close[<?= $d ?>]" value="<?= e($h['close_time']) ?>" aria-label="<?= e($days[$d]) ?> closing time"></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <button class="primary-btn2" type="submit">Save hours</button>
                </form>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="sap-card">
                <h2>Block out days</h2>
                <form method="post" class="sap-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="block">
                    <div class="row g-2">
                        <div class="col-6"><label for="from">From</label><input id="from" type="date" name="from" required min="<?= date('Y-m-d') ?>"></div>
                        <div class="col-6"><label for="to">To <span class="sap-muted">(optional)</span></label><input id="to" type="date" name="to" min="<?= date('Y-m-d') ?>"></div>
                    </div>
                    <label for="reason">Reason <span class="sap-muted">(only you see this)</span></label>
                    <input id="reason" name="reason" maxlength="120" placeholder="e.g. Holiday">
                    <button class="primary-btn2" type="submit">Block</button>
                </form>
                <h3 class="mt-4">Blocked days</h3>
                <?php if (!$blocked): ?>
                    <p class="sap-muted">No upcoming blocked days.</p>
                <?php else: ?>
                    <ul class="sap-blocked">
                        <?php foreach ($blocked as $bd): ?>
                            <li>
                                <span><?= e(fmt_date($bd['blocked_date'])) ?><?= $bd['reason'] ? ' <span class="sap-muted">· ' . e($bd['reason']) . '</span>' : '' ?></span>
                                <form method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="unblock">
                                    <input type="hidden" name="block_id" value="<?= (int) $bd['id'] ?>">
                                    <button type="submit" class="sap-icon-btn" aria-label="Unblock <?= e(fmt_date($bd['blocked_date'])) ?>"><i class="bi bi-x-lg"></i></button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php layout_end();
