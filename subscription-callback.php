<?php
require_once __DIR__ . '/config.php';
requireLogin();
$userId = $_SESSION['user_id'];

$orderNumber = $_GET['order'] ?? '';

// Get payment_link_id from session (set when payment link was created)
$paymentLinkId = $_SESSION['pending_subscription']['payment_link_id'] ?? '';

if (empty($orderNumber) || strpos($orderNumber, 'SUB-') !== 0) {
    setFlash('error', 'Invalid subscription callback.');
    redirect(SITE_URL . '/developer/subscription.php');
}

$subscriptionId = (int)str_replace('SUB-', '', $orderNumber);
if (!$subscriptionId) {
    setFlash('error', 'Invalid subscription reference.');
    redirect(SITE_URL . '/developer/subscription.php');
}

// Find subscription
require_once __DIR__ . '/classes/Subscription.php';
$sub = new Subscription($pdo);
$subscription = $sub->getById($subscriptionId);

if (!$subscription || $subscription['developer_id'] != $userId) {
    setFlash('error', 'Subscription not found.');
    redirect(SITE_URL . '/developer/subscription.php');
}

if ($subscription['status'] === 'active') {
    setFlash('info', 'This subscription is already active.');
    redirect(SITE_URL . '/developer/subscription.php');
}

// Verify payment via Razorpay API
$keyId = getSetting('razorpay_key_id');
$keySecret = getSetting('razorpay_key_secret');

if (empty($paymentLinkId) || empty($keyId) || empty($keySecret)) {
    setFlash('error', 'Payment verification unavailable. Please contact support.');
    redirect(SITE_URL . '/developer/subscription.php');
}

$ch = curl_init('https://api.razorpay.com/v1/payment_links/' . $paymentLinkId);
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
    redirect(SITE_URL . '/developer/subscription.php');
}

$paymentLink = json_decode($response, true);
$isPaid = ($paymentLink['status'] === 'paid');

if (!$isPaid) {
    setFlash('error', 'Payment not completed. Please try again.');
    redirect(SITE_URL . '/developer/subscription.php');
}

// Activate subscription
$expiryDate = date('Y-m-d H:i:s', strtotime('+30 days'));
$stmt = $pdo->prepare("UPDATE subscriptions SET status = 'active', purchase_date = NOW(), expiry_date = ? WHERE id = ?");
$stmt->execute([$expiryDate, $subscriptionId]);

// Generate invoice
$invoiceNumber = 'INV-' . strtoupper($subscription['plan_name']) . '-' . str_pad($subscriptionId, 5, '0', STR_PAD_LEFT);
$invoicePath = 'invoices/generate_invoice.php?id=' . $subscriptionId;
$stmt = $pdo->prepare("INSERT INTO subscription_invoices (subscription_id, invoice_number, invoice_path) VALUES (?, ?, ?)");
$stmt->execute([$subscriptionId, $invoiceNumber, $invoicePath]);

unset($_SESSION['pending_subscription']);

setFlash('success', 'Payment successful! Your ' . ucfirst($subscription['plan_name']) . ' plan is now active.');
redirect(SITE_URL . '/developer/subscription.php');
