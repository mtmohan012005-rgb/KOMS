<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if (!has_role('master') && !has_role('senior') && !has_role('super_admin')) {
    $_SESSION['error_msg'] = 'Unauthorized access.';
    redirect('/index.php');
}

$session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
if (!$session_id) redirect('/master/attendance.php');

$stmt = $pdo->prepare("SELECT s.*, d.name AS dojo_name, d.master_id FROM attendance_sessions s JOIN dojos d ON s.dojo_id = d.id WHERE s.id = ? LIMIT 1");
$stmt->execute([$session_id]);
$session = $stmt->fetch();

if (!$session) {
    $_SESSION['error_msg'] = 'Attendance session not found.';
    redirect('/master/attendance.php');
}

if (has_role('master') && (int)$session['master_id'] !== (int)$_SESSION['user_id']) {
    $_SESSION['error_msg'] = 'Unauthorized session access.';
    redirect('/master/attendance.php');
}

$back_url = has_role('senior') ? '/senior/dashboard.php' : '/master/attendance.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_msg'] = 'Invalid form submission. Please try again.';
        redirect('/master/mark_attendance.php?session_id=' . $session_id);
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'lock') {
        if ($session['is_locked']) {
            $_SESSION['error_msg'] = 'This session is already locked.';
        } elseif (has_role('senior')) {
            $_SESSION['error_msg'] = 'Only the Master or Super Admin can lock an attendance session.';
        } else {
            $pdo->prepare('UPDATE attendance_sessions SET is_locked = 1 WHERE id = ?')->execute([$session_id]);
            log_audit_action($pdo, $_SESSION['user_id'], 'UPDATE', 'attendance', $session_id, "Locked attendance session #$session_id");
            $_SESSION['success_msg'] = 'Attendance session locked successfully.';
        }
        redirect('/master/mark_attendance.php?session_id=' . $session_id);
    }

    if ($action === 'save') {
        if ($session['is_locked']) {
            $_SESSION['error_msg'] = 'This session is locked and cannot be changed.';
            redirect('/master/mark_attendance.php?session_id=' . $session_id);
        }

        $attendees = $_POST['attendees'] ?? [];
        $single_id = isset($_POST['single_save']) ? (int)$_POST['single_save'] : 0;

        $check = $pdo->prepare('SELECT id FROM attendance_entries WHERE session_id = ? AND student_id = ? LIMIT 1');
        $update = $pdo->prepare('UPDATE attendance_entries SET status = ?, remarks = ?, marked_by = ?, marked_at = NOW() WHERE session_id = ? AND student_id = ?');
        $insert = $pdo->prepare('INSERT INTO attendance_entries (session_id, student_id, status, remarks, marked_by) VALUES (?, ?, ?, ?, ?)');

        $saved = 0;
        foreach ($attendees as $student_id => $data) {
            $student_id = (int)$student_id;
            if ($single_id > 0 && $student_id !== $single_id) continue;

            $status = $data['status'] ?? '';
            $allowed = ['present', 'absent', 'late', 'excused'];
            if (!in_array($status, $allowed, true)) continue;

            $remarks = sanitize_input($data['remarks'] ?? '');
            $check->execute([$session_id, $student_id]);

            if ($check->fetch()) {
                $update->execute([$status, $remarks, $_SESSION['user_id'], $session_id, $student_id]);
            } else {
                $insert->execute([$session_id, $student_id, $status, $remarks, $_SESSION['user_id']]);
            }
            $saved++;
        }

        log_audit_action($pdo, $_SESSION['user_id'], 'UPDATE', 'attendance', $session_id, "Saved attendance for $saved student(s)");
        $_SESSION['success_msg'] = "$saved attendance record(s) saved successfully.";
        redirect('/master/mark_attendance.php?session_id=' . $session_id);
    }
}

$page_title = 'Mark Attendance';
require_once '../includes/header.php';

$stmt = $pdo->prepare("\n    SELECT u.id, u.first_name, u.last_name, u.email, e.status, e.remarks\n    FROM dojo_memberships m\n    JOIN users u ON m.student_id = u.id\n    LEFT JOIN attendance_entries e ON e.student_id = u.id AND e.session_id = ?\n    WHERE m.dojo_id = ? AND m.status = 'approved'\n    ORDER BY u.first_name, u.last_name\n");
$stmt->execute([$session_id, $session['dojo_id']]);
$students = $stmt->fetchAll();

