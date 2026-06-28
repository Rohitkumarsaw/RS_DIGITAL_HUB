<?php
require_once __DIR__ . '/../config.php';
requireLogin();
require_once __DIR__ . '/ReportPdf.php';

$userId = $_SESSION['user_id'];
$role = $_SESSION['user_role'] ?? 'user';

if (!in_array($role, ['admin', 'super_admin', 'developer'])) {
    die('Access denied.');
}

$currentSection = getCurrentSection();
$targetUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$productFilterId = $role === 'developer' ? $userId : ($targetUserId ?: null);
$products = getExportProducts($productFilterId, $currentSection);

$headings = ['Product', 'Category', 'Price', 'Sales', 'Status', 'Date'];
$colWidths = [42, 32, 26, 24, 26, 30];
$rows = [];
$totalSales = 0;
$activeCount = 0;

foreach ($products as $p) {
    $rows[] = [
        $p['title'],
        $p['category_name'] ?: 'General',
        formatPrice($p['sale_price'] ?: $p['price']),
        (int)$p['sales_count'],
        ucfirst($p['status']),
        date('M d, Y', strtotime($p['created_at'])),
    ];
    $totalSales += (int)$p['sales_count'];
    if ($p['status'] === 'active') $activeCount++;
}

if (empty($rows)) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: text/html; charset=utf-8');
    echo '<p>No products found to export.</p><a href="javascript:history.back()">Go Back</a>';
    exit;
}

$summary = [
    'Total Products' => count($products),
    'Active' => $activeCount,
    'Total Sales' => $totalSales,
];

try {
    $report = new ReportPdf();
    $pdf = $report->generateReport('Products Report', 'Product inventory report', $headings, $rows, $summary, '', $colWidths);
    $report->output('products-report-' . date('Ymd') . '.pdf');
} catch (Exception $e) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: text/html; charset=utf-8');
    die('PDF Error: ' . $e->getMessage());
}
