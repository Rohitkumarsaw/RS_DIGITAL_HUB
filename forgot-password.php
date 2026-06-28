<?php
$pageTitle = 'Forgot Password';
require_once __DIR__ . '/includes/header.php';

if (isLoggedIn()) {
    redirect(SITE_URL . '/profile.php');
}

$resetLink = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);

    if (empty($email)) {
        setFlash('error', 'Please enter your email address.');
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = generateToken();
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?");
            $stmt->execute([$user['id']]);

            $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$user['id'], $token, $expiresAt]);

            $resetLink = SITE_URL . '/reset-password.php?token=' . $token;
        } else {
            setFlash('success', 'If an account exists with that email, a reset link has been sent.');
        }
    }
}
?>

<div class="auth-container">
    <div class="auth-card">
        <h2>Forgot Password</h2>
        <p>Enter your email to receive a password reset link</p>

        <?php if ($resetLink): ?>
        <div style="background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.3);border-radius:var(--border-radius-sm);padding:1rem;margin-bottom:1.5rem">
            <p style="color:#10b981;font-weight:600;margin-bottom:0.5rem">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                Reset link generated!
            </p>
            <p style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:0.75rem">Copy the link below and open it in your browser to reset your password. This link expires in 1 hour.</p>
            <div style="display:flex;gap:0.5rem">
                <input type="text" id="resetLinkInput" value="<?php echo sanitize($resetLink); ?>" readonly style="flex:1;padding:0.5rem 0.75rem;background:var(--bg-tertiary);border:1px solid var(--border-color);border-radius:var(--border-radius-sm);color:var(--text-primary);font-size:0.85rem;cursor:text">
                <button onclick="copyResetLink()" class="btn btn-primary btn-sm" style="white-space:nowrap">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                    Copy
                </button>
            </div>
            <p id="copyMsg" style="display:none;color:#10b981;font-size:0.8rem;margin-top:0.5rem">Link copied to clipboard!</p>
            <a href="<?php echo sanitize($resetLink); ?>" class="btn btn-sm btn-outline" style="margin-top:0.75rem;width:100%;text-align:center">Open Reset Link</a>
        </div>
        <?php endif; ?>

        <form method="POST" data-validate>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg">Send Reset Link</button>
        </form>

        <div class="auth-footer">
            Remember your password? <a href="<?php echo SITE_URL; ?>/login.php">Login</a>
        </div>
    </div>
</div>

<script>
function copyResetLink() {
    const input = document.getElementById('resetLinkInput');
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value).then(function() {
        document.getElementById('copyMsg').style.display = 'block';
        setTimeout(function() {
            document.getElementById('copyMsg').style.display = 'none';
        }, 2000);
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
