<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('master');

$master_id = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT id, name, status FROM dojos WHERE master_id = ? LIMIT 1");
$stmt->execute([$master_id]);
$dojo = $stmt->fetch();

if (!$dojo) {
    $_SESSION['info_msg'] = 'Register your dojo before managing students.';
    redirect('/master/register_dojo.php');
}

$dojo_id = (int)$dojo['id'];

$stmt = $pdo->prepare("SELECT u.id, u.member_id, u.first_name, u.last_name, u.email, u.phone, u.profile_photo, u.created_at, m.joined_at, (SELECT gh.new_belt FROM grading_history gh WHERE gh.student_id = u.id ORDER BY gh.exam_date DESC, gh.id DESC LIMIT 1) AS current_belt, (SELECT COUNT(*) FROM attendance_entries ae JOIN attendance_sessions ats ON ae.session_id = ats.id WHERE ae.student_id = u.id AND ats.dojo_id = ? AND ae.status = 'present') AS present_count, (SELECT COUNT(*) FROM attendance_entries ae JOIN attendance_sessions ats ON ae.session_id = ats.id WHERE ae.student_id = u.id AND ats.dojo_id = ?) AS attendance_count FROM dojo_memberships m JOIN users u ON m.student_id = u.id WHERE m.dojo_id = ? AND m.status = 'approved' ORDER BY u.first_name, u.last_name");
$stmt->execute([$dojo_id, $dojo_id, $dojo_id]);
$students = $stmt->fetchAll();

$student_count = count($students);
$excellent_count = 0;
foreach ($students as &$student) {
    $student['attendance_percent'] = $student['attendance_count'] > 0 ? round(($student['present_count'] / $student['attendance_count']) * 100) : 0;
    if ($student['attendance_percent'] >= 90) $excellent_count++;
}
unset($student);

$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $needle = strtolower($search);
    $students = array_values(array_filter($students, function ($student) use ($needle) {
        $name = strtolower($student['first_name'] . ' ' . $student['last_name']);
        $email = strtolower((string)$student['email']);
        $phone = strtolower((string)$student['phone']);
        $member_id = strtolower((string)$student['member_id']);
        return str_contains($name, $needle) || str_contains($email, $needle) || str_contains($phone, $needle) || str_contains($member_id, $needle);
    }));
}

$page_title = 'Student Management';
require_once '../includes/header.php';
?>

