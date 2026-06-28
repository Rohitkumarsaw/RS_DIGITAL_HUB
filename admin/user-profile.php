<?php
$pageTitle = 'User Profile';
require_once __DIR__ . '/includes/header.php';

$isStrictAdmin = in_array($_SESSION['user_role'] ?? '', ['admin', 'super_admin']);
if (!$isStrictAdmin) {
    setFlash('error', 'Access denied.');
    redirect(ADMIN_URL . '/index.php');
}

$profileId = (int)($_GET['id'] ?? 0);
if (!$profileId) {
    setFlash('error', 'Invalid user ID.');
    redirect(ADMIN_URL . '/users.php');
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$profileId]);
$user = $stmt->fetch();

if (!$user) {
    setFlash('error', 'User not found.');
    redirect(ADMIN_URL . '/users.php');
}

// Get stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
$stmt->execute([$profileId]);
$orderCount = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(final_amount), 0) FROM orders WHERE user_id = ? AND payment_status = 'completed'");
$stmt->execute([$profileId]);
$totalSpent = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE user_id = ? AND status != 'closed'");
$stmt->execute([$profileId]);
$openTickets = $stmt->fetchColumn();

if ($user['role'] === 'developer') {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE developer_id = ?");
    $stmt->execute([$profileId]);
    $productCount = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM subscriptions WHERE developer_id = ? AND status = 'active'");
    $stmt->execute([$profileId]);
    $activeSub = $stmt->fetchColumn();
}

$profileComplete = ($user['profile_status'] ?? 'incomplete') === 'complete';
$regNo = $user['registration_no'] ?: 'Not assigned';
$idProofFile = $user['id_proof_file'] ?: null;
$idProofPath = $idProofFile ? (SITE_URL . '/uploads/id_proofs/' . rawurlencode($idProofFile)) : null;
?>

<div class="d-flex justify-between align-center mb-3 flex-wrap gap-1">
    <h2 class="mb-0">User Profile</h2>
    <a href="<?php echo ADMIN_URL; ?>/users.php" class="btn btn-secondary">Back to Users</a>
</div>

