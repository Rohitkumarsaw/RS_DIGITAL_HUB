<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$userId = $_SESSION['user_id'];
$role = $_SESSION['user_role'] ?? 'user';

if ($role !== 'developer') {
    die('Access denied.');
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$developer = $stmt->fetch();

if (!$developer) {
    die('Developer not found.');
}

// Get active subscription
require_once __DIR__ . '/../classes/Subscription.php';
$sub = new Subscription($pdo);
$activeSub = $sub->getActiveByDeveloper($userId);
$planName = $activeSub ? $activeSub['plan_name'] : 'N/A';
$joinDate = $activeSub ? date('d M Y', strtotime($activeSub['purchase_date'])) : date('d M Y');

// Get template
$template = getDocumentTemplate('joining_letter');
if (!$template) {
    die('Joining Letter template not found.');
}

$bodyContent = replaceDocumentPlaceholders($template['body_content'], $developer, $planName, $joinDate);

require_once __DIR__ . '/../includes/DocumentPdf.php';
$doc = new DocumentPdf();
try {
    $pdf = $doc->generateJoiningLetter($developer, $bodyContent, $planName, $joinDate);
    $filename = 'joining-letter-' . $developer['registration_no'] . '-' . date('Ymd') . '.pdf';
    $doc->output($filename);
} catch (Exception $e) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: text/html; charset=utf-8');
    die('PDF Error: ' . $e->getMessage());
}
