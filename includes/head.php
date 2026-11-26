<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($description) ?>">
    <?php if (!empty($opt['noindex'])): ?><meta name="robots" content="noindex"><?php endif; ?>
    <meta name="theme-color" content="#e83a30">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e($siteName) ?>">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($description) ?>">
    <meta property="og:image" content="<?= e(abs_url('/assets/images/bg/hero-dog-781x713.jpg')) ?>">

    <link rel="icon" href="<?= e(url('/assets/images/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="icon" href="<?= e(url('/assets/images/favicon-32.png')) ?>" type="image/png" sizes="32x32">
    <link rel="apple-touch-icon" href="<?= e(url('/assets/images/apple-touch-icon.png')) ?>">

    <link rel="stylesheet" href="<?= e(asset('assets/css/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/boxicons.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/bootstrap-icons.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/swiper-bundle.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/nice-select.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/pets-anim.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">

    <script>try{if(sessionStorage.getItem('sap-logo-drawn')==='1')document.documentElement.className+=' sap-logo-seen';sessionStorage.setItem('sap-logo-drawn','1')}catch(e){}</script>
    <script src="https://cdn.lordicon.com/lordicon.js" defer></script>
</head>

<body class="<?= e($bodyClass) ?>">
<a class="sap-skip" href="#main">Skip to content</a>
