<?php
$pageTitle = 'My Documents';
require_once __DIR__ . '/../admin/includes/header.php';
require_once __DIR__ . '/../classes/Subscription.php';

$sub = new Subscription($pdo);
$userId = $_SESSION['user_id'];
$activeSub = $sub->getActiveByDeveloper($userId);

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$developer = $stmt->fetch();

ensureDocumentTables();
?>
<div class="admin-page-header">
    <h2>My Documents</h2>
</div>

<?php if (!$activeSub): ?>
<div class="admin-empty">
    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
    <h3>No Active Subscription</h3>
    <p>Please subscribe to a plan to access your documents.</p>
    <a href="<?php echo SITE_URL; ?>/developer/subscription.php" class="btn btn-primary">View Subscription</a>
</div>
<?php else: ?>
<div class="admin-stats-grid" style="grid-template-columns:1fr 1fr;gap:1.5rem;margin-top:1rem">
    <!-- Agreement Card -->
    <div class="admin-card" style="transition:transform 0.2s;border-color:var(--primary)">
        <div class="admin-card-header">
            <h3 style="display:flex;align-items:center;gap:0.5rem">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--primary)"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                Agreement
            </h3>
            <span class="badge badge-primary">Developer Agreement</span>
        </div>
        <div class="admin-card-body">
            <p class="text-muted" style="font-size:0.9rem;margin-bottom:1rem">
                View and download your Developer Agreement. This document outlines the terms and conditions of your partnership with <?php echo getSetting('site_name', 'RS Digital Hub'); ?>.
            </p>
            <div style="display:flex;gap:0.75rem">
                <a href="<?php echo SITE_URL; ?>/pdf/document-agreement.php" target="_blank" class="btn btn-primary" style="flex:1;text-align:center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    View
                </a>
                <a href="<?php echo SITE_URL; ?>/pdf/document-agreement.php?download=1" class="btn btn-outline" style="flex:1;text-align:center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Download
                </a>
            </div>
        </div>
    </div>

    <!-- Joining Letter Card -->
    <div class="admin-card" style="transition:transform 0.2s;border-color:var(--accent)">
        <div class="admin-card-header">
            <h3 style="display:flex;align-items:center;gap:0.5rem">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--accent)"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                Joining Letter
            </h3>
            <span class="badge badge-info">Welcome Letter</span>
        </div>
        <div class="admin-card-body">
            <p class="text-muted" style="font-size:0.9rem;margin-bottom:1rem">
                View and download your Joining Letter. This document confirms your onboarding as a Developer on the <?php echo getSetting('site_name', 'RS Digital Hub'); ?> platform.
            </p>
            <div style="display:flex;gap:0.75rem">
                <a href="<?php echo SITE_URL; ?>/pdf/document-joining-letter.php" target="_blank" class="btn btn-primary" style="flex:1;text-align:center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    View
                </a>
                <a href="<?php echo SITE_URL; ?>/pdf/document-joining-letter.php?download=1" class="btn btn-outline" style="flex:1;text-align:center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Download
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../admin/includes/footer.php'; ?>
