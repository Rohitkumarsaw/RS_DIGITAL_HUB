<?php
require_once __DIR__ . '/../config.php';

if (isAdmin()) {
    redirect(SITE_URL . '/section-select.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role IN ('admin', 'super_admin')");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'blocked') {
                $error = 'Your account has been blocked.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                try {
                    createSession($pdo, $user['id']);
                } catch (PDOException $e) {
                    // Migrations not run yet — skip
                }

                redirect(SITE_URL . '/section-select.php');
            }
        } else {
            $error = 'Invalid credentials or not an admin account.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo getSetting('site_name', SITE_NAME); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <script>(function(){var t=localStorage.getItem('theme')||'dark';document.documentElement.setAttribute('data-theme',t);})();</script>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h2>Admin Login</h2>
            <p>Access the admin dashboard</p>

            <?php if (isset($error)): ?>
            <div class="toast-popup toast-danger" style="position:fixed;top:calc(var(--navbar-height) + 0.75rem);right:1rem;z-index:9999;max-width:400px;min-width:280px;display:flex;align-items:center;gap:0.75rem;padding:1rem 1.25rem;border-radius:var(--border-radius);background:var(--bg-primary);border:1px solid var(--border-color);box-shadow:0 8px 32px rgba(0,0,0,0.25);border-left:4px solid #ef4444;animation:toastIn 0.4s cubic-bezier(0.34,1.56,0.64,1) forwards;transform:translateX(120%);opacity:0">
                <div class="toast-popup-icon" style="flex-shrink:0;width:28px;height:28px;display:flex;align-items:center;justify-content:center;border-radius:50%;background:rgba(239,68,68,0.15);color:#ef4444"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></div>
                <div class="toast-popup-msg" style="flex:1;font-size:0.9rem;line-height:1.4;color:var(--text-primary)"><?php echo sanitize($error); ?></div>
                <button class="toast-popup-close" style="flex-shrink:0;background:none;border:none;font-size:1.25rem;cursor:pointer;color:var(--text-muted);padding:0;line-height:1;width:24px;height:24px;display:flex;align-items:center;justify-content:center;border-radius:50%" onclick="this.parentElement.remove()">&times;</button>
            </div>
            <script>setTimeout(function(){var t=document.querySelector('.toast-popup');if(t){t.style.animation='toastOut 0.3s ease forwards';setTimeout(function(){if(t.parentNode)t.remove();},300);}},5000);</script>
            <?php endif; ?>

            <form method="POST" data-validate>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="Enter admin email" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" class="form-control" placeholder="Enter password" required>
                        <button type="button" class="password-toggle">
                            <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="icon-eye-off" style="display:none" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">Login</button>
            </form>

            <div class="auth-footer">
                <a href="<?php echo SITE_URL; ?>/index.php">Back to Website</a>
            </div>
        </div>
    </div>

    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
</body>
</html>
