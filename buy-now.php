<?php
require_once __DIR__ . '/config.php';
requireLogin();
$userId = $_SESSION['user_id'];

// Verify user still exists in database
$stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
$stmt->execute([$userId]);
if (!$stmt->fetch()) {
    session_unset();
    session_destroy();
    setFlash('error', 'Account not found. Please login again.');
    redirect(SITE_URL . '/login.php');
    exit;
}

// Check Razorpay API keys are configured
if (empty(getSetting('razorpay_key_id')) || empty(getSetting('razorpay_key_secret'))) {
    setFlash('error', 'Payment not configured. Please contact the admin.');
    redirect(SITE_URL . '/products.php');
}

// Handle remove coupon AJAX — must be before product_id check (no product_id needed)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_coupon') {
    @error_reporting(0);
    @ini_set('display_errors', 0);
    if (ob_get_level()) ob_clean();
    header('Content-Type: application/json');

    $csrfToken = $_POST['csrf_token'] ?? '';
    if ($csrfToken !== ($_SESSION['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token']);
        exit;
    }

    unset($_SESSION['buy_coupon']);
    echo json_encode(['success' => true]);
    exit;
}

$productId = (int)($_GET['product_id'] ?? $_POST['product_id'] ?? 0);
if (!$productId) {
    setFlash('error', 'Invalid product.');
    redirect(SITE_URL . '/products.php');
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    setFlash('error', 'Product not found.');
    redirect(SITE_URL . '/products.php');
}

// Free product — skip payment, create order + download token directly
if ($product['is_free']) {
    $orderNumber = generateOrderNumber();
    $stmt = $pdo->prepare("
        INSERT INTO orders (order_number, user_id, total_amount, tax_amount, discount_amount, final_amount, status, payment_method, payment_status)
        VALUES (?, ?, 0, 0, 0, 0, 'completed', 'free', 'completed')
    ");
    $stmt->execute([$orderNumber, $userId]);
    $orderId = $pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, price, payment_status) VALUES (?, ?, 0, 'completed')");
    $stmt->execute([$orderId, $productId]);

    createDownloadToken($userId, $productId, $orderId);

    setFlash('success', 'Product added to your library! Download now.');
    redirect(SITE_URL . '/orders.php');
}

// Handle apply coupon AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'apply_coupon') {
    @error_reporting(0);
    @ini_set('display_errors', 0);
    if (ob_get_level()) ob_clean();
    header('Content-Type: application/json');

    $csrfToken = $_POST['csrf_token'] ?? '';
    if ($csrfToken !== ($_SESSION['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token']);
        exit;
    }

    $code = sanitize($_POST['code']);
    $price = ($product['sale_price'] > 0) ? $product['sale_price'] : $product['price'];
    $taxPercent = (float)getSetting('tax_percentage', 0);
    $taxAmount = ($price * $taxPercent) / 100;
    $result = validateCoupon($code);
    if ($result['valid']) {
        $calc = calculateDiscount($result['coupon'], $price);
        if ($calc['discount'] > 0) {
            $_SESSION['buy_coupon'] = ['code' => $code, 'discount' => $calc['discount']];
            $newTotal = max(0, $price + $taxAmount - $calc['discount']);
            echo json_encode(['success' => true, 'discount' => $calc['discount'], 'discountFormatted' => formatPrice($calc['discount']), 'newTotalFormatted' => formatPrice($newTotal)]);
            exit;
        }
    }
    echo json_encode(['success' => false, 'message' => $result['message'] ?? 'Invalid coupon']);
    exit;
}

