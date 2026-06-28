<?php
require_once __DIR__ . '/config.php';
requireLogin();
require_once __DIR__ . '/includes/InvoicePdf.php';

$orderId = (int)($_GET['id'] ?? 0);
$order = getOrder($orderId, $_SESSION['user_id']);

if (!$order) {
    die('Order not found.');
}

$stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$order['user_id']]);
$user = $stmt->fetch();
$order['user_name'] = $user['name'] ?? '';
$order['user_email'] = $user['email'] ?? '';
$netAmount = $order['total_amount'] - $order['discount_amount'];
$order['tax_percentage'] = $order['total_amount'] > 0 && $order['tax_amount'] > 0 && $netAmount > 0
    ? round(($order['tax_amount'] / $netAmount) * 100, 2)
    : (float)getSetting('tax_percentage', 0);

$invoice = new InvoicePdf();
$pdf = $invoice->generateOrderInvoice($order);
if (ob_get_level()) ob_clean();
$pdf->Output('invoice-' . $order['order_number'] . '.pdf', 'D');
