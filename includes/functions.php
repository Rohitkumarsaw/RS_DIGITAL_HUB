<?php
// RS Digital Hub - Helper Functions

// Sanitize Input
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Sanitize URL
function sanitizeUrl($url) {
    $url = trim($url);
    return filter_var($url, FILTER_SANITIZE_URL);
}

// Generate Slug
function generateSlug($string) {
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

// Generate Random Token
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Generate Order Number
function generateOrderNumber() {
    return 'RS-' . date('Ymd') . '-' . strtoupper(substr(generateToken(4), 0, 8));
}

// Format Price
function formatPrice($price) {
    static $symbol = null;
    if ($symbol === null) {
        global $pdo;
        $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'currency_symbol'");
        $symbol = $stmt->fetchColumn() ?: '₹';
    }
    return $symbol . number_format($price, 2, '.', ',');
}

// Get Setting Value
function getSetting($key, $default = '') {
    global $pdo;
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ? AND setting_value != ''");
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    return $value !== false ? $value : $default;
}

// Update Setting
function updateSetting($key, $value) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->execute([$key, $value]);
}

// Get all plan prices from settings (with hardcoded fallbacks)
function getPlanPrices() {
    return [
        'starter' => (int)getSetting('plan_price_starter', 999),
        'business' => (int)getSetting('plan_price_business', 2499),
        'professional' => (int)getSetting('plan_price_professional', 4999),
    ];
}

// Get plan duration in days from settings (with hardcoded fallback)
function getPlanDuration($planName) {
    $durations = [
        'starter' => (int)getSetting('plan_duration_starter', 30),
        'business' => (int)getSetting('plan_duration_business', 30),
        'professional' => (int)getSetting('plan_duration_professional', 30),
    ];
    return $durations[$planName] ?? 30;
}

// Redirect
function redirect($url) {
    header("Location: " . $url);
    exit;
}

// Set Flash Message
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

// Get Flash Message
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Check if User is Logged In
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if Admin is Logged In
function isAdmin() {
    return isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'super_admin']);
}

// Require Login
function requireLogin() {
    if (!isLoggedIn()) {
        setFlash('error', 'Please login to access this page.');
        redirect(SITE_URL . '/login.php');
    }
}

// Require Admin
function requireAdmin() {
    if (!isAdmin()) {
        setFlash('error', 'Access denied. Admin privileges required.');
        redirect(SITE_URL . '/login.php');
    }
}

// Check if Developer
function isDeveloper() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'developer';
}

// Check if Admin or Developer
function isAdminOrDeveloper() {
    return isAdmin() || isDeveloper();
}

// Require Admin or Developer
function requireAdminOrDeveloper() {
    if (!isAdminOrDeveloper()) {
        setFlash('error', 'Access denied. You do not have permission.');
        redirect(SITE_URL . '/login.php');
    }
}

// Require strict Admin only (Developer cannot access)
function requireStrictAdmin() {
    if (!isAdmin()) {
        setFlash('error', 'Access denied. Only administrators can access this page.');
        redirect(SITE_URL . '/login.php');
    }
}

// Get Current User
function getCurrentUser() {
    global $pdo;
    if (!isLoggedIn()) return null;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Clear Cart (kept for backward compatibility)
function clearCart($userId) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
    return $stmt->execute([$userId]);
}

// Create Razorpay Payment Link via API
function createRazorpayPaymentLink($amount, $description, $orderNumber, $customer = [], $customKeys = []) {
    $keyId = $customKeys['key_id'] ?? getSetting('razorpay_key_id');
    $keySecret = $customKeys['key_secret'] ?? getSetting('razorpay_key_secret');

    if (empty($keyId) || empty($keySecret)) {
        return ['success' => false, 'error' => 'Razorpay API keys not configured'];
    }

    $url = 'https://api.razorpay.com/v1/payment_links';
    $callbackUrl = SITE_URL . '/payment-callback.php?order=' . urlencode($orderNumber);

    $data = [
        'amount' => (int)round($amount * 100),
        'currency' => getSetting('currency', 'INR'),
        'accept_partial' => false,
        'description' => $description,
        'callback_url' => $callbackUrl,
        'callback_method' => 'get',
        'customer' => [
            'name' => $customer['name'] ?? 'Customer',
            'email' => $customer['email'] ?? '',
            'contact' => $customer['contact'] ?? '',
        ],
        'notify' => ['sms' => false, 'email' => false],
        'reminder_enable' => false,
        'notes' => ['order_number' => $orderNumber],
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_USERPWD => $keyId . ':' . $keySecret,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['success' => false, 'error' => 'cURL error: ' . $error];
    }

    $result = json_decode($response, true);

    if ($httpCode >= 200 && $httpCode < 300 && isset($result['short_url'])) {
        return ['success' => true, 'url' => $result['short_url'], 'id' => $result['id']];
    }

    $errMsg = $result['error']['description'] ?? 'Unknown Razorpay error (HTTP ' . $httpCode . ')';
    if ($httpCode == 401) {
        $errMsg = 'Invalid Razorpay API keys. Please check your Razorpay settings.';
    }
    return ['success' => false, 'error' => $errMsg];
}

// Validate Coupon
function validateCoupon($code) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND status = 'active'");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch();

    if (!$coupon) return ['valid' => false, 'message' => 'Invalid coupon code.'];
    if (!empty($coupon['expires_at']) && strtotime($coupon['expires_at']) < time()) {
        return ['valid' => false, 'message' => 'Coupon has expired.'];
    }
    if ($coupon['usage_limit'] && $coupon['used_count'] >= $coupon['usage_limit']) {
        return ['valid' => false, 'message' => 'Coupon usage limit reached.'];
    }

    return ['valid' => true, 'coupon' => $coupon];
}

// Calculate Discount
function calculateDiscount($coupon, $total) {
    if (($coupon['min_order_amount'] ?? 0) > $total) {
        return ['discount' => 0, 'message' => 'Minimum order amount not met.'];
    }

    if ($coupon['type'] === 'percentage') {
        $discount = ($total * $coupon['value']) / 100;
        if ($coupon['max_discount'] && $discount > $coupon['max_discount']) {
            $discount = $coupon['max_discount'];
        }
    } else {
        $discount = $coupon['value'];
    }

    return ['discount' => min($discount, $total), 'message' => ''];
}

// Create Download Token
function createDownloadToken($userId, $productId, $orderId, $fileId = null) {
    global $pdo;
    $token = generateToken(16);
    $expiryHours = (int)getSetting('download_expiry_hours', 48);
    $expiresAt = $expiryHours > 0 ? date('Y-m-d H:i:s', strtotime("+$expiryHours hours")) : null;

    $stmt = $pdo->prepare("
        INSERT INTO downloads (user_id, product_id, order_id, download_token, expires_at, product_file_id)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $productId, $orderId, $token, $expiresAt, $fileId]);

    return $token;
}

