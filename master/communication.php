<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('master');

$master_id = (int)($_SESSION['user_id'] ?? 0);

$stmt = $pdo->prepare("SELECT id, name FROM dojos WHERE master_id = ? AND status = 'approved' LIMIT 1");
$stmt->execute([$master_id]);
$dojo = $stmt->fetch();

if (!$dojo) {
    $_SESSION['error_msg'] = 'You need an approved dojo to use communication tools.';
    redirect('/master/dashboard.php');
}

$dojo_id = (int)$dojo['id'];
$error = '';

/* Create the communication table once if this KOMS installation does not have it yet. */
$pdo->exec("CREATE TABLE IF NOT EXISTS communication_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dojo_id INT NOT NULL,
    sender_id INT NOT NULL,
    recipient_id INT NULL,
    subject VARCHAR(180) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('sent','archived') NOT NULL DEFAULT 'sent',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cm_dojo (dojo_id),
    INDEX idx_cm_sender (sender_id),
    INDEX idx_cm_recipient (recipient_id),
    CONSTRAINT fk_cm_dojo FOREIGN KEY (dojo_id) REFERENCES dojos(id) ON DELETE CASCADE,
    CONSTRAINT fk_cm_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_cm_recipient FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'send') {
            $recipient_id = (int)($_POST['recipient_id'] ?? 0);
            $subject = trim(sanitize_input($_POST['subject'] ?? ''));
            $message = trim(sanitize_input($_POST['message'] ?? ''));

            if ($subject === '' || $message === '') {
                $error = 'Subject and message are required.';
            } else {
                $valid_recipient = true;

                if ($recipient_id > 0) {
                    $check = $pdo->prepare("SELECT u.id FROM dojo_memberships m JOIN users u ON u.id = m.student_id WHERE m.dojo_id = ? AND m.student_id = ? AND m.status = 'approved' AND u.status = 'active' LIMIT 1");
                    $check->execute([$dojo_id, $recipient_id]);
                    $valid_recipient = (bool)$check->fetchColumn();
                }

                if (!$valid_recipient) {
                    $error = 'The selected student is not an active member of this dojo.';
                } else {
                    $insert = $pdo->prepare("INSERT INTO communication_messages (dojo_id, sender_id, recipient_id, subject, message) VALUES (?, ?, ?, ?, ?)");
                    $insert->execute([$dojo_id, $master_id, $recipient_id > 0 ? $recipient_id : null, $subject, $message]);
                    $message_id = (int)$pdo->lastInsertId();

                    log_audit_action(
                        $pdo,
                        $master_id,
                        'CREATE',
                        'communication_messages',
                        $message_id,
                        'Sent dojo communication: ' . $subject
                    );

                    $_SESSION['success_msg'] = $recipient_id > 0
                        ? 'Message sent to the selected student.'
                        : 'Broadcast message sent to the dojo.';
                    redirect('/master/communication.php');
                }
            }
        } elseif ($action === 'archive') {
            $message_id = (int)($_POST['message_id'] ?? 0);
            if ($message_id > 0) {
                $update = $pdo->prepare("UPDATE communication_messages SET status = 'archived' WHERE id = ? AND dojo_id = ? AND sender_id = ?");
                $update->execute([$message_id, $dojo_id, $master_id]);
                if ($update->rowCount() > 0) {
                    log_audit_action($pdo, $master_id, 'UPDATE', 'communication_messages', $message_id, 'Archived communication message');
                    $_SESSION['success_msg'] = 'Message archived.';
                } else {
                    $_SESSION['error_msg'] = 'Message not found.';
                }
                redirect('/master/communication.php');
            }
        }
    }
}

$students_stmt = $pdo->prepare("SELECT u.id, u.member_id, u.first_name, u.last_name, u.email FROM dojo_memberships m JOIN users u ON u.id = m.student_id WHERE m.dojo_id = ? AND m.status = 'approved' AND u.status = 'active' ORDER BY u.first_name, u.last_name");
$students_stmt->execute([$dojo_id]);
$students = $students_stmt->fetchAll();

$list = $pdo->prepare("SELECT cm.*, u.first_name, u.last_name, u.member_id FROM communication_messages cm JOIN users u ON u.id = cm.recipient_id WHERE cm.dojo_id = ? AND cm.sender_id = ? AND cm.status <> 'archived' ORDER BY cm.created_at DESC LIMIT 50");
$list->execute([$dojo_id, $master_id]);
$direct_messages = $list->fetchAll();

$broadcast = $pdo->prepare("SELECT * FROM communication_messages WHERE dojo_id = ? AND sender_id = ? AND recipient_id IS NULL AND status <> 'archived' ORDER BY created_at DESC LIMIT 50");
$broadcast->execute([$dojo_id, $master_id]);
$broadcasts = $broadcast->fetchAll();

$sent_total = count($direct_messages) + count($broadcasts);
$student_total = count($students);

$page_title = 'Communication Center';
require_once '../includes/header.php';
?>

