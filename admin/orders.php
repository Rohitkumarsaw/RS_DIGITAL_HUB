<?php
$pageTitle = 'Orders';
require_once __DIR__ . '/includes/header.php';

$currentSection = getCurrentSection();
$isDeveloper = $_SESSION['user_role'] === 'developer';
$userId = $_SESSION['user_id'];

$sectionFilter = $currentSection !== 'general' ? " AND p.product_type = ?" : "";
$sectionParam = $currentSection !== 'general' ? [$currentSection] : [];

// CSRF check for all POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if ($csrf !== ($_SESSION['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid security token.');
        redirect(ADMIN_URL . '/orders.php');
    }
}

// Handle status update (only admin can update any order, developer restricted)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = (int)$_POST['order_id'];
    $status = sanitize($_POST['status']);
    $paymentStatus = sanitize($_POST['payment_status']);

    if (!$isDeveloper) {
        $pdo->prepare("UPDATE orders SET status = ?, payment_status = ? WHERE id = ?")->execute([$status, $paymentStatus, $orderId]);
        setFlash('success', 'Order updated successfully.');
    }
    redirect(ADMIN_URL . '/orders.php');
}

// Handle refund (admin only)
if (isset($_GET['refund']) && !$isDeveloper) {
    $orderId = (int)$_GET['refund'];
    $pdo->prepare("UPDATE orders SET payment_status = 'refunded', status = 'cancelled' WHERE id = ?")->execute([$orderId]);
    setFlash('success', 'Order refunded successfully.');
    redirect(ADMIN_URL . '/orders.php');
}

$statusFilter = sanitize($_GET['status'] ?? '');

// Developer: only see orders containing their products (section-aware)
if ($isDeveloper) {
    $sql = "SELECT DISTINCT o.*, u.name as user_name, u.email as user_email
            FROM orders o
            JOIN users u ON o.user_id = u.id
            JOIN order_items oi ON o.id = oi.order_id
            JOIN products p ON oi.product_id = p.id
            WHERE p.developer_id = ?" . $sectionFilter;
    $params = array_merge([$userId], $sectionParam);

    if ($statusFilter) {
        $sql .= " AND o.payment_status = ?";
        $params[] = $statusFilter;
    }
} else {
    $sql = "SELECT o.*, u.name as user_name, u.email as user_email FROM orders o JOIN users u ON o.user_id = u.id";
    $params = [];

    if ($currentSection !== 'general') {
        $sql .= " JOIN order_items oi ON o.id = oi.order_id JOIN products p ON oi.product_id = p.id WHERE p.product_type = ?";
        $params[] = $currentSection;
    }

    if ($statusFilter) {
        $sql .= ($currentSection === 'general' ? " WHERE" : " AND") . " o.payment_status = ?";
        $params[] = $statusFilter;
    }
}

$sql .= " ORDER BY o.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Stats (developer sees their own, admin sees global — both section-aware)
if ($isDeveloper) {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(oi.price), 0) FROM orders o JOIN order_items oi ON o.id = oi.order_id JOIN products p ON oi.product_id = p.id WHERE p.developer_id = ? AND o.payment_status = 'completed'" . $sectionFilter);
    $stmt->execute(array_merge([$userId], $sectionParam));
    $myRevenue = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(oi.price), 0) FROM orders o JOIN order_items oi ON o.id = oi.order_id JOIN products p ON oi.product_id = p.id WHERE p.developer_id = ? AND o.payment_status = 'completed' AND DATE(o.created_at) = CURDATE()" . $sectionFilter);
    $stmt->execute(array_merge([$userId], $sectionParam));
    $myTodayRevenue = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(oi.price), 0) FROM orders o JOIN order_items oi ON o.id = oi.order_id JOIN products p ON oi.product_id = p.id WHERE p.developer_id = ? AND o.payment_status = 'completed' AND MONTH(o.created_at) = MONTH(CURDATE()) AND YEAR(o.created_at) = YEAR(CURDATE())" . $sectionFilter);
    $stmt->execute(array_merge([$userId], $sectionParam));
    $myMonthRevenue = $stmt->fetchColumn();
} else {
    if ($currentSection === 'general') {
        $totalRevenue = $pdo->query("SELECT COALESCE(SUM(final_amount), 0) FROM orders WHERE payment_status = 'completed'")->fetchColumn();
        $todayRevenue = $pdo->query("SELECT COALESCE(SUM(final_amount), 0) FROM orders WHERE payment_status = 'completed' AND DATE(created_at) = CURDATE()")->fetchColumn();
        $monthRevenue = $pdo->query("SELECT COALESCE(SUM(final_amount), 0) FROM orders WHERE payment_status = 'completed' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetchColumn();
    } else {
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(o.final_amount), 0) FROM orders o JOIN order_items oi ON o.id = oi.order_id JOIN products p ON oi.product_id = p.id WHERE p.product_type = ? AND o.payment_status = 'completed'");
        $stmt->execute([$currentSection]);
        $totalRevenue = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COALESCE(SUM(o.final_amount), 0) FROM orders o JOIN order_items oi ON o.id = oi.order_id JOIN products p ON oi.product_id = p.id WHERE p.product_type = ? AND o.payment_status = 'completed' AND DATE(o.created_at) = CURDATE()");
        $stmt->execute([$currentSection]);
        $todayRevenue = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COALESCE(SUM(o.final_amount), 0) FROM orders o JOIN order_items oi ON o.id = oi.order_id JOIN products p ON oi.product_id = p.id WHERE p.product_type = ? AND o.payment_status = 'completed' AND MONTH(o.created_at) = MONTH(CURDATE()) AND YEAR(o.created_at) = YEAR(CURDATE())");
        $stmt->execute([$currentSection]);
        $monthRevenue = $stmt->fetchColumn();
    }
}
?>

