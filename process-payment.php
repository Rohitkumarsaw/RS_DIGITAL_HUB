<?php
// This page is no longer used. Redirect to orders.
require_once __DIR__ . '/config.php';
requireLogin();

$orderNumber = $_GET['order'] ?? ($_SESSION['pending_order']['order_number'] ?? null);
if ($orderNumber) {
    redirect(SITE_URL . '/payment-callback.php?order=' . urlencode($orderNumber));
}
redirect(SITE_URL . '/orders.php');
