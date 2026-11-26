<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/policy.php';

/*
 * Payment & Subscription Policy. Matches how the site works: bookings are paid after they're confirmed,
 * by card through Stripe or by PayPal (or another way if the owner allows it); there are no automatic
 * recurring charges. Values come from the config file (payments, policy and booking sections).
 */
$cancelHours = (int) cfg('booking.cancel_notice_hours', 24);
$late = (int) cfg('policy.late_cancel_charge', 50);
$noShow = (int) cfg('policy.no_show_charge', 100);
$due = (string) cfg('policy.payment_due', 'before the booking starts');
$other = (string) cfg('policy.other_payment_methods', '');
$currency = strtoupper((string) cfg('payments.currency', 'NZD'));
$sections = [
    'prices'      => 'Prices',
    'when'        => 'When you pay',
    'how'         => 'How you can pay',
    'security'    => 'Keeping your payment safe',
    'receipts'    => 'Receipts',
    'refunds'     => 'Cancellations and refunds',
    'recurring'   => 'Regular bookings and subscriptions',
    'problems'    => 'Payment problems and disputes',
    'changes'     => 'Changes to this policy',
];

layout_start('Payment & Subscription Policy', ['description' => 'How payment, refunds and regular bookings work at Something About Pets.']);
page_banner('Payment & Subscription Policy', 'Last updated ' . policy_date(__FILE__));
?>
<div class="container sap-narrow sap-prose sap-policy">
    <?php policy_nav('payments'); ?>
    <p class="sap-lead">The short version: you only pay once we've confirmed your booking. You can pay securely by card or PayPal, we never see your card details, and we never charge you automatically.</p>
    <?php policy_toc($sections); ?>

    <h2 id="prices">1. Prices</h2>
    <ul>
        <li>All prices are in New Zealand dollars (<?= e($currency) ?>).</li>
        <li>Walks, daily visits and pet taxi trips are priced per booking. In-house sitting is priced per night.</li>
        <li>The price for each booking is shown when you book and in your account. If a service shows "Price on request", we'll agree the price with you before we confirm.</li>
        <li>We may change our prices from time to time. Changes never affect bookings that are already confirmed.</li>
    </ul>

    <h2 id="when">2. When you pay</h2>
    <ul>
        <li>Making a booking request doesn't cost anything. Nothing is charged until we've confirmed the booking.</li>
        <li>Once confirmed, you'll see a <strong>Pay now</strong> button on the booking in your account and a link in your confirmation email.</li>
        <li>Please pay <?= e($due) ?>. If a booking isn't paid by then, we'll get in touch, and we may cancel it if we can't reach you.</li>
    </ul>

    <h2 id="how">3. How you can pay</h2>
    <ul>
        <li><strong>Card (via Stripe):</strong> Visa, Mastercard and American Express, plus Apple Pay and Google Pay where your device supports them.</li>
        <li><strong>PayPal:</strong> pay with your PayPal balance, bank account or a card saved in PayPal.</li>
        <?php if ($other !== ''): ?><li><strong>Other ways:</strong> by arrangement, you can also pay by <?= e($other) ?>. We'll mark your booking as paid when we receive it.</li><?php endif; ?>
    </ul>
    <p>We don't add a surcharge for paying by card or PayPal.</p>

    <h2 id="security">4. Keeping your payment safe</h2>
    <p>Payments are handled by <a href="https://stripe.com/nz/privacy" target="_blank" rel="noopener">Stripe</a> and <a href="https://www.paypal.com/nz/legalhub/privacy-full" target="_blank" rel="noopener">PayPal</a>, two of the world's largest payment providers. When you pay, you're taken to their secure checkout page. Your card or PayPal details go straight to them. <?= biz() ?> never sees or stores your full card number, and our website never asks for it. We only receive confirmation that you've paid, the amount, and a payment reference.</p>

    <h2 id="receipts">5. Receipts</h2>
    <p>We email you a receipt as soon as your payment comes through, and the booking shows as <strong>Paid</strong> in your account. Stripe or PayPal may also send you their own receipt.</p>

    <h2 id="refunds">6. Cancellations and refunds</h2>
    <ul>
        <li><strong>You cancel <?= $cancelHours ?>+ hours before the booking:</strong> cancel from your account and any online payment is refunded in full automatically.</li>
        <li><strong>You cancel less than <?= $cancelHours ?> hours before:</strong> <?= $late > 0 ? "we may keep {$late}% of the price and refund the rest." : 'please contact us; we\'ll refund you in full.' ?></li>
        <li><strong>We can't get access to your pet</strong> at the booked time (for example a missing key): <?= $noShow > 0 ? "the booking may be charged at {$noShow}% of the price." : 'we\'ll rebook you at no charge.' ?></li>
        <li><strong>We cancel or decline a booking:</strong> you get a full refund.</li>
        <li><strong>Something wasn't right?</strong> Please tell us within 7 days and we'll make it right, which may include a full or partial refund.</li>
    </ul>
    <p>Refunds go back to the card or PayPal account you paid with. They usually show up within 5–10 working days, depending on your bank. Payments made another way are refunded by bank transfer.</p>

    <h2 id="recurring">7. Regular bookings and subscriptions</h2>
    <ul>
        <li><strong>We don't run automatic subscriptions or recurring charges.</strong> Each booking is paid separately, and we never take money from your card or PayPal account without you choosing to pay.</li>
        <li>If your pet needs regular care (like weekday walks), you can book as many sessions as you need, and you can cancel any of them under the rules above.</li>
        <li>If we offer packages or memberships in future, we'll show the full price, what's included, how often you'll be charged and how to cancel <em>before</em> you sign up, and you'll be able to cancel at any time. Those terms will be added to this page.</li>
    </ul>

    <h2 id="problems">8. Payment problems and disputes</h2>
    <ul>
        <li>If a payment fails, you haven't been charged. You can try again or choose another way to pay.</li>
        <li>If you think you've been charged incorrectly or twice, email us first at <?= biz_email() ?> and we'll sort it out quickly, usually within 2 working days.</li>
        <li>You can also raise a dispute with your bank or PayPal, but talking to us first is usually the fastest way to get your money back.</li>
        <li>Your rights under the Consumer Guarantees Act 1993 and Fair Trading Act 1986 aren't affected by this policy.</li>
    </ul>

    <h2 id="changes">9. Changes to this policy</h2>
    <p>We may update this policy. The version in place when you pay applies to that payment. If we make a significant change, we'll let account holders know by email.</p>
</div>
<?php layout_end();
