<?php
require_once __DIR__ . '/../../config.php';
requireAdminOrDeveloper();

$currentPage = basename($_SERVER['PHP_SELF']);
$adminUser = getCurrentUser();
$flash = getFlash();
$currentSection = getCurrentSection();
$userRole = $_SESSION['user_role'] ?? 'user';
$isDeveloper = $userRole === 'developer';
$isStrictAdmin = in_array($userRole, ['admin', 'super_admin']);

// Restricted pages for developers
$restrictedPages = ['policies.php', 'about.php', 'notices.php', 'contact-settings.php'];
if ($isDeveloper && in_array($currentPage, $restrictedPages)) {
    setFlash('error', 'Access denied. You do not have permission to access this page.');
    redirect(ADMIN_URL . '/index.php');
}

// Subscription restriction for developers (no active plan = restricted)
$allowedPagesWithoutSub = ['subscription.php', 'subscriptions.php', 'plan_features.php', 'guidelines.php', 'logout.php', 'section-switch.php', 'documents.php'];
if ($isDeveloper && !in_array($currentPage, $allowedPagesWithoutSub)) {
    try {
        require_once __DIR__ . '/../../classes/Subscription.php';
        $subCheck = new Subscription($pdo);
        if ($subCheck->isDeveloperRestricted((int)$adminUser['id'])) {
            setFlash('info', 'Please subscribe to a plan to access this feature.');
            redirect(SITE_URL . '/developer/subscription.php');
        }
    } catch (PDOException $e) {
        // Subscriptions table may not exist yet — allow access
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Admin - <?php echo getSetting('site_name', SITE_NAME); ?></title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' stop-color='%234F46E5'/%3E%3Cstop offset='100%25' stop-color='%238183F4'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='32' height='32' rx='8' fill='url(%23g)'/%3E%3Ccircle cx='16' cy='16' r='9' fill='white' opacity='.15'/%3E%3Ctext x='16' y='21' font-family='system-ui,-apple-system,sans-serif' font-weight='800' font-size='15' fill='white' text-anchor='middle'%3ERS%3C/text%3E%3C/svg%3E">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css?v=<?php echo ($t = @filemtime(__DIR__ . '/../../assets/css/style.css')) ? $t : '1'; ?>">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css?v=<?php echo ($t = @filemtime(__DIR__ . '/../../assets/css/admin.css')) ? $t : '1'; ?>">
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
    <?php if ($flash): ?>
    <?php $allowedTypes = ['success', 'error', 'warning', 'info']; $flashType = in_array($flash['type'], $allowedTypes) ? $flash['type'] : 'info'; ?>
    <div class="toast-popup toast-<?php echo $flash['type'] === 'error' ? 'danger' : $flashType; ?>">
        <div class="toast-popup-icon"><?php if ($flash['type'] === 'success'): ?><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg><?php elseif ($flash['type'] === 'error'): ?><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg><?php elseif ($flash['type'] === 'warning'): ?><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg><?php else: ?><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg><?php endif; ?></div>
        <div class="toast-popup-msg"><?php echo sanitize($flash['message']); ?></div>
        <button class="toast-popup-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
    <script>setTimeout(function(){var t=document.querySelector('.toast-popup');if(t){t.classList.add('toast-hiding');setTimeout(function(){if(t.parentNode)t.remove();},300);}},4000);</script>
    <?php endif; ?>

    <!-- Section Selection Bar -->
    <div class="admin-section-bar">
        <div class="admin-navbar-container" style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
            <span style="font-size:0.85rem;font-weight:600;color:var(--text-secondary);white-space:nowrap">Section:</span>
            <form method="POST" action="<?php echo ADMIN_URL; ?>/section-switch.php" style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                <select name="section" class="form-control" style="width:auto;min-width:140px;font-size:0.85rem;padding:0.35rem 0.75rem" onchange="this.form.submit()">
                    <option value="general" <?php echo $currentSection === 'general' ? 'selected' : ''; ?>>General</option>
                    <?php foreach (getProductTypes() as $key => $label): ?>
                    <option value="<?php echo $key; ?>" <?php echo $currentSection === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="badge badge-primary" style="font-size:0.75rem">
                    <?php echo $currentSection === 'general' ? 'Main Site' : getProductTypeLabel($currentSection); ?>
                </span>
            </form>
                    <div style="flex:1"></div>
            <a href="<?php echo SITE_URL; ?>/section-select.php" class="btn btn-sm btn-outline" style="font-size:0.8rem;padding:0.3rem 0.6rem" title="Switch Section">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:2px"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Switch
            </a>
            <?php if ($isDeveloper && $adminUser['store_name']): ?>
            <span style="font-size:0.8rem;color:var(--text-muted)">
                Store: <strong><?php echo sanitize($adminUser['store_name']); ?></strong>
            </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Admin Navbar -->
    <nav class="admin-navbar">
        <div class="admin-navbar-container">
            <button id="sidebarToggle" class="sidebar-toggle" aria-label="Toggle sidebar">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>

            <a href="<?php echo ADMIN_URL; ?>/index.php" class="admin-brand">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <span class="admin-brand-text"><?php echo $isDeveloper ? 'Developer Panel' : 'Admin Panel'; ?></span>
            </a>

            <div class="admin-navbar-actions">
                <button id="themeToggle" class="admin-theme-toggle" aria-label="Toggle theme">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                </button>

                <a href="<?php echo SITE_URL; ?>" target="_blank" class="btn btn-sm btn-outline admin-view-site-btn hide-mobile">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    Site
                </a>

                <span class="admin-user-info hide-mobile">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <?php echo sanitize($adminUser['name']); ?>
                    <?php if ($isDeveloper): ?>
                    <span class="badge badge-info" style="font-size:0.65rem;margin-left:0.25rem">DEV</span>
                    <?php endif; ?>
                </span>

                <a href="<?php echo SITE_URL; ?>/logout.php" class="btn btn-sm btn-danger admin-logout-btn hide-mobile">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    <span class="hide-mobile">Logout</span>
                </a>
            </div>
        </div>
    </nav>

    <div id="sidebarOverlay" class="sidebar-overlay"></div>

    <div class="admin-layout">
        <aside class="admin-sidebar" id="adminSidebar">
        <div class="admin-sidebar-header">
            <a href="<?php echo ADMIN_URL; ?>/index.php" class="admin-sidebar-brand">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <span><?php echo $isDeveloper ? 'Developer Panel' : 'Admin Panel'; ?></span>
            </a>
            <div style="display:flex;align-items:center;gap:0.25rem">
                <button id="sidebarClose" class="sidebar-close" aria-label="Close sidebar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
        </div>

        <div class="admin-sidebar-user">
            <div class="admin-sidebar-avatar"><?php echo strtoupper(substr($adminUser['name'], 0, 1)); ?></div>
            <div class="admin-sidebar-user-info">
                <strong><?php echo sanitize($adminUser['name']); ?></strong>
                <small><?php echo $isDeveloper ? 'Developer' : 'Admin'; ?></small>
            </div>
            <a href="<?php echo SITE_URL; ?>/logout.php" class="admin-sidebar-logout-icon" title="Logout">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            </a>
        </div>

        <nav class="admin-sidebar-nav">
            <a href="<?php echo ADMIN_URL; ?>/index.php" class="<?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>
            <a href="<?php echo ADMIN_URL; ?>/products.php" class="<?php echo $currentPage === 'products.php' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                Products
            </a>
            <?php if ($isStrictAdmin || $isDeveloper): ?>
            <a href="<?php echo ADMIN_URL; ?>/orders.php" class="<?php echo $currentPage === 'orders.php' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                Orders
            </a>
            <?php endif; ?>

            <?php if ($currentSection === 'general'): ?>
            <?php if ($isStrictAdmin): ?>
            <a href="<?php echo ADMIN_URL; ?>/users.php" class="<?php echo $currentPage === 'users.php' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Users
            </a>
            <?php endif; ?>
            <?php if ($isStrictAdmin || $isDeveloper): ?>
            <a href="<?php echo ADMIN_URL; ?>/coupons.php" class="<?php echo $currentPage === 'coupons.php' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
                Coupons
            </a>
            <?php endif; ?>
            <?php if (!$isDeveloper): ?>
            <a href="<?php echo ADMIN_URL; ?>/support.php" class="<?php echo $currentPage === 'support.php' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                Support
            </a>
            <?php endif; ?>

            <?php if ($isStrictAdmin || $isDeveloper): ?>
            <a href="<?php echo ADMIN_URL; ?>/messages.php" class="<?php echo $currentPage === 'messages.php' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                Messages
            </a>
            <?php endif; ?>

            <?php if ($isStrictAdmin): ?>
            <a href="<?php echo ADMIN_URL; ?>/verification.php" class="<?php echo $currentPage === 'verification.php' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                Verification
            </a>
            <a href="<?php echo ADMIN_URL; ?>/sessions.php" class="<?php echo $currentPage === 'sessions.php' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Sessions
            </a>
            <div class="sidebar-divider">Content Management</div>
            <a href="<?php echo ADMIN_URL; ?>/policies.php?section=<?php echo $currentSection; ?>" class="<?php echo $currentPage === 'policies.php' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                Policies
            </a>
            <a href="<?php echo ADMIN_URL; ?>/about.php?section=<?php echo $currentSection; ?>" class="<?php echo $currentPage === 'about.php' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                About Us
            </a>
            <a href="<?php echo ADMIN_URL; ?>/notices.php?section=<?php echo $currentSection; ?>" class="<?php echo $currentPage === 'notices.php' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                Notice Board
            </a>
            <a href="<?php echo ADMIN_URL; ?>/contact-settings.php?section=<?php echo $currentSection; ?>" class="<?php echo $currentPage === 'contact-settings.php' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                Contact & Social
            </a>
            <?php endif; ?>

            <div class="sidebar-divider"><?php echo $isDeveloper ? 'Store' : 'System'; ?></div>
            <?php if ($isStrictAdmin || $isDeveloper): ?>
            <a href="<?php echo ADMIN_URL; ?>/settings.php" class="<?php echo $currentPage === 'settings.php' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                <?php echo $isDeveloper ? 'Store Settings' : 'Settings'; ?>
            </a>
            <?php endif; ?>
            <?php if ($isStrictAdmin): ?>
            <a href="<?php echo ADMIN_URL; ?>/backup.php" class="<?php echo $currentPage === 'backup.php' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Database Backup
            </a>
            <?php require_once __DIR__ . '/subscriptions_sidebar.php'; ?>
            <?php elseif ($isDeveloper): ?>
            <?php require_once __DIR__ . '/../../developer/includes/subscription_sidebar.php'; ?>
            <?php endif; ?>
            <?php if ($isStrictAdmin): ?>
            <div class="sidebar-divider">Documents</div>
            <a href="<?php echo ADMIN_URL; ?>/document-templates.php" class="<?php echo $currentPage === 'document-templates.php' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                Document Templates
            </a>
            <a href="<?php echo ADMIN_URL; ?>/documents.php" class="<?php echo $currentPage === 'documents.php' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                Generated Documents
            </a>
            <a href="<?php echo ADMIN_URL; ?>/documents-meta.php" class="<?php echo $currentPage === 'documents-meta.php' ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                Signature & Stamp
            </a>
            <?php endif; ?>
            <?php endif; // end general-only nav items ?>
        </nav>

        <div class="admin-sidebar-footer">
            <a href="<?php echo SITE_URL; ?>" target="_blank">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                View Website
            </a>
            <a href="<?php echo SITE_URL; ?>/logout.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Logout
            </a>
        </div>
    </aside>

    <main class="admin-content">
