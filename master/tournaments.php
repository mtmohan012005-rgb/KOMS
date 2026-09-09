<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('master');

// Get master's dojo
$stmt = $pdo->prepare("SELECT id FROM dojos WHERE master_id = ? AND status = 'approved' LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$dojo_id = $stmt->fetchColumn();

if (!$dojo_id) redirect('/master/dashboard.php');

// Handle Selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error_msg'] = "Invalid request.";
    } else {
        $reg_id = (int)$_POST['reg_id'];
        $action = $_POST['action'];
        $status = ($action === 'select') ? 'selected' : 'rejected';
        
        // Ensure request belongs to this master's dojo
        $stmt = $pdo->prepare("UPDATE tournament_registrations SET status = ?, reviewed_by = ? WHERE id = ? AND dojo_id = ?");
        if ($stmt->execute([$status, $_SESSION['user_id'], $reg_id, $dojo_id])) {
            $_SESSION['success_msg'] = "Student has been {$status} for the tournament.";
        } else {
            $_SESSION['error_msg'] = "Failed to update status.";
        }
    }
    redirect('/master/tournaments.php');
}

$page_title = 'Tournament Selection';
require_once '../includes/header.php';

// Fetch all registrations from students in this dojo
$stmt = $pdo->prepare("
    SELECT r.id, r.status, t.name as tournament_name, t.event_date, u.first_name, u.last_name 
    FROM tournament_registrations r 
    JOIN tournaments t ON r.tournament_id = t.id 
    JOIN users u ON r.student_id = u.id 
    WHERE r.dojo_id = ? 
    ORDER BY t.event_date DESC, r.created_at ASC
");
$stmt->execute([$dojo_id]);
$registrations = $stmt->fetchAll();
?>

<div class="row mb-4">
    <div class="col-12">
        <h2>Tournament Participants Review</h2>
        <p class="text-muted">Review applications from your students and select who will represent your dojo.</p>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Student Name</th>
                        <th>Tournament</th>
                        <th>Event Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registrations as $r): ?>
                    <tr>
                        <td class="fw-bold"><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></td>
                        <td><?= htmlspecialchars($r['tournament_name']) ?></td>
                        <td><?= date('M j, Y', strtotime($r['event_date'])) ?></td>
                        <td>
                            <?php 
                            $bg = ['pending_review'=>'warning text-dark', 'selected'=>'success', 'rejected'=>'danger'][$r['status']];
                            ?>
                            <span class="badge bg-<?= $bg ?> text-uppercase"><?= str_replace('_', ' ', $r['status']) ?></span>
                        </td>
                        <td>
                            <?php if ($r['status'] === 'pending_review'): ?>
                            <form method="POST" action="" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                <input type="hidden" name="reg_id" value="<?= $r['id'] ?>">
                                <button type="submit" name="action" value="select" class="btn btn-sm btn-success">Select</button>
                                <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger">Reject</button>
                            </form>
                            <?php else: ?>
                                <span class="text-muted small">Reviewed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($registrations)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">No tournament registrations found for your students.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
