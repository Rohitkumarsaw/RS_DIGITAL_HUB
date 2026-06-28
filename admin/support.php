<?php
$pageTitle = 'Support';
require_once __DIR__ . '/includes/header.php';

$isDeveloper = $_SESSION['user_role'] === 'developer';
$userId = $_SESSION['user_id'];
$tab = $_GET['tab'] ?? 'tickets';

// Developer cannot access support page
if ($isDeveloper) {
    setFlash('error', 'Access denied.');
    redirect(ADMIN_URL . '/index.php');
}

// CSRF check for all POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if ($csrf !== ($_SESSION['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid security token.');
        redirect(ADMIN_URL . '/support.php');
    }
}

// Handle ticket reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_ticket'])) {
    $ticketId = (int)$_POST['ticket_id'];
    $message = sanitize($_POST['message']);
    $status = sanitize($_POST['status'] ?? 'in_progress');

    if (!empty($message)) {
        addTicketReply($ticketId, $userId, $message, true);
        $pdo->prepare("UPDATE tickets SET status = ? WHERE id = ?")->execute([$status, $ticketId]);
        setFlash('success', 'Reply sent.');
        redirect(ADMIN_URL . '/support.php?tab=view&id=' . $ticketId);
    }
}

// Handle FAQ add/edit/delete (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_faq']) && !$isDeveloper) {
    $faqId = (int)($_POST['faq_id'] ?? 0);
    $question = sanitize($_POST['question']);
    $answer = sanitize($_POST['answer']);
    $faqStatus = sanitize($_POST['faq_status']);
    $sortOrder = (int)($_POST['sort_order'] ?? 0);

    if (!empty($question) && !empty($answer)) {
        if ($faqId) {
            $pdo->prepare("UPDATE faqs SET question = ?, answer = ?, status = ?, sort_order = ? WHERE id = ?")->execute([$question, $answer, $faqStatus, $sortOrder, $faqId]);
            setFlash('success', 'FAQ updated.');
        } else {
            $pdo->prepare("INSERT INTO faqs (question, answer, status, sort_order) VALUES (?, ?, ?, ?)")->execute([$question, $answer, $faqStatus, $sortOrder]);
            setFlash('success', 'FAQ created.');
        }
    }
    redirect(ADMIN_URL . '/support.php?tab=faqs');
}

if (isset($_GET['delete_faq']) && !$isDeveloper) {
    $pdo->prepare("DELETE FROM faqs WHERE id = ?")->execute([(int)$_GET['delete_faq']]);
    setFlash('success', 'FAQ deleted.');
    redirect(ADMIN_URL . '/support.php?tab=faqs');
}

// Handle testimonial save/edit/delete (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_testimonial']) && !$isDeveloper) {
    $testimonialId = (int)($_POST['testimonial_id'] ?? 0);
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $message = sanitize($_POST['message']);
    $rating = (int)($_POST['rating'] ?? 5);
    $testimonialStatus = sanitize($_POST['testimonial_status']);
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    if ($rating < 1) $rating = 1;
    if ($rating > 5) $rating = 5;

    if (!empty($name) && !empty($message)) {
        if ($testimonialId) {
            $pdo->prepare("UPDATE testimonials SET name = ?, email = ?, message = ?, rating = ?, status = ?, sort_order = ? WHERE id = ?")->execute([$name, $email, $message, $rating, $testimonialStatus, $sortOrder, $testimonialId]);
            setFlash('success', 'Testimonial updated.');
        } else {
            $pdo->prepare("INSERT INTO testimonials (name, email, message, rating, status, sort_order) VALUES (?, ?, ?, ?, ?, ?)")->execute([$name, $email, $message, $rating, $testimonialStatus, $sortOrder]);
            setFlash('success', 'Testimonial created.');
        }
    }
    redirect(ADMIN_URL . '/support.php?tab=testimonials');
}

if (isset($_GET['delete_testimonial']) && !$isDeveloper) {
    $pdo->prepare("DELETE FROM testimonials WHERE id = ?")->execute([(int)$_GET['delete_testimonial']]);
    setFlash('success', 'Testimonial deleted.');
    redirect(ADMIN_URL . '/support.php?tab=testimonials');
}