<div class="row" style="display:flex;flex-wrap:wrap;gap:1.5rem">
    <!-- Export Buttons -->
    <div class="card mb-3">
        <div class="card-header"><h3>Export Reports</h3></div>
        <div class="card-body">
            <div style="display:flex;flex-wrap:wrap;gap:0.75rem">
                <a href="<?php echo ADMIN_URL; ?>/pdf-preview.php?type=orders&user_id=<?php echo $profileId; ?>" class="btn btn-primary btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Orders PDF
                </a>
                <a href="<?php echo SITE_URL; ?>/pdf/export-downloads.php?user_id=<?php echo $profileId; ?>" class="btn btn-primary btn-sm" target="_blank">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Downloads PDF
                </a>
                <a href="<?php echo ADMIN_URL; ?>/pdf-preview.php?type=payments&user_id=<?php echo $profileId; ?>" class="btn btn-primary btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Payments PDF
                </a>
                <?php if ($user['role'] === 'developer'): ?>
                <a href="<?php echo ADMIN_URL; ?>/pdf-preview.php?type=products&user_id=<?php echo $profileId; ?>" class="btn btn-primary btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Products PDF
                </a>
                <a href="<?php echo ADMIN_URL; ?>/pdf-preview.php?type=subscriptions&user_id=<?php echo $profileId; ?>" class="btn btn-primary btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Subscription PDF
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Left Column: Account Info -->
    <div style="flex:1;min-width:300px">
        <div class="card mb-3">
            <div class="card-header">
                <h3>Account Information</h3>
            </div>
            <div class="card-body">
                <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem;padding-bottom:1.5rem;border-bottom:1px solid var(--border-color)">
                    <div style="width:56px;height:56px;border-radius:50%;background:var(--primary);color:white;display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:700;flex-shrink:0">
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    </div>
                    <div>
                        <h3 class="mb-1"><?php echo sanitize($user['name']); ?></h3>
                        <p class="text-muted mb-1"><?php echo sanitize($user['email']); ?></p>
                        <div class="d-flex gap-1 flex-wrap">
                            <span class="badge badge-<?php echo $user['role'] === 'super_admin' ? 'danger' : ($user['role'] === 'admin' ? 'primary' : ($user['role'] === 'developer' ? 'info' : 'secondary')); ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                            </span>
                            <span class="badge badge-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>">
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                            <span class="badge badge-<?php echo $profileComplete ? 'success' : 'warning'; ?>">
                                Profile: <?php echo $profileComplete ? 'Complete' : 'Incomplete'; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                <table class="table" style="margin:0">
                    <tr><td style="padding:0.5rem 0"><strong>Registration No:</strong></td><td style="padding:0.5rem 0"><?php echo sanitize($regNo); ?></td></tr>
                    <tr><td style="padding:0.5rem 0"><strong>User ID:</strong></td><td style="padding:0.5rem 0">#<?php echo $user['id']; ?></td></tr>
                    <tr><td style="padding:0.5rem 0"><strong>Joined:</strong></td><td style="padding:0.5rem 0"><?php echo formatDateTime($user['created_at']); ?></td></tr>
                    <tr><td style="padding:0.5rem 0"><strong>Last Updated:</strong></td><td style="padding:0.5rem 0"><?php echo formatDateTime($user['updated_at']); ?></td></tr>
                    <tr><td style="padding:0.5rem 0"><strong>Profile Completed:</strong></td><td style="padding:0.5rem 0"><?php echo $user['profile_completed_at'] ? formatDateTime($user['profile_completed_at']) : 'Not completed'; ?></td></tr>
                </table>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="card">
            <div class="card-header"><h3>Statistics</h3></div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                    <div style="text-align:center;padding:1rem;background:var(--bg-tertiary);border-radius:var(--border-radius-sm)">
                        <div style="font-size:1.5rem;font-weight:700;color:var(--primary)"><?php echo $orderCount; ?></div>
                        <div class="text-muted" style="font-size:0.8rem">Orders</div>
                    </div>
                    <div style="text-align:center;padding:1rem;background:var(--bg-tertiary);border-radius:var(--border-radius-sm)">
                        <div style="font-size:1.5rem;font-weight:700;color:var(--success)"><?php echo formatPrice($totalSpent); ?></div>
                        <div class="text-muted" style="font-size:0.8rem">Total Spent</div>
                    </div>
                    <div style="text-align:center;padding:1rem;background:var(--bg-tertiary);border-radius:var(--border-radius-sm)">
                        <div style="font-size:1.5rem;font-weight:700;color:var(--accent)"><?php echo $openTickets; ?></div>
                        <div class="text-muted" style="font-size:0.8rem">Open Tickets</div>
                    </div>
                    <?php if ($user['role'] === 'developer'): ?>
                    <div style="text-align:center;padding:1rem;background:var(--bg-tertiary);border-radius:var(--border-radius-sm)">
                        <div style="font-size:1.5rem;font-weight:700;color:var(--info)"><?php echo $productCount ?? 0; ?></div>
                        <div class="text-muted" style="font-size:0.8rem">Products</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Profile Details -->
    <div style="flex:1.2;min-width:300px">
        <div class="card mb-3">
            <div class="card-header">
                <h3>Profile Details</h3>
                <?php if (!$profileComplete): ?>
                <span class="badge badge-warning">Incomplete</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                <table class="table" style="margin:0">
                    <tr>
                        <td style="padding:0.6rem 0;width:140px"><strong>Father Name:</strong></td>
                        <td style="padding:0.6rem 0"><?php echo sanitize($user['father_name'] ?: 'Not provided'); ?></td>
                    </tr>
                    <tr>
                        <td style="padding:0.6rem 0"><strong>Mobile:</strong></td>
                        <td style="padding:0.6rem 0"><?php echo sanitize($user['mobile'] ?: 'Not provided'); ?></td>
                    </tr>
                    <tr>
                        <td style="padding:0.6rem 0"><strong>Email:</strong></td>
                        <td style="padding:0.6rem 0"><?php echo sanitize($user['email']); ?></td>
                    </tr>
                    <tr>
                        <td style="padding:0.6rem 0"><strong>Address:</strong></td>
                        <td style="padding:0.6rem 0"><?php echo nl2br(sanitize($user['address'] ?: 'Not provided')); ?></td>
                    </tr>
                    <tr>
                        <td style="padding:0.6rem 0"><strong>Date of Birth:</strong></td>
                        <td style="padding:0.6rem 0"><?php echo $user['dob'] ? date('M d, Y', strtotime($user['dob'])) : 'Not provided'; ?></td>
                    </tr>
                    <tr>
                        <td style="padding:0.6rem 0"><strong>Gender:</strong></td>
                        <td style="padding:0.6rem 0"><?php echo $user['gender'] ? ucfirst($user['gender']) : 'Not provided'; ?></td>
                    </tr>
                    <tr>
                        <td style="padding:0.6rem 0"><strong>Nationality:</strong></td>
                        <td style="padding:0.6rem 0"><?php echo sanitize($user['nationality'] ?: 'Not provided'); ?></td>
                    </tr>
                    <tr>
                        <td style="padding:0.6rem 0"><strong>Occupation:</strong></td>
                        <td style="padding:0.6rem 0"><?php echo sanitize($user['occupation'] ?: 'Not provided'); ?></td>
                    </tr>
                    <tr>
                        <td style="padding:0.6rem 0"><strong>ID Proof:</strong></td>
                        <td style="padding:0.6rem 0">
                            <?php if ($idProofFile): ?>
                                <a href="<?php echo $idProofPath; ?>" target="_blank" class="btn btn-sm btn-outline">View ID Proof</a>
                                <span class="text-muted" style="font-size:0.8rem;margin-left:0.5rem"><?php echo sanitize($idProofFile); ?></span>
                            <?php else: ?>
                                <span class="text-muted">Not uploaded</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                </div>
            </div>
        </div>

        <!-- Payout Info (if developer) -->
        <?php if ($user['role'] === 'developer' && ($user['payout_method'] || $user['account_holder'])): ?>
        <div class="card">
            <div class="card-header"><h3>Payout Information</h3></div>
            <div class="card-body">
                <div class="table-responsive">
                <table class="table" style="margin:0">
                    <?php if ($user['account_holder']): ?>
                    <tr><td style="padding:0.5rem 0"><strong>Account Holder:</strong></td><td style="padding:0.5rem 0"><?php echo sanitize($user['account_holder']); ?></td></tr>
                    <?php endif; ?>
                    <?php if ($user['bank_name']): ?>
                    <tr><td style="padding:0.5rem 0"><strong>Bank:</strong></td><td style="padding:0.5rem 0"><?php echo sanitize($user['bank_name']); ?></td></tr>
                    <?php endif; ?>
                    <?php if ($user['account_number']): ?>
                    <tr><td style="padding:0.5rem 0"><strong>A/c No:</strong></td><td style="padding:0.5rem 0"><?php echo sanitize($user['account_number']); ?></td></tr>
                    <?php endif; ?>
                    <?php if ($user['ifsc_code']): ?>
                    <tr><td style="padding:0.5rem 0"><strong>IFSC:</strong></td><td style="padding:0.5rem 0"><?php echo sanitize($user['ifsc_code']); ?></td></tr>
                    <?php endif; ?>
                    <?php if ($user['upi_id']): ?>
                    <tr><td style="padding:0.5rem 0"><strong>UPI:</strong></td><td style="padding:0.5rem 0"><?php echo sanitize($user['upi_id']); ?></td></tr>
                    <?php endif; ?>
                </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
