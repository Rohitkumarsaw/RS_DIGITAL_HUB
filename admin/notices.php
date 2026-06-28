<?php
$pageTitle = 'Notice Board';
require_once __DIR__ . '/includes/header.php';

$action = $_GET['action'] ?? 'list';
$adminUser = getCurrentUser();
$currentSection = getCurrentSection();

// Handle delete
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM notices WHERE id = ?")->execute([(int)$_GET['delete']]);
    setFlash('success', 'Notice deleted.');
    redirect(ADMIN_URL . '/notices.php');
}

// Handle toggle status
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $pdo->prepare("SELECT status FROM notices WHERE id = ?");
    $stmt->execute([$id]);
    $current = $stmt->fetchColumn();
    $newStatus = $current === 'active' ? 'draft' : 'active';
    $pdo->prepare("UPDATE notices SET status = ? WHERE id = ?")->execute([$newStatus, $id]);
    setFlash('success', 'Notice status updated.');
    redirect(ADMIN_URL . '/notices.php');
}

// Handle toggle pin
if (isset($_GET['pin'])) {
    $id = (int)$_GET['pin'];
    $stmt = $pdo->prepare("SELECT pinned FROM notices WHERE id = ?");
    $stmt->execute([$id]);
    $current = (int)$stmt->fetchColumn();
    $pdo->prepare("UPDATE notices SET pinned = ? WHERE id = ?")->execute([$current ? 0 : 1, $id]);
    setFlash('success', 'Notice pin updated.');
    redirect(ADMIN_URL . '/notices.php');
}

// Handle add/edit POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if ($token !== ($_SESSION['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid security token.');
        redirect(ADMIN_URL . '/notices.php');
    }
    $title = sanitize($_POST['title']);
    $content = sanitizeHtml($_POST['content']);
    $priority = sanitize($_POST['priority']);
    $status = sanitize($_POST['status']);
    $display_from = !empty($_POST['display_from']) ? $_POST['display_from'] : null;
    $display_to = !empty($_POST['display_to']) ? $_POST['display_to'] : null;
    $pinned = isset($_POST['pinned']) ? 1 : 0;
    $noticeId = (int)($_POST['notice_id'] ?? 0);
    $productType = sanitize($_POST['product_type'] ?? $currentSection);

    if (empty($title) || empty($content)) {
        setFlash('error', 'Title and content are required.');
    } else {
        if ($noticeId) {
            $stmt = $pdo->prepare("UPDATE notices SET title = ?, content = ?, priority = ?, status = ?, display_from = ?, display_to = ?, pinned = ?, product_type = ? WHERE id = ?");
            $stmt->execute([$title, $content, $priority, $status, $display_from, $display_to, $pinned, $productType, $noticeId]);
            setFlash('success', 'Notice updated.');
        } else {
            $stmt = $pdo->prepare("INSERT INTO notices (title, content, priority, status, display_from, display_to, pinned, created_by, product_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $content, $priority, $status, $display_from, $display_to, $pinned, $adminUser['id'], $productType]);
            setFlash('success', 'Notice created.');
        }
        redirect(ADMIN_URL . '/notices.php');
    }
}

// Get notice for edit
$editNotice = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM notices WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $editNotice = $stmt->fetch();
}

// Get notices filtered by section
if ($currentSection !== 'general') {
    $stmt = $pdo->prepare("SELECT n.*, u.name as creator_name FROM notices n LEFT JOIN users u ON n.created_by = u.id WHERE n.product_type = ? ORDER BY n.pinned DESC, n.created_at DESC");
    $stmt->execute([$currentSection]);
} else {
    $stmt = $pdo->query("SELECT n.*, u.name as creator_name FROM notices n LEFT JOIN users u ON n.created_by = u.id ORDER BY n.pinned DESC, n.created_at DESC");
}
$notices = $stmt->fetchAll();
?>

<?php if ($action === 'list'): ?>
<div class="admin-page-header">
    <h2>Notice Board <?php echo $currentSection !== 'general' ? '- ' . getProductTypeLabel($currentSection) : ''; ?></h2>
    <a href="?action=add" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Notice
    </a>
</div>

