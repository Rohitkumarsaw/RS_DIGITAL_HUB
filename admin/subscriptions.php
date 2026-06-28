<?php
$pageTitle = 'Subscription Management';
require_once __DIR__ . '/includes/header.php';
requireStrictAdmin();

require_once __DIR__ . '/../classes/Subscription.php';
require_once __DIR__ . '/../classes/PlanFeature.php';
require_once __DIR__ . '/../includes/currency_converter.php';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$sub = new Subscription($pdo);

$action = $_GET['action'] ?? 'list';

if ($action === 'view' && isset($_GET['id'])) {
    $subscription = $sub->getById((int)$_GET['id']);
    if (!$subscription) {
        setFlash('error', 'Subscription not found.');
        redirect(ADMIN_URL . '/subscriptions.php');
    }
    $daysRemaining = $sub->getDaysRemaining($subscription['expiry_date']);

    $stmt = $pdo->prepare("SELECT * FROM subscription_invoices WHERE subscription_id = ? ORDER BY created_at DESC");
    $stmt->execute([$subscription['id']]);
    $invoices = $stmt->fetchAll();
    ?>
    <div class="admin-page-header">
        <h2>Subscription Details</h2>
        <a href="subscriptions.php" class="btn btn-secondary">Back to List</a>
    </div>

    <div class="admin-card">
        <div class="admin-card-header"><h3>Developer: <?php echo sanitize($subscription['developer_name']); ?></h3></div>
        <div class="admin-card-body">
            <div class="admin-stats-grid" style="grid-template-columns:repeat(2,1fr);gap:1rem;margin-bottom:1.5rem">
                <div class="admin-stat-card" style="padding:1rem">
                    <div class="admin-stat-label">Plan</div>
                    <div class="admin-stat-value" style="text-transform:capitalize"><?php echo $subscription['plan_name']; ?></div>
                </div>
                <div class="admin-stat-card" style="padding:1rem">
                    <div class="admin-stat-label">Status</div>
                    <div><span class="badge badge-<?php echo $subscription['status'] === 'active' ? 'success' : ($subscription['status'] === 'paused' ? 'warning' : 'danger'); ?>"><?php echo ucfirst($subscription['status']); ?></span></div>
                </div>
                <div class="admin-stat-card" style="padding:1rem">
                    <div class="admin-stat-label">Purchase Date</div>
                    <div class="admin-stat-value" style="font-size:1rem"><?php echo date('d M Y', strtotime($subscription['purchase_date'])); ?></div>
                </div>
                <div class="admin-stat-card" style="padding:1rem">
                    <div class="admin-stat-label">Expiry Date</div>
                    <div class="admin-stat-value" style="font-size:1rem"><?php echo date('d M Y', strtotime($subscription['expiry_date'])); ?></div>
                </div>
                <div class="admin-stat-card" style="padding:1rem">
                    <div class="admin-stat-label">Days Remaining</div>
                    <div class="admin-stat-value" style="font-size:1rem"><?php echo $daysRemaining; ?> days</div>
                </div>
                <div class="admin-stat-card" style="padding:1rem">
                    <div class="admin-stat-label">Price</div>
                    <div class="admin-stat-value" style="font-size:1rem"><?php echo formatINR($sub->getPlanPrice($subscription['plan_name'])); ?></div>
                </div>
            </div>

            <h4 style="margin-bottom:0.75rem">Features Included</h4>
            <?php
            $pf = new PlanFeature($pdo);
            $features = $pf->getByPlan($subscription['plan_name']);
            ?>
            <div style="display:flex;flex-wrap:wrap;gap:0.5rem;margin-bottom:1.5rem">
                <?php foreach ($features as $f): ?>
                <span class="badge badge-<?php echo $f['is_enabled'] ? 'success' : 'danger'; ?>"><?php echo $pf->getFeatureLabel($f['feature_name']); ?>: <?php echo $f['is_enabled'] ? sanitize($f['feature_value'] ?? $pf->getFeatureValue($subscription['plan_name'], $f['feature_name'])) : 'N/A'; ?></span>
                <?php endforeach; ?>
            </div>

            <?php
            $planPrice = $sub->getPlanPrice($subscription['plan_name']);
            $taxPercent = (float)getSetting('tax_percentage', 0);
            $taxAmount = ($planPrice * $taxPercent) / 100;
            $total = $planPrice + $taxAmount;
            ?>
            <h4 style="margin-bottom:0.75rem">Payment Summary</h4>
            <div style="display:flex;flex-wrap:wrap;gap:1.5rem;margin-bottom:1.5rem;padding:1rem;background:var(--bg-tertiary);border-radius:8px;font-size:14px">
                <div><span class="text-muted">Subtotal:</span> <strong><?php echo formatINR($planPrice); ?></strong></div>
                <div><span class="text-muted"><?php echo sanitize(getSetting('tax_type', 'Tax')); ?> (<?php echo $taxPercent; ?>%):</span> <strong><?php echo formatINR($taxAmount); ?></strong></div>
                <div><span class="text-muted">Total Paid:</span> <strong style="color:var(--primary)"><?php echo formatINR($total); ?></strong></div>
                <div><span class="text-muted">Payment Method:</span> <strong>Razorpay</strong></div>
            </div>

            <?php if (!empty($invoices)): ?>
            <h4 style="margin-bottom:0.75rem">Invoices</h4>
            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead><tr><th>Invoice #</th><th>Amount</th><th>Date</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($invoices as $inv): ?>
                        <tr>
                            <td><?php echo sanitize($inv['invoice_number']); ?></td>
                            <td><?php echo formatINR($total); ?></td>
                            <td><?php echo date('d M Y', strtotime($inv['created_at'])); ?></td>
                            <td><a href="<?php echo SITE_URL . '/' . $inv['invoice_path']; ?>" class="btn btn-sm btn-primary" target="_blank">Download Invoice</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
} else {
    $subscriptions = $sub->getAll();
    ?>
    <div class="admin-page-header">
        <h2>Subscription Management</h2>
        <div style="display:flex;gap:0.5rem;align-items:center">
            <a href="<?php echo SITE_URL; ?>/admin/pdf-preview.php?type=subscriptions" class="btn btn-sm btn-outline">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Export PDF
            </a>
        </div>
    </div>

    <?php if (empty($subscriptions)): ?>
    <div class="admin-empty">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
        <h3>No Subscriptions Yet</h3>
        <p>Developers will appear here once they subscribe to a plan.</p>
    </div>
    <?php else: ?>
    <div class="admin-table-wrapper">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Developer</th>
                    <th>Plan</th>
                    <th>Amount</th>
                    <th>Purchase Date</th>
                    <th>Expiry Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 0; foreach ($subscriptions as $s): $i++; ?>
                <tr>
                    <td><?php echo $i; ?></td>
                    <td><strong><?php echo sanitize($s['developer_name']); ?></strong><br><small style="color:var(--text-muted)"><?php echo sanitize($s['developer_email']); ?></small></td>
                    <td><span class="badge badge-primary" style="text-transform:capitalize"><?php echo $s['plan_name']; ?></span></td>
                    <td><?php echo formatINR($sub->getPlanPrice($s['plan_name'])); ?></td>
                    <td><?php echo date('d M Y', strtotime($s['purchase_date'])); ?></td>
                    <td><?php echo date('d M Y', strtotime($s['expiry_date'])); ?></td>
                    <td><span class="badge badge-<?php echo $s['status'] === 'active' ? 'success' : ($s['status'] === 'pending' ? 'warning' : ($s['status'] === 'paused' ? 'warning' : 'danger')); ?>"><?php echo ucfirst($s['status']); ?></span></td>
                    <td>
                        <div class="admin-actions">
                            <?php if ($s['status'] === 'active'): ?>
                            <a href="<?php echo SITE_URL; ?>/processes/process_subscription.php?action=pause&id=<?php echo $s['id']; ?>&token=<?php echo $_SESSION['csrf_token'] ?? ''; ?>" class="btn btn-sm btn-warning" onclick="showConfirm('Pause this subscription?',this.href);return false">Pause</a>
                            <a href="<?php echo SITE_URL; ?>/processes/process_subscription.php?action=cancel&id=<?php echo $s['id']; ?>&token=<?php echo $_SESSION['csrf_token'] ?? ''; ?>" class="btn btn-sm btn-danger" onclick="showConfirm('Cancel this subscription?',this.href);return false">Cancel</a>
                            <?php elseif ($s['status'] === 'pending'): ?>
                            <a href="<?php echo SITE_URL; ?>/processes/process_subscription.php?action=activate&id=<?php echo $s['id']; ?>&token=<?php echo $_SESSION['csrf_token'] ?? ''; ?>" class="btn btn-sm btn-success" onclick="showConfirm('Activate this pending subscription?',this.href);return false">Activate</a>
                            <a href="<?php echo SITE_URL; ?>/processes/process_subscription.php?action=delete&id=<?php echo $s['id']; ?>&token=<?php echo $_SESSION['csrf_token'] ?? ''; ?>" class="btn btn-sm btn-danger" onclick="showConfirm('Delete this pending subscription?',this.href);return false">Delete</a>
                            <?php elseif ($s['status'] === 'paused'): ?>
                            <a href="<?php echo SITE_URL; ?>/processes/process_subscription.php?action=resume&id=<?php echo $s['id']; ?>&token=<?php echo $_SESSION['csrf_token'] ?? ''; ?>" class="btn btn-sm btn-success" onclick="showConfirm('Resume this subscription?',this.href);return false">Resume</a>
                            <a href="<?php echo SITE_URL; ?>/processes/process_subscription.php?action=cancel&id=<?php echo $s['id']; ?>&token=<?php echo $_SESSION['csrf_token'] ?? ''; ?>" class="btn btn-sm btn-danger" onclick="showConfirm('Cancel this subscription?',this.href);return false">Cancel</a>
                            <?php endif; ?>
                            <a href="?action=view&id=<?php echo $s['id']; ?>" class="btn btn-sm btn-primary">View</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    <?php
}
require_once __DIR__ . '/includes/footer.php';
?>
