<?php
require_once __DIR__ . '/../config.php';
$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="description" content="<?php echo getSetting('site_description', 'RS Digital Hub - Premium Digital Products Marketplace'); ?>">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo getSetting('site_name', SITE_NAME); ?></title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' stop-color='%234F46E5'/%3E%3Cstop offset='100%25' stop-color='%238183F4'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='32' height='32' rx='8' fill='url(%23g)'/%3E%3Ccircle cx='16' cy='16' r='9' fill='white' opacity='.15'/%3E%3Ctext x='16' y='21' font-family='system-ui,-apple-system,sans-serif' font-weight='800' font-size='15' fill='white' text-anchor='middle'%3ERS%3C/text%3E%3C/svg%3E">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css?v=<?php echo ($t = @filemtime(__DIR__ . '/../assets/css/style.css')) ? $t : '1'; ?>">
    <?php if (isset($extraCSS)): ?>
        <?php foreach ($extraCSS as $css): ?>
            <link rel="stylesheet" href="<?php echo SITE_URL . $css . '?v=' . ($t = @filemtime(__DIR__ . '/..' . $css)) ? $t : '1'; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
    <script>(function(){var t=localStorage.getItem('theme')||'dark';document.documentElement.setAttribute('data-theme',t);})();</script>
    <script>
    function showToast(message, type) {
        var existing = document.querySelectorAll('.toast-popup');
        for (var i = 0; i < existing.length; i++) existing[i].remove();
        var icons = {success:'<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',error:'<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',warning:'<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',info:'<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'};
        var t = document.createElement('div');
        t.className = 'toast-popup toast-' + (type === 'error' ? 'danger' : type);
        t.innerHTML = '<div class="toast-popup-icon">' + (icons[type] || icons.info) + '</div><div class="toast-popup-msg">' + message + '</div><button class="toast-popup-close" onclick="this.parentElement.remove()">&times;</button>';
        document.body.appendChild(t);
        setTimeout(function(){
            t.classList.add('toast-hiding');
            setTimeout(function(){ if(t.parentNode) t.remove(); }, 300);
        }, 4000);
    }
    function showConfirm(message, callbackOrUrl) {
        document.querySelectorAll('.confirm-modal-overlay').forEach(function(e){e.remove();});
        var overlay = document.createElement('div');
        overlay.className = 'confirm-modal-overlay';
        overlay.innerHTML = '<div class="confirm-modal"><div class="confirm-modal-icon"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div><div class="confirm-modal-msg">' + message + '</div><div class="confirm-modal-btns"><button class="confirm-modal-cancel">Cancel</button><button class="confirm-modal-ok">Confirm</button></div></div>';
        document.body.appendChild(overlay);
        overlay.querySelector('.confirm-modal-cancel').onclick = function(){ overlay.remove(); };
        overlay.querySelector('.confirm-modal-ok').onclick = function(){
            overlay.remove();
            if (typeof callbackOrUrl === 'string') window.location.href = callbackOrUrl;
            else if (typeof callbackOrUrl === 'function') callbackOrUrl();
        };
        overlay.onclick = function(e){ if (e.target === overlay) overlay.remove(); };
    }
    </script>
