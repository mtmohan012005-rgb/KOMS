<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('student');

$user_id = (int)($_SESSION['user_id'] ?? 0);

$user_stmt = $pdo->prepare("SELECT id, first_name, last_name, email, dob, gender, phone, address, profile_photo, created_at FROM users WHERE id = ? LIMIT 1");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch() ?: [];

$student_name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'Student';
$student_id = 'MD-' . str_pad((string)$user_id, 5, '0', STR_PAD_LEFT);
$initials = strtoupper(substr($user['first_name'] ?? 'S', 0, 1) . substr($user['last_name'] ?? '', 0, 1));

$membership_stmt = $pdo->prepare("SELECT d.id AS dojo_id, d.name AS dojo_name, d.location, d.training_days, d.training_timings, m.status, m.joined_at FROM dojo_memberships m JOIN dojos d ON d.id = m.dojo_id WHERE m.student_id = ? ORDER BY CASE WHEN m.status = 'approved' THEN 0 WHEN m.status = 'pending' THEN 1 ELSE 2 END, m.created_at DESC LIMIT 1");
$membership_stmt->execute([$user_id]);
$membership = $membership_stmt->fetch();

$belt_stmt = $pdo->prepare("SELECT new_belt, previous_belt, exam_date, grade FROM grading_history WHERE student_id = ? ORDER BY exam_date DESC, id DESC LIMIT 1");
$belt_stmt->execute([$user_id]);
$current_belt = $belt_stmt->fetch();
$belt_name = $current_belt['new_belt'] ?? 'White Belt';
$next_belt = 'Next grading level';

$attendance_stmt = $pdo->prepare("SELECT COUNT(*) AS total_records, SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) AS present_records, SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) AS late_records FROM attendance_entries WHERE student_id = ?");
$attendance_stmt->execute([$user_id]);
$attendance = $attendance_stmt->fetch() ?: ['total_records' => 0, 'present_records' => 0, 'late_records' => 0];
$total_attendance = (int)$attendance['total_records'];
$present_attendance = (int)$attendance['present_records'];
$late_attendance = (int)$attendance['late_records'];
$attendance_rate = $total_attendance > 0 ? round(($present_attendance / $total_attendance) * 100) : 0;

$fee_stmt = $pdo->prepare("SELECT COALESCE(SUM(GREATEST(fr.amount_due - COALESCE(p.paid, 0), 0)), 0) FROM fee_records fr LEFT JOIN (SELECT fee_record_id, SUM(amount) AS paid FROM payments GROUP BY fee_record_id) p ON p.fee_record_id = fr.id WHERE fr.student_id = ? AND fr.status IN ('pending','partially_paid','overdue')");
$fee_stmt->execute([$user_id]);
$outstanding_fees = (float)$fee_stmt->fetchColumn();

$tournament_stmt = $pdo->prepare("SELECT COUNT(*) FROM tournament_registrations WHERE student_id = ? AND status <> 'rejected'");
$tournament_stmt->execute([$user_id]);
$tournament_count = (int)$tournament_stmt->fetchColumn();

$recent_att_stmt = $pdo->prepare("SELECT s.session_date, s.start_time, d.name AS dojo_name, e.status FROM attendance_entries e JOIN attendance_sessions s ON s.id=e.session_id JOIN dojos d ON d.id=s.dojo_id WHERE e.student_id=? ORDER BY s.session_date DESC, s.id DESC LIMIT 5");
$recent_att_stmt->execute([$user_id]);
$recent_attendance = $recent_att_stmt->fetchAll();

$announcements = [];
if ($membership && $membership['status'] === 'approved') {
    $ann_stmt = $pdo->prepare("SELECT title, content, publish_date FROM announcements WHERE status='active' AND publish_date<=CURDATE() AND (expiry_date IS NULL OR expiry_date>=CURDATE()) AND (level='global' OR (level='dojo' AND dojo_id=?)) ORDER BY publish_date DESC, id DESC LIMIT 4");
    $ann_stmt->execute([(int)$membership['dojo_id']]);
    $announcements = $ann_stmt->fetchAll();
} else {
    $ann_stmt = $pdo->query("SELECT title, content, publish_date FROM announcements WHERE status='active' AND publish_date<=CURDATE() AND (expiry_date IS NULL OR expiry_date>=CURDATE()) AND level='global' ORDER BY publish_date DESC, id DESC LIMIT 4");
    $announcements = $ann_stmt->fetchAll();
}

