<?php
require_once __DIR__ . '/config.php';
requireLogin();

$orderId = (int)($_GET['id'] ?? 0);
$order = getOrder($orderId, $_SESSION['user_id']);

if (!$order) {
    die('Order not found.');
}

$stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$order['user_id']]);
$user = $stmt->fetch();
$order['user_name'] = $user['name'] ?? '';
$order['user_email'] = $user['email'] ?? '';

$gst = getSetting('admin_gst', '');
$siteName = getSetting('site_name', 'RS Digital Hub');
$currency = getSetting('currency_symbol', '₹');
$taxType = getSetting('tax_type', 'GST');
$signatureName = getSetting('admin_signature_text', 'Authorized Signatory');
$compAddr = getSetting('company_address', '');
$compPhone = getSetting('company_phone', '');
$compEmail = getSetting('company_email', '');
$bankName = getSetting('invoice_bank_name', '');
$bankAcct = getSetting('invoice_bank_account', '');
$bankIfsc = getSetting('invoice_bank_ifsc', '');
$footerText = getSetting('invoice_footer_text', 'Thank you for your business!');

$netAmount = $order['total_amount'] - $order['discount_amount'];
$taxP = $order['total_amount'] > 0 && $order['tax_amount'] > 0 && $netAmount > 0
    ? round(($order['tax_amount'] / $netAmount) * 100, 2)
    : (float)getSetting('tax_percentage', 0);

$subtotal = $order['total_amount'] ?? 0;
$taxA = $order['tax_amount'] ?? 0;
$total = $order['final_amount'] ?? $subtotal;
$discount = $order['discount_amount'] ?? 0;

