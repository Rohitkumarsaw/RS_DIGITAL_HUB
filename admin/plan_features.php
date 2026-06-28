<?php
$pageTitle = 'Plan Features';
require_once __DIR__ . '/includes/header.php';
requireStrictAdmin();

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../classes/PlanFeature.php';
require_once __DIR__ . '/../classes/Subscription.php';
require_once __DIR__ . '/../includes/currency_converter.php';
$pf = new PlanFeature($pdo);

// Handle toggle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_features'])) {
    $token = sanitize($_POST['csrf_token'] ?? '');
    if ($token !== ($_SESSION['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid security token. Please try again.');
        redirect(ADMIN_URL . '/plan_features.php');
    }
    $planName = sanitize($_POST['plan_name']);
    $validPlans = ['starter', 'business', 'professional'];
    if (!in_array($planName, $validPlans)) {
        setFlash('error', 'Invalid plan name.');
        redirect(ADMIN_URL . '/plan_features.php');
    }
    $features = $pf->getByPlan($planName);
    if (empty($features)) {
        setFlash('error', 'No features found for ' . ucfirst($planName) . ' plan.');
        redirect(ADMIN_URL . '/plan_features.php');
    }
    foreach ($features as $f) {
        $enabled = isset($_POST['feature_' . $f['id']]) ? 1 : 0;
        $value = sanitize($_POST['value_' . $f['id']] ?? '');
        $pf->updateFeature($f['id'], $enabled, $value);
    }
    setFlash('success', 'Features updated for ' . ucfirst($planName) . ' plan.');
    redirect(ADMIN_URL . '/plan_features.php');
}

$plans = ['starter', 'business', 'professional'];
$planLabels = ['starter' => 'Starter', 'business' => 'Business', 'professional' => 'Professional'];
?>
<div class="admin-page-header">
    <h2>Plan Feature Configuration</h2>
</div>

<div class="row" style="display:flex;flex-wrap:wrap;gap:1.5rem">
    <?php foreach ($plans as $plan): ?>
    <?php $features = $pf->getByPlan($plan); ?>
    <div class="admin-card" style="flex:1;min-width:320px">
        <div class="admin-card-header">
            <h3 style="text-transform:capitalize;margin:0"><?php echo $planLabels[$plan]; ?></h3>
            <span class="badge badge-primary" style="text-transform:capitalize"><?php echo formatINR((new Subscription($pdo))->getPlanPrice($plan)); ?> /mo</span>
        </div>
        <div class="admin-card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                <input type="hidden" name="plan_name" value="<?php echo $plan; ?>">

                <?php if (empty($features)): ?>
                <p style="text-align:center;padding:1rem;color:var(--text-muted)">No features configured for this plan.</p>
                <?php else: ?>
                <?php foreach ($features as $f): ?>
                <div style="display:flex;align-items:center;gap:0.75rem;padding:0.75rem 0;border-bottom:1px solid var(--border-color)">
                    <label class="toggle-switch" style="position:relative;display:inline-block;width:44px;height:24px;cursor:pointer;flex-shrink:0">
                        <input type="checkbox" name="feature_<?php echo $f['id']; ?>" value="1" <?php echo $f['is_enabled'] ? 'checked' : ''; ?> style="opacity:0;width:0;height:0">
                        <span style="position:absolute;top:0;left:0;right:0;bottom:0;background:<?php echo $f['is_enabled'] ? 'var(--primary)' : 'var(--border-color)'; ?>;border-radius:24px;transition:0.3s">
                            <span style="position:absolute;top:2px;left:<?php echo $f['is_enabled'] ? '22px' : '2px'; ?>;width:20px;height:20px;background:var(--text-inverse);border-radius:50%;transition:0.3s"></span>
                        </span>
                    </label>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:0.9rem;font-weight:500"><?php echo $pf->getFeatureLabel($f['feature_name']); ?></div>
                        <input type="text" name="value_<?php echo $f['id']; ?>" value="<?php echo sanitize($f['feature_value'] ?? ''); ?>" placeholder="Value (e.g. 5 Projects)" style="width:100%;font-size:0.8rem;padding:0.25rem 0.5rem;border:1px solid var(--border-color);border-radius:4px;background:var(--bg-card);color:var(--text-primary);margin-top:0.25rem">
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>

                <button type="submit" name="update_features" class="btn btn-primary btn-block mt-3">Save <?php echo $planLabels[$plan]; ?> Features</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<style>
.toggle-switch input:checked + span { background: var(--primary); }
.toggle-switch input:checked + span span { left: 22px; }
</style>

<script>
document.querySelectorAll('.toggle-switch input').forEach(function(cb) {
    cb.addEventListener('change', function() {
        var bg = this.closest('.toggle-switch').querySelector('span');
        var dot = bg.querySelector('span');
        if (this.checked) {
            bg.style.background = 'var(--primary)';
            dot.style.left = '22px';
        } else {
            bg.style.background = 'var(--border-color)';
            dot.style.left = '2px';
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
