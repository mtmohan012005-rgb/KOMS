<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('master');

$stmt = $pdo->prepare("SELECT id, name FROM dojos WHERE master_id = ? AND status = 'approved' LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$dojo = $stmt->fetch();
if (!$dojo) {
    redirect('/master/dashboard.php');
}
$dojo_id = (int)$dojo['id'];

$allowed_belts = ['White','Yellow','Orange','Green','Blue','Purple','Brown','Black (1st Dan)','Black (2nd Dan)','Black (3rd Dan)'];
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_grading'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        $student_id = (int)($_POST['student_id'] ?? 0);
        $new_belt = sanitize_input($_POST['new_belt'] ?? '');
        $date = sanitize_input($_POST['exam_date'] ?? '');
        $grade = sanitize_input($_POST['grade'] ?? '');
        $remarks = sanitize_input($_POST['remarks'] ?? '');

        if (!$student_id || !in_array($new_belt, $allowed_belts, true) || $date === '') {
            $error = 'Please complete all required grading fields.';
        } elseif (strtotime($date) > strtotime(date('Y-m-d'))) {
            $error = 'Exam date cannot be in the future.';
        } else {
            $member = $pdo->prepare("SELECT u.id FROM dojo_memberships m JOIN users u ON m.student_id = u.id WHERE m.dojo_id = ? AND m.student_id = ? AND m.status = 'approved' LIMIT 1");
            $member->execute([$dojo_id, $student_id]);

            if (!$member->fetch()) {
                $error = 'Selected student is not an active member of this dojo.';
            } else {
                $prev_stmt = $pdo->prepare("SELECT new_belt FROM grading_history WHERE student_id = ? AND dojo_id = ? ORDER BY exam_date DESC, id DESC LIMIT 1");
                $prev_stmt->execute([$student_id, $dojo_id]);
                $prev_belt = $prev_stmt->fetchColumn() ?: 'White';

                $insert = $pdo->prepare("INSERT INTO grading_history (student_id, dojo_id, previous_belt, new_belt, exam_date, grade, instructor_id, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $insert->execute([$student_id, $dojo_id, $prev_belt, $new_belt, $date, $grade, $_SESSION['user_id'], $remarks]);

                $grading_id = (int)$pdo->lastInsertId();
                log_audit_action($pdo, $_SESSION['user_id'], 'CREATE', 'grading', $grading_id, 'Added grading record for student ' . $student_id);
                $_SESSION['success_msg'] = 'Grading record saved successfully.';
                redirect('/master/grading.php');
            }
        }
    }
}

$page_title = 'Grading & Belts';
require_once '../includes/header.php';

$stu_stmt = $pdo->prepare("SELECT u.id, u.first_name, u.last_name, COALESCE((SELECT gh.new_belt FROM grading_history gh WHERE gh.student_id = u.id AND gh.dojo_id = ? ORDER BY gh.exam_date DESC, gh.id DESC LIMIT 1), 'White') AS current_belt FROM dojo_memberships m JOIN users u ON m.student_id = u.id WHERE m.dojo_id = ? AND m.status = 'approved' ORDER BY u.first_name, u.last_name");
$stu_stmt->execute([$dojo_id, $dojo_id]);
$students = $stu_stmt->fetchAll();

$hist_stmt = $pdo->prepare("SELECT g.*, u.first_name, u.last_name FROM grading_history g JOIN users u ON g.student_id = u.id WHERE g.dojo_id = ? ORDER BY g.exam_date DESC, g.id DESC LIMIT 50");
$hist_stmt->execute([$dojo_id]);
$history = $hist_stmt->fetchAll();

$b_count = [];
foreach ($students as $s) {
    $belt = $s['current_belt'] ?: 'White';
    $b_count[$belt] = ($b_count[$belt] ?? 0) + 1;
}
?>

