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

$targetUserId = isset($_GET['user_id']) && in_array($role, ['admin', 'super_admin']) ? (int)$_GET['user_id'] : null;
$filterId = $targetUserId ?: $userId;

$stmt = $pdo->prepare("
    SELECT d.*, p.title, p.slug, p.file_name, p.file_size, p.download_limit,
           o.order_number
    FROM downloads d
    LEFT JOIN products p ON d.product_id = p.id
    LEFT JOIN orders o ON d.order_id = o.id
    WHERE d.user_id = ?
    ORDER BY d.created_at DESC
");
$stmt->execute([$filterId]);
$downloads = $stmt->fetchAll();

$headings = ['Product', 'Order', 'Downloads', 'Limit', 'Status', 'Date'];
$colWidths = [40, 30, 22, 22, 32, 34];
$rows = [];

foreach ($downloads as $d) {
    $isExpired = $d['expires_at'] && strtotime($d['expires_at']) < time();
    $isLimitReached = $d['download_count'] >= $d['download_limit'];
    $status = $isExpired ? 'Expired' : ($isLimitReached ? 'Limit Reached' : 'Active');
    $rows[] = [
        $d['title'],
        $d['order_number'],
        (int)$d['download_count'],
        (int)$d['download_limit'],
        $status,
        date('M d, Y', strtotime($d['created_at'])),
    ];
}

if (empty($rows)) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: text/html; charset=utf-8');
    echo '<p>No downloads found to export.</p><a href="javascript:history.back()">Go Back</a>';
    exit;
}

$summary = [
    'Total Downloads' => count($downloads),
    'Active' => count(array_filter($downloads, fn($d) => !($d['expires_at'] && strtotime($d['expires_at']) < time()) && $d['download_count'] < $d['download_limit'])),
];

try {
    $report = new ReportPdf();
    $pdf = $report->generateReport('Downloads Report', 'User download history', $headings, $rows, $summary, '', $colWidths);
    $report->output('downloads-report-' . date('Ymd') . '.pdf');
} catch (Exception $e) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: text/html; charset=utf-8');
    die('PDF Error: ' . $e->getMessage());
}
