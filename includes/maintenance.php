<?php
// Maintenance Mode - Helpers
// Modes: banner | popup | full
// Settings stored in `settings` table via getSetting()/updateSetting()

function getMaintenanceSettings() {
    return [
        'enabled' => getSetting('maintenance_enabled', '0') === '1',
        'mode'    => getSetting('maintenance_mode', 'banner'),
        'title'   => getSetting('maintenance_title', "We're performing maintenance"),
        'message' => getSetting('maintenance_message', 'We are currently working on our website to make it better for you. Please check back in a little while.'),
    ];
}

// Should this visitor be allowed through full-page maintenance?
// During maintenance ONLY admins (admin / super_admin) can access the website.
// Regular users and developers are blocked completely.
function maintenanceIsPrivileged() {
    $role = $_SESSION['user_role'] ?? '';

    // Logged-in admins can access everything
    if (in_array($role, ['admin', 'super_admin'])) {
        return true;
    }

    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $bypassScripts = [
        'login.php',
        'logout.php',
        'forgot-password.php',
        'reset-password.php',
        'payment-callback.php',
        'subscription-callback.php',
        'process-payment.php',
    ];
    if (in_array($script, $bypassScripts)) {
        return true;
    }

    $path = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    if (strpos($path, '/admin/') !== false) {
        // Block already-logged-in non-admin (developer / user) from the admin panel
        if ($role !== '' && !in_array($role, ['admin', 'super_admin'])) {
            return false;
        }
        // Allow the admin login page / admin panel for logged-out visitors and admins
        return true;
    }

    return false;
}

