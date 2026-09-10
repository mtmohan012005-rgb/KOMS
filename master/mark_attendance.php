<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Allow both master and senior (sub-admin) to mark attendance
if (!has_role('master') && !has_role('senior') && !has_role('super_admin')) {
    $_SESSION['error_msg'] = "Unauthorized access.";
    redirect('/index.php');
}

$session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
if (!$session_id) redirect('/master/attendance.php');

// Fetch session and verify ownership
$stmt = $pdo->prepare("SELECT s.*, d.name as dojo_name, d.master_id FROM attendance_sessions s JOIN dojos d ON s.dojo_id = d.id WHERE s.id = ?");
$stmt->execute([$session_id]);
$session = $stmt->fetch();

if (!$session) {
    $_SESSION['error_msg'] = "Session not found.";
    redirect('/master/attendance.php');
}

// If master, ensure ownership
if (has_role('master') && $session['master_id'] != $_SESSION['user_id']) {
    $_SESSION['error_msg'] = "Unauthorized session access.";
    redirect('/master/attendance.php');
}

// Handle Permanent Locking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'lock') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_msg'] = "Invalid request.";
    } elseif ($session['is_locked']) {
        $_SESSION['error_msg'] = "Session is already locked.";
    } else {
        $pdo->prepare("UPDATE attendance_sessions SET is_locked = 1 WHERE id = ?")->execute([$session_id]);
        $_SESSION['success_msg'] = "Attendance session has been mathematically and permanently sealed.";
        log_audit_action($pdo, $_SESSION['user_id'], 'UPDATE', 'attendance', $session_id, "Sealed attendance session #$session_id");
        redirect("/master/mark_attendance.php?session_id=$session_id");
    }
}

