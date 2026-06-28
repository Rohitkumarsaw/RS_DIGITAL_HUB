<?php
$pageTitle = 'Messages';
require_once __DIR__ . '/includes/header.php';
requireAdminOrDeveloper();

$userId = $_SESSION['user_id'];
$isAdmin = isAdmin();

// Handle archive / unarchive
if (isset($_GET['archive'])) {
    $msgId = (int)$_GET['archive'];
    $pdo->prepare("UPDATE messages SET is_archived = 1 WHERE id = ? AND receiver_id = ?")->execute([$msgId, $userId]);
    setFlash('success', 'Message archived.');
    redirect(ADMIN_URL . '/messages.php');
}
if (isset($_GET['unarchive'])) {
    $msgId = (int)$_GET['unarchive'];
    $pdo->prepare("UPDATE messages SET is_archived = 0 WHERE id = ? AND receiver_id = ?")->execute([$msgId, $userId]);
    setFlash('success', 'Message restored.');
    redirect(ADMIN_URL . '/messages.php');
}

$view = $_GET['view'] ?? 'inbox';
$msgDetail = null;
if (isset($_GET['id'])) {
    $msgId = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT m.*, u1.name as sender_name, u1.email as sender_email, u2.name as receiver_name FROM messages m JOIN users u1 ON m.sender_id = u1.id JOIN users u2 ON m.receiver_id = u2.id WHERE m.id = ? AND (m.receiver_id = ? OR m.sender_id = ?)");
    $stmt->execute([$msgId, $userId, $userId]);
    $msgDetail = $stmt->fetch();
    if ($msgDetail && $msgDetail['receiver_id'] == $userId && !$msgDetail['is_read']) {
        $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ?")->execute([$msgId]);
    }
}

// Compose
if (isset($_GET['compose'])) {
    $view = 'compose';
}

// Fetch inbox
$inboxStmt = $pdo->prepare("SELECT m.*, u.name as sender_name, u.email as sender_email FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.receiver_id = ? AND m.parent_message_id IS NULL ORDER BY m.created_at DESC");
$inboxStmt->execute([$userId]);
$inbox = $inboxStmt->fetchAll();

// Fetch sent
$sentStmt = $pdo->prepare("SELECT m.*, u.name as receiver_name, u.email as receiver_email FROM messages m JOIN users u ON m.receiver_id = u.id WHERE m.sender_id = ? AND m.parent_message_id IS NULL ORDER BY m.created_at DESC");
$sentStmt->execute([$userId]);
$sent = $sentStmt->fetchAll();

// Get developers list for compose (admin only)
$developers = [];
if ($isAdmin) {
    $devStmt = $pdo->query("SELECT id, name, email, store_name FROM users WHERE role = 'developer' ORDER BY name");
    $developers = $devStmt->fetchAll();
}

// Get admin user for developer compose
$adminUser = null;
if (!$isAdmin) {
    $stmt = $pdo->query("SELECT id FROM users WHERE role IN ('admin','super_admin') LIMIT 1");
    $adminUser = $stmt->fetch();
}
?>
<div class="d-flex justify-between align-center mb-3 flex-wrap gap-1">
    <h2 class="mb-0">Messages</h2>
    <div class="d-flex gap-1 flex-wrap">
        <a href="?view=inbox" class="btn <?php echo $view === 'inbox' ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">Inbox</a>
        <a href="?view=sent" class="btn <?php echo $view === 'sent' ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">Sent</a>
        <a href="?compose=1" class="btn btn-primary btn-sm">+ New Message</a>
    </div>
</div>

