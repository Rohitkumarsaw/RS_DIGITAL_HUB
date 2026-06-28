<?php
require_once __DIR__ . '/../config.php';
requireLogin();
require_once __DIR__ . '/../includes/InvoicePdf.php';
require_once __DIR__ . '/../classes/Subscription.php';
require_once __DIR__ . '/../includes/currency_converter.php';

$subscriptionId = (int)($_GET['id'] ?? 0);
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'] ?? '';

$sub = new Subscription($pdo);
$data = $sub->getById($subscriptionId);

if (!$data) {
    die('Invalid invoice request.');
}

if ($data['developer_id'] != $userId && !in_array($userRole, ['admin', 'super_admin'])) {
    die('Invalid invoice request.');
}

$planPrice = $sub->getPlanPrice($data['plan_name']);
$taxPercent = (float)getSetting('tax_percentage', 0);
$taxAmount = ($planPrice * $taxPercent) / 100;
$total = $planPrice + $taxAmount;

$invoice = new InvoicePdf();
$pdf = $invoice->generateSubscriptionInvoice($data, $planPrice, $taxPercent, $taxAmount, $total);
$pdf->Output('invoice-' . strtoupper($data['plan_name']) . '-' . str_pad($data['id'], 5, '0', STR_PAD_LEFT) . '.pdf', 'D');
