<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('student');

$page_title = 'Student Dashboard';
$user_id = (int)$_SESSION['user_id'];

// Current approved/pending dojo membership.
$membership_stmt = $pdo->prepare("
    SELECT d.id AS dojo_id, d.name AS dojo_name, d.location, d.training_days, d.training_timings, m.status, m.joined_at
    FROM dojo_memberships m
    JOIN dojos d ON d.id = m.dojo_id
    WHERE m.student_id = ?
    ORDER BY CASE WHEN m.status = 'approved' THEN 0 WHEN m.status = 'pending' THEN 1 ELSE 2 END, m.created_at DESC
    LIMIT 1
");
$membership_stmt->execute([$user_id]);
$membership = $membership_stmt->fetch();

// Student profile name.
$user_stmt = $pdo->prepare("SELECT first_name, last_name, email, profile_photo FROM users WHERE id = ? LIMIT 1");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch() ?: [];
$student_name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));

$belt_stmt = $pdo->prepare("SELECT new_belt, exam_date, grade FROM grading_history WHERE student_id = ? ORDER BY exam_date DESC, id DESC LIMIT 1");
$belt_stmt->execute([$user_id]);
$current_belt = $belt_stmt->fetch();

// Attendance percentage over all available records.
$attendance_stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_records,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) AS present_records,
        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) AS late_records
    FROM attendance_entries
    WHERE student_id = ?");
$attendance_stmt->execute([$user_id]);
$attendance = $attendance_stmt->fetch() ?: ['total_records' => 0, 'present_records' => 0, 'late_records' => 0];
$total_attendance = (int)$attendance['total_records'];
$present_attendance = (int)$attendance['present_records'];
$attendance_rate = $total_attendance > 0 ? round(($present_attendance / $total_attendance) * 100) : 0;