if ($tab === 'view' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT t.*, u.name as user_name, u.email as user_email FROM tickets t JOIN users u ON t.user_id = u.id WHERE t.id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $ticket = $stmt->fetch();
    // Scope check: developers can only view their own tickets
    if ($ticket && $isDeveloper && $ticket['user_id'] != $userId) {
        $ticket = null;
        setFlash('error', 'Ticket not found.');
    }
    $replies = $ticket ? getTicketReplies($ticket['id']) : [];
}

// Tickets: developer sees only their own tickets
if ($isDeveloper) {
    $stmt = $pdo->prepare("
        SELECT t.*, u.name as user_name, u.email as user_email
        FROM tickets t
        JOIN users u ON t.user_id = u.id
        WHERE t.user_id = ?
        ORDER BY t.updated_at DESC
    ");
    $stmt->execute([$userId]);
    $tickets = $stmt->fetchAll();
} else {
    $tickets = $pdo->query("
        SELECT t.*, u.name as user_name, u.email as user_email
        FROM tickets t
        JOIN users u ON t.user_id = u.id
        ORDER BY t.updated_at DESC
    ")->fetchAll();
}

$faqs = $pdo->query("SELECT * FROM faqs ORDER BY sort_order ASC")->fetchAll();
$testimonials = $pdo->query("SELECT * FROM testimonials ORDER BY sort_order ASC, created_at DESC")->fetchAll();
$editFaq = null;
$editTestimonial = null;

if ($tab === 'faqs' && isset($_GET['edit_faq'])) {
    $stmt = $pdo->prepare("SELECT * FROM faqs WHERE id = ?");
    $stmt->execute([(int)$_GET['edit_faq']]);
    $editFaq = $stmt->fetch();
}

if ($tab === 'testimonials' && isset($_GET['edit_testimonial'])) {
    $stmt = $pdo->prepare("SELECT * FROM testimonials WHERE id = ?");
    $stmt->execute([(int)$_GET['edit_testimonial']]);
    $editTestimonial = $stmt->fetch();
}
?>

<h2 class="mb-3">Support</h2>

<div class="filters">
    <a href="?tab=tickets" class="btn <?php echo $tab === 'tickets' || $tab === 'view' ? 'btn-primary' : 'btn-secondary'; ?>">Tickets</a>
    <a href="?tab=faqs" class="btn <?php echo $tab === 'faqs' ? 'btn-primary' : 'btn-secondary'; ?>">FAQs</a>
    <a href="?tab=testimonials" class="btn <?php echo $tab === 'testimonials' ? 'btn-primary' : 'btn-secondary'; ?>">Testimonials</a>
</div>

<?php if ($tab === 'tickets' || $tab === 'view'): ?>
<?php if ($tab === 'view' && isset($ticket)): ?>
<div class="card mb-3">
    <div class="card-header d-flex justify-between align-center flex-wrap gap-1">
        <div>
            <h3 class="mb-1"><?php echo sanitize($ticket['subject']); ?></h3>
            <span class="badge badge-<?php echo $ticket['status'] === 'open' ? 'success' : ($ticket['status'] === 'in_progress' ? 'warning' : 'info'); ?>">
                <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
            </span>
            <span class="text-muted ml-2">From: <?php echo sanitize($ticket['user_name']); ?> (<?php echo sanitize($ticket['user_email']); ?>)</span>
        </div>
        <a href="?tab=tickets" class="btn btn-sm btn-secondary">Back</a>
    </div>
    <div class="card-body">
        <div class="mb-3" style="padding:1rem;background:var(--bg-tertiary);border-radius:var(--border-radius-sm)">
            <p class="mb-1"><strong><?php echo sanitize($ticket['user_name']); ?></strong> - <?php echo formatDateTime($ticket['created_at']); ?></p>
            <p class="mb-0"><?php echo nl2br(sanitize($ticket['message'])); ?></p>
        </div>

        <?php foreach ($replies as $reply): ?>
        <div class="mb-3" style="padding:1rem;background:<?php echo $reply['is_admin'] ? 'rgba(99,102,241,0.05)' : 'var(--bg-tertiary)'; ?>;border-radius:var(--border-radius-sm);border-left:3px solid <?php echo $reply['is_admin'] ? 'var(--primary)' : 'var(--border-color)'; ?>">
            <p class="mb-1">
                <strong><?php echo sanitize($reply['name']); ?></strong>
                <?php if ($reply['is_admin']): ?><span class="badge badge-primary">Admin</span><?php endif; ?>
                - <?php echo formatDateTime($reply['created_at']); ?>
            </p>
            <p class="mb-0"><?php echo nl2br(sanitize($reply['message'])); ?></p>
        </div>
        <?php endforeach; ?>

        <?php if ($ticket['status'] !== 'closed'): ?>
        <form method="POST" class="mt-3">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
            <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
            <div class="form-group">
                <label>Your Reply</label>
                <textarea name="message" class="form-control" required></textarea>
            </div>
            <div class="d-flex gap-1">
                <select name="status" class="form-control" style="max-width:150px">
                    <option value="in_progress">In Progress</option>
                    <option value="closed">Close Ticket</option>
                </select>
                <button type="submit" name="reply_ticket" class="btn btn-primary">Send Reply</button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Updated</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $t): ?>
                <tr>
                    <td>#<?php echo $t['id']; ?></td>
                    <td><?php echo sanitize($t['user_name']); ?></td>
                    <td><?php echo sanitize($t['subject']); ?></td>
                    <td><span class="badge badge-<?php echo $t['status'] === 'open' ? 'success' : ($t['status'] === 'in_progress' ? 'warning' : 'info'); ?>"><?php echo ucfirst(str_replace('_', ' ', $t['status'])); ?></span></td>
                    <td><span class="badge badge-<?php echo $t['priority'] === 'high' ? 'danger' : ($t['priority'] === 'medium' ? 'warning' : 'info'); ?>"><?php echo ucfirst($t['priority']); ?></span></td>
                    <td><?php echo timeAgo($t['updated_at']); ?></td>
                    <td><a href="?tab=view&id=<?php echo $t['id']; ?>" class="btn btn-sm btn-outline">View</a></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($tickets)): ?>
                <tr><td colspan="7" class="text-center text-muted" style="padding:2rem">No tickets found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>
