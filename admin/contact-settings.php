<?php
$pageTitle = 'Contact & Social Settings';
require_once __DIR__ . '/includes/header.php';

$tab = $_GET['tab'] ?? 'contact';
$currentSection = getCurrentSection();

// Ensure social links exist for this section
function ensureSocialLinksForSection($pdo, $section) {
    $platforms = ['facebook', 'instagram', 'twitter', 'youtube', 'linkedin', 'telegram', 'whatsapp', 'github', 'pinterest', 'threads'];
    $existing = $pdo->prepare("SELECT platform FROM social_links WHERE product_type = ?");
    $existing->execute([$section]);
    $existingPlatforms = $existing->fetchAll(PDO::FETCH_COLUMN);
    $insert = $pdo->prepare("INSERT IGNORE INTO social_links (platform, url, label, sort_order, product_type) VALUES (?, ?, ?, ?, ?)");
    foreach ($platforms as $i => $platform) {
        if (!in_array($platform, $existingPlatforms)) {
            $insert->execute([$platform, '', ucfirst($platform), $i + 1, $section]);
        }
    }
}
ensureSocialLinksForSection($pdo, $currentSection);

// Handle contact info update (stored per-section as settings prefix)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'contact') {
    $token = $_POST['csrf_token'] ?? '';
    if ($token !== ($_SESSION['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid security token.');
        redirect(ADMIN_URL . '/contact-settings.php?tab=contact');
    }
    $settings = [
        'contact_email' => sanitize($_POST['contact_email']),
        'contact_phone' => sanitize($_POST['contact_phone']),
        'contact_address' => sanitize($_POST['contact_address']),
        'contact_hours' => sanitize($_POST['contact_hours']),
        'contact_map_embed' => sanitizeHtml($_POST['contact_map_embed']),
    ];

    $prefix = $currentSection !== 'general' ? $currentSection . '_' : '';
    foreach ($settings as $key => $value) {
        updateSetting($prefix . $key, $value);
    }

    setFlash('success', 'Contact information updated.');
    redirect(ADMIN_URL . '/contact-settings.php?tab=contact');
}

// Handle social link update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'social') {
    $token = $_POST['csrf_token'] ?? '';
    if ($token !== ($_SESSION['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid security token.');
        redirect(ADMIN_URL . '/contact-settings.php?tab=social');
    }
    $socials = $pdo->prepare("SELECT id, platform FROM social_links WHERE product_type = ? ORDER BY sort_order ASC");
    $socials->execute([$currentSection]);
    $socials = $socials->fetchAll();

    foreach ($socials as $social) {
        $url = sanitize($_POST['social_' . $social['platform']] ?? '');
        $active = isset($_POST['active_' . $social['platform']]) ? 1 : 0;
        $pdo->prepare("UPDATE social_links SET url = ?, is_active = ? WHERE platform = ? AND product_type = ?")->execute([$url, $active, $social['platform'], $currentSection]);
    }

    setFlash('success', 'Social media links updated.');
    redirect(ADMIN_URL . '/contact-settings.php?tab=social');
}

