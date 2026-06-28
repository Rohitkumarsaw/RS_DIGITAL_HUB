<?php
require_once __DIR__ . '/../config.php';
requireLogin();
require_once __DIR__ . '/ReportPdf.php';

$userId = $_SESSION['user_id'];
$role = $_SESSION['user_role'] ?? 'user';

if (!in_array($role, ['user', 'developer'])) {
    die('Access denied.');
}

// Only allow if profile is complete
if (!isProfileComplete($userId)) {
    setFlash('error', 'Please complete your profile first before downloading the registration slip.');
    redirect(SITE_URL . '/profile.php?tab=profile');
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    die('User not found.');
}

$user['registration_no'] = ensureRegistrationNo($userId);

try {
    $report = new ReportPdf();
    $pdf = $report->generateRegistrationSlip($user);
    $report->output('registration-slip-' . $user['registration_no'] . '.pdf');
} catch (Exception $e) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: text/html; charset=utf-8');
    die('PDF Error: ' . $e->getMessage());
}
