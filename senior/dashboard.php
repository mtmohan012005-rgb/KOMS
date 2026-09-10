<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('senior');

$page_title = 'Senior Belt Dashboard';
require_once '../includes/header.php';

// Fetch open training sessions for marking
$stmt = $pdo->query("
    SELECT s.*, d.name as dojo_name, COUNT(e.id) as attendance_count 
    FROM attendance_sessions s 
    JOIN dojos d ON s.dojo_id = d.id 
    LEFT JOIN attendance_entries e ON s.id = e.session_id 
    WHERE s.is_locked = 0 
    GROUP BY s.id 
    ORDER BY s.session_date DESC 
    LIMIT 10
");
$open_sessions = $stmt->fetchAll();

// Fetch student list for monitoring
$stu_stmt = $pdo->query("
    SELECT u.id, u.first_name, u.last_name, u.email, d.name as dojo_name,
    (SELECT new_belt FROM grading_history WHERE student_id = u.id ORDER BY exam_date DESC LIMIT 1) as current_belt
    FROM users u
    JOIN dojo_memberships m ON u.id = m.student_id
    JOIN dojos d ON m.dojo_id = d.id
    WHERE u.role = 'student' AND m.status = 'approved'
    ORDER BY u.first_name ASC
    LIMIT 15
");
$students = $stu_stmt->fetchAll();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2">Senior Assistant Instructor Dashboard</h1>
        <p class="text-muted">Daily Attendance Logging and Student Monitoring (Financial and Grading modules disabled).</p>
    </div>
    <span class="badge bg-info text-dark fs-6 px-3 py-2">Role: Senior / Sub-Admin</span>
</div>

<div class="row mb-4">
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-calendar-check text-success me-2"></i>Active Training Sessions</h5>
                <span class="badge bg-success"><?= count($open_sessions) ?> Open</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Dojo</th>
                                <th>Schedule</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($open_sessions) > 0): ?>
                                <?php foreach ($open_sessions as $s): ?>
                                <tr>
                                    <td class="fw-bold"><?= date('M j, Y', strtotime($s['session_date'])) ?></td>
                                    <td><?= htmlspecialchars($s['dojo_name']) ?></td>
                                    <td><?= htmlspecialchars($s['start_time'] ? date('g:i A', strtotime($s['start_time'])) : 'Scheduled') ?></td>
                                    <td>
                                        <a href="<?= APP_URL ?>/master/mark_attendance.php?session_id=<?= $s['id'] ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-check-circle me-1"></i>Mark
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">No open sessions to mark right now.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-user-graduate text-primary me-2"></i>Student Roster (Read-Only)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Student</th>
                                <th>Dojo</th>
                                <th>Current Belt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $stu): ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($stu['first_name'] . ' ' . $stu['last_name']) ?></td>
                                <td><?= htmlspecialchars($stu['dojo_name']) ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($stu['current_belt'] ?: 'White') ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
