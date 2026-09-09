<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('master');

// Get master's dojo
$stmt = $pdo->prepare("SELECT id FROM dojos WHERE master_id = ? AND status = 'approved' LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$dojo_id = $stmt->fetchColumn();

if (!$dojo_id) {
    $_SESSION['error_msg'] = "You need an active dojo to manage students.";
    redirect('/master/dashboard.php');
}

// Handle request process (same logic as dashboard but standalone page)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error_msg'] = "Invalid request.";
    } else {
        $req_id = (int)$_POST['request_id'];
        $action = $_POST['action'];
        $status = ($action === 'approve') ? 'approved' : 'rejected';
        
        // Ensure request belongs to this master's dojo
        $stmt = $pdo->prepare("UPDATE dojo_memberships SET status = ?, joined_at = IF(?='approved', NOW(), NULL) WHERE id = ? AND dojo_id = ? AND status = 'pending'");
        if ($stmt->execute([$status, $status, $req_id, $dojo_id])) {
            $_SESSION['success_msg'] = "Student request has been {$status}.";
            log_audit_action($pdo, $_SESSION['user_id'], 'UPDATE', 'memberships', $req_id, "Master {$status} student join request");
        } else {
            $_SESSION['error_msg'] = "Failed to update request.";
        }
    }
    redirect('/master/requests.php');
}

$page_title = 'Student Requests';
require_once '../includes/header.php';

$stmt = $pdo->prepare("SELECT m.id, u.first_name, u.last_name, u.email, m.created_at FROM dojo_memberships m JOIN users u ON m.student_id = u.id WHERE m.dojo_id = ? AND m.status = 'pending' ORDER BY m.created_at DESC");
$stmt->execute([$dojo_id]);
$requests = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Pending Student Requests</h2>
    <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Requested On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($requests) > 0): ?>
                        <?php foreach ($requests as $req): ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($req['first_name'] . ' ' . $req['last_name']) ?></td>
                            <td><?= htmlspecialchars($req['email']) ?></td>
                            <td><?= date('M j, Y - g:i A', strtotime($req['created_at'])) ?></td>
                            <td>
                                <form method="POST" action="">
                                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                    <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                    <button type="submit" name="action" value="approve" class="btn btn-success"><i class="fas fa-check"></i> Approve</button>
                                    <button type="submit" name="action" value="reject" class="btn btn-danger"><i class="fas fa-times"></i> Reject</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">No pending student requests at this time.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
