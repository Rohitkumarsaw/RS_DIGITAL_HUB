<?php
$pageTitle = 'Guidelines';
require_once __DIR__ . '/../admin/includes/header.php';

require_once __DIR__ . '/../classes/Guideline.php';
$guideline = new Guideline($pdo);
$guidelines = $guideline->getAll();
?>
<div class="admin-page-header">
    <h2>Guidelines for Developers</h2>
</div>

<?php if (empty($guidelines)): ?>
<div class="admin-empty">
    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
    <h3>No Guidelines Available</h3>
    <p>Guidelines from the admin will appear here.</p>
</div>
<?php else: ?>
<div class="row" style="display:flex;flex-direction:column;gap:1rem">
    <?php foreach ($guidelines as $g): ?>
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 style="margin:0;font-size:1.05rem"><?php echo sanitize($g['title']); ?></h3>
            <small style="color:var(--text-muted)"><?php echo date('d M Y', strtotime($g['created_at'])); ?></small>
        </div>
        <div class="admin-card-body">
            <div style="white-space:pre-wrap;line-height:1.7;color:var(--text-secondary)"><?php echo nl2br(sanitize($g['content'])); ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../admin/includes/footer.php'; ?>
