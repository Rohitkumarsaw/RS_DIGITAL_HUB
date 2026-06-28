<?php
ob_start();

error_reporting(0);
ini_set('display_errors', 0);

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
define('ALLOWED_PROOF_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf']);
define('ID_PROOF_DIR', __DIR__ . '/uploads/id_proofs/');
define('SIGNATURE_IMAGE', __DIR__ . '/uploads/signature.png');
define('ADMIN_ASSETS_DIR', __DIR__ . '/uploads/admin_assets/');

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

date_default_timezone_set('Asia/Kolkata');

require_once __DIR__ . '/includes/functions.php';

// Ensure upload directories exist
if (!is_dir(UPLOAD_DIR)) {
    @mkdir(UPLOAD_DIR, 0755, true);
}
if (!is_dir(SCREENSHOT_DIR)) {
    @mkdir(SCREENSHOT_DIR, 0755, true);
}
if (!is_dir(ID_PROOF_DIR)) {
    @mkdir(ID_PROOF_DIR, 0755, true);
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

// Session login limit (layer on top of existing auth)
require_once __DIR__ . '/includes/session_helper.php';
$scriptName = basename($_SERVER['SCRIPT_NAME'] ?? '');
$skipValidate = $scriptName === 'login.php' || $scriptName === 'logout.php' || $scriptName === 'signup.php' || $scriptName === 'forgot-password.php' || $scriptName === 'reset-password.php';
if (!empty($_SESSION['user_id']) && !$skipValidate) {
    try {
        $sessionValid = validateSession($pdo);
        if (!$sessionValid) {
            $token = $_COOKIE['AUTH_TOKEN'] ?? ($_SESSION['session_token'] ?? '');
            if (!empty($token)) {
                session_unset();
                session_destroy();
                setcookie('AUTH_TOKEN', '', time() - 3600, '/');
                header('Location: ' . SITE_URL . '/login.php?session_expired=1');
                exit;
            }
            // Existing session (pre-feature) — silently create a session record
            createSession($pdo, $_SESSION['user_id']);
        }
    } catch (PDOException $e) {
        // Migrations not run yet — skip session validation silently
    }}

// Auto-create profile columns if missing (lazy migration)
try {
    ensureProfileColumns();
} catch (Exception $e) {
    // Silently ignore
}

// Auto-create product_files table if missing
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS product_files (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        file_path VARCHAR(255) NOT NULL DEFAULT '',
        file_name VARCHAR(255) NOT NULL DEFAULT '',
        file_size VARCHAR(50) NOT NULL DEFAULT '',
        file_url VARCHAR(500) NOT NULL DEFAULT '',
        version VARCHAR(100) NOT NULL DEFAULT '',
        sort_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_product_id (product_id),
        KEY idx_sort (product_id, sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (PDOException $e) {
    // Silently ignore
}

// Auto-create document & asset tables
ensureDocumentTables();

// Ensure admin assets directory exists
if (!is_dir(ADMIN_ASSETS_DIR)) {
    @mkdir(ADMIN_ASSETS_DIR, 0755, true);
}

// Ensure company stamp exists
if (function_exists('ensureCompanyStamp')) {
    ensureCompanyStamp();
}
