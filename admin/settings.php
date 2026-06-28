<?php
$pageTitle = 'Settings';
require_once __DIR__ . '/includes/header.php';

$isDeveloper = $_SESSION['user_role'] === 'developer';
$isAdmin = $_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'super_admin';
$userId = $_SESSION['user_id'];
$tab = $_GET['tab'] ?? ($isDeveloper ? 'store' : 'general');

$allowedSystemTabs = ['general', 'tax', 'download', 'email', 'invoice'];

// CSRF check for all POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if ($csrf !== ($_SESSION['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid security token.');
        redirect(ADMIN_URL . '/settings.php?tab=' . $tab);
    }
}

// Handle store settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'store') {
    $storeName = sanitize($_POST['store_name']);
    $storeDescription = sanitize($_POST['store_description']);

    if (empty($storeName)) {
        setFlash('error', 'Store name is required.');
    } else {
        $currentUser = getCurrentUser();
        $storeSlug = $currentUser['store_slug'] ?: (generateSlug($storeName) . '-' . uniqid());
        $stmt = $pdo->prepare("UPDATE users SET store_name = ?, store_description = ?, store_slug = ? WHERE id = ?");
        $stmt->execute([$storeName, $storeDescription, $storeSlug, $userId]);
        $_SESSION['store_name'] = $storeName;
        setFlash('success', 'Store settings updated successfully.');
    }
    redirect(ADMIN_URL . '/settings.php?tab=store');
}

// Handle payment settings for developers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'payment') {
    $keyId = sanitize($_POST['razorpay_key_id'] ?? '');
    $keySecret = sanitize($_POST['razorpay_key_secret'] ?? '');
    // Keep existing secret if placeholder submitted (not changed)
    if ($keySecret === '********') {
        $stmt = $pdo->prepare("SELECT razorpay_key_secret FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $keySecret = $stmt->fetchColumn() ?: '';
    }
    $stmt = $pdo->prepare("UPDATE users SET razorpay_key_id = ?, razorpay_key_secret = ? WHERE id = ?");
    $stmt->execute([$keyId, $keySecret, $userId]);
    setFlash('success', 'Payment settings updated successfully.');
    redirect(ADMIN_URL . '/settings.php?tab=payment');
}

// Handle system settings (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab !== 'store' && $tab !== 'payment') {
    if (!$isAdmin) {
        setFlash('error', 'Access denied.');
        redirect(ADMIN_URL . '/settings.php');
    }
    $allowedFields = [
        'general' => ['site_name', 'site_footer_text', 'currency', 'currency_symbol', 'razorpay_mode', 'razorpay_key_id', 'razorpay_key_secret'],
        'tax' => ['tax_percentage', 'tax_type'],
        'download' => ['download_expiry_hours', 'download_limit'],
        'email' => ['email_from', 'email_from_name'],
        'invoice' => ['admin_gst', 'admin_signature_text', 'company_address', 'company_phone', 'company_email', 'invoice_bank_name', 'invoice_bank_account', 'invoice_bank_ifsc', 'invoice_footer_text'],
    ];
    $fields = $allowedFields[$tab] ?? [];
    foreach ($fields as $key) {
        $value = $_POST[$key] ?? '';
        // Keep existing secret if placeholder submitted
        if ($key === 'razorpay_key_secret' && $value === '********') {
            continue;
        }
        if (in_array($key, ['download_expiry_hours', 'download_limit'])) {
            $value = (int)$value;
        } else {
            $value = sanitize($value);
        }
        updateSetting($key, $value);
    }

    setFlash('success', 'Settings updated successfully.');
    redirect(ADMIN_URL . '/settings.php?tab=' . $tab);
}

