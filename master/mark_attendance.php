<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('master');

$session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
if (!$session_id) redirect('/master/attendance.php');

// Fetch session and verify ownership
$stmt = $pdo->prepare("SELECT s.*, d.name as dojo_name FROM attendance_sessions s JOIN dojos d ON s.dojo_id = d.id WHERE s.id = ? AND d.master_id = ?");
$stmt->execute([$session_id, $_SESSION['user_id']]);
$session = $stmt->fetch();

if (!$session) {
    $_SESSION['error_msg'] = "Session not found.";
    redirect('/master/attendance.php');
}

// Handle Locking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'lock') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error_msg'] = "Invalid request.";
    } elseif ($session['is_locked']) {
        $_SESSION['error_msg'] = "Session is already locked.";
    } else {
        $pdo->prepare("UPDATE attendance_sessions SET is_locked = 1 WHERE id = ?")->execute([$session_id]);
        $_SESSION['success_msg'] = "Attendance session has been permanently locked.";
        log_audit_action($pdo, $_SESSION['user_id'], 'UPDATE', 'attendance', $session_id, "Locked attendance session");
        redirect("/master/mark_attendance.php?session_id=$session_id");
    }
}

// Handle Marking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_attendance'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error_msg'] = "Invalid form submission.";
    } elseif ($session['is_locked']) {
        $_SESSION['error_msg'] = "Cannot modify a locked attendance session.";
    } else {
        $student_id = (int)$_POST['student_id'];
        $status = sanitize_input($_POST['status']);
        $remarks = sanitize_input($_POST['remarks']);
        
        // Upsert logic
        $check = $pdo->prepare("SELECT id FROM attendance_entries WHERE session_id = ? AND student_id = ?");
        $check->execute([$session_id, $student_id]);
        
        if ($check->fetch()) {
            $update = $pdo->prepare("UPDATE attendance_entries SET status = ?, remarks = ?, marked_by = ?, marked_at = NOW() WHERE session_id = ? AND student_id = ?");
            $update->execute([$status, $remarks, $_SESSION['user_id'], $session_id, $student_id]);
        } else {
            $insert = $pdo->prepare("INSERT INTO attendance_entries (session_id, student_id, status, remarks, marked_by) VALUES (?, ?, ?, ?, ?)");
            $insert->execute([$session_id, $student_id, $status, $remarks, $_SESSION['user_id']]);
        }
        $_SESSION['success_msg'] = "Attendance updated.";
        redirect("/master/mark_attendance.php?session_id=$session_id");
    }
}

$page_title = 'Mark Attendance';
require_once '../includes/header.php';

// Fetch all active students in dojo, LEFT JOIN with entries for this session
$stmt = $pdo->prepare("
    SELECT u.id, u.first_name, u.last_name, e.status, e.remarks 
    FROM dojo_memberships m 
    JOIN users u ON m.student_id = u.id 
    LEFT JOIN attendance_entries e ON u.id = e.student_id AND e.session_id = ?
    WHERE m.dojo_id = ? AND m.status = 'approved'
    ORDER BY u.first_name, u.last_name
");
$stmt->execute([$session_id, $session['dojo_id']]);
$students = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Session: <?= date('M j, Y', strtotime($session['session_date'])) ?></h2>
        <p class="text-muted mb-0">Dojo: <?= htmlspecialchars($session['dojo_name']) ?></p>
    </div>
    <div>
        <a href="attendance.php" class="btn btn-outline-secondary me-2">Back</a>
        <?php if (!$session['is_locked']): ?>
            <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure? Once locked, attendance cannot be modified.');">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" name="action" value="lock">
                <button type="submit" class="btn btn-warning"><i class="fas fa-lock"></i> Lock Session</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($session['is_locked']): ?>
<div class="alert alert-secondary">
    <i class="fas fa-lock text-dark"></i> This attendance session is locked and read-only.
</div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Student Name</th>
                    <th>Status</th>
                    <th>Remarks</th>
                    <?php if (!$session['is_locked']): ?><th>Action</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $stu): ?>
                <tr>
                    <td class="fw-bold"><?= htmlspecialchars($stu['first_name'] . ' ' . $stu['last_name']) ?></td>
                    <?php if ($session['is_locked']): ?>
                        <td>
                            <?php 
                            $bg = ['present'=>'success', 'absent'=>'danger', 'late'=>'warning', 'excused'=>'info'][$stu['status'] ?? 'absent'];
                            ?>
                            <span class="badge bg-<?= $bg ?> text-uppercase"><?= $stu['status'] ?: 'Not Marked' ?></span>
                        </td>
                        <td><?= htmlspecialchars($stu['remarks'] ?: '-') ?></td>
                    <?php else: ?>
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            <input type="hidden" name="mark_attendance" value="1">
                            <input type="hidden" name="student_id" value="<?= $stu['id'] ?>">
                            
                            <td>
                                <select name="status" class="form-select form-select-sm" required>
                                    <option value="" disabled <?= !$stu['status'] ? 'selected' : '' ?>>Select...</option>
                                    <option value="present" <?= $stu['status'] === 'present' ? 'selected' : '' ?>>Present</option>
                                    <option value="absent" <?= $stu['status'] === 'absent' ? 'selected' : '' ?>>Absent</option>
                                    <option value="late" <?= $stu['status'] === 'late' ? 'selected' : '' ?>>Late</option>
                                    <option value="excused" <?= $stu['status'] === 'excused' ? 'selected' : '' ?>>Excused</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="remarks" class="form-control form-control-sm" placeholder="Optional notes" value="<?= htmlspecialchars($stu['remarks'] ?? '') ?>">
                            </td>
                            <td>
                                <button type="submit" class="btn btn-sm btn-primary">Save</button>
                            </td>
                        </form>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
