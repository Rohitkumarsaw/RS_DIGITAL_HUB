<?php
$pageTitle = 'Signature Settings';
require_once __DIR__ . '/includes/header.php';
requireStrictAdmin();

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

ensureDocumentTables();

// CSRF check for all POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if ($csrf !== ($_SESSION['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid security token.');
        redirect(ADMIN_URL . '/documents-meta.php');
    }
}

// Handle save signature name
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signature_name'])) {
    $name = sanitize($_POST['signature_name']);
    updateSetting('admin_signature_text', $name);
    setFlash('success', 'Signature name saved.');
    redirect(ADMIN_URL . '/documents-meta.php');
}

// Handle digital certificate upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_cert'])) {
    $password = $_POST['cert_password'] ?? '';
    if (empty($password)) {
        setFlash('error', 'Certificate password is required.');
    } elseif (!isset($_FILES['cert_file']) || $_FILES['cert_file']['error'] !== UPLOAD_ERR_OK) {
        setFlash('error', 'Please select a valid PFX/P12 certificate file.');
    } else {
        $pfxData = file_get_contents($_FILES['cert_file']['tmp_name']);
        if (saveDigitalCert($pfxData, $password)) {
            setFlash('success', 'Digital certificate uploaded and enabled.');
        } else {
            setFlash('error', 'Failed to read certificate. Check the password and file format.');
        }
    }
    redirect(ADMIN_URL . '/documents-meta.php');
}

// Handle digital certificate removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_cert'])) {
    removeDigitalCert();
    setFlash('success', 'Digital certificate removed.');
    redirect(ADMIN_URL . '/documents-meta.php');
}

// Handle enable/disable toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_digital'])) {
    $enabled = $_POST['digital_enabled'] ?? '';
    updateSetting('digital_signature_enabled', $enabled === '1' ? '1' : '');
    setFlash('success', $enabled === '1' ? 'Digital signature enabled.' : 'Digital signature disabled.');
    redirect(ADMIN_URL . '/documents-meta.php');
}

$signatureName  = getSetting('admin_signature_text', 'Authorized Signatory');
$digitalEnabled = getSetting('digital_signature_enabled');
$certInfo       = file_exists(DIGITAL_CERT_PEM_PATH) ? getDigitalCertInfo() : null;
?>
<div class="admin-page-header">
    <h2>Signature Settings</h2>
    <a href="documents.php" class="btn btn-secondary">Back to Documents</a>
</div>

<div class="admin-stats-grid" style="grid-template-columns:1fr 1fr 1fr;gap:1.5rem;margin-top:1rem">
    <!-- Signature Name -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Admin Signature</h3>
        </div>
        <div class="admin-card-body">
            <p style="margin-bottom:1rem;font-size:0.85rem;color:var(--text-muted)">Enter the name that will appear as a handwritten-style signature on all documents.</p>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                <div class="form-group">
                    <label for="signature_name">Signature Name</label>
                    <input type="text" id="signature_name" name="signature_name" class="form-control" value="<?php echo sanitize($signatureName); ?>" placeholder="Authorized Signatory">
                </div>
                <button type="submit" class="btn btn-primary">Save Signature</button>
            </form>
            <div style="margin-top:1rem;padding:0.75rem;background:var(--bg-tertiary);border-radius:var(--border-radius);text-align:center;font-family:'times',serif;font-style:italic;font-size:1.1rem;color:#16a34a">
                <?php echo sanitize($signatureName); ?>
            </div>
        </div>
    </div>

    <!-- Digital Signature (PKI) -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Digital Signature (PKI)</h3>
            <?php if ($digitalEnabled === '1'): ?>
                <span class="badge badge-success">Active</span>
            <?php else: ?>
                <span class="badge badge-secondary">Disabled</span>
            <?php endif; ?>
        </div>
        <div class="admin-card-body">
            <?php if ($certInfo): ?>
                <!-- Cert info -->
                <div style="margin-bottom:1rem;padding:0.75rem;background:var(--bg-tertiary);border-radius:var(--border-radius);font-size:0.8rem">
                    <div><strong>CN:</strong> <?php echo sanitize($certInfo['cn']); ?></div>
                    <div><strong>Issuer:</strong> <?php echo sanitize($certInfo['issuer']); ?></div>
                    <div><strong>Valid From:</strong> <?php echo sanitize($certInfo['validFromStr']); ?></div>
                    <div><strong>Valid To:</strong> <?php echo sanitize($certInfo['validToStr']); ?></div>
                </div>

                <!-- Enable/disable toggle -->
                <form method="POST" style="margin-bottom:0.75rem">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                    <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;font-size:0.85rem">
                        <input type="hidden" name="toggle_digital" value="1">
                        <input type="checkbox" name="digital_enabled" value="1" onchange="this.form.submit()" <?php echo $digitalEnabled === '1' ? 'checked' : ''; ?>>
                        Enable digital signing
                    </label>
                </form>

                <!-- Remove cert -->
                <form method="POST" onsubmit="return confirm('Remove the digital certificate? Signed documents will still show the text signature.')">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                    <input type="hidden" name="remove_cert" value="1">
                    <button type="submit" class="btn btn-danger btn-sm">Remove Certificate</button>
                </form>
            <?php else: ?>
                <!-- Upload form -->
                <p style="margin-bottom:1rem;font-size:0.85rem;color:var(--text-muted)">
                    Upload a PFX/P12 digital certificate to enable PKI-based digital signing. Documents will include a verified digital signature compatible with Adobe Acrobat and other PDF readers.
                </p>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                    <div class="form-group">
                        <label for="cert_file">PFX/P12 Certificate File</label>
                        <input type="file" id="cert_file" name="cert_file" accept=".pfx,.p12" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="cert_password">Certificate Password</label>
                        <input type="password" id="cert_password" name="cert_password" class="form-control" placeholder="Enter PFX password" required>
                    </div>
                    <button type="submit" name="upload_cert" class="btn btn-primary">Upload & Enable</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
require_once __DIR__ . '/includes/footer.php';
?>
