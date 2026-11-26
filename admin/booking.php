<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';

require_admin();
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$b = booking_with_details($id);
if (!$b) {
    flash('error', 'Booking not found.');
    redirect('/admin/bookings.php');
}

// Allowed status changes.
$transitions = [
    'pending'   => ['confirmed' => 'Confirm', 'declined' => 'Decline'],
    'confirmed' => ['completed' => 'Mark completed', 'cancelled' => 'Cancel booking'],
    'declined'  => [],
    'cancelled' => [],
    'completed' => [],
];

if (is_post() && isset($_POST['payment_action'])) {
    csrf_check();
    $act = (string) $_POST['payment_action'];
    try {
        if ($act === 'mark_paid' && payment_state($b) === 'unpaid') {
            $how = in_array($_POST['method'] ?? '', ['Cash', 'Bank transfer', 'Other'], true) ? $_POST['method'] : 'Other';
            q("INSERT INTO payments (booking_id, provider, amount_cents, currency, status, note, created_at, updated_at) VALUES (?, 'manual', ?, ?, 'created', ?, ?, ?)",
                [$id, (int) $b['price_cents'], pay_currency(), $how, now_sql(), now_sql()]);
            mark_payment_paid((int) db()->lastInsertId(), '');
            flash('success', 'Marked as paid (' . $how . '). The client has been sent a receipt.');
        } elseif ($act === 'refund' && ($pp = booking_payment($id)) && $pp['status'] === 'paid') {
            refund_payment($pp);
            send_mail($b['client_email'], 'Refund for booking #' . $id,
                'Hi ' . explode(' ', $b['client_name'])[0] . ",\n\nWe've refunded " . money((int) $pp['amount_cents']) . ' ' . $pp['currency']
                . ($pp['provider'] === 'manual' ? '.' : ' to your original payment method. It usually takes 5–10 working days to show up.')
                . "\n\n" . booking_summary_text($b) . mail_footer(), (string) cfg('business.email'));
            flash('success', $pp['provider'] === 'manual' ? 'Recorded as refunded. Remember to pay the client back yourself.' : 'Refund sent through ' . ucfirst($pp['provider']) . ' and the client has been emailed.');
        }
    } catch (Throwable $e) {
        error_log('[sap admin payment] ' . $e->getMessage());
        flash('error', 'That didn\'t work: ' . $e->getMessage());
    }
    redirect('/admin/booking.php?id=' . $id);
}

if (is_post()) {
    csrf_check();
    $to = (string) ($_POST['status'] ?? '');
    // Quick actions from the calendar don't send a note: keep the existing one.
    $note = isset($_POST['admin_note']) ? input('admin_note', 2000) : (string) ($b['admin_note'] ?? '');
    $back = safe_next($_POST['return'] ?? '', '/admin/booking.php?id=' . $id);

    if ($to === '' && isset($_POST['save_note'])) {
        q('UPDATE bookings SET admin_note = ?, updated_at = ? WHERE id = ?', [$note, now_sql(), $id]);
        flash('success', 'Note saved.');
        redirect('/admin/booking.php?id=' . $id);
    }

    if (!isset($transitions[$b['status']][$to])) {
        flash('error', 'That change is not allowed for this booking.');
        redirect($back);
    }

    q('UPDATE bookings SET status = ?, admin_note = ?, updated_at = ? WHERE id = ?', [$to, $note, now_sql(), $id]);
    $refundNote = '';
    if (in_array($to, ['declined', 'cancelled'], true) && !empty($_POST['refund']) && ($pp = booking_payment($id)) && $pp['status'] === 'paid') {
        try {
            refund_payment($pp);
            $refundNote = $pp['provider'] === 'manual' ? '' : "\nYour payment of " . money((int) $pp['amount_cents']) . ' has been refunded to your original payment method (allow 5–10 working days).';
        } catch (Throwable $e) {
            error_log('[sap refund] ' . $e->getMessage());
            flash('error', 'The booking was updated but the refund failed: ' . $e->getMessage());
        }
    }
    $b['status'] = $to;
    $b['admin_note'] = $note;

    $first = explode(' ', $b['client_name'])[0];
    $msg = $note !== '' ? "\nMessage from us: $note\n" : '';
    $mail = match ($to) {
        'confirmed' => ["Your booking is confirmed (#$id)", "Hi $first,\n\nGreat news, your booking is confirmed!\n\n" . booking_summary_text($b) . $msg
            . (can_pay_online($b) ? "\nPay online here: " . abs_url('/account/booking.php?id=' . $id) . "\n" : '')],
        'declined'  => ["About your booking request (#$id)", "Hi $first,\n\nSorry, we can't take this booking.$refundNote\n\n" . booking_summary_text($b) . $msg . "\nPlease pick another time or reply to this email and we'll help."],
        'cancelled' => ["Your booking has been cancelled (#$id)", "Hi $first,\n\nYour booking has been cancelled.$refundNote\n\n" . booking_summary_text($b) . $msg],
        default     => null,
    };
    if ($mail && !empty($_POST['notify'])) {
        send_mail($b['client_email'], $mail[0], $mail[1] . "\nYour bookings: " . abs_url('/account/') . mail_footer(), (string) cfg('business.email'));
    }
    flash('success', 'Booking #' . $id . ' updated' . ($mail && !empty($_POST['notify']) ? ' and the client has been emailed.' : '.'));
    redirect($back);
}

