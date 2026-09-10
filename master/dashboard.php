<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('master');

$page_title = 'Master Dashboard';
require_once '../includes/header.php';

// Get the master's dojo
$stmt = $pdo->prepare("SELECT id, name, status FROM dojos WHERE master_id = ? LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$my_dojo = $stmt->fetch();

if (!$my_dojo) {
    echo '<div class="alert alert-warning mt-4">You have not registered a dojo yet. <a href="'.APP_URL.'/master/register_dojo.php" class="alert-link">Register your Dojo now</a>.</div>';
    require_once '../includes/footer.php';
    exit();
}

$dojo_id = $my_dojo['id'];

// Quick Stats
$stats = [
    'students' => $pdo->prepare("SELECT COUNT(*) FROM dojo_memberships WHERE dojo_id = ? AND status = 'approved'"),
    'pending_reqs' => $pdo->prepare("SELECT COUNT(*) FROM dojo_memberships WHERE dojo_id = ? AND status = 'pending'")
];
$stats['students']->execute([$dojo_id]);
$stats['pending_reqs']->execute([$dojo_id]);
$active_students_count = (int)$stats['students']->fetchColumn();
$pending_reqs_count = (int)$stats['pending_reqs']->fetchColumn();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Dojo Dashboard: <?= htmlspecialchars($my_dojo['name']) ?></h1>
    <?php if ($my_dojo['status'] === 'pending'): ?>
        <span class="badge bg-warning text-dark fs-6">Pending Admin Approval</span>
    <?php elseif ($my_dojo['status'] === 'approved'): ?>
        <span class="badge bg-success fs-6">Active</span>
    <?php endif; ?>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-info text-white shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">Active Students</h5>
                <h2 class="display-4"><?= $active_students_count ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-warning text-dark shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">Pending Join Requests</h5>
                <h2 class="display-4"><?= $pending_reqs_count ?></h2>
                <?php if ($pending_reqs_count > 0): ?>
                    <a href="requests.php" class="btn btn-sm btn-dark mt-2">Review Requests</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">Today's Attendance</h5>
                <h2 class="display-4">-</h2>
                <a href="attendance.php" class="btn btn-sm btn-light mt-2">Take Attendance</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Join Requests</h5>
                <a href="requests.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Requested On</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->prepare("SELECT m.id, u.first_name, u.last_name, m.created_at FROM dojo_memberships m JOIN users u ON m.student_id = u.id WHERE m.dojo_id = ? AND m.status = 'pending' ORDER BY m.created_at DESC LIMIT 5");
                            $stmt->execute([$dojo_id]);
                            while ($req = $stmt->fetch()):
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($req['first_name'] . ' ' . $req['last_name']) ?></td>
                                <td><?= date('M j, Y', strtotime($req['created_at'])) ?></td>
                                <td>
                                    <form method="POST" action="process_request.php" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                        <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                        <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">Approve</button>
                                        <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger">Reject</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">Dojo Management</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="edit_dojo.php" class="btn btn-outline-secondary text-start"><i class="fas fa-edit me-2"></i> Edit Dojo Profile</a>
                    <a href="students.php" class="btn btn-outline-primary text-start"><i class="fas fa-user-graduate me-2"></i> Manage Students</a>
                    <a href="fees.php" class="btn btn-outline-success text-start"><i class="fas fa-money-bill-wave me-2"></i> Fee Management</a>
                    <a href="grading.php" class="btn btn-outline-info text-start"><i class="fas fa-medal me-2"></i> Grading & Belts</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
