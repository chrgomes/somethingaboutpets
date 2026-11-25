<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

/*
 * DRAFT – please have the business owner review and complete these terms
 * (payment, insurance, keys, emergencies) before going live. Not legal advice.
 */
$cancelHours = (int) cfg('booking.cancel_notice_hours', 24);
layout_start('Terms & Conditions', ['description' => 'Booking terms for Something About Pets pet sitting and dog walking services.']);
page_banner('Terms & Conditions');
?>
<div class="container sap-narrow sap-prose">
    <p><em>Last updated <?= e(date('j F Y', filemtime(__FILE__))) ?></em></p>

    <h2>Bookings</h2>
    <p>Bookings made online are requests until we confirm them by email. We hold the time for you while your request is waiting for confirmation. We may decline a booking if we can't provide the care your pet needs.</p>

    <h2>Your pet's information</h2>
    <p>Please keep your pets' details up to date in your account, including feeding, medication, vet details and anything about their behaviour we should know. We rely on this information to care for them safely.</p>

    <h2>Cancellations</h2>
    <p>You can cancel a booking online up to <?= $cancelHours ?> hours before it starts. For later changes or cancellations, please contact us directly.</p>

    <h2>Prices and payment</h2>
    <p>Prices are shown when you book. Where a service shows "Price on request", we'll confirm the price with you before the booking is confirmed.</p>

    <h2>Emergencies</h2>
    <p>If your pet becomes unwell or is injured while in our care, we will try to contact you straight away and, if needed, take your pet to your nominated vet or the nearest available vet.</p>

    <h2>Contact</h2>
    <p>Questions? Email <a href="mailto:<?= e(cfg('business.email')) ?>"><?= e(cfg('business.email')) ?></a>.</p>
</div>
<?php layout_end();