<div class="admin-card">
    <div class="admin-table-wrapper">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:30px">Pin</th>
                    <th>Title</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Display Period</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($notices)): ?>
                <tr><td colspan="7" class="text-center text-muted" style="padding:2rem">No notices yet. Click "Add Notice" to create one.</td></tr>
                <?php else: ?>
                <?php foreach ($notices as $notice): ?>
                <tr>
                    <td>
                        <a href="?pin=<?php echo $notice['id']; ?>" class="btn btn-sm <?php echo $notice['pinned'] ? 'btn-warning' : 'btn-outline'; ?>" title="<?php echo $notice['pinned'] ? 'Unpin' : 'Pin'; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="<?php echo $notice['pinned'] ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 17v5"/><path d="M9 10.5a3 3 0 0 1 3-3 3 3 0 0 1 3 3v0a3 3 0 0 1-3 3 3 3 0 0 1-3-3v0z"/><path d="M12 2v5"/><path d="M5 10h14"/></svg>
                        </a>
                    </td>
                    <td>
                        <strong><?php echo sanitize($notice['title']); ?></strong>
                        <?php if (mb_strlen($notice['content']) > 60): ?>
                        <br><span class="text-muted" style="font-size:0.8rem"><?php echo sanitize(mb_substr(strip_tags($notice['content']), 0, 60)); ?>...</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge badge-<?php echo $notice['priority'] === 'urgent' ? 'danger' : ($notice['priority'] === 'warning' ? 'warning' : ($notice['priority'] === 'success' ? 'success' : 'info')); ?>"><?php echo ucfirst($notice['priority']); ?></span></td>
                    <td><span class="badge badge-<?php echo $notice['status'] === 'active' ? 'success' : ($notice['status'] === 'draft' ? 'warning' : 'secondary'); ?>"><?php echo ucfirst($notice['status']); ?></span></td>
                    <td style="font-size:0.8rem">
                        <?php if ($notice['display_from']): ?>
                            <?php echo formatDate($notice['display_from'], 'M d'); ?>
                        <?php else: ?>
                            <span class="text-muted">Always</span>
                        <?php endif; ?>
                        <?php if ($notice['display_to']): ?>
                            - <?php echo formatDate($notice['display_to'], 'M d'); ?>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:0.8rem">
                        <?php echo formatDate($notice['created_at'], 'M d'); ?>
                        <?php if ($notice['creator_name']): ?>
                        <br><span class="text-muted">by <?php echo sanitize($notice['creator_name']); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="admin-actions">
                            <a href="?action=edit&id=<?php echo $notice['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                            <a href="?toggle=<?php echo $notice['id']; ?>" class="btn btn-sm <?php echo $notice['status'] === 'active' ? 'btn-warning' : 'btn-success'; ?>">
                                <?php echo $notice['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                            </a>
                            <a href="?delete=<?php echo $notice['id']; ?>" class="btn btn-sm btn-danger" onclick="showConfirm('Are you sure you want to delete this?',this.href);return false">Delete</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php else: ?>
<div class="admin-page-header">
    <h2><?php echo $action === 'add' ? 'Add' : 'Edit'; ?> Notice <?php echo $currentSection !== 'general' ? '- ' . getProductTypeLabel($currentSection) : ''; ?></h2>
    <a href="notices.php" class="btn btn-secondary">Back to List</a>
</div>

<div class="admin-card">
    <div class="admin-card-body">
        <form method="POST">
            <?php if ($editNotice): ?>
            <input type="hidden" name="notice_id" value="<?php echo $editNotice['id']; ?>">
            <?php endif; ?>
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
            <input type="hidden" name="product_type" value="<?php echo $currentSection !== 'general' ? $currentSection : ($editNotice['product_type'] ?? 'general'); ?>">

            <div class="admin-form-grid">
                <div class="admin-form-group">
                    <label for="title">Notice Title *</label>
                    <input type="text" id="title" name="title" class="form-control" value="<?php echo $editNotice ? sanitize($editNotice['title']) : (isset($_POST['title']) ? sanitize($_POST['title']) : ''); ?>" required>
                </div>

                <div class="admin-form-group">
                    <label for="priority">Priority</label>
                    <select id="priority" name="priority" class="form-control">
                        <option value="info" <?php echo ($editNotice && $editNotice['priority'] === 'info') || !$editNotice ? 'selected' : ''; ?>>Info</option>
                        <option value="success" <?php echo ($editNotice && $editNotice['priority'] === 'success') ? 'selected' : ''; ?>>Success</option>
                        <option value="warning" <?php echo ($editNotice && $editNotice['priority'] === 'warning') ? 'selected' : ''; ?>>Warning</option>
                        <option value="urgent" <?php echo ($editNotice && $editNotice['priority'] === 'urgent') ? 'selected' : ''; ?>>Urgent</option>
                    </select>
                </div>
            </div>

            <div class="admin-form-grid">
                <div class="admin-form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="active" <?php echo ($editNotice && $editNotice['status'] === 'active') || !$editNotice ? 'selected' : ''; ?>>Active</option>
                        <option value="draft" <?php echo ($editNotice && $editNotice['status'] === 'draft') ? 'selected' : ''; ?>>Draft</option>
                        <option value="expired" <?php echo ($editNotice && $editNotice['status'] === 'expired') ? 'selected' : ''; ?>>Expired</option>
                    </select>
                </div>

                <div class="admin-form-group">
                    <label for="display_from">Display From (optional)</label>
                    <input type="datetime-local" id="display_from" name="display_from" class="form-control" value="<?php echo $editNotice && $editNotice['display_from'] ? date('Y-m-d\TH:i', strtotime($editNotice['display_from'])) : ''; ?>">
                </div>
            </div>

            <div class="admin-form-group">
                <label for="display_to">Display Until (optional)</label>
                <input type="datetime-local" id="display_to" name="display_to" class="form-control" value="<?php echo $editNotice && $editNotice['display_to'] ? date('Y-m-d\TH:i', strtotime($editNotice['display_to'])) : ''; ?>">
            </div>

            <div class="admin-form-group">
                <label for="content">Notice Content *</label>
                <textarea id="content" name="content" class="form-control" rows="8" required><?php echo $editNotice ? $editNotice['content'] : (isset($_POST['content']) ? $_POST['content'] : ''); ?></textarea>
                <p class="text-muted mt-1" style="font-size:0.8rem">Use HTML for formatting. Supported: strong, em, br, ul, li, a</p>
            </div>

            <div class="admin-form-group">
                <label>
                    <input type="checkbox" name="pinned" value="1" <?php echo ($editNotice && $editNotice['pinned']) ? 'checked' : ''; ?>>
                    Pin this notice (show at top)
                </label>
            </div>

            <div class="d-flex gap-1 flex-wrap">
                <button type="submit" class="btn btn-primary btn-lg"><?php echo $action === 'add' ? 'Create' : 'Update'; ?> Notice</button>
                <a href="notices.php" class="btn btn-secondary btn-lg">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
