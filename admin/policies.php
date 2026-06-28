<?php
$pageTitle = 'Policies';
require_once __DIR__ . '/includes/header.php';

$action = $_GET['action'] ?? 'list';
$currentSection = getCurrentSection();

// Handle delete
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM policies WHERE id = ?")->execute([(int)$_GET['delete']]);
    setFlash('success', 'Policy deleted.');
    redirect(ADMIN_URL . '/policies.php');
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if ($token !== ($_SESSION['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid security token.');
        redirect(ADMIN_URL . '/policies.php');
    }
    $title = sanitize($_POST['title']);
    $slug = generateSlug($_POST['slug'] ?? $title);
    $content = sanitizeHtml($_POST['content']);
    $status = sanitize($_POST['status']);
    $policyId = (int)($_POST['policy_id'] ?? 0);
    $productType = sanitize($_POST['product_type'] ?? $currentSection);

    if (empty($title) || empty($content)) {
        setFlash('error', 'Title and content are required.');
    } else {
        if ($policyId) {
            $stmt = $pdo->prepare("UPDATE policies SET title = ?, slug = ?, content = ?, status = ?, product_type = ? WHERE id = ?");
            $stmt->execute([$title, $slug, $content, $status, $productType, $policyId]);
            setFlash('success', 'Policy updated.');
        } else {
            $stmt = $pdo->prepare("INSERT INTO policies (title, slug, content, status, product_type) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $slug, $content, $status, $productType]);
            setFlash('success', 'Policy created.');
        }
        redirect(ADMIN_URL . '/policies.php');
    }
}

$editPolicy = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM policies WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $editPolicy = $stmt->fetch();
}

if ($currentSection !== 'general') {
    $stmt = $pdo->prepare("SELECT * FROM policies WHERE product_type = ? ORDER BY title ASC");
    $stmt->execute([$currentSection]);
} else {
    $stmt = $pdo->query("SELECT * FROM policies ORDER BY title ASC");
}
$policies = $stmt->fetchAll();
?>

<?php if ($action === 'list'): ?>
<div class="admin-page-header">
    <h2>Policies <?php echo $currentSection !== 'general' ? '- ' . getProductTypeLabel($currentSection) : ''; ?></h2>
    <a href="?action=add" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Policy
    </a>
</div>

<div class="admin-card">
    <div class="admin-table-wrapper">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Slug</th>
                    <th>Status</th>
                    <th>Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($policies as $policy): ?>
                <tr>
                    <td><?php echo sanitize($policy['title']); ?></td>
                    <td><code style="background:var(--bg-tertiary);padding:0.2rem 0.5rem;border-radius:4px;font-size:0.8rem"><?php echo sanitize($policy['slug']); ?></code></td>
                    <td><span class="badge badge-<?php echo $policy['status'] === 'active' ? 'success' : 'warning'; ?>"><?php echo ucfirst($policy['status']); ?></span></td>
                    <td><?php echo formatDate($policy['updated_at']); ?></td>
                    <td>
                        <div class="admin-actions">
                            <a href="?action=edit&id=<?php echo $policy['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                            <a href="?delete=<?php echo $policy['id']; ?>" class="btn btn-sm btn-danger" onclick="showConfirm('Are you sure you want to delete this?',this.href);return false">Delete</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php else: ?>
<div class="admin-page-header">
    <h2><?php echo $action === 'add' ? 'Add' : 'Edit'; ?> Policy <?php echo $currentSection !== 'general' ? '- ' . getProductTypeLabel($currentSection) : ''; ?></h2>
    <a href="policies.php" class="btn btn-secondary">Back to List</a>
</div>

<div class="admin-card">
    <div class="admin-card-body">
        <form method="POST">
            <?php if ($editPolicy): ?>
            <input type="hidden" name="policy_id" value="<?php echo $editPolicy['id']; ?>">
            <?php endif; ?>
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
            <input type="hidden" name="product_type" value="<?php echo $currentSection !== 'general' ? $currentSection : ($editPolicy['product_type'] ?? 'general'); ?>">

            <div class="admin-form-grid">
                <div class="admin-form-group">
                    <label for="title">Policy Title *</label>
                    <input type="text" id="title" name="title" class="form-control" value="<?php echo $editPolicy ? sanitize($editPolicy['title']) : (isset($_POST['title']) ? sanitize($_POST['title']) : ''); ?>" required>
                </div>

                <div class="admin-form-group">
                    <label for="slug">URL Slug</label>
                    <input type="text" id="slug" name="slug" class="form-control" value="<?php echo $editPolicy ? sanitize($editPolicy['slug']) : (isset($_POST['slug']) ? sanitize($_POST['slug']) : ''); ?>" placeholder="auto-generated from title">
                </div>

                <div class="admin-form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="active" <?php echo ($editPolicy && $editPolicy['status'] === 'active') || (!isset($editPolicy)) ? 'selected' : ''; ?>>Active</option>
                        <option value="draft" <?php echo ($editPolicy && $editPolicy['status'] === 'draft') ? 'selected' : ''; ?>>Draft</option>
                    </select>
                </div>
            </div>

            <div class="admin-form-group">
                <label for="content">Policy Content *</label>
                <textarea id="content" name="content" class="form-control" rows="20" required><?php echo $editPolicy ? $editPolicy['content'] : (isset($_POST['content']) ? $_POST['content'] : ''); ?></textarea>
                <p class="text-muted mt-1" style="font-size:0.8rem">Use HTML tags for formatting. Supported: h2, h3, p, ul, li, strong, em, a</p>
            </div>

            <div class="d-flex gap-1 flex-wrap">
                <button type="submit" class="btn btn-primary btn-lg"><?php echo $action === 'add' ? 'Create' : 'Update'; ?> Policy</button>
                <a href="policies.php" class="btn btn-secondary btn-lg">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
