<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';

$currentSection = getCurrentSection();
$isGeneral = $currentSection === 'general';

$userRole = $_SESSION['user_role'] ?? 'user';
$isDeveloper = $userRole === 'developer';
$userId = $_SESSION['user_id'];

$sectionFilter = $currentSection !== 'general' ? " AND product_type = ?" : "";
$sectionParam = $currentSection !== 'general' ? [$currentSection] : [];

// Developer-only stats (section-aware)
if ($isDeveloper) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE developer_id = ?" . $sectionFilter);
    $stmt->execute(array_merge([$userId], $sectionParam));
    $myProducts = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE developer_id = ? AND status = 'active'" . $sectionFilter);
    $stmt->execute(array_merge([$userId], $sectionParam));
    $myActiveProducts = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders o JOIN order_items oi ON o.id = oi.order_id JOIN products p ON oi.product_id = p.id WHERE p.developer_id = ? AND o.payment_status = 'completed'" . $sectionFilter);
    $stmt->execute(array_merge([$userId], $sectionParam));
    $mySales = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(oi.price), 0) FROM orders o JOIN order_items oi ON o.id = oi.order_id JOIN products p ON oi.product_id = p.id WHERE p.developer_id = ? AND o.payment_status = 'completed'" . $sectionFilter);
    $stmt->execute(array_merge([$userId], $sectionParam));
    $myRevenue = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE user_id = ? AND status IN ('open', 'in_progress')");
    $stmt->execute([$userId]);
    $myTickets = $stmt->fetchColumn();
} else {
    // Admin stats (section-aware)
    if ($currentSection === 'general') {
        $totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
        $activeProducts = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn();
    } else {
        $totalProducts = $pdo->prepare("SELECT COUNT(*) FROM products WHERE product_type = ?");
        $totalProducts->execute([$currentSection]);
        $totalProducts = $totalProducts->fetchColumn();
        $activeProducts = $pdo->prepare("SELECT COUNT(*) FROM products WHERE product_type = ? AND status = 'active'");
        $activeProducts->execute([$currentSection]);
        $activeProducts = $activeProducts->fetchColumn();
    }

    $orderSectionSql = $currentSection !== 'general' ? " JOIN order_items oi ON o.id = oi.order_id JOIN products p ON oi.product_id = p.id WHERE p.product_type = ?" : "";
    $orderSectionParam = $currentSection !== 'general' ? [$currentSection] : [];

    if ($currentSection !== 'general') {
        $totalOrders = $pdo->prepare("SELECT COUNT(DISTINCT o.id) FROM orders o" . $orderSectionSql);
        $totalOrders->execute($orderSectionParam);
        $totalOrders = $totalOrders->fetchColumn();

        $completedOrders = $pdo->prepare("SELECT COUNT(DISTINCT o.id) FROM orders o" . $orderSectionSql . " AND o.payment_status = 'completed'");
        $completedOrders->execute($orderSectionParam);
        $completedOrders = $completedOrders->fetchColumn();

        $totalRevenue = $pdo->prepare("SELECT COALESCE(SUM(o.final_amount), 0) FROM orders o" . $orderSectionSql . " AND o.payment_status = 'completed'");
        $totalRevenue->execute($orderSectionParam);
        $totalRevenue = $totalRevenue->fetchColumn();
    } else {
        $totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
        $completedOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'completed'")->fetchColumn();
        $totalRevenue = $pdo->query("SELECT COALESCE(SUM(final_amount), 0) FROM orders WHERE payment_status = 'completed'")->fetchColumn();
    }

    $totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
    $totalDevelopers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'developer'")->fetchColumn();
    $openTickets = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status IN ('open', 'in_progress')")->fetchColumn();
}
?>

<?php if ($isDeveloper): ?>
<div class="admin-page-header">
    <h2><?php echo sanitize($adminUser['store_name'] ?: 'Developer'); ?> Dashboard</h2>
    <span class="badge badge-info">Section: <?php echo $currentSection === 'general' ? 'All' : getProductTypeLabel($currentSection); ?></span>
    <a href="<?php echo SITE_URL; ?>/section-select.php" class="btn btn-sm btn-outline" style="font-size:0.8rem">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        Switch Section
    </a>
</div>

<?php if (!$adminUser['store_name']): ?>
<div class="toast-popup toast-warning">
    <div class="toast-popup-icon"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div>
    <div class="toast-popup-msg"><strong>Complete your store profile!</strong> Set up your store name in <a href="<?php echo ADMIN_URL; ?>/settings.php">Store Settings</a> to start selling products.</div>
    <button class="toast-popup-close" onclick="this.parentElement.remove()">&times;</button>
