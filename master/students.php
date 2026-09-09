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

$page_title = 'My Students';
require_once '../includes/header.php';

$stmt = $pdo->prepare("
    SELECT u.*, m.joined_at,
    (SELECT new_belt FROM grading_history WHERE student_id = u.id ORDER BY exam_date DESC LIMIT 1) as current_belt
    FROM dojo_memberships m 
    JOIN users u ON m.student_id = u.id 
    WHERE m.dojo_id = ? AND m.status = 'approved' 
    ORDER BY u.first_name, u.last_name
");
$stmt->execute([$dojo_id]);
$students = $stmt->fetchAll();
?>

<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h2>Active Students</h2>
            <p class="text-muted">Manage the students enrolled in your dojo.</p>
        </div>
        <a href="grading.php" class="btn btn-outline-primary"><i class="fas fa-medal me-2"></i>Manage Grading</a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Current Belt</th>
                        <th>Joined Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $stu): ?>
                    <tr>
                        <td class="fw-bold">
                            <i class="fas fa-user-circle text-muted me-2"></i>
                            <?= htmlspecialchars($stu['first_name'] . ' ' . $stu['last_name']) ?>
                        </td>
                        <td><?= htmlspecialchars($stu['email']) ?></td>
                        <td><?= htmlspecialchars($stu['phone'] ?: 'N/A') ?></td>
                        <td>
                            <span class="badge bg-primary"><?= htmlspecialchars($stu['current_belt'] ?: 'White') ?></span>
                        </td>
                        <td><?= date('M j, Y', strtotime($stu['joined_at'])) ?></td>
                        <td>
                            <a href="attendance.php" class="btn btn-sm btn-outline-secondary">History</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($students)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">No active students found in your dojo.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
