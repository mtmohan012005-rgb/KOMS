<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('super_admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error_msg'] = "Invalid request.";
    } else {
        $dojo_id = (int)$_POST['dojo_id'];
        $action = $_POST['action'];
        $status = ($action === 'approve') ? 'approved' : 'rejected';
        
        $stmt = $pdo->prepare("UPDATE dojos SET status = ?, approval_date = NOW() WHERE id = ?");
        if ($stmt->execute([$status, $dojo_id])) {
            $_SESSION['success_msg'] = "Dojo has been {$status}.";
            log_audit_action($pdo, $_SESSION['user_id'], 'UPDATE', 'dojos', $dojo_id, "Admin {$status} dojo");
        } else {
            $_SESSION['error_msg'] = "Failed to update dojo status.";
        }
    }
    redirect('/admin/dojos.php');
}

$page_title = 'Manage Dojos';
require_once '../includes/header.php';

$stmt = $pdo->query("SELECT d.*, u.first_name, u.last_name FROM dojos d JOIN users u ON d.master_id = u.id ORDER BY FIELD(d.status, 'pending', 'approved', 'rejected', 'inactive'), d.created_at DESC");
$dojos = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Organization Dojos</h2>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Dojo Name</th>
                        <th>Master</th>
                        <th>Location</th>
                        <th>Submitted On</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dojos as $dojo): ?>
                    <tr>
                        <td class="fw-bold"><?= htmlspecialchars($dojo['name']) ?></td>
                        <td><?= htmlspecialchars($dojo['first_name'] . ' ' . $dojo['last_name']) ?></td>
                        <td><?= htmlspecialchars($dojo['location']) ?></td>
                        <td><?= date('M j, Y', strtotime($dojo['created_at'])) ?></td>
                        <td>
                            <?php
                            $badge = [
                                'pending' => 'warning text-dark',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'inactive' => 'secondary'
                            ][$dojo['status']];
                            ?>
                            <span class="badge bg-<?= $badge ?> text-uppercase"><?= $dojo['status'] ?></span>
                        </td>
                        <td>
                            <?php if ($dojo['status'] === 'pending'): ?>
                            <form method="POST" action="" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                <input type="hidden" name="dojo_id" value="<?= $dojo['id'] ?>">
                                <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">Approve</button>
                                <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger">Reject</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
