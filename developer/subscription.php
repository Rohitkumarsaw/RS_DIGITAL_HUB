<?php
$pageTitle = 'My Subscription';
require_once __DIR__ . '/../admin/includes/header.php';

require_once __DIR__ . '/../classes/Subscription.php';
require_once __DIR__ . '/../classes/PlanFeature.php';
require_once __DIR__ . '/../includes/currency_converter.php';

$sub = new Subscription($pdo);
$pf = new PlanFeature($pdo);
$userId = $_SESSION['user_id'];

$activeSubscription = $sub->getActiveByDeveloper($userId);
$allSubscriptions = $sub->getByDeveloper($userId);

$planPrices = getPlanPrices();

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<div class="admin-page-header">
    <h2>My Subscription</h2>
</div>

<?php if ($activeSubscription): ?>
<?php
$daysRemaining = $sub->getDaysRemaining($activeSubscription['expiry_date']);
$features = $pf->getByPlan($activeSubscription['plan_name']);
?>
<div class="admin-card" style="border-color:var(--primary);border-width:2px">
    <div class="admin-card-header">
        <h3 style="margin:0;text-transform:capitalize"><?php echo $activeSubscription['plan_name']; ?> Plan</h3>
        <span class="badge badge-success">Active</span>
    </div>
    <div class="admin-card-body">
        <div class="admin-stats-grid" style="grid-template-columns:repeat(2,1fr);gap:1rem;margin-bottom:1.5rem">
            <div class="admin-stat-card" style="padding:1rem">
                <div class="admin-stat-label">Purchase Date</div>
                <div class="admin-stat-value" style="font-size:1rem"><?php echo date('d M Y', strtotime($activeSubscription['purchase_date'])); ?></div>
            </div>
            <div class="admin-stat-card" style="padding:1rem">
                <div class="admin-stat-label">Expiry Date</div>
                <div class="admin-stat-value" style="font-size:1rem"><?php echo date('d M Y', strtotime($activeSubscription['expiry_date'])); ?></div>
            </div>
            <div class="admin-stat-card" style="padding:1rem">
                <div class="admin-stat-label">Days Remaining</div>
                <div class="admin-stat-value" style="font-size:1rem;color:<?php echo $daysRemaining <= 7 ? 'var(--danger)' : 'var(--primary)'; ?>"><?php echo $daysRemaining; ?> days</div>
            </div>
            <div class="admin-stat-card" style="padding:1rem">
                <div class="admin-stat-label">Price</div>
                <div class="admin-stat-value" style="font-size:1rem"><?php echo formatINR($planPrices[$activeSubscription['plan_name']]); ?>/mo</div>
            </div>
        </div>

        <h4 style="margin-bottom:0.75rem">Features Included</h4>
        <div style="display:flex;flex-wrap:wrap;gap:0.5rem;margin-bottom:1.5rem">
            <?php if (empty($features)): ?>
            <p style="color:var(--text-muted);font-size:0.9rem">No features configured for this plan. Contact admin.</p>
            <?php else: ?>
            <?php foreach ($features as $f): ?>
            <span class="badge badge-<?php echo $f['is_enabled'] ? 'success' : 'danger'; ?>" style="<?php echo $f['is_enabled'] ? '' : 'opacity:0.5;'; ?>">
                <?php echo $pf->getFeatureLabel($f['feature_name']); ?>: <?php echo ($f['feature_value'] !== null && $f['feature_value'] !== '') ? sanitize($f['feature_value']) : ($f['is_enabled'] ? 'Yes' : 'No'); ?>
            </span>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="admin-actions" style="gap:0.75rem">
            <a href="<?php echo SITE_URL; ?>/plan-selection.php" class="btn btn-primary">Change Plan</a>
            <a href="<?php echo SITE_URL; ?>/invoices/generate_invoice.php?id=<?php echo $activeSubscription['id']; ?>" class="btn btn-outline" target="_blank">Download Invoice</a>
            <a href="<?php echo SITE_URL; ?>/processes/process_subscription.php?action=pause&id=<?php echo $activeSubscription['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-outline" onclick="showConfirm('Pause your subscription?',this.href);return false">Pause Subscription</a>
            <a href="<?php echo SITE_URL; ?>/processes/process_subscription.php?action=cancel&id=<?php echo $activeSubscription['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-danger" onclick="showConfirm('Cancel your subscription? Your dashboard will be restricted.',this.href);return false">Cancel Subscription</a>
        </div>
    </div>