</div>
<?php endif; ?>

<div class="admin-stats-grid">
    <div class="admin-stat-card">
        <div class="admin-stat-icon primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        </div>
        <div class="admin-stat-value"><?php echo number_format($myProducts); ?></div>
        <div class="admin-stat-label">My Products</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        </div>
        <div class="admin-stat-value"><?php echo number_format($myActiveProducts); ?></div>
        <div class="admin-stat-label">Active Products</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon success">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        </div>
        <div class="admin-stat-value"><?php echo number_format($mySales); ?></div>
        <div class="admin-stat-label">My Sales</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon warning">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <div class="admin-stat-value"><?php echo formatPrice($myRevenue); ?></div>
        <div class="admin-stat-label">My Revenue</div>
    </div>
</div>

<?php if ($isGeneral): ?>
<div class="admin-stats-grid" style="margin-bottom:1.5rem">
    <div class="admin-stat-card">
        <div class="admin-stat-icon danger">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        </div>
        <div class="admin-stat-value"><?php echo $myTickets; ?></div>
        <div class="admin-stat-label">My Support Tickets</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
        </div>
        <div class="admin-stat-value">-</div>
        <div class="admin-stat-label">Coupons</div>
    </div>
    <div class="admin-stat-card" onclick="location.href='<?php echo ADMIN_URL; ?>/settings.php'" style="cursor:pointer">
        <div class="admin-stat-icon warning">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
        </div>
        <div class="admin-stat-value">Store</div>
        <div class="admin-stat-label">Store Settings</div>
    </div>
</div>
<?php endif; ?>

<div class="admin-card mt-3">
    <div class="admin-card-header">
        <h3>Quick Actions</h3>
    </div>
    <div class="admin-card-body">
        <div class="d-flex flex-wrap gap-2">
            <a href="<?php echo ADMIN_URL; ?>/products.php?action=add" class="btn btn-primary">Add New Product</a>
            <a href="<?php echo ADMIN_URL; ?>/products.php" class="btn btn-outline">Manage Products</a>
            <a href="<?php echo ADMIN_URL; ?>/orders.php" class="btn btn-outline">View Orders</a>
            <?php if ($isGeneral): ?>
            <a href="<?php echo ADMIN_URL; ?>/coupons.php" class="btn btn-outline">Coupons</a>
            <a href="<?php echo ADMIN_URL; ?>/support.php" class="btn btn-outline">Support Tickets</a>
            <a href="<?php echo ADMIN_URL; ?>/settings.php" class="btn btn-outline">Store Settings</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php else: ?>
<div class="admin-page-header">
    <h2>Dashboard</h2>
    <span class="badge badge-primary">Section: <?php echo $currentSection === 'general' ? 'All' : getProductTypeLabel($currentSection); ?></span>
    <a href="<?php echo SITE_URL; ?>/section-select.php" class="btn btn-sm btn-outline" style="font-size:0.8rem">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        Switch Section
    </a>
</div>

<?php
// Pending products alert
$pendingSql = $currentSection === 'general' ? "SELECT COUNT(*) FROM products WHERE status = 'pending'" : "SELECT COUNT(*) FROM products WHERE product_type = ? AND status = 'pending'";
if ($currentSection === 'general') {
    $pendingCount = $pdo->query($pendingSql)->fetchColumn();
} else {
    $stmt = $pdo->prepare($pendingSql);
    $stmt->execute([$currentSection]);
    $pendingCount = $stmt->fetchColumn();
}
if ($pendingCount > 0):
?>
<div class="toast-popup toast-warning">
    <div class="toast-popup-icon"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div>
    <div class="toast-popup-msg"><strong><?php echo $pendingCount; ?> product<?php echo $pendingCount !== 1 ? 's' : ''; ?> pending approval.</strong> Review and approve them from the <a href="<?php echo ADMIN_URL; ?>/products.php">Products</a> page.</div>
    <button class="toast-popup-close" onclick="this.parentElement.remove()">&times;</button>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="admin-stats-grid">
    <div class="admin-stat-card">
        <div class="admin-stat-icon primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        </div>
        <div class="admin-stat-value"><?php echo number_format($activeProducts); ?></div>
        <div class="admin-stat-label">Active Products</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon success">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        </div>
        <div class="admin-stat-value"><?php echo number_format($completedOrders); ?></div>
        <div class="admin-stat-label">Completed Orders</div>
    </div>
    <?php if ($isGeneral): ?>
    <div class="admin-stat-card">
        <div class="admin-stat-icon warning">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div class="admin-stat-value"><?php echo number_format($totalUsers + $totalDevelopers); ?></div>
        <div class="admin-stat-label">Total Users</div>
    </div>
    <?php endif; ?>
    <div class="admin-stat-card">
        <div class="admin-stat-icon danger">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <div class="admin-stat-value"><?php echo formatPrice($totalRevenue); ?></div>
        <div class="admin-stat-label">Total Revenue</div>
    </div>
