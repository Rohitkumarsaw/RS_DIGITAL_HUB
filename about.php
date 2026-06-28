<?php
$pageTitle = 'About Us';
require_once __DIR__ . '/includes/header.php';

$stmt = $pdo->query("SELECT * FROM about_us LIMIT 1");
$about = $stmt->fetch();

if (!$about) {
    setFlash('error', 'About page not configured.');
    redirect(SITE_URL . '/index.php');
}

$stats = json_decode($about['stats_json'] ?? '{}', true) ?: [];
?>

<!-- Hero Section -->
<section class="section" style="background:linear-gradient(135deg,var(--bg-secondary) 0%,var(--bg-tertiary) 100%);padding:4rem 0">
    <div class="container" style="text-align:center;max-width:800px">
        <h1 style="font-size:clamp(1.75rem,5vw,2.5rem);margin-bottom:1rem"><?php echo sanitize($about['title']); ?></h1>
        <?php if ($about['subtitle']): ?>
        <p style="font-size:1.2rem;color:var(--text-secondary);margin-bottom:2rem"><?php echo sanitize($about['subtitle']); ?></p>
        <?php endif; ?>
        <div style="width:80px;height:4px;background:linear-gradient(90deg,var(--primary),var(--accent));border-radius:2px;margin:0 auto"></div>
    </div>
</section>

<!-- Stats Section -->
<?php if (!empty($stats) && array_filter($stats)): ?>
<section class="section" style="padding:3rem 0">
    <div class="container">
        <div class="d-flex flex-wrap justify-center gap-2">
            <?php if (!empty($stats['products'])): ?>
            <div class="stat-card">
                <div class="stat-number"><?php echo sanitize($stats['products']); ?></div>
                <div class="stat-label">Products</div>
            </div>
            <?php endif; ?>
            <?php if (!empty($stats['customers'])): ?>
            <div class="stat-card">
                <div class="stat-number"><?php echo sanitize($stats['customers']); ?></div>
                <div class="stat-label">Customers</div>
            </div>
            <?php endif; ?>
            <?php if (!empty($stats['downloads'])): ?>
            <div class="stat-card">
                <div class="stat-number"><?php echo sanitize($stats['downloads']); ?></div>
                <div class="stat-label">Downloads</div>
            </div>
            <?php endif; ?>
            <?php if (!empty($stats['rating'])): ?>
            <div class="stat-card">
                <div class="stat-number"><?php echo sanitize($stats['rating']); ?></div>
                <div class="stat-label">Rating</div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Main Content -->
<section class="section" style="padding:2rem 0 4rem">
    <div class="container" style="max-width:900px">
        <div class="card">
            <div class="card-body" style="padding:2.5rem">
                <div class="about-content">
                    <?php echo $about['content']; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Mission & Vision -->
<?php if ($about['mission'] || $about['vision']): ?>
<section class="section" style="padding:0 0 4rem">
    <div class="container">
        <div class="d-flex flex-wrap gap-2">
            <?php if ($about['mission']): ?>
            <div class="card" style="flex:1;min-width:280px">
                <div class="card-body" style="padding:2rem">
                    <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1rem">
                        <div style="width:48px;height:48px;border-radius:12px;background:var(--primary-glow-sm);display:flex;align-items:center;justify-content:center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                        </div>
                        <h3 style="margin:0">Our Mission</h3>
                    </div>
                    <p style="color:var(--text-secondary);line-height:1.7"><?php echo sanitize($about['mission']); ?></p>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($about['vision']): ?>
            <div class="card" style="flex:1;min-width:280px">
                <div class="card-body" style="padding:2rem">
                    <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1rem">
                        <div style="width:48px;height:48px;border-radius:12px;background:var(--accent-glow-sm);display:flex;align-items:center;justify-content:center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </div>
                        <h3 style="margin:0">Our Vision</h3>
                    </div>
                    <p style="color:var(--text-secondary);line-height:1.7"><?php echo sanitize($about['vision']); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- CTA Section -->
<section class="section" style="padding:0 0 4rem">
    <div class="container">
        <div class="card" style="background:linear-gradient(135deg,var(--primary-glow-sm),var(--accent-glow-sm));text-align:center;padding:3rem 2rem">
            <h2 style="margin-bottom:1rem">Ready to Explore?</h2>
            <p style="color:var(--text-secondary);margin-bottom:1.5rem">Browse our collection of premium digital products and find exactly what you need.</p>
            <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary btn-lg">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                Browse Products
            </a>
        </div>
    </div>
</section>

<style>
.stat-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1.5rem 2rem;
    text-align: center;
    min-width: 140px;
    flex: 1;
    max-width: 200px;
}
.stat-number {
    font-size: 2rem;
    font-weight: 800;
    background: linear-gradient(135deg, var(--primary), var(--accent));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.stat-label {
    font-size: 0.85rem;
    color: var(--text-secondary);
    margin-top: 0.25rem;
}
.about-content p {
    margin-bottom: 1rem;
    color: var(--text-secondary);
    line-height: 1.8;
    font-size: 1.05rem;
}
.about-content strong {
    color: var(--text-primary);
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