<?php if ($view === 'compose' && ($isAdmin || $adminUser)): ?>
<div class="card">
    <div class="card-body">
        <h3 class="mb-3">New Message</h3>
        <form method="POST" action="<?php echo ADMIN_URL; ?>/ajax/send_message.php">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
            <?php if ($isAdmin): ?>
            <div class="form-group">
                <label for="receiver_id">To (Developer)</label>
                <select id="receiver_id" name="receiver_id" class="form-control" required>
                    <option value="">Select a developer...</option>
                    <?php foreach ($developers as $d): ?>
                    <option value="<?php echo $d['id']; ?>"><?php echo sanitize($d['store_name'] ?: $d['name']); ?> (<?php echo sanitize($d['email']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php else: ?>
            <input type="hidden" name="receiver_id" value="<?php echo $adminUser ? (int)$adminUser['id'] : 0; ?>">
            <div class="form-group">
                <label>To</label>
                <p style="padding:8px 0;margin:0"><strong>Admin</strong></p>
            </div>
            <?php endif; ?>
            <div class="form-group">
                <label for="subject">Subject</label>
                <input type="text" id="subject" name="subject" class="form-control" required placeholder="Message subject...">
            </div>
            <div class="form-group">
                <label for="body">Message</label>
                <textarea id="body" name="body" class="form-control" rows="6" required placeholder="Write your message..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Send Message</button>
        </form>
    </div>
</div>

<?php elseif ($view === 'compose' && !$isAdmin && !$adminUser): ?>
<div class="card">
    <div class="card-body">
        <p class="text-muted text-center">Cannot send message: no admin user found.</p>
    </div>
</div>

<?php elseif ($msgDetail): ?>
<div class="card">
    <div class="card-body">
        <a href="?view=<?php echo $view; ?>" class="btn btn-sm btn-secondary mb-3">&larr; Back</a>
        <h3 class="mb-1"><?php echo sanitize($msgDetail['subject']); ?></h3>
        <p class="text-muted" style="font-size:0.85rem">
            <strong>From:</strong> <?php echo sanitize($msgDetail['sender_name']); ?> &lt;<?php echo sanitize($msgDetail['sender_email']); ?>&gt; |
            <strong>Date:</strong> <?php echo $msgDetail['created_at']; ?>
        </p>
        <hr>
        <div style="white-space:pre-wrap;line-height:1.7"><?php echo sanitize($msgDetail['body']); ?></div>

        <!-- Reply form -->
        <?php
        $parentId = $msgDetail['parent_message_id'] ?: $msgDetail['id'];
        $replyTo = $msgDetail['sender_id'] == $userId ? $msgDetail['receiver_id'] : $msgDetail['sender_id'];
        ?>
        <hr>
        <h4 class="mb-2">Reply</h4>
        <form method="POST" action="<?php echo ADMIN_URL; ?>/ajax/send_message.php">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
            <input type="hidden" name="receiver_id" value="<?php echo $replyTo; ?>">
            <input type="hidden" name="parent_message_id" value="<?php echo $parentId; ?>">
            <div class="form-group">
                <label for="subject">Subject</label>
                <input type="text" name="subject" class="form-control" value="Re: <?php echo sanitize($msgDetail['subject']); ?>" required>
            </div>
            <div class="form-group">
                <label for="body">Message</label>
                <textarea name="body" class="form-control" rows="4" required placeholder="Write your reply..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Send Reply</button>
        </form>

        <!-- Thread replies -->
        <?php
        $threadStmt = $pdo->prepare("SELECT m.*, u.name as sender_name FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.parent_message_id = ? OR m.id = ? ORDER BY m.created_at ASC");
        $threadStmt->execute([$parentId, $parentId]);
        $thread = $threadStmt->fetchAll();
        if (count($thread) > 1): ?>
        <hr>
        <h4 class="mb-2">Conversation</h4>
        <div style="max-height:300px;overflow-y:auto">
        <?php foreach ($thread as $t): ?>
        <div style="padding:10px;margin-bottom:8px;background:<?php echo $t['sender_id'] == $userId ? 'var(--bg-card)' : 'var(--bg-tertiary)'; ?>;border-radius:6px;border-left:3px solid <?php echo $t['sender_id'] == $userId ? 'var(--primary)' : 'var(--text-muted)'; ?>">
            <strong style="font-size:0.85rem"><?php echo sanitize($t['sender_name']); ?></strong>
            <small class="text-muted" style="float:right"><?php echo $t['created_at']; ?></small>
            <p style="margin:4px 0 0;white-space:pre-wrap;font-size:0.9rem"><?php echo sanitize($t['body']); ?></p>
        </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Subject</th>
                    <th><?php echo $view === 'inbox' ? 'From' : 'To'; ?></th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $items = $view === 'inbox' ? $inbox : $sent; if (empty($items)): ?>
                <tr><td colspan="5" class="text-center text-muted">No messages.</td></tr>
                <?php else: foreach ($items as $m): ?>
                <tr style="<?php echo $view === 'inbox' && !$m['is_read'] ? 'font-weight:600' : ''; ?>">
                    <td><a href="?id=<?php echo $m['id']; ?>&view=<?php echo $view; ?>"><?php echo sanitize(mb_substr($m['subject'], 0, 50) . (mb_strlen($m['subject']) > 50 ? '...' : '')); ?></a></td>
                    <td><?php echo sanitize($view === 'inbox' ? ($m['sender_name'] ?? 'N/A') : ($m['receiver_name'] ?? 'N/A')); ?></td>
                    <td style="font-size:0.85rem"><?php echo date('d M Y', strtotime($m['created_at'])); ?></td>
                    <td><?php if ($view === 'inbox'): ?><span class="badge badge-<?php echo $m['is_read'] ? 'secondary' : 'warning'; ?>"><?php echo $m['is_read'] ? 'Read' : 'New'; ?><?php echo $m['is_archived'] ? ' (Archived)' : ''; ?></span><?php else: ?><span class="badge badge-success">Sent</span><?php endif; ?></td>
                    <td>
                        <a href="?id=<?php echo $m['id']; ?>&view=<?php echo $view; ?>" class="btn btn-sm btn-outline">View</a>
                        <?php if ($view === 'inbox'): ?>
                        <?php if ($m['is_archived']): ?>
                        <a href="?unarchive=<?php echo $m['id']; ?>" class="btn btn-sm btn-success">Unarchive</a>
                        <?php else: ?>
                        <a href="?archive=<?php echo $m['id']; ?>" class="btn btn-sm btn-secondary" onclick="showConfirm('Archive this message?',this.href);return false">Archive</a>
                        <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
