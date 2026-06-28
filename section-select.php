<?php
require_once __DIR__ . '/config.php';
requireLogin();
$pageTitle = 'Select Section';
$currentSection = getCurrentSection();
$userRole = $_SESSION['user_role'] ?? 'user';
$isAdmin = in_array($userRole, ['admin', 'super_admin', 'developer']);

// Route developers without active subscription to subscription page
if ($userRole === 'developer') {
    try {
        require_once __DIR__ . '/classes/Subscription.php';
        $subCheck = new Subscription($pdo);
        if ($subCheck->isDeveloperRestricted((int)$_SESSION['user_id'])) {
            $redirectToSub = true;
        }
    } catch (PDOException $e) {
        $redirectToSub = false;
    }
}

// Handle selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['section'])) {
    $section = sanitize($_POST['section']);
    $validTypes = array_merge(['general'], array_keys(getProductTypes()));
    if (in_array($section, $validTypes)) {
        setCurrentSection($section);
        $_SESSION['current_section'] = $section;
    }
    if ($isAdmin) {
        if (!empty($redirectToSub)) {
            setFlash('info', 'Please subscribe to a plan to access this feature.');
            redirect(SITE_URL . '/developer/subscription.php');
        }
        redirect(ADMIN_URL . '/index.php');
    }
    redirect(SITE_URL . '/products.php');
}

