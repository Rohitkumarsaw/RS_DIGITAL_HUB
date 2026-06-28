<?php
$pageTitle = 'Users';
require_once __DIR__ . '/includes/header.php';

// Only admins can manage users
$isStrictAdmin = in_array($_SESSION['user_role'] ?? '', ['admin', 'super_admin']);
if (!$isStrictAdmin) {
    setFlash('error', 'Access denied.');
    redirect(ADMIN_URL . '/index.php');
}

// Handle block/unblock
if (isset($_GET['toggle_status'])) {
    $userId = (int)$_GET['toggle_status'];
    $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if ($user) {
        $newStatus = $user['status'] === 'active' ? 'blocked' : 'active';
        $pdo->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$newStatus, $userId]);
        setFlash('success', 'User status updated.');
    }
    redirect(ADMIN_URL . '/users.php');
}

// Handle delete
if (isset($_GET['delete'])) {
    try {
        $pdo->beginTransaction();
        $userId = (int)$_GET['delete'];

        $stmt = $pdo->prepare("SELECT role, name, id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $targetUser = $stmt->fetch();

        if (!$targetUser) {
            setFlash('error', 'User not found.');
            $pdo->rollBack();
            redirect(ADMIN_URL . '/users.php');
        }

        if ($targetUser['role'] === 'super_admin') {
            setFlash('error', 'Cannot delete super admin.');
            $pdo->rollBack();
            redirect(ADMIN_URL . '/users.php');
        }

        // Delete all related data before the user
        $subIds = $pdo->prepare("SELECT id FROM subscriptions WHERE developer_id = ?");
        $subIds->execute([$userId]);
        $subRows = $subIds->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($subRows)) {
            $placeholders = implode(',', array_fill(0, count($subRows), '?'));
            $pdo->prepare("DELETE FROM subscription_invoices WHERE subscription_id IN ($placeholders)")->execute($subRows);
        }
        $pdo->prepare("DELETE FROM subscriptions WHERE developer_id = ?")->execute([$userId]);
        $pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$userId]);
        $pdo->prepare("DELETE FROM notices WHERE created_by = ?")->execute([$userId]);
        $pdo->prepare("UPDATE products SET developer_id = NULL WHERE developer_id = ?")->execute([$userId]);
        $pdo->prepare("DELETE FROM coupons WHERE developer_id = ?")->execute([$userId]);

        // Delete user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);

        if ($stmt->rowCount() > 0) {
            $pdo->commit();
            setFlash('success', 'User "' . sanitize($targetUser['name']) . '" deleted successfully.');
        } else {
            $pdo->rollBack();
            setFlash('error', 'Could not delete user. Unknown error.');
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlash('error', 'Delete failed: ' . $e->getMessage());
    }
    redirect(ADMIN_URL . '/users.php');
}

$roleFilter = sanitize($_GET['role'] ?? '');
$sql = "
    SELECT u.*, COUNT(o.id) as order_count, COALESCE(SUM(o.final_amount), 0) as total_spent
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id AND o.payment_status = 'completed'
";
$params = [];
$where = [];

if ($roleFilter) {
    $where[] = "u.role = ?";
    $params[] = $roleFilter;
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " GROUP BY u.id ORDER BY u.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<div class="d-flex justify-between align-center mb-3 flex-wrap gap-1">
    <h2 class="mb-0">Users</h2>
    <div class="d-flex gap-1 flex-wrap align-center">
        <span class="text-muted"><?php echo count($users); ?> total</span>
        <a href="<?php echo SITE_URL; ?>/admin/pdf-preview.php?type=users" class="btn btn-sm btn-outline">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export PDF
        </a>
    </div>
</div>

<div class="filters mb-3">
    <a href="users.php" class="btn <?php echo !$roleFilter ? 'btn-primary' : 'btn-secondary'; ?>">All</a>
    <a href="?role=user" class="btn <?php echo $roleFilter === 'user' ? 'btn-primary' : 'btn-secondary'; ?>">Users</a>
    <a href="?role=developer" class="btn <?php echo $roleFilter === 'developer' ? 'btn-primary' : 'btn-secondary'; ?>">Developers</a>
    <a href="?role=admin" class="btn <?php echo $roleFilter === 'admin' ? 'btn-primary' : 'btn-secondary'; ?>">Admins</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Store</th>
                    <th>Orders</th>
                    <th>Total Spent</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 0; foreach ($users as $user): $i++; ?>
                <tr>
                    <td><?php echo $i; ?></td>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo sanitize($user['name']); ?></td>
                    <td><?php echo sanitize($user['email']); ?></td>
                    <td>
                        <span class="badge badge-<?php echo $user['role'] === 'super_admin' ? 'danger' : ($user['role'] === 'admin' ? 'primary' : ($user['role'] === 'developer' ? 'info' : 'secondary')); ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                        </span>
                    </td>
                    <td><?php echo $user['store_name'] ? sanitize($user['store_name']) : '-'; ?></td>
                    <td><?php echo $user['order_count']; ?></td>
                    <td><?php echo formatPrice($user['total_spent']); ?></td>
                    <td><span class="badge badge-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>"><?php echo ucfirst($user['status']); ?></span></td>
                    <td><?php echo formatDate($user['created_at']); ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="user-profile.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline">View Profile</a>
                            <a href="?toggle_status=<?php echo $user['id']; ?>" class="btn btn-sm btn-<?php echo $user['status'] === 'active' ? 'warning' : 'success'; ?>">
                                <?php echo $user['status'] === 'active' ? 'Block' : 'Unblock'; ?>
                            </a>
                            <?php if ($user['role'] !== 'super_admin'): ?>
                            <a href="?delete=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="showConfirm('Delete this user permanently?',this.href);return false">Delete</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
