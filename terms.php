<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/policy.php';

/*
 * Plain-language terms for a New Zealand pet-sitting business. A solid starting point, not legal advice:
 * the owner should read it through (and get it checked if she wants) before launch.
 * Values such as cancellation notice and charges come from the config file (policy + booking sections).
 */
$cancelHours = (int) cfg('booking.cancel_notice_hours', 24);
$late = (int) cfg('policy.late_cancel_charge', 50);
$noShow = (int) cfg('policy.no_show_charge', 100);
$sections = [
    'about'       => 'About these terms',
    'account'     => 'Your account',
    'bookings'    => 'Bookings',
    'your-pet'    => 'Your pet\'s information',
    'access'      => 'Keys and access to your home',
    'care'        => 'How we care for your pet',
    'health'      => 'Health, vaccinations and behaviour',
    'emergencies' => 'Emergencies and vet care',
    'cancel'      => 'Cancellations and changes',
    'payment'     => 'Prices and payment',
    'liability'   => 'Responsibility and liability',
    'photos'      => 'Photos and updates',
    'ending'      => 'Ending or refusing service',
    'law'         => 'Your rights and the law',
    'contact'     => 'Contact us',
];

layout_start('Terms & Conditions', ['description' => 'Booking terms for Something About Pets pet sitting, dog walking, daily visits and pet taxi in Auckland.']);
page_banner('Terms & Conditions', 'Last updated ' . policy_date(__FILE__));
?>
<div class="container sap-narrow sap-prose sap-policy">
    <?php policy_nav('terms'); ?>
    <p class="sap-lead">These terms explain how bookings with <?= biz() ?> work and what we each agree to. We've kept them as plain as we can. By creating an account or making a booking, you agree to them.</p>
    <?php policy_toc($sections); ?>

    <h2 id="about">1. About these terms</h2>
    <p>"We", "us" and "our" means <?= biz() ?>, based in <?= e(cfg('business.location')) ?>. "You" means the person who books our services and is responsible for the pet. These terms apply together with our <a href="<?= e(url('/payments.php')) ?>">Payment &amp; Subscription Policy</a> and <a href="<?= e(url('/privacy.php')) ?>">Privacy Policy</a>. We may update these terms; the version on this page when you book applies to that booking.</p>

    <h2 id="account">2. Your account</h2>
    <ul>
        <li>You must be 18 or older and the owner or carer of the pets you book for.</li>
        <li>Keep your login details private. You're responsible for bookings made from your account.</li>
        <li>Please keep your contact details, address and emergency contact up to date.</li>
    </ul>

    <h2 id="bookings">3. Bookings</h2>
    <ul>
        <li>A booking made online is a <strong>request</strong> until we confirm it. We hold the time for you while it waits, and email you when it's confirmed or if we can't take it.</li>
        <li>Walks, visits and pet taxi trips are booked for a set time. We'll arrive as close to that time as we can, allowing for traffic and the pet before yours.</li>
        <li>In-house sitting is booked by date. We'll agree arrival and departure times with you before the stay.</li>
        <li>For new clients we may ask for a free meet and greet before the first booking, so we can meet your pet and learn their routine.</li>
    </ul>

    <h2 id="your-pet">4. Your pet's information</h2>
    <p>Our care depends on what you tell us. You agree to give accurate, complete information in your pet's profile, including health conditions, medication, allergies, behaviour, bite history, fears and escape habits, and to update it when anything changes. If you don't tell us about something that affects your pet's care or safety, we can't be responsible for the result.</p>

    <h2 id="access">5. Keys and access to your home</h2>
    <ul>
        <li>You'll give us a key, door code or other way in. We keep keys labelled with a code, never your address.</li>
        <li>If we can't get in at the booked time (for example a key doesn't work or nobody's home), we'll try to reach you. If we can't, the booking may be charged as a no-show at <?= $noShow ?>% of the price.</li>
        <li>We'll leave your home as we found it, lock up and follow any instructions you give about alarms, lights or plants.</li>
    </ul>

    <h2 id="care">6. How we care for your pet</h2>
    <ul>
        <li>We follow the feeding, walking and care instructions in your pet's profile and booking notes.</li>
        <li>Dogs are walked on a lead unless you've told us in writing that off-lead is fine <em>and</em> it's safe and legal where we are. We follow Auckland Council dog access rules.</li>
        <li>We don't walk dogs together with other clients' dogs unless you've agreed to it.</li>
        <li>In extreme weather we may shorten a walk or swap it for indoor play, for your pet's safety.</li>
    </ul>

    <h2 id="health">7. Health, vaccinations and behaviour</h2>
    <ul>
        <li>Pets must be in good health, and dogs and cats should be up to date with their vaccinations and flea and worm treatments. Please add vaccination dates to your pet's profile.</li>
        <li>Dogs must be registered with Auckland Council and microchipped as required by law.</li>
        <li>Please tell us if your pet has ever bitten, attacked or seriously threatened a person or animal. We may decline or end a booking if we believe caring for a pet is unsafe.</li>
    </ul>

    <h2 id="emergencies">8. Emergencies and vet care</h2>
    <ul>
        <li>If your pet seems unwell or is injured while in our care, we'll contact you straight away, then your emergency contact.</li>
        <li>If we can't reach either of you and we believe your pet needs urgent care, you authorise us to take them to your nominated vet, or the nearest available vet, and to agree to treatment up to the spending limit you set in your pet's profile (or more, if it's needed to save your pet's life or relieve severe suffering).</li>
        <li>You're responsible for vet bills and any reasonable costs of getting your pet to the vet.</li>
    </ul>

    <h2 id="cancel">9. Cancellations and changes</h2>
    <ul>
        <li><strong>By you:</strong> cancel online from your account up to <?= $cancelHours ?> hours before the booking starts, at no charge. Any online payment is refunded in full.</li>
        <li><strong>Late cancellations:</strong> inside <?= $cancelHours ?> hours, please contact us. <?= $late > 0 ? "A late cancellation may be charged at {$late}% of the price, because we've usually turned other bookings away." : 'We won\'t charge for late cancellations, but please let us know as early as you can.' ?></li>
        <li><strong>By us:</strong> if we have to cancel (for example illness or an emergency) we'll tell you as soon as possible, try to help find cover, and refund anything you've paid for that booking in full.</li>
        <li>To change a time or date, contact us. Changes depend on availability.</li>
    </ul>

    <h2 id="payment">10. Prices and payment</h2>
    <p>Prices are shown when you book, in New Zealand dollars. How and when to pay, refunds and recurring bookings are covered in our <a href="<?= e(url('/payments.php')) ?>">Payment &amp; Subscription Policy</a>.</p>

    <h2 id="liability">11. Responsibility and liability</h2>
    <ul>
        <li>We'll care for your pet with the skill and attention of a responsible, experienced pet carer.</li>
        <li>Animals can be unpredictable. We're not responsible for illness, injury or loss that happens despite reasonable care, or that results from a condition, behaviour or risk you didn't tell us about.</li>
        <li>You're responsible for damage or injury your pet causes to people, other animals or property while in our care, unless it was caused by our negligence.</li>
        <li>Nothing in these terms limits rights you have that can't be excluded by law.</li>
    </ul>

    <h2 id="photos">12. Photos and updates</h2>
    <p>We love sending updates. We may take photos of your pet during bookings to send to you. We'll only post photos of your pet publicly (for example on social media) if you've said that's OK in your pet's profile, and we'll never show your address or anything that identifies your home.</p>

    <h2 id="ending">13. Ending or refusing service</h2>
    <p>You can stop using our services at any time and ask us to delete your account. We may refuse or stop providing services if a pet is unsafe to care for, our safety is at risk, payments aren't made, or these terms aren't followed. Any payment for bookings we cancel is refunded.</p>

    <h2 id="law">14. Your rights and the law</h2>
    <p>These terms are governed by New Zealand law. If you're using our services as a consumer, you have rights under the Consumer Guarantees Act 1993 and the Fair Trading Act 1986, and nothing in these terms takes those rights away. If something goes wrong, please talk to us first and we'll do our best to put it right.</p>

    <h2 id="contact">15. Contact us</h2>
    <p>Questions about these terms? Email <?= biz_email() ?>.</p>
</div>
<?php layout_end();
