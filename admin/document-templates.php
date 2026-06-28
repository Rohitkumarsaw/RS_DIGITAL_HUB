<?php
$pageTitle = 'Document Templates';
require_once __DIR__ . '/includes/header.php';
requireStrictAdmin();

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

ensureDocumentTables();

$action = $_GET['action'] ?? 'list';
$editType = $_GET['edit'] ?? '';

// CSRF check for all POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if ($csrf !== ($_SESSION['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid security token.');
        redirect(ADMIN_URL . '/document-templates.php');
    }
}

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_template'])) {
    $type = sanitize($_POST['type']);
    $title = sanitize($_POST['title']);
    $body = $_POST['body_content'];
    $status = isset($_POST['status']) ? 1 : 0;

    try {
        $stmt = $pdo->prepare("UPDATE document_templates SET title = ?, body_content = ?, status = ? WHERE type = ?");
        $stmt->execute([$title, $body, $status, $type]);
        setFlash('success', 'Template updated successfully.');
        redirect(ADMIN_URL . '/document-templates.php?edit=' . $type);
    } catch (Exception $e) {
        setFlash('error', 'Update failed: ' . $e->getMessage());
    }
}

$templates = getAllDocumentTemplates();

if ($action === 'edit' && $editType) {
    $template = getDocumentTemplate($editType);
    if (!$template) {
        // Try by type even if status is 0
        try {
            $stmt = $pdo->prepare("SELECT * FROM document_templates WHERE type = ?");
            $stmt->execute([$editType]);
            $template = $stmt->fetch();
        } catch (PDOException $e) {}
    }
    if (!$template) {
        setFlash('error', 'Template not found.');
        redirect(ADMIN_URL . '/document-templates.php');
    }
    ?>
    <div class="admin-page-header">
        <h2>Edit Template: <?php echo ucfirst(str_replace('_', ' ', $template['type'])); ?></h2>
        <a href="document-templates.php" class="btn btn-secondary">Back</a>
    </div>

    <div class="admin-card">
        <div class="admin-card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                <input type="hidden" name="type" value="<?php echo $template['type']; ?>">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" class="form-control" value="<?php echo sanitize($template['title']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="body_content">Body Content</label>
                    <p class="text-muted" style="font-size:0.8rem;margin-bottom:0.5rem">
                        Available placeholders: {{developer_name}}, {{developer_email}}, {{plan_name}}, {{join_date}}, {{company_name}}, {{admin_name}}
                    </p>
                    <textarea id="body_content" name="body_content" class="form-control" rows="20" style="font-family:monospace;font-size:0.85rem"><?php echo sanitizeHtml($template['body_content'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="status" value="1" <?php echo $template['status'] ? 'checked' : ''; ?>>
                        Active
                    </label>
                </div>
                <button type="submit" name="save_template" class="btn btn-primary">Save Template</button>
            </form>
        </div>
    </div>

    <div class="admin-card" style="margin-top:1.5rem">
        <div class="card-header">
            <h3>Preview</h3>
        </div>
        <div class="admin-card-body" style="border:1px solid var(--border-color);border-radius:var(--border-radius);padding:1.5rem;background:var(--bg-primary)">
            <?php echo replaceDocumentPlaceholders($template['body_content'] ?? '', ['name' => 'John Developer', 'email' => 'john@example.com'], 'Business', date('d M Y')); ?>
        </div>
    </div>
    <?php
} else {
    ?>
    <div class="admin-page-header">
        <h2>Document Templates</h2>
    </div>

    <div class="admin-table-wrapper">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Type</th>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($templates)): ?>
                <tr><td colspan="6" class="text-center">No templates found.</td></tr>
                <?php else: ?>
                <?php foreach ($templates as $i => $t): ?>
                <tr>
                    <td><?php echo $i + 1; ?></td>
                    <td><span class="badge badge-<?php echo $t['type'] === 'agreement' ? 'primary' : 'info'; ?>"><?php echo ucfirst(str_replace('_', ' ', $t['type'])); ?></span></td>
                    <td><?php echo sanitize($t['title']); ?></td>
                    <td><span class="badge badge-<?php echo $t['status'] ? 'success' : 'danger'; ?>"><?php echo $t['status'] ? 'Active' : 'Inactive'; ?></span></td>
                    <td><?php echo $t['updated_at'] ? date('d M Y', strtotime($t['updated_at'])) : '-'; ?></td>
                    <td>
                        <a href="?action=edit&edit=<?php echo $t['type']; ?>" class="btn btn-sm btn-primary">Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
require_once __DIR__ . '/includes/footer.php';
?>
