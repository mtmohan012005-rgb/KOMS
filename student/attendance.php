<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('student');

$page_title = 'My Attendance';
require_once '../includes/header.php';

// Fetch read-only attendance history for student
$stmt = $pdo->prepare("
    SELECT s.session_date, s.start_time, s.end_time, s.is_locked, e.status, e.remarks, u.first_name, u.last_name
    FROM attendance_entries e 
    JOIN attendance_sessions s ON e.session_id = s.id 
    LEFT JOIN users u ON s.instructor_id = u.id
    WHERE e.student_id = ? 
    ORDER BY s.session_date DESC
");
$stmt->execute([$_SESSION['user_id']]);
$records = $stmt->fetchAll();

// Calculate stats
$total = count($records);
$present = 0;
foreach($records as $r) { if($r['status'] === 'present') $present++; }
$percentage = $total > 0 ? round(($present / $total) * 100) : 0;
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-8">
        <h2>Attendance History</h2>
        <p class="text-muted">Review your past training sessions and attendance records.</p>
    </div>
    <div class="col-md-4 text-md-end">
        <div class="card bg-light border-0 shadow-sm d-inline-block p-3 text-center">
            <h6 class="text-muted mb-1 text-uppercase">Overall Attendance</h6>
            <h2 class="<?= $percentage >= 80 ? 'text-success' : 'text-warning' ?> mb-0"><?= $percentage ?>%</h2>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Instructor</th>
                        <th>Status</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($total > 0): ?>
                        <?php foreach ($records as $r): ?>
                        <tr>
                            <td class="fw-bold"><?= date('M j, Y', strtotime($r['session_date'])) ?></td>
                            <td>
                                <?= $r['start_time'] ? date('g:i A', strtotime($r['start_time'])) : '--' ?>
                            </td>
                            <td><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></td>
                            <td>
                                <?php 
                                $bg = ['present'=>'success', 'absent'=>'danger', 'late'=>'warning', 'excused'=>'info'][$r['status']];
                                ?>
                                <span class="badge bg-<?= $bg ?> text-uppercase"><?= $r['status'] ?></span>
                                <?php if ($r['is_locked']): ?>
                                    <i class="fas fa-lock text-muted ms-1" title="Locked Record"></i>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted"><?= htmlspecialchars($r['remarks'] ?: '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No attendance records found yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
