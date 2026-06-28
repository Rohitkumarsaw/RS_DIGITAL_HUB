<?php
require_once __DIR__ . '/../../config.php';
requireAdminOrDeveloper();

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if ($isAjax) {
    header('Content-Type: application/json');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    } else {
        header('Location: ' . ADMIN_URL . '/messages.php');
    }
    exit;
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    if ($isAjax) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    } else {
        setFlash('error', 'Invalid CSRF token. Please try again.');
        header('Location: ' . ADMIN_URL . '/messages.php');
    }
    exit;
}

$senderId = (int)$_SESSION['user_id'];
$receiverId = (int)($_POST['receiver_id'] ?? 0);
$subject = trim($_POST['subject'] ?? '');
$body = trim($_POST['body'] ?? '');
$parentId = !empty($_POST['parent_message_id']) ? (int)$_POST['parent_message_id'] : null;

if ($receiverId <= 0 || empty($subject) || empty($body)) {
    if ($isAjax) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    } else {
        setFlash('error', 'Missing required fields.');
        header('Location: ' . ADMIN_URL . '/messages.php');
    }
    exit;
}

// Validate: admin can message anyone, developer can only message admin
$isAdmin = isAdmin();
if (!$isAdmin) {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$receiverId]);
    $receiverRole = $stmt->fetchColumn();
    if ($receiverRole !== 'admin' && $receiverRole !== 'super_admin') {
        if ($isAjax) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Developers can only message admins.']);
        } else {
            setFlash('error', 'Developers can only message admins.');
            header('Location: ' . ADMIN_URL . '/messages.php');
        }
        exit;
    }
}

// Validate parent message exists if provided
if ($parentId) {
    $stmt = $pdo->prepare("SELECT id FROM messages WHERE id = ? AND (sender_id = ? OR receiver_id = ?)");
    $stmt->execute([$parentId, $senderId, $senderId]);
    if (!$stmt->fetch()) {
        $parentId = null;
    }
}

$stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, subject, body, parent_message_id) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$senderId, $receiverId, $subject, $body, $parentId]);
$msgId = $pdo->lastInsertId();

if ($isAjax) {
    echo json_encode(['success' => true, 'message_id' => $msgId, 'redirect' => ADMIN_URL . '/messages.php?view=sent']);
} else {
    setFlash('success', 'Message sent successfully.');
    header('Location: ' . ADMIN_URL . '/messages.php?view=sent');
}