// Validate Download Token
function validateDownloadToken($token) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT d.*, p.file_path, p.file_name, p.file_url, p.download_limit,
               pf.download_count as file_download_count, pf.download_limit as file_download_limit,
               pf.download_expiry_hours as file_download_expiry, pf.is_paid as file_is_paid,
               pf.title as file_title, pf.version as file_version
        FROM downloads d
        JOIN products p ON d.product_id = p.id
        LEFT JOIN product_files pf ON d.product_file_id = pf.id
        WHERE d.download_token = ?
    ");
    $stmt->execute([$token]);
    $download = $stmt->fetch();

    if (!$download) return ['valid' => false, 'message' => 'Invalid download link.'];
    if ($download['expires_at'] && strtotime($download['expires_at']) < time()) {
        return ['valid' => false, 'message' => 'Download link has expired.'];
    }

    if ($download['product_file_id']) {
        $dlLimit = $download['file_download_limit'];
        $dlCount = $download['file_download_count'];
        $dlExpiry = $download['file_download_expiry'];
        if ($dlLimit > 0 && $dlCount >= $dlLimit) {
            return ['valid' => false, 'message' => 'Download limit reached for this file.'];
        }
        if ($dlExpiry > 0) {
            $createdAt = $download['created_at'] ?? null;
            if ($createdAt && strtotime("+{$dlExpiry} hours", strtotime($createdAt)) < time()) {
                return ['valid' => false, 'message' => 'Download period has expired for this file.'];
            }
        }
    } else {
        if ($download['download_limit'] > 0 && $download['download_count'] >= $download['download_limit']) {
            return ['valid' => false, 'message' => 'Download limit reached.'];
        }
    }

    return ['valid' => true, 'download' => $download];
}

// Increment Download Count
function incrementDownloadCount($token) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE downloads SET download_count = download_count + 1 WHERE download_token = ?");
    $stmt->execute([$token]);
}

// Get Product by ID
function getProduct($id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name, c.slug as category_slug
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Get Product Files (extra files/versions)
function getProductFiles($productId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM product_files WHERE product_id = ? ORDER BY sort_order ASC, id ASC");
    $stmt->execute([$productId]);
    return $stmt->fetchAll();
}

// Get Product by Slug
function getProductBySlug($slug) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name, c.slug as category_slug,
               u.store_name as developer_store, u.name as developer_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN users u ON p.developer_id = u.id
        WHERE p.slug = ? AND (p.developer_id IS NULL OR EXISTS (SELECT 1 FROM subscriptions s WHERE s.developer_id = p.developer_id AND s.status = 'active' AND s.expiry_date > NOW()))
    ");
    $stmt->execute([$slug]);
    return $stmt->fetch();
}

// Get active subscription subquery for filtering developer products
function getActiveSubFilter($alias = 'p') {
    return " AND ($alias.developer_id IS NULL OR EXISTS (SELECT 1 FROM subscriptions s WHERE s.developer_id = $alias.developer_id AND s.status = 'active' AND s.expiry_date > NOW()))";
}

// Get All Active Products
function getProducts($filters = []) {
    global $pdo;
    $sql = "
        SELECT p.*, c.name as category_name, c.slug as category_slug,
               u.store_name as developer_store, u.name as developer_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN users u ON p.developer_id = u.id
        WHERE p.status = 'active'
    ";
    $sql .= getActiveSubFilter();
    $params = [];

    if (!empty($filters['search'])) {
        $sql .= " AND (p.title LIKE ? OR p.description LIKE ?)";
        $params[] = '%' . $filters['search'] . '%';
        $params[] = '%' . $filters['search'] . '%';
    }

    if (!empty($filters['category'])) {
        $sql .= " AND p.category_id = ?";
        $params[] = $filters['category'];
    }

    if (!empty($filters['type'])) {
        $sql .= " AND p.product_type = ?";
        $params[] = $filters['type'];
    }

    switch ($filters['sort'] ?? 'newest') {
        case 'price_low':
            $sql .= " ORDER BY COALESCE(p.sale_price, p.price) ASC";
            break;
        case 'price_high':
            $sql .= " ORDER BY COALESCE(p.sale_price, p.price) DESC";
            break;
        case 'popular':
            $sql .= " ORDER BY p.sales_count DESC";
            break;
        default:
            $sql .= " ORDER BY p.created_at DESC";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Get All Categories
function getCategories() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    return $stmt->fetchAll();
}

// Get Related Products
function getRelatedProducts($productId, $categoryId, $limit = 4) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name,
               u.store_name as developer_store, u.name as developer_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN users u ON p.developer_id = u.id
        WHERE p.id != ? AND p.category_id = ? AND p.status = 'active'
        AND (p.developer_id IS NULL OR EXISTS (SELECT 1 FROM subscriptions s WHERE s.developer_id = p.developer_id AND s.status = 'active' AND s.expiry_date > NOW()))
        ORDER BY RAND()
        LIMIT ?
    ");
    $stmt->execute([$productId, $categoryId, $limit]);
    return $stmt->fetchAll();
}

// Get User Orders
function getUserOrders($userId) {
    global $pdo;
    $pdo->exec("SET SESSION group_concat_max_len = 100000");
    $stmt = $pdo->prepare("
        SELECT o.*, COALESCE(GROUP_CONCAT(DISTINCT p.title SEPARATOR ', '), '') as product_titles
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE o.user_id = ? AND o.payment_status IN ('completed', 'pending')
        GROUP BY o.id
        ORDER BY FIELD(o.payment_status, 'completed', 'pending'), o.created_at DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

// Get Order Details
function getOrder($orderId, $userId = null) {
    global $pdo;
    $sql = "
        SELECT o.*, COALESCE(GROUP_CONCAT(p.title SEPARATOR ', '), '') as product_titles
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE o.id = ?
    ";
    $params = [$orderId];

    if ($userId) {
        $sql .= " AND o.user_id = ?";
        $params[] = $userId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

// Get Order Items
function getOrderItems($orderId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT oi.*, p.title, p.slug, p.file_name, p.file_size
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$orderId]);
    return $stmt->fetchAll();
}

// Get User Downloads
function getUserDownloads($userId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT d.*, p.title, p.slug, p.file_name, p.file_size, p.download_limit,
               o.order_number
        FROM downloads d
        LEFT JOIN products p ON d.product_id = p.id
        LEFT JOIN orders o ON d.order_id = o.id
        WHERE d.user_id = ?
        ORDER BY d.created_at DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

// Get Active Testimonials
function getTestimonials($limit = 6) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM testimonials WHERE status = 'active' ORDER BY sort_order ASC, created_at DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

// Get Active FAQs
function getFaqs() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM faqs WHERE status = 'active' ORDER BY sort_order ASC");
    return $stmt->fetchAll();
}

// Create Ticket
function createTicket($userId, $subject, $message, $priority = 'medium') {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO tickets (user_id, subject, message, priority) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$userId, sanitize($subject), sanitize($message), $priority])) {
        return $pdo->lastInsertId();
    }
    return false;
}