<style>
.comm-wrap{max-width:1200px;margin:1.5rem auto}
.comm-hero{border-radius:24px;padding:1.8rem 2rem;color:#fff;background:linear-gradient(135deg,#080808,#1d1d1d 58%,#4d0808);box-shadow:0 22px 50px rgba(0,0,0,.14)}
.comm-kicker{font-size:.72rem;letter-spacing:.14em;text-transform:uppercase;font-weight:800;color:#ffd15e}
.comm-title{font-size:clamp(1.7rem,4vw,2.65rem);font-weight:900;margin:.25rem 0 .4rem}
.metric{background:#fff;border:0;border-radius:18px;padding:1rem 1.1rem;box-shadow:0 12px 30px rgba(17,24,39,.06);height:100%}
.metric-label{font-size:.7rem;text-transform:uppercase;letter-spacing:.08em;color:#888;font-weight:800}.metric-value{font-size:1.6rem;font-weight:900}
.panel{border:0;border-radius:20px;overflow:hidden;box-shadow:0 15px 38px rgba(17,24,39,.08)}.panel .card-header{background:#fff;border:0;padding:1.2rem 1.35rem}.panel .card-body{padding:1.35rem}
.form-control,.form-select{border-radius:12px;padding:.72rem .85rem}.message-card{border:1px solid #eee;border-radius:16px;padding:1rem;background:#fff}.message-card+.message-card{margin-top:.8rem}
.type-pill{display:inline-flex;border-radius:999px;padding:.35rem .6rem;font-size:.68rem;font-weight:800}.pill-broadcast{background:#fff0bd;color:#684e00}.pill-direct{background:#e4efff;color:#16477c}
</style>

<div class="comm-wrap">
    <section class="comm-hero mb-4">
        <div class="comm-kicker">Master Control • Communication</div>
        <div class="comm-title">Communication Center</div>
        <p class="mb-0" style="color:rgba(255,255,255,.72)">Send direct messages to students or broadcast an important update to the entire dojo.</p>
    </section>

    <?php if ($error): ?>
        <div class="alert alert-danger border-0 shadow-sm mb-4"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="metric"><div class="metric-label">Active students</div><div class="metric-value"><?= $student_total ?></div></div></div>
        <div class="col-md-4"><div class="metric"><div class="metric-label">Visible sent messages</div><div class="metric-value"><?= $sent_total ?></div></div></div>
        <div class="col-md-4"><div class="metric"><div class="metric-label">Delivery modes</div><div class="metric-value">2</div><div class="small text-muted">Direct + Broadcast</div></div></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card panel">
                <div class="card-header">
                    <div class="text-uppercase text-muted" style="font-size:.72rem;font-weight:800;letter-spacing:.08em;">Compose</div>
                    <h5 class="mb-0 mt-1">Send a Message</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                        <input type="hidden" name="action" value="send">

                        <div class="mb-3">
                            <label class="form-label fw-bold">Recipient</label>
                            <select name="recipient_id" class="form-select">
                                <option value="0">Broadcast to entire dojo</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?= (int)$student['id'] ?>"><?= htmlspecialchars($student['first_name'].' '.$student['last_name']) ?><?= $student['member_id'] ? ' — '.htmlspecialchars($student['member_id']) : '' ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Choose the first option to send the same message to the whole dojo.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Subject</label>
                            <input type="text" name="subject" class="form-control" maxlength="180" required placeholder="e.g. Saturday class timing update">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Message</label>
                            <textarea name="message" class="form-control" rows="7" maxlength="5000" required placeholder="Write your message here..."></textarea>
                        </div>

                        <button class="btn btn-dark w-100" type="submit"><i class="fas fa-paper-plane me-2"></i>Send Message</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card panel">
                <div class="card-header d-flex justify-content-between align-items-center gap-2">
                    <div><div class="text-uppercase text-muted" style="font-size:.72rem;font-weight:800;letter-spacing:.08em;">Sent</div><h5 class="mb-0 mt-1">Recent Communications</h5></div>
                    <a href="announcements.php" class="btn btn-sm btn-outline-secondary">Announcements</a>
                </div>
                <div class="card-body">
                    <?php foreach ($broadcasts as $item): ?>
                        <article class="message-card">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div><span class="type-pill pill-broadcast">BROADCAST</span><h6 class="fw-bold mt-2 mb-1"><?= htmlspecialchars($item['subject']) ?></h6></div>
                                <small class="text-muted"><?= date('M j, Y g:i A', strtotime($item['created_at'])) ?></small>
                            </div>
                            <p class="text-muted mb-0" style="white-space:pre-line;"><?= htmlspecialchars($item['message']) ?></p>
                        </article>
                    <?php endforeach; ?>

                    <?php foreach ($direct_messages as $item): ?>
                        <article class="message-card">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div><span class="type-pill pill-direct">DIRECT</span><h6 class="fw-bold mt-2 mb-1"><?= htmlspecialchars($item['subject']) ?></h6><div class="small text-muted">To: <?= htmlspecialchars($item['first_name'].' '.$item['last_name']) ?><?php if ($item['member_id']): ?> • <?= htmlspecialchars($item['member_id']) ?><?php endif; ?></div></div>
                                <small class="text-muted"><?= date('M j, Y g:i A', strtotime($item['created_at'])) ?></small>
                            </div>
                            <p class="text-muted mt-3 mb-3" style="white-space:pre-line;"><?= htmlspecialchars($item['message']) ?></p>
                            <form method="POST" onsubmit="return confirm('Archive this message?');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                <input type="hidden" name="action" value="archive">
                                <input type="hidden" name="message_id" value="<?= (int)$item['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-box-archive me-1"></i>Archive</button>
                            </form>
                        </article>
                    <?php endforeach; ?>

                    <?php if (!$broadcasts && !$direct_messages): ?>
                        <div class="text-center text-muted py-5"><i class="fas fa-comments fa-2x mb-3"></i><div class="fw-bold">No messages sent yet</div><div class="small">Your recent dojo communications will appear here.</div></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
