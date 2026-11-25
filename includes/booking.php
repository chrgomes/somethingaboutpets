<?php
declare(strict_types=1);

/*
 * Booking + availability rules.
 *
 *  - "slot" services (walks, visits, pet taxi) take a start time inside the working hours set by the admin.
 *  - "stay" services (in-house sitting) take a date range and block those whole days.
 *  - One sitter: any pending or confirmed booking makes that time unavailable to everyone else.
 *  - Blocked dates (holidays, days off) are never bookable.
 */

const ACTIVE_STATUSES = ['pending', 'confirmed'];

function services(bool $activeOnly = true): array
{
    $sql = 'SELECT * FROM services' . ($activeOnly ? ' WHERE active = 1' : '') . ' ORDER BY sort_order, id';
    return rows($sql);
}

function service(int $id): ?array
{
    return row('SELECT * FROM services WHERE id = ?', [$id]);
}

function working_hours(): array
{
    $out = [];
    foreach (rows('SELECT * FROM working_hours ORDER BY weekday') as $r) {
        $out[(int) $r['weekday']] = $r;
    }
    return $out;
}

function is_blocked_date(string $date): bool
{
    return (bool) scalar('SELECT 1 FROM blocked_dates WHERE blocked_date = ?', [$date]);
}

function valid_date(string $date): bool
{
    $d = DateTime::createFromFormat('!Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/** Earliest bookable moment and latest bookable date. */
function booking_window(): array
{
    $earliest = time() + (int) cfg('booking.min_notice_hours', 12) * 3600;
    $latest   = strtotime('today +' . (int) cfg('booking.max_days_ahead', 90) . ' days 23:59:59');
    return [$earliest, $latest];
}

/** Active bookings that overlap [start, end). */
function overlapping_bookings(string $start, string $end, ?int $ignoreId = null): array
{
    $sql = "SELECT * FROM bookings WHERE status IN ('pending','confirmed') AND start_at < ? AND end_at > ?";
    $params = [$end, $start];
    if ($ignoreId) {
        $sql .= ' AND id <> ?';
        $params[] = $ignoreId;
    }
    return rows($sql, $params);
}

/**
 * Available start times (as 'H:i') for a slot service on a date.
 */
function available_slots(array $service, string $date): array
{
    if ($service['kind'] !== 'slot' || !valid_date($date) || is_blocked_date($date)) {
        return [];
    }
    $hours = working_hours()[(int) date('w', strtotime($date))] ?? null;
    if (!$hours || !(int) $hours['is_open']) {
        return [];
    }
    [$earliest, $latest] = booking_window();
    $duration = max(15, (int) $service['duration_minutes']) * 60;
    $step     = max(5, (int) cfg('booking.slot_step_minutes', 30)) * 60;
    $buffer   = max(0, (int) cfg('booking.buffer_minutes', 15)) * 60;

    $open  = strtotime("$date {$hours['open_time']}");
    $close = strtotime("$date {$hours['close_time']}");

    // Everything already booked that day (plus the day around it, for stays).
    $busy = [];
    foreach (overlapping_bookings(date('Y-m-d 00:00:00', $open), date('Y-m-d 23:59:59', $open)) as $b) {
        $busy[] = [strtotime($b['start_at']) - $buffer, strtotime($b['end_at']) + $buffer];
    }

    $slots = [];
    for ($t = $open; $t + $duration <= $close; $t += $step) {
        if ($t < $earliest || $t > $latest) {
            continue;
        }
        $end = $t + $duration;
        foreach ($busy as [$bs, $be]) {
            if ($t < $be && $end > $bs) {
                continue 2;
            }
        }
        $slots[] = date('H:i', $t);
    }
    return $slots;
}

/**
 * Check a requested booking. Returns [start_at, end_at] on success or throws InvalidArgumentException with a friendly message.
 */
function validate_booking_time(array $service, string $date, string $time, string $endDate, ?int $ignoreId = null): array
{
    if (!valid_date($date)) {
        throw new InvalidArgumentException('Please choose a date.');
    }
    [$earliest, $latest] = booking_window();

    if ($service['kind'] === 'stay') {
        if (!valid_date($endDate) || $endDate <= $date) {
            throw new InvalidArgumentException('Please choose a check-out date after the start date.');
        }
        $nights = (new DateTime($date))->diff(new DateTime($endDate))->days;
        if ($nights > (int) cfg('booking.max_stay_nights', 60)) {
            throw new InvalidArgumentException('That stay is longer than we can book online. Please contact us.');
        }
        if (strtotime("$date 12:00") < $earliest || strtotime($endDate) > $latest) {
            throw new InvalidArgumentException('Those dates are outside the dates we can book online.');
        }
        $blocked = scalar('SELECT COUNT(*) FROM blocked_dates WHERE blocked_date BETWEEN ? AND ?', [$date, $endDate]);
        if ((int) $blocked > 0) {
            throw new InvalidArgumentException('We are unavailable on some of those dates. Please pick different dates.');
        }
        $start = "$date 00:00:00";
        $end   = "$endDate 23:59:59";
        if (overlapping_bookings($start, $end, $ignoreId)) {
            throw new InvalidArgumentException('Sorry, those dates are already booked. Please pick different dates.');
        }
        return [$start, $end];
    }

    if (!preg_match('/^\d{2}:\d{2}$/', $time) || !in_array($time, available_slots($service, $date), true)) {
        throw new InvalidArgumentException('Sorry, that time is no longer available. Please pick another time.');
    }
    $startTs = strtotime("$date $time");
    return [date('Y-m-d H:i:s', $startTs), date('Y-m-d H:i:s', $startTs + (int) $service['duration_minutes'] * 60)];
}

/** Dates in the coming window that are fully unavailable (blocked, closed or booked by a stay). Used by the date picker. */
function unavailable_dates(): array
{
    [, $latest] = booking_window();
    $from = date('Y-m-d');
    $to   = date('Y-m-d', $latest);
    $out = [];
    foreach (rows('SELECT blocked_date FROM blocked_dates WHERE blocked_date BETWEEN ? AND ?', [$from, $to]) as $r) {
        $out[$r['blocked_date']] = true;
    }
    $stays = rows(
        "SELECT b.start_at, b.end_at FROM bookings b JOIN services s ON s.id = b.service_id
         WHERE s.kind = 'stay' AND b.status IN ('pending','confirmed') AND b.end_at >= ? AND b.start_at <= ?",
        ["$from 00:00:00", "$to 23:59:59"]
    );
    foreach ($stays as $s) {
        foreach (date_range(substr($s['start_at'], 0, 10), substr($s['end_at'], 0, 10)) as $d) {
            $out[$d] = true;
        }
    }
    ksort($out);
    return array_keys($out);
}

function closed_weekdays(): array
{
    $closed = [];
    foreach (working_hours() as $wd => $h) {
        if (!(int) $h['is_open']) {
            $closed[] = $wd;
        }
    }
    return $closed;
}

function booking_price(array $service, string $start, string $end): ?int
{
    if ($service['price_cents'] === null) {
        return null;
    }
    $price = (int) $service['price_cents'];
    if ($service['kind'] === 'stay') {
        $nights = max(1, (new DateTime(substr($start, 0, 10)))->diff(new DateTime(substr($end, 0, 10)))->days);
        return $price * $nights;
    }
    return $price;
}

function booking_when(array $b): string
{
    if (($b['kind'] ?? '') === 'stay') {
        return fmt_date($b['start_at']) . ' → ' . fmt_date($b['end_at']);
    }
    return fmt_date($b['start_at']) . ', ' . fmt_time($b['start_at']) . '–' . fmt_time($b['end_at']);
}

function booking_pets(int $bookingId): array
{
    return rows(
        'SELECT p.* FROM booking_pets bp JOIN pets p ON p.id = bp.pet_id WHERE bp.booking_id = ? ORDER BY p.name',
        [$bookingId]
    );
}

function booking_with_details(int $id): ?array
{
    return row(
        'SELECT b.*, s.name AS service_name, s.kind, u.name AS client_name, u.email AS client_email, u.phone AS client_phone, u.address AS client_address
         FROM bookings b JOIN services s ON s.id = b.service_id JOIN users u ON u.id = b.user_id WHERE b.id = ?',
        [$id]
    );
}

function can_client_cancel(array $b): bool
{
    if (!in_array($b['status'], ACTIVE_STATUSES, true)) {
        return false;
    }
    return strtotime($b['start_at']) - time() >= (int) cfg('booking.cancel_notice_hours', 24) * 3600;
}

function booking_summary_text(array $b): string
{
    $pets = array_map(fn($p) => $p['name'] . ' (' . $p['species'] . ')', booking_pets((int) $b['id']));
    return "Booking #{$b['id']}\n"
        . "Service: {$b['service_name']}\n"
        . 'When: ' . booking_when($b) . "\n"
        . 'Pets: ' . ($pets ? implode(', ', $pets) : '-') . "\n"
        . 'Price: ' . money($b['price_cents'] === null ? null : (int) $b['price_cents']) . "\n"
        . ($b['notes'] !== '' && $b['notes'] !== null ? "Notes: {$b['notes']}\n" : '');
}
