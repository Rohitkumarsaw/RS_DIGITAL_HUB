<?php
$pageTitle = 'Guidelines';
require_once __DIR__ . '/includes/header.php';
requireStrictAdmin();

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../classes/Guideline.php';
$guideline = new Guideline($pdo);

$action = $_GET['action'] ?? 'list';
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

if ($action === 'add' || ($action === 'edit' && $editId)) {
    $editData = null;
    if ($action === 'edit' && $editId) {
        $editData = $guideline->getById($editId);
        if (!$editData) {
            setFlash('error', 'Guideline not found.');
            redirect(ADMIN_URL . '/guidelines.php');
        }
    }
    ?>
    <div class="admin-page-header">
        <h2><?php echo $action === 'add' ? 'Add' : 'Edit'; ?> Guideline</h2>
        <a href="guidelines.php" class="btn btn-secondary">Back to List</a>
    </div>

    <div class="admin-card">
        <div class="admin-card-body">
            <form method="POST" action="<?php echo SITE_URL; ?>/processes/process_guideline.php">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                <?php if ($editData): ?>
                <input type="hidden" name="id" value="<?php echo $editData['id']; ?>">
                <input type="hidden" name="action" value="update">
                <?php else: ?>
                <input type="hidden" name="action" value="create">
                <?php endif; ?>

                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title" class="form-control" value="<?php echo $editData ? sanitize($editData['title']) : (isset($_POST['title']) ? sanitize($_POST['title']) : ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="content">Content *</label>
                    <textarea id="content" name="content" class="form-control" rows="8" required><?php echo $editData ? $editData['content'] : (isset($_POST['content']) ? $_POST['content'] : ''); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary"><?php echo $action === 'add' ? 'Add Guideline' : 'Update Guideline'; ?></button>
            </form>
        </div>
    </div>
    <?php
} else {
    $guidelines = $guideline->getAll();
    ?>
    <div class="admin-page-header">
        <h2>Guidelines</h2>
        <a href="?action=add" class="btn btn-primary">Add Guideline</a>
    </div>

    <?php if (empty($guidelines)): ?>
    <div class="admin-empty">
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        <h3>No Guidelines Yet</h3>
        <p>Add guidelines that developers can view.</p>
        <a href="?action=add" class="btn btn-primary">Add First Guideline</a>
    </div>
    <?php else: ?>
    <div class="admin-table-wrapper">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Created</th>
                    <th>Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($guidelines as $g): ?>
                <tr>
                    <td><?php echo $g['id']; ?></td>
                    <td><strong><?php echo sanitize($g['title']); ?></strong></td>
                    <td><?php echo date('d M Y', strtotime($g['created_at'])); ?></td>
                    <td><?php echo date('d M Y', strtotime($g['updated_at'])); ?></td>
                    <td>
                        <div class="admin-actions">
                            <a href="?action=edit&edit=<?php echo $g['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                            <a href="<?php echo SITE_URL; ?>/processes/process_guideline.php?action=delete&id=<?php echo $g['id']; ?>&token=<?php echo $_SESSION['csrf_token'] ?? ''; ?>" class="btn btn-sm btn-danger" onclick="showConfirm('Delete this guideline?',this.href);return false">Delete</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    <?php
}
require_once __DIR__ . '/includes/footer.php';
?>
