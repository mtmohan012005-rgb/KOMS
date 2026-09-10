<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('master');

$master_id = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT id, name, training_days, training_timings FROM dojos WHERE master_id = ? AND status = 'approved' LIMIT 1");
$stmt->execute([$master_id]);
$dojo = $stmt->fetch();

if (!$dojo) {
    $_SESSION['error_msg'] = 'You need an approved dojo to manage attendance.';
    redirect('/master/dashboard.php');
}

$dojo_id = (int)$dojo['id'];
$today = date('Y-m-d');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_session'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        $date = sanitize_input($_POST['session_date'] ?? '');
        $start = sanitize_input($_POST['start_time'] ?? '');
        $end = sanitize_input($_POST['end_time'] ?? '');

        $validDate = DateTime::createFromFormat('Y-m-d', $date);
        if (!$validDate || $validDate->format('Y-m-d') !== $date) {
            $error = 'Please select a valid training date.';
        } elseif ($date > $today) {
            $error = 'Attendance sessions cannot be created for a future date.';
        } elseif ($start !== '' && $end !== '' && $end <= $start) {
            $error = 'End time must be later than start time.';
        } else {
            $day = date('D', strtotime($date));
            $check = $pdo->prepare("SELECT id FROM attendance_sessions WHERE dojo_id = ? AND session_date = ? LIMIT 1");
            $check->execute([$dojo_id, $date]);

            if ($check->fetch()) {
                $error = 'A training session already exists for this date.';
            } else {
                $insert = $pdo->prepare("INSERT INTO attendance_sessions (dojo_id, instructor_id, session_date, scheduled_day, start_time, end_time, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $insert->execute([$dojo_id, $master_id, $date, $day, $start !== '' ? $start : null, $end !== '' ? $end : null, $master_id]);

                $session_id = (int)$pdo->lastInsertId();
                log_audit_action($pdo, $master_id, 'CREATE', 'attendance_sessions', $session_id, 'Created attendance session');
                $_SESSION['success_msg'] = 'Attendance session created successfully.';
                redirect('/master/mark_attendance.php?session_id=' . $session_id);
            }
        }
    }
}

