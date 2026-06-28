<?php
require_once __DIR__ . '/../config.php';
requireLogin();

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'] ?? 'user';

require_once __DIR__ . '/../classes/Subscription.php';
$sub = new Subscription($pdo);

$action = sanitize($_GET['action'] ?? $_POST['action'] ?? '');
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$token = sanitize($_GET['token'] ?? $_POST['csrf_token'] ?? '');

// Simple CSRF check
if ($token !== ($_SESSION['csrf_token'] ?? '')) {
    setFlash('error', 'Invalid security token. Please try again.');
    redirect(SITE_URL . '/dashboard.php');
}

if (!$id) {
    setFlash('error', 'Invalid subscription.');
    redirect(SITE_URL . '/dashboard.php');
}

$subscription = $sub->getById($id);
if (!$subscription) {
    setFlash('error', 'Subscription not found.');
    redirect(SITE_URL . '/dashboard.php');
}

// Check permission: admin can manage any, developer can manage own
$isAdmin = in_array($userRole, ['admin', 'super_admin']);
if (!$isAdmin && $subscription['developer_id'] != $userId) {
    setFlash('error', 'Access denied.');
    redirect(SITE_URL . '/dashboard.php');
}

switch ($action) {
    case 'cancel':
        $sub->updateStatus($id, 'cancelled');
        setFlash('success', 'Subscription cancelled successfully.');
        break;
    case 'pause':
        $sub->updateStatus($id, 'paused');
        setFlash('success', 'Subscription paused successfully.');
        break;
    case 'resume':
        $newPlan = sanitize($_POST['new_plan'] ?? $subscription['plan_name']);
        $validPlans = ['starter', 'business', 'professional'];
        if (!in_array($newPlan, $validPlans)) $newPlan = $subscription['plan_name'];
        // Plan change requires payment — redirect to plan-selection
        if ($newPlan !== $subscription['plan_name']) {
            setFlash('info', 'Please complete payment to change your plan.');
            redirect(SITE_URL . '/plan-selection.php');
        }
        $newExpiry = date('Y-m-d H:i:s', strtotime('+' . $sub->getPlanDuration($newPlan) . ' days'));
        $sub->changePlan($id, $newPlan, $newExpiry);
        setFlash('success', 'Subscription resumed successfully.');
        break;
    case 'delete':
        $sub->deleteById($id);
        setFlash('success', 'Pending subscription deleted successfully.');
        break;
    case 'activate':
        $expiryDate = date('Y-m-d H:i:s', strtotime('+30 days'));
        $sub->changePlan($id, $subscription['plan_name'], $expiryDate);
        // Generate invoice
        $invoiceNumber = 'INV-' . strtoupper($subscription['plan_name']) . '-' . str_pad($id, 5, '0', STR_PAD_LEFT);
        $invoicePath = 'invoices/generate_invoice.php?id=' . $id;
        $stmt = $pdo->prepare("INSERT INTO subscription_invoices (subscription_id, invoice_number, invoice_path) VALUES (?, ?, ?)");
        $stmt->execute([$id, $invoiceNumber, $invoicePath]);
        setFlash('success', 'Subscription activated successfully.');
        break;
    default:
        setFlash('error', 'Invalid action.');
}

// Redirect back
if ($isAdmin) {
    redirect(ADMIN_URL . '/subscriptions.php');
} else {
    redirect(SITE_URL . '/developer/subscription.php');
}