</div>

<?php elseif (!empty($allSubscriptions)): ?>
<?php $lastSub = $allSubscriptions[0]; ?>
<div class="admin-card">
    <div class="admin-card-header">
        <h3 style="margin:0">Subscription Expired / <?php echo ucfirst($lastSub['status']); ?></h3>
        <span class="badge badge-<?php echo $lastSub['status'] === 'paused' ? 'warning' : 'danger'; ?>"><?php echo ucfirst($lastSub['status']); ?></span>
    </div>
    <div class="admin-card-body">
        <p>Your <?php echo ucfirst($lastSub['plan_name']); ?> plan has <?php echo $lastSub['status']; ?>.</p>
        <?php if ($lastSub['status'] === 'paused'): ?>
        <p>Your subscription is paused. You can resume it anytime to regain access.</p>
        <div class="admin-actions" style="gap:0.75rem">
            <a href="<?php echo SITE_URL; ?>/processes/process_subscription.php?action=resume&id=<?php echo $lastSub['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-primary btn-lg">Resume Subscription</a>
            <a href="<?php echo SITE_URL; ?>/plan-selection.php" class="btn btn-outline btn-lg">Change Plan</a>
        </div>
        <?php else: ?>
        <p>Your dashboard access is currently restricted. Please renew or subscribe to a new plan to regain access.</p>
        <a href="<?php echo SITE_URL; ?>/plan-selection.php" class="btn btn-primary btn-lg">View Plans & Renew</a>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<div class="admin-empty">
    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
    <h3>No Active Subscription</h3>
    <p>Subscribe to a plan to unlock all developer features.</p>
    <a href="<?php echo SITE_URL; ?>/plan-selection.php" class="btn btn-primary btn-lg">View Plans</a>
</div>
<?php endif; ?>

<?php
// Pending subscriptions - always show if any
$pendingSubs = $sub->getPendingByDeveloper($userId);
if (!empty($pendingSubs)):
?>
<div class="admin-card" style="border-color:var(--warning);margin-top:1.5rem">
    <div class="admin-card-header">
        <h3 style="margin:0">Pending Subscription</h3>
        <span class="badge badge-warning">Pending Payment</span>
    </div>
    <div class="admin-card-body">
        <?php foreach ($pendingSubs as $ps): ?>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;padding:0.75rem 0;border-bottom:1px solid var(--border-color)">
            <div>
                <strong style="text-transform:capitalize"><?php echo $ps['plan_name']; ?> Plan</strong>
                <span style="color:var(--text-muted);font-size:0.85rem;margin-left:0.5rem"><?php echo date('d M Y h:i A', strtotime($ps['created_at'])); ?></span>
            </div>
            <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap">
                <span style="font-size:0.85rem;color:var(--text-muted)"><?php echo formatINR($planPrices[$ps['plan_name']]); ?></span>
                <a href="<?php echo SITE_URL; ?>/plan-selection.php?plan=<?php echo $ps['plan_name']; ?>" class="btn btn-sm btn-primary">Complete Payment</a>
                <a href="<?php echo SITE_URL; ?>/processes/process_subscription.php?action=delete&id=<?php echo $ps['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-sm btn-danger" onclick="showConfirm('Delete this pending subscription?',this.href);return false">Delete</a>
            </div>
        </div>
        <?php endforeach; ?>
        <p style="margin-top:0.75rem;font-size:0.85rem;color:var(--text-muted)">Payment pending. Click "Complete Payment" to retry or "Delete" to start fresh with a different plan.</p>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($allSubscriptions)): ?>
<h3 style="margin-top:2rem;margin-bottom:1rem">Subscription History</h3>
<div class="admin-table-wrapper">
    <table class="admin-table">
        <thead><tr><th>Plan</th><th>Start Date</th><th>Expiry Date</th><th>Status</th></tr></thead>
        <tbody>
            <?php foreach ($allSubscriptions as $s): ?>
            <tr>
                <td style="text-transform:capitalize"><?php echo $s['plan_name']; ?></td>
                <td><?php echo date('d M Y', strtotime($s['purchase_date'])); ?></td>
                <td><?php echo date('d M Y', strtotime($s['expiry_date'])); ?></td>
                <td><span class="badge badge-<?php echo $s['status'] === 'active' ? 'success' : ($s['status'] === 'paused' ? 'warning' : 'danger'); ?>"><?php echo ucfirst($s['status']); ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../admin/includes/footer.php'; ?>
