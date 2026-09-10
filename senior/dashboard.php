<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('senior');

$page_title = 'Senior Dashboard';

// Dashboard metrics.
$total_students = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student' AND status = 'active'")->fetchColumn();
$total_dojos = (int)$pdo->query("SELECT COUNT(*) FROM dojos WHERE status = 'approved'")->fetchColumn();
$today_sessions = (int)$pdo->query("SELECT COUNT(*) FROM attendance_sessions WHERE session_date = CURDATE()")->fetchColumn();
$today_present = (int)$pdo->query("SELECT COUNT(*) FROM attendance_entries e JOIN attendance_sessions s ON s.id = e.session_id WHERE s.session_date = CURDATE() AND e.status = 'present'")->fetchColumn();

$open_sessions_stmt = $pdo->query("
    SELECT s.id, s.session_date, s.start_time, s.end_time, s.scheduled_day,
           d.name AS dojo_name,
           COUNT(e.id) AS marked_count
    FROM attendance_sessions s
    JOIN dojos d ON d.id = s.dojo_id
    LEFT JOIN attendance_entries e ON e.session_id = s.id
    WHERE s.is_locked = 0
      AND s.session_date >= CURDATE() - INTERVAL 2 DAY
    GROUP BY s.id, s.session_date, s.start_time, s.end_time, s.scheduled_day, d.name
    ORDER BY s.session_date DESC, s.start_time DESC
    LIMIT 8
");
$open_sessions = $open_sessions_stmt->fetchAll();

$students_stmt = $pdo->query("
    SELECT u.id, u.first_name, u.last_name, u.email,
           d.name AS dojo_name,
           COALESCE((
               SELECT gh.new_belt
               FROM grading_history gh
               WHERE gh.student_id = u.id
               ORDER BY gh.exam_date DESC, gh.id DESC
               LIMIT 1
           ), 'White') AS current_belt
    FROM users u
    JOIN dojo_memberships m ON m.student_id = u.id AND m.status = 'approved'
    JOIN dojos d ON d.id = m.dojo_id
    WHERE u.role = 'student' AND u.status = 'active'
    ORDER BY u.first_name ASC, u.last_name ASC
    LIMIT 12
");
$students = $students_stmt->fetchAll();

$announcements_stmt = $pdo->query("
    SELECT title, content, publish_date
    FROM announcements
    WHERE status = 'active'
      AND publish_date <= CURDATE()
      AND (expiry_date IS NULL OR expiry_date >= CURDATE())
    ORDER BY publish_date DESC, id DESC
    LIMIT 4
");
$announcements = $announcements_stmt->fetchAll();

require_once '../includes/header.php';
?>

<style>
    .senior-shell { margin-top: 1.5rem; }
    .senior-hero {
        position: relative;
        overflow: hidden;
        border-radius: 24px;
        padding: 2rem;
        background: linear-gradient(135deg, #090909 0%, #171717 58%, #3b0b0b 100%);
        color: #fff;
        box-shadow: 0 24px 60px rgba(0,0,0,.16);
    }
    .senior-hero::after {
        content: '';
        position: absolute;
        width: 260px;
        height: 260px;
        right: -100px;
        top: -110px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(220,20,60,.45), transparent 68%);
    }
    .senior-kicker { color: #ffcf5c; letter-spacing: .14em; text-transform: uppercase; font-size: .75rem; font-weight: 800; }
    .senior-title { font-size: clamp(1.8rem, 4vw, 3rem); font-weight: 900; margin: .35rem 0 .65rem; }
    .senior-subtitle { color: rgba(255,255,255,.72); max-width: 720px; margin-bottom: 0; }
    .metric-card {
        border: 0;
        border-radius: 20px;
        background: #fff;
        box-shadow: 0 15px 38px rgba(17,24,39,.08);
        height: 100%;
    }
    .metric-icon {
        width: 48px; height: 48px; border-radius: 15px;
        display: inline-flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, #111, #7d1010); color: #fff;
    }
    .metric-number { font-size: 2rem; font-weight: 900; line-height: 1; margin-top: .8rem; }
    .dashboard-panel { border: 0; border-radius: 20px; box-shadow: 0 15px 38px rgba(17,24,39,.08); overflow: hidden; }
    .dashboard-panel .card-header { border: 0; background: #fff; padding: 1.1rem 1.25rem; }
    .dashboard-panel .card-body { background: #fff; }
    .soft-label { font-size: .74rem; text-transform: uppercase; letter-spacing: .08em; color: #8a8a8a; font-weight: 800; }
    .belt-badge { background: #111; color: #fff; border-radius: 999px; padding: .38rem .7rem; font-size: .75rem; font-weight: 800; }
    .quick-action { border-radius: 16px; padding: 1rem; background: #f7f7f7; text-decoration: none; color: #151515; transition: transform .2s, box-shadow .2s; display: block; }
    .quick-action:hover { transform: translateY(-3px); box-shadow: 0 10px 22px rgba(0,0,0,.08); color: #151515; }
    .quick-action i { color: #b51212; font-size: 1.2rem; }
    .announcement-item { border-bottom: 1px solid #eee; padding: 1rem 0; }
    .announcement-item:last-child { border-bottom: 0; padding-bottom: 0; }
    .table thead th { font-size: .74rem; text-transform: uppercase; letter-spacing: .06em; color: #858585; border-bottom-width: 1px; }
    @media (max-width: 767.98px) {
        .senior-shell { margin-top: 1rem; }
        .senior-hero { padding: 1.35rem; border-radius: 18px; }
        .dashboard-panel { border-radius: 16px; }
    }
</style>

<div class="senior-shell">
    <section class="senior-hero mb-4">
        <div class="senior-kicker">KOMS • Senior / Sub-Admin</div>
        <div class="senior-title">Training Command Center</div>
        <p class="senior-subtitle">Monitor students, support daily attendance, follow dojo activity, and keep training operations moving without accessing financial administration.</p>
    </section>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="card metric-card">
                <div class="card-body p-3 p-lg-4">
                    <span class="metric-icon"><i class="fas fa-user-graduate"></i></span>
                    <div class="soft-label mt-3">Active Students</div>
                    <div class="metric-number"><?= number_format($total_students) ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card metric-card">
                <div class="card-body p-3 p-lg-4">
                    <span class="metric-icon"><i class="fas fa-house-chimney"></i></span>
                    <div class="soft-label mt-3">Approved Dojos</div>
                    <div class="metric-number"><?= number_format($total_dojos) ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card metric-card">
                <div class="card-body p-3 p-lg-4">
                    <span class="metric-icon"><i class="fas fa-calendar-check"></i></span>
                    <div class="soft-label mt-3">Today's Sessions</div>
                    <div class="metric-number"><?= number_format($today_sessions) ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card metric-card">
                <div class="card-body p-3 p-lg-4">
                    <span class="metric-icon"><i class="fas fa-user-check"></i></span>
                    <div class="soft-label mt-3">Present Today</div>
                    <div class="metric-number"><?= number_format($today_present) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card dashboard-panel mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <div class="soft-label">Attendance Operations</div>
                        <h5 class="mb-0 mt-1">Open Training Sessions</h5>
                    </div>
                    <a href="<?= APP_URL ?>/master/attendance.php" class="btn btn-sm btn-dark">Attendance</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Date</th>
                                    <th>Dojo</th>
                                    <th>Time</th>
                                    <th>Marked</th>
                                    <th class="pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (!$open_sessions): ?>
                                <tr><td colspan="5" class="text-center py-5 text-muted">No open sessions are waiting for attendance.</td></tr>
                            <?php else: ?>
                                <?php foreach ($open_sessions as $session): ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold"><?= date('M j, Y', strtotime($session['session_date'])) ?></td>
                                        <td><?= htmlspecialchars($session['dojo_name']) ?></td>
                                        <td><?= $session['start_time'] ? date('g:i A', strtotime($session['start_time'])) : 'Scheduled' ?></td>
                                        <td><span class="badge text-bg-light"><?= (int)$session['marked_count'] ?></span></td>
                                        <td class="pe-4"><a class="btn btn-sm btn-outline-danger" href="<?= APP_URL ?>/master/mark_attendance.php?session_id=<?= (int)$session['id'] ?>">Open</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card dashboard-panel">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <div class="soft-label">Student Monitoring</div>
                        <h5 class="mb-0 mt-1">Active Student Roster</h5>
                    </div>
                    <a href="<?= APP_URL ?>/master/students.php" class="btn btn-sm btn-outline-dark">View Students</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Student</th>
                                    <th>Dojo</th>
                                    <th>Belt</th>
                                    <th class="pe-4">Email</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (!$students): ?>
                                <tr><td colspan="4" class="text-center py-5 text-muted">No active students found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></td>
                                        <td><?= htmlspecialchars($student['dojo_name']) ?></td>
                                        <td><span class="belt-badge"><?= htmlspecialchars($student['current_belt']) ?></span></td>
                                        <td class="pe-4 text-muted"><?= htmlspecialchars($student['email']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card dashboard-panel mb-4">
                <div class="card-header">
                    <div class="soft-label">Shortcuts</div>
                    <h5 class="mb-0 mt-1">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6"><a class="quick-action" href="<?= APP_URL ?>/master/attendance.php"><i class="fas fa-calendar-check"></i><div class="fw-bold mt-2">Attendance</div><small class="text-muted">Mark sessions</small></a></div>
                        <div class="col-6"><a class="quick-action" href="<?= APP_URL ?>/master/students.php"><i class="fas fa-users"></i><div class="fw-bold mt-2">Students</div><small class="text-muted">View roster</small></a></div>
                        <div class="col-6"><a class="quick-action" href="<?= APP_URL ?>/master/grading.php"><i class="fas fa-medal"></i><div class="fw-bold mt-2">Grading</div><small class="text-muted">Belt progress</small></a></div>
                        <div class="col-6"><a class="quick-action" href="<?= APP_URL ?>/master/dojo.php"><i class="fas fa-house"></i><div class="fw-bold mt-2">Dojo</div><small class="text-muted">Training info</small></a></div>
                    </div>
                </div>
            </div>

            <div class="card dashboard-panel">
                <div class="card-header">
                    <div class="soft-label">Communication</div>
                    <h5 class="mb-0 mt-1">Latest Announcements</h5>
                </div>
                <div class="card-body">
                    <?php if (!$announcements): ?>
                        <div class="text-muted py-2">No active announcements right now.</div>
                    <?php else: ?>
                        <?php foreach ($announcements as $announcement): ?>
                            <div class="announcement-item">
                                <div class="fw-bold mb-1"><?= htmlspecialchars($announcement['title']) ?></div>
                                <div class="small text-muted mb-2"><?= date('M j, Y', strtotime($announcement['publish_date'])) ?></div>
                                <div class="small text-muted"><?= htmlspecialchars(mb_strimwidth(strip_tags($announcement['content']), 0, 120, '…')) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