$grading_stmt = $pdo->prepare("SELECT previous_belt, new_belt, exam_date, grade FROM grading_history WHERE student_id=? ORDER BY exam_date DESC, id DESC LIMIT 4");
$grading_stmt->execute([$user_id]);
$grading_history = $grading_stmt->fetchAll();

if ($grading_history) {
    $belt_order = ['White'=>0,'Yellow'=>1,'Orange'=>2,'Green'=>3,'Blue'=>4,'Purple'=>5,'Brown'=>6,'Black'=>7];
    $current_base = strtolower(trim(explode(' ', (string)$belt_name)[0]));
    $map = ['white'=>'Yellow','yellow'=>'Orange','orange'=>'Green','green'=>'Blue','blue'=>'Purple','purple'=>'Brown','brown'=>'Black','black'=>'Advanced'];
    $next_belt = $map[$current_base] ?? 'Next grading level';
}

$quick_stats = [
    ['label'=>'Attendance','value'=>$attendance_rate.'%','icon'=>'✓','tone'=>'green','note'=>$present_attendance.' of '.$total_attendance.' present'],
    ['label'=>'Pending Fees','value'=>'₹'.number_format($outstanding_fees, 0),'icon'=>'₹','tone'=>'red','note'=>$outstanding_fees > 0 ? 'Payment required' : 'All clear'],
    ['label'=>'Tournament Entries','value'=>(string)$tournament_count,'icon'=>'★','tone'=>'orange','note'=>'Active registrations'],
    ['label'=>'Current Belt','value'=>$belt_name,'icon'=>'🥋','tone'=>'gold','note'=>$next_belt],
];

