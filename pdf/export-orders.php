<?php
require_once __DIR__ . '/../config.php';
requireLogin();
require_once __DIR__ . '/ReportPdf.php';

$userId = $_SESSION['user_id'];
$role = $_SESSION['user_role'] ?? 'user';

$allowed = ['user', 'developer', 'admin', 'super_admin'];
if (!in_array($role, $allowed)) {
    die('Access denied.');
}

$currentSection = getCurrentSection();
$sectionFilter = $currentSection !== 'general' ? " AND p.product_type = ?" : "";
$sectionParam = $currentSection !== 'general' ? [$currentSection] : [];
$targetUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

// Get orders based on role
if (in_array($role, ['admin', 'super_admin'])) {
    $adminFilter = $targetUserId ? " AND o.user_id = ?" : "";
    $params = $targetUserId ? [$targetUserId] : [];
    if ($currentSection !== 'general') {
        if ($targetUserId) {
            $params = array_merge([$targetUserId], $sectionParam);
        } else {
            $params = $sectionParam;
        }
        $stmt = $pdo->prepare("
            SELECT DISTINCT o.*, u.name as user_name, u.email as user_email
            FROM orders o
            JOIN users u ON o.user_id = u.id
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE o.payment_status IN ('completed', 'pending')" . $adminFilter . $sectionFilter . "
            ORDER BY o.created_at DESC
        ");
        $stmt->execute($params);
        $orders = $stmt->fetchAll();
    } else {
        $sql = "SELECT o.*, u.name as user_name, u.email as user_email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.payment_status IN ('completed', 'pending')" . $adminFilter . " ORDER BY o.created_at DESC";
        if ($targetUserId) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$targetUserId]);
        } else {
            $stmt = $pdo->query($sql);
        }
        $orders = $stmt->fetchAll();
    }
} elseif ($role === 'developer') {
    $stmt = $pdo->prepare("SELECT DISTINCT o.*, u.name as user_name, u.email as user_email
            FROM orders o
            JOIN users u ON o.user_id = u.id
            JOIN order_items oi ON o.id = oi.order_id
            JOIN products p ON oi.product_id = p.id AND p.developer_id = ?
            WHERE o.payment_status IN ('completed', 'pending')
            ORDER BY o.created_at DESC");
    $stmt->execute([$userId]);
    $orders = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT DISTINCT o.*, u.name as user_name, u.email as user_email
            FROM orders o
            JOIN users u ON o.user_id = u.id
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE o.user_id = ? AND o.payment_status IN ('completed', 'pending')
            ORDER BY o.created_at DESC");
    $stmt->execute([$userId]);
    $orders = $stmt->fetchAll();
}

$headings = ['Order #', 'Customer', 'Amount', 'Method', 'Transaction ID', 'Status', 'Date'];
$colWidths = [26, 30, 22, 22, 34, 22, 24];
$rows = [];
$totalAmount = 0;
$completedCount = 0;

foreach ($orders as $o) {
    $rows[] = [
        $o['order_number'],
        $o['user_name'],
        formatPrice($o['final_amount']),
        ucfirst($o['payment_method'] ?: 'N/A'),
        $o['transaction_id'] ?: 'N/A',
        ucfirst($o['payment_status']),
        date('M d, Y', strtotime($o['created_at'])),
    ];
    $totalAmount += $o['final_amount'];
    if ($o['payment_status'] === 'completed') $completedCount++;
}

if (empty($rows)) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: text/html; charset=utf-8');
    echo '<p>No orders found to export.</p><a href="javascript:history.back()">Go Back</a>';
    exit;
}

$summary = [
    'Total Orders' => count($orders),
    'Completed' => $completedCount,
    'Total Amount' => formatPrice($totalAmount),
];

try {
    $report = new ReportPdf();
    $pdf = $report->generateReport('Orders Report', 'Complete order history', $headings, $rows, $summary, '', $colWidths);
    $report->output('orders-report-' . date('Ymd') . '.pdf');
} catch (Exception $e) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: text/html; charset=utf-8');
    die('PDF Error: ' . $e->getMessage());
}
