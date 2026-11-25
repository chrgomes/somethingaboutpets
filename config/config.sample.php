<?php
/**
 * Something About Pets – site configuration (SAMPLE).
 *
 * 1. Copy this file to ONE of these places and fill in the real values:
 *      - one folder ABOVE public_html, named  sap-config.php   (recommended on GoDaddy)
 *      - or  config/config.php  inside the site (this folder is blocked from the web by .htaccess)
 * 2. Never commit the real config file to GitHub (config/config.php is in .gitignore).
 */
return [
    'app' => [
        'name'      => 'Something About Pets',
        // Full public address of the site, no trailing slash. Used in email links.
        'url'       => 'https://somethingaboutpets.com',
        // If the site lives in a sub-folder (e.g. https://example.com/pets) put '/pets' here, otherwise ''.
        'base_path' => '',
        'timezone'  => 'Pacific/Auckland',
        'debug'     => false,
        // One-time key that unlocks install.php. Make it long and random, run the installer, then delete install.php.
        'install_key' => 'change-me-to-a-long-random-string',
    ],

    'business' => [
        'email'    => 'somethingaboutpets@gmail.com',   // where booking + contact notifications are sent
        'location' => 'Auckland, New Zealand',
        'phone'    => '',                               // optional, shown in the footer when set
        // Leave a link empty ('') to hide that icon in the footer.
        'social'   => [
            'facebook'  => '',
            'instagram' => '',
            'twitter'   => '',
        ],
    ],

    'db' => [
        // GoDaddy cPanel: create the database + user under "MySQL Databases".
        // Names are usually prefixed with your cPanel username, e.g. cpuser_pets
        'dsn'  => 'mysql:host=localhost;dbname=CPANELUSER_pets;charset=utf8mb4',
        'user' => 'CPANELUSER_petsuser',
        'pass' => 'database-password-here',
    ],

    'mail' => [
        // "From" address. Use an address on your own domain (create it in cPanel > Email Accounts).
        'from_email' => 'no-reply@somethingaboutpets.com',
        'from_name'  => 'Something About Pets',
        // SMTP is more reliable than PHP mail(). Needs PHPMailer (see README). Leave enabled=false to use mail().
        'smtp' => [
            'enabled'  => false,
            'host'     => 'mail.somethingaboutpets.com',
            'port'     => 465,
            'secure'   => 'ssl',      // 'ssl' for 465, 'tls' for 587
            'username' => 'no-reply@somethingaboutpets.com',
            'password' => 'email-account-password',
        ],
    ],

    'booking' => [
        'slot_step_minutes'     => 30,  // how far apart the start times offered to clients are
        'buffer_minutes'        => 15,  // travel/gap time kept free between bookings
        'min_notice_hours'      => 12,  // how soon a booking can start
        'max_days_ahead'        => 90,  // how far ahead clients can book
        'max_stay_nights'       => 60,
        'cancel_notice_hours'   => 24,  // clients can cancel online up to this many hours before the start
        'currency_symbol'       => '$',
    ],
];