$present = $late = $absent = $excused = $marked = 0;
foreach ($students as $stu) {
    $status = $stu['status'] ?? '';
    if ($status !== '') $marked++;
    if ($status === 'present') $present++;
    if ($status === 'late') $late++;
    if ($status === 'absent') $absent++;
    if ($status === 'excused') $excused++;
}
$total = count($students);
$unmarked = max(0, $total - $marked);
?>

<style>
    .attendance-page { max-width: 1180px; margin: 1.5rem auto 2.5rem; }
    .attendance-hero { background: linear-gradient(135deg,#070707,#1b1b1b 58%,#520909); color:#fff; border-radius:26px; padding:1.7rem 1.8rem; box-shadow:0 22px 48px rgba(0,0,0,.16); }
    .eyebrow { color:#f3c95a; text-transform:uppercase; letter-spacing:.14em; font-size:.72rem; font-weight:800; }
    .hero-title { font-size:clamp(1.7rem,4vw,2.5rem); font-weight:900; margin:.25rem 0 .35rem; }
    .hero-meta { color:rgba(255,255,255,.72); }
    .stat-card { border:0; border-radius:18px; box-shadow:0 10px 26px rgba(17,24,39,.08); height:100%; }
    .stat-label { color:#777; font-size:.72rem; text-transform:uppercase; letter-spacing:.08em; font-weight:800; }
    .stat-value { font-size:1.55rem; font-weight:900; }
    .work-card { border:0; border-radius:20px; box-shadow:0 14px 34px rgba(17,24,39,.08); overflow:hidden; }
    .work-card .card-header { background:#fff; border:0; padding:1rem 1.2rem; }
    .table thead th { font-size:.74rem; text-transform:uppercase; letter-spacing:.06em; color:#777; white-space:nowrap; }
    .student-name { font-weight:800; }
    .student-email { color:#888; font-size:.76rem; }
    .status-select, .remarks-input { border-radius:10px; }
    .locked-box { border-radius:18px; }
    .sticky-actions { position:sticky; bottom:14px; z-index:20; }
    @media (max-width: 767px) {
        .attendance-hero { padding:1.35rem; }
        .table { min-width:780px; }
    }
</style>

<div class="attendance-page">
    <section class="attendance-hero mb-4">
        <div class="eyebrow">Training Session • Attendance</div>
        <div class="hero-title"><?= date('l, M j, Y', strtotime($session['session_date'])) ?></div>
        <div class="hero-meta">
            <strong class="text-white"><?= htmlspecialchars($session['dojo_name']) ?></strong>
            <span class="mx-2">•</span>
            <?= $session['start_time'] ? date('g:i A', strtotime($session['start_time'])) : 'Time not set' ?>
            <?php if ($session['end_time']): ?>
                - <?= date('g:i A', strtotime($session['end_time'])) ?>
            <?php endif; ?>
        </div>
    </section>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-2"><div class="card stat-card p-3"><div class="stat-label">Students</div><div class="stat-value"><?= $total ?></div></div></div>
        <div class="col-6 col-lg-2"><div class="card stat-card p-3"><div class="stat-label">Marked</div><div class="stat-value"><?= $marked ?></div></div></div>
        <div class="col-6 col-lg-2"><div class="card stat-card p-3"><div class="stat-label">Present</div><div class="stat-value text-success"><?= $present ?></div></div></div>
        <div class="col-6 col-lg-2"><div class="card stat-card p-3"><div class="stat-label">Late</div><div class="stat-value text-warning"><?= $late ?></div></div></div>
        <div class="col-6 col-lg-2"><div class="card stat-card p-3"><div class="stat-label">Absent</div><div class="stat-value text-danger"><?= $absent ?></div></div></div>
        <div class="col-6 col-lg-2"><div class="card stat-card p-3"><div class="stat-label">Unmarked</div><div class="stat-value"><?= $unmarked ?></div></div></div>
    </div>

    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
        <a href="<?= APP_URL . $back_url ?>" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Back</a>
        <?php if (!$session['is_locked']): ?>
            <button type="button" class="btn btn-outline-success" onclick="markAll('present')"><i class="fas fa-check-double me-2"></i>Mark All Present</button>
        <?php endif; ?>
    </div>

    <?php if ($session['is_locked']): ?>
        <div class="alert alert-secondary border-0 shadow-sm locked-box d-flex align-items-center mb-4 p-3">
            <i class="fas fa-lock fa-2x me-3"></i>
            <div><strong>Attendance Locked</strong><br><span class="small">This session is sealed. Existing attendance records can be viewed but not modified.</span></div>
        </div>
    <?php else: ?>
        <div class="alert alert-light border-0 shadow-sm mb-4">
            <i class="fas fa-circle-info me-2"></i>Select each student's status, add optional remarks, then use <strong>Save All Attendance</strong> or save an individual row.
        </div>
    <?php endif; ?>

    <div class="card work-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <div class="text-muted small text-uppercase fw-bold">Student Roster</div>
                <h5 class="mb-0 mt-1">Attendance Records</h5>
            </div>
            <?php if (!$session['is_locked'] && (has_role('master') || has_role('super_admin'))): ?>
                <form method="POST" onsubmit="return confirm('Lock this attendance session? You will not be able to edit it afterwards.');">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                    <input type="hidden" name="action" value="lock">
                    <button class="btn btn-warning"><i class="fas fa-lock me-2"></i>Lock Session</button>
                </form>
            <?php endif; ?>
        </div>

        <?php if (!$session['is_locked']): ?>
            <form method="POST" id="attendanceForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                <input type="hidden" name="action" value="save">
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Student</th>
                        <th>Status</th>
                        <th>Remarks</th>
                        <?php if (!$session['is_locked']): ?><th class="text-center">Save</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php if ($students): ?>
                    <?php foreach ($students as $stu): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="student-name"><i class="fas fa-user-circle me-2 text-muted"></i><?= htmlspecialchars($stu['first_name'] . ' ' . $stu['last_name']) ?></div>
                                <div class="student-email ms-4"><?= htmlspecialchars($stu['email']) ?></div>
                            </td>
                            <?php if ($session['is_locked']): ?>
                                <?php $badge = ['present'=>'success','absent'=>'danger','late'=>'warning text-dark','excused'=>'info'][$stu['status'] ?? ''] ?? 'secondary'; ?>
                                <td><span class="badge bg-<?= $badge ?> text-uppercase px-3 py-2"><?= htmlspecialchars($stu['status'] ?: 'Not Marked') ?></span></td>
                                <td><?= htmlspecialchars($stu['remarks'] ?: '-') ?></td>
                            <?php else: ?>
                                <td style="min-width:170px;">
                                    <select name="attendees[<?= (int)$stu['id'] ?>][status]" class="form-select form-select-sm status-select" required>
                                        <option value="" <?= !$stu['status'] ? 'selected' : '' ?> disabled>Select status</option>
                                        <option value="present" <?= $stu['status'] === 'present' ? 'selected' : '' ?>>Present</option>
                                        <option value="absent" <?= $stu['status'] === 'absent' ? 'selected' : '' ?>>Absent</option>
                                        <option value="late" <?= $stu['status'] === 'late' ? 'selected' : '' ?>>Late</option>
                                        <option value="excused" <?= $stu['status'] === 'excused' ? 'selected' : '' ?>>Excused</option>
                                    </select>
                                </td>
                                <td style="min-width:220px;"><input type="text" name="attendees[<?= (int)$stu['id'] ?>][remarks]" class="form-control form-control-sm remarks-input" value="<?= htmlspecialchars($stu['remarks'] ?? '') ?>" placeholder="Optional note"></td>
                                <td class="text-center"><button type="submit" name="single_save" value="<?= (int)$stu['id'] ?>" class="btn btn-sm btn-outline-dark" title="Save this student"><i class="fas fa-save"></i></button></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="text-center py-5 text-muted">No approved students are currently enrolled in this dojo.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (!$session['is_locked'] && $students): ?>
            <div class="card-footer bg-white border-0 p-3 sticky-actions text-end">
                <button type="submit" class="btn btn-dark btn-lg px-4"><i class="fas fa-check-circle me-2"></i>Save All Attendance</button>
            </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
function markAll(status) {
    document.querySelectorAll('.status-select').forEach(function(select) {
        select.value = status;
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>