// Handle social link reorder
if (isset($_GET['reorder'])) {
    $token = $_POST['csrf_token'] ?? $_GET['token'] ?? '';
    if ($token !== ($_SESSION['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid security token.');
        redirect(ADMIN_URL . '/contact-settings.php?tab=social');
    }
    $order = $_POST['sort_order'] ?? [];
    foreach ($order as $platform => $sort) {
        $pdo->prepare("UPDATE social_links SET sort_order = ? WHERE platform = ? AND product_type = ?")->execute([(int)$sort, $platform, $currentSection]);
    }
    setFlash('success', 'Social links reordered.');
    redirect(ADMIN_URL . '/contact-settings.php?tab=social');
}

$stmt = $pdo->prepare("SELECT * FROM social_links WHERE product_type = ? ORDER BY sort_order ASC");
$stmt->execute([$currentSection]);
$socials = $stmt->fetchAll();
?>

<h2 class="mb-3">Contact & Social Settings</h2>

<div class="filters">
    <a href="?tab=contact" class="btn <?php echo $tab === 'contact' ? 'btn-primary' : 'btn-secondary'; ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        Contact Info
    </a>
    <a href="?tab=social" class="btn <?php echo $tab === 'social' ? 'btn-primary' : 'btn-secondary'; ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        Social Media
    </a>
    <a href="<?php echo SITE_URL; ?>/contact.php" target="_blank" class="btn btn-outline btn-sm">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
        Preview
    </a>
</div>

<?php if ($tab === 'contact'): ?>
<form method="POST">
    <div class="card">
        <div class="card-body">
            <h3 class="mb-3">Contact Information</h3>
            <p class="text-muted mb-3" style="font-size:0.85rem">This information will be displayed on the Contact Us page and footer.</p>

<?php $prefix = $currentSection !== 'general' ? $currentSection . '_' : ''; ?>
<?php if (!isset($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); } ?>
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div class="admin-form-grid">
                <div class="admin-form-group">
                    <label for="contact_email">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        Email Address
                    </label>
                    <input type="email" id="contact_email" name="contact_email" class="form-control" value="<?php echo getSetting($prefix . 'contact_email', getSetting('contact_email')); ?>" placeholder="support@example.com">
                </div>

                <div class="admin-form-group">
                    <label for="contact_phone">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        Phone Number
                    </label>
                    <input type="text" id="contact_phone" name="contact_phone" class="form-control" value="<?php echo getSetting($prefix . 'contact_phone', getSetting('contact_phone')); ?>" placeholder="+91 9876543210">
                </div>

            <div class="admin-form-group">
                <label for="contact_address">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="12" r="3"/></svg>
                    Office Address
                </label>
                <textarea id="contact_address" name="contact_address" class="form-control" rows="2" placeholder="Full office address"><?php echo getSetting($prefix . 'contact_address', getSetting('contact_address')); ?></textarea>
            </div>

            <div class="admin-form-group">
                <label for="contact_hours">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Support Hours
                </label>
                <input type="text" id="contact_hours" name="contact_hours" class="form-control" value="<?php echo getSetting($prefix . 'contact_hours', getSetting('contact_hours')); ?>" placeholder="Monday - Friday: 9AM - 6PM">
            </div>

            <div class="admin-form-group">
                <label for="contact_map_embed">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>
                    Google Maps Embed (iframe code)
                </label>
                <textarea id="contact_map_embed" name="contact_map_embed" class="form-control" rows="4" placeholder='<iframe src="https://www.google.com/maps/embed?..." width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>'><?php echo htmlspecialchars(getSetting($prefix . 'contact_map_embed', getSetting('contact_map_embed'))); ?></textarea>
                <p class="text-muted mt-1" style="font-size:0.8rem">Go to Google Maps → Share → Embed a map → Copy HTML</p>
            </div>

            <button type="submit" class="btn btn-primary mt-3">Save Contact Info</button>
        </div>
    </div>
</form>

<?php elseif ($tab === 'social'): ?>
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
    <div class="card">
        <div class="card-body">
            <h3 class="mb-3">Social Media Links</h3>
            <p class="text-muted mb-3" style="font-size:0.85rem">Add your social media profile URLs. Only active links will be displayed on the site.</p>

            <div class="social-links-grid">
                <?php foreach ($socials as $social): ?>
                <div class="social-link-item <?php echo $social['is_active'] ? '' : 'inactive'; ?>">
                    <div class="social-link-header">
                        <div class="social-link-icon social-<?php echo $social['platform']; ?>">
                            <?php echo getSocialIcon($social['platform']); ?>
                        </div>
                        <div class="social-link-info">
                            <strong><?php echo ucfirst($social['platform']); ?></strong>
                            <span class="text-muted" style="font-size:0.75rem"><?php echo sanitize($social['label'] ?? $social['platform']); ?></span>
                        </div>
                        <label class="social-toggle">
                            <input type="checkbox" name="active_<?php echo $social['platform']; ?>" value="1" <?php echo $social['is_active'] ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="social-link-input">
                        <input type="url" name="social_<?php echo $social['platform']; ?>" class="form-control form-control-sm" value="<?php echo sanitize($social['url']); ?>" placeholder="https://<?php echo $social['platform']; ?>.com/yourprofile">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <button type="submit" class="btn btn-primary btn-lg mt-3">Save Social Links</button>
        </div>
    </div>
</form>

<style>
.social-links-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1rem;
}
.social-link-item {
    background: var(--bg-tertiary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1rem;
    transition: var(--transition);
}
.social-link-item.inactive {
    opacity: 0.5;
}
.social-link-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
}
.social-link-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.social-link-icon svg { width: 20px; height: 20px; }
.social-facebook { background: rgba(24,119,242,0.15); color: #1877F2; }
.social-instagram { background: rgba(225,48,108,0.15); color: #E1306C; }
.social-twitter { background: rgba(0,0,0,0.15); color: var(--text-primary); }
.social-youtube { background: rgba(255,0,0,0.15); color: #FF0000; }
.social-linkedin { background: rgba(0,119,181,0.15); color: #0077B5; }
.social-telegram { background: rgba(0,136,204,0.15); color: #0088CC; }
.social-whatsapp { background: rgba(37,211,102,0.15); color: #25D366; }
.social-github { background: rgba(110,118,129,0.15); color: #6E7681; }
.social-pinterest { background: rgba(230,0,35,0.15); color: #E60023; }
.social-threads { background: rgba(0,0,0,0.15); color: var(--text-primary); }
.social-link-info { flex: 1; }
.social-link-input { margin-top: 0.5rem; }

.toggle-slider {
    position: relative;
    display: inline-block;
    width: 36px;
    height: 20px;
    background: var(--border-color);
    border-radius: 10px;
    cursor: pointer;
    transition: var(--transition);
}
.toggle-slider::after {
    content: '';
    position: absolute;
    top: 2px;
    left: 2px;
    width: 16px;
    height: 16px;
    background: var(--text-inverse);
    border-radius: 50%;
    transition: var(--transition);
}
input[type="checkbox"]:checked + .toggle-slider {
    background: var(--primary);
}
input[type="checkbox"]:checked + .toggle-slider::after {
    transform: translateX(16px);
}
.social-toggle input[type="checkbox"] { display: none; }
.social-toggle { cursor: pointer; }
</style>
<?php endif; ?>

<?php
function getSocialIcon($platform) {
    $icons = [
        'facebook' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
        'instagram' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>',
        'twitter' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
        'youtube' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>',
        'linkedin' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
        'telegram' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>',
        'whatsapp' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>',
        'github' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 2.41 1.005.695-.19 1.44-.285 2.19-.285.75 0 1.495.095 2.19.285 1.405-1.327 2.41-1.005 2.41-1.005.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>',
        'pinterest' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.668.967-2.914 2.171-2.914 1.023 0 1.518.769 1.518 1.69 0 1.029-.655 2.568-.994 3.995-.283 1.194.599 2.169 1.777 2.169 2.133 0 3.772-2.249 3.772-5.495 0-2.873-2.064-4.882-5.012-4.882-3.414 0-5.418 2.561-5.418 5.207 0 1.031.397 2.138.893 2.738a.36.36 0 01.083.345l-.333 1.36c-.053.22-.174.267-.402.161-1.499-.698-2.436-2.889-2.436-4.649 0-3.785 2.75-7.262 7.929-7.262 4.163 0 7.398 2.967 7.398 6.931 0 4.136-2.607 7.464-6.227 7.464-1.216 0-2.359-.631-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24 12.017 24c6.624 0 11.99-5.367 11.99-11.988C24.007 5.367 18.641.001 12.017.001z"/></svg>',
        'threads' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12.186 24h-.007c-3.581-.024-6.334-1.205-8.184-3.509C2.35 18.44 1.5 15.586 1.472 12.01v-.017c.03-3.579.879-6.43 2.525-8.482C5.845 1.205 8.6 0 12.186 0h.007c2.786.02 5.086.968 6.826 2.816 1.677 1.785 2.576 4.29 2.672 7.444v.01c-.097 3.155-.996 5.66-2.672 7.444-1.74 1.848-4.04 2.796-6.826 2.816l-.007-.01zm-.168-21.27c-3.013.02-5.283 1.043-6.754 3.038C3.87 7.67 3.127 10.16 3.098 13.21c.03 3.05.772 5.54 2.166 7.44 1.47 2 3.74 3.02 6.754 3.04 3.013-.02 5.283-1.043 6.754-3.038 1.394-1.9 2.136-4.39 2.166-7.44-.03-3.05-.772-5.54-2.166-7.44-1.47-2-3.74-3.02-6.754-3.04z"/></svg>',
    ];
    return $icons[$platform] ?? '';
}
?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