$items = $order['items'] ?? [];
if (empty($items) && isset($order['id'])) {
    $stmt = $pdo->prepare("SELECT oi.*, p.title FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
    $stmt->execute([$order['id']]);
    $items = $stmt->fetchAll();
}

$statusLabel = $order['payment_status'] === 'completed' ? 'Completed' : ($order['payment_status'] === 'pending' ? 'Pending' : 'Failed');
$pageTitle = 'Invoice - ' . $order['order_number'];
require_once __DIR__ . '/includes/header.php';
?>
<style>
.invoice-wrapper{max-width:820px;margin:30px auto;background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.08);overflow:hidden}
.invoice-header{background:#0f172a;padding:28px 40px;position:relative;overflow:hidden}
.invoice-header::before{content:'';position:absolute;top:-40px;right:-30px;width:200px;height:200px;background:rgba(99,102,241,.25);border-radius:50%}
.invoice-header::after{content:'';position:absolute;bottom:-60px;left:-20px;width:140px;height:140px;background:rgba(16,185,129,.15);border-radius:50%}
.invoice-header h1{color:#818cf8;font-size:22px;margin:0;position:relative;z-index:1}
.invoice-header .sub{color:#94a3b8;font-size:12px;margin-top:2px;position:relative;z-index:1}
.invoice-header .inv-right{position:absolute;right:40px;top:28px;text-align:right;z-index:1}
.invoice-header .inv-right h2{color:#818cf8;font-size:26px;margin:0;letter-spacing:2px}
.invoice-header .inv-right .ref{color:#cbd5e1;font-size:11px;margin-top:2px}
.invoice-body{padding:30px 40px;overflow-x:auto}
.info-row{display:flex;justify-content:space-between;margin-bottom:24px}
.info-col h3{font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:1px;margin:0 0 6px}
.info-col p{margin:2px 0;font-size:14px;color:#1e293b}
.info-col .small{font-size:12px;color:#475569}
.info-col.right{text-align:right}
.invoice-table{width:100%;border-collapse:collapse;margin-top:8px}
.invoice-table th{background:#f8fafc;color:#64748b;font-size:11px;text-transform:uppercase;letter-spacing:1px;padding:10px 12px;text-align:left;border-bottom:1px solid #e2e8f0}
.invoice-table th:last-child{text-align:right}
.invoice-table th:nth-child(2){text-align:center}
.invoice-table td{padding:10px 12px;font-size:13px;color:#334155;border-bottom:1px solid #e2e8f0}
.invoice-table td:last-child{text-align:right;font-weight:600}
.invoice-table td:nth-child(2){text-align:center}
.totals-wrap{margin-top:16px;display:flex;justify-content:flex-end}
.totals-box{width:280px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px 18px}
.totals-box .tr{display:flex;justify-content:space-between;padding:4px 0;font-size:13px;color:#64748b}
.totals-box .tr.total{border-top:1px solid #e2e8f0;margin-top:4px;padding-top:8px;font-size:15px;font-weight:700;color:#1e293b}
.totals-box .tr.total .val{color:#4f46e5}
.signature-section{margin-top:40px;text-align:right;padding-right:10px}
.signature-section .label{font-size:11px;color:#64748b;margin-bottom:2px}
.signature-section .name{font-family:'Dancing Script',cursive;font-size:22px;color:#16a34a;font-weight:500}
.signature-section .line{width:120px;height:2px;background:#16a34a;margin:4px 0 0 auto}
.header-info{font-size:11px;color:#94a3b8;margin-top:6px;position:relative;z-index:1}
.header-info span{margin-right:14px}
.footer-note{margin-top:32px;padding-top:16px;border-top:1px solid #e2e8f0;text-align:center;color:#94a3b8;font-size:12px}
.bank-info{margin-top:20px;padding:10px 16px;background:#f8fafc;border-radius:8px;font-size:12px;color:#475569;line-height:1.6}
.download-bar{background:#f1f5f9;padding:20px 40px;text-align:center;border-top:1px solid #e2e8f0}
.download-bar .btn-download{display:inline-flex;align-items:center;gap:8px;padding:12px 32px;background:#4f46e5;color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer;text-decoration:none;transition:background .2s}
.download-bar .btn-download:hover{background:#4338ca}
@media print{.no-print{display:none!important}}
</style>
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@500;700&display=swap" rel="stylesheet">

<div class="invoice-wrapper">
    <div class="invoice-header">
        <h1><?php echo sanitize($siteName); ?></h1>
        <div class="sub">Official Invoice</div>
        <?php if (!empty($compAddr) || !empty($compPhone) || !empty($compEmail)): ?>
        <div class="header-info">
            <?php if ($compAddr): ?><span><?php echo sanitize($compAddr); ?></span><?php endif; ?>
            <?php if ($compPhone): ?><span><?php echo sanitize($compPhone); ?></span><?php endif; ?>
            <?php if ($compEmail): ?><span><?php echo sanitize($compEmail); ?></span><?php endif; ?>
        </div>
        <?php endif; ?>
        <div class="inv-right">
            <h2>INVOICE</h2>
            <div class="ref"><?php echo sanitize($order['order_number']); ?></div>
        </div>
    </div>

    <div class="invoice-body">
        <div class="info-row">
            <div class="info-col">
                <h3>Bill To</h3>
                <p><strong><?php echo sanitize($order['user_name']); ?></strong></p>
                <p class="small"><?php echo sanitize($order['user_email']); ?></p>
                <?php if (!empty($gst)): ?>
                <p class="small" style="margin-top:6px;color:#64748b"><strong>GSTIN:</strong> <?php echo sanitize($gst); ?></p>
                <?php endif; ?>
            </div>
            <div class="info-col right">
                <h3>Invoice Details</h3>
                <p class="small">Date: <?php echo date('M d, Y', strtotime($order['created_at'])); ?></p>
                <p class="small">Order: <?php echo sanitize($order['order_number']); ?></p>
                <p class="small">Status: <?php echo $statusLabel; ?></p>
            </div>
        </div>

        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th style="width:60px">Qty</th>
                    <th style="width:120px">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo sanitize($item['title'] ?? 'Product'); ?></td>
                    <td>1</td>
                    <td><?php echo $currency; ?><?php echo number_format($item['price'] ?? 0, 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="totals-wrap">
            <div class="totals-box">
                <div class="tr">
                    <span>Subtotal</span>
                    <span><?php echo $currency; ?><?php echo number_format($subtotal, 2); ?></span>
                </div>
                <?php if ($discount > 0): ?>
                <div class="tr">
                    <span>Discount</span>
                    <span style="color:#16a34a">-<?php echo $currency; ?><?php echo number_format($discount, 2); ?></span>
                </div>
                <?php endif; ?>
                <?php if ($taxP > 0): ?>
                <div class="tr">
                    <span><?php echo sanitize($taxType); ?> (<?php echo $taxP; ?>%)</span>
                    <span><?php echo $currency; ?><?php echo number_format($taxA, 2); ?></span>
                </div>
                <?php endif; ?>
                <div class="tr total">
                    <span>Total Paid</span>
                    <span class="val"><?php echo $currency; ?><?php echo number_format($total, 2); ?></span>
                </div>
            </div>
        </div>

        <div class="signature-section">
            <div class="label">Authorized Signature</div>
            <div class="name"><?php echo sanitize($signatureName); ?></div>
            <div class="line"></div>
        </div>

        <?php if (!empty($bankName) || !empty($bankAcct) || !empty($bankIfsc)): ?>
        <div class="bank-info">
            <strong>Bank Details:</strong>
            <?php if ($bankName): ?><?php echo sanitize($bankName); ?><?php endif; ?>
            <?php if ($bankAcct): ?> &mdash; A/c: <?php echo sanitize($bankAcct); ?><?php endif; ?>
            <?php if ($bankIfsc): ?> &mdash; IFSC: <?php echo sanitize($bankIfsc); ?><?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="footer-note">
            <?php echo sanitize($footerText); ?><br>
            <?php echo sanitize($siteName); ?> | <?php echo !empty($compEmail) ? sanitize($compEmail) : 'support@' . strtolower(str_replace(' ', '', $siteName)) . '.com'; ?>
        </div>
    </div>

    <div class="download-bar no-print">
        <a href="invoice-pdf.php?id=<?php echo $order['id']; ?>" class="btn-download">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Download PDF
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
