<?php
$pageTitle = 'Plan Pricing';
require_once __DIR__ . '/includes/header.php';
requireStrictAdmin();

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../includes/currency_converter.php';

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_pricing'])) {
    $token = sanitize($_POST['csrf_token'] ?? '');
    if ($token !== ($_SESSION['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid security token.');
        redirect(ADMIN_URL . '/plan-pricing.php');
    }

    $plans = ['starter', 'business', 'professional'];
    $plansLabel = ['starter' => 'Starter', 'business' => 'Business', 'professional' => 'Professional'];

    foreach ($plans as $plan) {
        $price = (int)sanitize($_POST['price_' . $plan] ?? 0);
        $duration = (int)sanitize($_POST['duration_' . $plan] ?? 30);
        if ($price < 0) $price = 0;
        if ($duration < 1) $duration = 1;

        updateSetting('plan_price_' . $plan, $price);
        updateSetting('plan_duration_' . $plan, $duration);
    }

    setFlash('success', 'Plan pricing updated successfully!');
    redirect(ADMIN_URL . '/plan-pricing.php');
}

$prices = getPlanPrices();
$plans = [
    'starter' => ['label' => 'Starter', 'price' => $prices['starter'], 'duration' => getPlanDuration('starter'), 'desc' => 'Perfect for beginners starting their digital store'],
    'business' => ['label' => 'Business', 'price' => $prices['business'], 'duration' => getPlanDuration('business'), 'desc' => 'For growing developers with multiple products'],
    'professional' => ['label' => 'Professional', 'price' => $prices['professional'], 'duration' => getPlanDuration('professional'), 'desc' => 'Unlimited access for serious developers'],
];
?>
<div class="admin-page-header">
    <h2>Plan Pricing</h2>
</div>

<form method="POST" class="admin-form" style="max-width:800px">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1.25rem;margin-bottom:2rem">
        <?php foreach ($plans as $key => $plan): ?>
        <div class="admin-card" style="border-top:3px solid <?php echo $key === 'business' ? 'var(--primary)' : 'var(--border-color)'; ?>">
            <div class="admin-card-header">
                <h3 style="margin:0;text-transform:capitalize"><?php echo $plan['label']; ?></h3>
                <?php if ($key === 'business'): ?>
                <span class="badge badge-primary">POPULAR</span>
                <?php endif; ?>
            </div>
            <div class="admin-card-body">
                <p style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:1rem"><?php echo $plan['desc']; ?></p>
                <div class="form-group">
                    <label class="form-label">Price (₹)</label>
                    <input type="number" name="price_<?php echo $key; ?>" value="<?php echo $plan['price']; ?>" class="form-control" min="0" step="1" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Duration (days)</label>
                    <input type="number" name="duration_<?php echo $key; ?>" value="<?php echo $plan['duration']; ?>" class="form-control" min="1" step="1" required>
                </div>
                <div style="margin-top:0.75rem;padding:0.5rem 0.75rem;background:var(--bg-secondary);border-radius:var(--border-radius-sm);font-size:0.85rem;color:var(--text-secondary)">
                    Current: <strong><?php echo formatINR($plan['price']); ?></strong> / <?php echo $plan['duration']; ?> day<?php echo $plan['duration'] > 1 ? 's' : ''; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="admin-actions">
        <button type="submit" name="save_pricing" class="btn btn-primary">Save Pricing</button>
        <a href="<?php echo ADMIN_URL; ?>/subscriptions.php" class="btn btn-outline">Cancel</a>
    </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
