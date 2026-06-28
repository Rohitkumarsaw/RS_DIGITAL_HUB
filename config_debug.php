<?php
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database Configuration
require_once __DIR__ . '/db.php';

// Auto-detect site URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$baseDir = trim($script, '/');
$projectName = explode('/', $baseDir)[0] ?? '';

define('SITE_NAME', 'RS Digital Hub');
define('SITE_URL', $protocol . '://' . $host . ($projectName ? '/' . $projectName : ''));
define('ADMIN_URL', SITE_URL . '/admin');

// Upload directories
define('UPLOAD_DIR', __DIR__ . '/uploads/products/');
define('SCREENSHOT_DIR', __DIR__ . '/uploads/screenshots/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024);
define('ALLOWED_FILE_TYPES', ['zip', 'rar', 'pdf', 'doc', 'docx', 'exe', 'dmg', 'mp4', 'mp3']);
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

date_default_timezone_set('Asia/Kolkata');

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/session_helper.php';

// Ensure upload directories exist
if (!is_dir(UPLOAD_DIR)) {
    @mkdir(UPLOAD_DIR, 0755, true);
}
if (!is_dir(SCREENSHOT_DIR)) {
    @mkdir(SCREENSHOT_DIR, 0755, true);
}

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed. Please check db.php settings.");
}
