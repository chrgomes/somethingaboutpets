<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

/*
 * DRAFT – please have the business owner review this text (and get advice if needed)
 * before going live. It is a plain-language starting point, not legal advice.
 */
layout_start('Privacy Policy', ['description' => 'How Something About Pets collects, uses and protects your personal information.']);
page_banner('Privacy Policy');
?>
<div class="container sap-narrow sap-prose">
    <p><em>Last updated <?= e(date('j F Y', filemtime(__FILE__))) ?></em></p>
    <p>Something About Pets ("we", "us") respects your privacy. This policy explains what personal information we collect through this website and how we use it.</p>

    <h2>What we collect</h2>
    <ul>
        <li><strong>Your account details:</strong> name, email, phone number and address.</li>
        <li><strong>Your pets' details:</strong> names, breeds, ages and the care notes you give us (for example feeding, medication or vet details).</li>
        <li><strong>Bookings:</strong> the services, dates and notes for each booking.</li>
        <li><strong>Messages</strong> you send us through the contact form.</li>
    </ul>

    <h2>How we use it</h2>
    <p>We only use your information to provide and manage our pet care services: to arrange and confirm bookings, care for your pets, contact you about them, and reply to your messages. We don't sell your information or use it for unrelated marketing.</p>

    <h2>Who can see it</h2>
    <p>Only Something About Pets can see your information. It is stored securely with our web hosting provider. We may share it if the law requires us to, or with a vet in an emergency involving your pet.</p>

    <h2>Cookies</h2>
    <p>We use a single essential cookie to keep you logged in and protect our forms. We don't use advertising or tracking cookies.</p>

    <h2>Keeping it safe</h2>
    <p>Passwords are stored in encrypted (hashed) form and the site uses a secure connection. Please keep your password private.</p>

    <h2>Your rights</h2>
    <p>Under the New Zealand Privacy Act 2020 you can ask to see or correct the personal information we hold about you. You can update most details yourself in <a href="<?= e(url('/account/profile.php')) ?>">your account</a>. To ask for a copy of your information, or to delete your account, email us at <a href="mailto:<?= e(cfg('business.email')) ?>"><?= e(cfg('business.email')) ?></a>.</p>

    <h2>Contact</h2>
    <p>Questions about this policy? Email <a href="mailto:<?= e(cfg('business.email')) ?>"><?= e(cfg('business.email')) ?></a>.</p>
</div>
<?php layout_end();
