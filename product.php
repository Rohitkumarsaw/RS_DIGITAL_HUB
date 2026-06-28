<?php
$pageTitle = 'Product Detail';
require_once __DIR__ . '/includes/header.php';

$slug = trim($_GET['slug'] ?? '');
if (empty($slug)) {
    redirect(SITE_URL . '/products.php');
}

$product = getProductBySlug($slug);
if (!$product || $product['status'] !== 'active') {
    setFlash('error', 'Product not found.');
    redirect(SITE_URL . '/products.php');
}

// Increment views
$pdo->prepare("UPDATE products SET views = views + 1 WHERE id = ?")->execute([$product['id']]);

$price = $product['sale_price'] ?? $product['price'];
$hasSale = $product['sale_price'] && $product['sale_price'] < $product['price'];
$discount = $hasSale ? round((($product['price'] - $product['sale_price']) / $product['price']) * 100) : 0;
$screenshots = json_decode($product['screenshots'] ?? '[]', true);
$relatedProducts = getRelatedProducts($product['id'], $product['category_id']);

$pageTitle = $product['title'];
?>

<div class="container">
    <div class="section">
        <nav class="mb-3" style="color:var(--text-secondary)">
            <a href="<?php echo SITE_URL; ?>/index.php">Home</a> /
            <a href="<?php echo SITE_URL; ?>/products.php">Products</a> /
            <?php if ($product['product_type']): ?>
            <a href="<?php echo SITE_URL; ?>/products.php?type=<?php echo $product['product_type']; ?>"><?php echo getProductTypeLabel($product['product_type']); ?></a> /
            <?php endif; ?>
            <span><?php echo sanitize($product['title']); ?></span>
        </nav>

        <div class="product-detail">
            <!-- Gallery -->
            <div>
                <div class="product-gallery">
                    <div class="product-gallery-main">
                        <?php if (!empty($screenshots)): ?>
                        <img src="<?php echo SITE_URL . '/uploads/screenshots/' . sanitize($screenshots[0]); ?>" alt="<?php echo sanitize($product['title']); ?>" id="mainImage">
                        <?php elseif ($product['image_url']): ?>
                        <img src="<?php echo sanitize($product['image_url']); ?>" alt="<?php echo sanitize($product['title']); ?>" id="mainImage">
                        <?php else: ?>
                        <div style="position:absolute;top:0;left:0;width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:var(--bg-tertiary)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if (count($screenshots) > 1): ?>
                    <div class="product-gallery-thumbs">
                        <?php foreach ($screenshots as $i => $screenshot): ?>
                        <img src="<?php echo SITE_URL . '/uploads/screenshots/' . sanitize($screenshot); ?>" alt="Screenshot <?php echo $i + 1; ?>" class="<?php echo $i === 0 ? 'active' : ''; ?>">
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Product Info -->
            <div class="product-info">
                <div class="product-card-category mb-2"><?php echo getProductTypeLabel($product['product_type'] ?? ''); ?></div>
                <h1><?php echo sanitize($product['title']); ?></h1>

                <p class="text-muted mb-2" style="font-size:0.9rem">by <a href="<?php echo SITE_URL; ?>/store.php?developer=<?php echo $product['developer_id']; ?>" style="color:inherit;font-weight:600;text-decoration:underline;text-underline-offset:2px"><?php echo sanitize($product['developer_store'] ?: $product['developer_name'] ?: 'Digital Hub'); ?></a></p>

                <div class="product-meta">
                    <span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <?php echo number_format($product['views']); ?> views
                    </span>
                    <span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                        <?php echo $product['sales_count']; ?> sales
                    </span>
                    <?php if ($product['file_size']): ?>
                    <span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>
                        <?php echo $product['file_size']; ?>
                    </span>
                    <?php endif; ?>
                </div>

                <div class="product-card-price mb-3">
                    <span class="price-current" style="font-size:2rem"><?php echo formatPrice($price); ?></span>
                    <?php if ($hasSale): ?>
                    <span class="price-original" style="font-size:1.25rem"><?php echo formatPrice($product['price']); ?></span>
                    <span class="badge badge-danger">Save <?php echo $discount; ?>%</span>
                    <?php endif; ?>
                </div>

                <div class="product-actions">
                    <?php if (isLoggedIn()): ?>
                    <form method="POST" action="<?php echo SITE_URL; ?>/buy-now.php">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                            <?php if ($product['is_free']): ?>
                            Download Now — Free
                            <?php else: ?>
                            Buy Now — <?php echo formatPrice($product['sale_price'] > 0 ? $product['sale_price'] : $product['price']); ?>
                            <?php endif; ?>
                        </button>
                    </form>
                    <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-primary btn-lg"><?php echo $product['is_free'] ? 'Login to Download' : 'Login to Purchase'; ?></a>
                    <?php endif; ?>
                </div>

                <?php if (!empty($product['demo_url']) && preg_match('#^https?://#', $product['demo_url'])): ?>
                <a href="<?php echo htmlspecialchars($product['demo_url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="btn btn-outline btn-block mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    View Live Demo
                </a>
                <?php endif; ?>

                <div class="product-features">
                    <h4>Product Details</h4>
                    <ul>
                        <li>Instant digital download after purchase</li>
                        <li>Secure payment processing</li>
                        <li>Lifetime access to purchased files</li>
                        <li>Free updates included</li>
                        <li>24/7 customer support</li>
                    </ul>
                </div>

                <?php
                $productFiles = getProductFiles($product['id']);
                if (!empty($productFiles)):
                ?>
                <div class="product-files" style="margin-top:1.5rem;padding-top:1.5rem;border-top:1px solid var(--border-color)">
                    <h4 style="margin-bottom:0.75rem">Available Files / Versions</h4>
                    <div style="display:flex;flex-direction:column;gap:0.5rem">
                        <?php foreach ($productFiles as $pf):
                        $hasFile = !empty($pf['file_path']);
                        $hasUrl = !empty($pf['file_url']) && preg_match('#^https?://#', $pf['file_url']);
                        ?>
                        <div style="display:flex;align-items:center;gap:0.75rem;padding:0.6rem 0.8rem;background:var(--bg-tertiary);border-radius:var(--border-radius-sm)">
                            <div style="flex:1">
                                <strong><?php echo sanitize($pf['title']); ?></strong>
                                <?php if ($pf['version']): ?>
                                <span class="badge badge-primary" style="font-size:0.65rem;margin-left:0.35rem">v<?php echo sanitize($pf['version']); ?></span>
                                <?php endif; ?>
                                <?php if ($pf['file_name']): ?>
                                <span style="display:block;font-size:0.8rem;color:var(--text-muted)"><?php echo sanitize($pf['file_name']); ?> (<?php echo $pf['file_size']; ?>)</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($hasUrl): ?>
                            <a href="<?php echo sanitize($pf['file_url']); ?>" target="_blank" class="btn btn-sm btn-outline" style="flex-shrink:0">View Link</a>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Description -->
        <div class="card mt-4">
            <div class="card-header">
                <h3>Description</h3>
            </div>
            <div class="card-body">
                <?php if ($product['short_description']): ?>
                <p class="text-muted" style="font-size:1.1rem"><?php echo nl2br(sanitize($product['short_description'])); ?></p>
                <hr style="margin:1.5rem 0;border:none;border-top:1px solid var(--border-color)">
                <?php endif; ?>
                <?php echo nl2br(sanitize($product['description'])); ?>
            </div>
        </div>

        <!-- Related Products -->
        <?php if (!empty($relatedProducts)): ?>
        <div class="mt-4">
            <div class="section-title">
                <h3>Related Products</h3>
            </div>
            <div class="product-grid">
                <?php foreach ($relatedProducts as $related): ?>
                <?php
                $relPrice = $related['sale_price'] ?? $related['price'];
                $relHasSale = $related['sale_price'] && $related['sale_price'] < $related['price'];
                $relScreenshots = json_decode($related['screenshots'] ?? '[]', true);
                $relThumbnail = !empty($relScreenshots) ? $relScreenshots[0] : null;
                ?>
                <div class="product-card">
                    <div class="product-card-image">
                        <?php if ($relThumbnail): ?>
                        <img src="<?php echo SITE_URL . '/uploads/screenshots/' . sanitize($relThumbnail); ?>" alt="<?php echo sanitize($related['title']); ?>">
                        <?php elseif (!empty($related['image_url'])): ?>
                        <img src="<?php echo sanitize($related['image_url']); ?>" alt="<?php echo sanitize($related['title']); ?>">
                        <?php else: ?>
                        <div style="position:absolute;top:0;left:0;width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:var(--bg-tertiary)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="product-card-body">
                        <div class="product-card-category"><?php echo getProductTypeLabel($related['product_type'] ?? ''); ?></div>
                        <h3 class="product-card-title">
                            <a href="<?php echo SITE_URL; ?>/product.php?slug=<?php echo urlencode($related['slug']); ?>"><?php echo sanitize($related['title']); ?></a>
                        </h3>
                        <div class="product-card-price">
                            <span class="price-current"><?php echo formatPrice($relPrice); ?></span>
                        </div>
                        <div class="product-card-developer">by <a href="<?php echo SITE_URL; ?>/store.php?developer=<?php echo $related['developer_id']; ?>" style="color:var(--text-muted);text-decoration:underline;text-underline-offset:2px"><?php echo sanitize($related['developer_store'] ?: $related['developer_name'] ?: 'Digital Hub'); ?></a></div>
                        <div class="product-card-footer">
                            <a href="<?php echo SITE_URL; ?>/product.php?slug=<?php echo urlencode($related['slug']); ?>" class="btn btn-secondary btn-sm">View</a>
                            <?php if (isLoggedIn()): ?>
                            <form method="POST" action="<?php echo SITE_URL; ?>/buy-now.php" style="display:inline">
                                <input type="hidden" name="product_id" value="<?php echo $related['id']; ?>">
                                <button type="submit" class="btn btn-primary btn-sm"><?php echo $related['is_free'] ? 'Download' : 'Buy Now'; ?></button>
                            </form>
                            <?php else: ?>
                            <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-primary btn-sm">Login</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
