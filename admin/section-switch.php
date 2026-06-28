<?php
require_once __DIR__ . '/../config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if ($csrfToken !== ($_SESSION['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid security token.');
        redirect(ADMIN_URL . '/index.php');
    }

    $section = $_POST['section'] ?? 'general';
    $validTypes = array_merge(['general'], array_keys(getProductTypes()));

    if (in_array($section, $validTypes)) {
        setCurrentSection($section);
    }
}

// Only allow same-origin redirects
$referrer = $_SERVER['HTTP_REFERER'] ?? '';
$allowedHost = $_SERVER['HTTP_HOST'];
$parsedReferrer = parse_url($referrer);
$referrerHost = $parsedReferrer['host'] ?? '';

if ($referrerHost === $allowedHost && !empty($referrer)) {
    redirect($referrer);
}
redirect(ADMIN_URL . '/index.php');
