<?php
require_once __DIR__ . '/../config.php';
requireLogin();

require_once __DIR__ . '/../classes/Subscription.php';
require_once __DIR__ . '/../classes/PlanFeature.php';
require_once __DIR__ . '/../includes/currency_converter.php';

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'] ?? 'user';

$sub = new Subscription($pdo);

// Handle GET: show payment page
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $plan = sanitize($_GET['plan'] ?? '');
    $validPlans = ['starter', 'business', 'professional'];
    if (!in_array($plan, $validPlans)) {
        setFlash('error', 'Invalid plan selected.');
        redirect(SITE_URL . '/plan-selection.php');
    }
    // Redirect to plan-selection payment page
    redirect(SITE_URL . '/plan-selection.php?plan=' . $plan);
}

// Handle POST: process payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    $plan = sanitize($_POST['plan'] ?? '');
    $csrfToken = sanitize($_POST['csrf_token'] ?? '');

    if ($csrfToken !== ($_SESSION['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid security token.');
        redirect(SITE_URL . '/plan-selection.php');
    }

    $validPlans = ['starter', 'business', 'professional'];
    if (!in_array($plan, $validPlans)) {
        setFlash('error', 'Invalid plan selected.');
        redirect(SITE_URL . '/plan-selection.php');
    }

    $planLabels = ['starter' => 'Starter', 'business' => 'Business', 'professional' => 'Professional'];
    $planPrices = ['starter' => 999, 'business' => 2499, 'professional' => 4999];
    $price = $planPrices[$plan];
    $planLabel = $planLabels[$plan];
    $taxPercent = (float)getSetting('tax_percentage', 0);
    $taxAmount = ($price * $taxPercent) / 100;
    $total = $price + $taxAmount;

    // Cancel any existing active subscriptions
    $existingSubs = $sub->getByDeveloper($userId);
    foreach ($existingSubs as $es) {
        if ($es['status'] === 'active') {
            $sub->updateStatus($es['id'], 'cancelled');
        }
    }

    // Create subscription as pending first
    $expiryDate = date('Y-m-d H:i:s', strtotime('+30 days'));
    $subscriptionId = $sub->create($userId, $plan, $expiryDate);

    // Store in session for callback
    $_SESSION['pending_subscription'] = [
        'id' => $subscriptionId,
        'plan' => $plan,
        'user_id' => $userId,
    ];

    // Create Razorpay payment link with custom callback
    $keyId = getSetting('razorpay_key_id');
    $keySecret = getSetting('razorpay_key_secret');
    if (empty($keyId) || empty($keySecret)) {
        setFlash('error', 'Payment not configured. Please contact the admin.');
        redirect(SITE_URL . '/plan-selection.php');
    }
    $orderRef = 'SUB-' . $subscriptionId;
    $callbackUrl = SITE_URL . '/subscription-callback.php?order=' . urlencode($orderRef);
    $currentUser = getCurrentUser();

    $razorpayData = [
        'amount' => (int)round($total * 100),
        'currency' => 'INR',
        'accept_partial' => false,
        'description' => $planLabel . ' Plan Subscription',
        'callback_url' => $callbackUrl,
        'callback_method' => 'get',
        'customer' => [
            'name' => $currentUser['name'] ?? 'Developer',
            'email' => $currentUser['email'] ?? '',
            'contact' => $currentUser['phone'] ?? '',
        ],
        'notify' => ['sms' => false, 'email' => false],
        'reminder_enable' => false,
        'notes' => ['subscription_id' => $subscriptionId, 'plan' => $plan, 'price' => $price, 'tax_percent' => $taxPercent, 'tax_amount' => $taxAmount],
    ];

    $ch = curl_init('https://api.razorpay.com/v1/payment_links');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($razorpayData),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_USERPWD => $keyId . ':' . $keySecret,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    $apiResult = ['success' => false, 'url' => '', 'id' => ''];
    if (!$error && $httpCode >= 200 && $httpCode < 300) {
        $result = json_decode($response, true);
        if (isset($result['short_url'])) {
            $apiResult = ['success' => true, 'url' => $result['short_url'], 'id' => $result['id'] ?? ''];
        }
    }
    if (!$apiResult['success']) {
        $errMsg = $error ?: 'Unknown error';
        if (isset($result['error']['description'])) {
            $errMsg = $result['error']['description'];
            if ($httpCode == 401) {
                $errMsg = 'Invalid Razorpay API keys. Please check your Razorpay settings.';
            }
        }
        setFlash('error', 'Payment setup failed: ' . $errMsg);
        redirect(SITE_URL . '/plan-selection.php');
    }
    $razorpayUrl = $apiResult['url'];
    $razorpayPaymentLinkId = $apiResult['id'] ?? '';

    // Store payment link ID for verification
    if (!empty($razorpayPaymentLinkId)) {
        $_SESSION['pending_subscription']['payment_link_id'] = $razorpayPaymentLinkId;
    }

    $pageTitle = 'Redirecting to Payment';
    require_once __DIR__ . '/../includes/header.php';
    ?>
    <div class="container">
        <div class="section" style="text-align:center;padding:3rem 1rem">
            <div style="max-width:500px;margin:0 auto">
                <div style="margin-bottom:1.5rem">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                </div>
                <h3>Redirecting to Payment</h3>
                <p class="text-muted">You're being redirected to Razorpay for secure payment...</p>
                <div style="display:inline-block;margin:1.5rem auto;padding:0.75rem 1.5rem;background:rgba(99,102,241,0.05);border-radius:var(--border-radius-sm)">
                    <span class="text-muted" style="font-size:0.85rem">Plan:</span>
                    <strong><?php echo $planLabel; ?></strong>
                    <span style="margin:0 0.5rem;color:var(--text-muted)">|</span>
                    <span class="text-muted" style="font-size:0.85rem">Amount:</span>
                    <strong><?php echo formatINR($total); ?></strong>
                </div>
                <div style="margin:1.5rem 0">
                    <div class="spinner" style="width:40px;height:40px;border:4px solid var(--border-color);border-top-color:var(--primary);border-radius:50%;animation:spin 0.8s linear infinite;margin:0 auto"></div>
                </div>
                <noscript><meta http-equiv="refresh" content="3;url=<?php echo htmlspecialchars($razorpayUrl, ENT_QUOTES, 'UTF-8'); ?>"></noscript>
                <a href="<?php echo htmlspecialchars($razorpayUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary mt-3">Continue to Razorpay</a>
            </div>
        </div>
    </div>
    <style>@keyframes spin{to{transform:rotate(360deg)}}</style>
    <script>setTimeout(function(){ window.location.href = <?php echo json_encode($razorpayUrl); ?>; }, 2500);</script>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}