<div class="admin-page-header">
    <h2 class="mb-0"><?php echo $isDeveloper ? 'My Orders' : 'Orders'; ?></h2>
    <div style="display:flex;gap:0.5rem;align-items:center">
        <a href="<?php echo SITE_URL; ?>/admin/pdf-preview.php?type=orders" class="btn btn-sm btn-outline">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Orders Preview
        </a>
        <a href="<?php echo SITE_URL; ?>/admin/pdf-preview.php?type=payments" class="btn btn-sm btn-outline">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Payments Preview
        </a>
        <span class="badge badge-<?php echo $isDeveloper ? 'info' : 'primary'; ?>">Section: <?php echo $currentSection === 'general' ? 'All' : getProductTypeLabel($currentSection); ?></span>
    </div>
</div>

<div class="stats-grid mb-3">
    <?php if ($isDeveloper): ?>
    <div class="stat-card">
        <div class="stat-card-value"><?php echo formatPrice($myRevenue); ?></div>
        <div class="stat-card-label">My Revenue</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-value"><?php echo formatPrice($myTodayRevenue); ?></div>
        <div class="stat-card-label">Today's Revenue</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-value"><?php echo formatPrice($myMonthRevenue); ?></div>
        <div class="stat-card-label">This Month</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-value"><?php echo count($orders); ?></div>
        <div class="stat-card-label">My Orders</div>
    </div>
    <?php else: ?>
    <div class="stat-card">
        <div class="stat-card-value"><?php echo formatPrice($totalRevenue); ?></div>
        <div class="stat-card-label">Total Revenue</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-value"><?php echo formatPrice($todayRevenue); ?></div>
        <div class="stat-card-label">Today's Revenue</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-value"><?php echo formatPrice($monthRevenue); ?></div>
        <div class="stat-card-label">This Month</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-value"><?php echo count($orders); ?></div>
        <div class="stat-card-label">Total Orders</div>
    </div>
    <?php endif; ?>
</div>

<div class="filters">
    <a href="orders.php" class="btn <?php echo !$statusFilter ? 'btn-primary' : 'btn-secondary'; ?>">All</a>
    <a href="?status=completed" class="btn <?php echo $statusFilter === 'completed' ? 'btn-primary' : 'btn-secondary'; ?>">Completed</a>
    <a href="?status=pending" class="btn <?php echo $statusFilter === 'pending' ? 'btn-primary' : 'btn-secondary'; ?>">Pending</a>
    <a href="?status=failed" class="btn <?php echo $statusFilter === 'failed' ? 'btn-primary' : 'btn-secondary'; ?>">Failed</a>
    <a href="?status=refunded" class="btn <?php echo $statusFilter === 'refunded' ? 'btn-primary' : 'btn-secondary'; ?>">Refunded</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 0; foreach ($orders as $order): $i++; ?>
                <tr>
                    <td><?php echo $i; ?></td>
                    <td><?php echo sanitize($order['order_number']); ?></td>
                    <td>
                        <?php echo sanitize($order['user_name']); ?>
                        <br><small class="text-muted"><?php echo sanitize($order['user_email']); ?></small>
                    </td>
                    <td><?php echo formatPrice($order['final_amount']); ?></td>
                    <td><span class="badge badge-<?php echo $order['payment_method'] === 'stripe' ? 'primary' : ($order['payment_method'] === 'paypal' ? 'info' : 'warning'); ?>"><?php echo ucfirst(sanitize($order['payment_method'])); ?></span></td>
                    <td><span class="badge badge-<?php echo $order['payment_status'] === 'completed' ? 'success' : ($order['payment_status'] === 'pending' ? 'warning' : 'danger'); ?>"><?php echo ucfirst($order['payment_status']); ?></span></td>
                    <td><?php echo formatDate($order['created_at']); ?></td>
                    <td>
                        <?php if ($isDeveloper): ?>
                        <span class="text-muted" style="font-size:0.8rem">View Only</span>
                        <?php else: ?>
                        <div class="d-flex gap-1 flex-wrap">
                            <form method="POST" class="d-flex gap-1">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <select name="payment_status" class="form-control" style="padding:0.4rem;font-size:0.8rem">
                                    <option value="pending" <?php echo $order['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="completed" <?php echo $order['payment_status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="failed" <?php echo $order['payment_status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                    <option value="refunded" <?php echo $order['payment_status'] === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                </select>
                                <button type="submit" name="update_status" class="btn btn-sm btn-outline">Update</button>
                            </form>
                            <?php if ($order['payment_status'] === 'completed'): ?>
                            <a href="?refund=<?php echo $order['id']; ?>" class="btn btn-sm btn-danger" onclick="showConfirm('Refund this order?',this.href);return false">Refund</a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
