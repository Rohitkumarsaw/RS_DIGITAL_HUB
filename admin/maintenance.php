<?php
$pageTitle = 'Maintenance Mode';
require_once __DIR__ . '/includes/header.php';

if (!in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    setFlash('error', 'Access denied. Only administrators can manage maintenance mode.');
    redirect(ADMIN_URL . '/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if ($csrf !== ($_SESSION['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid security token.');
        redirect(ADMIN_URL . '/maintenance.php');
    }

    $enabled = isset($_POST['maintenance_enabled']) ? '1' : '0';
    $mode    = in_array($_POST['maintenance_mode'] ?? '', ['banner', 'popup', 'full']) ? $_POST['maintenance_mode'] : 'banner';
    $title   = sanitize($_POST['maintenance_title'] ?? '');
    $message = sanitize($_POST['maintenance_message'] ?? '');

    if (empty($title))   $title   = "We're performing maintenance";
    if (empty($message)) $message = 'We are currently working on our website to make it better for you. Please check back in a little while.';

    updateSetting('maintenance_enabled', $enabled);
    updateSetting('maintenance_mode', $mode);
    updateSetting('maintenance_title', $title);
    updateSetting('maintenance_message', $message);

    setFlash('success', $enabled === '1' ? 'Maintenance mode is now ON (' . ucfirst($mode) . ').' : 'Maintenance mode is now OFF.');
    redirect(ADMIN_URL . '/maintenance.php');
}

$m = getMaintenanceSettings();
$isOn = $m['enabled'];
$mode = $m['mode'];
?>
<style>
.mode-card{display:block;border:1.5px solid var(--border-color);border-radius:12px;padding:1rem 1.1rem;cursor:pointer;transition:border-color .15s,box-shadow .15s;background:var(--bg-card);margin-bottom:0.75rem}
.mode-card:hover{border-color:var(--primary)}
.mode-card input[type="radio"]{accent-color:var(--primary)}
.mode-card.selected{border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-glow-sm, rgba(74,108,247,.12))}
.status-dot{display:inline-block;width:10px;height:10px;border-radius:50%;margin-right:6px;vertical-align:middle}
.preview-scene{border:1px dashed var(--border-color);border-radius:12px;padding:1.25rem;margin-top:1rem;background:var(--bg-tertiary)}
</style>

<div class="d-flex justify-between align-center mb-3" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem">
    <h2 style="margin:0">Maintenance Mode</h2>
    <span class="badge <?php echo $isOn ? 'badge-danger' : 'badge-success'; ?>" style="font-size:0.8rem">
        <span class="status-dot" style="background:<?php echo $isOn ? 'var(--danger)' : 'var(--success)'; ?>"></span>
        <?php echo $isOn ? 'Maintenance ON' : 'Maintenance OFF'; ?>
        <?php if ($isOn): ?>&mdash; <?php echo ucfirst($mode); ?><?php endif; ?>
    </span>
</div>

<?php if ($isOn): ?>
<div class="alert <?php echo $mode === 'full' ? 'alert-danger' : 'alert-warning'; ?>" style="padding:0.8rem 1rem;border-radius:8px;background:<?php echo $mode === 'full' ? 'var(--danger-glow, rgba(239,68,68,.12))' : 'rgba(245,158,11,.12)'; ?>;border:1px solid <?php echo $mode === 'full' ? 'var(--danger)' : 'rgba(245,158,11,.35)'; ?>;color:var(--text-secondary);font-size:0.88rem;margin-bottom:1.25rem">
    <?php if ($mode === 'full'): ?>
        <strong>Full Page mode is active.</strong> Visitors are currently seeing a maintenance page. Only admins / developers can access the website right now.
    <?php else: ?>
        <strong><?php echo ucfirst($mode); ?> is active.</strong> Visitors are seeing the notice but the website still works normally.
    <?php endif; ?>
</div>
<?php endif; ?>