// Full-page maintenance block — called from config.php
function checkMaintenanceFullPage() {
    if (maintenanceIsPrivileged()) {
        return;
    }
    $m = getMaintenanceSettings();
    if (!$m['enabled'] || $m['mode'] !== 'full') {
        return;
    }
    http_response_code(503);
    header('Retry-After: 3600');
    header('Cache-Control: no-store, no-cache, must-revalidate');

    $siteName = sanitize(getSetting('site_name', SITE_NAME));
    $title    = sanitize($m['title']);
    $message  = nl2br(sanitize($m['message']));
    $year     = date('Y');
    $userRole   = $_SESSION['user_role'] ?? '';
    $isLoggedIn = !empty($_SESSION['user_id']);
    $isAdminUser = in_array($userRole, ['admin', 'super_admin']);
    $isNonAdmin  = $isLoggedIn && !$isAdminUser;
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - <?php echo $siteName; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>(function(){try{var t=localStorage.getItem('theme')||'dark';document.documentElement.setAttribute('data-theme',t);}catch(e){}})();</script>
    <style>
        :root, [data-theme="dark"] {
            --bg: #0b0b14;
            --bg2: #13131d;
            --card: #171723;
            --border: #222235;
            --text: #e6e8f0;
            --text-muted: #8b8fa3;
            --primary: #00f0ff;
            --primary-glow: rgba(0, 240, 255, 0.35);
            --accent: #818cf8;
        }
        [data-theme="light"] {
            --bg: #f4f6fb;
            --bg2: #ffffff;
            --card: #ffffff;
            --border: #e2e8f0;
            --text: #0f172a;
            --text-muted: #64748b;
            --primary: #4f46e5;
            --primary-glow: rgba(79, 70, 229, 0.25);
            --accent: #4f46e5;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            position: relative;
            overflow: hidden;
        }
        body::before {
            content: "";
            position: fixed;
            top: -30%;
            left: -20%;
            width: 700px;
            height: 700px;
            background: radial-gradient(circle, var(--primary-glow) 0%, transparent 65%);
            border-radius: 50%;
            opacity: 0.5;
            animation: drift 9s ease-in-out infinite;
        }
        body::after {
            content: "";
            position: fixed;
            bottom: -35%;
            right: -15%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(129, 140, 248, 0.28) 0%, transparent 65%);
            border-radius: 50%;
            opacity: 0.5;
            animation: drift 12s ease-in-out infinite reverse;
        }
        @keyframes drift {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(30px, -20px); }
        }
        .card {
            position: relative;
            z-index: 2;
            max-width: 560px;
            width: 100%;
            text-align: center;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 3rem 2.5rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
            backdrop-filter: blur(8px);
        }
        .icon-wrap {
            width: 84px;
            height: 84px;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 24px;
            background: var(--primary-glow);
            color: var(--primary);
            animation: pulse 2.2s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 var(--primary-glow); }
            50% { box-shadow: 0 0 0 18px transparent; }
        }
        .pill {
            display: inline-block;
            padding: 0.35rem 0.9rem;
            border-radius: 50px;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            background: var(--primary-glow);
            color: var(--primary);
            border: 1px solid var(--border);
            margin-bottom: 1rem;
        }
        h1 {
            font-size: 1.9rem;
            font-weight: 800;
            margin-bottom: 0.9rem;
            background: linear-gradient(135deg, var(--text) 0%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .msg {
            font-size: 1rem;
            color: var(--text-muted);
            margin-bottom: 2rem;
        }
        .btn {
            display: inline-block;
            padding: 0.7rem 1.6rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            color: #fff;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 8px 24px var(--primary-glow); }
        .site {
            margin-top: 2.2rem;
            padding-top: 1.2rem;
            border-top: 1px dashed var(--border);
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        .site strong { color: var(--text); font-weight: 700; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
        </div>
        <span class="pill">Under Maintenance</span>
        <h1><?php echo $title; ?></h1>
        <div class="msg"><?php echo $message; ?></div>
        <?php if ($isAdminUser): ?>
        <a href="<?php echo ADMIN_URL; ?>" class="btn">Go to Admin Panel</a>
        <?php elseif ($isNonAdmin): ?>
        <div style="margin-top:0.25rem;font-size:0.82rem;color:var(--text-muted)">You are logged in as a <strong><?php echo sanitize($userRole); ?></strong>, but only administrators can access the site during maintenance.</div>
        <a href="<?php echo SITE_URL; ?>/logout.php" class="btn" style="margin-top:1.5rem">Logout &amp; Login as Admin</a>
        <?php else: ?>
        <a href="<?php echo SITE_URL; ?>/login.php" class="btn">Back to Login</a>
        <?php endif; ?>
        <div class="site">&copy; <?php echo $year; ?> <strong><?php echo $siteName; ?></strong></div>
    </div>
</body>
</html>
    <?php
    exit;
}

// Banner / popup notice — called from includes/header.php
function renderMaintenanceNotice() {
    $m = getMaintenanceSettings();
    if (!$m['enabled'] || $m['mode'] === 'full') {
        return;
    }
    $title   = sanitize($m['title']);
    $message = nl2br(sanitize($m['message']));

    if ($m['mode'] === 'banner') {
        ?>
        <div class="maintenance-banner" style="display:flex;align-items:center;gap:0.75rem;justify-content:center;flex-wrap:wrap;padding:0.6rem 1rem;background:linear-gradient(90deg,var(--primary) 0%,var(--secondary,var(--primary-dark)) 100%);color:#fff;font-size:0.85rem;font-weight:500;position:relative;z-index:1200;text-align:center">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
            <span><strong><?php echo $title; ?></strong> <?php echo $message; ?></span>
        </div>
        <?php
    } elseif ($m['mode'] === 'popup') {
        ?>
        <div class="maintenance-popup-overlay" id="maintenancePopup" style="position:fixed;inset:0;background:rgba(0,0,0,0.6);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;z-index:9999;opacity:0;visibility:hidden;transition:opacity .25s,visibility .25s">
            <div class="maintenance-popup-card" style="background:var(--bg-card);border:1px solid var(--border-color);border-radius:18px;max-width:420px;width:calc(100% - 2rem);padding:2rem;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.4);position:relative">
                <button type="button" class="maintenance-popup-close" aria-label="Close" style="position:absolute;top:10px;right:12px;background:none;border:none;font-size:22px;color:var(--text-muted);cursor:pointer">&times;</button>
                <div style="width:64px;height:64px;margin:0 auto 1rem;display:flex;align-items:center;justify-content:center;border-radius:18px;background:var(--primary-glow-sm, rgba(74,108,247,.15));color:var(--primary)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                </div>
                <h3 style="margin:0 0 0.5rem;font-weight:700;font-size:1.15rem;color:var(--text-primary)"><?php echo $title; ?></h3>
                <div style="color:var(--text-secondary);font-size:0.9rem;margin-bottom:1.25rem"><?php echo $message; ?></div>
                <button type="button" class="btn btn-sm maintenance-popup-close-btn" style="background:linear-gradient(135deg,var(--primary) 0%,var(--secondary,var(--primary-dark)) 100%);color:#fff;border:none;padding:0.55rem 1.4rem;border-radius:10px;font-weight:600;cursor:pointer">Got it</button>
            </div>
        </div>
        <script>
        (function(){
            var overlay = document.getElementById('maintenancePopup');
            if (!overlay) return;
            function close(){
                overlay.style.opacity = '0';
                overlay.style.visibility = 'hidden';
            }
            overlay.querySelector('.maintenance-popup-close').addEventListener('click', close);
            var gotIt = overlay.querySelector('.maintenance-popup-close-btn');
            if (gotIt) gotIt.addEventListener('click', close);
            overlay.addEventListener('click', function(e){ if (e.target === overlay) close(); });
            setTimeout(function(){
                overlay.style.opacity = '1';
                overlay.style.visibility = 'visible';
            }, 400);
        })();
        </script>
        <?php
    }
}
