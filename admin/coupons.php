<?php
$pageTitle = 'Coupons';
require_once __DIR__ . '/includes/header.php';

$isDeveloper = $_SESSION['user_role'] === 'developer';
$userId = $_SESSION['user_id'];

// Handle delete
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM coupons WHERE id = ?" . ($isDeveloper ? " AND developer_id = ?" : ""));
    if ($isDeveloper) {
        $stmt->execute([(int)$_GET['delete'], $userId]);
    } else {
        $stmt->execute([(int)$_GET['delete']]);
    }
    setFlash('success', 'Coupon deleted.');
    redirect(ADMIN_URL . '/coupons.php');
}

// CSRF check for all POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if ($csrf !== ($_SESSION['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid security token.');
        redirect(ADMIN_URL . '/coupons.php');
    }
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(sanitize($_POST['code']));
    $type = sanitize($_POST['type']);
    $value = (float)$_POST['value'];
    $minOrder = (float)($_POST['min_order'] ?? 0);
    $maxDiscount = !empty($_POST['max_discount']) ? (float)$_POST['max_discount'] : null;
    $usageLimit = !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null;
    $expiresAt = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
    $status = sanitize($_POST['status']);
    $couponId = (int)($_POST['coupon_id'] ?? 0);

    // Developer can only edit their own coupons
    if ($couponId && $isDeveloper) {
        $check = $pdo->prepare("SELECT id FROM coupons WHERE id = ? AND developer_id = ?");
        $check->execute([$couponId, $userId]);
        if (!$check->fetch()) {
            setFlash('error', 'Access denied.');
            redirect(ADMIN_URL . '/coupons.php');
        }
    }

    if (empty($code) || $value <= 0) {
        setFlash('error', 'Code and value are required.');
    } else {
        if ($couponId) {
            $stmt = $pdo->prepare("
                UPDATE coupons SET code = ?, type = ?, value = ?, min_order_amount = ?, max_discount = ?, usage_limit = ?, expires_at = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([$code, $type, $value, $minOrder, $maxDiscount, $usageLimit, $expiresAt, $status, $couponId]);
            setFlash('success', 'Coupon updated.');
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO coupons (code, type, value, min_order_amount, max_discount, usage_limit, expires_at, status, developer_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$code, $type, $value, $minOrder, $maxDiscount, $usageLimit, $expiresAt, $status, $isDeveloper ? $userId : null]);
            setFlash('success', 'Coupon created.');
        }
        redirect(ADMIN_URL . '/coupons.php');
    }
}

$action = $_GET['action'] ?? 'list';
$editCoupon = null;

if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE id = ?" . ($isDeveloper ? " AND developer_id = ?" : ""));
    if ($isDeveloper) {
        $stmt->execute([(int)$_GET['id'], $userId]);
    } else {
        $stmt->execute([(int)$_GET['id']]);
    }
    $editCoupon = $stmt->fetch();
}

$stmt = $isDeveloper
    ? $pdo->prepare("SELECT * FROM coupons WHERE developer_id = ? ORDER BY created_at DESC")
    : $pdo->prepare("SELECT * FROM coupons ORDER BY created_at DESC");
if ($isDeveloper) { $stmt->execute([$userId]); } else { $stmt->execute(); }
$coupons = $stmt->fetchAll();
?>

<?php if ($action === 'list'): ?>
<div class="d-flex justify-between align-center mb-3 flex-wrap gap-1">
    <h2 class="mb-0">Coupons</h2>
    <a href="?action=add" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Coupon
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Type</th>
                    <th>Value</th>
                    <th>Min Order</th>
                    <th>Used</th>
                    <th>Expires</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($coupons as $coupon): ?>
                <tr>
                    <td><strong><?php echo sanitize($coupon['code']); ?></strong></td>
                    <td><?php echo ucfirst($coupon['type']); ?></td>
                    <td><?php echo $coupon['type'] === 'percentage' ? $coupon['value'] . '%' : formatPrice($coupon['value']); ?></td>
                    <td><?php echo $coupon['min_order_amount'] > 0 ? formatPrice($coupon['min_order_amount']) : '-'; ?></td>
                    <td><?php echo $coupon['used_count']; ?><?php echo $coupon['usage_limit'] ? '/' . $coupon['usage_limit'] : ''; ?></td>
                    <td><?php echo $coupon['expires_at'] ? formatDate($coupon['expires_at']) : 'Never'; ?></td>
                    <td><span class="badge badge-<?php echo $coupon['status'] === 'active' ? 'success' : 'danger'; ?>"><?php echo ucfirst($coupon['status']); ?></span></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="?action=edit&id=<?php echo $coupon['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                            <a href="?delete=<?php echo $coupon['id']; ?>" class="btn btn-sm btn-danger" onclick="showConfirm('Are you sure you want to delete this?',this.href);return false">Delete</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php else: ?>
