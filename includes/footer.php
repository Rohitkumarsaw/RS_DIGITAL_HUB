    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <h3><?php echo getSetting('site_name', SITE_NAME); ?></h3>
                    <p>Your trusted marketplace for premium digital products. Quality templates, scripts, eBooks, and more.</p>
                </div>
                <div class="footer-links">
                    <h4>Quick Links</h4>
                    <a href="<?php echo SITE_URL; ?>/index.php">Home</a>
                    <a href="<?php echo SITE_URL; ?>/products.php">Products</a>
                    <a href="<?php echo SITE_URL; ?>/about.php">About Us</a>
                    <a href="<?php echo SITE_URL; ?>/notices.php">Notice Board</a>
                    <a href="<?php echo SITE_URL; ?>/faq.php">FAQ</a>
                    <a href="<?php echo SITE_URL; ?>/testimonials.php">Testimonials</a>
                </div>
                <div class="footer-links">
                    <h4>Account</h4>
                    <?php if (isLoggedIn()): ?>
                    <a href="<?php echo SITE_URL; ?>/profile.php">Profile</a>
                    <a href="<?php echo SITE_URL; ?>/orders.php">My Orders</a>
                    <a href="<?php echo SITE_URL; ?>/logout.php">Logout</a>
                    <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/login.php">Login</a>
                    <a href="<?php echo SITE_URL; ?>/signup.php">Sign Up</a>
                    <?php endif; ?>
                </div>
                <div class="footer-links">
                    <h4>Support</h4>
                    <a href="<?php echo SITE_URL; ?>/contact.php">Contact Us</a>
                    <a href="<?php echo SITE_URL; ?>/faq.php">FAQ</a>
                    <a href="<?php echo SITE_URL; ?>/tickets.php">Support Tickets</a>
                </div>
                <div class="footer-links">
                    <h4>Legal</h4>
                    <a href="<?php echo SITE_URL; ?>/policy.php?slug=privacy-policy">Privacy Policy</a>
                    <a href="<?php echo SITE_URL; ?>/policy.php?slug=terms-and-conditions">Terms & Conditions</a>
                    <a href="<?php echo SITE_URL; ?>/policy.php?slug=refund-cancellation-policy">Refund Policy</a>
                    <a href="<?php echo SITE_URL; ?>/policy.php?slug=shipping-delivery-policy">Shipping Policy</a>
                    <a href="<?php echo SITE_URL; ?>/policy.php?slug=copyright-dmca-policy">Copyright & DMCA</a>
                    <a href="<?php echo SITE_URL; ?>/policy.php?slug=license">License</a>
                </div>
            </div>
            <div class="footer-bottom">
                <div class="footer-social">
                    <?php
                    $footerSocials = $pdo->query("SELECT * FROM social_links WHERE is_active = 1 AND url != '' ORDER BY sort_order ASC LIMIT 6")->fetchAll();
                    foreach ($footerSocials as $fs):
                    ?>
                    <a href="<?php echo sanitize($fs['url']); ?>" target="_blank" rel="noopener noreferrer" class="footer-social-icon footer-social-<?php echo $fs['platform']; ?>" title="<?php echo sanitize($fs['label'] ?? ucfirst($fs['platform'])); ?>">
                        <?php echo getFooterSocialIcon($fs['platform']); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                <p><?php echo getSetting('site_footer_text', '© ' . date('Y') . ' ' . SITE_NAME . '. All rights reserved.'); ?></p>
            </div>
        </div>
    </footer>

    <!-- Bottom Navigation (Mobile) -->
    <nav class="bottom-nav">
        <a href="<?php echo SITE_URL; ?>/index.php" class="<?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            Home
        </a>
        <a href="<?php echo SITE_URL; ?>/products.php" class="<?php echo $currentPage === 'products.php' ? 'active' : ''; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
            Products
        </a>
        <a href="<?php echo SITE_URL; ?>/profile.php" class="<?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Profile
        </a>
    </nav>

    <script src="<?php echo SITE_URL; ?>/assets/js/main.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/main.js'); ?>"></script>
    <?php if (isset($extraJS)): ?>
        <?php foreach ($extraJS as $js): ?>
            <script src="<?php echo SITE_URL . $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