// Handle Pay Now
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_now'])) {
    $price = ($product['sale_price'] > 0) ? $product['sale_price'] : $product['price'];
    $taxPercent = (float)getSetting('tax_percentage', 0);
    $taxAmount = ($price * $taxPercent) / 100;
    $discountAmount = $_SESSION['buy_coupon']['discount'] ?? 0;
    $couponCode = $_SESSION['buy_coupon']['code'] ?? '';
    $total = max(0, $price + $taxAmount - $discountAmount);

    // Create pending order
    $orderNumber = generateOrderNumber();
    $stmt = $pdo->prepare("
        INSERT INTO orders (order_number, user_id, total_amount, tax_amount, discount_amount, final_amount, coupon_code, status, payment_method, payment_status)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', 'razorpay', 'pending')
    ");
    $stmt->execute([$orderNumber, $userId, $price, $taxAmount, $discountAmount, $total, $couponCode]);
    $orderId = $pdo->lastInsertId();

    // Add order item
    $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, price, quantity) VALUES (?, ?, ?, 1)");
    $stmt->execute([$orderId, $productId, $price]);

    // Increment coupon usage count
    if (!empty($couponCode)) {
        $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE code = ?")->execute([$couponCode]);
    }

    // Store in session
    $_SESSION['pending_order'] = [
        'order_number' => $orderNumber,
        'order_id' => $orderId,
        'product_id' => $productId,
        'total' => $total
    ];

    unset($_SESSION['buy_coupon']);

    // Create payment link via Razorpay API
    $currentUser = getCurrentUser();
    if (empty($currentUser)) {
        session_unset();
        session_destroy();
        setFlash('error', 'Session expired. Please login again.');
        redirect(SITE_URL . '/login.php');
        exit;
    }
    // Use product owner's Razorpay keys if set, fallback to admin keys
    $customKeys = [];
    if (!empty($product['developer_id'])) {
        $stmt = $pdo->prepare("SELECT razorpay_key_id, razorpay_key_secret FROM users WHERE id = ?");
        $stmt->execute([$product['developer_id']]);
        $devKeys = $stmt->fetch();
        if (!empty($devKeys['razorpay_key_id']) && !empty($devKeys['razorpay_key_secret'])) {
            $customKeys = ['key_id' => $devKeys['razorpay_key_id'], 'key_secret' => $devKeys['razorpay_key_secret']];
        }
    }
    $apiResult = createRazorpayPaymentLink(
        $total,
        $product['title'],
        $orderNumber,
        [
            'name' => $currentUser['name'] ?? 'Customer',
            'email' => $currentUser['email'] ?? '',
            'contact' => $currentUser['phone'] ?? '',
        ],
        $customKeys
    );

    if (!$apiResult['success']) {
        setFlash('error', 'Payment setup failed: ' . $apiResult['error']);
        redirect(SITE_URL . '/buy-now.php?product_id=' . $productId);
    }

    // Store payment link ID for verification in payment-callback.php
    if (!empty($apiResult['id'])) {
        $stmt = $pdo->prepare("UPDATE orders SET transaction_id = ? WHERE id = ?");
        $stmt->execute([$apiResult['id'], $orderId]);
    }

    $razorpayUrl = $apiResult['url'];

    $pageTitle = 'Redirecting to Razorpay';
    require_once __DIR__ . '/includes/header.php';
    ?>
    <div class="container">
        <div class="section" style="text-align:center;padding:3rem 1rem">
            <div style="max-width:500px;margin:0 auto">
                <div style="margin-bottom:1.5rem">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                </div>
                <h3>Order Created Successfully!</h3>
                <p class="text-muted">Redirecting you to Razorpay for secure payment...</p>
                <div style="display:inline-block;margin:1.5rem auto;padding:0.75rem 1.5rem;background:rgba(99,102,241,0.05);border-radius:var(--border-radius-sm)">
                    <span class="text-muted" style="font-size:0.85rem">Order:</span>
                    <strong><?php echo sanitize($orderNumber); ?></strong>
                </div>
                <div style="margin:1.5rem 0">
                    <div class="spinner" style="width:40px;height:40px;border:4px solid var(--border-color);border-top-color:var(--primary);border-radius:50%;animation:spin 0.8s linear infinite;margin:0 auto"></div>
                </div>
                <p style="font-size:0.9rem">After payment, you will be <strong>automatically redirected</strong> to your orders page.</p>
                <noscript><meta http-equiv="refresh" content="3;url=<?php echo htmlspecialchars($razorpayUrl, ENT_QUOTES, 'UTF-8'); ?>"></noscript>
                <a href="<?php echo htmlspecialchars($razorpayUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary mt-3">Continue to Razorpay</a>
            </div>
        </div>
    </div>
    <style>@keyframes spin{to{transform:rotate(360deg)}}</style>
    <script>setTimeout(function(){ window.location.href = <?php echo json_encode($razorpayUrl); ?>; }, 2500);</script>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// GET: show payment details page
$price = ($product['sale_price'] > 0) ? $product['sale_price'] : $product['price'];
$taxPercent = (float)getSetting('tax_percentage', 0);
$taxAmount = ($price * $taxPercent) / 100;
$discountAmount = $_SESSION['buy_coupon']['discount'] ?? 0;
$couponCode = $_SESSION['buy_coupon']['code'] ?? '';
$total = max(0, $price + $taxAmount - $discountAmount);

