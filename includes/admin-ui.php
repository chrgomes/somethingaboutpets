<?php
declare(strict_types=1);

/** Shared pieces for the admin pages. */

/** A booking card with call / directions links and quick approve, decline or complete buttons. */
function admin_booking_card(array $b, string $return): string
{
    ob_start();
    ?>
                <article class="sap-card cal-dcard cal-border-<?= e($b['status']) ?>">
                    <div class="cal-dtime">
                        <?php if ($b['kind'] === 'stay'): ?>
                            <strong>All day</strong><span><?= e(date('j M', strtotime($b['start_at']))) ?> → <?= e(date('j M', strtotime($b['end_at']))) ?></span>
                        <?php else: ?>
                            <strong><?= e(fmt_time($b['start_at'])) ?></strong><span>to <?= e(fmt_time($b['end_at'])) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="cal-dbody">
                        <h3><a href="<?= e(url('/admin/booking.php?id=' . (int) $b['id'])) ?>"><?= e($b['service_name']) ?> · <?= e($b['client_name']) ?></a> <?= status_badge($b['status']) ?></h3>
                        <?php if ($b['pet_names']): ?><p><i class="bi bi-heart"></i> <?= e(implode(', ', $b['pet_names'])) ?></p><?php endif; ?>
                        <?php if ($b['notes']): ?><p class="sap-muted"><?= e(mb_strimwidth($b['notes'], 0, 160, '…')) ?></p><?php endif; ?>
                        <div class="cal-dlinks">
                            <?php if ($b['client_phone']): ?><a href="<?= e(tel_link($b['client_phone'])) ?>"><i class="bi bi-telephone"></i> Call</a><?php endif; ?>
                            <?php if ($b['client_address']): ?><a href="<?= e(maps_link($b['client_address'])) ?>" target="_blank" rel="noopener"><i class="bi bi-geo-alt"></i> Directions</a><?php endif; ?>
                            <a href="<?= e(url('/admin/booking.php?id=' . (int) $b['id'])) ?>"><i class="bi bi-card-text"></i> Details</a>
                        </div>
                        <?php if ($b['status'] === 'pending'): ?>
                            <form method="post" action="<?= e(url('/admin/booking.php')) ?>" class="cal-quick">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                                <input type="hidden" name="notify" value="1">
                                <input type="hidden" name="return" value="<?= e($return) ?>">
                                <button class="primary-btn2" type="submit" name="status" value="confirmed">Confirm</button>
                                <button class="sap-btn-danger" type="submit" name="status" value="declined">Decline</button>
                            </form>
                        <?php elseif ($b['status'] === 'confirmed' && $b['end_at'] < now_sql()): ?>
                            <form method="post" action="<?= e(url('/admin/booking.php')) ?>" class="cal-quick">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                                <input type="hidden" name="return" value="<?= e($return) ?>">
                                <button class="primary-btn2" type="submit" name="status" value="completed">Mark completed</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
    <?php
    return (string) ob_get_clean();
}

/** Horizontal bar rows: [label => value]. */
function admin_bars(array $data, string $suffix = ''): string
{
    if (!$data) {
        return '<p class="sap-muted">Not enough bookings yet.</p>';
    }
    $max = max($data) ?: 1;
    $html = '<div class="dash-bars">';
    foreach ($data as $label => $n) {
        $html .= '<div class="dash-bar"><span class="dash-bar-l">' . e($label) . '</span>'
            . '<span class="dash-bar-t"><span style="width:' . round($n / $max * 100, 1) . '%"></span></span>'
            . '<span class="dash-bar-n">' . e($n . $suffix) . '</span></div>';
    }
    return $html . '</div>';
}

/** "+3 vs last month" style comparison. */
function admin_delta(float $now, float $before, bool $money = false): string
{
    $diff = $now - $before;
    if (abs($diff) < 0.005) {
        return '<span class="dash-delta">Same as last month</span>';
    }
    $txt = $money ? money((int) round(abs($diff))) : (string) abs((int) $diff);
    return '<span class="dash-delta ' . ($diff > 0 ? 'is-up' : 'is-down') . '"><i class="bi bi-arrow-' . ($diff > 0 ? 'up' : 'down') . '-short"></i>'
        . e($txt) . ' vs last month</span>';
}