$stmt = $pdo->prepare("
    SELECT s.*,
           (SELECT COUNT(*) FROM attendance_entries ae WHERE ae.session_id = s.id) AS marked_count,
           (SELECT COUNT(*) FROM attendance_entries ae WHERE ae.session_id = s.id AND ae.status = 'present') AS present_count
    FROM attendance_sessions s
    WHERE s.dojo_id = ?
    ORDER BY s.session_date DESC, s.start_time DESC
    LIMIT 30
");
$stmt->execute([$dojo_id]);
$sessions = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM dojo_memberships WHERE dojo_id = ? AND status = 'approved'");
$stmt->execute([$dojo_id]);
$active_students = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance_sessions WHERE dojo_id = ? AND session_date = ?");
$stmt->execute([$dojo_id, $today]);
$today_sessions = (int)$stmt->fetchColumn();

$page_title = 'Attendance Management';
require_once '../includes/header.php';
?>

<style>
    .att-wrap { max-width: 1180px; margin: 1.5rem auto; }
    .att-hero { padding: 1.8rem 2rem; border-radius: 24px; background: linear-gradient(135deg,#090909,#1c1c1c 58%,#4d0808); color:#fff; box-shadow:0 22px 50px rgba(0,0,0,.15); }
    .att-kicker { color:#ffcf5c; text-transform:uppercase; letter-spacing:.14em; font-size:.72rem; font-weight:800; }
    .att-title { font-size:clamp(1.8rem,4vw,2.7rem); font-weight:900; margin:.35rem 0 .45rem; }
    .metric-card { background:#fff; border:0; border-radius:18px; padding:1.15rem; box-shadow:0 12px 30px rgba(17,24,39,.08); height:100%; }
    .metric-icon { width:42px;height:42px;border-radius:13px;display:grid;place-items:center;background:#111;color:#ffcf5c; }
    .metric-value { font-size:1.55rem; font-weight:900; line-height:1; margin-top:.8rem; }
    .panel { border:0; border-radius:20px; overflow:hidden; box-shadow:0 15px 38px rgba(17,24,39,.08); }
    .panel .card-header { background:#fff; border:0; padding:1.15rem 1.35rem; }
    .panel .card-body { background:#fff; padding:1.25rem; }
    .form-control { border-radius:12px; padding:.7rem .82rem; border-color:#ddd; }
    .form-control:focus { border-color:#111; box-shadow:0 0 0 .18rem rgba(17,17,17,.08); }
    .status-pill { border-radius:999px; padding:.4rem .7rem; font-size:.72rem; font-weight:800; }
    .table thead th { font-size:.72rem; text-transform:uppercase; letter-spacing:.06em; color:#777; white-space:nowrap; }
    .empty-state { padding:3rem 1rem; text-align:center; color:#888; }
</style>

<div class="att-wrap">
    <section class="att-hero mb-4">
        <div class="att-kicker">Master Control • Attendance</div>
        <div class="att-title">Training Attendance</div>
        <p class="mb-0" style="color:rgba(255,255,255,.72);">Create training sessions, record attendance, and review your dojo's attendance activity.</p>
    </section>

    <?php if ($error): ?>
        <div class="alert alert-danger border-0 shadow-sm mb-4"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="metric-card"><div class="metric-icon"><i class="fas fa-users"></i></div><div class="text-muted small mt-2">Active Students</div><div class="metric-value"><?= $active_students ?></div></div></div>
        <div class="col-md-4"><div class="metric-card"><div class="metric-icon"><i class="fas fa-calendar-day"></i></div><div class="text-muted small mt-2">Today's Sessions</div><div class="metric-value"><?= $today_sessions ?></div></div></div>
        <div class="col-md-4"><div class="metric-card"><div class="metric-icon"><i class="fas fa-dojo"></i></div><div class="text-muted small mt-2">Dojo</div><div class="metric-value" style="font-size:1.1rem;line-height:1.2;"><?= htmlspecialchars($dojo['name']) ?></div></div></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card panel">
                <div class="card-header">
                    <div class="text-uppercase text-muted" style="font-size:.72rem;font-weight:800;letter-spacing:.08em;">New Session</div>
                    <h5 class="mb-0 mt-1">Create Training Session</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                        <input type="hidden" name="create_session" value="1">

                        <div class="mb-3">
                            <label class="form-label fw-bold">Date</label>
                            <input type="date" name="session_date" class="form-control" value="<?= $today ?>" max="<?= $today ?>" required>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3"><label class="form-label fw-bold">Start</label><input type="time" name="start_time" class="form-control"></div>
                            <div class="col-6 mb-3"><label class="form-label fw-bold">End</label><input type="time" name="end_time" class="form-control"></div>
                        </div>
                        <?php if (!empty($dojo['training_days']) || !empty($dojo['training_timings'])): ?>
                            <div class="small text-muted mb-3">
                                <strong>Configured schedule:</strong>
                                <?= htmlspecialchars($dojo['training_days'] ?: 'Days not set') ?>
                                <?php if (!empty($dojo['training_timings'])): ?> · <?= htmlspecialchars($dojo['training_timings']) ?><?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-dark w-100"><i class="fas fa-plus-circle me-2"></i>Create & Mark Attendance</button>
                    </form>
                </div>
            </div>

            <div class="card panel mt-4">
                <div class="card-body">
                    <div class="fw-bold mb-2">Quick Actions</div>
                    <div class="d-grid gap-2">
                        <a href="students.php" class="btn btn-outline-dark text-start"><i class="fas fa-users me-2"></i>Student Roster</a>
                        <a href="grading.php" class="btn btn-outline-dark text-start"><i class="fas fa-medal me-2"></i>Grading & Belts</a>
                        <a href="dashboard.php" class="btn btn-outline-dark text-start"><i class="fas fa-arrow-left me-2"></i>Back to Dashboard</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card panel">
                <div class="card-header d-flex justify-content-between align-items-center gap-3">
                    <div><div class="text-uppercase text-muted" style="font-size:.72rem;font-weight:800;letter-spacing:.08em;">History</div><h5 class="mb-0 mt-1">Recent Training Sessions</h5></div>
                    <span class="badge bg-light text-dark border"><?= count($sessions) ?> shown</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr><th class="ps-3">Date</th><th>Time</th><th>Attendance</th><th>Status</th><th class="pe-3">Action</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sessions as $s): ?>
                                    <tr>
                                        <td class="ps-3"><div class="fw-bold"><?= date('M j, Y', strtotime($s['session_date'])) ?></div><small class="text-muted"><?= htmlspecialchars($s['scheduled_day']) ?></small></td>
                                        <td><?= $s['start_time'] ? date('g:i A', strtotime($s['start_time'])) : '--' ?><?= $s['end_time'] ? ' – ' . date('g:i A', strtotime($s['end_time'])) : '' ?></td>
                                        <td><strong><?= (int)$s['present_count'] ?></strong><span class="text-muted"> / <?= (int)$s['marked_count'] ?></span></td>
                                        <td><?= $s['is_locked'] ? '<span class="status-pill bg-secondary text-white"><i class="fas fa-lock me-1"></i>Locked</span>' : '<span class="status-pill bg-success text-white">Open</span>' ?></td>
                                        <td class="pe-3"><a href="mark_attendance.php?session_id=<?= (int)$s['id'] ?>" class="btn btn-sm <?= $s['is_locked'] ? 'btn-outline-secondary' : 'btn-dark' ?>"><?= $s['is_locked'] ? 'View' : 'Mark' ?></a></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($sessions)): ?>
                                    <tr><td colspan="5"><div class="empty-state"><i class="fas fa-calendar-plus fa-2x mb-3"></i><div class="fw-bold">No attendance sessions yet</div><div class="small">Create your first training session to start marking attendance.</div></div></td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