<?php if ($tab === 'faqs'): ?>
<div class="d-flex justify-between align-center mb-3 flex-wrap gap-1">
    <h3 class="mb-0"><?php echo $editFaq ? 'Edit' : 'Add'; ?> FAQ</h3>
    <?php if ($editFaq): ?><a href="?tab=faqs" class="btn btn-sm btn-secondary">Cancel</a><?php endif; ?>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
            <?php if ($editFaq): ?><input type="hidden" name="faq_id" value="<?php echo $editFaq['id']; ?>"><?php endif; ?>
            <div class="form-group">
                <label for="faq_question">Question</label>
                <input type="text" id="faq_question" name="question" class="form-control" value="<?php echo $editFaq ? sanitize($editFaq['question']) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="faq_answer">Answer</label>
                <textarea id="faq_answer" name="answer" class="form-control" required><?php echo $editFaq ? sanitize($editFaq['answer']) : ''; ?></textarea>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <div class="form-group" style="flex:1;min-width:150px">
                    <label for="faq_status">Status</label>
                    <select id="faq_status" name="faq_status" class="form-control">
                        <option value="active" <?php echo $editFaq && $editFaq['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="hidden" <?php echo $editFaq && $editFaq['status'] === 'hidden' ? 'selected' : ''; ?>>Hidden</option>
                    </select>
                </div>
                <div class="form-group" style="flex:1;min-width:100px">
                    <label for="faq_sort">Sort Order</label>
                    <input type="number" id="faq_sort" name="sort_order" class="form-control" value="<?php echo $editFaq ? $editFaq['sort_order'] : 0; ?>">
                </div>
            </div>
            <button type="submit" name="save_faq" class="btn btn-primary"><?php echo $editFaq ? 'Update' : 'Add'; ?> FAQ</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Question</th>
                    <th>Status</th>
                    <th>Order</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($faqs as $faq): ?>
                <tr>
                    <td><?php echo sanitize($faq['question']); ?></td>
                    <td><span class="badge badge-<?php echo $faq['status'] === 'active' ? 'success' : 'warning'; ?>"><?php echo ucfirst($faq['status']); ?></span></td>
                    <td><?php echo $faq['sort_order']; ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="?tab=faqs&edit_faq=<?php echo $faq['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                            <a href="?delete_faq=<?php echo $faq['id']; ?>" class="btn btn-sm btn-danger" onclick="showConfirm('Are you sure you want to delete this?',this.href);return false">Delete</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>
<?php if ($tab === 'testimonials'): ?>
<div class="d-flex justify-between align-center mb-3 flex-wrap gap-1">
    <h3 class="mb-0"><?php echo $editTestimonial ? 'Edit' : 'Add'; ?> Testimonial</h3>
    <?php if ($editTestimonial): ?><a href="?tab=testimonials" class="btn btn-sm btn-secondary">Cancel</a><?php endif; ?>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
            <?php if ($editTestimonial): ?><input type="hidden" name="testimonial_id" value="<?php echo $editTestimonial['id']; ?>"><?php endif; ?>
            <div class="form-group">
                <label for="testimonial_name">Name</label>
                <input type="text" id="testimonial_name" name="name" class="form-control" value="<?php echo $editTestimonial ? sanitize($editTestimonial['name']) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="testimonial_email">Email</label>
                <input type="email" id="testimonial_email" name="email" class="form-control" value="<?php echo $editTestimonial ? sanitize($editTestimonial['email']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="testimonial_message">Message</label>
                <textarea id="testimonial_message" name="message" class="form-control" required><?php echo $editTestimonial ? sanitize($editTestimonial['message']) : ''; ?></textarea>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <div class="form-group" style="flex:1;min-width:120px">
                    <label for="testimonial_rating">Rating</label>
                    <select id="testimonial_rating" name="rating" class="form-control">
                        <?php for ($r = 5; $r >= 1; $r--): ?>
                        <option value="<?php echo $r; ?>" <?php echo $editTestimonial && $editTestimonial['rating'] == $r ? 'selected' : ''; ?>><?php echo $r; ?> Star<?php echo $r > 1 ? 's' : ''; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group" style="flex:1;min-width:150px">
                    <label for="testimonial_status">Status</label>
                    <select id="testimonial_status" name="testimonial_status" class="form-control">
                        <option value="active" <?php echo $editTestimonial && $editTestimonial['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="pending" <?php echo $editTestimonial && $editTestimonial['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="hidden" <?php echo $editTestimonial && $editTestimonial['status'] === 'hidden' ? 'selected' : ''; ?>>Hidden</option>
                    </select>
                </div>
                <div class="form-group" style="flex:1;min-width:100px">
                    <label for="testimonial_sort">Sort Order</label>
                    <input type="number" id="testimonial_sort" name="sort_order" class="form-control" value="<?php echo $editTestimonial ? $editTestimonial['sort_order'] : 0; ?>">
                </div>
            </div>
            <button type="submit" name="save_testimonial" class="btn btn-primary"><?php echo $editTestimonial ? 'Update' : 'Add'; ?> Testimonial</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Rating</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Order</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($testimonials as $testimonial): ?>
                <tr>
                    <td><?php echo sanitize($testimonial['name']); ?></td>
                    <td><?php echo str_repeat('★', $testimonial['rating']); ?></td>
                    <td><?php echo substr(sanitize($testimonial['message']), 0, 100); ?>...</td>
                    <td><span class="badge badge-<?php echo $testimonial['status'] === 'active' ? 'success' : ($testimonial['status'] === 'pending' ? 'warning' : 'danger'); ?>"><?php echo ucfirst($testimonial['status']); ?></span></td>
                    <td><?php echo $testimonial['sort_order']; ?></td>
                    <td><?php echo formatDate($testimonial['created_at']); ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="?tab=testimonials&edit_testimonial=<?php echo $testimonial['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                            <a href="?delete_testimonial=<?php echo $testimonial['id']; ?>" class="btn btn-sm btn-danger" onclick="showConfirm('Are you sure you want to delete this testimonial?',this.href);return false">Delete</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>


<?php require_once __DIR__ . '/includes/footer.php'; ?>