</div>

<?php if ($isGeneral): ?>
<div class="admin-stats-grid" style="margin-bottom: 1.5rem">
    <div class="admin-stat-card">
        <div class="admin-stat-value"><?php echo number_format($openTickets); ?></div>
        <div class="admin-stat-label">Open Tickets</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value"><?php echo number_format($totalProducts); ?></div>
        <div class="admin-stat-label">Total Products</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value"><?php echo number_format($totalOrders); ?></div>
        <div class="admin-stat-label">Total Orders</div>
    </div>
</div>
<?php else: ?>
<div class="admin-stats-grid" style="margin-bottom: 1.5rem">
    <div class="admin-stat-card">
        <div class="admin-stat-value"><?php echo number_format($totalProducts); ?></div>
        <div class="admin-stat-label">Total Products</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value"><?php echo number_format($totalOrders); ?></div>
        <div class="admin-stat-label">Total Orders</div>
    </div>
</div>
<?php endif; ?>

<div class="d-flex flex-wrap gap-2">
    <div class="admin-card" style="flex:2;min-width:280px">
        <div class="admin-card-header">
            <h3>Recent Orders</h3>
            <a href="orders.php" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="admin-table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($currentSection === 'general') {
                        $recentOrders = $pdo->query("SELECT o.*, u.name as user_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 10")->fetchAll();
                    } else {
                        $stmt = $pdo->prepare("SELECT DISTINCT o.*, u.name as user_name FROM orders o JOIN order_items oi ON o.id = oi.order_id JOIN products p ON oi.product_id = p.id JOIN users u ON o.user_id = u.id WHERE p.product_type = ? ORDER BY o.created_at DESC LIMIT 10");
                        $stmt->execute([$currentSection]);
                        $recentOrders = $stmt->fetchAll();
                    }
                    ?>
                    <?php foreach ($recentOrders as $order): ?>
                    <tr>
                        <td><?php echo sanitize($order['order_number']); ?></td>
                        <td><?php echo sanitize($order['user_name']); ?></td>
                        <td><?php echo formatPrice($order['final_amount']); ?></td>
                        <td><span class="badge badge-<?php echo $order['payment_status'] === 'completed' ? 'success' : 'warning'; ?>"><?php echo ucfirst($order['payment_status']); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="admin-card" style="flex:1;min-width:240px">
        <div class="admin-card-header">
            <h3>Top Products</h3>
        </div>
        <div class="admin-card-body">
            <?php
            if ($currentSection === 'general') {
                $topProducts = $pdo->query("SELECT p.title, p.sales_count, COALESCE(SUM(oi.price), 0) as revenue FROM products p LEFT JOIN order_items oi ON p.id = oi.product_id WHERE p.sales_count > 0 GROUP BY p.id ORDER BY p.sales_count DESC LIMIT 5")->fetchAll();
            } else {
                $stmt = $pdo->prepare("SELECT p.title, p.sales_count, COALESCE(SUM(oi.price), 0) as revenue FROM products p LEFT JOIN order_items oi ON p.id = oi.product_id WHERE p.product_type = ? AND p.sales_count > 0 GROUP BY p.id ORDER BY p.sales_count DESC LIMIT 5");
                $stmt->execute([$currentSection]);
                $topProducts = $stmt->fetchAll();
            }
            ?>
            <?php if (empty($topProducts)): ?>
            <p class="text-muted">No sales data yet.</p>
            <?php else: ?>
            <?php foreach ($topProducts as $i => $product): ?>
            <div class="d-flex justify-between align-center" style="padding:0.75rem 0;<?php echo $i < count($topProducts) - 1 ? 'border-bottom:1px solid var(--border-color)' : ''; ?>">
                <div>
                    <strong style="font-size:0.9rem"><?php echo sanitize($product['title']); ?></strong>
                    <p class="text-muted mb-0" style="font-size:0.75rem"><?php echo $product['sales_count']; ?> sales</p>
                </div>
                <span class="text-primary" style="font-weight:600;font-size:0.9rem"><?php echo formatPrice($product['revenue']); ?></span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
