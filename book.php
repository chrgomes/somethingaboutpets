<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$user = require_login();
$services = services();
$pets = rows('SELECT * FROM pets WHERE user_id = ? ORDER BY name', [$user['id']]);

$src = is_post() ? $_POST : $_GET;
$serviceId = (int) ($src['service'] ?? ($services[0]['id'] ?? 0));
$service = null;
foreach ($services as $s) {
    if ((int) $s['id'] === $serviceId) {
        $service = $s;
    }
}
$service ??= $services[0] ?? null;

$date    = (string) ($src['date'] ?? '');
$endDate = (string) ($src['end_date'] ?? '');
$time    = (string) ($src['time'] ?? '');
$petIds  = array_map('intval', (array) ($src['pets'] ?? (count($pets) === 1 ? [$pets[0]['id']] : [])));
$notes   = mb_substr(trim((string) ($src['notes'] ?? '')), 0, 2000);
$errors  = [];

[$earliestTs, $latestTs] = booking_window();
$minDate = date('Y-m-d', $earliestTs);
$maxDate = date('Y-m-d', $latestTs);

if (is_post() && $service) {
    csrf_check();
    $ownPetIds = array_map(fn($p) => (int) $p['id'], $pets);
    $petIds = array_values(array_intersect($petIds, $ownPetIds));
    if (!$petIds) {
        $errors[] = 'Please choose which pet(s) this booking is for.';
    }
    if (!rate_limit('booking', 10, 3600)) {
        $errors[] = 'You have made a lot of bookings in a short time. Please contact us if you need more.';
    }
    if (!$errors) {
        try {
            $bookingId = with_booking_lock(function () use ($service, $date, $time, $endDate, $user, $petIds, $notes) {
                [$start, $end] = validate_booking_time($service, $date, $time, $endDate);
                q('INSERT INTO bookings (user_id, service_id, start_at, end_at, status, price_cents, notes, created_at, updated_at)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [$user['id'], $service['id'], $start, $end, 'pending', booking_price($service, $start, $end), $notes, now_sql(), now_sql()]);
                $id = (int) db()->lastInsertId();
                foreach ($petIds as $pid) {
                    q('INSERT INTO booking_pets (booking_id, pet_id) VALUES (?, ?)', [$id, $pid]);
                }
                return $id;
            });

            $b = booking_with_details($bookingId);
            $summary = booking_summary_text($b);
            send_mail($user['email'], 'We received your booking request (#' . $bookingId . ')',
                "Hi {$user['name']},\n\nThanks for booking with us! Your request is held for you and we'll confirm it shortly.\n\n"
                . $summary . "\nSee your bookings: " . abs_url('/account/') . mail_footer());
            notify_business('New booking request #' . $bookingId . ' – ' . $b['service_name'],
                "New booking request from {$user['name']} ({$user['email']}, {$user['phone']}).\n\n" . $summary
                . "\nApprove or decline: " . abs_url('/admin/booking.php?id=' . $bookingId) . mail_footer(), $user['email']);

            flash('success', 'Booking request sent! We\'ll email you when it\'s confirmed.');
            redirect('/account/booking.php?id=' . $bookingId);
        } catch (InvalidArgumentException $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$slots = ($service && $service['kind'] === 'slot' && valid_date($date)) ? available_slots($service, $date) : [];

layout_start('Book a service', ['noindex' => true, 'scripts' => ['assets/js/book.js']]);
page_banner('Book a service', 'Choose a service, pick a time, and we\'ll take it from there.');
account_nav('book');
?>
<div class="container sap-wide">
    <?= render_flashes() ?>
    <?php foreach ($errors as $er): ?><div class="sap-alert sap-alert-error" role="alert"><?= e($er) ?></div><?php endforeach; ?>

    <?php if (!$services): ?>
        <div class="sap-card"><p>Online booking isn't open yet. Please <a href="<?= e(url('/#contact')) ?>">contact us</a>.</p></div>
    <?php elseif (!$pets): ?>
        <div class="sap-card sap-empty">
            <?= pet_mark('pet-mark--empty', true, '') ?>
            <h2>First, tell us about your pets</h2>
            <p>Add your pets once and you can choose them for every booking.</p>
            <a class="primary-btn2" href="<?= e(url('/account/pets.php?add=1&next=' . rawurlencode('/book.php?service=' . (int) $service['id']))) ?>">Add a pet</a>
        </div>
    <?php else: ?>
    <form method="post" class="sap-form sap-book" id="book-form"
          data-slots-url="<?= e(url('/api/slots.php')) ?>">
        <?= csrf_field() ?>

        <section class="sap-card sap-step">
            <h2><span class="sap-step-no">1</span> Choose a service</h2>
            <div class="sap-choice-grid">
                <?php foreach ($services as $s): ?>
                    <label class="sap-choice">
                        <input type="radio" name="service" value="<?= (int) $s['id'] ?>" data-kind="<?= e($s['kind']) ?>"
                               <?= (int) $s['id'] === (int) $service['id'] ? 'checked' : '' ?> required>
                        <span class="sap-choice-body">
                            <strong><?= e($s['name']) ?></strong>
                            <span class="sap-muted">
                                <?= $s['kind'] === 'stay' ? 'Per night' : (int) $s['duration_minutes'] . ' min' ?>
                                · <?= e(money($s['price_cents'] === null ? null : (int) $s['price_cents'])) ?>
                            </span>
                            <?php if ($s['description']): ?><span class="sap-choice-desc"><?= e($s['description']) ?></span><?php endif; ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="sap-card sap-step">
            <h2><span class="sap-step-no">2</span> Pick a date <span class="sap-when-slot">and time</span></h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="date"><span class="sap-when-slot">Date</span><span class="sap-when-stay">Start date</span></label>
                    <input id="date" type="date" name="date" required min="<?= e($minDate) ?>" max="<?= e($maxDate) ?>" value="<?= e($date) ?>">
                </div>
                <div class="col-md-6 sap-when-stay">
                    <label for="end_date">End date</label>
                    <input id="end_date" type="date" name="end_date" min="<?= e($minDate) ?>" max="<?= e($maxDate) ?>" value="<?= e($endDate) ?>">
                </div>
            </div>
            <div class="sap-when-slot">
                <p class="sap-label">Available times</p>
                <div id="slot-list" class="sap-slots" aria-live="polite">
                    <?php if (!valid_date($date)): ?>
                        <p class="sap-muted">Choose a date to see available times.</p>
                    <?php elseif (!$slots): ?>
                        <p class="sap-muted">No times available on this day. Please try another date.</p>
                    <?php else: foreach ($slots as $sl): ?>
                        <label class="sap-slot"><input type="radio" name="time" value="<?= e($sl) ?>" <?= $sl === $time ? 'checked' : '' ?>><span><?= e(date('g:i a', strtotime("2000-01-01 $sl"))) ?></span></label>
                    <?php endforeach; endif; ?>
                </div>
                <noscript><button type="submit" formmethod="get" class="sap-link-btn">Show available times</button></noscript>
            </div>
            <p class="sap-when-stay sap-muted" id="stay-info">We stay with your pets from the start date until the end date.</p>
        </section>

        <section class="sap-card sap-step">
            <h2><span class="sap-step-no">3</span> Which pets?</h2>
            <div class="sap-choice-grid sap-choice-grid--pets">
                <?php foreach ($pets as $p): ?>
                    <label class="sap-choice sap-choice--pet">
                        <input type="checkbox" name="pets[]" value="<?= (int) $p['id'] ?>" <?= in_array((int) $p['id'], $petIds, true) ? 'checked' : '' ?>>
                        <span class="sap-choice-body">
                            <i class="bi <?= $p['species'] === 'cat' ? 'bi-emoji-heart-eyes' : 'bi-heart' ?>" aria-hidden="true"></i>
                            <strong><?= e($p['name']) ?></strong>
                            <span class="sap-muted"><?= e(ucfirst($p['species'])) ?><?= $p['breed'] ? ' · ' . e($p['breed']) : '' ?></span>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
            <p class="sap-muted"><a href="<?= e(url('/account/pets.php?add=1&next=' . rawurlencode('/book.php'))) ?>">+ Add another pet</a></p>
        </section>

        <section class="sap-card sap-step">
            <h2><span class="sap-step-no">4</span> Anything we should know?</h2>
            <label for="notes" class="visually-hidden">Notes</label>
            <textarea id="notes" name="notes" rows="4" maxlength="2000" placeholder="Key or door code arrangements, feeding, routes your dog loves…"><?= e($notes) ?></textarea>
            <p class="sap-muted">You can cancel online up to <?= (int) cfg('booking.cancel_notice_hours', 24) ?> hours before your booking.</p>
            <button class="primary-btn2" type="submit">Send booking request</button>
        </section>
    </form>
    <?php endif; ?>
</div>
<?php layout_end();
