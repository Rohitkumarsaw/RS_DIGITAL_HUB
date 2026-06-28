<?php
$pageTitle = 'Testimonials';
require_once __DIR__ . '/includes/header.php';

$testimonials = getTestimonials(100);
?>

<div class="container">
    <div class="section">
        <div class="section-title">
            <h2>What Our Customers Say</h2>
            <p>Trusted by thousands of satisfied customers worldwide</p>
        </div>

        <?php if (empty($testimonials)): ?>
        <div class="empty-state">
            <h3>No Testimonials Yet</h3>
            <p>Check back soon for customer reviews.</p>
            <a href="<?php echo SITE_URL; ?>/contact.php" class="btn btn-primary">Contact Us</a>
        </div>
        <?php else: ?>
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
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
