<?php
$pageTitle = 'Sign Up';
require_once __DIR__ . '/config.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $role = in_array($_POST['role'] ?? '', ['user', 'developer']) ? $_POST['role'] : 'user';
    $storeName = trim($_POST['store_name'] ?? '');

    if (empty($name)) $errors[] = 'Name is required.';
    if (empty($email)) $errors[] = 'Email is required.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
    if (empty($password)) $errors[] = 'Password is required.';
    elseif (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirmPassword) $errors[] = 'Passwords do not match.';

    if ($role === 'developer') {
        if (empty($storeName)) $errors[] = 'Store name is required for developer accounts.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Email already registered.';
        }
    }

    if (empty($errors)) {
        ensureProfileColumns();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        if ($role === 'developer') {
            $storeSlug = generateSlug($storeName) . '-' . uniqid();
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, profile_status, store_name, store_slug) VALUES (?, ?, ?, 'developer', 'incomplete', ?, ?)");
            $success = $stmt->execute([$name, $email, $hashedPassword, $storeName, $storeSlug]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, profile_status) VALUES (?, ?, ?, 'user', 'incomplete')");
            $success = $stmt->execute([$name, $email, $hashedPassword]);
        }

        if ($success) {
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = $role;

            require_once __DIR__ . '/includes/session_helper.php';
            try {
                createSession($pdo, $_SESSION['user_id']);
            } catch (PDOException $e) {
                // Migrations not run yet — skip
            }

            setFlash('success', 'Account created successfully! Welcome to ' . getSetting('site_name', SITE_NAME) . '.');
            if ($role === 'developer') {
                redirect(SITE_URL . '/plan-selection.php');
            } else {
                redirect(SITE_URL . '/section-select.php');
            }
        } else {
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';

if (isLoggedIn()) {
    redirect(SITE_URL . '/section-select.php');
}

// Show inline errors
if (!empty($errors)): ?>
<div class="toast-popup toast-danger">
    <div class="toast-popup-icon"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></div>
    <div class="toast-popup-msg"><?php echo htmlspecialchars(implode(' ', $errors), ENT_QUOTES, 'UTF-8'); ?></div>
    <button class="toast-popup-close" onclick="this.parentElement.remove()">&times;</button>
</div>
<script>setTimeout(function(){var t=document.querySelector('.toast-popup');if(t){t.classList.add('toast-hiding');setTimeout(function(){if(t.parentNode)t.remove();},300);}},4000);</script>
<?php endif; ?>

<div class="auth-container">
    <div class="auth-card">
        <h2>Create Account</h2>
        <p>Join us to access premium digital products</p>

        <form method="POST" data-validate>
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" class="form-control" placeholder="Enter your name" required value="<?php echo isset($_POST['name']) ? sanitize($_POST['name']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group">
                <label>Account Type</label>
                <div class="d-flex gap-2">
                    <label class="radio-inline" style="display:flex;align-items:center;gap:0.4rem;cursor:pointer">
                        <input type="radio" name="role" value="user" <?php echo (!isset($_POST['role']) || $_POST['role'] === 'user') ? 'checked' : ''; ?> onchange="toggleStoreField()"> User Account
                    </label>
                    <label class="radio-inline" style="display:flex;align-items:center;gap:0.4rem;cursor:pointer">
                        <input type="radio" name="role" value="developer" <?php echo (isset($_POST['role']) && $_POST['role'] === 'developer') ? 'checked' : ''; ?> onchange="toggleStoreField()"> Developer Account
                    </label>
                </div>
            </div>

            <div id="storeFieldGroup" class="form-group" style="<?php echo (isset($_POST['role']) && $_POST['role'] === 'developer') ? '' : 'display:none;'; ?>">
                <label for="store_name">Store Name *</label>
                <input type="text" id="store_name" name="store_name" class="form-control" placeholder="Your personal store name" value="<?php echo isset($_POST['store_name']) ? sanitize($_POST['store_name']) : ''; ?>">
                <p class="text-muted" style="font-size:0.8rem;margin-top:0.25rem">This will be displayed on your store page and product listings.</p>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" class="form-control" placeholder="Create a password (min 6 characters)" required>
                    <button type="button" class="password-toggle">
                        <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="icon-eye-off" style="display:none" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="password-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirm your password" required>
                    <button type="button" class="password-toggle">
                        <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="icon-eye-off" style="display:none" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg">Create Account</button>
        </form>

        <script>
        function toggleStoreField() {
            var storeField = document.getElementById('storeFieldGroup');
            var devRadio = document.querySelector('input[name="role"][value="developer"]');
            if (devRadio && devRadio.checked) {
                storeField.style.display = 'block';
            } else {
                storeField.style.display = 'none';
            }
        }
        </script>

        <div class="auth-footer">
            Already have an account? <a href="<?php echo SITE_URL; ?>/login.php">Login</a>
        </div>
    </div>
</div>

<script>
function togglePasswordVisibility(inputId, btn) {
    var input = document.getElementById(inputId);
    if (!input) return;
    
    var eyeIcon = btn.querySelector('.icon-eye');
    var eyeOffIcon = btn.querySelector('.icon-eye-off');
    
    if (input.type === 'password') {
        input.type = 'text';
        eyeIcon.style.display = 'none';
        eyeOffIcon.style.display = 'block';
    } else {
        input.type = 'password';
        eyeIcon.style.display = 'block';
        eyeOffIcon.style.display = 'none';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
