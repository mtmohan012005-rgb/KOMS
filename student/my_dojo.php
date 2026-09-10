<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('student');

$user_id = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT d.*, m.status AS membership_status, m.joined_at,
           u.first_name AS master_fname, u.last_name AS master_lname,
           u.email AS master_email, u.phone AS master_phone
    FROM dojo_memberships m
    INNER JOIN dojos d ON m.dojo_id = d.id
    INNER JOIN users u ON d.master_id = u.id
    WHERE m.student_id = ? AND m.status = 'approved'
    LIMIT 1
");
$stmt->execute([$user_id]);
$dojo = $stmt->fetch();

if (!$dojo) {
    $_SESSION['info_msg'] = 'You are not currently an approved member of any dojo.';
    redirect('/student/dashboard.php');
}

$classmates_stmt = $pdo->prepare("
    SELECT u.first_name, u.last_name, m.joined_at,
           (SELECT gh.new_belt FROM grading_history gh
            WHERE gh.student_id = u.id
            ORDER BY gh.exam_date DESC, gh.id DESC LIMIT 1) AS belt
    FROM dojo_memberships m
    INNER JOIN users u ON m.student_id = u.id
    WHERE m.dojo_id = ? AND m.status = 'approved' AND u.id <> ?
    ORDER BY u.first_name, u.last_name
    LIMIT 12
");
$classmates_stmt->execute([(int)$dojo['id'], $user_id]);
$classmates = $classmates_stmt->fetchAll();

$stats_stmt = $pdo->prepare("
    SELECT
        (SELECT COUNT(*) FROM dojo_memberships WHERE dojo_id = ? AND status = 'approved') AS member_count,
        (SELECT COUNT(*) FROM attendance_sessions WHERE dojo_id = ?) AS session_count,
        (SELECT COUNT(*) FROM grading_history WHERE dojo_id = ?) AS grading_count,
        (SELECT COUNT(*) FROM announcements WHERE dojo_id = ? AND status = 'active') AS announcement_count
");
$stats_stmt->execute([(int)$dojo['id'], (int)$dojo['id'], (int)$dojo['id'], (int)$dojo['id']]);
$stats = $stats_stmt->fetch() ?: [];

$ann_stmt = $pdo->prepare("
    SELECT title, content, publish_date, expiry_date
    FROM announcements
    WHERE dojo_id = ? AND status = 'active'
      AND publish_date <= CURDATE()
      AND (expiry_date IS NULL OR expiry_date >= CURDATE())
    ORDER BY publish_date DESC, id DESC
    LIMIT 5
");
$ann_stmt->execute([(int)$dojo['id']]);
$announcements = $ann_stmt->fetchAll();

$page_title = 'My Dojo - ' . $dojo['name'];
require_once '../includes/header.php';
?>

<style>
    .dojo-page{margin-top:1.25rem;margin-bottom:3rem}
    .dojo-hero{position:relative;overflow:hidden;border-radius:26px;padding:2rem;color:#fff;background:linear-gradient(135deg,#070707 0%,#171717 55%,#4b0909 100%);box-shadow:0 24px 60px rgba(0,0,0,.16)}
    .dojo-hero:after{content:'';position:absolute;width:300px;height:300px;border-radius:50%;right:-90px;top:-130px;background:radial-gradient(circle,rgba(229,9,20,.38),transparent 68%)}
    .dojo-kicker{font-size:.72rem;letter-spacing:.16em;text-transform:uppercase;font-weight:800;color:#f4bd17}
    .dojo-title{font-size:clamp(1.9rem,4vw,3rem);font-weight:900;line-height:1.05;margin:.45rem 0 .55rem}
    .dojo-sub{color:rgba(255,255,255,.68);margin:0;max-width:760px}
    .dojo-badge{border-radius:999px;padding:.4rem .75rem;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.12);font-size:.72rem;font-weight:800;text-transform:uppercase}
    .dojo-panel{border:0;border-radius:20px;overflow:hidden;box-shadow:0 14px 38px rgba(17,24,39,.08);height:100%}
    .dojo-panel .card-header{background:#fff;border:0;padding:1.1rem 1.2rem}
    .dojo-panel .card-body{background:#fff}
    .panel-kicker{font-size:.7rem;text-transform:uppercase;letter-spacing:.08em;color:#8b8b8b;font-weight:800}
    .info-box{padding:1rem;border-radius:16px;background:#f7f7f7;height:100%}
    .info-box i{width:28px;color:#b51212}
    .info-label{font-size:.7rem;text-transform:uppercase;letter-spacing:.07em;color:#8b8b8b;font-weight:800}
    .info-value{margin-top:.25rem;font-weight:700;color:#222}
    .stat-card{border:0;border-radius:18px;box-shadow:0 12px 32px rgba(17,24,39,.07);height:100%}
    .stat-icon{width:46px;height:46px;border-radius:14px;display:inline-flex;align-items:center;justify-content:center;background:#111;color:#fff}
    .stat-label{margin-top:.8rem;color:#8b8b8b;font-size:.7rem;text-transform:uppercase;letter-spacing:.08em;font-weight:800}
    .stat-value{font-size:1.7rem;font-weight:900;line-height:1.1;margin-top:.2rem}
    .member-item{display:flex;justify-content:space-between;align-items:center;gap:1rem;padding:.85rem 1rem;border-bottom:1px solid #eee}
    .member-item:last-child{border-bottom:0}
    .member-avatar{width:40px;height:40px;border-radius:12px;background:#111;color:#f4bd17;display:inline-flex;align-items:center;justify-content:center;font-weight:900;flex:0 0 auto}
    .belt-pill{font-size:.68rem;font-weight:800;padding:.35rem .55rem;border-radius:999px;background:#f1f1f1;color:#555}
    .announcement{padding:1rem 0;border-bottom:1px solid #eee}
    .announcement:last-child{border-bottom:0;padding-bottom:0}
    .announcement-title{font-weight:800;color:#222}
    .announcement-date{font-size:.72rem;color:#888}
    .quick-link{display:flex;align-items:center;gap:.8rem;text-decoration:none;color:#222;padding:.85rem 1rem;border-radius:14px;background:#f7f7f7;transition:.2s}
    .quick-link:hover{color:#222;transform:translateY(-2px);box-shadow:0 8px 18px rgba(0,0,0,.06)}
    .quick-link i{color:#b51212}
    @media(max-width:767px){.dojo-page{margin-top:.75rem}.dojo-hero{padding:1.35rem;border-radius:18px}}
</style>

<div class="dojo-page">
    <section class="dojo-hero mb-4">
        <div class="position-relative" style="z-index:2">
            <div class="dojo-kicker">KOMS • My Dojo</div>
            <div class="dojo-title"><?= htmlspecialchars($dojo['name']) ?></div>
            <p class="dojo-sub">Your training home, instructor details, class community, schedule and dojo announcements.</p>
            <div class="d-flex flex-wrap gap-2 mt-3">
                <span class="dojo-badge"><i class="fas fa-circle-check me-1"></i> Approved Member</span>
                <span class="dojo-badge"><i class="fas fa-location-dot me-1"></i><?= htmlspecialchars($dojo['location']) ?></span>
            </div>
        </div>
    </section>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3"><div class="card stat-card"><div class="card-body p-3 p-lg-4"><span class="stat-icon"><i class="fas fa-users"></i></span><div class="stat-label">Active Students</div><div class="stat-value"><?= (int)($stats['member_count'] ?? 0) ?></div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card stat-card"><div class="card-body p-3 p-lg-4"><span class="stat-icon"><i class="fas fa-calendar-check"></i></span><div class="stat-label">Training Sessions</div><div class="stat-value"><?= (int)($stats['session_count'] ?? 0) ?></div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card stat-card"><div class="card-body p-3 p-lg-4"><span class="stat-icon"><i class="fas fa-medal"></i></span><div class="stat-label">Grading Records</div><div class="stat-value"><?= (int)($stats['grading_count'] ?? 0) ?></div></div></div></div>
        <div class="col-6 col-xl-3"><div class="card stat-card"><div class="card-body p-3 p-lg-4"><span class="stat-icon"><i class="fas fa-bullhorn"></i></span><div class="stat-label">Active Notices</div><div class="stat-value"><?= (int)($stats['announcement_count'] ?? 0) ?></div></div></div></div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card dojo-panel mb-4">
                <div class="card-header"><div class="panel-kicker">Dojo Profile</div><h5 class="mb-0 mt-1">Training Information</h5></div>
                <div class="card-body p-4">
                    <?php if (!empty($dojo['description'])): ?>
                        <p class="text-muted mb-4"><?= nl2br(htmlspecialchars($dojo['description'])) ?></p>
                    <?php endif; ?>
                    <div class="row g-3">
                        <div class="col-md-6"><div class="info-box"><div class="info-label"><i class="fas fa-location-dot"></i>Location</div><div class="info-value"><?= htmlspecialchars($dojo['location']) ?></div></div></div>
                        <div class="col-md-6"><div class="info-box"><div class="info-label"><i class="fas fa-calendar-days"></i>Training Days</div><div class="info-value"><?= htmlspecialchars($dojo['training_days'] ?: 'See dojo schedule') ?></div></div></div>
                        <div class="col-md-6"><div class="info-box"><div class="info-label"><i class="fas fa-clock"></i>Training Timings</div><div class="info-value"><?= htmlspecialchars($dojo['training_timings'] ?: 'See dojo schedule') ?></div></div></div>
                        <div class="col-md-6"><div class="info-box"><div class="info-label"><i class="fas fa-phone"></i>Contact</div><div class="info-value"><?= htmlspecialchars($dojo['contact_number'] ?: $dojo['master_phone'] ?: 'N/A') ?></div></div></div>
                    </div>
                </div>
            </div>

            <div class="card dojo-panel mb-4">
                <div class="card-header"><div class="panel-kicker">Head Instructor</div><h5 class="mb-0 mt-1">Sensei <?= htmlspecialchars($dojo['master_fname'] . ' ' . $dojo['master_lname']) ?></h5></div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6"><div class="info-box"><div class="info-label"><i class="fas fa-envelope"></i>Email</div><div class="info-value"><?= htmlspecialchars($dojo['email'] ?: $dojo['master_email']) ?></div></div></div>
                        <div class="col-md-6"><div class="info-box"><div class="info-label"><i class="fas fa-phone"></i>Phone</div><div class="info-value"><?= htmlspecialchars($dojo['master_phone'] ?: 'N/A') ?></div></div></div>
                        <div class="col-12"><div class="info-box"><div class="info-label"><i class="fas fa-clock-rotate-left"></i>Membership Started</div><div class="info-value"><?= !empty($dojo['joined_at']) ? date('F j, Y', strtotime($dojo['joined_at'])) : 'Not recorded' ?></div></div></div>
                    </div>
                </div>
            </div>

            <div class="card dojo-panel">
                <div class="card-header"><div class="panel-kicker">Latest Updates</div><h5 class="mb-0 mt-1">Dojo Announcements</h5></div>
                <div class="card-body p-4">
                    <?php if ($announcements): ?>
                        <?php foreach ($announcements as $a): ?>
                            <div class="announcement">
                                <div class="d-flex justify-content-between gap-3 align-items-start">
                                    <div class="announcement-title"><?= htmlspecialchars($a['title']) ?></div>
                                    <div class="announcement-date"><?= date('M j, Y', strtotime($a['publish_date'])) ?></div>
                                </div>
                                <div class="text-muted small mt-2"><?= nl2br(htmlspecialchars($a['content'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted"><i class="fas fa-bell-slash fa-2x mb-2"></i><div>No active dojo announcements.</div></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card dojo-panel mb-4">
                <div class="card-header"><div class="panel-kicker">Your Community</div><h5 class="mb-0 mt-1">Fellow Students</h5></div>
                <div class="card-body p-0">
                    <?php if ($classmates): ?>
                        <?php foreach ($classmates as $cm): ?>
                            <?php $initials = strtoupper(substr($cm['first_name'],0,1) . substr($cm['last_name'],0,1)); ?>
                            <div class="member-item">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="member-avatar"><?= htmlspecialchars($initials) ?></div>
                                    <div><div class="fw-bold"><?= htmlspecialchars($cm['first_name'] . ' ' . $cm['last_name']) ?></div><div class="small text-muted">Joined <?= !empty($cm['joined_at']) ? date('M Y', strtotime($cm['joined_at'])) : '—' ?></div></div>
                                </div>
                                <span class="belt-pill"><?= htmlspecialchars($cm['belt'] ?: 'White Belt') ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted">No other active students yet.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card dojo-panel mb-4">
                <div class="card-header"><div class="panel-kicker">Quick Access</div><h5 class="mb-0 mt-1">Student Tools</h5></div>
                <div class="card-body p-3">
                    <div class="d-grid gap-2">
                        <a class="quick-link" href="attendance.php"><i class="fas fa-calendar-check"></i><span>My Attendance</span></a>
                        <a class="quick-link" href="fees.php"><i class="fas fa-wallet"></i><span>Fees & Payments</span></a>
                        <a class="quick-link" href="grading.php"><i class="fas fa-medal"></i><span>Grading History</span></a>
                        <a class="quick-link" href="tournaments.php"><i class="fas fa-trophy"></i><span>Tournaments</span></a>
                        <a class="quick-link" href="announcements.php"><i class="fas fa-bullhorn"></i><span>All Announcements</span></a>
                    </div>
                </div>
            </div>

            <a href="dashboard.php" class="btn btn-dark w-100 py-3"><i class="fas fa-arrow-left me-2"></i>Back to Student Dashboard</a>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>