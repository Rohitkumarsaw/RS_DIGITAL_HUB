<?php
$pageTitle = 'Policy';
require_once __DIR__ . '/includes/header.php';

$slug = sanitize($_GET['slug'] ?? '');

if (empty($slug)) {
    redirect(SITE_URL . '/index.php');
}

$stmt = $pdo->prepare("SELECT * FROM policies WHERE slug = ? AND status = 'active'");
$stmt->execute([$slug]);
$policy = $stmt->fetch();

if (!$policy) {
    setFlash('error', 'Policy not found.');
    redirect(SITE_URL . '/index.php');
}

$pageTitle = $policy['title'];

// Get all active policies for sidebar
$allPolicies = $pdo->query("SELECT slug, title FROM policies WHERE status = 'active' ORDER BY title")->fetchAll();
?>

<div class="container">
    <div class="section">
        <nav class="mb-3" style="color:var(--text-secondary)">
            <a href="<?php echo SITE_URL; ?>/index.php">Home</a> /
            <span><?php echo sanitize($policy['title']); ?></span>
        </nav>

        <div class="d-flex flex-wrap gap-2">
            <!-- Policy Sidebar -->
            <div class="card policy-sidebar" style="width:280px;flex-shrink:0;height:fit-content;position:sticky;top:calc(var(--navbar-height) + 1rem);max-width:100%">
                <div class="card-header">
                    <h4 class="mb-0">Policies</h4>
                </div>
                <div class="card-body" style="padding:0.75rem">
                    <?php foreach ($allPolicies as $p): ?>
                    <a href="?slug=<?php echo urlencode($p['slug']); ?>" class="d-flex align-center gap-1" style="padding:0.65rem 0.75rem;color:var(--text-secondary);border-radius:var(--border-radius-sm);transition:var(--transition);font-size:0.85rem;<?php echo $p['slug'] === $slug ? 'background:var(--primary-glow-sm);color:var(--primary);font-weight:600' : ''; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        <?php echo sanitize($p['title']); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Policy Content -->
            <div class="card policy-content-card" style="flex:1;min-width:0">
                <div class="card-body" style="padding:2rem">
                    <h1><?php echo sanitize($policy['title']); ?></h1>
                    <p class="text-muted mb-3">Last updated: <?php echo formatDate($policy['updated_at']); ?></p>
                    <hr style="border:none;border-top:1px solid var(--border-color);margin:1.5rem 0">
                    <div class="policy-content">
                        <?php echo $policy['content']; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.policy-content h2 { font-size:1.35rem; margin:2rem 0 1rem; color:var(--text-primary); }
.policy-content h3 { font-size:1.1rem; margin:1.5rem 0 0.75rem; color:var(--text-primary); }
.policy-content p { margin-bottom:1rem; color:var(--text-secondary); line-height:1.7; }
.policy-content ul { margin:0.75rem 0 1rem 1.5rem; list-style:disc; }
.policy-content li { margin-bottom:0.5rem; color:var(--text-secondary); }
@media (max-width:768px) {
    .policy-sidebar { width:100% !important; position:static !important; }
    .policy-content-card .card-body { padding:1.25rem !important; }
    .policy-content h2 { font-size:1.2rem; }
    .policy-content { overflow-wrap:break-word; word-break:break-word; }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
