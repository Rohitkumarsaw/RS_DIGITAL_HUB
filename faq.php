<?php
$pageTitle = 'FAQ';
require_once __DIR__ . '/includes/header.php';

$faqs = getFaqs();
?>

<div class="container">
    <div class="section">
        <div class="section-title">
            <h2>Frequently Asked Questions</h2>
            <p>Find answers to common questions about our products and services</p>
        </div>

        <?php if (empty($faqs)): ?>
        <div class="empty-state">
            <h3>No FAQs Yet</h3>
            <p>Check back soon for frequently asked questions.</p>
            <a href="<?php echo SITE_URL; ?>/contact.php" class="btn btn-primary">Contact Us</a>
        </div>
        <?php else: ?>
        <div class="faq-list">
            <?php foreach ($faqs as $faq): ?>
            <div class="faq-item">
                <div class="faq-question">
                    <span><?php echo sanitize($faq['question']); ?></span>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
                <div class="faq-answer">
                    <p><?php echo nl2br(sanitize($faq['answer'])); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="text-center mt-4">
            <p class="text-muted">Still have questions?</p>
            <a href="<?php echo SITE_URL; ?>/contact.php" class="btn btn-primary">Contact Support</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
