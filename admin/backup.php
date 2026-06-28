<?php
$pageTitle = 'Database Backup';
require_once __DIR__ . '/includes/header.php';

if (!in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    setFlash('error', 'Access denied.');
    redirect(ADMIN_URL . '/index.php');
    exit;
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
}

if (isset($_GET['download']) && $_GET['download'] === '1') {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="backup-' . DB_NAME . '-' . date('Y-m-d-H-i-s') . '.sql"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    echo "-- ============================================\n";
    echo "-- Database: " . DB_NAME . "\n";
    echo "-- Backup Date: " . date('Y-m-d H:i:s') . "\n";
    echo "-- ============================================\n\n";
    echo "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    echo "SET AUTOCOMMIT = 0;\n";
    echo "START TRANSACTION;\n";
    echo "SET time_zone = \"+00:00\";\n\n";

    foreach ($tables as $table) {
        echo "\n-- --------------------------------------------------------\n\n";
        echo "DROP TABLE IF EXISTS `$table`;\n";

        $createStmt = $pdo->query("SHOW CREATE TABLE `$table`");
        $createRow = $createStmt->fetch();
        echo $createRow['Create Table'] . ";\n\n";

        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            $columns = array_keys($rows[0]);
            $colNames = '`' . implode('`, `', $columns) . '`';
            $insertSql = "INSERT INTO `$table` ($colNames) VALUES ";
            $valueRows = [];
            foreach ($rows as $row) {
                $values = [];
                foreach ($row as $val) {
                    $values[] = $val === null ? 'NULL' : $pdo->quote($val);
                }
                $valueRows[] = '(' . implode(', ', $values) . ')';
            }
            echo $insertSql . implode(",\n", $valueRows) . ";\n\n";
        }
    }

    echo "COMMIT;\n";
    echo "-- ============================================\n";
    echo "-- Backup Completed: " . date('Y-m-d H:i:s') . "\n";
    echo "-- ============================================\n";
    exit;
}

$totalSize = 0;
$tables = $pdo->query("SHOW TABLE STATUS FROM `" . DB_NAME . "`")->fetchAll(PDO::FETCH_ASSOC);
$tableCount = count($tables);
foreach ($tables as $t) {
    $totalSize += $t['Data_length'] + $t['Index_length'];
}
?>

<div class="admin-page-header">
    <div>
        <h1>Database Backup</h1>
        <p class="text-muted">Download a complete SQL backup of your database</p>
    </div>
    <a href="?download=1" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Download Backup
    </a>
</div>

<div class="admin-stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));margin-bottom:24px">
    <div class="admin-stat-card">
        <div class="admin-stat-icon" style="background:rgba(99,102,241,0.1);color:#818cf8">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        </div>
        <div class="admin-stat-info">
            <span class="admin-stat-value"><?php echo $tableCount; ?></span>
            <span class="admin-stat-label">Total Tables</span>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon" style="background:rgba(52,211,153,0.1);color:#34d399">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
        </div>
        <div class="admin-stat-info">
            <span class="admin-stat-value"><?php echo formatBytes($totalSize); ?></span>
            <span class="admin-stat-label">Database Size</span>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon" style="background:rgba(251,191,36,0.1);color:#fbbf24">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        </div>
        <div class="admin-stat-info">
            <span class="admin-stat-value">Download</span>
            <span class="admin-stat-label">One-Click Export</span>
        </div>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h5>Download Database Backup</h5>
    </div>
    <div class="admin-card-body">
        <p>Click the button below to download a complete SQL backup of your database. This file can be used to restore your data on any other server.</p>
        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:16px">
            <a href="?download=1" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Download Backup (.sql)
            </a>
        </div>
        <div style="margin-top:20px;padding:16px;background:rgba(251,191,36,0.08);border-radius:12px;border:1px solid rgba(251,191,36,0.15)">
            <strong style="color:#fbbf24">How to Restore on Another Computer:</strong>
            <ol style="margin-top:8px;padding-left:20px;color:var(--text-secondary);font-size:0.85rem;line-height:1.8">
                <li>Install XAMPP on your new computer</li>
                <li>Copy this project folder to <code>C:\xampp\htdocs\</code></li>
                <li>Open phpMyAdmin and create a new database named <code><?php echo DB_NAME; ?></code></li>
                <li>Click the <strong>Import</strong> tab and select your backup <code>.sql</code> file</li>
                <li>Click <strong>Go</strong> — your data will be restored</li>
            </ol>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
