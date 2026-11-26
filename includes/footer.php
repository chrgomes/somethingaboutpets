<?php
$__user = null;
try {
    $__user = current_user();
} catch (Throwable $e) {
}
$__social = array_filter((array) cfg('business.social', []));
$__icons = ['facebook' => 'bxl-facebook', 'instagram' => 'bxl-instagram', 'twitter' => 'bxl-twitter'];
$__phone = (string) cfg('business.phone', '');
?>
    <!-- ========== Footer Area Start ============= -->
    <div class="sap-footer-peek" aria-hidden="true">
        <?= str_replace('<svg ', '<svg data-draw-on-scroll ', pet_mark('pet-mark--peek', true, '')) ?>
    </div>
    <footer>
        <div class="container">
            <div class="row pt-90 pb-90 justify-content-center">
                <div class="col-lg-3 col-sm-6 order-lg-1 order-2 d-flex justify-content-sm-start justify-content-start">
                    <div class="footer-items contact">
                        <h3>Contacts</h3>
                        <div class="email mb-30">
                            <div class="email-icon"><i class="bi bi-envelope" aria-hidden="true"></i></div>
                            <div class="email-info">
                                <h6 class="mb-10"><a href="mailto:<?= e(cfg('business.email')) ?>"><?= e(cfg('business.email')) ?></a></h6>
                            </div>
                        </div>
                        <?php if ($__phone !== ''): ?>
                        <div class="email mb-30">
                            <div class="email-icon"><i class="bi bi-telephone" aria-hidden="true"></i></div>
                            <div class="email-info">
                                <h6 class="mb-10"><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $__phone)) ?>"><?= e($__phone) ?></a></h6>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="email">
                            <div class="email-icon"><i class="bi bi-geo-alt" aria-hidden="true"></i></div>
                            <div class="email-info">
                                <h6><?= e(cfg('business.location')) ?></h6>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 d-flex align-items-center order-lg-2 order-1 justify-content-sm-center justify-content-start">
                    <div class="footer-items">
                        <h2>Every Pet Deserves The Best, and We're Here To Provide Just That.</h2>
                        <div class="book-btn2 d-flex justify-content-center text-center">
                            <a class="primary-btn2" href="<?= e(url('/book.php')) ?>">Book Now</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6 d-flex justify-content-sm-end justify-content-start order-3">
                    <div class="footer-items opening-time">
                        <?php if ($__social): ?>
                        <h3>Socials</h3>
                        <ul class="social-icons">
                            <?php foreach ($__social as $net => $link): ?>
                                <li><a href="<?= e($link) ?>" target="_blank" rel="noopener" aria-label="<?= e(ucfirst($net)) ?>"><i class="bx <?= e($__icons[$net] ?? 'bx-link') ?>"></i></a></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php else: ?>
                        <h3>Account</h3>
                        <ul class="sap-footer-links">
                            <?php if (!empty($__user)): ?>
                                <li><a href="<?= e(url($__user['role'] === 'admin' ? '/admin/' : '/account/')) ?>"><?= $__user['role'] === 'admin' ? 'Admin' : 'My account' ?></a></li>
                                <li><a href="<?= e(url('/book.php')) ?>">Book online</a></li>
                                <li><a href="<?= e(url('/logout.php')) ?>">Log out</a></li>
                            <?php else: ?>
                                <li><a href="<?= e(url('/login.php')) ?>">Log in</a></li>
                                <li><a href="<?= e(url('/register.php')) ?>">Create an account</a></li>
                                <li><a href="<?= e(url('/book.php')) ?>">Book online</a></li>
                            <?php endif; ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="row border-top">
                <div class="col-lg-6">
                    <div class="copyright-area">
                        <p>&copy; <?= date('Y') ?> somethingaboutpets.com. All Rights Reserved.</p>
                    </div>
                </div>
                <div class="col-lg-6 d-flex justify-content-lg-end justify-content-center">
                    <ul class="footer-btm-menu">
                        <li><a href="<?= e(url('/privacy.php')) ?>">Privacy Policy</a></li>
                        <li><a href="<?= e(url('/terms.php')) ?>">Terms &amp; Conditions</a></li>
                        <li><a href="<?= e(url('/payments.php')) ?>">Payments &amp; Refunds</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>
    <!-- ========== Footer Area end ============= -->

    <button type="button" class="yarn-top" aria-label="Back to top">
        <svg viewBox="0 0 60 60" aria-hidden="true">
            <path class="yarn-thread" d="M48 44 C56 50 52 58 44 57" fill="none" stroke="#e83a30" stroke-width="2.5" stroke-linecap="round"/>
            <g class="yarn-ball">
                <circle cx="30" cy="30" r="22" fill="#e83a30"/>
                <g fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" opacity=".85">
                    <path d="M12 22 C24 26 34 38 38 50"/>
                    <path d="M10 32 C20 32 30 42 30 52"/>
                    <path d="M22 9 C24 22 36 30 51 30"/>
                    <path d="M32 8 C33 18 42 24 52 22"/>
                    <path d="M14 15 C26 14 40 20 46 12"/>
                </g>
            </g>
        </svg>
    </button>

    <script src="<?= e(asset('assets/js/jquery-3.6.0.min.js')) ?>"></script>
    <script src="<?= e(asset('assets/js/bootstrap.bundle.min.js')) ?>"></script>
    <script src="<?= e(asset('assets/js/swiper-bundle.min.js')) ?>"></script>
    <script src="<?= e(asset('assets/js/jquery.nice-select.js')) ?>"></script>
    <script src="<?= e(asset('assets/js/main.js')) ?>"></script>
    <script src="<?= e(asset('assets/js/pets-anim.js')) ?>"></script>
    <script src="<?= e(asset('assets/js/site.js')) ?>"></script>
    <?php foreach (($opt['scripts'] ?? []) as $__s): ?>
    <script src="<?= e(asset($__s)) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
