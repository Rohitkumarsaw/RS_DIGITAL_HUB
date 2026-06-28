<?php
require_once __DIR__ . '/config.php';
requireLogin();
$userId = $_SESSION['user_id'];

$orderNumber = $_GET['order'] ?? ($_SESSION['pending_order']['order_number'] ?? null);

if (!$orderNumber) {
    setFlash('info', 'No pending order found.');
    redirect(SITE_URL . '/orders.php');
}

$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ? AND user_id = ?");
$stmt->execute([$orderNumber, $userId]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('error', 'Order not found.');
    redirect(SITE_URL . '/orders.php');
}

if ($order['status'] === 'completed') {
    setFlash('info', 'This order is already completed.');
    redirect(SITE_URL . '/orders.php');
}

if ($order['status'] === 'cancelled') {
    setFlash('error', 'This order has been cancelled.');
    redirect(SITE_URL . '/orders.php');
}

// Verify payment via Razorpay API
$razorpayPaymentLinkId = $order['transaction_id'];
if (empty($razorpayPaymentLinkId)) {
    setFlash('error', 'Payment reference missing. Please contact support.');
    redirect(SITE_URL . '/orders.php');
}

// Fetch payment link status from Razorpay
$keyId = getSetting('razorpay_key_id');
$keySecret = getSetting('razorpay_key_secret');

if (empty($keyId) || empty($keySecret)) {
    setFlash('error', 'Payment gateway not configured. Please contact support.');
    redirect(SITE_URL . '/orders.php');
}

$ch = curl_init('https://api.razorpay.com/v1/payment_links/' . $razorpayPaymentLinkId);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPGET => true,
    CURLOPT_USERPWD => $keyId . ':' . $keySecret,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_SSL_VERIFYPEER => true,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error || $httpCode !== 200) {
    setFlash('error', 'Payment verification failed. Please contact support.');
    redirect(SITE_URL . '/orders.php');
}

$paymentLink = json_decode($response, true);
$isPaid = ($paymentLink['status'] === 'paid');

if (!$isPaid) {
    setFlash('error', 'Payment not completed. Please try again.');
    redirect(SITE_URL . '/orders.php');
}

// Mark order as completed
$stmt = $pdo->prepare("UPDATE orders SET status = 'completed', payment_status = 'completed' WHERE id = ?");
$stmt->execute([$order['id']]);

$stmt = $pdo->prepare("UPDATE order_items SET payment_status = 'completed' WHERE order_id = ?");
$stmt->execute([$order['id']]);

// Grant download access
$stmt = $pdo->prepare("SELECT product_id FROM order_items WHERE order_id = ?");
$stmt->execute([$order['id']]);

foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $pid) {
    $check = $pdo->prepare("SELECT id FROM downloads WHERE user_id = ? AND product_id = ?");
    $check->execute([$userId, $pid]);
    if (!$check->fetch()) {
        $expiresAt = date('Y-m-d H:i:s', strtotime('+72 hours'));
        $token = generateToken(20);
        $ins = $pdo->prepare("INSERT INTO downloads (user_id, product_id, order_id, download_token, download_count, expires_at) VALUES (?, ?, ?, ?, 0, ?)");
        $ins->execute([$userId, $pid, $order['id'], $token, $expiresAt]);
    }
}

unset($_SESSION['pending_order']);

setFlash('success', 'Payment received! Your order is complete. You can now download your products.');
redirect(SITE_URL . '/orders.php');
