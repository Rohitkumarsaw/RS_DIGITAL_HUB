<?php
$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';

$featuredProducts = getProducts(['sort' => 'popular']);
$featuredProducts = array_slice($featuredProducts, 0, 8);
$testimonials = getTestimonials();
$faqs = array_slice(getFaqs(), 0, 5);
$productTypes = getProductTypes();
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <h1>Premium Digital Products</h1>
        <p>Discover high-quality templates, scripts, eBooks, and more. Instant downloads, secure payments, and lifetime access.</p>

        <!-- Hero Search Bar -->
        <form action="<?php echo SITE_URL; ?>/products.php" method="GET" class="hero-search">
            <input type="text" name="search" placeholder="Search for templates, scripts, eBooks...">
            <button type="submit" class="btn btn-lg" style="background:var(--primary);color:var(--text-inverse);border:none">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                Search
            </button>
        </form>

        <div class="d-flex gap-1 justify-center flex-wrap">
            <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-lg" style="background:var(--bg-card);color:var(--primary);border:1.5px solid var(--border-color)">
                Explore Products
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
            <?php if (!isLoggedIn()): ?>
            <a href="<?php echo SITE_URL; ?>/signup.php" class="btn btn-lg btn-outline" style="border-color:white;color:white">
                Get Started Free
            </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Browse Categories -->
<section class="section" style="background:var(--bg-tertiary)">
    <div class="container">
        <div class="section-title">
            <h2>Browse Categories</h2>
            <p>Explore products by category</p>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:1rem;max-width:800px;margin:0 auto">
            <?php foreach ($productTypes as $key => $label): ?>
            <a href="<?php echo SITE_URL; ?>/products.php?type=<?php echo $key; ?>" class="category-card" style="display:flex;flex-direction:column;align-items:center;gap:0.5rem;padding:1.5rem 1rem;border:2px solid var(--border-color);border-radius:var(--border-radius);background:var(--bg-secondary);text-decoration:none;color:inherit;transition:all 0.2s">
                <span style="font-size:1rem;font-weight:600"><?php echo $label; ?></span>
                <span style="font-size:0.8rem;color:var(--text-muted)">Browse Products →</span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<style>
.category-card:hover, .category-card:focus-visible {
    border-color: var(--primary) !important;
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(99,102,241,0.1);
}
.contact-map-card iframe { max-width: 100%; width: 100%; }
</style>

