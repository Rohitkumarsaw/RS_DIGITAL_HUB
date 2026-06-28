<?php
$pageTitle = 'Notice Board';
require_once __DIR__ . '/includes/header.php';

// Get active notices with date filtering
$stmt = $pdo->query("
    SELECT * FROM notices
    WHERE status = 'active'
    AND (display_from IS NULL OR display_from <= NOW())
    AND (display_to IS NULL OR display_to >= NOW())
    ORDER BY pinned DESC, created_at DESC
");
$notices = $stmt->fetchAll();

$priorityIcons = [
    'info' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
    'success' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
    'warning' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
    'urgent' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
];

$priorityColors = [
    'info' => ['bg' => 'rgba(59,130,246,0.1)', 'border' => 'rgba(59,130,246,0.3)', 'text' => '#3b82f6', 'icon' => 'var(--primary)'],
    'success' => ['bg' => 'rgba(16,185,129,0.1)', 'border' => 'rgba(16,185,129,0.3)', 'text' => '#10b981', 'icon' => 'var(--accent)'],
    'warning' => ['bg' => 'rgba(245,158,11,0.1)', 'border' => 'rgba(245,158,11,0.3)', 'text' => '#f59e0b', 'icon' => '#f59e0b'],
    'urgent' => ['bg' => 'rgba(239,68,68,0.1)', 'border' => 'rgba(239,68,68,0.3)', 'text' => '#ef4444', 'icon' => '#ef4444'],
];
?>

<section class="section" style="padding:2rem 0 4rem">
    <div class="container">
        <nav class="mb-3" style="color:var(--text-secondary)">
            <a href="<?php echo SITE_URL; ?>/index.php">Home</a> /
            <span>Notice Board</span>
        </nav>

        <div class="d-flex align-center gap-1 mb-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            <h1 style="margin:0">Notice Board</h1>
        </div>
        <p class="text-muted mb-3">Stay updated with the latest announcements, updates, and important information.</p>

        <?php if (empty($notices)): ?>
        <div class="card" style="text-align:center;padding:3rem">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <h3 class="mt-2" style="color:var(--text-secondary)">No Active Notices</h3>
            <p class="text-muted">There are no notices to display at this time. Check back later!</p>
        </div>
        <?php else: ?>
        <div class="notices-list">
            <?php foreach ($notices as $notice):
                $colors = $priorityColors[$notice['priority']] ?? $priorityColors['info'];
            ?>
            <div class="notice-card" style="border-left:4px solid <?php echo $colors['icon']; ?>">
                <div class="notice-header">
                    <div class="d-flex align-center gap-1">
                        <span class="notice-icon" style="color:<?php echo $colors['icon']; ?>">
                            <?php echo $priorityIcons[$notice['priority']] ?? $priorityIcons['info']; ?>
                        </span>
                        <h3 class="notice-title"><?php echo sanitize($notice['title']); ?></h3>
                        <?php if ($notice['pinned']): ?>
                        <span class="notice-pin" title="Pinned">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 17v5"/><path d="M9 10.5a3 3 0 0 1 3-3 3 3 0 0 1 3 3v0a3 3 0 0 1-3 3 3 3 0 0 1-3-3v0z"/><path d="M12 2v5"/><path d="M5 10h14"/></svg>
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex align-center gap-1">
                        <span class="notice-badge" style="background:<?php echo $colors['bg']; ?>;color:<?php echo $colors['text']; ?>;border:1px solid <?php echo $colors['border']; ?>">
                            <?php echo ucfirst($notice['priority']); ?>
                        </span>
                        <span class="notice-date"><?php echo timeAgo($notice['created_at']); ?></span>
                    </div>
                </div>
                <div class="notice-content">
                    <?php echo $notice['content']; ?>
                </div>
                <?php if ($notice['display_from'] || $notice['display_to']): ?>
                <div class="notice-footer">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <?php if ($notice['display_from'] && $notice['display_to']): ?>
                        <?php echo formatDate($notice['display_from'], 'M d'); ?> - <?php echo formatDate($notice['display_to'], 'M d, Y'); ?>
                    <?php elseif ($notice['display_from']): ?>
                        From <?php echo formatDate($notice['display_from'], 'M d, Y'); ?>
                    <?php elseif ($notice['display_to']): ?>
                        Until <?php echo formatDate($notice['display_to'], 'M d, Y'); ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<style>
.notices-list { display:flex; flex-direction:column; gap:1rem; }
.notice-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    transition: var(--transition);
}
.notice-card:hover {
    border-color: var(--primary);
    box-shadow: 0 4px 20px var(--primary-glow-sm);
}
.notice-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    flex-wrap: wrap;
    gap: 0.75rem;
}
.notice-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
}
.notice-icon { flex-shrink: 0; }
.notice-pin { color: var(--warning); }
.notice-badge {
    padding: 0.2rem 0.6rem;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.notice-date {
    font-size: 0.8rem;
    color: var(--text-muted);
}
.notice-content {
    color: var(--text-secondary);
    line-height: 1.7;
    margin-bottom: 0.75rem;
}
.notice-content strong { color: var(--text-primary); }
.notice-footer {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8rem;
    color: var(--text-muted);
    padding-top: 0.75rem;
    border-top: 1px solid var(--border-color);
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
