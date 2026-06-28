<?php
require_once __DIR__ . '/../config.php';
requireLogin();
require_once __DIR__ . '/ReportPdf.php';

$role = $_SESSION['user_role'] ?? 'user';
if (!in_array($role, ['admin', 'super_admin'])) {
    die('Access denied. Only administrators can view subscription history.');
}

require_once __DIR__ . '/../classes/Subscription.php';
$sub = new Subscription($pdo);
$subscriptions = getSubscriptions();

$headings = ['Developer', 'Plan', 'Amount', 'Status', 'Start Date', 'Expiry'];
$colWidths = [36, 26, 26, 26, 32, 34];
$rows = [];
$totalRevenue = 0;
$activeCount = 0;

foreach ($subscriptions as $s) {
    $price = $sub->getPlanPrice($s['plan_name']);
    $rows[] = [
        $s['developer_name'],
        ucfirst($s['plan_name']),
        formatPrice($price),
        ucfirst($s['status']),
        date('M d, Y', strtotime($s['created_at'])),
        $s['expiry_date'] ? date('M d, Y', strtotime($s['expiry_date'])) : 'N/A',
    ];
    $totalRevenue += $price;
    if ($s['status'] === 'active') $activeCount++;
}

if (empty($rows)) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: text/html; charset=utf-8');
    echo '<p>No subscriptions found to export.</p><a href="javascript:history.back()">Go Back</a>';
    exit;
}

$summary = [
    'Total Subscriptions' => count($subscriptions),
    'Active' => $activeCount,
    'Total Revenue' => formatPrice($totalRevenue),
];

try {
    $report = new ReportPdf();
    $pdf = $report->generateReport('Subscriptions Report', 'Subscription history', $headings, $rows, $summary, '', $colWidths);
    $report->output('subscriptions-report-' . date('Ymd') . '.pdf');
} catch (Exception $e) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: text/html; charset=utf-8');
    die('PDF Error: ' . $e->getMessage());
}

$summary = [
    'Total Subscriptions' => count($subscriptions),
    'Active' => $activeCount,
    'Total Revenue' => formatPrice($totalRevenue),
];

try {
    $report = new ReportPdf();
    $pdf = $report->generateReport('Subscriptions Report', 'Subscription history', $headings, $rows, $summary);
    $report->output('subscriptions-report-' . date('Ymd') . '.pdf');
} catch (Exception $e) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: text/html; charset=utf-8');
    die('PDF Error: ' . $e->getMessage());
}