$sections = [
    'general' => ['label' => 'General', 'desc' => 'Main site overview and products', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>'],
    'mobile_apps' => ['label' => 'Mobile Apps', 'desc' => 'Android & iOS applications', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>'],
    'pc_software' => ['label' => 'PC Software', 'desc' => 'Windows desktop applications & tools', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>'],
    'mobile_games' => ['label' => 'Mobile Games', 'desc' => 'Games for Android & iOS devices', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M6 12h4M8 10v4"/><circle cx="16" cy="12" r="1"/><circle cx="19" cy="12" r="1"/></svg>'],
    'pc_games' => ['label' => 'PC Games', 'desc' => 'Windows & cross-platform games', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="6" y1="11" x2="10" y2="11"/><line x1="8" y1="9" x2="8" y2="13"/><line x1="15" y1="12" x2="15.01" y2="12"/><line x1="18" y1="10" x2="18.01" y2="10"/><path d="M17.32 5H6.68a4 4 0 0 0-3.978 3.59c-.006.052-.01.101-.017.152C2.604 9.416 2 14.456 2 16a3 3 0 0 0 3 3c1 0 1.5-.5 2-1l1.414-1.414A2 2 0 0 1 9.828 16h4.344a2 2 0 0 1 1.414.586L17 18c.5.5 1 1 2 1a3 3 0 0 0 3-3c0-1.545-.604-6.584-.685-7.258-.007-.05-.011-.1-.017-.151A4 4 0 0 0 17.32 5z"/></svg>'],
    'mac_software' => ['label' => 'Mac Software', 'desc' => 'macOS applications & utilities', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="12" rx="2"/><path d="M8 20h8"/><path d="M12 16v4"/></svg>'],
    'mac_apps' => ['label' => 'Mac Apps', 'desc' => 'App Store & productivity apps for Mac', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20.94c1.5 0 2.75 1.06 4 1.06 3 0 6-8 6-12.22A4.91 4.91 0 0 0 17 5c-2.22 0-4 1.44-5 2-1-.56-2.78-2-5-2a4.9 4.9 0 0 0-5 4.78C2 14 5 22 8 22c1.25 0 2.5-1.06 4-1.06z"/></svg>'],
    'documents' => ['label' => 'Documents', 'desc' => 'PDFs, templates & document resources', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>'],
    'resources' => ['label' => 'Resources', 'desc' => 'Educational & reference materials', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>'],
    'movies' => ['label' => 'Movies', 'desc' => 'Film & video entertainment', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"/><line x1="7" y1="2" x2="7" y2="22"/><line x1="17" y1="2" x2="17" y2="22"/><line x1="2" y1="12" x2="22" y2="12"/><line x1="2" y1="7" x2="7" y2="7"/><line x1="2" y1="17" x2="7" y2="17"/><line x1="17" y1="17" x2="22" y2="17"/><line x1="17" y1="7" x2="22" y2="7"/></svg>'],
    'courses' => ['label' => 'Courses', 'desc' => 'Online learning & tutorial courses', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>'],
    'cricket_highlights' => ['label' => 'Cricket Highlights', 'desc' => 'Cricket match highlights & clips', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/></svg>'],
    'web_series' => ['label' => 'Web Series', 'desc' => 'Premium web series episodes & collections', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>'],
    'books' => ['label' => 'Books', 'desc' => 'eBooks, guides & educational books', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>'],
    'premium_apps' => ['label' => 'Premium Apps', 'desc' => 'Premium mobile & desktop applications', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>'],
    'crack_software' => ['label' => 'Crack Software PC/Mac', 'desc' => 'Cracked software for Windows & macOS', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>'],
    'modded_games' => ['label' => 'Modded Games', 'desc' => 'Modified games with unlocked features', 'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="6" y1="11" x2="10" y2="11"/><line x1="8" y1="9" x2="8" y2="13"/><line x1="15" y1="12" x2="15.01" y2="12"/><line x1="18" y1="10" x2="18.01" y2="10"/><path d="M17.32 5H6.68a4 4 0 0 0-3.978 3.59c-.006.052-.01.101-.017.152C2.604 9.416 2 14.456 2 16a3 3 0 0 0 3 3c1 0 1.5-.5 2-1l1.414-1.414A2 2 0 0 1 9.828 16h4.344a2 2 0 0 1 1.414.586L17 18c.5.5 1 1 2 1a3 3 0 0 0 3-3c0-1.545-.604-6.584-.685-7.258-.007-.05-.011-.1-.017-.151A4 4 0 0 0 17.32 5z"/></svg>'],
];

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="section" style="text-align:center">
        <div style="margin-bottom:2rem">
            <h2>Select a Section</h2>
            <p class="text-muted">Choose a section to continue</p>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;max-width:900px;margin:0 auto">
            <?php foreach ($sections as $key => $sec): ?>
            <form method="POST" style="margin:0">
                <input type="hidden" name="section" value="<?php echo $key; ?>">
                <button type="submit" class="section-card <?php echo $currentSection === $key ? 'active' : ''; ?>" style="width:100%;cursor:pointer;text-align:center;padding:1.5rem 1rem;border:2px solid var(--border-color);border-radius:var(--border-radius);background:var(--bg-secondary);transition:all 0.2s;font-family:inherit;color:inherit">
                    <div style="margin-bottom:0.75rem;color:var(--primary)"><?php echo $sec['icon']; ?></div>
                    <h4 style="margin:0 0 0.25rem;font-size:1rem"><?php echo $sec['label']; ?></h4>
                    <p style="margin:0;font-size:0.8rem;color:var(--text-muted)"><?php echo $sec['desc']; ?></p>
                </button>
            </form>
            <?php endforeach; ?>
        </div>

        <div style="margin-top:2rem">
            <a href="<?php echo $isAdmin ? ADMIN_URL . '/index.php' : SITE_URL . '/products.php'; ?>" class="btn btn-outline">
                Skip — Go to <?php echo $isAdmin ? 'Dashboard' : 'Products'; ?>
            </a>
        </div>
    </div>
</div>

<style>
.section-card:hover, .section-card:focus-visible {
    border-color: var(--primary) !important;
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(99,102,241,0.1);
}
.section-card.active {
    border-color: var(--primary) !important;
    background: rgba(99,102,241,0.05) !important;
}
.section-card:active {
    transform: translateY(-1px);
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