$screenshots = json_decode($product['screenshots'] ?? '[]', true);
$thumbnail = !empty($screenshots) ? $screenshots[0] : null;

$pageTitle = 'Buy - ' . $product['title'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="section">
        <div style="max-width:700px;margin:0 auto">
            <h2 class="mb-3">Complete Your Purchase</h2>

            <div class="checkout-grid">
                <!-- Product Details -->
                <div>
                    <div class="card">
                        <div class="card-header"><h3>Product</h3></div>
                        <div class="card-body">
                            <div style="display:flex;gap:1rem;align-items:flex-start">
                                <?php if ($thumbnail): ?>
                                <img src="<?php echo SITE_URL . '/uploads/screenshots/' . sanitize($thumbnail); ?>" alt="<?php echo sanitize($product['title']); ?>" style="width:100px;height:100px;object-fit:cover;border-radius:var(--border-radius-sm);flex-shrink:0">
                                <?php endif; ?>
                                <div>
                                    <h4 style="margin:0 0 0.25rem"><?php echo sanitize($product['title']); ?></h4>
                                    <p class="text-muted mb-1" style="font-size:0.9rem"><?php echo sanitize($product['short_description'] ?? ''); ?></p>
                                    <div style="font-size:1.2rem;font-weight:700;color:var(--primary)"><?php echo formatPrice($price); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Razorpay info -->
                    <div class="card mt-3">
                        <div class="card-body" style="padding:1rem">
                            <div style="display:flex;align-items:center;gap:0.75rem">
                                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                                <div>
                                    <strong>Secure Payment via Razorpay</strong>
                                    <p class="text-muted mb-0" style="font-size:0.85rem">Pay using UPI, Credit/Debit Card, Net Banking, Wallet</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div>
                    <div class="cart-summary">
                        <h3 class="mb-3">Order Summary</h3>

                        <div class="cart-summary-row" style="border-bottom:none">
                            <span><?php echo sanitize($product['title']); ?> × 1</span>
                            <span><?php echo formatPrice($price); ?></span>
                        </div>

                        <div class="cart-summary-row">
                            <span>Subtotal</span>
                            <span><?php echo formatPrice($price); ?></span>
                        </div>

                        <?php if ($taxPercent > 0): ?>
                        <div class="cart-summary-row">
                            <span>Tax (<?php echo $taxPercent; ?>%)</span>
                            <span><?php echo formatPrice($taxAmount); ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if ($discountAmount > 0): ?>
                        <div class="cart-summary-row" style="color:var(--success)" id="discountRow">
                            <span>Discount (<?php echo sanitize($couponCode); ?>)</span>
                            <span>-<?php echo formatPrice($discountAmount); ?></span>
                        </div>
                        <?php endif; ?>

                        <div class="cart-summary-row" style="font-size:1.1rem;font-weight:700">
                            <span>Total</span>
                            <span id="totalDisplay"><?php echo formatPrice($total); ?></span>
                        </div>

                        <!-- Coupon -->
                        <?php if (!isset($_SESSION['buy_coupon'])): ?>
                        <div class="d-flex gap-1 mt-3">
                            <input type="text" id="couponCode" class="form-control" placeholder="Coupon code">
                            <button type="button" id="applyCoupon" class="btn btn-secondary">Apply</button>
                        </div>
                        <?php else: ?>
                        <div class="mt-3">
                            <span class="badge badge-success"><?php echo sanitize($couponCode); ?> applied</span>
                            <button type="button" id="removeCoupon" class="text-danger" style="margin-left:0.5rem;font-size:0.85rem;background:none;border:none;cursor:pointer;text-decoration:underline">Remove</button>
                        </div>
                        <?php endif; ?>

                        <!-- Pay Now -->
                        <form method="POST" action="<?php echo SITE_URL; ?>/buy-now.php" style="margin-top:1rem">
                            <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
                            <button type="submit" name="pay_now" class="btn btn-success btn-block btn-lg">
                                Pay Now — <?php echo formatPrice($total); ?>
                            </button>
                        </form>

                        <p class="text-muted mt-2" style="text-align:center;font-size:0.8rem">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:2px"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            Secure checkout. Your payment is protected.
                        </p>
                        <p class="text-muted" style="text-align:center;font-size:0.8rem;margin-top:0.25rem">
                            After payment, you'll be <strong>automatically redirected</strong> to your orders page.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



<?php require_once __DIR__ . '/includes/footer.php'; ?>
