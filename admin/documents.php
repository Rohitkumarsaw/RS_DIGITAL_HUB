<?php
$pageTitle = 'Developer Documents';
require_once __DIR__ . '/includes/header.php';
requireStrictAdmin();

ensureDocumentTables();

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("SELECT id FROM developer_documents WHERE id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetch()) {
            $pdo->prepare("DELETE FROM developer_documents WHERE id = ?")->execute([$id]);
            setFlash('success', 'Document record deleted.');
        }
    } catch (PDOException $e) {
        setFlash('error', 'Delete failed.');
    }
    redirect(ADMIN_URL . '/documents.php');
}

// Get all documents with developer info
$documents = [];
try {
    $docs = $pdo->query("
        SELECT dd.*, u.name as developer_name, u.email as developer_email, u.registration_no
        FROM developer_documents dd
        JOIN users u ON dd.developer_id = u.id
        ORDER BY dd.generated_at DESC
    ")->fetchAll();
} catch (PDOException $e) {
    $docs = [];
}
?>
<div class="admin-page-header">
    <h2>Developer Documents</h2>
    <div style="display:flex;gap:0.5rem">
        <a href="document-templates.php" class="btn btn-outline">Templates</a>
        <a href="documents-meta.php" class="btn btn-outline">Signature Settings</a>
    </div>
</div>

<div class="admin-table-wrapper">
    <table class="admin-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Developer</th>
                <th>Document Type</th>
                <th>Status</th>
                <th>Generated</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($docs)): ?>
            <tr><td colspan="6" class="text-center">No documents generated yet.</td></tr>
            <?php else: ?>
            <?php foreach ($docs as $i => $d): ?>
            <tr>
                <td><?php echo $i + 1; ?></td>
                <td>
                    <strong><?php echo sanitize($d['developer_name']); ?></strong>
                    <br><small class="text-muted"><?php echo sanitize($d['developer_email']); ?></small>
                </td>
                <td><span class="badge badge-<?php echo $d['document_type'] === 'agreement' ? 'primary' : 'info'; ?>"><?php echo ucfirst(str_replace('_', ' ', $d['document_type'])); ?></span></td>
                <td><span class="badge badge-<?php echo $d['status'] === 'generated' ? 'success' : 'warning'; ?>"><?php echo ucfirst($d['status']); ?></span></td>
                <td><?php echo date('d M Y h:i A', strtotime($d['generated_at'])); ?></td>
                <td>
                    <a href="<?php echo SITE_URL; ?>/pdf/document-<?php echo $d['document_type']; ?>.php?dev_id=<?php echo $d['developer_id']; ?>" class="btn btn-sm btn-primary" target="_blank">View</a>
                    <a href="?delete=<?php echo $d['id']; ?>" class="btn btn-sm btn-danger" onclick="showConfirm('Delete this document record?',this.href);return false">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php
require_once __DIR__ . '/includes/footer.php';
?>
