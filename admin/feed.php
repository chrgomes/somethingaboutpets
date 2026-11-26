<?php
/**
 * Private calendar feed (iCalendar / .ics) of upcoming bookings, for Google Calendar or Apple Calendar.
 * Protected by a secret token instead of a login, because calendar apps can't log in.
 */
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';

$token = (string) ($_GET['token'] ?? '');
if (!empty(config_all()['_sample']) || !hash_equals(calendar_feed_token(), $token)) {
    http_response_code(404);
    exit('Not found.');
}

function ics_text(string $s): string
{
    return str_replace(["\\", ";", ",", "\r\n", "\n", "\r"], ["\\\\", "\\;", "\\,", "\\n", "\\n", ""], $s);
}

function ics_line(string $line): string
{
    // Fold long lines at 74 bytes as the iCalendar format requires.
    $out = '';
    while (strlen($line) > 74) {
        $cut = 74;
        while ($cut > 0 && (ord($line[$cut]) & 0xC0) === 0x80) {
            $cut--; // don't split a multi-byte character
        }
        $out .= substr($line, 0, $cut) . "\r\n ";
        $line = substr($line, $cut);
    }
    return $out . $line . "\r\n";
}

function ics_utc(string $local): string
{
    return (new DateTime($local))->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z');
}

$from = date('Y-m-d 00:00:00', strtotime('-30 days'));
$to = date('Y-m-d 23:59:59', strtotime('+365 days'));
$host = parse_url(abs_url('/'), PHP_URL_HOST) ?: 'somethingaboutpets';

$ics = ics_line('BEGIN:VCALENDAR') . ics_line('VERSION:2.0') . ics_line('PRODID:-//Something About Pets//Bookings//EN')
    . ics_line('CALSCALE:GREGORIAN') . ics_line('METHOD:PUBLISH')
    . ics_line('X-WR-CALNAME:' . ics_text(cfg('app.name') . ' bookings'))
    . ics_line('X-WR-TIMEZONE:' . date_default_timezone_get())
    . ics_line('REFRESH-INTERVAL;VALUE=DURATION:PT1H') . ics_line('X-PUBLISHED-TTL:PT1H');

foreach (bookings_between($from, $to, ['pending', 'confirmed']) as $b) {
    $summary = ($b['status'] === 'pending' ? '[Awaiting approval] ' : '') . $b['service_name'] . ' – ' . $b['client_name']
        . ($b['pet_names'] ? ' (' . implode(', ', $b['pet_names']) . ')' : '');
    $desc = 'Client: ' . $b['client_name'] . "\nPhone: " . ($b['client_phone'] ?: '-') . "\nEmail: " . $b['client_email']
        . ($b['notes'] ? "\nNotes: " . $b['notes'] : '') . "\n\n" . abs_url('/admin/booking.php?id=' . (int) $b['id']);

    $ics .= ics_line('BEGIN:VEVENT')
        . ics_line('UID:booking-' . (int) $b['id'] . '@' . $host)
        . ics_line('DTSTAMP:' . ics_utc($b['updated_at']));
    if ($b['kind'] === 'stay') {
        $ics .= ics_line('DTSTART;VALUE=DATE:' . date('Ymd', strtotime($b['start_at'])))
            . ics_line('DTEND;VALUE=DATE:' . date('Ymd', strtotime(substr($b['end_at'], 0, 10) . ' +1 day')));
    } else {
        $ics .= ics_line('DTSTART:' . ics_utc($b['start_at'])) . ics_line('DTEND:' . ics_utc($b['end_at']));
    }
    $ics .= ics_line('SUMMARY:' . ics_text($summary))
        . ics_line('DESCRIPTION:' . ics_text($desc))
        . ($b['client_address'] ? ics_line('LOCATION:' . ics_text($b['client_address'])) : '')
        . ics_line('STATUS:' . ($b['status'] === 'confirmed' ? 'CONFIRMED' : 'TENTATIVE'))
        . ics_line('URL:' . abs_url('/admin/booking.php?id=' . (int) $b['id']))
        . ics_line('END:VEVENT');
}
$ics .= ics_line('END:VCALENDAR');

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: inline; filename="bookings.ics"');
header('Cache-Control: private, max-age=900');
header('X-Robots-Tag: noindex');
echo $ics;
