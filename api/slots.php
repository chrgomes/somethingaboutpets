<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';

header('Cache-Control: no-store');
if (!current_user()) {
    json_response(['ok' => false, 'message' => 'Please log in.'], 401);
}

$service = service((int) ($_GET['service'] ?? 0));
$date = (string) ($_GET['date'] ?? '');
if (!$service || !(int) $service['active']) {
    json_response(['ok' => false, 'message' => 'Unknown service.'], 404);
}

if ($service['kind'] === 'stay') {
    $end = (string) ($_GET['end_date'] ?? '');
    if (!valid_date($date) || !valid_date($end)) {
        json_response(['ok' => false, 'kind' => 'stay', 'message' => 'Choose a start and end date.']);
    }
    try {
        [$s, $e] = validate_booking_time($service, $date, '', $end);
        $nights = (new DateTime($date))->diff(new DateTime($end))->days;
        json_response([
            'ok' => true, 'kind' => 'stay', 'nights' => $nights,
            'message' => "Those dates are available: $nights night" . ($nights === 1 ? '' : 's') . ', ' . money(booking_price($service, $s, $e)) . '.',
        ]);
    } catch (InvalidArgumentException $ex) {
        json_response(['ok' => false, 'kind' => 'stay', 'message' => $ex->getMessage()]);
    }
}

$slots = available_slots($service, $date);
json_response([
    'ok' => true,
    'kind' => 'slot',
    'slots' => array_map(fn($t) => ['value' => $t, 'label' => date('g:i a', strtotime("2000-01-01 $t"))], $slots),
    'message' => $slots ? '' : 'No times available on this day. Please try another date.',
]);
