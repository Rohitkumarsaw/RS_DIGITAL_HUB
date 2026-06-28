<?php
$type = $_GET['type'] ?? '';
$allowedTypes = ['orders', 'payments', 'subscriptions', 'products', 'users'];
$targetUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

if (!in_array($type, $allowedTypes)) {
    die('Invalid preview type.');
}

$pageTitle = ucfirst($type) . ' Preview';
require_once __DIR__ . '/includes/header.php';

$role = $_SESSION['user_role'] ?? 'user';
$userId = $_SESSION['user_id'];
$currentSection = getCurrentSection();
?>

<div class="admin-page-header">
    <h2 class="mb-0"><?php echo ucfirst($type); ?> Preview</h2>
    <a href="<?php echo SITE_URL; ?>/pdf/export-<?php echo $type; ?>.php<?php echo $targetUserId ? '?user_id=' . $targetUserId : ''; ?>" class="btn btn-primary" target="_blank">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Download PDF
    </a>
</div>

<div class="card" style="margin-top:1.5rem">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <?php if ($type === 'orders'): ?>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Date</th>
                        <?php elseif ($type === 'payments'): ?>
                        <th>Payment #</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Transaction ID</th>
                        <th>Date</th>
                        <?php elseif ($type === 'subscriptions'): ?>
                        <th>Developer</th>
                        <th>Plan</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Start Date</th>
                        <th>Expiry</th>
                        <?php elseif ($type === 'products'): ?>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Sales</th>
                        <th>Status</th>
                        <th>Date</th>
                        <?php elseif ($type === 'users'): ?>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Profile</th>
                        <th>Orders</th>
                        <th>Total Spent</th>
                        <th>Joined</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($type === 'orders'):
                        $sectionFilter = $currentSection !== 'general' ? " AND p.product_type = ?" : "";
                        $userFilter = $targetUserId ? " AND o.user_id = ?" : "";
                        if (in_array($role, ['admin', 'super_admin'])) {
                            if ($currentSection !== 'general') {
                                $sql = "SELECT DISTINCT o.*, u.name as user_name, u.email as user_email FROM orders o JOIN users u ON o.user_id = u.id LEFT JOIN order_items oi ON o.id = oi.order_id LEFT JOIN products p ON oi.product_id = p.id WHERE 1=1" . $userFilter . $sectionFilter . " ORDER BY o.created_at DESC";
                                $params = [];
                                if ($targetUserId) $params[] = $targetUserId;
                                if ($currentSection !== 'general') $params[] = $currentSection;
                                $stmt = $pdo->prepare($sql);
                                $stmt->execute($params);
                            } else {
                                if ($targetUserId) {
                                    $stmt = $pdo->prepare("SELECT o.*, u.name as user_name, u.email as user_email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.user_id = ? ORDER BY o.created_at DESC");
                                    $stmt->execute([$targetUserId]);
                                } else {
                                    $stmt = $pdo->query("SELECT o.*, u.name as user_name, u.email as user_email FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC");
                                }
                            }
                        } elseif ($role === 'developer') {
                            $stmt = $pdo->prepare("SELECT DISTINCT o.*, u.name as user_name, u.email as user_email FROM orders o JOIN users u ON o.user_id = u.id JOIN order_items oi ON o.id = oi.order_id JOIN products p ON oi.product_id = p.id AND p.developer_id = ? ORDER BY o.created_at DESC");
                            $stmt->execute([$userId]);
                        } else {
                            $stmt = $pdo->prepare("SELECT DISTINCT o.*, u.name as user_name, u.email as user_email FROM orders o JOIN users u ON o.user_id = u.id LEFT JOIN order_items oi ON o.id = oi.order_id LEFT JOIN products p ON oi.product_id = p.id WHERE o.user_id = ? ORDER BY o.created_at DESC");
                            $stmt->execute([$userId]);
                        }
                        $items = $stmt->fetchAll();
                        $totalAmount = 0;
                        $i = 0;
                        foreach ($items as $r):
                            $i++;
                            $totalAmount += $r['final_amount']; ?>
                        <tr>
                            <td><?php echo $i; ?></td>
                            <td><?php echo $r['order_number']; ?></td>
                            <td><?php echo $r['user_name']; ?></td>
                            <td><?php echo formatPrice($r['final_amount']); ?></td>
                            <td><?php echo ucfirst($r['payment_method'] ?: 'N/A'); ?></td>
                            <td><span class="badge badge-<?php echo $r['payment_status'] === 'completed' ? 'success' : ($r['payment_status'] === 'pending' ? 'warning' : 'danger'); ?>"><?php echo ucfirst($r['payment_status']); ?></span></td>
                            <td><?php echo date('M d, Y', strtotime($r['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr style="font-weight:600;background:var(--bg-tertiary)">
                            <td colspan="2">Total Orders: <?php echo count($items); ?></td>
                            <td><?php echo formatPrice($totalAmount); ?></td>
                            <td colspan="4"></td>
                        </tr>
                    <?php elseif ($type === 'payments'):
                        $payments = getPayments(in_array($role, ['admin', 'super_admin']) ? ($targetUserId ?: null) : $userId, $role, $currentSection);
                        $totalAmount = 0;
                        $i = 0;
                        foreach ($payments as $r):
                            $i++;
                            $totalAmount += $r['final_amount']; ?>
                        <tr>
                            <td><?php echo $i; ?></td>
                            <td><?php echo $r['order_number']; ?></td>
                            <td><?php echo $r['user_name']; ?></td>
                            <td><?php echo formatPrice($r['final_amount']); ?></td>
                            <td><?php echo ucfirst($r['payment_method'] ?: 'N/A'); ?></td>
                            <td><span class="badge badge-<?php echo $r['payment_status'] === 'completed' ? 'success' : ($r['payment_status'] === 'pending' ? 'warning' : 'danger'); ?>"><?php echo ucfirst($r['payment_status']); ?></span></td>
                            <td><?php echo $r['transaction_id'] ?: 'N/A'; ?></td>
                            <td><?php echo date('M d, Y', strtotime($r['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr style="font-weight:600;background:var(--bg-tertiary)">
                            <td colspan="2">Total Payments: <?php echo count($payments); ?></td>
                            <td><?php echo formatPrice($totalAmount); ?></td>
                            <td colspan="5"></td>
                        </tr>
                    <?php elseif ($type === 'subscriptions'):
                        if (!in_array($role, ['admin', 'super_admin'])) { echo '<tr><td colspan="7">Access denied.</td></tr>'; }
                        else {
                            require_once __DIR__ . '/../classes/Subscription.php';
                            $sub = new Subscription($pdo);
                            $items = getSubscriptions();
                            $totalRevenue = 0;
                            $i = 0;
                            foreach ($items as $r):
                                $i++;
                                $price = $sub->getPlanPrice($r['plan_name']);
                                $totalRevenue += $price; ?>
                        <tr>
                            <td><?php echo $i; ?></td>
                            <td><?php echo $r['developer_name']; ?></td>
                            <td><?php echo ucfirst($r['plan_name']); ?></td>
                            <td><?php echo formatPrice($price); ?></td>
                            <td><span class="badge badge-<?php echo $r['status'] === 'active' ? 'success' : ($r['status'] === 'pending' ? 'warning' : 'secondary'); ?>"><?php echo ucfirst($r['status']); ?></span></td>
                            <td><?php echo date('M d, Y', strtotime($r['created_at'])); ?></td>
                            <td><?php echo $r['expiry_date'] ? date('M d, Y', strtotime($r['expiry_date'])) : 'N/A'; ?></td>
                        </tr>
                            <?php endforeach; ?>
                        <tr style="font-weight:600;background:var(--bg-tertiary)">
                            <td colspan="2">Total: <?php echo count($items); ?></td>
                            <td><?php echo formatPrice($totalRevenue); ?></td>
                            <td colspan="4"></td>
                        </tr>
                        <?php } ?>
                    <?php elseif ($type === 'products'):
                        if (!in_array($role, ['admin', 'super_admin', 'developer'])) { echo '<tr><td colspan="6">Access denied.</td></tr>'; }
                        else {
                            $productFilterId = $role === 'developer' ? $userId : ($targetUserId ?: null);
                            $products = getExportProducts($productFilterId, $currentSection);
                            $i = 0;
                            foreach ($products as $r):
                                $i++; ?>
                        <tr>
                            <td><?php echo $i; ?></td>
                            <td><?php echo $r['title']; ?></td>
                            <td><?php echo $r['category_name'] ?: 'General'; ?></td>
                            <td><?php echo formatPrice($r['sale_price'] ?: $r['price']); ?></td>
                            <td><?php echo (int)$r['sales_count']; ?></td>
                            <td><span class="badge badge-<?php echo $r['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($r['status']); ?></span></td>
                            <td><?php echo date('M d, Y', strtotime($r['created_at'])); ?></td>
                        </tr>
                            <?php endforeach; ?>
                        <tr style="font-weight:600;background:var(--bg-tertiary)">
                            <td colspan="2">Total: <?php echo count($products); ?></td>
                            <td colspan="5"></td>
                        </tr>
                        <?php } ?>
                    <?php elseif ($type === 'users'):
                        if (!in_array($role, ['admin', 'super_admin'])) { echo '<tr><td colspan="8">Access denied.</td></tr>'; }
                        else {
                            $users = getExportUsers();
                            $i = 0;
                            foreach ($users as $r):
                                $i++; ?>
                        <tr>
                            <td><?php echo $i; ?></td>
                            <td><?php echo $r['name']; ?></td>
                            <td><?php echo $r['email']; ?></td>
                            <td><?php echo ucfirst(str_replace('_', ' ', $r['role'])); ?></td>
                            <td><span class="badge badge-<?php echo $r['status'] === 'active' ? 'success' : 'danger'; ?>"><?php echo ucfirst($r['status']); ?></span></td>
                            <td><?php echo ucfirst($r['profile_status'] ?? 'N/A'); ?></td>
                            <td><?php echo (int)$r['order_count']; ?></td>
                            <td><?php echo formatPrice($r['total_spent']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($r['created_at'])); ?></td>
                        </tr>
                            <?php endforeach; ?>
                        <tr style="font-weight:600;background:var(--bg-tertiary)">
                            <td colspan="4">Total: <?php echo count($users); ?></td>
                            <td colspan="5"></td>
                        </tr>
                        <?php } ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