<!-- Featured Products -->
<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>Featured Products</h2>
            <p>Our most popular digital products</p>
        </div>

        <?php if (empty($featuredProducts)): ?>
        <div class="empty-state">
            <h3>No Products Yet</h3>
            <p>Check back soon for amazing digital products!</p>
        </div>
        <?php else: ?>
        <div class="product-grid">
            <?php foreach ($featuredProducts as $product): ?>
            <?php
            $price = $product['sale_price'] ?? $product['price'];
            $hasSale = $product['sale_price'] && $product['sale_price'] < $product['price'];
            $discount = $hasSale ? round((($product['price'] - $product['sale_price']) / $product['price']) * 100) : 0;
            $screenshots = json_decode($product['screenshots'] ?? '[]', true);
            $thumbnail = !empty($screenshots) ? $screenshots[0] : null;
            ?>
            <div class="product-card">
                <div class="product-card-image">
                    <?php if ($thumbnail): ?>
                    <img src="<?php echo SITE_URL . '/uploads/screenshots/' . sanitize($thumbnail); ?>" alt="<?php echo sanitize($product['title']); ?>">
                    <?php elseif (!empty($product['image_url'])): ?>
                    <img src="<?php echo sanitize($product['image_url']); ?>" alt="<?php echo sanitize($product['title']); ?>">
                    <?php else: ?>
                    <div style="position:absolute;top:0;left:0;width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:var(--bg-tertiary)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    </div>
                    <?php endif; ?>
                    <?php if ($hasSale): ?>
                    <span class="product-card-badge">-<?php echo $discount; ?>%</span>
                    <?php endif; ?>
                </div>
                <div class="product-card-body">
                    <div class="product-card-category"><?php echo getProductTypeLabel($product['product_type'] ?? ''); ?></div>
                    <h3 class="product-card-title">
                        <a href="<?php echo SITE_URL; ?>/product.php?slug=<?php echo urlencode($product['slug']); ?>"><?php echo sanitize($product['title']); ?></a>
                    </h3>
                    <div class="product-card-price">
                        <span class="price-current"><?php echo formatPrice($price); ?></span>
                        <?php if ($hasSale): ?>
                        <span class="price-original"><?php echo formatPrice($product['price']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="product-card-developer">by <a href="<?php echo SITE_URL; ?>/store.php?developer=<?php echo $product['developer_id']; ?>" style="color:var(--text-muted);text-decoration:underline;text-underline-offset:2px"><?php echo sanitize($product['developer_store'] ?: $product['developer_name'] ?: 'Digital Hub'); ?></a></div>
                    <div class="product-card-footer">
                        <a href="<?php echo SITE_URL; ?>/product.php?slug=<?php echo urlencode($product['slug']); ?>" class="btn btn-secondary btn-sm">View</a>
                        <?php if (isLoggedIn()): ?>
                        <form method="POST" action="<?php echo SITE_URL; ?>/buy-now.php" style="display:inline">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <button type="submit" class="btn btn-primary btn-sm">Buy Now</button>
                        </form>
                        <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-primary btn-sm">Login to Buy</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-4">
            <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-outline btn-lg">View All Products</a>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Stats Section -->
<section class="section" style="background:var(--bg-tertiary)">
    <div class="container">
        <div class="stats-grid">
            <?php
            $totalProducts = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active' AND (developer_id IS NULL OR EXISTS (SELECT 1 FROM subscriptions WHERE developer_id = products.developer_id AND status = 'active' AND expiry_date > NOW()))")->fetchColumn();
            $totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
            $totalOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'completed'")->fetchColumn();
            $totalDevs = $pdo->query("SELECT COUNT(DISTINCT developer_id) FROM subscriptions WHERE status = 'active' AND expiry_date > NOW()")->fetchColumn();
            ?>
            <div class="stat-card text-center">
                <div class="stat-card-value"><?php echo number_format($totalProducts); ?>+</div>
                <div class="stat-card-label">Digital Products</div>
            </div>
            <div class="stat-card text-center">
                <div class="stat-card-value"><?php echo number_format($totalUsers); ?>+</div>
                <div class="stat-card-label">Happy Customers</div>
            </div>
            <div class="stat-card text-center">
                <div class="stat-card-value"><?php echo number_format($totalDevs); ?>+</div>
                <div class="stat-card-label">Happy Developers</div>
            </div>
            <div class="stat-card text-center">
                <div class="stat-card-value"><?php echo number_format($totalOrders); ?>+</div>
                <div class="stat-card-label">Orders Completed</div>
            </div>
            <div class="stat-card text-center">
                <div class="stat-card-value">24/7</div>
                <div class="stat-card-label">Support Available</div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials -->
<?php if (!empty($testimonials)): ?>
<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>What Our Customers Say</h2>
            <p>Trusted by thousands of satisfied customers</p>
        </div>

        <div class="testimonials-grid">
            <?php foreach ($testimonials as $testimonial): ?>
            <div class="testimonial-card">
                <div class="testimonial-rating">
                    <?php for ($i = 0; $i < 5; $i++): ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="<?php echo $i < $testimonial['rating'] ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    <?php endfor; ?>
                </div>
                <p class="testimonial-text">"<?php echo sanitize($testimonial['message']); ?>"</p>
                <div class="testimonial-author">- <?php echo sanitize($testimonial['name']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="<?php echo SITE_URL; ?>/testimonials.php" class="btn btn-outline">View All Testimonials</a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- FAQ Section -->
<?php if (!empty($faqs)): ?>
<section class="section" style="background:var(--bg-tertiary)">
    <div class="container">
        <div class="section-title">
            <h2>Frequently Asked Questions</h2>
            <p>Find answers to common questions</p>
        </div>

        <div class="faq-list">
            <?php foreach ($faqs as $faq): ?>
            <div class="faq-item">
                <div class="faq-question">
                    <span><?php echo sanitize($faq['question']); ?></span>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
                <div class="faq-answer">
                    <p><?php echo sanitize($faq['answer']); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-4">
            <a href="<?php echo SITE_URL; ?>/faq.php" class="btn btn-outline">View All FAQs</a>
        </div>
    </div>
</section>
<?php endif; ?>

<?php
$contactEmail = getSetting('contact_email', '');
$contactPhone = getSetting('contact_phone', '');
$contactAddress = getSetting('contact_address', '');
$contactHours = getSetting('contact_hours', '');
$contactMapEmbed = getSetting('contact_map_embed', '');
$socials = $pdo->query("SELECT * FROM social_links WHERE is_active = 1 AND url != '' ORDER BY sort_order ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $subject = sanitize($_POST['subject']);
    $message = sanitize($_POST['message']);
    if (!empty($name) && !empty($email) && !empty($subject) && !empty($message) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS contact_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL,
            subject VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_read (is_read),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $subject, $message]);
        $ticketUserId = isLoggedIn() ? $_SESSION['user_id'] : $pdo->query("SELECT id FROM users WHERE role IN ('admin', 'super_admin') ORDER BY id ASC LIMIT 1")->fetchColumn();
        if ($ticketUserId) createTicket($ticketUserId, "Contact: $subject", "From: $name ($email)\n\n$message");
        echo '<script>alert("Thank you for contacting us. We will respond shortly.");</script>';
    }
}
?>

