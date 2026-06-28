<?php
$pageTitle = 'Choose Your Plan';
require_once __DIR__ . '/includes/header.php';

require_once __DIR__ . '/classes/Subscription.php';
require_once __DIR__ . '/classes/PlanFeature.php';
require_once __DIR__ . '/includes/currency_converter.php';

if (!isLoggedIn()) {
    redirect(SITE_URL . '/login.php');
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'] ?? 'user';

$pf = new PlanFeature($pdo);
$sub = new Subscription($pdo);

// Check if already has active subscription
$activeSub = $sub->getActiveByDeveloper($userId);

$plans = [
    'starter' => ['label' => 'Starter', 'price' => 999, 'desc' => 'Perfect for beginners starting their digital store'],
    'business' => ['label' => 'Business', 'price' => 2499, 'desc' => 'For growing developers with multiple products'],
    'professional' => ['label' => 'Professional', 'price' => 4999, 'desc' => 'Unlimited access for serious developers'],
];

$features = $pf->getPlansGrouped();
?>
<div class="container">
    <div class="section" style="text-align:center;padding-top:2rem">
        <h2 style="font-size:2rem;margin-bottom:0.5rem">Choose Your Developer Plan</h2>
        <p style="max-width:500px;margin:0 auto 2rem;color:var(--text-secondary)">Select a plan that suits your needs. Upgrade anytime to unlock more features.</p>

        <?php if ($activeSub): ?>
        <div class="alert alert-info" style="max-width:600px;margin:0 auto 2rem">
            You already have an active <?php echo ucfirst($activeSub['plan_name']); ?> plan.
            <a href="<?php echo SITE_URL; ?>/developer/subscription.php" style="margin-left:0.5rem">Manage Subscription →</a>
        </div>
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.5rem;max-width:1000px;margin:0 auto">
            <?php foreach ($plans as $key => $plan): ?>
            <?php $planFeatures = $features[$key] ?? []; ?>
            <div class="card" style="text-align:left;position:relative;<?php echo $key === 'business' ? 'border-color:var(--primary);border-width:2px;transform:scale(1.03)' : ''; ?>">
                <?php if ($key === 'business'): ?>
                <div style="position:absolute;top:-12px;left:50%;transform:translateX(-50%);background:var(--primary);color:var(--text-inverse);padding:0.25rem 1rem;border-radius:50px;font-size:0.75rem;font-weight:700">POPULAR</div>
                <?php endif; ?>
                <div class="card-body">
                    <h3 style="text-transform:capitalize;margin-bottom:0.25rem"><?php echo $plan['label']; ?></h3>
                    <p style="font-size:0.85rem;margin-bottom:1rem"><?php echo $plan['desc']; ?></p>
                    <div style="font-size:2.5rem;font-weight:800;color:var(--primary);margin-bottom:0.25rem">
                        <?php echo formatINR($plan['price']); ?>
                    </div>
                    <p style="font-size:0.85rem;color:var(--text-muted);margin-bottom:1.5rem">per month</p>

                    <div style="margin-bottom:1.5rem">
                        <?php foreach ($planFeatures as $f): ?>
                        <div style="display:flex;align-items:center;gap:0.5rem;padding:0.5rem 0;border-bottom:1px solid var(--border-color)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="<?php echo $f['is_enabled'] ? 'var(--success)' : 'var(--text-muted)'; ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <?php if ($f['is_enabled']): ?>
                                <polyline points="20 6 9 17 4 12"/>
                                <?php else: ?>
                                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                                <?php endif; ?>
                            </svg>
                            <span style="font-size:0.9rem;color:<?php echo $f['is_enabled'] ? 'var(--text-primary)' : 'var(--text-muted)'; ?>">
                                <?php echo $pf->getFeatureLabel($f['feature_name']); ?>: <strong><?php echo $pf->getFeatureValue($key, $f['feature_name']); ?></strong>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($activeSub && $activeSub['plan_name'] === $key): ?>
                    <button class="btn btn-success btn-block" disabled>Current Plan</button>
                    <?php elseif ($activeSub): ?>
                    <a href="<?php echo SITE_URL; ?>/processes/process_subscription_payment.php?plan=<?php echo $key; ?>" class="btn btn-primary btn-block">Upgrade to <?php echo $plan['label']; ?></a>
                    <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/processes/process_subscription_payment.php?plan=<?php echo $key; ?>" class="btn btn-primary btn-block">Get Started</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php
// If a plan is selected via GET, show payment confirmation
$selectedPlan = $_GET['plan'] ?? '';
if (!empty($selectedPlan) && isset($plans[$selectedPlan])):
    $planData = $plans[$selectedPlan];
    $planData = $plans[$selectedPlan];
    $taxPercent = (float)getSetting('tax_percentage', 0);
    $taxAmount = ($planData['price'] * $taxPercent) / 100;
    $total = $planData['price'] + $taxAmount;
?>
<div class="modal-overlay" id="paymentModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Confirm Subscription</h3>
            <a href="<?php echo SITE_URL; ?>/plan-selection.php" class="modal-close">&times;</a>
        </div>
        <div class="modal-body">
            <div class="summary-box">
                <div class="summary-row">
                    <span>Plan</span>
                    <strong><?php echo $planData['label']; ?></strong>
                </div>
                <div class="summary-row">
                    <span>Amount</span>
                    <strong class="price"><?php echo formatINR($planData['price']); ?></strong>
                </div>
            </div>

            <?php if ($taxPercent > 0): ?>
            <div class="detail-row">
                <span>Tax (<?php echo $taxPercent; ?>%)</span>
                <span><?php echo formatINR($taxAmount); ?></span>
            </div>
            <?php endif; ?>

            <div class="total-row">
                <span>Total</span>
                <span><?php echo formatINR($total); ?></span>
            </div>

            <form method="POST" action="<?php echo SITE_URL; ?>/processes/process_subscription_payment.php">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                <input type="hidden" name="plan" value="<?php echo $selectedPlan; ?>">
                <button type="submit" name="process_payment" class="btn btn-success btn-block btn-lg">
                    Pay Now — <?php echo formatINR($total); ?>
                </button>
            </form>

            <p class="text-muted" style="text-align:center;font-size:0.8rem">After payment, your plan will be activated automatically.</p>
        </div>
    </div>
</div>
<style>
.modal-overlay{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:1000;display:flex;align-items:center;justify-content:center;padding:1rem}
.modal-content{background:var(--bg-primary,#fff);border-radius:var(--border-radius,12px);max-width:440px;width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.15)}
.modal-header{display:flex;align-items:center;justify-content:space-between;padding:1.25rem 1.5rem;border-bottom:1px solid var(--border-color,#eee)}
.modal-header h3{margin:0;font-size:1.1rem}
.modal-close{font-size:1.5rem;line-height:1;text-decoration:none;color:var(--text-muted,#999)}
.modal-body{padding:1.5rem}
.summary-box{background:var(--bg-secondary,#f8f9fa);border-radius:var(--border-radius-sm,8px);padding:1rem;margin-bottom:1rem}
.summary-row{display:flex;justify-content:space-between;align-items:center;padding:0.25rem 0}
.summary-row .price{font-size:1.2rem}
.detail-row{display:flex;justify-content:space-between;padding:0.5rem 0;border-bottom:1px solid var(--border-color,#eee)}
.total-row{display:flex;justify-content:space-between;padding:0.75rem 0;font-size:1.15rem;font-weight:700;border-top:2px solid var(--border-color,#eee);margin-top:0.25rem}
</style>
<script>document.addEventListener('DOMContentLoaded',function(){var m=document.getElementById('paymentModal');if(m){m.addEventListener('click',function(e){if(e.target===this)window.location.href='<?php echo SITE_URL; ?>/plan-selection.php'})}})</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
