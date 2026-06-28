<?php
require_once __DIR__ . '/../config.php';
requireLogin();
require_once __DIR__ . '/ReportPdf.php';

$role = $_SESSION['user_role'] ?? 'user';
if (!in_array($role, ['admin', 'super_admin'])) {
    die('Access denied. Only administrators can view user/developer reports.');
}

$users = getExportUsers();

$headings = ['Name', 'Email', 'Role', 'Status', 'Profile', 'Orders', 'Total Spent', 'Joined'];
$colWidths = [28, 34, 20, 18, 20, 16, 22, 22];
$rows = [];
$totalUsers = 0;
$totalOrders = 0;
$totalRevenue = 0;

foreach ($users as $u) {
    $rows[] = [
        $u['name'],
        $u['email'],
        ucfirst(str_replace('_', ' ', $u['role'])),
        ucfirst($u['status']),
        ucfirst($u['profile_status'] ?? 'N/A'),
        (int)$u['order_count'],
        formatPrice($u['total_spent']),
        date('M d, Y', strtotime($u['created_at'])),
    ];
    $totalUsers++;
    $totalOrders += (int)$u['order_count'];
    $totalRevenue += $u['total_spent'];
}

if (empty($rows)) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: text/html; charset=utf-8');
    echo '<p>No users found to export.</p><a href="javascript:history.back()">Go Back</a>';
    exit;
}

$summary = [
    'Total Users' => $totalUsers,
    'Total Orders' => $totalOrders,
    'Total Revenue' => formatPrice($totalRevenue),
];

try {
    $report = new ReportPdf();
    $pdf = $report->generateReport('Users Report', 'Complete user/developer report', $headings, $rows, $summary, '', $colWidths);
    $report->output('users-report-' . date('Ymd') . '.pdf');
} catch (Exception $e) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: text/html; charset=utf-8');
    die('PDF Error: ' . $e->getMessage());
}