<!-- Contact Section -->
<section class="section contact-section">
    <div class="container">
        <div class="section-title">
            <h2>Get In Touch</h2>
            <p>Have a question? We'd love to hear from you.</p>
        </div>
        <div class="contact-grid">
            <div class="card">
                <div class="card-body">
                    <h3 class="contact-heading">Contact Information</h3>
                    <?php if ($contactEmail): ?>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        </div>
                        <div class="contact-item-text">
                            <span class="contact-item-label">Email</span>
                            <a href="mailto:<?php echo sanitize($contactEmail); ?>" class="contact-item-value"><?php echo sanitize($contactEmail); ?></a>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($contactPhone): ?>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        </div>
                        <div class="contact-item-text">
                            <span class="contact-item-label">Phone</span>
                            <span class="contact-item-value"><?php echo sanitize($contactPhone); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($contactAddress): ?>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        </div>
                        <div class="contact-item-text">
                            <span class="contact-item-label">Address</span>
                            <span class="contact-item-value"><?php echo nl2br(sanitize($contactAddress)); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($contactHours): ?>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </div>
                        <div class="contact-item-text">
                            <span class="contact-item-label">Support Hours</span>
                            <span class="contact-item-value"><?php echo sanitize($contactHours); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($socials)): ?>
                    <div class="contact-social-section">
                        <span class="contact-social-label">Follow Us</span>
                        <div class="contact-social-icons">
                            <?php foreach ($socials as $social): ?>
                            <a href="<?php echo sanitize($social['url']); ?>" target="_blank" rel="noopener" class="contact-social-link social-<?php echo $social['platform']; ?>" title="<?php echo sanitize($social['label'] ?? ucfirst($social['platform'])); ?>">
                                <?php echo getSocialIcon($social['platform']); ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h3 class="contact-heading" style="margin:0">Send us a Message</h3></div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label for="home-name">Your Name</label>
                            <input type="text" id="home-name" name="name" class="form-control" placeholder="Enter your name" required value="<?php echo isLoggedIn() ? sanitize(getCurrentUser()['name']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="home-email">Email Address</label>
                            <input type="email" id="home-email" name="email" class="form-control" placeholder="Enter your email" required value="<?php echo isLoggedIn() ? sanitize(getCurrentUser()['email']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="home-subject">Subject</label>
                            <input type="text" id="home-subject" name="subject" class="form-control" placeholder="What is this about?" required>
                        </div>
                        <div class="form-group">
                            <label for="home-message">Message</label>
                            <textarea id="home-message" name="message" class="form-control" rows="4" placeholder="Describe your question or issue..." required></textarea>
                        </div>
                        <button type="submit" name="contact_submit" class="btn btn-primary btn-block">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                            Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <?php if (!empty($contactMapEmbed) && strpos($contactMapEmbed, '<iframe') !== false): ?>
        <div class="card mt-3 contact-map-card" style="overflow:hidden">
            <div class="card-body" style="padding:0">
                <?php echo $contactMapEmbed; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- CTA Section -->
<section class="section">
    <div class="container text-center">
        <h2>Ready to Get Started?</h2>
        <p>Join thousands of customers and start downloading premium digital products today.</p>
        <a href="<?php echo SITE_URL; ?>/signup.php" class="btn btn-primary btn-lg">Create Free Account</a>
    </div>
</section>

<?php
function getSocialIcon($platform) {
    $icons = [
        'facebook' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
        'instagram' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>',
        'twitter' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
        'youtube' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>',
        'linkedin' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
        'telegram' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>',
        'whatsapp' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>',
        'github' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 2.41 1.005.695-.19 1.44-.285 2.19-.285.75 0 1.495.095 2.19.285 1.405-1.327 2.41-1.005 2.41-1.005.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>',
        'pinterest' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.668.967-2.914 2.171-2.914 1.023 0 1.518.769 1.518 1.69 0 1.029-.655 2.568-.994 3.995-.283 1.194.599 2.169 1.777 2.169 2.133 0 3.772-2.249 3.772-5.495 0-2.873-2.064-4.882-5.012-4.882-3.414 0-5.418 2.561-5.418 5.207 0 1.031.397 2.138.893 2.738a.36.36 0 01.083.345l-.333 1.36c-.053.22-.174.267-.402.161-1.499-.698-2.436-2.889-2.436-4.649 0-3.785 2.75-7.262 7.929-7.262 4.163 0 7.398 2.967 7.398 6.931 0 4.136-2.607 7.464-6.227 7.464-1.216 0-2.359-.631-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24 12.017 24c6.624 0 11.99-5.367 11.99-11.988C24.007 5.367 18.641.001 12.017.001z"/></svg>',
        'threads' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M12.186 24h-.007c-3.581-.024-6.334-1.205-8.184-3.509C2.35 18.44 1.5 15.586 1.472 12.01v-.017c.03-3.579.879-6.43 2.525-8.482C5.845 1.205 8.6 0 12.186 0h.007c2.786.02 5.086.968 6.826 2.816 1.677 1.785 2.576 4.29 2.672 7.444v.01c-.097 3.155-.996 5.66-2.672 7.444-1.74 1.848-4.04 2.796-6.826 2.816l-.007-.01z"/></svg>',
    ];
    return $icons[$platform] ?? '';
}
?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
