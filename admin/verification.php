<?php
$pageTitle = 'Product Verification';
require_once __DIR__ . '/includes/header.php';

// Only admins can verify products
$isStrictAdmin = in_array($_SESSION['user_role'] ?? '', ['admin', 'super_admin']);
if (!$isStrictAdmin) {
    setFlash('error', 'Access denied.');
    redirect(ADMIN_URL . '/index.php');
}

$currentSection = getCurrentSection();

// Handle approve/reject
if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    if (canManageProduct($id)) {
        $pdo->prepare("UPDATE products SET status = 'active' WHERE id = ?")->execute([$id]);
        setFlash('success', 'Product approved and published.');
    }
    redirect(ADMIN_URL . '/verification.php');
}
if (isset($_GET['reject'])) {
    $id = (int)$_GET['reject'];
    if (canManageProduct($id)) {
        $reason = sanitize($_GET['reason'] ?? '');
        $pdo->prepare("UPDATE products SET status = 'inactive', rejection_reason = ? WHERE id = ?")->execute([$reason, $id]);
        setFlash('success', 'Product rejected.');
    }
    redirect(ADMIN_URL . '/verification.php');
}

// Fetch pending products
if ($currentSection === 'general') {
    $stmt = $pdo->query("
        SELECT p.*, u.name as developer_name, u.email as developer_email, u.store_name as developer_store
        FROM products p
        LEFT JOIN users u ON p.developer_id = u.id
        WHERE p.status = 'pending'
        ORDER BY p.created_at DESC
    ");
    $pendingProducts = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("
        SELECT p.*, u.name as developer_name, u.email as developer_email, u.store_name as developer_store
        FROM products p
        LEFT JOIN users u ON p.developer_id = u.id
        WHERE p.status = 'pending' AND p.product_type = ?
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$currentSection]);
    $pendingProducts = $stmt->fetchAll();
}
?>

<div class="admin-page-header">
    <h2>Product Verification</h2>
    <span class="badge badge-warning">Pending: <?php echo count($pendingProducts); ?></span>
    <span class="badge badge-primary">Section: <?php echo $currentSection === 'general' ? 'All' : getProductTypeLabel($currentSection); ?></span>
</div>

<?php if (empty($pendingProducts)): ?>
<div class="empty-state" style="text-align:center;padding:3rem 1rem">
    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    <h3>No Pending Products</h3>
    <p class="text-muted">All products have been reviewed. Check back later.</p>
</div>
<?php else: ?>
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Type</th>
                    <th>Developer</th>
                    <th>Price</th>
                    <th>Submitted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pendingProducts as $product): ?>
                <tr>
                    <td>
                        <strong><?php echo sanitize($product['title']); ?></strong>
                    </td>
                    <td><span class="badge badge-info" style="font-size:0.65rem"><?php echo getProductTypeLabel($product['product_type'] ?? 'general'); ?></span></td>
                    <td>
                        <?php echo sanitize($product['developer_store'] ?: $product['developer_name'] ?: 'N/A'); ?>
                        <br><small class="text-muted"><?php echo sanitize($product['developer_email']); ?></small>
                    </td>
                    <td>
                        <?php if ($product['is_free']): ?>
                        <span class="badge badge-success">Free</span>
                        <?php else: ?>
                        <?php echo formatPrice($product['sale_price'] ?? $product['price']); ?>
                        <?php endif; ?>
                    </td>
                    <td><?php echo formatDate($product['created_at']); ?></td>
                    <td>
                        <div class="d-flex gap-1 flex-wrap">
                            <a href="?approve=<?php echo $product['id']; ?>" class="btn btn-sm btn-success" onclick="showConfirm('Approve this product?',this.href);return false">Approve</a>
                            <a href="?reject=<?php echo $product['id']; ?>" class="btn btn-sm btn-danger" onclick="return rejectProduct(<?php echo $product['id']; ?>)">Reject</a>
                            <a href="?action=edit&id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<style>
.modal-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5);
    align-items: center;
    justify-content: center;
    z-index: 9999;
}
.modal-box {
    background: var(--bg-card);
    border-radius: 8px;
    padding: 24px;
    width: 420px;
    max-width: 90vw;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
}
.modal-box h3 {
    font-size: 1.1rem;
    margin-bottom: 4px;
    color: var(--text-primary);
}
.modal-box .modal-desc {
    font-size: 0.85rem;
    color: var(--text-secondary);
    margin-bottom: 14px;
}
.modal-box textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    font-size: 0.9rem;
    resize: vertical;
    min-height: 90px;
    box-sizing: border-box;
    background: var(--bg-input);
    color: var(--text-primary);
}
.modal-box textarea:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 2px var(--primary-glow-sm);
}
.modal-actions {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
    margin-top: 16px;
}
.modal-actions button {
    padding: 8px 18px;
    border: none;
    border-radius: 6px;
    font-size: 0.85rem;
    cursor: pointer;
    font-weight: 500;
}
.modal-actions .btn-cancel {
    background: var(--bg-tertiary);
    color: var(--text-primary);
}
.modal-actions .btn-cancel:hover {
    background: var(--border-color);
}
.modal-actions .btn-submit {
    background: #dc3545;
    color: #fff;
}
.modal-actions .btn-submit:hover {
    background: #c82333;
}
</style>

<div class="modal-overlay" id="rejectModal" onclick="if(event.target===this)closeRejectModal()">
    <div class="modal-box">
        <h3>Reject Product</h3>
        <p class="modal-desc">Please provide a reason for rejecting this product. The developer will see this reason.</p>
        <input type="hidden" id="rejectProductId">
        <textarea id="rejectReason" placeholder="Enter rejection reason..."></textarea>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeRejectModal()">Cancel</button>
            <button class="btn-submit" onclick="submitReject()">Submit &amp; Reject</button>
        </div>
    </div>
</div>

<script>
function rejectProduct(id) {
    document.getElementById('rejectModal').style.display = 'flex';
    document.getElementById('rejectProductId').value = id;
    document.getElementById('rejectReason').value = '';
    document.getElementById('rejectReason').focus();
    return false;
}
function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}
function submitReject() {
    var id = document.getElementById('rejectProductId').value;
    var reason = document.getElementById('rejectReason').value.trim();
    if (!reason) {
        showToast('Please enter a reason for rejection.','warning');
        return;
    }
    window.location.href = '?reject=' + id + '&reason=' + encodeURIComponent(reason);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