<div class="d-flex justify-between align-center mb-3 flex-wrap gap-1">
    <h2 class="mb-0"><?php echo $action === 'add' ? 'Add' : 'Edit'; ?> Coupon</h2>
    <a href="coupons.php" class="btn btn-secondary">Back to List</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
            <?php if ($editCoupon): ?>
            <input type="hidden" name="coupon_id" value="<?php echo $editCoupon['id']; ?>">
            <?php endif; ?>

            <div class="d-flex flex-wrap gap-2">
                <div class="form-group" style="flex:1;min-width:200px">
                    <label for="code">Coupon Code *</label>
                    <input type="text" id="code" name="code" class="form-control" value="<?php echo $editCoupon ? sanitize($editCoupon['code']) : (isset($_POST['code']) ? sanitize($_POST['code']) : ''); ?>" placeholder="SUMMER2026" required style="text-transform:uppercase">
                </div>

                <div class="form-group" style="flex:1;min-width:150px">
                    <label for="type">Discount Type *</label>
                    <select id="type" name="type" class="form-control">
                        <option value="percentage" <?php echo ($editCoupon && $editCoupon['type'] === 'percentage') || (!isset($editCoupon) && isset($_POST['type']) && $_POST['type'] === 'percentage') ? 'selected' : ''; ?>>Percentage (%)</option>
                        <option value="flat" <?php echo ($editCoupon && $editCoupon['type'] === 'flat') || (isset($_POST['type']) && $_POST['type'] === 'flat') ? 'selected' : ''; ?>>Flat Amount</option>
                    </select>
                </div>

                <div class="form-group" style="flex:1;min-width:150px">
                    <label for="value">Value *</label>
                    <input type="number" id="value" name="value" class="form-control" step="0.01" min="0" value="<?php echo $editCoupon ? $editCoupon['value'] : (isset($_POST['value']) ? $_POST['value'] : ''); ?>" required>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <div class="form-group" style="flex:1;min-width:150px">
                    <label for="min_order">Minimum Order Amount</label>
                    <input type="number" id="min_order" name="min_order" class="form-control" step="0.01" min="0" value="<?php echo $editCoupon ? $editCoupon['min_order_amount'] : (isset($_POST['min_order']) ? $_POST['min_order'] : 0); ?>">
                </div>

                <div class="form-group" style="flex:1;min-width:150px">
                    <label for="max_discount">Max Discount (for %)</label>
                    <input type="number" id="max_discount" name="max_discount" class="form-control" step="0.01" min="0" value="<?php echo $editCoupon ? ($editCoupon['max_discount'] ?? '') : (isset($_POST['max_discount']) ? $_POST['max_discount'] : ''); ?>">
                </div>

                <div class="form-group" style="flex:1;min-width:150px">
                    <label for="usage_limit">Usage Limit</label>
                    <input type="number" id="usage_limit" name="usage_limit" class="form-control" min="1" value="<?php echo $editCoupon ? ($editCoupon['usage_limit'] ?? '') : (isset($_POST['usage_limit']) ? $_POST['usage_limit'] : ''); ?>">
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <div class="form-group" style="flex:1;min-width:200px">
                    <label for="expires_at">Expiry Date</label>
                    <input type="datetime-local" id="expires_at" name="expires_at" class="form-control" value="<?php echo $editCoupon ? date('Y-m-d\TH:i', strtotime($editCoupon['expires_at'])) : (isset($_POST['expires_at']) ? $_POST['expires_at'] : ''); ?>">
                </div>

                <div class="form-group" style="flex:1;min-width:150px">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="active" <?php echo ($editCoupon && $editCoupon['status'] === 'active') || (!isset($editCoupon)) ? 'selected' : ''; ?>>Active</option>
                        <option value="disabled" <?php echo ($editCoupon && $editCoupon['status'] === 'disabled') ? 'selected' : ''; ?>>Disabled</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg"><?php echo $action === 'add' ? 'Create' : 'Update'; ?> Coupon</button>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
