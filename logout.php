<?php
require_once __DIR__ . '/config.php';

try {
    destroySession($pdo);
} catch (PDOException $e) {
    // Migrations not run yet — skip
}

// Clear all session variables but keep flash intact
$flash = isset($_SESSION['flash']) ? $_SESSION['flash'] : null;
$_SESSION = [];
session_destroy();

// Restart session to set flash message
session_start();
$_SESSION = [];
if ($flash) {
    $_SESSION['flash'] = $flash;
}

setcookie('remember_token', '', time() - 3600, '/');

setFlash('success', 'You have been logged out successfully.');
redirect(SITE_URL . '/login.php');