</head>
<body>
    <!-- Flash Messages -->
    <?php if ($flash): ?>
    <?php $allowedTypes = ['success', 'error', 'warning', 'info']; $flashType = in_array($flash['type'], $allowedTypes) ? $flash['type'] : 'info'; ?>
    <div class="toast-popup toast-<?php echo $flash['type'] === 'error' ? 'danger' : $flashType; ?>">
        <div class="toast-popup-icon"><?php if ($flash['type'] === 'success'): ?><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg><?php elseif ($flash['type'] === 'error'): ?><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg><?php elseif ($flash['type'] === 'warning'): ?><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg><?php else: ?><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg><?php endif; ?></div>
        <div class="toast-popup-msg"><?php echo sanitize($flash['message']); ?></div>
        <button class="toast-popup-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
    <script>setTimeout(function(){var t=document.querySelector('.toast-popup');if(t){t.classList.add('toast-hiding');setTimeout(function(){if(t.parentNode)t.remove();},300);}},4000);</script>
    <?php endif; ?>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="container">
            <!-- Brand -->
            <a href="<?php echo SITE_URL; ?>/index.php" class="navbar-brand">
                <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                <span class="brand-text">RS Digital Hub</span>
            </a>

            <!-- Desktop Nav Links -->
            <div class="navbar-nav hide-mobile">
                <a href="<?php echo SITE_URL; ?>/index.php" class="<?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">Home</a>
                <a href="<?php echo SITE_URL; ?>/products.php" class="<?php echo $currentPage === 'products.php' ? 'active' : ''; ?>">Products</a>
                <a href="<?php echo SITE_URL; ?>/contact.php" class="<?php echo $currentPage === 'contact.php' ? 'active' : ''; ?>">Contact</a>
                <a href="<?php echo SITE_URL; ?>/faq.php" class="<?php echo $currentPage === 'faq.php' ? 'active' : ''; ?>">FAQ</a>
                <a href="<?php echo SITE_URL; ?>/testimonials.php" class="<?php echo $currentPage === 'testimonials.php' ? 'active' : ''; ?>">Testimonials</a>
            </div>

            <!-- Desktop Search -->
            <form action="<?php echo SITE_URL; ?>/products.php" method="GET" class="navbar-search hide-mobile">
                <input type="text" name="search" placeholder="Search products..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </button>
            </form>

            <!-- Actions -->
            <div class="navbar-actions">
                <button id="themeToggle" class="theme-toggle" aria-label="Toggle theme">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                </button>

                <?php if (isLoggedIn()): ?>
                <a href="<?php echo SITE_URL; ?>/section-select.php" class="btn btn-sm btn-outline nav-section-btn hide-mobile" title="Switch Section">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:2px"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    Switch
                </a>
                <a href="<?php echo SITE_URL; ?>/profile.php" class="btn btn-sm btn-primary nav-profile-btn hide-mobile">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Profile
                </a>
                <?php else: ?>
                <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-sm btn-outline nav-login-btn hide-mobile">Login</a>
                <a href="<?php echo SITE_URL; ?>/signup.php" class="btn btn-sm btn-primary nav-signup-btn hide-mobile">Sign Up</a>
                <?php endif; ?>

                <!-- Hamburger (Mobile Only) -->
                <button id="menuToggle" class="nav-hamburger" aria-label="Toggle menu">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
            </div>
        </div>
    </nav>

    <!-- Mobile Menu (only visible on mobile via hamburger) -->
    <div id="mobileMenu" class="mobile-menu">
        <div class="mobile-menu-divider">Navigation</div>
        <a href="<?php echo SITE_URL; ?>/index.php">Home</a>
        <a href="<?php echo SITE_URL; ?>/products.php">Products</a>
        <a href="<?php echo SITE_URL; ?>/orders.php">My Orders</a>
        <a href="<?php echo SITE_URL; ?>/contact.php">Contact</a>
        <a href="<?php echo SITE_URL; ?>/faq.php">FAQ</a>
        <a href="<?php echo SITE_URL; ?>/testimonials.php">Testimonials</a>

        <div class="mobile-menu-divider">Account</div>
        <?php if (isLoggedIn()): ?>
        <a href="<?php echo SITE_URL; ?>/section-select.php">Switch Section</a>
        <a href="<?php echo SITE_URL; ?>/profile.php">Profile</a>
        <a href="<?php echo SITE_URL; ?>/logout.php">Logout</a>
        <?php else: ?>
        <a href="<?php echo SITE_URL; ?>/login.php">Login</a>
        <a href="<?php echo SITE_URL; ?>/signup.php">Sign Up</a>
        <?php endif; ?>
    </div>

    <!-- Main Content -->
    <main class="main-content">
