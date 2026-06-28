<?php
require_once __DIR__ . '/config.php';
$pageTitle = 'Order Success';
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
    <div class="section text-center">
        <div style="width:80px;height:80px;background:var(--success);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        </div>

        <h1>Order Successful!</h1>
        <p class="text-muted">Thank you for your purchase. Your order has been confirmed.</p>

        <div class="card" style="max-width:500px;margin:2rem auto">
            <div class="card-body">
                <p><strong>Order Number:</strong> <?php echo sanitize($order['order_number']); ?></p>
                <p><strong>Date:</strong> <?php echo formatDateTime($order['created_at']); ?></p>
                <p><strong>Amount Paid:</strong> <span class="text-primary" style="font-size:1.5rem;font-weight:700"><?php echo formatPrice($order['final_amount']); ?></span></p>
                <p><strong>Payment Method:</strong> <?php echo ucfirst(sanitize($order['payment_method'])); ?></p>
            </div>
        </div>

        <h3 class="mt-4">Your Downloads</h3>
        <p class="text-muted">Click below to download your purchased products</p>

        <div style="max-width:500px;margin:1.5rem auto">
            <?php foreach ($items as $item): ?>
            <?php
            $stmt = $pdo->prepare("SELECT download_token FROM downloads WHERE user_id = ? AND product_id = ? AND order_id = ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$_SESSION['user_id'], $item['product_id'], $orderId]);
            $download = $stmt->fetch();
            ?>
            <div class="order-card">
                <div class="order-body d-flex justify-between align-center flex-wrap gap-1">
                    <span><?php echo sanitize($item['title']); ?></span>
                    <?php if ($download): ?>
                    <a href="<?php echo SITE_URL; ?>/download.php?token=<?php echo $download['download_token']; ?>" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Download
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="d-flex gap-1 justify-center flex-wrap mt-4">
            <a href="<?php echo SITE_URL; ?>/profile.php?tab=downloads" class="btn btn-primary">View All Downloads</a>
            <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-outline">Continue Shopping</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