<style>
.grading-wrap{max-width:1180px;margin:1.5rem auto}.grading-hero{padding:1.7rem 1.9rem;border-radius:24px;background:linear-gradient(135deg,#080808,#202020 58%,#4b0808);color:#fff;box-shadow:0 20px 50px rgba(0,0,0,.14)}
.kicker{font-size:.7rem;letter-spacing:.14em;text-transform:uppercase;font-weight:800;color:#f4c84b}.hero-title{font-weight:900;font-size:clamp(1.7rem,4vw,2.6rem);margin:.25rem 0}.hero-sub{color:rgba(255,255,255,.72);margin:0}.panel{border:0;border-radius:20px;overflow:hidden;box-shadow:0 15px 38px rgba(17,24,39,.08)}.panel .card-header{background:#fff;border:0;padding:1.2rem 1.35rem}.panel .card-body{background:#fff;padding:1.35rem}
.form-label{font-size:.86rem;font-weight:750}.form-control,.form-select{border-radius:12px;padding:.72rem .85rem}.form-control:focus,.form-select:focus{border-color:#111;box-shadow:0 0 0 .18rem rgba(17,17,17,.08)}.stat{border-radius:15px;background:#f7f7f7;padding:.9rem}.stat small{display:block;color:#777;font-size:.7rem;text-transform:uppercase;letter-spacing:.08em;font-weight:800}.stat strong{font-size:1.2rem}.belt-dot{width:9px;height:9px;border-radius:50%;display:inline-block;background:#222;margin-right:.45rem}.table thead th{font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;color:#666}.history-row td{padding-top:.9rem;padding-bottom:.9rem}
</style>

<div class="grading-wrap">
    <section class="grading-hero mb-4">
        <div class="kicker">Master Control • Progression</div>
        <div class="hero-title">Grading & Belt Management</div>
        <p class="hero-sub">Record belt promotions, preserve the student's progression history, and track grading across your dojo.</p>
    </section>

    <?php if ($error): ?>
        <div class="alert alert-danger border-0 shadow-sm mb-4"><i class="fas fa-circle-exclamation me-2"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-sm-4"><div class="stat"><small>Active students</small><strong><?= count($students) ?></strong></div></div>
        <div class="col-sm-4"><div class="stat"><small>Grading records</small><strong><?= count($history) ?></strong></div></div>
        <div class="col-sm-4"><div class="stat"><small>Belts represented</small><strong><?= count($b_count) ?></strong></div></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card panel">
                <div class="card-header"><div class="text-uppercase text-muted" style="font-size:.72rem;font-weight:800;letter-spacing:.08em;">Promotion Entry</div><h5 class="mb-0 mt-1">Add Grading Record</h5></div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                        <input type="hidden" name="add_grading" value="1">
                        <div class="mb-3"><label class="form-label">Student *</label><select name="student_id" class="form-select" required><option value="" disabled selected>Select student</option><?php foreach ($students as $s): ?><option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['first_name'].' '.$s['last_name']) ?> — <?= htmlspecialchars($s['current_belt']) ?></option><?php endforeach; ?></select></div>
                        <div class="mb-3"><label class="form-label">New Belt *</label><select name="new_belt" class="form-select" required><?php foreach ($allowed_belts as $belt): ?><option value="<?= htmlspecialchars($belt) ?>"><?= htmlspecialchars($belt) ?></option><?php endforeach; ?></select></div>
                        <div class="mb-3"><label class="form-label">Exam Date *</label><input type="date" name="exam_date" class="form-control" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required></div>
                        <div class="mb-3"><label class="form-label">Grade / Score</label><input type="text" name="grade" class="form-control" maxlength="20" placeholder="A, 95%, Pass"></div>
                        <div class="mb-4"><label class="form-label">Instructor Remarks</label><textarea name="remarks" class="form-control" rows="4" maxlength="1000" placeholder="Performance notes, areas of improvement, exam remarks."></textarea></div>
                        <button type="submit" class="btn btn-dark w-100"><i class="fas fa-medal me-2"></i>Save Grading Record</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card panel">
                <div class="card-header d-flex justify-content-between align-items-center gap-2"><div><div class="text-uppercase text-muted" style="font-size:.72rem;font-weight:800;letter-spacing:.08em;">History</div><h5 class="mb-0 mt-1">Recent Belt Progression</h5></div><a href="students.php" class="btn btn-sm btn-outline-dark">Students</a></div>
                <div class="card-body p-0">
                    <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Student</th><th>Date</th><th>Progression</th><th>Grade</th><th>Remarks</th></tr></thead><tbody>
                    <?php foreach ($history as $h): ?>
                        <tr class="history-row"><td class="fw-bold"><?= htmlspecialchars($h['first_name'].' '.$h['last_name']) ?></td><td><?= date('M j, Y', strtotime($h['exam_date'])) ?></td><td><?php if ($h['previous_belt']): ?><span class="text-muted"><?= htmlspecialchars($h['previous_belt']) ?></span> <i class="fas fa-arrow-right mx-1 text-danger"></i><?php endif; ?><span class="fw-bold"> <?= htmlspecialchars($h['new_belt']) ?></span></td><td><?= htmlspecialchars($h['grade'] ?: '-') ?></td><td class="text-muted small"><?= htmlspecialchars($h['remarks'] ?: '-') ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$history): ?><tr><td colspan="5" class="text-center py-5 text-muted">No grading records yet.</td></tr><?php endif; ?>
                    </tbody></table></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