// Handle Batch or Row Marking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['batch_attendance']) || isset($_POST['single_save']))) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_msg'] = "Invalid form submission.";
    } elseif ($session['is_locked']) {
        $_SESSION['error_msg'] = "Cannot modify a sealed attendance session.";
    } else {
        $attendees = $_POST['attendees'] ?? [];
        $single_id = isset($_POST['single_save']) ? (int)$_POST['single_save'] : null;

        $check = $pdo->prepare("SELECT id FROM attendance_entries WHERE session_id = ? AND student_id = ?");
        $update = $pdo->prepare("UPDATE attendance_entries SET status = ?, remarks = ?, marked_by = ?, marked_at = NOW() WHERE session_id = ? AND student_id = ?");
        $insert = $pdo->prepare("INSERT INTO attendance_entries (session_id, student_id, status, remarks, marked_by) VALUES (?, ?, ?, ?, ?)");

        $saved_count = 0;
        foreach ($attendees as $student_id => $data) {
            $student_id = (int)$student_id;
            // If single_save clicked, process only that student
            if ($single_id && $student_id !== $single_id) continue;

            $status = sanitize_input($data['status'] ?? '');
            if (!$status) continue;
            $remarks = sanitize_input($data['remarks'] ?? '');

            $check->execute([$session_id, $student_id]);
            if ($check->fetch()) {
                $update->execute([$status, $remarks, $_SESSION['user_id'], $session_id, $student_id]);
            } else {
                $insert->execute([$session_id, $student_id, $status, $remarks, $_SESSION['user_id']]);
            }
            $saved_count++;
        }

        $_SESSION['success_msg'] = "Attendance records saved ($saved_count students updated).";
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

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2>Session: <?= date('l, M j, Y', strtotime($session['session_date'])) ?></h2>
        <p class="text-muted mb-0">Dojo: <strong><?= htmlspecialchars($session['dojo_name']) ?></strong> | Time: <?= $session['start_time'] ? date('g:i A', strtotime($session['start_time'])) : 'Scheduled' ?></p>
    </div>
    <div class="d-flex gap-2">
        <?php if (has_role('senior')): ?>
            <a href="<?= APP_URL ?>/senior/dashboard.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
        <?php else: ?>
            <a href="attendance.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
        <?php endif; ?>

        <?php if (!$session['is_locked'] && (has_role('master') || has_role('super_admin'))): ?>
            <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently lock this session? It will be mathematically sealed and immutable.');">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" name="action" value="lock">
                <button type="submit" class="btn btn-warning"><i class="fas fa-lock me-1"></i>Seal Session</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($session['is_locked']): ?>
<div class="alert alert-secondary d-flex align-items-center mb-4 shadow-sm border-0">
    <i class="fas fa-lock fa-2x text-dark me-3"></i>
    <div>
        <strong>Session Cryptographically Sealed:</strong> This attendance session is locked. Records are permanent and immutable to safeguard grading eligibility.
    </div>
</div>
<?php else: ?>
<div class="card shadow-sm border-0 mb-3 bg-light">
    <div class="card-body py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="small text-muted"><i class="fas fa-info-circle me-1"></i>Mark all students and click <strong>Save All Attendance</strong>.</span>
        <button type="button" class="btn btn-sm btn-outline-success" onclick="markAll('present')">
            <i class="fas fa-check-double me-1"></i>Quick Mark All Present
        </button>
    </div>
</div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <?php if (!$session['is_locked']): ?>
        <form method="POST" action="" id="attendanceForm">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <input type="hidden" name="batch_attendance" value="1">
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 30%;">Student Name</th>
                        <th style="width: 25%;">Attendance Status</th>
                        <th style="width: 30%;">Notes / Remarks</th>
                        <?php if (!$session['is_locked']): ?>
                            <th style="width: 15%; text-align: center;">Row Save</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($students) > 0): ?>
                        <?php foreach ($students as $stu): ?>
                        <tr>
                            <td class="fw-bold">
                                <i class="fas fa-user-circle text-muted me-2"></i>
                                <?= htmlspecialchars($stu['first_name'] . ' ' . $stu['last_name']) ?>
                            </td>

                            <?php if ($session['is_locked']): ?>
                                <td>
                                    <?php 
                                    $bg = ['present'=>'success', 'absent'=>'danger', 'late'=>'warning text-dark', 'excused'=>'info'][$stu['status'] ?? 'absent'];
                                    ?>
                                    <span class="badge bg-<?= $bg ?> text-uppercase"><?= $stu['status'] ?: 'Not Marked' ?></span>
                                </td>
                                <td><?= htmlspecialchars($stu['remarks'] ?: '-') ?></td>
                            <?php else: ?>
                                <td>
                                    <select name="attendees[<?= $stu['id'] ?>][status]" class="form-select form-select-sm status-select" required>
                                        <option value="" disabled <?= !$stu['status'] ? 'selected' : '' ?>>Select status...</option>
                                        <option value="present" <?= $stu['status'] === 'present' ? 'selected' : '' ?>>Present</option>
                                        <option value="absent" <?= $stu['status'] === 'absent' ? 'selected' : '' ?>>Absent</option>
                                        <option value="late" <?= $stu['status'] === 'late' ? 'selected' : '' ?>>Late</option>
                                        <option value="excused" <?= $stu['status'] === 'excused' ? 'selected' : '' ?>>Excused</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="attendees[<?= $stu['id'] ?>][remarks]" class="form-control form-control-sm" placeholder="Optional notes" value="<?= htmlspecialchars($stu['remarks'] ?? '') ?>">
                                </td>
                                <td class="text-center">
                                    <button type="submit" name="single_save" value="<?= $stu['id'] ?>" class="btn btn-sm btn-outline-primary" title="Save this row">
                                        <i class="fas fa-save"></i>
                                    </button>
                                </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center py-4 text-muted">No active students found in this dojo.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (!$session['is_locked'] && count($students) > 0): ?>
            <div class="card-footer bg-white text-end py-3">
                <button type="submit" class="btn btn-success btn-lg px-4">
                    <i class="fas fa-check-circle me-2"></i>Save All Attendance
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
function markAll(status) {
    document.querySelectorAll('.status-select').forEach(select => {
        select.value = status;
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>
