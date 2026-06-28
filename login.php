<?php
$pageTitle = 'Login';
require_once __DIR__ . '/includes/header.php';

if (isset($_GET['session_expired'])) {
    setFlash('error', 'Your session has expired or was terminated on another device. Please login again.');
}

if (isLoggedIn()) {
    redirect(SITE_URL . '/section-select.php');
}

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    if (empty($email) || empty($password)) {
        setFlash('error', 'Please fill in all fields.');
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'blocked') {
                setFlash('error', 'Your account has been blocked. Please contact support.');
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

                if ($remember) {
                    setcookie('remember_token', generateToken(), [
                        'expires' => time() + (86400 * 30),
                        'path' => '/',
                        'httponly' => true,
                        'samesite' => 'Lax',
                    ]);
                }

                // Section selection step for all users
                redirect(SITE_URL . '/section-select.php');
            }
        } else {
            setFlash('error', 'Invalid email or password.');
        }
    }
}
?>

<div class="auth-container">
    <div class="auth-card">
        <h2>Welcome Back</h2>
        <p>Login to your account to continue</p>

        <form method="POST" data-validate>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
                    <button type="button" class="password-toggle">
                        <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="icon-eye-off" style="display:none" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
                </div>
            </div>

            <div class="form-group d-flex align-center justify-between">
                <label style="margin:0;display:flex;align-items:center;gap:0.5rem;cursor:pointer">
                    <input type="checkbox" name="remember"> Remember me
                </label>
                <a href="<?php echo SITE_URL; ?>/forgot-password.php">Forgot Password?</a>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg">Login</button>
        </form>

        <div class="auth-footer">
            Don't have an account? <a href="<?php echo SITE_URL; ?>/signup.php">Sign Up</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
