<?php
require_once __DIR__ . '/config.php';
$pageTitle = 'My Orders';
requireLogin();
require_once __DIR__ . '/includes/header.php';

$orders = getUserOrders($_SESSION['user_id']);
?>

<div class="container">
    <div class="section">
        <h2 class="mb-3">My Orders</h2>

        <?php if (empty($orders)): ?>
        <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            <h3>No Orders Yet</h3>
            <p>Start shopping to see your orders here.</p>
            <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary">Browse Products</a>
        </div>
        <?php else: ?>
        <?php foreach ($orders as $order): ?>
        <div class="order-card">
            <div class="order-header">
                <div>
                    <strong><?php echo sanitize($order['order_number']); ?></strong>
                    <span class="text-muted" style="margin-left:0.5rem"><?php echo formatDate($order['created_at']); ?></span>
                </div>
                <div class="d-flex gap-1 flex-wrap">
                    <?php if ($order['status'] === 'completed'): ?>
                    <span class="badge badge-success">Completed</span>
                    <?php elseif ($order['status'] === 'cancelled'): ?>
                    <span class="badge badge-danger">Cancelled</span>
                    <?php else: ?>
                    <span class="badge badge-warning">Pending</span>
                    <?php endif; ?>
                    <span class="badge badge-primary"><?php echo formatPrice($order['final_amount']); ?></span>
                </div>
            </div>
            <div class="order-body">
                <p class="mb-2"><?php echo sanitize($order['product_titles']); ?></p>
                <div class="d-flex justify-between align-center flex-wrap gap-1">
                    <?php if ($order['status'] === 'pending'): ?>
                    <span class="text-muted">Awaiting payment</span>
                    <div class="d-flex gap-1 flex-wrap">
                        <a href="<?php echo SITE_URL; ?>/order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">Pay Now</a>
                    </div>
                    <?php else: ?>
                    <span class="text-muted">Payment: <?php echo ucfirst(sanitize($order['payment_method'])); ?></span>
                    <div class="d-flex gap-1 flex-wrap">
                        <a href="<?php echo SITE_URL; ?>/order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">View & Download</a>
                        <a href="<?php echo SITE_URL; ?>/invoice.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline" target="_blank">Invoice</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
