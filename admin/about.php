<?php
$pageTitle = 'About Us';
require_once __DIR__ . '/includes/header.php';

$currentSection = getCurrentSection();

// Get current about us content for this section
$about = getContentByType('about_us', $currentSection);

if (!$about) {
    $pdo->prepare("INSERT INTO about_us (title, subtitle, content, mission, vision, stats_json, product_type) VALUES ('About Us', '', '', '', '', '{}', ?)")->execute([$currentSection]);
    $about = getContentByType('about_us', $currentSection);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if ($token !== ($_SESSION['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid security token.');
        redirect(ADMIN_URL . '/about.php');
    }
    $title = sanitize($_POST['title']);
    $subtitle = sanitize($_POST['subtitle']);
    $content = sanitizeHtml($_POST['content']);
    $mission = sanitize($_POST['mission']);
    $vision = sanitize($_POST['vision']);
    $stats = json_encode([
        'products' => sanitize($_POST['stat_products'] ?? ''),
        'customers' => sanitize($_POST['stat_customers'] ?? ''),
        'downloads' => sanitize($_POST['stat_downloads'] ?? ''),
        'rating' => sanitize($_POST['stat_rating'] ?? ''),
    ]);

    $stmt = $pdo->prepare("UPDATE about_us SET title = ?, subtitle = ?, content = ?, mission = ?, vision = ?, stats_json = ? WHERE id = ?");
    $stmt->execute([$title, $subtitle, $content, $mission, $vision, $stats, $about['id']]);

    setFlash('success', 'About Us page updated successfully.');
    redirect(ADMIN_URL . '/about.php');
}

$stats = json_decode($about['stats_json'] ?? '{}', true) ?: [];
?>

<div class="admin-page-header">
    <h2>About Us <?php echo $currentSection !== 'general' ? '- ' . getProductTypeLabel($currentSection) : ''; ?></h2>
    <a href="<?php echo SITE_URL; ?>/about.php" target="_blank" class="btn btn-outline btn-sm">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
        View Page
    </a>
</div>

<div class="admin-card">
    <div class="admin-card-body">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
            <div class="admin-form-grid">
                <div class="admin-form-group">
                    <label for="title">Page Title *</label>
                    <input type="text" id="title" name="title" class="form-control" value="<?php echo sanitize($about['title']); ?>" required>
                </div>

                <div class="admin-form-group">
                    <label for="subtitle">Subtitle / Tagline</label>
                    <input type="text" id="subtitle" name="subtitle" class="form-control" value="<?php echo sanitize($about['subtitle'] ?? ''); ?>" placeholder="e.g. Your Trusted Digital Marketplace">
                </div>
            </div>

            <div class="admin-form-group">
                <label for="content">Main Content *</label>
                <textarea id="content" name="content" class="form-control" rows="10" required><?php echo htmlspecialchars($about['content'] ?? ''); ?></textarea>
                <p class="text-muted mt-1" style="font-size:0.8rem">Use HTML tags for formatting. Supported: p, strong, em, br, ul, li, h2, h3</p>
            </div>

            <div class="admin-form-grid">
                <div class="admin-form-group">
                    <label for="mission">Our Mission</label>
                    <textarea id="mission" name="mission" class="form-control" rows="4"><?php echo htmlspecialchars($about['mission'] ?? ''); ?></textarea>
                </div>

                <div class="admin-form-group">
                    <label for="vision">Our Vision</label>
                    <textarea id="vision" name="vision" class="form-control" rows="4"><?php echo htmlspecialchars($about['vision'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="admin-form-group">
                <label>Statistics (displayed as counters)</label>
                <div class="admin-form-grid" style="grid-template-columns:repeat(4,1fr)">
                    <div>
                        <input type="text" name="stat_products" class="form-control" value="<?php echo sanitize($stats['products'] ?? ''); ?>" placeholder="Products (e.g. 500+)">
                    </div>
                    <div>
                        <input type="text" name="stat_customers" class="form-control" value="<?php echo sanitize($stats['customers'] ?? ''); ?>" placeholder="Customers (e.g. 10,000+)">
                    </div>
                    <div>
                        <input type="text" name="stat_downloads" class="form-control" value="<?php echo sanitize($stats['downloads'] ?? ''); ?>" placeholder="Downloads (e.g. 50,000+)">
                    </div>
                    <div>
                        <input type="text" name="stat_rating" class="form-control" value="<?php echo sanitize($stats['rating'] ?? ''); ?>" placeholder="Rating (e.g. 4.9/5)">
                    </div>
                </div>
            </div>

            <div class="d-flex gap-1 mt-3">
                <button type="submit" class="btn btn-primary btn-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Update About Page
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
