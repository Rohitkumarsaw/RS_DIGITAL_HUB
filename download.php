<?php
require_once __DIR__ . '/config.php';

$token = trim($_GET['token'] ?? '');
$fileId = (int)($_GET['file_id'] ?? 0);

if (empty($token)) {
    setFlash('error', 'Invalid download link.');
    redirect(SITE_URL . '/profile.php?tab=downloads');
}

$result = validateDownloadToken($token);

if (!$result['valid']) {
    setFlash('error', $result['message']);
    redirect(SITE_URL . '/profile.php?tab=downloads');
}

$download = $result['download'];
$productId = $download['product_id'];

// If file_id specified, download specific product_file
if ($fileId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM product_files WHERE id = ? AND product_id = ?");
    $stmt->execute([$fileId, $productId]);
    $pf = $stmt->fetch();
    if (!$pf) {
        setFlash('error', 'File not found.');
        redirect(SITE_URL . '/profile.php?tab=downloads');
    }
    incrementDownloadCount($token);

    if (!empty($pf['file_url']) && preg_match('#^https?://#', $pf['file_url'])) {
        ob_clean();
        header('Location: ' . $pf['file_url']);
        exit;
    }

    if (!empty($pf['file_path'])) {
        $baseDir = rtrim(UPLOAD_DIR, '/\\');
        $requestedPath = ltrim($pf['file_path'], '/\\');
        $fullPath = realpath($baseDir . DIRECTORY_SEPARATOR . $requestedPath);
        $filePath = $fullPath ?: $baseDir . DIRECTORY_SEPARATOR . $requestedPath;
        if (strpos($filePath, $baseDir) === 0 && file_exists($filePath)) {
            $dlName = $pf['file_name'] ?: $pf['title'];
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($dlName) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        }
    }
    setFlash('error', 'File not found on server.');
    redirect(SITE_URL . '/profile.php?tab=downloads');
}

// Increment download count
incrementDownloadCount($token);

// Check if external URL
if (!empty($download['file_url'])) {
    if (preg_match('#^https?://#', $download['file_url'])) {
        ob_clean();
        header('Location: ' . $download['file_url']);
        exit;
    }
    setFlash('error', 'Invalid download URL.');
    redirect(SITE_URL . '/profile.php?tab=downloads');
}

// Serve local file — prevent path traversal
$baseDir = rtrim(UPLOAD_DIR, '/\\');
$requestedPath = ltrim($download['file_path'], '/\\');
$fullPath = realpath($baseDir . DIRECTORY_SEPARATOR . $requestedPath);
$filePath = $fullPath ?: $baseDir . DIRECTORY_SEPARATOR . $requestedPath;

// Ensure resolved path is within the upload directory
if (strpos($filePath, $baseDir) !== 0) {
    setFlash('error', 'Invalid file path.');
    redirect(SITE_URL . '/profile.php?tab=downloads');
}

if (!file_exists($filePath)) {
    setFlash('error', 'File not found on server.');
    redirect(SITE_URL . '/profile.php?tab=downloads');
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($download['file_name'] ?: $download['file_path']) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;