$currentUser = getCurrentUser();
?>
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@500;700&display=swap" rel="stylesheet">
<style>
.signature-preview{font-family:'Dancing Script',cursive;font-size:28px;color:#16a34a;padding:8px 0;border-bottom:2px solid #16a34a;display:inline-block;font-weight:500}
</style>

<h2 class="mb-3"><?php echo $isDeveloper ? 'Store Settings' : 'System Settings'; ?></h2>

<div class="filters">
    <?php if ($isDeveloper): ?>
    <a href="?tab=store" class="btn <?php echo $tab === 'store' ? 'btn-primary' : 'btn-secondary'; ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        Store Profile
    </a>
    <a href="?tab=payment" class="btn <?php echo $tab === 'payment' ? 'btn-primary' : 'btn-secondary'; ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
        Payment
    </a>
    <a href="?tab=tax" class="btn <?php echo $tab === 'tax' ? 'btn-primary' : 'btn-secondary'; ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        Tax
    </a>
    <?php else: ?>
    <a href="?tab=store" class="btn <?php echo $tab === 'store' ? 'btn-primary' : 'btn-secondary'; ?>">Store Profile</a>
    <a href="?tab=general" class="btn <?php echo $tab === 'general' ? 'btn-primary' : 'btn-secondary'; ?>">General</a>
    <a href="?tab=tax" class="btn <?php echo $tab === 'tax' ? 'btn-primary' : 'btn-secondary'; ?>">Tax</a>
    <a href="?tab=download" class="btn <?php echo $tab === 'download' ? 'btn-primary' : 'btn-secondary'; ?>">Downloads</a>
    <a href="?tab=email" class="btn <?php echo $tab === 'email' ? 'btn-primary' : 'btn-secondary'; ?>">Email</a>
    <a href="?tab=invoice" class="btn <?php echo $tab === 'invoice' ? 'btn-primary' : 'btn-secondary'; ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        Invoice
    </a>
    <?php endif; ?>
</div>

<!-- Store Profile Tab -->
<?php if ($tab === 'store'): ?>
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
    <div class="card">
        <div class="card-body">
            <h3 class="mb-3">Store Profile</h3>
            <p class="text-muted mb-3" style="font-size:0.85rem">Manage your store name and description.</p>

            <div class="form-group">
                <label for="store_name">Store Name *</label>
                <input type="text" id="store_name" name="store_name" class="form-control" value="<?php echo sanitize($currentUser['store_name'] ?? ''); ?>" placeholder="My Digital Store" required>
            </div>

            <div class="form-group">
                <label for="store_description">Store Description</label>
                <textarea id="store_description" name="store_description" class="form-control" rows="4" placeholder="Tell customers about your store..."><?php echo sanitize($currentUser['store_description'] ?? ''); ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-lg mt-3">Save Store Profile</button>
        </div>
    </div>
</form>
<?php endif; ?>

<!-- Payment Tab (Developers) -->
<?php if ($tab === 'payment'): ?>
<?php
$stmt = $pdo->prepare("SELECT razorpay_key_id, razorpay_key_secret FROM users WHERE id = ?");
$stmt->execute([$userId]);
$devRazorpay = $stmt->fetch();
?>
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
    <div class="card">
        <div class="card-body">
            <h3 class="mb-3">Payment Settings</h3>
            <p class="text-muted mb-3" style="font-size:0.85rem">Configure Razorpay API keys to receive payments directly to your account. <a href="https://dashboard.razorpay.com" target="_blank">Get keys from Razorpay Dashboard</a>.</p>

            <div class="form-group">
                <label for="razorpay_key_id">Razorpay Key ID</label>
                <input type="text" id="razorpay_key_id" name="razorpay_key_id" class="form-control" value="<?php echo sanitize($devRazorpay['razorpay_key_id'] ?? ''); ?>" placeholder="rzp_test_xxxxxxxxxxxx">
            </div>
            <div class="form-group">
                <label for="razorpay_key_secret">Razorpay Key Secret</label>
                <input type="password" id="razorpay_key_secret" name="razorpay_key_secret" class="form-control" value="<?php echo !empty($devRazorpay['razorpay_key_secret']) ? '********' : ''; ?>" placeholder="xxxxxxxxxxxxxxxx">
            </div>

            <div class="mt-3" style="padding:0.75rem;background:rgba(245,158,11,0.08);border-radius:8px;border:1px solid rgba(245,158,11,0.2);font-size:0.85rem">
                <strong>Note:</strong> When customers buy your products, the payment will go to your Razorpay account. Leave empty to use the admin's Razorpay keys.
            </div>

            <button type="submit" class="btn btn-primary btn-lg mt-3">Save Payment Settings</button>
        </div>
    </div>
</form>
<?php endif; ?>

<!-- Invoice Tab (Admin Only) -->
<?php if ($tab === 'invoice'): ?>
<?php
$gst = getSetting('admin_gst', '');
$signatureText = getSetting('admin_signature_text', 'Authorized Signatory');
$compAddr = getSetting('company_address', '');
$compPhone = getSetting('company_phone', '');
$compEmail = getSetting('company_email', '');
$bankName = getSetting('invoice_bank_name', '');
$bankAcct = getSetting('invoice_bank_account', '');
$bankIfsc = getSetting('invoice_bank_ifsc', '');
$footerText = getSetting('invoice_footer_text', 'Thank you for your business!');
?>
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
    <div class="card">
        <div class="card-body">
            <h3 class="mb-3">Invoice Settings</h3>
            <p class="text-muted mb-3" style="font-size:0.85rem">Configure everything that appears on invoices — header, stamps, signature, bank details, and footer.</p>

            <!-- Header Section -->
            <h4 style="font-size:0.95rem;color:var(--text-primary);margin-bottom:12px;padding-bottom:6px;border-bottom:1px solid var(--border-color)">Header</h4>

            <div class="form-group">
                <label for="admin_gst">GSTIN (optional)</label>
                <input type="text" id="admin_gst" name="admin_gst" class="form-control" value="<?php echo sanitize($gst); ?>" placeholder="22AAAAA0000A1Z5">
                <small style="color:var(--text-muted);font-size:0.8rem">Leave empty if not applicable. Will appear on invoices if set.</small>
            </div>

            <div class="form-group" style="margin-top:1rem">
                <label for="company_address">Company Address</label>
                <textarea id="company_address" name="company_address" class="form-control" rows="2" placeholder="123 Business Park, Main Street"><?php echo sanitize($compAddr); ?></textarea>
                <small style="color:var(--text-muted);font-size:0.8rem">Appears below the header on invoices.</small>
            </div>

            <div class="d-flex flex-wrap gap-2" style="margin-top:1rem">
                <div class="form-group" style="flex:1;min-width:200px">
                    <label for="company_phone">Phone Number</label>
                    <input type="text" id="company_phone" name="company_phone" class="form-control" value="<?php echo sanitize($compPhone); ?>" placeholder="+91 98765 43210">
                </div>
                <div class="form-group" style="flex:1;min-width:200px">
                    <label for="company_email">Support Email</label>
                    <input type="email" id="company_email" name="company_email" class="form-control" value="<?php echo sanitize($compEmail); ?>" placeholder="support@example.com">
                    <small style="color:var(--text-muted);font-size:0.8rem">Appears in invoice header and footer.</small>
                </div>
            </div>

            <!-- Signature Section -->
            <h4 style="font-size:0.95rem;color:var(--text-primary);margin:1.5rem 0 12px;padding-bottom:6px;border-bottom:1px solid var(--border-color)">Signature</h4>

            <div class="form-group">
                <label for="admin_signature_text">Signature Name</label>
                <input type="text" id="admin_signature_text" name="admin_signature_text" class="form-control" value="<?php echo sanitize($signatureText); ?>" placeholder="Authorized Signatory">
                <small style="color:var(--text-muted);font-size:0.8rem">This name will appear as a cursive/handwriting signature on invoices.</small>
            </div>

            <!-- Bank Details Section -->
            <h4 style="font-size:0.95rem;color:var(--text-primary);margin:1.5rem 0 12px;padding-bottom:6px;border-bottom:1px solid var(--border-color)">Bank Details (Footer)</h4>

            <div class="d-flex flex-wrap gap-2">
                <div class="form-group" style="flex:1;min-width:180px">
                    <label for="invoice_bank_name">Bank Name</label>
                    <input type="text" id="invoice_bank_name" name="invoice_bank_name" class="form-control" value="<?php echo sanitize($bankName); ?>" placeholder="State Bank of India">
                </div>
                <div class="form-group" style="flex:1;min-width:180px">
                    <label for="invoice_bank_account">Account Number</label>
                    <input type="text" id="invoice_bank_account" name="invoice_bank_account" class="form-control" value="<?php echo sanitize($bankAcct); ?>" placeholder="12345678901">
                </div>
                <div class="form-group" style="flex:1;min-width:120px">
                    <label for="invoice_bank_ifsc">IFSC Code</label>
                    <input type="text" id="invoice_bank_ifsc" name="invoice_bank_ifsc" class="form-control" value="<?php echo sanitize($bankIfsc); ?>" placeholder="SBIN0001234">
                </div>
            </div>

            <!-- Footer Section -->
            <h4 style="font-size:0.95rem;color:var(--text-primary);margin:1.5rem 0 12px;padding-bottom:6px;border-bottom:1px solid var(--border-color)">Footer</h4>

            <div class="form-group">
                <label for="invoice_footer_text">Footer Thank You Text</label>
                <input type="text" id="invoice_footer_text" name="invoice_footer_text" class="form-control" value="<?php echo sanitize($footerText); ?>" placeholder="Thank you for your business!">
                <small style="color:var(--text-muted);font-size:0.8rem">This text appears at the bottom of every invoice.</small>
            </div>

            <button type="submit" class="btn btn-primary btn-lg mt-3">Save Invoice Settings</button>
        </div>
    </div>
</form>

<!-- Preview Section -->
<div class="card" style="margin-top:2rem">
    <div class="card-body">
        <h3 class="mb-3">Preview</h3>
        <p class="text-muted mb-3" style="font-size:0.85rem">How the signature and info will appear on invoices.</p>

        <p style="font-size:0.8rem;color:var(--text-muted);margin-bottom:4px">Authorized Signature <span style="color:#16a34a">(green)</span></p>
        <div class="signature-preview"><?php echo sanitize($signatureText ?: 'Authorized Signatory'); ?></div>

        <?php if (!empty($gst)): ?>
        <p style="margin-top:1rem;padding:0.5rem;background:var(--bg-tertiary);border-radius:6px;font-size:0.85rem;color:var(--text-secondary)">
            <strong>GSTIN:</strong> <?php echo sanitize($gst); ?>
        </p>
        <?php endif; ?>

        <?php if (!empty($compAddr) || !empty($compEmail)): ?>
        <p style="margin-top:0.5rem;padding:0.5rem;background:var(--bg-tertiary);border-radius:6px;font-size:0.85rem;color:var(--text-secondary)">
            <?php echo sanitize($compAddr); ?><?php if (!empty($compEmail)): ?><br><strong>Email:</strong> <?php echo sanitize($compEmail); ?><?php endif; ?>
        </p>
        <?php endif; ?>

        <?php if (!empty($bankName) || !empty($bankAcct) || !empty($bankIfsc)): ?>
        <p style="margin-top:0.5rem;padding:0.5rem;background:var(--bg-tertiary);border-radius:6px;font-size:0.85rem;color:var(--text-secondary)">
            <strong>Bank Details:</strong><br>
            <?php if ($bankName): ?><?php echo sanitize($bankName); ?><?php endif; ?>
            <?php if ($bankAcct): ?> &mdash; A/c: <?php echo sanitize($bankAcct); ?><?php endif; ?>
            <?php if ($bankIfsc): ?> &mdash; IFSC: <?php echo sanitize($bankIfsc); ?><?php endif; ?>
        </p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- System Settings -->
<?php if ($tab !== 'store' && $tab !== 'payment' && $tab !== 'invoice'): ?>
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
    <div class="card">
        <div class="card-body">
            <?php if ($tab === 'general'): ?>
            <h3 class="mb-3">General Settings</h3>

            <div class="form-group">
                <label for="site_name">Site Name</label>
                <input type="text" id="site_name" name="site_name" class="form-control" value="<?php echo getSetting('site_name', SITE_NAME); ?>">
            </div>

            <div class="form-group">
                <label for="site_footer_text">Footer Text</label>
                <input type="text" id="site_footer_text" name="site_footer_text" class="form-control" value="<?php echo getSetting('site_footer_text'); ?>">
            </div>

            <div class="d-flex flex-wrap gap-2">
                <div class="form-group" style="flex:1;min-width:150px">
                    <label for="currency">Currency Code</label>
                    <input type="text" id="currency" name="currency" class="form-control" value="<?php echo getSetting('currency', 'INR'); ?>" placeholder="INR">
                </div>
                <div class="form-group" style="flex:1;min-width:100px">
                    <label for="currency_symbol">Currency Symbol</label>
                    <input type="text" id="currency_symbol" name="currency_symbol" class="form-control" value="<?php echo getSetting('currency_symbol', '₹'); ?>" placeholder="₹">
                </div>
                <div class="form-group" style="flex:1;min-width:150px">
                    <label for="razorpay_mode">Razorpay Mode</label>
                    <select id="razorpay_mode" name="razorpay_mode" class="form-control">
                        <option value="test" <?php echo getSetting('razorpay_mode', 'test') === 'test' ? 'selected' : ''; ?>>Test Mode</option>
                        <option value="live" <?php echo getSetting('razorpay_mode') === 'live' ? 'selected' : ''; ?>>Live Mode</option>
                    </select>
                </div>
            </div>

            <div class="mt-3">
                <h4>Razorpay API Keys</h4>
                <p class="text-muted mb-2" style="font-size:0.85rem">Required for automatic redirect after payment. Get these from <a href="https://dashboard.razorpay.com" target="_blank">Razorpay Dashboard</a> → Settings → API Keys.</p>
                <div class="d-flex flex-wrap gap-2">
                    <div class="form-group" style="flex:1;min-width:250px">
                        <label for="razorpay_key_id">Key ID</label>
                        <input type="text" id="razorpay_key_id" name="razorpay_key_id" class="form-control" value="<?php echo getSetting('razorpay_key_id'); ?>" placeholder="rzp_test_xxxxxxxxxxxx">
                    </div>
                    <div class="form-group" style="flex:1;min-width:250px">
                        <label for="razorpay_key_secret">Key Secret</label>
                        <input type="password" id="razorpay_key_secret" name="razorpay_key_secret" class="form-control" value="<?php echo !empty(getSetting('razorpay_key_secret')) ? '********' : ''; ?>" placeholder="xxxxxxxxxxxxxxxx">
                    </div>
                </div>
            </div>

            <?php elseif ($tab === 'tax'): ?>
            <h3 class="mb-3">Tax Settings</h3>

            <div class="d-flex flex-wrap gap-2">
                <div class="form-group" style="flex:1;min-width:150px">
                    <label for="tax_type">Tax Type</label>
                    <select id="tax_type" name="tax_type" class="form-control">
                        <option value="GST" <?php echo getSetting('tax_type') === 'GST' ? 'selected' : ''; ?>>GST</option>
                        <option value="VAT" <?php echo getSetting('tax_type') === 'VAT' ? 'selected' : ''; ?>>VAT</option>
                        <option value="Sales Tax" <?php echo getSetting('tax_type') === 'Sales Tax' ? 'selected' : ''; ?>>Sales Tax</option>
                        <option value="None" <?php echo getSetting('tax_type') === 'None' ? 'selected' : ''; ?>>None</option>
                    </select>
                </div>
                <div class="form-group" style="flex:1;min-width:150px">
                    <label for="tax_percentage">Tax Percentage (%)</label>
                    <input type="number" id="tax_percentage" name="tax_percentage" class="form-control" step="0.01" min="0" max="100" value="<?php echo getSetting('tax_percentage', 0); ?>">
                </div>
            </div>

            <?php elseif ($tab === 'download'): ?>
            <h3 class="mb-3">Download Settings</h3>

            <div class="d-flex flex-wrap gap-2">
                <div class="form-group" style="flex:1;min-width:200px">
                    <label for="download_limit">Default Download Limit</label>
                    <input type="number" id="download_limit" name="download_limit" class="form-control" min="1" value="<?php echo getSetting('download_limit', 5); ?>">
                </div>
                <div class="form-group" style="flex:1;min-width:200px">
                    <label for="download_expiry_hours">Download Expiry (Hours)</label>
                    <input type="number" id="download_expiry_hours" name="download_expiry_hours" class="form-control" min="1" value="<?php echo getSetting('download_expiry_hours', 48); ?>">
                </div>
            </div>

            <?php elseif ($tab === 'email'): ?>
            <h3 class="mb-3">Email Settings</h3>

            <div class="d-flex flex-wrap gap-2">
                <div class="form-group" style="flex:1;min-width:250px">
                    <label for="email_from">From Email</label>
                    <input type="email" id="email_from" name="email_from" class="form-control" value="<?php echo getSetting('email_from'); ?>">
                </div>
                <div class="form-group" style="flex:1;min-width:200px">
                    <label for="email_from_name">From Name</label>
                    <input type="text" id="email_from_name" name="email_from_name" class="form-control" value="<?php echo getSetting('email_from_name', SITE_NAME); ?>">
                </div>
            </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary btn-lg mt-3">Save Settings</button>
        </div>
    </div>
</form>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
