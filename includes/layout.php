<?php
declare(strict_types=1);

require_once __DIR__ . '/pet-mark.php';

/**
 * Print the top of a page: <head>, header and navigation.
 * Options: description, body_class, section ('home'|'account'|'admin'), scripts (extra JS files), noindex
 */
function layout_start(string $title, array $opt = []): void
{
    $GLOBALS['__layout'] = $opt;
    $siteName = (string) cfg('app.name');
    $pageTitle = $title === '' ? $siteName . ' | Pet Sitting & Dog Walking in Auckland' : $title . ' | ' . $siteName;
    $description = $opt['description'] ?? 'Loving, reliable pet sitting, dog walking, daily visits and pet taxi in Auckland. Book online with Something About Pets.';
    $bodyClass = $opt['body_class'] ?? 'home-pages-2 inner-page';
    require __DIR__ . '/head.php';
    require __DIR__ . '/header.php';
    if (($opt['section'] ?? '') !== 'home') {
        echo '<main id="main" class="sap-page">';
    }
}

function layout_end(): void
{
    $opt = $GLOBALS['__layout'] ?? [];
    if (($opt['section'] ?? '') !== 'home') {
        echo '</main>';
    }
    require __DIR__ . '/footer.php';
}

/** Coloured title band used at the top of inner pages. */
function page_banner(string $title, string $subtitle = ''): void
{
    echo '<section class="sap-banner"><div class="container"><h1>' . e($title) . '</h1>';
    if ($subtitle !== '') {
        echo '<p>' . e($subtitle) . '</p>';
    }
    echo '</div></section>';
}

/** Tab-style navigation for the client account and admin areas. */
function sub_nav(array $items, string $active): void
{
    echo '<nav class="sap-subnav" aria-label="Section"><div class="container"><ul>';
    foreach ($items as $key => [$label, $href]) {
        $cur = $key === $active ? ' class="active" aria-current="page"' : '';
        echo '<li><a href="' . e(url($href)) . '"' . $cur . '>' . e($label) . '</a></li>';
    }
    echo '</ul></div></nav>';
}

function account_nav(string $active): void
{
    sub_nav([
        'dashboard' => ['My bookings', '/account/'],
        'book'      => ['Book a service', '/book.php'],
        'pets'      => ['My pets', '/account/pets.php'],
        'profile'   => ['Profile', '/account/profile.php'],
    ], $active);
}

function admin_nav(string $active): void
{
    $unread = 0;
    $pending = 0;
    try {
        $unread = (int) scalar('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0');
        $pending = (int) scalar("SELECT COUNT(*) FROM bookings WHERE status = 'pending'");
    } catch (Throwable $e) {
    }
    sub_nav([
        'dashboard'    => ['Overview', '/admin/'],
        'bookings'     => ['Bookings' . ($pending ? " ($pending)" : ''), '/admin/bookings.php'],
        'availability' => ['Availability', '/admin/availability.php'],
        'services'     => ['Services & prices', '/admin/services.php'],
        'clients'      => ['Clients', '/admin/clients.php'],
        'messages'     => ['Messages' . ($unread ? " ($unread)" : ''), '/admin/messages.php'],
    ], $active);
}