<form method="POST" id="maintForm">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">

    <div class="card">
        <div class="card-body">
            <h3 class="mb-2">Status</h3>
            <p class="text-muted mb-3" style="font-size:0.85rem">Turn maintenance mode on whenever you are working on the website, and off when done.</p>

            <label style="display:flex;align-items:center;gap:0.6rem;cursor:pointer;font-weight:600">
                <input type="checkbox" name="maintenance_enabled" id="maintenance_enabled" <?php echo $isOn ? 'checked' : ''; ?> style="width:20px;height:20px;accent-color:var(--primary)">
                Enable Maintenance Mode
            </label>

            <div class="form-group" style="margin-top:1.5rem">
                <label class="d-block mb-2"><strong>Maintenance Mode Type</strong></label>

                <label class="mode-card" data-mode="banner">
                    <div style="display:flex;align-items:flex-start;gap:0.75rem">
                        <input type="radio" name="maintenance_mode" value="banner" <?php echo $mode === 'banner' ? 'checked' : ''; ?> style="margin-top:3px">
                        <div>
                            <strong>Banner</strong>
                            <div style="font-size:0.82rem;color:var(--text-muted)">A dismissible notice bar at the top of the website. The site keeps working normally.</div>
                        </div>
                    </div>
                </label>

                <label class="mode-card" data-mode="popup">
                    <div style="display:flex;align-items:flex-start;gap:0.75rem">
                        <input type="radio" name="maintenance_mode" value="popup" <?php echo $mode === 'popup' ? 'checked' : ''; ?> style="margin-top:3px">
                        <div>
                            <strong>Popup</strong>
                            <div style="font-size:0.82rem;color:var(--text-muted)">A popup window visitors see once per session. The site keeps working normally.</div>
                        </div>
                    </div>
                </label>

                <label class="mode-card" data-mode="full">
                    <div style="display:flex;align-items:flex-start;gap:0.75rem">
                        <input type="radio" name="maintenance_mode" value="full" <?php echo $mode === 'full' ? 'checked' : ''; ?> style="margin-top:3px">
                        <div>
                            <strong>Full Page</strong>
                            <div style="font-size:0.82rem;color:var(--text-muted)">The entire website is replaced with a maintenance page. Only admins/developers can access it.</div>
                        </div>
                    </div>
                </label>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:1.5rem">
        <div class="card-body">
            <h3 class="mb-2">Notice Content</h3>
            <p class="text-muted mb-3" style="font-size:0.85rem">The message visitors will see when maintenance is on.</p>

            <div class="form-group">
                <label for="maintenance_title">Title</label>
                <input type="text" id="maintenance_title" name="maintenance_title" class="form-control" value="<?php echo sanitize($m['title']); ?>" placeholder="We're performing maintenance">
            </div>

            <div class="form-group">
                <label for="maintenance_message">Message</label>
                <textarea id="maintenance_message" name="maintenance_message" class="form-control" rows="4" placeholder="We are currently working on our website. Please check back soon."><?php echo sanitize($m['message']); ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-lg mt-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Save Maintenance Settings
            </button>
        </div>
    </div>
</form>

<!-- Live Preview -->
<div class="card" style="margin-top:1.5rem">
    <div class="card-body">
        <h3 class="mb-2">Live Preview</h3>
        <p class="text-muted mb-3" style="font-size:0.85rem">How the notice will look with the current settings.</p>
        <div id="previewContainer" class="preview-scene"></div>
    </div>
</div>

<script>
(function(){
    function esc(s){
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    var titleEl = document.getElementById('maintenance_title');
    var msgEl = document.getElementById('maintenance_message');
    var radios = document.querySelectorAll('input[name="maintenance_mode"]');
    var cards = document.querySelectorAll('.mode-card');
    var preview = document.getElementById('previewContainer');

    function currentMode(){ return document.querySelector('input[name="maintenance_mode"]:checked').value; }

    function refresh(){
        var t = esc(titleEl.value) || esc("We're performing maintenance");
        var mm = esc(msgEl.value.replace(/\n/g, '<br>')) || esc('We are currently working on our website. Please check back soon.');
        var mode = currentMode();
        var s = '';
        if (mode === 'banner') {
            s = '<div style="display:flex;align-items:center;gap:0.5rem;justify-content:center;flex-wrap:wrap;padding:0.55rem 1rem;background:linear-gradient(90deg,var(--primary) 0%,var(--primary-dark) 100%);color:#fff;font-size:0.85rem;border-radius:8px;text-align:center">' +
                '&#x1F527; <span><strong>' + t + '</strong> ' + mm + '</span></div>';
        } else if (mode === 'popup') {
            s = '<div style="position:relative;max-width:360px;margin:0 auto;background:var(--bg-card);border:1px solid var(--border-color);border-radius:16px;padding:1.5rem;text-align:center">' +
                '<div style="width:52px;height:52px;margin:0 auto 0.75rem;display:flex;align-items:center;justify-content:center;border-radius:14px;background:var(--primary-glow-sm, rgba(74,108,247,.15));color:var(--primary);font-size:24px">&#x1F527;</div>' +
                '<h4 style="margin:0 0 0.4rem;color:var(--text-primary)">' + t + '</h4>' +
                '<div style="color:var(--text-secondary);font-size:0.85rem">' + mm + '</div></div>';
        } else {
            s = '<div style="background:var(--bg-tertiary);border:1px solid var(--border-color);border-radius:14px;padding:2rem 1rem;text-align:center">' +
                '<div style="width:56px;height:56px;margin:0 auto 0.75rem;display:flex;align-items:center;justify-content:center;border-radius:16px;background:var(--primary-glow-sm, rgba(74,108,247,.15));color:var(--primary);font-size:26px">&#x1F527;</div>' +
                '<div style="font-size:0.7rem;letter-spacing:1px;text-transform:uppercase;color:var(--text-muted);margin-bottom:0.4rem">Under Maintenance</div>' +
                '<h4 style="margin:0 0 0.4rem;color:var(--text-primary)">' + t + '</h4>' +
                '<div style="color:var(--text-secondary);font-size:0.85rem;max-width:320px;margin:0 auto">' + mm + '</div></div>';
        }
        preview.innerHTML = s;
    }

    radios.forEach(function(r){ r.addEventListener('change', function(){ cards.forEach(function(c){ c.classList.toggle('selected', c.dataset.mode === currentMode()); }); refresh(); }); });
    titleEl.addEventListener('input', refresh);
    msgEl.addEventListener('input', refresh);
    cards.forEach(function(c){ c.addEventListener('click', function(){ var r = c.querySelector('input'); if (r) { r.checked = true; cards.forEach(function(x){ x.classList.toggle('selected', x === c); }); refresh(); } }); });
    cards.forEach(function(c){ c.classList.toggle('selected', c.dataset.mode === currentMode()); });
    refresh();
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
