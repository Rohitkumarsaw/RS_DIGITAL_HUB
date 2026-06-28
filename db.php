<?php
// ==========================================================
// RS Digital Hub - Smart Environment Database Configuration
// ==========================================================
// Auto-detects Localhost vs Live Server and loads the
// corresponding credentials. No manual switching needed.
// ==========================================================

// ---- Detect environment ----
$__db_hostname = $_SERVER['HTTP_HOST'] ?? '';
$__db_remote_addr = $_SERVER['REMOTE_ADDR'] ?? '';
$__db_server_addr = $_SERVER['SERVER_ADDR'] ?? '';
$__db_is_local = (
    $__db_hostname === 'localhost' ||
    $__db_hostname === '127.0.0.1' ||
    preg_match('/^(10\.|192\.168\.|172\.(1[6-9]|2\d|3[01])\.)/', $__db_hostname) ||
    preg_match('/^(10\.|192\.168\.|172\.(1[6-9]|2\d|3[01])\.)/', $__db_server_addr) ||
    $__db_remote_addr === '127.0.0.1' ||
    $__db_remote_addr === '::1'
);

if ($__db_is_local) {
    // ======== LOCAL DEVELOPMENT ========
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'ybt_digital');

    error_reporting(E_ALL);
    ini_set('display_errors', 1);

} else {
    // ======== LIVE PRODUCTION (InfinityFree) ========
    define('DB_HOST', 'sql207.infinityfree.com');
    define('DB_USER', 'if0_41983521');
    define('DB_PASS', 'rkrp10111');
    define('DB_NAME', 'if0_41983521_database');

    // Production: never expose errors to users
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// Clean up temporary variables
unset($__db_hostname, $__db_remote_addr, $__db_is_local);