// Outstanding balance with recorded payments deducted.
$fee_stmt = $pdo->prepare("
    SELECT COALESCE(SUM(GREATEST(fr.amount_due - COALESCE(p.paid, 0), 0)), 0)
    FROM fee_records fr
    LEFT JOIN (
        SELECT fee_record_id, SUM(amount) AS paid
        FROM payments
        GROUP BY fee_record_id
    ) p ON p.fee_record_id = fr.id
    WHERE fr.student_id = ? AND fr.status IN ('pending', 'partially_paid', 'overdue')");
$fee_stmt->execute([$user_id]);
$outstanding_fees = (float)$fee_stmt->fetchColumn();

// Tournament registrations.
$tournament_stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM tournament_registrations
    WHERE student_id = ? AND status <> 'rejected'");
$tournament_stmt->execute([$user_id]);
$tournament_count = (int)$tournament_stmt->fetchColumn();

// Recent attendance.
$recent_att_stmt = $pdo->prepare("
    SELECT s.session_date, s.start_time, d.name AS dojo_name, e.status
    FROM attendance_entries e
    JOIN attendance_sessions s ON s.id = e.session_id
    JOIN dojos d ON d.id = s.dojo_id
    WHERE e.student_id = ?
    ORDER BY s.session_date DESC, s.id DESC
    LIMIT 6");
$recent_att_stmt->execute([$user_id]);
$recent_attendance = $recent_att_stmt->fetchAll();

// Announcements visible to the student's dojo.
$recent_announcements = [];
if ($membership && $membership['status'] === 'approved') {
    $ann_stmt = $pdo->prepare("
        SELECT title, content, level, publish_date
        FROM announcements
        WHERE status = 'active'
          AND publish_date <= CURDATE()
          AND (expiry_date IS NULL OR expiry_date >= CURDATE())
          AND (level = 'global' OR (level = 'dojo' AND dojo_id = ?))
        ORDER BY publish_date DESC, id DESC
        LIMIT 4");
    $ann_stmt->execute([(int)$membership['dojo_id']]);
    $recent_announcements = $ann_stmt->fetchAll();
}

// Recent grading history.
$grading_stmt = $pdo->prepare("
    SELECT previous_belt, new_belt, exam_date, grade
    FROM grading_history
    WHERE student_id = ?
    ORDER BY exam_date DESC, id DESC
    LIMIT 4");
$grading_stmt->execute([$user_id]);
$grading_history = $grading_stmt->fetchAll();

require_once '../includes/header.php';
?>

<style>
    .student-shell { margin-top: 1.5rem; }
    .student-hero {
        position: relative;
        overflow: hidden;
        border-radius: 26px;
        padding: 2rem;
        color: #fff;
        background: linear-gradient(135deg, #070707 0%, #181818 56%, #470909 100%);
        box-shadow: 0 24px 60px rgba(0,0,0,.16);
    }
    .student-hero::before {
        content: '';
        position: absolute;
        right: -90px;
        top: -120px;
        width: 300px;
        height: 300px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(220,20,60,.42), transparent 68%);
    }
    .student-kicker { color: #ffcf5c; font-size: .74rem; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; }
    .student-title { font-size: clamp(1.9rem, 4vw, 3.1rem); font-weight: 900; margin: .35rem 0 .5rem; }
    .student-copy { color: rgba(255,255,255,.72); margin: 0; max-width: 760px; }
    .student-avatar {
        width: 72px; height: 72px; border-radius: 22px;
        display: inline-flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg,#fff,#e8e8e8); color: #111;
        font-size: 1.6rem; font-weight: 900; flex: 0 0 auto;
    }
    .student-stat { border: 0; border-radius: 20px; background: #fff; box-shadow: 0 14px 38px rgba(17,24,39,.08); height: 100%; }
    .stat-icon { width: 46px; height: 46px; border-radius: 15px; display:inline-flex; align-items:center; justify-content:center; background:#111; color:#fff; }
    .stat-label { margin-top: .85rem; color:#8b8b8b; font-size:.72rem; text-transform:uppercase; letter-spacing:.08em; font-weight:800; }
    .stat-value { margin-top:.25rem; font-size:1.9rem; font-weight:900; line-height:1.1; }
    .student-panel { border:0; border-radius:20px; overflow:hidden; box-shadow:0 14px 38px rgba(17,24,39,.08); }
    .student-panel .card-header { background:#fff; border:0; padding:1.15rem 1.25rem; }
    .student-panel .card-body { background:#fff; }
    .panel-kicker { font-size:.72rem; text-transform:uppercase; letter-spacing:.08em; color:#8b8b8b; font-weight:800; }
    .dojo-card { border-radius:18px; background:linear-gradient(135deg,#111,#2c0a0a); color:#fff; padding:1.25rem; }
    .dojo-card small { color:rgba(255,255,255,.64); }
    .dojo-name { font-size:1.35rem; font-weight:900; }
    .action-card { display:block; text-decoration:none; color:#161616; background:#f7f7f7; border-radius:16px; padding:1rem; height:100%; transition:transform .2s, box-shadow .2s; }
    .action-card:hover { color:#161616; transform:translateY(-3px); box-shadow:0 10px 22px rgba(0,0,0,.08); }
    .action-card i { color:#b51212; font-size:1.15rem; }
    .status-badge { border-radius:999px; padding:.36rem .7rem; font-size:.72rem; font-weight:800; text-transform:uppercase; }
    .history-row { border-bottom:1px solid #eee; padding:.85rem 0; }
    .history-row:last-child { border-bottom:0; padding-bottom:0; }
    .announcement-item { border-bottom:1px solid #eee; padding:1rem 0; }
    .announcement-item:last-child { border-bottom:0; padding-bottom:0; }
    .progress-track { height:9px; background:#ededed; border-radius:999px; overflow:hidden; }
    .progress-fill { height:100%; border-radius:999px; background:linear-gradient(90deg,#111,#c51414); }
    @media (max-width:767.98px) {
        .student-shell { margin-top:1rem; }
        .student-hero { padding:1.35rem; border-radius:18px; }
    }
</style>

<div class="student-shell">
    <section class="student-hero mb-4">
        <div class="d-flex flex-wrap align-items-center gap-3 position-relative">
            <div class="student-avatar">
                <?php
                $initials = strtoupper(substr($user['first_name'] ?? 'S', 0, 1) . substr($user['last_name'] ?? '', 0, 1));
                echo htmlspecialchars($initials ?: 'S');
                ?>
            </div>
            <div>
                <div class="student-kicker">KOMS • Student Portal</div>
                <div class="student-title">Welcome, <?= htmlspecialchars($student_name ?: 'Student') ?></div>
                <p class="student-copy">Track your training, attendance, belt progress, tournaments and important dojo updates from one place.</p>
            </div>
        </div>
    </section>

    <?php if (!$membership): ?>
        <div class="card student-panel mb-4">
            <div class="card-body p-4 p-lg-5 text-center">
                <div class="mb-3"><i class="fas fa-house-chimney fa-3x"></i></div>
                <h3 class="fw-bold">Your KOMS journey starts here</h3>
                <p class="text-muted mx-auto" style="max-width:650px;">You are not connected to a dojo yet. Find an approved dojo and send a membership request to begin training and tracking your progress.</p>
                <a href="<?= APP_URL ?>/find_dojo.php" class="btn btn-dark px-4"><i class="fas fa-search me-2"></i>Find a Dojo</a>
            </div>
        </div>
    <?php elseif ($membership['status'] === 'pending'): ?>
        <div class="card student-panel mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
                    <div>
                        <div class="panel-kicker">Membership Status</div>
                        <h3 class="fw-bold mb-2">Waiting for dojo approval</h3>
                        <p class="text-muted mb-2">Your request to join <strong><?= htmlspecialchars($membership['dojo_name']) ?></strong> is currently pending with the Dojo Master.</p>
                        <span class="status-badge bg-warning text-dark">Pending</span>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif ($membership['status'] === 'approved'): ?>
        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-3">
                <div class="card student-stat"><div class="card-body p-3 p-lg-4"><span class="stat-icon"><i class="fas fa-calendar-check"></i></span><div class="stat-label">Attendance</div><div class="stat-value"><?= $attendance_rate ?>%</div><div class="small text-muted mt-1"><?= $present_attendance ?> of <?= $total_attendance ?> marked present</div></div></div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="card student-stat"><div class="card-body p-3 p-lg-4"><span class="stat-icon"><i class="fas fa-medal"></i></span><div class="stat-label">Current Belt</div><div class="stat-value" style="font-size:1.35rem;"><?= htmlspecialchars($current_belt['new_belt'] ?? 'White Belt') ?></div><div class="small text-muted mt-1"><?= $current_belt ? date('M j, Y', strtotime($current_belt['exam_date'])) : 'No grading record yet' ?></div></div></div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="card student-stat"><div class="card-body p-3 p-lg-4"><span class="stat-icon"><i class="fas fa-wallet"></i></span><div class="stat-label">Outstanding Fees</div><div class="stat-value">₹<?= number_format($outstanding_fees, 2) ?></div><div class="small text-muted mt-1">Current unpaid balance</div></div></div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="card student-stat"><div class="card-body p-3 p-lg-4"><span class="stat-icon"><i class="fas fa-trophy"></i></span><div class="stat-label">Tournament Entries</div><div class="stat-value"><?= number_format($tournament_count) ?></div><div class="small text-muted mt-1">Active registrations</div></div></div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-xl-8">
                <div class="card student-panel mb-4">
                    <div class="card-header"><div class="panel-kicker">My Training Base</div><h5 class="mb-0 mt-1">Dojo Overview</h5></div>
                    <div class="card-body">
                        <div class="dojo-card mb-3">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <div>
                                    <div class="panel-kicker" style="color:#ffcf5c;">Approved Membership</div>
                                    <div class="dojo-name mt-1"><?= htmlspecialchars($membership['dojo_name']) ?></div>
                                    <small><i class="fas fa-location-dot me-1"></i><?= htmlspecialchars($membership['location']) ?></small>
                                </div>
                                <span class="status-badge bg-light text-dark">Active</span>
                            </div>
                            <div class="row g-3 mt-2">
                                <div class="col-md-6"><small><i class="fas fa-calendar-days me-1"></i>Training Days</small><div class="fw-semibold mt-1"><?= htmlspecialchars($membership['training_days'] ?: 'See dojo schedule') ?></div></div>
                                <div class="col-md-6"><small><i class="fas fa-clock me-1"></i>Training Time</small><div class="fw-semibold mt-1"><?= htmlspecialchars($membership['training_timings'] ?: 'See dojo schedule') ?></div></div>
                            </div>
                        </div>
                        <a href="my_dojo.php" class="btn btn-outline-dark"><i class="fas fa-arrow-right me-2"></i>View Full Dojo Details</a>
                    </div>
                </div>

                <div class="card student-panel mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center"><div><div class="panel-kicker">Recent Training Record</div><h5 class="mb-0 mt-1">Attendance History</h5></div><a href="attendance.php" class="btn btn-sm btn-outline-dark">View All</a></div>
                    <div class="card-body p-0">
                        <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                            <thead><tr><th class="ps-4">Date</th><th>Dojo</th><th>Time</th><th class="pe-4">Status</th></tr></thead>
                            <tbody>
                            <?php if (!$recent_attendance): ?>
                                <tr><td colspan="4" class="text-center py-5 text-muted">No attendance records yet.</td></tr>
                            <?php else: foreach ($recent_attendance as $row):
                                $badge_class = $row['status'] === 'present' ? 'bg-success text-white' : ($row['status'] === 'absent' ? 'bg-danger text-white' : 'bg-warning text-dark');
                            ?>
                                <tr>
                                    <td class="ps-4 fw-semibold"><?= date('M j, Y', strtotime($row['session_date'])) ?></td>
                                    <td><?= htmlspecialchars($row['dojo_name']) ?></td>
                                    <td><?= $row['start_time'] ? date('g:i A', strtotime($row['start_time'])) : 'Scheduled' ?></td>
                                    <td class="pe-4"><span class="status-badge <?= $badge_class ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table></div>
                    </div>
                </div>

                <div class="card student-panel">
                    <div class="card-header"><div class="panel-kicker">Martial Arts Progress</div><h5 class="mb-0 mt-1">Grading History</h5></div>
                    <div class="card-body">
                        <?php if (!$grading_history): ?>
                            <div class="text-center text-muted py-4">No grading history has been recorded yet.</div>
                        <?php else: foreach ($grading_history as $grade): ?>
                            <div class="history-row d-flex justify-content-between align-items-center gap-3">
                                <div><div class="fw-bold"><?= htmlspecialchars($grade['new_belt']) ?></div><div class="small text-muted"><?= date('M j, Y', strtotime($grade['exam_date'])) ?><?= $grade['grade'] ? ' • Grade ' . htmlspecialchars($grade['grade']) : '' ?></div></div>
                                <span class="belt-badge status-badge bg-dark text-white"><?= htmlspecialchars($grade['previous_belt'] ?: 'Start') ?> → <?= htmlspecialchars($grade['new_belt']) ?></span>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card student-panel mb-4">
                    <div class="card-header"><div class="panel-kicker">My KOMS</div><h5 class="mb-0 mt-1">Quick Actions</h5></div>
                    <div class="card-body"><div class="row g-2">
                        <div class="col-6"><a class="action-card" href="attendance.php"><i class="fas fa-calendar-check"></i><div class="fw-bold mt-2">Attendance</div><small class="text-muted">Training record</small></a></div>
                        <div class="col-6"><a class="action-card" href="fees.php"><i class="fas fa-wallet"></i><div class="fw-bold mt-2">Fees</div><small class="text-muted">Payments & dues</small></a></div>
                        <div class="col-6"><a class="action-card" href="grading.php"><i class="fas fa-medal"></i><div class="fw-bold mt-2">Grading</div><small class="text-muted">Belt progress</small></a></div>
                        <div class="col-6"><a class="action-card" href="tournaments.php"><i class="fas fa-trophy"></i><div class="fw-bold mt-2">Tournaments</div><small class="text-muted">Events & entry</small></a></div>
                        <div class="col-12"><a class="action-card" href="announcements.php"><i class="fas fa-bullhorn"></i><div class="fw-bold mt-2">Announcements</div><small class="text-muted">Latest dojo and KOMS notices</small></a></div>
                    </div></div>
                </div>

                <div class="card student-panel mb-4">
                    <div class="card-header"><div class="panel-kicker">Performance</div><h5 class="mb-0 mt-1">Attendance Rate</h5></div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2"><span class="text-muted">Present</span><strong><?= $attendance_rate ?>%</strong></div>
                        <div class="progress-track"><div class="progress-fill" style="width:<?= $attendance_rate ?>%"></div></div>
                        <div class="small text-muted mt-2">Late sessions: <?= (int)$attendance['late_records'] ?></div>
                    </div>
                </div>

                <div class="card student-panel">
                    <div class="card-header"><div class="panel-kicker">Communication</div><h5 class="mb-0 mt-1">Latest Announcements</h5></div>
                    <div class="card-body">
                        <?php if (!$recent_announcements): ?>
                            <div class="text-muted py-2">No active announcements right now.</div>
                        <?php else: foreach ($recent_announcements as $announcement): ?>
                            <div class="announcement-item">
                                <div class="d-flex justify-content-between align-items-start gap-2"><div class="fw-bold"><?= htmlspecialchars($announcement['title']) ?></div><small class="text-muted"><?= date('M j', strtotime($announcement['publish_date'])) ?></small></div>
                                <div class="small text-muted mt-2"><?= htmlspecialchars(mb_strimwidth(strip_tags($announcement['content']), 0, 125, '…')) ?></div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
