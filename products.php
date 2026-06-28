<?php
$pageTitle = 'Products';
require_once __DIR__ . '/includes/header.php';

$search = sanitize($_GET['search'] ?? '');
$type = sanitize($_GET['type'] ?? '');
$sort = sanitize($_GET['sort'] ?? 'newest');

// Default to session section if no type specified
if (empty($type)) {
    $currentSection = getCurrentSection();
    if ($currentSection !== 'general') {
        $type = $currentSection;
    }
}

$filters = [
    'search' => $search,
    'type' => $type,
    'sort' => $sort
];

$products = getProducts($filters);
$productTypes = getProductTypes();
?>

<div class="container">
    <div class="section">
        <div class="section-title">
            <h2>All Products</h2>
            <p>Browse our collection of premium digital products</p>
        </div>

        <!-- Search & Filters -->
        <form method="GET" class="filters">
            <div class="search-bar" style="flex:2;min-width:200px;margin-bottom:0">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" name="search" class="form-control" placeholder="Search products..." value="<?php echo $search; ?>">
            </div>

            <select name="type" class="form-control">
                <option value="">All Categories</option>
                <?php foreach ($productTypes as $key => $label): ?>
                <option value="<?php echo $key; ?>" <?php echo $type === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>

            <select name="sort" class="form-control">
                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Most Popular</option>
            </select>

            <button type="submit" class="btn btn-primary">Filter</button>
        </form>

        <!-- Products Grid -->
        <?php if (empty($products)): ?>
        <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
            <h3>No Products Found</h3>
            <p>Try adjusting your search or filters.</p>
            <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary">Clear Filters</a>
        </div>
        <?php else: ?>
        <p class="text-muted mb-3"><?php echo count($products); ?> product<?php echo count($products) !== 1 ? 's' : ''; ?> found</p>

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
                    <div class="product-card-developer">by <a href="<?php echo SITE_URL; ?>/store.php?developer=<?php echo $product['developer_id']; ?>" style="color:var(--text-muted);text-decoration:underline;text-underline-offset:2px"><?php echo sanitize($product['developer_store'] ?: $product['developer_name'] ?: 'Digital Hub'); ?></a></div>
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
