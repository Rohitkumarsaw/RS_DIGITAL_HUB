<?php
require_once __DIR__ . '/config.php';
$pageTitle = 'Support Tickets';
requireLogin();
require_once __DIR__ . '/includes/header.php';

// Handle new ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_ticket'])) {
    $subject = sanitize($_POST['subject']);
    $message = sanitize($_POST['message']);
    $priority = sanitize($_POST['priority'] ?? 'medium');

    if (empty($subject) || empty($message)) {
        setFlash('error', 'Subject and message are required.');
    } else {
        $ticketId = createTicket($_SESSION['user_id'], $subject, $message, $priority);
        if ($ticketId) {
            setFlash('success', 'Ticket created successfully.');
            redirect(SITE_URL . '/tickets.php?id=' . $ticketId);
        }
    }
}

// Handle ticket reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_ticket'])) {
    $ticketId = (int)$_POST['ticket_id'];
    $message = sanitize($_POST['message']);

    if (empty($message)) {
        setFlash('error', 'Reply message is required.');
    } else {
        addTicketReply($ticketId, $_SESSION['user_id'], $message);
        setFlash('success', 'Reply sent successfully.');
        redirect(SITE_URL . '/tickets.php?id=' . $ticketId);
    }
}

$ticketId = (int)($_GET['id'] ?? 0);
$tab = $ticketId ? 'view' : 'list';

if ($tab === 'view' && $ticketId) {
    $stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ? AND user_id = ?");
    $stmt->execute([$ticketId, $_SESSION['user_id']]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        setFlash('error', 'Ticket not found.');
        redirect(SITE_URL . '/tickets.php');
    }

    $replies = getTicketReplies($ticketId);
}

$tickets = getUserTickets($_SESSION['user_id']);
?>

<div class="container">
    <div class="section">
        <div class="d-flex justify-between align-center mb-3 flex-wrap gap-1">
            <h2 class="mb-0">Support Tickets</h2>
            <?php if ($tab === 'list'): ?>
            <button onclick="document.getElementById('newTicketForm').style.display='block'" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                New Ticket
            </button>
            <?php else: ?>
            <a href="<?php echo SITE_URL; ?>/tickets.php" class="btn btn-secondary">Back to Tickets</a>
            <?php endif; ?>
        </div>

        <!-- New Ticket Form -->
        <div id="newTicketForm" class="card mb-3" style="display:none">
            <div class="card-header">
                <h3>Create New Ticket</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" class="form-control" placeholder="Brief description of your issue" required>
                    </div>

                    <div class="form-group">
                        <label for="priority">Priority</label>
                        <select id="priority" name="priority" class="form-control">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" class="form-control" placeholder="Describe your issue in detail..." required></textarea>
                    </div>

                    <button type="submit" name="create_ticket" class="btn btn-primary">Create Ticket</button>
                </form>
            </div>
        </div>

        <!-- Ticket List -->
        <?php if ($tab === 'list'): ?>
        <?php if (empty($tickets)): ?>
        <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            <h3>No Tickets</h3>
            <p>You haven't created any support tickets yet.</p>
            <button onclick="document.getElementById('newTicketForm').style.display='block'" class="btn btn-primary">Create Ticket</button>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Ticket ID</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $t): ?>
                    <tr>
                        <td>#<?php echo $t['id']; ?></td>
                        <td><?php echo sanitize($t['subject']); ?></td>
                        <td><span class="badge badge-<?php echo $t['status'] === 'open' ? 'success' : ($t['status'] === 'in_progress' ? 'warning' : 'info'); ?>"><?php echo ucfirst(str_replace('_', ' ', $t['status'])); ?></span></td>
                        <td><span class="badge badge-<?php echo $t['priority'] === 'high' ? 'danger' : ($t['priority'] === 'medium' ? 'warning' : 'info'); ?>"><?php echo ucfirst($t['priority']); ?></span></td>
                        <td><?php echo timeAgo($t['created_at']); ?></td>
                        <td><a href="?id=<?php echo $t['id']; ?>" class="btn btn-sm btn-outline">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <!-- Single Ticket View -->
        <?php if ($tab === 'view' && isset($ticket)): ?>
        <div class="card mb-3">
            <div class="card-header d-flex justify-between align-center flex-wrap gap-1">
                <div>
                    <h3 class="mb-1"><?php echo sanitize($ticket['subject']); ?></h3>
                    <span class="badge badge-<?php echo $ticket['status'] === 'open' ? 'success' : ($ticket['status'] === 'in_progress' ? 'warning' : 'info'); ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                    </span>
                    <span class="badge badge-<?php echo $ticket['priority'] === 'high' ? 'danger' : ($ticket['priority'] === 'medium' ? 'warning' : 'info'); ?>">
                        <?php echo ucfirst($ticket['priority']); ?> Priority
                    </span>
                </div>
                <span class="text-muted">Created <?php echo timeAgo($ticket['created_at']); ?></span>
            </div>
            <div class="card-body">
                <div class="mb-3" style="padding:1rem;background:var(--bg-tertiary);border-radius:var(--border-radius-sm)">
                    <p class="mb-1"><strong>You</strong> - <?php echo formatDateTime($ticket['created_at']); ?></p>
                    <p class="mb-0"><?php echo nl2br(sanitize($ticket['message'])); ?></p>
                </div>

                <?php foreach ($replies as $reply): ?>
                <div class="mb-3" style="padding:1rem;background:<?php echo $reply['is_admin'] ? 'rgba(99,102,241,0.05)' : 'var(--bg-tertiary)'; ?>;border-radius:var(--border-radius-sm);border-left:3px solid <?php echo $reply['is_admin'] ? 'var(--primary)' : 'var(--border-color)'; ?>">
                    <p class="mb-1">
                        <strong><?php echo sanitize($reply['name']); ?></strong>
                        <?php if ($reply['is_admin']): ?>
                        <span class="badge badge-primary">Support</span>
                        <?php endif; ?>
                        - <?php echo formatDateTime($reply['created_at']); ?>
                    </p>
                    <p class="mb-0"><?php echo nl2br(sanitize($reply['message'])); ?></p>
                </div>
                <?php endforeach; ?>

                <?php if ($ticket['status'] !== 'closed'): ?>
                <form method="POST" class="mt-3">
                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                    <div class="form-group">
                        <label for="reply_message">Your Reply</label>
                        <textarea id="reply_message" name="message" class="form-control" placeholder="Type your reply..." required></textarea>
                    </div>
                    <button type="submit" name="reply_ticket" class="btn btn-primary">Send Reply</button>
                </form>
                <?php else: ?>
                <p class="text-muted mt-3">This ticket is closed.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