// Get User Tickets
function getUserTickets($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM tickets WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

// Get Ticket Replies
function getTicketReplies($ticketId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT tr.*, u.name, u.role
        FROM ticket_replies tr
        JOIN users u ON tr.user_id = u.id
        WHERE tr.ticket_id = ?
        ORDER BY tr.created_at ASC
    ");
    $stmt->execute([$ticketId]);
    return $stmt->fetchAll();
}

// Add Ticket Reply
function addTicketReply($ticketId, $userId, $message, $isAdmin = false) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO ticket_replies (ticket_id, user_id, message, is_admin) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$ticketId, $userId, sanitize($message), $isAdmin ? 1 : 0]);
}

// Format Date
function formatDate($date, $format = 'M d, Y') {
    $ts = strtotime($date);
    return $ts ? date($format, $ts) : 'Invalid date';
}

// Format Date Time
function formatDateTime($date) {
    $ts = strtotime($date);
    return $ts ? date('M d, Y h:i A', $ts) : 'Invalid date';
}

// Time Ago
function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}

// Get File Size Human Readable
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}

// Generate Premium Invoice HTML
function generateInvoice($order) {
    global $pdo;
    $items = getOrderItems($order['id']);
    $siteName = getSetting('site_name', SITE_NAME);
    $currency = getSetting('currency_symbol', '$');
    $taxType = getSetting('tax_type', 'GSTIN');
    $taxPercent = getSetting('tax_percentage', 0);

    // Get customer info
    $stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
    $stmt->execute([$order['user_id']]);
    $customer = $stmt->fetch();

    $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - ' . htmlspecialchars($order['order_number']) . '</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #f8fafc;
            color: #1e293b;
            line-height: 1.6;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .invoice-container {
            max-width: 800px;
            margin: 2rem auto;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        /* Header */
        .invoice-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            color: #ffffff;
            padding: 2.5rem 3rem;
            position: relative;
            overflow: hidden;
        }
        .invoice-header::before {
            content: "";
            position: absolute;
            top: -50%;
            right: -20%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(99,102,241,0.15) 0%, transparent 70%);
            border-radius: 50%;
        }
        .invoice-header::after {
            content: "";
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(16,185,129,0.1) 0%, transparent 70%);
            border-radius: 50%;
        }
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            position: relative;
            z-index: 1;
        }
        .brand h1 {
            font-size: 1.75rem;
            font-weight: 800;
            background: linear-gradient(135deg, #818cf8 0%, #34d399 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.25rem;
        }
        .brand p {
            font-size: 0.85rem;
            color: #94a3b8;
        }
        .invoice-badge {
            text-align: right;
        }
        .invoice-badge h2 {
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: 2px;
            color: #818cf8;
        }
        .invoice-badge .order-num {
            font-size: 0.9rem;
            color: #cbd5e1;
            margin-top: 0.25rem;
        }
        /* Body */
        .invoice-body {
            padding: 2.5rem 3rem;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2.5rem;
        }
        .info-box h4 {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #64748b;
            margin-bottom: 0.75rem;
            font-weight: 600;
        }
        .info-box p {
            font-size: 0.95rem;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }
        .info-box p strong {
            color: #475569;
        }
        .status-badge {
            display: inline-block;
            padding: 0.35rem 0.85rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-completed {
            background: rgba(16,185,129,0.12);
            color: #059669;
            border: 1px solid rgba(16,185,129,0.25);
        }
        .status-pending {
            background: rgba(245,158,11,0.12);
            color: #d97706;
            border: 1px solid rgba(245,158,11,0.25);
        }
        .status-failed {
            background: rgba(239,68,68,0.12);
            color: #dc2626;
            border: 1px solid rgba(239,68,68,0.25);
        }
        /* Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }
        .items-table thead th {
            background: #f8fafc;
            padding: 0.85rem 1rem;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            font-weight: 700;
            border-bottom: 2px solid #e2e8f0;
        }
        .items-table thead th:first-child {
            border-radius: 8px 0 0 0;
        }
        .items-table thead th:last-child {
            border-radius: 0 8px 0 0;
            text-align: right;
        }
        .items-table tbody td {
            padding: 1rem;
            font-size: 0.9rem;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
        }
        .items-table tbody td:last-child {
            text-align: right;
            font-weight: 600;
            color: #1e293b;
        }
        .items-table tbody tr:last-child td {
            border-bottom: none;
        }
        .items-table tbody tr:hover {
            background: #fafbfc;
        }
        .item-name {
            font-weight: 600;
            color: #1e293b;
        }
        .item-type {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: 0.15rem;
        }
        /* Summary */
        .summary-section {
            display: flex;
            justify-content: flex-end;
        }
        .summary-box {
            width: 280px;
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.25rem;
            border: 1px solid #e2e8f0;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            font-size: 0.9rem;
            color: #64748b;
        }
        .summary-row.discount {
            color: #059669;
        }
        .summary-row.total {
            border-top: 2px solid #e2e8f0;
            margin-top: 0.5rem;
            padding-top: 0.75rem;
            font-size: 1.15rem;
            font-weight: 800;
            color: #1e293b;
        }
        .summary-row.total span:last-child {
            color: #4f46e5;
        }
        /* Footer */
        .invoice-footer {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 1.5rem 3rem;
            text-align: center;
        }
        .invoice-footer p {
            font-size: 0.8rem;
            color: #94a3b8;
            margin-bottom: 0.25rem;
        }
        .invoice-footer .thank-you {
            font-size: 1rem;
            font-weight: 700;
            color: #475569;
            margin-bottom: 0.5rem;
        }
        .powered-by {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
        }
        .powered-by span {
            font-size: 0.7rem;
            color: #cbd5e1;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        /* Print Styles */
        @media print {
            body { background: #fff; }
            .invoice-container {
                box-shadow: none;
                margin: 0;
                border-radius: 0;
            }
            .invoice-header {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        @media (max-width: 600px) {
            .invoice-header, .invoice-body, .invoice-footer { padding: 1.5rem; }
            .header-top { flex-direction: column; gap: 1rem; }
            .invoice-badge { text-align: left; }
            .info-grid { grid-template-columns: 1fr; gap: 1rem; }
            .summary-box { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div class="header-top">
                <div class="brand">
                    <h1>' . htmlspecialchars($siteName) . '</h1>
                    <p>Premium Digital Products Marketplace</p>
                </div>
                <div class="invoice-badge">
                    <h2>INVOICE</h2>
                    <div class="order-num">' . htmlspecialchars($order['order_number']) . '</div>
                </div>
            </div>
        </div>

        <!-- Body -->
        <div class="invoice-body">
            <!-- Info Grid -->
            <div class="info-grid">
                <div class="info-box">
                    <h4>Bill To</h4>
                    <p><strong>' . htmlspecialchars($customer['name'] ?? 'Customer') . '</strong></p>
                    <p>' . htmlspecialchars($customer['email'] ?? '') . '</p>
                </div>
                <div class="info-box" style="text-align:right">
                    <h4>Invoice Details</h4>
                    <p><strong>Date:</strong> ' . date('M d, Y', strtotime($order['created_at'])) . '</p>
                    <p><strong>Payment:</strong> ' . ucfirst(htmlspecialchars($order['payment_method'])) . '</p>
                    <p><strong>Status:</strong> <span class="status-badge status-' . $order['payment_status'] . '">' . ucfirst(htmlspecialchars($order['payment_status'])) . '</span></p>
                </div>
            </div>

            <!-- Items Table -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width:50%">Product</th>
                        <th style="width:25%;text-align:center">Type</th>
                        <th style="width:25%;text-align:right">Amount</th>
                    </tr>
                </thead>
                <tbody>';

    foreach ($items as $item) {
        $ext = pathinfo($item['file_name'] ?? '', PATHINFO_EXTENSION);
        $typeLabel = strtoupper($ext ?: 'DIGITAL');
        $html .= '
                    <tr>
                        <td>
                            <div class="item-name">' . htmlspecialchars($item['title']) . '</div>
                            <div class="item-type">Digital Download</div>
                        </td>
                        <td style="text-align:center"><span style="background:#f1f5f9;padding:0.25rem 0.6rem;border-radius:4px;font-size:0.75rem;font-weight:600;color:#64748b">' . htmlspecialchars($typeLabel) . '</span></td>
                        <td>' . formatPrice($item['price']) . '</td>
                    </tr>';
    }

    $html .= '
                </tbody>
            </table>

            <!-- Summary -->
            <div class="summary-section">
                <div class="summary-box">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>' . formatPrice($order['total_amount']) . '</span>
                    </div>';

    if ($order['discount_amount'] > 0) {
        $html .= '
                    <div class="summary-row discount">
                        <span>Discount' . ($order['coupon_code'] ? ' (' . htmlspecialchars($order['coupon_code']) . ')' : '') . '</span>
                        <span>-' . formatPrice($order['discount_amount']) . '</span>
                    </div>';
    }

    if ($order['tax_amount'] > 0) {
        $html .= '
                    <div class="summary-row">
                        <span>' . htmlspecialchars($taxType) . ' (' . $taxPercent . '%)</span>
                        <span>' . formatPrice($order['tax_amount']) . '</span>
                    </div>';
    }

    $html .= '
                    <div class="summary-row total">
                        <span>Total Paid</span>
                        <span>' . formatPrice($order['final_amount']) . '</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="invoice-footer">
            <p class="thank-you">Thank you for your purchase!</p>
            <p>For support, contact: support@' . strtolower(str_replace(' ', '', $siteName)) . '.com</p>
            <div class="powered-by">
                <span>Powered by ' . htmlspecialchars($siteName) . '</span>
            </div>
        </div>
    </div>
</body>
</html>';

    return $html;
}

function getFooterSocialIcon($platform) {
    $icons = [
        'facebook' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
        'instagram' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>',
        'twitter' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
        'youtube' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>',
        'linkedin' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
        'telegram' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>',
        'whatsapp' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>',
        'github' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 2.41 1.005.695-.19 1.44-.285 2.19-.285.75 0 1.495.095 2.19.285 1.405-1.327 2.41-1.005 2.41-1.005.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>',
        'pinterest' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.668.967-2.914 2.171-2.914 1.023 0 1.518.769 1.518 1.69 0 1.029-.655 2.568-.994 3.995-.283 1.194.599 2.169 1.777 2.169 2.133 0 3.772-2.249 3.772-5.495 0-2.873-2.064-4.882-5.012-4.882-3.414 0-5.418 2.561-5.418 5.207 0 1.031.397 2.138.893 2.738a.36.36 0 01.083.345l-.333 1.36c-.053.22-.174.267-.402.161-1.499-.698-2.436-2.889-2.436-4.649 0-3.785 2.75-7.262 7.929-7.262 4.163 0 7.398 2.967 7.398 6.931 0 4.136-2.607 7.464-6.227 7.464-1.216 0-2.359-.631-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24 12.017 24c6.624 0 11.99-5.367 11.99-11.988C24.007 5.367 18.641.001 12.017.001z"/></svg>',
        'threads' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12.186 24h-.007c-3.581-.024-6.334-1.205-8.184-3.509C2.35 18.44 1.5 15.586 1.472 12.01v-.017c.03-3.579.879-6.43 2.525-8.482C5.845 1.205 8.6 0 12.186 0h.007c2.786.02 5.086.968 6.826 2.816 1.677 1.785 2.576 4.29 2.672 7.444v.01c-.097 3.155-.996 5.66-2.672 7.444-1.74 1.848-4.04 2.796-6.826 2.816l-.007-.01z"/></svg>',
    ];
    return $icons[$platform] ?? '';
}

// Get Available Product Types
function getProductTypes() {
    return [
        'mobile_apps' => 'Mobile Apps',
        'pc_software' => 'PC Software',
        'mobile_games' => 'Mobile Games',
        'pc_games' => 'PC Games',
        'mac_software' => 'Mac Software',
        'mac_apps' => 'Mac Apps',
        'documents' => 'Documents',
        'resources' => 'Resources',
        'movies' => 'Movies',
        'courses' => 'Courses',
        'cricket_highlights' => 'Cricket Highlights',
        'web_series' => 'Web Series',
        'books' => 'Books',
        'premium_apps' => 'Premium Apps',
        'crack_software' => 'Crack Software PC/Mac',
        'modded_games' => 'Modded Games',
    ];
}

// Get Product Type Label
function getProductTypeLabel($type) {
    $types = getProductTypes();
    return $types[$type] ?? 'General';
}

// Get User Store
function getUserStore($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND store_name IS NOT NULL");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

// Get Developer Products
function getDeveloperProducts($developerId, $productType = null, $filters = [], $activeOnly = false) {
    global $pdo;
    $sql = "
        SELECT p.*, c.name as category_name, c.slug as category_slug
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.developer_id = ?
    ";
    $params = [$developerId];

    if ($activeOnly) {
        $sql .= " AND p.status = 'active'";
    }

    if ($productType) {
        $sql .= " AND p.product_type = ?";
        $params[] = $productType;
    }

    if (!empty($filters['search'])) {
        $sql .= " AND (p.title LIKE ? OR p.description LIKE ?)";
        $params[] = '%' . $filters['search'] . '%';
        $params[] = '%' . $filters['search'] . '%';
    }

    $sql .= " ORDER BY p.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Get Products by Type (for admin)
function getProductsByType($productType, $filters = []) {
    global $pdo;
    $sql = "
        SELECT p.*, c.name as category_name, c.slug as category_slug,
               u.store_name as developer_store, u.name as developer_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN users u ON p.developer_id = u.id
        WHERE p.product_type = ?
    ";
    $params = [$productType];

    if (!empty($filters['search'])) {
        $sql .= " AND (p.title LIKE ? OR p.description LIKE ?)";
        $params[] = '%' . $filters['search'] . '%';
        $params[] = '%' . $filters['search'] . '%';
    }

    if (isset($filters['status'])) {
        $sql .= " AND p.status = ?";
        $params[] = $filters['status'];
    }

    $sql .= " ORDER BY p.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Check if user can manage product
function canManageProduct($productId) {
    global $pdo;
    $user = getCurrentUser();
    if (!$user) return false;
    if (isAdmin()) return true;
    if (isDeveloper()) {
        $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND developer_id = ?");
        $stmt->execute([$productId, $user['id']]);
        return $stmt->fetch() ? true : false;
    }
    return false;
}

// Get Content by Product Type
function getContentByType($table, $productType = 'general') {
    global $pdo;
    $allowed = ['about_us', 'hero_banners', 'testimonials', 'faqs'];
    $table = in_array($table, $allowed) ? $table : 'about_us';
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE product_type = ? ORDER BY id ASC LIMIT 1");
    $stmt->execute([$productType]);
    return $stmt->fetch();
}

// Get All Content by Product Type
function getAllContentByType($table, $productType = 'general') {
    global $pdo;
    $allowed = ['about_us', 'hero_banners', 'testimonials', 'faqs'];
    $table = in_array($table, $allowed) ? $table : 'about_us';
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE product_type = ? ORDER BY id ASC");
    $stmt->execute([$productType]);
    return $stmt->fetchAll();
}

// Sanitize HTML content — strips dangerous tags but keeps safe formatting
function sanitizeHtml($html) {
    $allowedTags = '<p><br><b><strong><i><em><u><s><h1><h2><h3><h4><h5><h6><ul><ol><li><blockquote><pre><code><hr><table><thead><tbody><tr><th><td><caption><colgroup><col><span><div><a><img><figure><figcaption><video><source><iframe>';
    return strip_tags($html, $allowedTags);
}

// Get Current Section
function getCurrentSection() {
    return $_SESSION['current_section'] ?? 'general';
}

// Set Current Section
function setCurrentSection($section) {
    $_SESSION['current_section'] = $section;
}

// Generate Registration Number
function generateRegistrationNo() {
    $prefix = 'REG-' . date('Ymd') . '-';
    $suffix = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
    return $prefix . $suffix;
}

// Ensure profile columns exist (auto-migration)
function ensureProfileColumns() {
    global $pdo;
    $columns = [
        'profile_status' => "VARCHAR(20) DEFAULT 'incomplete'",
        'father_name' => "VARCHAR(255) DEFAULT NULL",
        'mobile' => "VARCHAR(20) DEFAULT NULL",
        'address' => "TEXT DEFAULT NULL",
        'dob' => "DATE DEFAULT NULL",
        'gender' => "VARCHAR(10) DEFAULT NULL",
        'nationality' => "VARCHAR(100) DEFAULT NULL",
        'occupation' => "VARCHAR(255) DEFAULT NULL",
        'id_proof_file' => "VARCHAR(255) DEFAULT NULL",
        'registration_no' => "VARCHAR(50) DEFAULT NULL",
        'profile_completed_at' => "DATETIME DEFAULT NULL",
    ];
    try {
        $existing = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($columns as $col => $def) {
            if (!in_array($col, $existing)) {
                $pdo->exec("ALTER TABLE users ADD COLUMN $col $def");
            }
        }
    } catch (PDOException $e) {
        // Silently ignore if ALTER fails
    }
}

// Check if profile is complete
function isProfileComplete($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT profile_status FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn() === 'complete';
    } catch (PDOException $e) {
        return false;
    }
}

// Generate registration number if missing
function ensureRegistrationNo($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT registration_no FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $regNo = $stmt->fetchColumn();
        if (empty($regNo)) {
            $regNo = generateRegistrationNo();
            $pdo->prepare("UPDATE users SET registration_no = ? WHERE id = ?")->execute([$regNo, $userId]);
        }
        return $regNo;
    } catch (PDOException $e) {
        return 'REG-PENDING';
    }
}

// Get Signature Image Path
function getSignaturePath() {
    $path = defined('SIGNATURE_IMAGE') ? SIGNATURE_IMAGE : __DIR__ . '/../uploads/signature.png';
    return file_exists($path) ? $path : null;
}

// Get Payment History
function getPayments($userId = null, $role = null, $section = null) {
    global $pdo;
    $sectionFilter = $section && $section !== 'general' ? " AND p.product_type = ?" : "";
    $userFilter = $userId ? " AND o.user_id = ?" : "";
    if ($role === 'admin' || $role === 'super_admin') {
        $params = [];
        $sql = "SELECT DISTINCT o.*, u.name as user_name, u.email as user_email
                FROM orders o
                JOIN users u ON o.user_id = u.id";
        if ($section && $section !== 'general') {
            $sql .= " LEFT JOIN order_items oi ON o.id = oi.order_id LEFT JOIN products p ON oi.product_id = p.id";
        }
        $sql .= " WHERE o.payment_status IN ('completed', 'pending')" . $userFilter;
        if ($section && $section !== 'general') {
            $sql .= $sectionFilter;
        }
        if ($userId) $params[] = $userId;
        if ($section && $section !== 'general') $params[] = $section;
        $sql .= " ORDER BY o.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    if ($userId) {
        if ($role === 'developer') {
            $stmt = $pdo->prepare("
                SELECT DISTINCT o.*, u.name as user_name, u.email as user_email
                FROM orders o
                JOIN users u ON o.user_id = u.id
                JOIN order_items oi ON o.id = oi.order_id
                JOIN products p ON oi.product_id = p.id AND p.developer_id = ?
                ORDER BY o.created_at DESC");
            $stmt->execute([$userId]);
        } else {
            $stmt = $pdo->prepare("
                SELECT DISTINCT o.*, u.name as user_name, u.email as user_email
                FROM orders o
                JOIN users u ON o.user_id = u.id
                LEFT JOIN order_items oi ON o.id = oi.order_id
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE o.user_id = ? AND o.payment_status IN ('completed', 'pending')
                ORDER BY o.created_at DESC");
            $stmt->execute([$userId]);
        }
        return $stmt->fetchAll();
    }
    return [];
}

// Get Subscriptions (admin only)
function getSubscriptions() {
    global $pdo;
    return $pdo->query("
        SELECT s.*, u.name as developer_name, u.email as developer_email, u.store_name
        FROM subscriptions s
        JOIN users u ON s.developer_id = u.id
        ORDER BY s.created_at DESC
    ")->fetchAll();
}

// Get products for export
function getExportProducts($developerId = null, $section = null) {
    global $pdo;
    $sectionFilter = $section && $section !== 'general' ? " AND p.product_type = ?" : "";
    if ($developerId) {
        $sql = "
            SELECT p.*, c.name as category_name, u.name as developer_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN users u ON p.developer_id = u.id
            WHERE p.developer_id = ?" . $sectionFilter . "
            ORDER BY p.created_at DESC
        ";
        $params = [$developerId];
        if ($section && $section !== 'general') $params[] = $section;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    } else {
        $sql = "
            SELECT p.*, c.name as category_name, u.name as developer_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN users u ON p.developer_id = u.id
            WHERE 1=1" . $sectionFilter . "
            ORDER BY p.created_at DESC
        ";
        if ($section && $section !== 'general') {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$section]);
        } else {
            $stmt = $pdo->query($sql);
        }
    }
    return $stmt->fetchAll();
}

// Get users for export (admin only)
function getExportUsers($role = null) {
    global $pdo;
    $sql = "
        SELECT u.*, COUNT(o.id) as order_count, COALESCE(SUM(o.final_amount), 0) as total_spent
        FROM users u
        LEFT JOIN orders o ON u.id = o.user_id AND o.payment_status = 'completed'
    ";
    $params = [];
    if ($role) {
        $sql .= " WHERE u.role = ?";
        $params[] = $role;
    }
    $sql .= " GROUP BY u.id ORDER BY u.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// ========== DOCUMENT & ASSET HELPERS ==========

// Ensure document tables and columns exist
function ensureDocumentTables() {
    global $pdo;
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS document_templates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type VARCHAR(50) NOT NULL UNIQUE,
            title VARCHAR(255) NOT NULL,
            body_content TEXT,
            status TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (PDOException $e) {}

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS developer_documents (
            id INT AUTO_INCREMENT PRIMARY KEY,
            developer_id INT NOT NULL,
            document_type VARCHAR(50) NOT NULL,
            file_path VARCHAR(500) DEFAULT NULL,
            status VARCHAR(20) DEFAULT 'generated',
            generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_dev_doc (developer_id, document_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (PDOException $e) {}

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS admin_assets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            asset_type VARCHAR(50) NOT NULL UNIQUE,
            file_path VARCHAR(500) NOT NULL,
            status TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (PDOException $e) {}

    // Insert default document templates if missing
    $types = [
        'agreement' => [
            'title' => 'Developer Agreement',
            'body' => '<h3>DEVELOPER AGREEMENT</h3>
<p>This Agreement is entered into on {{join_date}} by and between <strong>{{company_name}}</strong> (hereinafter referred to as "Company") and <strong>{{developer_name}}</strong> (hereinafter referred to as "Developer").</p>
<h4>1. Engagement</h4>
<p>The Developer agrees to list and sell digital products on the Company\'s platform under the <strong>{{plan_name}}</strong> plan.</p>
<h4>2. Rights and Obligations</h4>
<p>The Developer retains all intellectual property rights to their products. The Company is granted a non-exclusive right to list, promote, and sell the Developer\'s products on the platform.</p>
<h4>3. Revenue Sharing</h4>
<p>Revenue from sales shall be shared as per the terms of the selected plan. The Company shall process payments and disburse the Developer\'s share as per the payment schedule.</p>
<h4>4. Term and Termination</h4>
<p>This agreement shall remain in effect for the duration of the Developer\'s active subscription. Either party may terminate this agreement with 30 days written notice.</p>
<h4>5. Confidentiality</h4>
<p>The Developer agrees to keep all non-public information about the Company\'s operations and business practices confidential.</p>
<p style="text-align:center;margin-top:15px"><strong>IN WITNESS WHEREOF</strong>, the parties have executed this Agreement as of the date first written above.</p>'
        ],
        'joining_letter' => [
            'title' => 'Joining Letter',
            'body' => '<h3>JOINING LETTER</h3>
<p>Date: {{join_date}}</p>
<p>To,<br><strong>{{developer_name}}</strong><br>{{developer_email}}</p>
<p>Dear <strong>{{developer_name}}</strong>,</p>
<p>We are pleased to welcome you as a <strong>Developer</strong> on the <strong>{{company_name}}</strong> platform.</p>
<p>Your developer account has been activated under the <strong>{{plan_name}}</strong> plan. You are now authorized to list, manage, and sell your digital products through our platform.</p>
<h4>Key Details</h4>
<ul>
<li><strong>Developer Name:</strong> {{developer_name}}</li>
<li><strong>Email:</strong> {{developer_email}}</li>
<li><strong>Plan:</strong> {{plan_name}}</li>
<li><strong>Joining Date:</strong> {{join_date}}</li>
</ul>
<p>Please review the developer guidelines and agreement for complete terms and conditions. We look forward to a successful partnership.</p>
<p>Welcome aboard!</p>
<p style="margin-top:20px">Sincerely,<br><strong>{{company_name}}</strong></p>'
        ]
    ];
    foreach ($types as $type => $data) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM document_templates WHERE type = ?");
            $stmt->execute([$type]);
            if ((int)$stmt->fetchColumn() === 0) {
                $pdo->prepare("INSERT INTO document_templates (type, title, body_content, status) VALUES (?, ?, ?, 1)")
                    ->execute([$type, $data['title'], $data['body']]);
            }
        } catch (PDOException $e) {}
    }
}

// Get document template by type
function getDocumentTemplate($type) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM document_templates WHERE type = ? AND status = 1");
        $stmt->execute([$type]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

// Get all document templates
function getAllDocumentTemplates() {
    global $pdo;
    try {
        return $pdo->query("SELECT * FROM document_templates ORDER BY type")->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

// Replace placeholders in template
function replaceDocumentPlaceholders($content, $developer, $planName = '', $joinDate = '') {
    $companyName = getSetting('site_name', 'RS Digital Hub');
    $replacements = [
        '{{developer_name}}' => $developer['name'] ?? 'Developer',
        '{{developer_email}}' => $developer['email'] ?? '',
        '{{plan_name}}' => $planName ?: 'N/A',
        '{{join_date}}' => $joinDate ?: date('d M Y'),
        '{{company_name}}' => $companyName,
        '{{admin_name}}' => getSetting('admin_signature_text', 'Authorized Signatory'),
    ];
    return str_replace(array_keys($replacements), array_values($replacements), $content);
}

// Get or generate developer document
function getDeveloperDocument($developerId, $documentType) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM developer_documents WHERE developer_id = ? AND document_type = ?");
        $stmt->execute([$developerId, $documentType]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

// Save developer document record
function saveDeveloperDocument($developerId, $documentType, $filePath = null) {
    global $pdo;
    try {
        $existing = getDeveloperDocument($developerId, $documentType);
        if ($existing) {
            $pdo->prepare("UPDATE developer_documents SET file_path = ?, status = 'generated', generated_at = NOW() WHERE id = ?")
                ->execute([$filePath, $existing['id']]);
        } else {
            $pdo->prepare("INSERT INTO developer_documents (developer_id, document_type, file_path, status) VALUES (?, ?, ?, 'generated')")
                ->execute([$developerId, $documentType, $filePath]);
        }
    } catch (PDOException $e) {}
}

// Get admin asset path
function getAdminAssetPath($assetType) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT file_path FROM admin_assets WHERE asset_type = ? AND status = 1");
        $stmt->execute([$assetType]);
        $path = $stmt->fetchColumn();
        if ($path && file_exists($path)) return $path;
    } catch (PDOException $e) {}
    return null;
}

// Get company stamp image path
function getCompanyStampPath() {
    return getAdminAssetPath('company_stamp');
}

// Convert any GD/Imagick-supported image to JPEG, saves to same dir with .jpg extension
function imageToJpeg($srcPath) {
    $dir = dirname($srcPath);
    $base = pathinfo($srcPath, PATHINFO_FILENAME);
    $dstPath = $dir . DIRECTORY_SEPARATOR . $base . '.jpg';

    if (file_exists($dstPath)) return $dstPath;

    // Try GD first
    if (function_exists('imagecreatefromstring')) {
        $data = @file_get_contents($srcPath);
        if ($data) {
            $src = @imagecreatefromstring($data);
            if ($src) {
                $w = imagesx($src);
                $h = imagesy($src);
                $bg = imagecreatetruecolor($w, $h);
                $white = imagecolorallocate($bg, 255, 255, 255);
                imagefill($bg, 0, 0, $white);
                imagecopy($bg, $src, 0, 0, 0, 0, $w, $h);
                imagejpeg($bg, $dstPath, 95);
                imagedestroy($src);
                imagedestroy($bg);
                if (file_exists($dstPath)) return $dstPath;
            }
        }
    }

    // Fallback: try Imagick
    if (class_exists('Imagick')) {
        try {
            $img = new Imagick($srcPath);
            $img->setImageFormat('jpeg');
            $img->setImageBackgroundColor('white');
            $img = $img->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
            $img->writeImage($dstPath);
            $img->destroy();
            if (file_exists($dstPath)) return $dstPath;
        } catch (Exception $e) {}
    }

    return $srcPath;
}

// Generate stamp image as PNG (circular premium stamp, no alpha)
function generateStampImage($outputPath) {
    $text = 'RS DIGITAL HUB';
    $size = 300;
    $img = imagecreatetruecolor($size, $size);

    $center = $size / 2;
    $radius = 135;
    $borderWidth = 8;

    // White background (no alpha)
    $white = imagecolorallocate($img, 255, 255, 255);
    imagefill($img, 0, 0, $white);

    // Outer circle
    $outerColor = imagecolorallocate($img, 79, 70, 229);
    imagefilledellipse($img, $center, $center, $radius * 2, $radius * 2, $outerColor);

    // Inner white fill
    $innerColor = imagecolorallocate($img, 255, 255, 255);
    imagefilledellipse($img, $center, $center, ($radius - $borderWidth) * 2, ($radius - $borderWidth) * 2, $innerColor);

    // Inner ring
    $ringColor = imagecolorallocate($img, 79, 70, 229);
    imageellipse($img, $center, $center, ($radius - $borderWidth - 8) * 2, ($radius - $borderWidth - 8) * 2, $ringColor);

    // Center dot
    $dotColor = imagecolorallocate($img, 79, 70, 229);
    imagefilledellipse($img, $center, $center, 12, 12, $dotColor);

    // Text color
    $textColor = imagecolorallocate($img, 79, 70, 229);

    $tffFiles = [
        'C:/Windows/Fonts/arialbd.ttf',
        'C:/Windows/Fonts/arial.ttf',
        '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
    ];

    $useFont = null;
    foreach ($tffFiles as $f) {
        if (file_exists($f)) { $useFont = $f; break; }
    }

    if (!$useFont) {
        imagestring($img, 5, (int)($center - 35), (int)($center - 8), $text, $textColor);
        imagestring($img, 3, (int)($center - 20), (int)($center + 10), 'OFFICIAL', $textColor);
    } else {
        $box = imagettfbbox(24, 0, $useFont, $text);
        $w = $box[2] - $box[0];
        $x = ($size - $w) / 2;
        imagettftext($img, 24, 0, (int)$x, (int)($center - 5), $textColor, $useFont, $text);
        $box2 = imagettfbbox(14, 0, $useFont, 'OFFICIAL');
        $w2 = $box2[2] - $box2[0];
        $x2 = ($size - $w2) / 2;
        imagettftext($img, 14, 0, (int)$x2, (int)($center + 28), $textColor, $useFont, 'OFFICIAL');
    }

    imagepng($img, $outputPath);
    imagedestroy($img);
    return true;
}

// Ensure stamp exists, generate if not (always returns JPG path)
function ensureCompanyStamp() {
    $stampDir = __DIR__ . '/../uploads/admin_assets';
    if (!is_dir($stampDir)) @mkdir($stampDir, 0755, true);

    // If JPG already exists, return it
    if (file_exists($stampDir . '/company_stamp.jpg')) return $stampDir . '/company_stamp.jpg';
    if (file_exists($stampDir . '/stamp.jpg')) return $stampDir . '/stamp.jpg';

    // If only PNG exists, convert to JPG and delete PNG
    foreach (['company_stamp.png', 'stamp.png'] as $name) {
        $pngPath = $stampDir . '/' . $name;
        if (file_exists($pngPath)) {
            $jpgPath = imageToJpeg($pngPath);
            if ($jpgPath !== $pngPath) {
                @unlink($pngPath);
                return $jpgPath;
            }
        }
    }

    // Generate fresh stamp
    if (function_exists('imagecreatetruecolor')) {
        $stampPath = $stampDir . '/company_stamp.png';
        generateStampImage($stampPath);
        $finalPath = imageToJpeg($stampPath);
        if ($finalPath !== $stampPath) {
            @unlink($stampPath);
        }
        return file_exists($finalPath) ? $finalPath : null;
    }
    return null;
}

// Safely place image in PDF — wraps in try-catch to never crash PDF
function pdfPlaceImage($pdf, $path, $x, $y, $w, $h = 0) {
    if (!file_exists($path)) return;
    try {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        // JPG — pass directly
        if ($ext === 'jpg' || $ext === 'jpeg') {
            $pdf->Image($path, $x, $y, $w, $h, 'JPG');
            return;
        }

        // PNG/GIF/WebP — convert via GD temp file, then embed
        if (function_exists('imagecreatefromstring')) {
            $data = @file_get_contents($path);
            if ($data) {
                $src = @imagecreatefromstring($data);
                if ($src) {
                    $ww = imagesx($src);
                    $hh = imagesy($src);
                    $bg = imagecreatetruecolor($ww, $hh);
                    $white = imagecolorallocate($bg, 255, 255, 255);
                    imagefill($bg, 0, 0, $white);
                    imagecopy($bg, $src, 0, 0, 0, 0, $ww, $hh);
                    $tmp = sys_get_temp_dir() . '/pdf_img_' . md5($path . time()) . '.jpg';
                    @imagejpeg($bg, $tmp, 95);
                    imagedestroy($src);
                    imagedestroy($bg);
                    if (file_exists($tmp) && filesize($tmp) > 0) {
                        $pdf->Image($tmp, $x, $y, $w, $h, 'JPG');
                        @unlink($tmp);
                        return;
                    }
                }
            }
        }
    } catch (Exception $e) {
        // Silently skip — never let an image crash the PDF
    }
}

// Apply stamp to a TCPDF instance
function applyPdfStamp($pdf, $x = null, $y = null, $width = 30) {
    $stampPath = getCompanyStampPath() ?: ensureCompanyStamp();
    if (!$stampPath || !file_exists($stampPath)) return;
    if ($x === null) $x = 170;
    if ($y === null) $y = 195;
    pdfPlaceImage($pdf, $stampPath, $x, $y, $width);
}

// Get paid stamp image path
function getPaidStampPath() {
    return getAdminAssetPath('paid_stamp');
}

// Apply paid stamp to a TCPDF instance (invoice only)
function applyPdfPaidStamp($pdf, $x = null, $y = null, $width = 40) {
    $path = getPaidStampPath();
    if (!$path || !file_exists($path)) return;
    if ($x === null) $x = 145;
    if ($y === null) $y = 85;
    pdfPlaceImage($pdf, $path, $x, $y, $width);
}

// === Digital Signature (PKI) ===

define('DIGITAL_CERT_PATH', __DIR__ . '/../uploads/signatures/digital_cert.p12');
define('DIGITAL_CERT_PEM_PATH', __DIR__ . '/../uploads/signatures/digital_cert.pem');

function saveDigitalCert($pfxData, $password) {
    $dir = dirname(DIGITAL_CERT_PATH);
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    if (!openssl_pkcs12_read($pfxData, $certs, $password)) {
        return false;
    }

    file_put_contents(DIGITAL_CERT_PATH, $pfxData);

    $pem = ($certs['cert'] ?? '') . "\n" . ($certs['pkey'] ?? '');
    if (!empty($certs['cacert'])) $pem .= "\n" . $certs['cacert'];
    file_put_contents(DIGITAL_CERT_PEM_PATH, $pem);

    updateSetting('digital_signature_enabled', '1');

    $info = getDigitalCertInfo();
    if ($info) {
        updateSetting('digital_cert_cn', $info['cn']);
        updateSetting('digital_cert_expiry', $info['validToStr']);
        updateSetting('digital_cert_issuer', $info['issuer']);
    }

    return true;
}

function removeDigitalCert() {
    @unlink(DIGITAL_CERT_PATH);
    @unlink(DIGITAL_CERT_PEM_PATH);
    updateSetting('digital_signature_enabled', '');
    updateSetting('digital_cert_cn', '');
    updateSetting('digital_cert_expiry', '');
    updateSetting('digital_cert_issuer', '');
}

function getDigitalCertInfo() {
    if (!file_exists(DIGITAL_CERT_PEM_PATH)) return null;
    $cert = file_get_contents(DIGITAL_CERT_PEM_PATH);
    if (empty($cert)) return null;
    $parsed = openssl_x509_read($cert);
    if (!$parsed) return null;
    $info = openssl_x509_parse($parsed);
    openssl_x509_free($parsed);
    if (!$info) return null;

    return [
        'cn'           => $info['subject']['CN'] ?? 'Unknown',
        'issuer'       => $info['issuer']['CN'] ?? 'Unknown',
        'validFrom'    => $info['validFrom_time_t'] ?? 0,
        'validTo'      => $info['validTo_time_t'] ?? 0,
        'validFromStr' => ($info['validFrom_time_t'] ?? 0) ? date('Y-m-d H:i:s', $info['validFrom_time_t']) : 'Unknown',
        'validToStr'   => ($info['validTo_time_t'] ?? 0) ? date('Y-m-d H:i:s', $info['validTo_time_t']) : 'Unknown',
    ];
}

function applyDigitalSignature($pdf, $appearanceX, $appearanceY, $appearanceW, $appearanceH) {
    if (getSetting('digital_signature_enabled') !== '1') return;
    if (!file_exists(DIGITAL_CERT_PEM_PATH)) return;

    try {
        $certInfo = getDigitalCertInfo();
        $name = $certInfo['cn'] ?? 'Digital Signature';
        $certPath = 'file://' . str_replace('\\', '/', DIGITAL_CERT_PEM_PATH);

        $pdf->setSignature(
            $certPath,
            $certPath,
            '@unused',
            '',
            2,
            [
                'Name'        => $name,
                'Location'    => '',
                'Reason'      => 'Document Signing',
                'ContactInfo' => '',
            ]
        );

        $pdf->setSignatureAppearance($appearanceX, $appearanceY, $appearanceW, $appearanceH, -1, $name);
    } catch (\Throwable $e) {
        // Silently skip — never let a digital signature failure crash PDF generation
    }
}
