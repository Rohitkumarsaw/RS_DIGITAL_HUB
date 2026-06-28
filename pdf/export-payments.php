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
$targetUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$payments = getPayments(
    in_array($role, ['admin', 'super_admin']) ? ($targetUserId ?: null) : $userId,
    $role,
    $currentSection
);

$headings = ['Order #', 'Customer', 'Amount', 'Method', 'Status', 'Transaction ID', 'Date'];
$colWidths = [28, 30, 22, 22, 22, 34, 22];
$rows = [];
$totalAmount = 0;

foreach ($payments as $p) {
    $rows[] = [
        $p['order_number'],
        $p['user_name'],
        formatPrice($p['final_amount']),
        ucfirst($p['payment_method'] ?: 'N/A'),
        ucfirst($p['payment_status']),
        $p['transaction_id'] ?: 'N/A',
        date('M d, Y', strtotime($p['created_at'])),
    ];
    $totalAmount += $p['final_amount'];
}

if (empty($rows)) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: text/html; charset=utf-8');
    echo '<p>No payments found to export.</p><a href="javascript:history.back()">Go Back</a>';
    exit;
}

$summary = [
    'Total Payments' => count($payments),
    'Total Amount' => formatPrice($totalAmount),
];

try {
    $report = new ReportPdf();
    $pdf = $report->generateReport('Payments Report', 'Complete payment history', $headings, $rows, $summary, '', $colWidths);
    $report->output('payments-report-' . date('Ymd') . '.pdf');
} catch (Exception $e) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: text/html; charset=utf-8');
    die('PDF Error: ' . $e->getMessage());
}