function student_h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= student_h($student_name) ?> • Mass Dragon Student Portal</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/student-portal.css?v=1">
</head>
<body>
<div class="student-portal">
    <div class="sp-overlay" id="studentOverlay"></div>
    <aside class="sp-sidebar" id="studentSidebar">
        <div class="sp-brand">
            <div class="sp-brand-logo">🥋</div>
            <div><strong>MASS DRAGON DOJO</strong><small>KOMS Student Portal</small></div>
        </div>
        <div class="sp-heading"><h1>Student Portal</h1><p>Your progress. Our pride.</p></div>
        <nav class="sp-nav" id="studentNav">
            <a class="active" href="<?= APP_URL ?>/student/dashboard.php"><span class="ico">⌂</span>Dashboard</a>
            <a href="<?= APP_URL ?>/profile.php"><span class="ico">◉</span>Profile</a>
            <a href="<?= APP_URL ?>/student/attendance.php"><span class="ico">✓</span>Attendance</a>
            <a href="<?= APP_URL ?>/student/grading.php"><span class="ico">🥋</span>Belt Progress</a>
            <a href="<?= APP_URL ?>/student/achievements.php"><span class="ico">★</span>Achievements</a>
            <a href="<?= APP_URL ?>/student/fees.php"><span class="ico">₹</span>Fees &amp; Payments</a>
            <a href="<?= APP_URL ?>/student/announcements.php"><span class="ico">◈</span>Announcements</a>
            <a href="<?= APP_URL ?>/student/tournaments.php"><span class="ico">▣</span>Tournaments</a>
            <a href="<?= APP_URL ?>/student/my_dojo.php"><span class="ico">⌘</span>My Dojo</a>
            <a href="<?= APP_URL ?>/logout.php"><span class="ico">↪</span>Sign Out</a>
        </nav>
        <div class="sp-side-note"><strong>Stay disciplined.</strong><br>Train consistently, track your progress and keep moving toward your next belt.</div>
    </aside>

    <main class="sp-main">
        <header class="sp-topbar">
            <div class="sp-top-left">
                <button class="sp-menu" id="studentMenu" type="button" aria-label="Open student menu">☰</button>
                <div class="sp-title"><h2>Dashboard</h2><p>Training, attendance, fees and progress at a glance.</p></div>
            </div>
            <div class="sp-user"><div class="sp-avatar"><?= student_h($initials ?: 'S') ?></div><div><b><?= student_h($student_name) ?></b><span><?= student_h($student_id) ?></span></div></div>
        </header>

        <section class="sp-content">
            <section class="sp-hero">
                <div class="sp-kicker">KOMS • Mass Dragon Dojo</div>
                <h1>Welcome back, <?= student_h($student_name) ?></h1>
                <p>Keep your training record, attendance, belt journey, tournaments and important dojo updates in one professional student portal.</p>
                <div class="sp-hero-actions">
                    <a class="sp-btn sp-btn-light" href="<?= APP_URL ?>/student/attendance.php"><i class="fa-solid fa-calendar-check"></i>View Attendance</a>
                    <a class="sp-btn sp-btn-dark" href="<?= APP_URL ?>/profile.php"><i class="fa-solid fa-user-pen"></i>My Profile</a>
                </div>
            </section>

            <?php if ($membership && $membership['status'] === 'pending'): ?>
                <div class="sp-panel" style="margin-top:18px;border-left:4px solid #f4a300;">
                    <div class="sp-panel-head"><h3>Membership awaiting approval</h3><span class="sp-badge badge-gold">Pending</span></div>
                    <p style="font-size:11px;color:#6f747a;margin:0;">Your request to join <strong><?= student_h($membership['dojo_name']) ?></strong> is waiting for the Dojo Master.</p>
                </div>
            <?php elseif (!$membership): ?>
                <div class="sp-panel" style="margin-top:18px;border-left:4px solid #b51218;">
                    <div class="sp-panel-head"><h3>No dojo assigned yet</h3><a href="<?= APP_URL ?>/find_dojo.php">Find a Dojo</a></div>
                    <p style="font-size:11px;color:#6f747a;margin:0;">Choose an approved dojo and submit a membership request to start tracking your training.</p>
                </div>
            <?php endif; ?>

            <section class="sp-stats">
                <?php foreach ($quick_stats as $stat): ?>
                    <div class="sp-card <?= student_h('tone-'.$stat['tone']) ?>">
                        <div class="sp-stat-top"><div class="sp-stat-icon"><?= $stat['icon'] ?></div></div>
                        <div class="sp-stat-label"><?= student_h($stat['label']) ?></div>
                        <div class="sp-stat-value"><?= student_h($stat['value']) ?></div>
                        <div class="sp-stat-note"><?= student_h($stat['note']) ?></div>
                    </div>
                <?php endforeach; ?>
            </section>

            <section class="sp-grid">
                <div>
                    <div class="sp-panel">
                        <div class="sp-panel-head"><h3>My Training Base</h3><a href="<?= APP_URL ?>/student/my_dojo.php">View dojo</a></div>
                        <?php if ($membership && $membership['status'] === 'approved'): ?>
                            <div class="sp-dojo">
                                <div class="sp-kicker">Approved Membership</div>
                                <div class="sp-dojo-name"><?= student_h($membership['dojo_name']) ?></div>
                                <small><i class="fa-solid fa-location-dot"></i> <?= student_h($membership['location']) ?></small>
                                <div class="sp-dojo-meta">
                                    <div><small>Training Days</small><strong><?= student_h($membership['training_days'] ?: 'See dojo schedule') ?></strong></div>
                                    <div><small>Training Time</small><strong><?= student_h($membership['training_timings'] ?: 'See dojo schedule') ?></strong></div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="sp-dojo"><div class="sp-dojo-name">No approved dojo</div><small>Membership information will appear here after approval.</small></div>
                        <?php endif; ?>
                    </div>

                    <div class="sp-panel" style="margin-top:18px;">
                        <div class="sp-panel-head"><h3>Recent Attendance</h3><a href="<?= APP_URL ?>/student/attendance.php">View all</a></div>
                        <div style="overflow:auto">
                            <table class="sp-table">
                                <thead><tr><th>Date</th><th>Dojo</th><th>Status</th></tr></thead>
                                <tbody>
                                <?php if (!$recent_attendance): ?>
                                    <tr><td colspan="3" style="text-align:center;color:#999;padding:22px 8px;">No attendance records yet.</td></tr>
                                <?php else: foreach ($recent_attendance as $row):
                                    $status = strtolower($row['status']);
                                    $badge = $status === 'present' ? 'badge-green' : ($status === 'absent' ? 'badge-red' : 'badge-gold');
                                ?>
                                    <tr>
                                        <td><?= student_h(date('M j, Y', strtotime($row['session_date']))) ?></td>
                                        <td><?= student_h($row['dojo_name']) ?></td>
                                        <td><span class="sp-badge <?= $badge ?>"><?= student_h($row['status']) ?></span></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="sp-panel" style="margin-top:18px;">
                        <div class="sp-panel-head"><h3>Latest Announcements</h3><a href="<?= APP_URL ?>/student/announcements.php">View all</a></div>
                        <?php if (!$announcements): ?>
                            <p style="font-size:10px;color:#8b8b8b;margin:0;">No active announcements right now.</p>
                        <?php else: foreach ($announcements as $announcement): ?>
                            <div class="sp-announce"><div class="sp-announce-icon">📢</div><div><strong><?= student_h($announcement['title']) ?></strong><small><?= student_h(date('M j, Y', strtotime($announcement['publish_date']))) ?></small></div></div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>

                <div>
                    <div class="sp-panel">
                        <div class="sp-panel-head"><h3>Quick Actions</h3></div>
                        <div class="sp-actions">
                            <a class="sp-action" href="<?= APP_URL ?>/student/attendance.php"><i class="fa-solid fa-calendar-check"></i><b>Attendance</b><small>Training record</small></a>
                            <a class="sp-action" href="<?= APP_URL ?>/student/fees.php"><i class="fa-solid fa-wallet"></i><b>Fees</b><small>Payments &amp; dues</small></a>
                            <a class="sp-action" href="<?= APP_URL ?>/student/grading.php"><i class="fa-solid fa-medal"></i><b>Grading</b><small>Belt progress</small></a>
                            <a class="sp-action" href="<?= APP_URL ?>/student/tournaments.php"><i class="fa-solid fa-trophy"></i><b>Tournaments</b><small>Events &amp; entry</small></a>
                            <a class="sp-action" href="<?= APP_URL ?>/student/achievements.php"><i class="fa-solid fa-star"></i><b>Achievements</b><small>Your records</small></a>
                            <a class="sp-action" href="<?= APP_URL ?>/profile.php"><i class="fa-solid fa-user"></i><b>Profile</b><small>Account details</small></a>
                        </div>
                    </div>

                    <div class="sp-panel" style="margin-top:18px;">
                        <div class="sp-panel-head"><h3>Attendance Rate</h3><span><?= $attendance_rate ?>%</span></div>
                        <div class="sp-track"><div class="sp-fill" style="width:<?= max(0,min(100,$attendance_rate)) ?>%"></div></div>
                        <div style="display:flex;justify-content:space-between;margin-top:9px;color:#8b8b8b;font-size:9px;"><span><?= $present_attendance ?> present</span><span><?= $late_attendance ?> late</span></div>
                    </div>

                    <div class="sp-panel" style="margin-top:18px;">
                        <div class="sp-panel-head"><h3>Belt History</h3><a href="<?= APP_URL ?>/student/grading.php">Details</a></div>
                        <?php if (!$grading_history): ?>
                            <p style="font-size:10px;color:#8b8b8b;margin:0;">No grading history has been recorded yet.</p>
                        <?php else: foreach ($grading_history as $grade): ?>
                            <div class="sp-announce"><div class="sp-announce-icon">🥋</div><div><strong><?= student_h($grade['new_belt']) ?></strong><small><?= student_h(date('M j, Y', strtotime($grade['exam_date']))) ?><?= $grade['grade'] ? ' • '.student_h($grade['grade']) : '' ?></small></div></div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </section>

            <div class="sp-footer">Mass Dragon Dojo Student Portal • KOMS • <?= date('Y') ?></div>
        </section>
    </main>
</div>
<script src="<?= APP_URL ?>/assets/js/app.js?v=1"></script>
<script>
(function(){
    const sidebar=document.getElementById('studentSidebar');
    const overlay=document.getElementById('studentOverlay');
    const menu=document.getElementById('studentMenu');
    function closeMenu(){ if(sidebar) sidebar.classList.remove('open'); if(overlay) overlay.classList.remove('show'); document.body.style.overflow=''; }
    function openMenu(){ if(sidebar) sidebar.classList.add('open'); if(overlay) overlay.classList.add('show'); document.body.style.overflow='hidden'; }
    if(menu) menu.addEventListener('click',()=> sidebar.classList.contains('open')?closeMenu():openMenu());
    if(overlay) overlay.addEventListener('click',closeMenu);
    document.addEventListener('keydown',e=>{if(e.key==='Escape') closeMenu();});
    document.querySelectorAll('#studentNav a').forEach(a=>a.addEventListener('click',closeMenu));
    window.addEventListener('resize',()=>{if(window.innerWidth>850) closeMenu();});
})();
</script>
</body>
</html>
