<?php
$__user = null;
try {
    $__user = current_user();
} catch (Throwable $e) {
    // Database not configured yet: show the public site anyway.
}
$__home = url('/');
?>
    <!-- ========== header ============= -->
    <header class="header-area style-2">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <div class="header-logo">
                <a class="sap-logo" href="<?= e($__home) ?>" aria-label="<?= e(cfg('app.name')) ?> home">
                    <?= pet_mark('pet-mark--header', true, '') ?>
                    <img class="sap-wordmark" src="<?= e(url('/assets/images/wordmark-black.png')) ?>" alt="somethingaboutpets" width="295" height="22">
                </a>
            </div>
            <div class="main-menu">
                <div class="mobile-logo-area d-lg-none d-flex justify-content-between align-items-center">
                    <div class="mobile-logo-wrap">
                        <a class="sap-logo" href="<?= e($__home) ?>" aria-label="<?= e(cfg('app.name')) ?> home">
                            <?= pet_mark('', false, '') ?>
                        </a>
                    </div>
                    <button type="button" class="menu-close-btn" aria-label="Close menu">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <ul class="menu-list">
                    <li><a href="<?= e($__home) ?>#about">About</a></li>
                    <li><a href="<?= e($__home) ?>#services">Services</a></li>
                    <li><a href="<?= e($__home) ?>#how-it-works">How It Works</a></li>
                    <li><a href="<?= e($__home) ?>#reviews">Reviews</a></li>
                    <li><a href="<?= e($__home) ?>#contact">Contact</a></li>
                    <?php if ($__user): ?>
                        <li class="sap-menu-account d-lg-none"><a href="<?= e(url($__user['role'] === 'admin' ? '/admin/' : '/account/')) ?>"><?= $__user['role'] === 'admin' ? 'Admin' : 'My Account' ?></a></li>
                        <li class="sap-menu-account d-lg-none"><a href="<?= e(url('/logout.php')) ?>">Log out</a></li>
                    <?php else: ?>
                        <li class="sap-menu-account d-lg-none"><a href="<?= e(url('/login.php')) ?>">Log in</a></li>
                    <?php endif; ?>
                </ul>
                <div class="d-lg-none sap-mobile-cta">
                    <a class="primary-btn2" href="<?= e(url('/book.php')) ?>">Book Now</a>
                </div>
            </div>

            <div class="nav-right d-flex justify-content-end align-items-center">
                <?php if ($__user): ?>
                    <a class="sap-account-link d-none d-lg-inline-flex" href="<?= e(url($__user['role'] === 'admin' ? '/admin/' : '/account/')) ?>"><i class="bi bi-person-circle" aria-hidden="true"></i> <?= $__user['role'] === 'admin' ? 'Admin' : 'My Account' ?></a>
                    <a class="sap-account-link sap-logout d-none d-lg-inline-flex" href="<?= e(url('/logout.php')) ?>" aria-label="Log out" title="Log out"><i class="bi bi-box-arrow-right" aria-hidden="true"></i></a>
                <?php else: ?>
                    <a class="sap-account-link d-none d-lg-inline-flex" href="<?= e(url('/login.php')) ?>"><i class="bi bi-person-circle" aria-hidden="true"></i> Log in</a>
                <?php endif; ?>
                <a class="primary-btn2 sap-header-cta d-none d-sm-inline-flex" href="<?= e(url('/book.php')) ?>">Book Now</a>
                <button type="button" class="sidebar-button mobile-menu-btn" aria-label="Open menu">
                    <i class="bi bi-list"></i>
                </button>
            </div>
        </div>
    </header>
    <!-- ========== header end ============= -->
