<?php
require_once __DIR__ . '/../../config.php';
requireAdminOrDeveloper();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if ($token !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid security token']);
    exit;
}

$msgId = (int)($_POST['id'] ?? 0);
if ($msgId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid message ID']);
    exit;
}

$userId = $_SESSION['user_id'];
$pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ? AND receiver_id = ?")->execute([$msgId, $userId]);

echo json_encode(['success' => true]);
