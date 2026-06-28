<?php
define('MAX_ACTIVE_SESSIONS', 2);

function createSession($pdo, $userId) {
    $token = bin2hex(random_bytes(32));
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sessions WHERE user_id = ? AND is_active = 1");
    $stmt->execute([$userId]);
    $activeCount = (int)$stmt->fetchColumn();

    if ($activeCount >= MAX_ACTIVE_SESSIONS) {
        $pdo->prepare("UPDATE sessions SET is_active = 0 WHERE user_id = ? AND is_active = 1 ORDER BY last_activity ASC LIMIT 1")->execute([$userId]);
    }

    $stmt = $pdo->prepare("INSERT INTO sessions (user_id, session_token, ip, user_agent) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $token, $ip, $ua]);

    setcookie('AUTH_TOKEN', $token, [
        'expires' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    $_SESSION['session_token'] = $token;
}

function validateSession($pdo) {
    $token = $_COOKIE['AUTH_TOKEN'] ?? ($_SESSION['session_token'] ?? '');
    if (empty($token) || empty($_SESSION['user_id'])) {
        return false;
    }
    $stmt = $pdo->prepare("SELECT id FROM sessions WHERE session_token = ? AND user_id = ? AND is_active = 1");
    $stmt->execute([$token, $_SESSION['user_id']]);
    if ($stmt->fetch()) {
        $pdo->prepare("UPDATE sessions SET last_activity = NOW() WHERE session_token = ?")->execute([$token]);
        return true;
    }
    return false;
}

function destroySession($pdo) {
    $token = $_COOKIE['AUTH_TOKEN'] ?? ($_SESSION['session_token'] ?? '');
    if (!empty($token) && !empty($_SESSION['user_id'])) {
        $pdo->prepare("UPDATE sessions SET is_active = 0 WHERE session_token = ? AND user_id = ?")->execute([$token, $_SESSION['user_id']]);
    }
    setcookie('AUTH_TOKEN', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'domain' => '',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    unset($_SESSION['session_token']);
}
