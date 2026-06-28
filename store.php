<?php
require_once __DIR__ . '/config.php';

$developerId = (int)($_GET['developer'] ?? 0);
if (!$developerId) {
    redirect(SITE_URL . '/products.php');
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'developer'");
$stmt->execute([$developerId]);
$developer = $stmt->fetch();

if (!$developer) {
    setFlash('error', 'Store not found.');
    redirect(SITE_URL . '/products.php');
}

// Check developer has active subscription
$stmt = $pdo->prepare("SELECT COUNT(*) FROM subscriptions WHERE developer_id = ? AND status = 'active' AND expiry_date > NOW()");
$stmt->execute([$developerId]);
$hasActiveSub = $stmt->fetchColumn() > 0;

$pageTitle = sanitize($developer['store_name'] ?: $developer['name']) . ' - Store';
require_once __DIR__ . '/includes/header.php';

if (!$hasActiveSub) {
    $products = [];
} else {
    $products = getDeveloperProducts($developerId, null, [], true);
}
$storeName = sanitize($developer['store_name'] ?: $developer['name']);
$storeDescription = sanitize($developer['store_description'] ?? '');
?>

<div class="container">
    <div class="section">
        <div class="store-header" style="text-align:center;padding:2rem 0">
            <h1><?php echo $storeName; ?></h1>
            <?php if ($storeDescription): ?>
            <p class="text-muted" style="max-width:600px;margin:0.5rem auto"><?php echo $storeDescription; ?></p>
            <?php endif; ?>
            <p class="text-muted" style="font-size:0.85rem"><?php echo count($products); ?> product<?php echo count($products) !== 1 ? 's' : ''; ?></p>
        </div>

        <?php if (empty($products)): ?>
        <div class="empty-state">
            <?php if (!$hasActiveSub): ?>
            <h3>Store Unavailable</h3>
            <p>This store is currently inactive. Please check back later.</p>
            <?php else: ?>
            <h3>No Products Yet</h3>
            <p>This store has no products available.</p>
            <?php endif; ?>
            <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary">Browse All Products</a>
        </div>
        <?php else: ?>
        <div class="product-grid">
            <?php foreach ($products as $product): ?>
            <?php
            $price = $product['sale_price'] ?? $product['price'];
            $hasSale = $product['sale_price'] && $product['sale_price'] < $product['price'];
            $discount = $hasSale ? round((($product['price'] - $product['sale_price']) / $product['price']) * 100) : 0;
            $screenshots = json_decode($product['screenshots'] ?? '[]', true);
            $thumbnail = !empty($screenshots) ? $screenshots[0] : null;
            ?>
            <div class="product-card">
                <div class="product-card-image">
                    <?php if ($thumbnail): ?>
                    <img src="<?php echo SITE_URL . '/uploads/screenshots/' . sanitize($thumbnail); ?>" alt="<?php echo sanitize($product['title']); ?>">
                    <?php elseif (!empty($product['image_url'])): ?>
                    <img src="<?php echo sanitize($product['image_url']); ?>" alt="<?php echo sanitize($product['title']); ?>">
                    <?php else: ?>
                    <div style="position:absolute;top:0;left:0;width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:var(--bg-tertiary)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    </div>
                    <?php endif; ?>
                    <?php if ($hasSale): ?>
                    <span class="product-card-badge">-<?php echo $discount; ?>%</span>
                    <?php endif; ?>
                </div>
                <div class="product-card-body">
                    <div class="product-card-category"><?php echo getProductTypeLabel($product['product_type'] ?? ''); ?></div>
                    <h3 class="product-card-title">
                        <a href="<?php echo SITE_URL; ?>/product.php?slug=<?php echo urlencode($product['slug']); ?>"><?php echo sanitize($product['title']); ?></a>
                    </h3>
                    <div class="product-card-price">
                        <span class="price-current"><?php echo formatPrice($price); ?></span>
                        <?php if ($hasSale): ?>
                        <span class="price-original"><?php echo formatPrice($product['price']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="product-card-developer">by <?php echo $storeName; ?></div>
                    <div class="product-card-footer">
                        <a href="<?php echo SITE_URL; ?>/product.php?slug=<?php echo urlencode($product['slug']); ?>" class="btn btn-secondary btn-sm">View</a>
                        <?php if (isLoggedIn()): ?>
                        <form method="POST" action="<?php echo SITE_URL; ?>/buy-now.php" style="display:inline">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <button type="submit" class="btn btn-primary btn-sm">Buy Now</button>
                        </form>
                        <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-primary btn-sm">Login to Buy</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