<style>
.students-wrap{max-width:1180px;margin:1.5rem auto 2.5rem}.students-hero{border-radius:24px;padding:1.8rem 2rem;background:linear-gradient(135deg,#080808,#1b1b1b 58%,#5b0909);color:#fff;box-shadow:0 22px 50px rgba(0,0,0,.16)}
.kicker{text-transform:uppercase;letter-spacing:.14em;font-size:.7rem;font-weight:800;color:#ffd15c}.hero-title{font-size:clamp(1.8rem,4vw,2.8rem);font-weight:900;margin:.35rem 0}.hero-sub{color:rgba(255,255,255,.7);max-width:760px}
.stat-card,.students-panel{border:0;border-radius:20px;box-shadow:0 15px 38px rgba(17,24,39,.08);background:#fff}.stat-card{padding:1.1rem 1.2rem;height:100%}.stat-label{font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;color:#888;font-weight:800}.stat-number{font-size:1.8rem;font-weight:900;margin-top:.2rem}.stat-icon{width:42px;height:42px;border-radius:13px;display:grid;place-items:center;background:#f3f3f3;color:#111}
.panel-head{padding:1.1rem 1.25rem;border-bottom:1px solid #eee}.search-box{border-radius:12px;padding:.72rem .9rem;border:1px solid #ddd}.student-avatar{width:42px;height:42px;border-radius:50%;object-fit:cover;background:#eee;display:grid;place-items:center;font-weight:800;color:#666}.belt{display:inline-flex;border-radius:999px;padding:.35rem .65rem;background:#f2f2f2;font-size:.75rem;font-weight:800}.member-id{display:inline-flex;border-radius:999px;padding:.27rem .55rem;background:#fff4cf;color:#705200;font-size:.68rem;font-weight:800;white-space:nowrap}.att-good{color:#137333;font-weight:800}.att-mid{color:#9a6700;font-weight:800}.att-low{color:#b42318;font-weight:800}.table> :not(caption)>*>*{padding:.9rem .7rem}.empty{padding:3rem 1rem;text-align:center;color:#777}.action-link{display:inline-flex;align-items:center;gap:5px;text-decoration:none}
@media(max-width:767px){.students-hero{padding:1.4rem}.panel-head{padding:1rem}.table{min-width:980px}}
</style>

<div class="students-wrap">
    <section class="students-hero mb-4">
        <div class="kicker">Mass Dragon Dojo • Student Management</div>
        <div class="hero-title">Your Student Roster</div>
        <div class="hero-sub">Manage approved students from the Master Portal. Search by name, email, phone or KOMS User ID and open attendance or grading for each student.</div>
        <div class="d-flex flex-wrap gap-2 mt-3">
            <a href="index.php" class="btn btn-light"><i class="fas fa-arrow-left me-2"></i>Master Dashboard</a>
            <a href="password_requests.php" class="btn btn-warning"><i class="fas fa-key me-2"></i>Password Requests</a>
            <a href="requests.php" class="btn btn-outline-light"><i class="fas fa-user-plus me-2"></i>Join Requests</a>
            <a href="attendance.php" class="btn btn-outline-light"><i class="fas fa-calendar-check me-2"></i>Attendance</a>
            <a href="grading.php" class="btn btn-outline-light"><i class="fas fa-medal me-2"></i>Grading</a>
        </div>
    </section>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="stat-card"><div class="d-flex justify-content-between"><div><div class="stat-label">Active students</div><div class="stat-number"><?= $student_count ?></div></div><div class="stat-icon"><i class="fas fa-users"></i></div></div></div></div>
        <div class="col-md-4"><div class="stat-card"><div class="d-flex justify-content-between"><div><div class="stat-label">90%+ attendance</div><div class="stat-number"><?= $excellent_count ?></div></div><div class="stat-icon"><i class="fas fa-chart-line"></i></div></div></div></div>
        <div class="col-md-4"><div class="stat-card"><div class="d-flex justify-content-between"><div><div class="stat-label">Dojo status</div><div class="stat-number" style="font-size:1.35rem;"><?= htmlspecialchars(ucfirst($dojo['status'])) ?></div></div><div class="stat-icon"><i class="fas fa-shield-halved"></i></div></div></div></div>
    </div>

    <div class="students-panel">
        <div class="panel-head d-flex flex-wrap gap-3 justify-content-between align-items-center">
            <div><h4 class="mb-1">Student Directory</h4><div class="text-muted small">Approved members of <?= htmlspecialchars($dojo['name']) ?></div></div>
            <form method="GET" class="d-flex gap-2" style="min-width:min(100%,430px);">
                <input class="search-box flex-grow-1" type="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search name, email, phone or KOMS User ID">
                <button class="btn btn-dark" type="submit"><i class="fas fa-search"></i></button>
                <?php if ($search !== ''): ?><a class="btn btn-outline-secondary" href="students.php" title="Clear search"><i class="fas fa-xmark"></i></a><?php endif; ?>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead style="background:#fafafa;">
                    <tr><th>Student</th><th>Contact</th><th>KOMS User ID</th><th>Belt</th><th>Attendance</th><th>Joined</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($students as $student):
                    $full_name = trim($student['first_name'] . ' ' . $student['last_name']);
                    $initials = strtoupper(substr($student['first_name'],0,1) . substr($student['last_name'],0,1));
                    $att = (int)$student['attendance_percent'];
                    $att_class = $att >= 90 ? 'att-good' : ($att >= 75 ? 'att-mid' : 'att-low');
                ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <?php if (!empty($student['profile_photo'])): ?><img class="student-avatar" src="<?= htmlspecialchars($student['profile_photo']) ?>" alt="">
                                <?php else: ?><div class="student-avatar"><?= htmlspecialchars($initials) ?></div><?php endif; ?>
                                <div><div class="fw-bold"><?= htmlspecialchars($full_name) ?></div><div class="small text-muted">Student #<?= (int)$student['id'] ?></div></div>
                            </div>
                        </td>
                        <td><div><?= htmlspecialchars($student['email']) ?></div><div class="small text-muted"><?= htmlspecialchars($student['phone'] ?: 'No phone') ?></div></td>
                        <td><span class="member-id"><?= htmlspecialchars($student['member_id'] ?: 'Not assigned') ?></span></td>
                        <td><span class="belt"><?= htmlspecialchars($student['current_belt'] ?: 'White Belt') ?></span></td>
                        <td><span class="<?= $att_class ?>"><?= $att ?>%</span><div class="small text-muted"><?= (int)$student['present_count'] ?> / <?= (int)$student['attendance_count'] ?> present</div></td>
                        <td><?= !empty($student['joined_at']) ? date('M j, Y', strtotime($student['joined_at'])) : '—' ?></td>
                        <td><div class="d-flex gap-1 flex-wrap"><a href="attendance.php?student_id=<?= (int)$student['id'] ?>" class="btn btn-sm btn-outline-secondary action-link"><i class="fas fa-calendar-check"></i>Attendance</a><a href="grading.php?student_id=<?= (int)$student['id'] ?>" class="btn btn-sm btn-outline-dark action-link"><i class="fas fa-medal"></i>Grading</a><a href="password_requests.php" class="btn btn-sm btn-outline-warning action-link" title="Student Password Management"><i class="fas fa-key"></i>Password</a></div></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$students): ?><tr><td colspan="7" class="empty"><i class="fas fa-user-slash fa-2x mb-3 d-block"></i><?= $search !== '' ? 'No students matched your search.' : 'No approved students found in your dojo yet.' ?></td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