$pets = booking_pets($id);
$clashes = in_array($b['status'], ACTIVE_STATUSES, true) ? overlapping_bookings($b['start_at'], $b['end_at'], $id) : [];

layout_start('Booking #' . $id, ['noindex' => true]);
page_banner('Booking #' . $id, $b['service_name'] . ' · ' . booking_when($b));
admin_nav('bookings');
?>
<div class="container sap-wide">
    <?= render_flashes() ?>
    <?php if ($clashes): ?>
        <div class="sap-alert sap-alert-error">Heads up: this overlaps with booking<?= count($clashes) > 1 ? 's' : '' ?>
            <?php foreach ($clashes as $c): ?><a href="<?= e(url('/admin/booking.php?id=' . (int) $c['id'])) ?>">#<?= (int) $c['id'] ?></a> <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="sap-card">
                <h2>Details</h2>
                <dl class="sap-dl">
                    <dt>Status</dt><dd><?= status_badge($b['status']) ?></dd>
                    <dt>Service</dt><dd><?= e($b['service_name']) ?></dd>
                    <dt>When</dt><dd><?= e(booking_when($b)) ?></dd>
                    <dt>Price</dt><dd><?= e(money($b['price_cents'] === null ? null : (int) $b['price_cents'])) ?></dd>
                    <dt>Client notes</dt><dd><?= $b['notes'] ? nl2br(e($b['notes'])) : '<span class="sap-muted">None</span>' ?></dd>
                    <dt>Requested</dt><dd><?= e(fmt_datetime($b['created_at'])) ?></dd>
                </dl>
                <h3>Pets</h3>
                <?php foreach ($pets as $p): $va = pet_vaccine_alert((int) $p['id']); ?>
                    <div class="sap-pet-mini">
                        <?php if ($u = pet_photo_url($p)): ?><img class="sap-pet-mini-photo" src="<?= e($u) ?>" alt=""><?php endif; ?>
                        <strong><a href="<?= e(url('/admin/pet.php?id=' . (int) $p['id'])) ?>"><?= e($p['name']) ?></a></strong> <span class="sap-muted"><?= e(ucfirst($p['species'])) ?><?= $p['breed'] ? ' · ' . e($p['breed']) : '' ?><?= $p['age'] ? ' · ' . e($p['age']) : '' ?></span>
                        <?php if ($va): ?><p class="pet-warn"><i class="bi bi-exclamation-triangle"></i> <?= $va === 'expired' ? 'Vaccination expired' : 'Vaccination due soon' ?></p><?php endif; ?>
                        <?php if (!(int) $p['emergency_authorised']): ?><p class="pet-warn"><i class="bi bi-exclamation-triangle"></i> No emergency vet authorisation</p><?php endif; ?>
                        <?php if (trim((string) $p['medical']) !== ''): ?><p><strong>Health:</strong> <?= nl2br(e($p['medical'])) ?></p><?php endif; ?>
                        <?php if (trim((string) $p['feeding']) !== ''): ?><p><strong>Feeding:</strong> <?= nl2br(e($p['feeding'])) ?></p><?php endif; ?>
                        <?php if ($p['notes']): ?><p><?= nl2br(e($p['notes'])) ?></p><?php endif; ?>
                        <a class="sap-link-btn" href="<?= e(url('/admin/pet.php?id=' . (int) $p['id'])) ?>">Full profile</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="sap-card">
                <h2>Client</h2>
                <dl class="sap-dl">
                    <dt>Name</dt><dd><a href="<?= e(url('/admin/clients.php?id=' . (int) $b['user_id'])) ?>"><?= e($b['client_name']) ?></a></dd>
                    <dt>Email</dt><dd><a href="mailto:<?= e($b['client_email']) ?>"><?= e($b['client_email']) ?></a></dd>
                    <dt>Phone</dt><dd><?= $b['client_phone'] ? '<a href="tel:' . e(preg_replace('/[^0-9+]/', '', $b['client_phone'])) . '">' . e($b['client_phone']) . '</a>' : '-' ?></dd>
                    <dt>Address</dt><dd><?= e($b['client_address'] ?: '-') ?></dd>
                </dl>
            </div>
            <?php $payState = payment_state($b); $pp = booking_payment($id); ?>
            <div class="sap-card">
                <h2>Payment <?= payment_badge($payState) ?></h2>
                <?php if ($pp): ?>
                    <dl class="sap-dl">
                        <dt>Amount</dt><dd><?= e(money((int) $pp['amount_cents'])) ?> <?= e($pp['currency']) ?></dd>
                        <dt>Method</dt><dd><?= e(['stripe' => 'Card (Stripe)', 'paypal' => 'PayPal', 'manual' => $pp['note'] ?: 'Manual'][$pp['provider']] ?? $pp['provider']) ?></dd>
                        <dt><?= $pp['status'] === 'refunded' ? 'Refunded' : 'Paid' ?></dt><dd><?= e(fmt_datetime($pp['updated_at'])) ?></dd>
                        <?php if ($pp['capture_ref']): ?><dt>Reference</dt><dd class="sap-ref"><?= e($pp['capture_ref']) ?></dd><?php endif; ?>
                    </dl>
                    <?php if ($pp['status'] === 'paid'): ?>
                        <form method="post" class="sap-form" data-confirm="Refund <?= e(money((int) $pp['amount_cents'])) ?> to <?= e($b['client_name']) ?>?">
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $id ?>"><input type="hidden" name="payment_action" value="refund">
                            <button class="sap-btn-danger" type="submit">Refund <?= $pp['provider'] === 'manual' ? '(record only)' : 'in full' ?></button>
                        </form>
                    <?php endif; ?>
                <?php elseif ($payState === 'unpaid'): ?>
                    <p class="sap-muted"><?= money((int) $b['price_cents']) ?> due. <?= payments_enabled() && $b['status'] === 'confirmed' ? 'The client can pay online from their account.' : (payments_enabled() ? 'The client can pay online once the booking is confirmed.' : 'Online payments are turned off.') ?></p>
                    <form method="post" class="sap-form sap-inline-form">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $id ?>"><input type="hidden" name="payment_action" value="mark_paid">
                        <label for="method">Paid another way?</label>
                        <select id="method" name="method" class="no-nice"><option>Cash</option><option>Bank transfer</option><option>Other</option></select>
                        <button class="sap-link-btn" type="submit">Mark as paid</button>
                    </form>
                <?php else: ?>
                    <p class="sap-muted">No price set for this booking. Add one in <a href="<?= e(url('/admin/services.php')) ?>">Services &amp; prices</a> for future bookings.</p>
                <?php endif; ?>
            </div>
            <div class="sap-card">
                <h2>Update</h2>
                <form method="post" class="sap-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $id ?>">
                    <label for="admin_note">Message to client <span class="sap-muted">(shown in their account and email)</span></label>
                    <textarea id="admin_note" name="admin_note" rows="3" maxlength="2000"><?= e($b['admin_note'] ?? '') ?></textarea>
                    <?php if ($transitions[$b['status']]): ?>
                        <label class="sap-check"><input type="checkbox" name="notify" value="1" checked> Email the client about this change</label>
                        <?php if ($pp && $pp['status'] === 'paid'): ?>
                            <label class="sap-check"><input type="checkbox" name="refund" value="1" checked> If declining or cancelling, refund their payment</label>
                        <?php endif; ?>
                        <div class="sap-dash-actions">
                            <?php foreach ($transitions[$b['status']] as $to => $lbl): ?>
                                <button type="submit" name="status" value="<?= e($to) ?>" class="<?= in_array($to, ['declined', 'cancelled'], true) ? 'sap-btn-danger' : 'primary-btn2' ?>"><?= e($lbl) ?></button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <button type="submit" name="save_note" value="1" class="sap-link-btn">Save message only</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php layout_end();
