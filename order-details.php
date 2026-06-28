<?php
require_once __DIR__ . '/config.php';
$pageTitle = 'Order Details';
requireLogin();
require_once __DIR__ . '/includes/header.php';

$orderId = (int)($_GET['id'] ?? 0);
$order = getOrder($orderId, $_SESSION['user_id']);

if (!$order) {
    setFlash('error', 'Order not found.');
    redirect(SITE_URL . '/orders.php');
}

$items = getOrderItems($orderId);
?>

<div class="container">
    <div class="section">
        <nav class="mb-3" style="color:var(--text-secondary)">
            <a href="<?php echo SITE_URL; ?>/profile.php?tab=orders">My Orders</a> /
            <span><?php echo sanitize($order['order_number']); ?></span>
        </nav>

        <div class="card mb-3">
            <div class="card-header d-flex justify-between align-center flex-wrap gap-1">
                <h3 class="mb-0">Order <?php echo sanitize($order['order_number']); ?></h3>
                <span class="badge badge-<?php echo $order['status'] === 'completed' ? 'success' : ($order['status'] === 'cancelled' ? 'danger' : 'warning'); ?>">
                    <?php echo ucfirst($order['status']); ?>
                </span>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <div>
                        <strong>Date:</strong> <?php echo formatDateTime($order['created_at']); ?>
                    </div>
                    <div>
                        <strong>Payment:</strong> <?php echo ucfirst(sanitize($order['payment_method'])); ?>
                    </div>
                    <?php if ($order['transaction_id']): ?>
                    <div>
                        <strong>Transaction ID:</strong> <?php echo sanitize($order['transaction_id']); ?>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($order['status'] === 'pending'): ?>
                <div style="text-align:center;padding:2rem 0">
                    <p class="text-muted mb-3">This order is awaiting payment.</p>
                    <a href="<?php echo SITE_URL; ?>/payment-callback.php?order=<?php echo urlencode($order['order_number']); ?>" class="btn btn-primary btn-lg">Complete Order & Download</a>
                    <p class="text-muted mt-2" style="font-size:0.85rem">
                        If you've already paid on Razorpay, click the button above to complete your order.
                    </p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <?php
                            $stmt = $pdo->prepare("SELECT download_token FROM downloads WHERE user_id = ? AND product_id = ? AND order_id = ? ORDER BY id DESC LIMIT 1");
                            $stmt->execute([$_SESSION['user_id'], $item['product_id'], $orderId]);
                            $download = $stmt->fetch();
                            $productFiles = $download ? getProductFiles($item['product_id']) : [];
                            ?>
                            <tr>
                                <td>
                                    <?php echo sanitize($item['title']); ?>
                                    <?php if (!empty($productFiles)): ?>
                                    <div style="margin-top:0.4rem;font-size:0.8rem">
                                        <?php foreach ($productFiles as $pf):
                                        $pfUrl = !empty($pf['file_url']) && preg_match('#^https?://#', $pf['file_url']);
                                        ?>
                                        <div style="padding:0.2rem 0">
                                            <span style="color:var(--text-muted)"><?php echo sanitize($pf['title']); ?><?php if ($pf['version']): ?> v<?php echo sanitize($pf['version']); ?><?php endif; ?>:</span>
                                            <a href="<?php echo SITE_URL; ?>/download.php?token=<?php echo $download['download_token']; ?>&file_id=<?php echo $pf['id']; ?>" style="margin-left:0.3rem" target="<?php echo $pfUrl ? '_blank' : ''; ?>"><?php echo $pfUrl ? 'Open Link' : 'Download'; ?></a>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatPrice($item['price']); ?></td>
                                <td>
                                    <?php if ($download): ?>
                                    <a href="<?php echo SITE_URL; ?>/download.php?token=<?php echo $download['download_token']; ?>" class="btn btn-sm btn-primary">Download</a>
                                    <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <div style="max-width:300px;margin-left:auto">
                    <div class="cart-summary-row">
                        <span>Subtotal</span>
                        <span><?php echo formatPrice($order['total_amount']); ?></span>
                    </div>
                    <?php if ($order['discount_amount'] > 0): ?>
                    <div class="cart-summary-row">
                        <span>Discount</span>
                        <span style="color:var(--success)">-<?php echo formatPrice($order['discount_amount']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($order['tax_amount'] > 0): ?>
                    <div class="cart-summary-row">
                        <span>Tax</span>
                        <span><?php echo formatPrice($order['tax_amount']); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="cart-summary-row">
                        <span>Total</span>
                        <span><?php echo formatPrice($order['final_amount']); ?></span>
                    </div>
                </div>

                <div class="mt-3 d-flex gap-1 flex-wrap">
                    <a href="<?php echo SITE_URL; ?>/invoice.php?id=<?php echo $order['id']; ?>" class="btn btn-outline" target="_blank">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        Download Invoice
                    </a>
                    <a href="<?php echo SITE_URL; ?>/orders.php" class="btn btn-secondary">Back to Orders</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
