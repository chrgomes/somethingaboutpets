<?php
declare(strict_types=1);

/** Shared bits for the Privacy, Terms and Payment policy pages. */

function policy_date(string $file): string
{
    $d = (string) cfg('policy.effective_date', '');
    return $d !== '' ? $d : date('j F Y', filemtime($file));
}

function policy_nav(string $active): void
{
    $pages = ['terms' => ['Terms & Conditions', '/terms.php'], 'payments' => ['Payment & Subscription Policy', '/payments.php'], 'privacy' => ['Privacy Policy', '/privacy.php']];
    echo '<nav class="sap-policy-nav" aria-label="Policies">';
    foreach ($pages as $k => [$label, $href]) {
        echo '<a href="' . e(url($href)) . '"' . ($k === $active ? ' class="active" aria-current="page"' : '') . '>' . e($label) . '</a>';
    }
    echo '</nav>';
}

/** "On this page" links built from [id => heading]. */
function policy_toc(array $sections): void
{
    echo '<details class="sap-toc" open><summary>On this page</summary><ol>';
    foreach ($sections as $id => $title) {
        echo '<li><a href="#' . e($id) . '">' . e($title) . '</a></li>';
    }
    echo '</ol></details>';
}

function biz(): string
{
    return e(cfg('policy.business_name', cfg('app.name')));
}

function biz_email(): string
{
    $m = e(cfg('business.email'));
    return '<a href="mailto:' . $m . '">' . $m . '</a>';
}
