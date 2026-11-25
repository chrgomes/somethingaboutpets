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

if (is_post()) {
    csrf_check();
    $to = (string) ($_POST['status'] ?? '');
    $note = input('admin_note', 2000);

    if ($to === '' && isset($_POST['save_note'])) {
        q('UPDATE bookings SET admin_note = ?, updated_at = ? WHERE id = ?', [$note, now_sql(), $id]);
        flash('success', 'Note saved.');
        redirect('/admin/booking.php?id=' . $id);
    }

    if (!isset($transitions[$b['status']][$to])) {
        flash('error', 'That change is not allowed for this booking.');
        redirect('/admin/booking.php?id=' . $id);
    }

    q('UPDATE bookings SET status = ?, admin_note = ?, updated_at = ? WHERE id = ?', [$to, $note, now_sql(), $id]);
    $b['status'] = $to;
    $b['admin_note'] = $note;

    $first = explode(' ', $b['client_name'])[0];
    $msg = $note !== '' ? "\nMessage from us: $note\n" : '';
    $mail = match ($to) {
        'confirmed' => ["Your booking is confirmed (#$id)", "Hi $first,\n\nGreat news, your booking is confirmed!\n\n" . booking_summary_text($b) . $msg],
        'declined'  => ["About your booking request (#$id)", "Hi $first,\n\nSorry, we can't take this booking.\n\n" . booking_summary_text($b) . $msg . "\nPlease pick another time or reply to this email and we'll help."],
        'cancelled' => ["Your booking has been cancelled (#$id)", "Hi $first,\n\nYour booking has been cancelled.\n\n" . booking_summary_text($b) . $msg],
        default     => null,
    };
    if ($mail && !empty($_POST['notify'])) {
        send_mail($b['client_email'], $mail[0], $mail[1] . "\nYour bookings: " . abs_url('/account/') . mail_footer(), (string) cfg('business.email'));
    }
    flash('success', 'Booking updated' . ($mail && !empty($_POST['notify']) ? ' and the client has been emailed.' : '.'));
    redirect('/admin/booking.php?id=' . $id);
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
                <?php foreach ($pets as $p): ?>
                    <div class="sap-pet-mini">
                        <strong><?= e($p['name']) ?></strong> <span class="sap-muted"><?= e(ucfirst($p['species'])) ?><?= $p['breed'] ? ' · ' . e($p['breed']) : '' ?><?= $p['age'] ? ' · ' . e($p['age']) : '' ?></span>
                        <?php if ($p['notes']): ?><p><?= nl2br(e($p['notes'])) ?></p><?php endif; ?>
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
            <div class="sap-card">
                <h2>Update</h2>
                <form method="post" class="sap-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $id ?>">
                    <label for="admin_note">Message to client <span class="sap-muted">(shown in their account and email)</span></label>
                    <textarea id="admin_note" name="admin_note" rows="3" maxlength="2000"><?= e($b['admin_note'] ?? '') ?></textarea>
                    <?php if ($transitions[$b['status']]): ?>
                        <label class="sap-check"><input type="checkbox" name="notify" value="1" checked> Email the client about this change</label>
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
