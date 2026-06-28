<?php
$pageTitle = 'Active Sessions';
require_once __DIR__ . '/includes/header.php';
requireStrictAdmin();

if (isset($_GET['terminate'])) {
    $token = $_GET['token'] ?? '';
    if ($token !== ($_SESSION['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid security token.');
        redirect(ADMIN_URL . '/sessions.php');
    }
    $sid = (int)$_GET['terminate'];
    $pdo->prepare("UPDATE sessions SET is_active = 0 WHERE id = ?")->execute([$sid]);
    setFlash('success', 'Session terminated.');
    redirect(ADMIN_URL . '/sessions.php');
}

$sessions = $pdo->query("
    SELECT s.*, u.name, u.email, u.role 
    FROM sessions s 
    JOIN users u ON s.user_id = u.id 
    WHERE s.is_active = 1 
    ORDER BY s.last_activity DESC
")->fetchAll();
?>
<div class="d-flex justify-between align-center mb-3 flex-wrap gap-1">
    <h2 class="mb-0">Active Sessions</h2>
    <span class="badge badge-primary">Total: <?php echo count($sessions); ?></span>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>IP</th>
                    <th>User Agent</th>
                    <th>Last Activity</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sessions)): ?>
                <tr><td colspan="6" class="text-center text-muted">No active sessions.</td></tr>
                <?php else: foreach ($sessions as $s): ?>
                <tr>
                    <td><strong><?php echo sanitize($s['name']); ?></strong><br><small class="text-muted"><?php echo sanitize($s['email']); ?></small></td>
                    <td><span class="badge badge-<?php echo $s['role'] === 'admin' || $s['role'] === 'super_admin' ? 'success' : 'info'; ?>"><?php echo ucfirst($s['role']); ?></span></td>
                    <td><?php echo sanitize($s['ip']); ?></td>
                    <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?php echo sanitize($s['user_agent']); ?>"><?php echo sanitize(substr($s['user_agent'], 0, 50) . (strlen($s['user_agent'] ?? '') > 50 ? '...' : '')); ?></td>
                    <td><?php echo $s['last_activity']; ?></td>
                    <td><a href="?terminate=<?php echo $s['id']; ?>&token=<?php echo urlencode($_SESSION['csrf_token'] ?? ''); ?>" class="btn btn-sm btn-danger" onclick="showConfirm('Terminate this session?',this.href);return false">Terminate</a></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
