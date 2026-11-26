<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/policy.php';

/*
 * Privacy Policy written for the New Zealand Privacy Act 2020. A plain-language starting point,
 * not legal advice: the owner should read it through before launch.
 */
$sections = [
    'collect'   => 'What we collect',
    'why'       => 'Why we collect it',
    'share'     => 'Who we share it with',
    'storage'   => 'Where it\'s stored and how it\'s protected',
    'keep'      => 'How long we keep it',
    'cookies'   => 'Cookies',
    'rights'    => 'Your rights',
    'kids'      => 'Children',
    'changes'   => 'Changes to this policy',
    'contact'   => 'Contact and complaints',
];

layout_start('Privacy Policy', ['description' => 'How Something About Pets collects, uses and protects your personal information.']);
page_banner('Privacy Policy', 'Last updated ' . policy_date(__FILE__));
?>
<div class="container sap-narrow sap-prose sap-policy">
    <?php policy_nav('privacy'); ?>
    <p class="sap-lead"><?= biz() ?> respects your privacy. This policy explains what information we collect, why, and how we look after it, in line with the New Zealand Privacy Act 2020.</p>
    <?php policy_toc($sections); ?>

    <h2 id="collect">1. What we collect</h2>
    <p><strong>About you:</strong></p>
    <ul>
        <li>Your name, email, phone number and address.</li>
        <li>An emergency contact's name and phone number, if you give them.</li>
        <li>Notes about access to your home (for example where a key is kept). We never store alarm or door codes in our system; please give those to us directly.</li>
        <li>Your bookings and messages you send us.</li>
        <li>Payment records: the amount, date, method and a reference from Stripe or PayPal. <strong>We never receive or store your card number.</strong></li>
    </ul>
    <p><strong>About your pets:</strong></p>
    <ul>
        <li>Photos, name, breed, age, weight, sex, colour and markings, microchip and council registration numbers.</li>
        <li>Health and care details: vaccinations (including records you upload), medication, allergies, feeding, walking preferences, behaviour and anxiety triggers.</li>
        <li>Your vet's details and the emergency vet authorisation you give us.</li>
    </ul>
    <p><strong>Automatically:</strong> basic technical information such as your IP address and browser, which our web host records in its server logs for security.</p>

    <h2 id="why">2. Why we collect it</h2>
    <p>We only use your information to:</p>
    <ul>
        <li>arrange, confirm and carry out bookings, and care for your pets safely</li>
        <li>contact you about your bookings and your pets, including in an emergency</li>
        <li>take payments, issue receipts and process refunds</li>
        <li>keep your account secure and prevent misuse of the website</li>
        <li>meet our legal and tax obligations</li>
    </ul>
    <p>We don't sell your information, and we don't use it for advertising. We'll only email you about your account and bookings, unless you ask to hear about other things.</p>

    <h2 id="share">3. Who we share it with</h2>
    <ul>
        <li><strong>Payment providers:</strong> when you pay online, Stripe or PayPal process the payment under their own privacy policies (<a href="https://stripe.com/nz/privacy" target="_blank" rel="noopener">Stripe</a>, <a href="https://www.paypal.com/nz/legalhub/privacy-full" target="_blank" rel="noopener">PayPal</a>). We send them your email address and the booking amount.</li>
        <li><strong>Vets:</strong> if your pet needs care, we'll share their details with the vet treating them.</li>
        <li><strong>Our web and email hosting providers</strong>, which store the website, its database and our email on our behalf.</li>
        <li><strong>Anyone the law requires us to</strong>, for example if asked by a court or the police.</li>
    </ul>
    <p>We'll never share your information with anyone else without your permission.</p>

    <h2 id="storage">4. Where it's stored and how it's protected</h2>
    <p>Your information is stored in our website's database with our hosting provider, GoDaddy, which may store data on servers outside New Zealand (for example in the United States). We take reasonable steps to keep it safe:</p>
    <ul>
        <li>the website uses an encrypted (HTTPS) connection</li>
        <li>passwords are stored scrambled (hashed), so even we can't read them</li>
        <li>only you and the business owner can see your account, pets and bookings</li>
        <li>uploaded pet photos and documents are only shown to you and to us</li>
        <li>repeated failed logins are blocked to stop people guessing passwords</li>
    </ul>
    <p>If a privacy breach ever happens that's likely to cause serious harm, we'll tell you and the Office of the Privacy Commissioner as the law requires.</p>

    <h2 id="keep">5. How long we keep it</h2>
    <p>We keep your information while you have an account with us. If you ask us to delete your account, we'll delete your details, pet profiles and uploaded files within 30 days, except records we must keep by law (such as payment records for tax, usually 7 years).</p>

    <h2 id="cookies">6. Cookies</h2>
    <p>We use one essential cookie that keeps you logged in and protects our forms from misuse. We don't use advertising or tracking cookies. Our pages load fonts and icons from Google Fonts and Lordicon, which may see your IP address when your browser fetches them.</p>

    <h2 id="rights">7. Your rights</h2>
    <p>You can ask to see the personal information we hold about you and ask us to correct it. You can update most of it yourself in <a href="<?= e(url('/account/profile.php')) ?>">your profile</a> and <a href="<?= e(url('/account/pets.php')) ?>">pet profiles</a>. To get a copy of your information or delete your account, email <?= biz_email() ?>. We'll reply within 20 working days.</p>

    <h2 id="kids">8. Children</h2>
    <p>Our services are for adults. You must be 18 or older to create an account.</p>

    <h2 id="changes">9. Changes to this policy</h2>
    <p>We may update this policy from time to time. The date at the top shows when it last changed. If we make a significant change, we'll let account holders know by email.</p>

    <h2 id="contact">10. Contact and complaints</h2>
    <p>Questions or concerns about your privacy? Email <?= biz_email() ?>. If you're not happy with how we've handled a privacy concern, you can contact the <a href="https://www.privacy.org.nz" target="_blank" rel="noopener">Office of the Privacy Commissioner</a>.</p>
</div>
<?php layout_end();
