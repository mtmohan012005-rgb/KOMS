<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('master');

$user_id = (int)($_SESSION['user_id'] ?? 0);

$userStmt = $pdo->prepare("SELECT id, first_name, last_name, email, dob, gender, phone, address, member_id FROM users WHERE id = ? LIMIT 1");
$userStmt->execute([$user_id]);
$master = $userStmt->fetch() ?: [];

$dojoStmt = $pdo->prepare("SELECT id, name, location, contact_number, phone, email, experience, achievements, training_days, training_timings, status FROM dojos WHERE master_id = ? ORDER BY id DESC LIMIT 1");
$dojoStmt->execute([$user_id]);
$dojo = $dojoStmt->fetch() ?: null;

$dojo_id = $dojo ? (int)$dojo['id'] : 0;

$active_students = 0;
$pending_requests = 0;
$today_present = 0;
$today_total = 0;
$today_payments = 0;

if ($dojo_id > 0) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM dojo_memberships WHERE dojo_id = ? AND status = 'approved'");
    $stmt->execute([$dojo_id]);
    $active_students = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM dojo_memberships WHERE dojo_id = ? AND status = 'pending'");
    $stmt->execute([$dojo_id]);
    $pending_requests = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance_entries ae JOIN attendance_sessions s ON ae.session_id = s.id WHERE s.dojo_id = ? AND s.session_date = CURDATE()");
    $stmt->execute([$dojo_id]);
    $today_total = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance_entries ae JOIN attendance_sessions s ON ae.session_id = s.id WHERE s.dojo_id = ? AND s.session_date = CURDATE() AND ae.status = 'present'");
    $stmt->execute([$dojo_id]);
    $today_present = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(p.amount),0) FROM payments p JOIN fee_records fr ON p.fee_record_id = fr.id JOIN fee_structures fs ON fr.fee_structure_id = fs.id WHERE fs.dojo_id = ? AND p.payment_date = CURDATE()");
    $stmt->execute([$dojo_id]);
    $today_payments = (float)$stmt->fetchColumn();
}

$attendance_percent = $today_total > 0 ? (int)round(($today_present / $today_total) * 100) : 0;
$full_name = trim(($master['first_name'] ?? '') . ' ' . ($master['last_name'] ?? ''));
$initials = strtoupper(substr($master['first_name'] ?? 'M', 0, 1) . substr($master['last_name'] ?? '', 0, 1));
$age = !empty($master['dob']) ? calculate_age($master['dob']) : null;

$recentStudents = [];
$recentRequests = [];
$announcements = [];

if ($dojo_id > 0) {
    $stmt = $pdo->prepare("SELECT u.id, u.first_name, u.last_name, u.email, u.member_id, m.joined_at FROM dojo_memberships m JOIN users u ON m.student_id = u.id WHERE m.dojo_id = ? AND m.status = 'approved' ORDER BY m.joined_at DESC LIMIT 5");
    $stmt->execute([$dojo_id]);
    $recentStudents = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT m.id, u.first_name, u.last_name, u.email, u.member_id, m.created_at FROM dojo_memberships m JOIN users u ON m.student_id = u.id WHERE m.dojo_id = ? AND m.status = 'pending' ORDER BY m.created_at DESC LIMIT 5");
    $stmt->execute([$dojo_id]);
    $recentRequests = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT title, publish_date FROM announcements WHERE (level = 'global' OR (level = 'dojo' AND dojo_id = ?)) AND status = 'active' AND (expiry_date IS NULL OR expiry_date >= CURDATE()) ORDER BY publish_date DESC LIMIT 4");
    $stmt->execute([$dojo_id]);
    $announcements = $stmt->fetchAll();
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#111315">
<title>Mass Dragon Dojo - Master Portal</title>
<link rel="stylesheet" href="../css/master.css?v=2">
</head>
<body>
<div class="mobile-overlay" id="mobileOverlay"></div>

<aside class="sidebar" id="sidebar">
    <div class="brand">
        <img src="../assets/images/shorin_ryu_crest.jpg" alt="Shorin Ryu" style="width:44px;height:44px;border-radius:50%;border:2px solid #d9b75d;box-shadow:0 0 14px rgba(217,183,93,0.4);object-fit:cover;flex-shrink:0;">
        <div class="brand-text">
            <h2>MASS DRAGON DOJO</h2>
            <span>Karate Organization Management System</span>
        </div>
    </div>

    <div class="portal-card">
        <small>MASTER PORTAL</small>
        <h3><?= htmlspecialchars($full_name ?: 'Master') ?></h3>
        <p><?= htmlspecialchars($master['member_id'] ?? 'Master Account') ?></p>
    </div>

    <div class="menu-title">MANAGEMENT</div>
    <nav class="sidebar-nav">
        <a href="#dashboard" class="active"><span>⌂</span>Dashboard</a>
        <a href="students.php"><span>♙</span>Students</a>
        <a href="password_requests.php"><span>🔑</span>Password Requests</a>
        <a href="attendance.php"><span>✓</span>Attendance</a>
        <a href="fees.php"><span>₹</span>Fees</a>
        <a href="grading.php"><span>🥋</span>Belt Promotions</a>
        <a href="tournaments.php"><span>🏆</span>Tournament</a>
        <a href="events.php"><span>◫</span>Events</a>
        <a href="announcements.php"><span>!</span>Announcements</a>
        <a href="gallery.php"><span>▧</span>Gallery</a>
        <a href="certificates.php"><span>▣</span>Certificates</a>
        <a href="reports.php"><span>◫</span>Reports</a>
        <a href="inventory.php"><span>◈</span>Inventory</a>
        <a href="communication.php"><span>✉</span>Communication</a>
        <a href="profile.php"><span>◉</span>Profile</a>
        <a href="settings.php"><span>⚙</span>Settings</a>
        <a href="../logout.php"><span>↪</span>Logout</a>
    </nav>

    <!-- Calligraphic Slogan from Reference Mockup -->
    <div style="margin-top:22px;padding:16px 10px 6px;border-top:1px solid rgba(255,255,255,0.08);">
        <div style="font-family:'Caveat Brush',cursive;font-size:1.35rem;color:#f0d584;line-height:1.25;">
            Discipline.<br>
            Strength.<br>
            Internal Peace.
        </div>
        <div style="width:50px;height:3px;background:linear-gradient(90deg,#e05b62,#790a10);border-radius:999px;margin-top:8px;"></div>
    </div>

    <div class="sidebar-footer" style="margin-top:14px;">
        <strong><?= htmlspecialchars($dojo['name'] ?? 'No dojo registered') ?></strong><br>
        <?= htmlspecialchars($dojo['training_days'] ?? 'Training schedule not set') ?><br>
        <?= htmlspecialchars($dojo['training_timings'] ?? 'Timing not set') ?>
    </div>
</aside>

<main class="main">
    <header class="topbar">
        <div class="topbar-left">
            <button class="menu-button" id="menuButton" aria-label="Open menu">☰</button>
            <div>
                <h1>Master Dashboard</h1>
                <p>Manage your dojo and students</p>
            </div>
        </div>
        <div class="topbar-right">
            <button class="notification-button" type="button" onclick="showToast('Notifications are up to date')">🔔</button>
            <div class="master-user">
                <div class="master-avatar"><?= htmlspecialchars($initials ?: 'MD') ?></div>
                <div class="master-user-text">
                    <strong><?= htmlspecialchars($full_name ?: 'Master') ?></strong>
                    <span><?= htmlspecialchars($master['member_id'] ?? 'MASTER') ?></span>
                </div>
            </div>
        </div>
    </header>

    <div class="content">
        <section class="hero" id="dashboard">
            <div class="hero-content">
                <span class="hero-badge">MASTER PORTAL</span>
                <h2>Welcome, <?= htmlspecialchars($master['first_name'] ?? 'Master') ?></h2>
                <p>Manage students, attendance, fees, belt promotions, tournaments and dojo activities from your Master Portal.</p>
                <div class="hero-buttons">
                    <a class="btn btn-gold" href="students.php">+ Add / Manage Students</a>
                    <a class="btn btn-dark" href="attendance.php">Mark Attendance</a>
                    <a class="btn btn-dark" href="reports.php">Generate Report</a>
                </div>
            </div>
        </section>

        <section class="stats-grid">
            <div class="stat-card"><div class="stat-icon">♙</div><span>Total Students</span><h3><?= $active_students ?></h3><small>Approved dojo members</small></div>
            <div class="stat-card"><div class="stat-icon">✓</div><span>Today's Attendance</span><h3 class="green"><?= $attendance_percent ?>%</h3><small><?= $today_present ?> present / <?= $today_total ?> records</small></div>
            <div class="stat-card"><div class="stat-icon">₹</div><span>Join Requests</span><h3 class="red"><?= $pending_requests ?></h3><small>Waiting for your review</small></div>
            <div class="stat-card"><div class="stat-icon">↗</div><span>Today's Payments</span><h3 class="gold">₹<?= number_format($today_payments, 0) ?></h3><small>Payments received today</small></div>
        </section>

        <section class="section-grid">
            <div class="card" id="profile">
                <div class="card-heading"><h3>Master Profile</h3><a href="profile.php">Edit</a></div>
                <div class="profile-header">
                    <div class="large-avatar"><?= htmlspecialchars($initials ?: 'MD') ?></div>
                    <div><h3><?= htmlspecialchars($full_name ?: 'Master') ?></h3><p>Master • <?= htmlspecialchars($master['member_id'] ?? 'KOMS') ?></p></div>
                </div>
                <div class="details-grid">
                    <div class="detail-item"><label>Full Name</label><strong><?= htmlspecialchars($full_name ?: '-') ?></strong></div>
                    <div class="detail-item"><label>Date of Birth</label><strong><?= htmlspecialchars($master['dob'] ?? '-') ?></strong></div>
                    <div class="detail-item"><label>Age</label><strong><?= $age !== null ? (int)$age . ' Years' : '-' ?></strong></div>
                    <div class="detail-item"><label>Gender</label><strong><?= htmlspecialchars(ucfirst($master['gender'] ?? '-')) ?></strong></div>
                    <div class="detail-item"><label>Phone</label><strong><?= htmlspecialchars($master['phone'] ?? '-') ?></strong></div>
                    <div class="detail-item"><label>Email</label><strong><?= htmlspecialchars($master['email'] ?? '-') ?></strong></div>
                </div>
            </div>

            <div class="card">
                <div class="card-heading"><h3>Dojo Details</h3><a href="edit_dojo.php">Settings</a></div>
                <?php if ($dojo): ?>
                    <div class="details-grid">
                        <div class="detail-item full"><label>Dojo Name</label><strong><?= htmlspecialchars($dojo['name']) ?></strong></div>
                        <div class="detail-item full"><label>Location</label><strong><?= htmlspecialchars($dojo['location']) ?></strong></div>
                        <div class="detail-item"><label>Status</label><strong class="gold-text"><?= htmlspecialchars(ucfirst($dojo['status'])) ?></strong></div>
                        <div class="detail-item"><label>Contact</label><strong><?= htmlspecialchars($dojo['contact_number'] ?? $dojo['phone'] ?? '-') ?></strong></div>
                        <div class="detail-item full"><label>Training Days</label><strong><?= htmlspecialchars($dojo['training_days'] ?? '-') ?></strong></div>
                        <div class="detail-item full"><label>Training Timings</label><strong><?= htmlspecialchars($dojo['training_timings'] ?? '-') ?></strong></div>
                    </div>
                <?php else: ?>
                    <div class="empty"><p>No dojo is registered for this Master account.</p><a class="full-button link-button" href="register_dojo.php">Register My Dojo</a></div>
                <?php endif; ?>
            </div>
        </section>

        <section class="section card" id="students">
            <div class="card-heading"><h3>Recent Students</h3><a href="students.php">View All</a></div>
            <?php if ($recentStudents): ?>
                <div class="student-list" id="studentList">
                    <?php foreach ($recentStudents as $student): ?>
                        <div class="student-row">
                            <div class="row-person"><div class="small-avatar"><?= htmlspecialchars(strtoupper(substr($student['first_name'],0,1) . substr($student['last_name'],0,1))) ?></div><div><strong><?= htmlspecialchars($student['first_name'].' '.$student['last_name']) ?></strong><span><?= htmlspecialchars($student['member_id'] ?? $student['email']) ?></span></div></div>
                            <span class="status active-status">Active</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?><div class="empty">No approved students yet.</div><?php endif; ?>
        </section>

        <section class="three-grid">
            <div class="card" id="attendance">
                <div class="card-heading"><h3>Attendance</h3><a href="attendance.php">Manage</a></div>
                <div class="attendance-total"><div class="attendance-circle" style="--attendance:<?= $attendance_percent ?>%"><span><?= $attendance_percent ?>%</span></div><div><strong>Today's Attendance</strong><p><?= $today_present ?> present out of <?= $today_total ?> recorded</p></div></div>
                <a class="full-button link-button" href="attendance.php">Open Attendance</a>
            </div>

            <div class="card" id="fees">
                <div class="card-heading"><h3>Fees</h3><a href="fees.php">Manage</a></div>
                <div class="fee-total"><span>Today's Collection</span><strong>₹<?= number_format($today_payments, 0) ?></strong></div>
                <a class="full-button link-button" href="fees.php">Open Fees & Payments</a>
            </div>

            <div class="card" id="promotions">
                <div class="card-heading"><h3>Belt Promotions</h3><a href="grading.php">Manage</a></div>
                <div class="belt-master"><div class="belt-circle">2D</div><div><strong><?= htmlspecialchars($full_name ?: 'Master') ?></strong><span>Current role</span><b>Master</b></div></div>
                <a class="full-button link-button" href="grading.php">Open Grading</a>
            </div>
        </section>

        <section class="section-grid">
            <div class="card" id="tournament">
                <div class="card-heading"><h3>Tournament Management</h3><a href="tournaments.php">Open</a></div>
                <div class="tournament-banner"><small>MASTER MODULE</small><h2>Competitions & Registrations</h2><p>Manage tournaments and student participation.</p></div>
            </div>

            <div class="card" id="events">
                <div class="card-heading"><h3>Events</h3><a href="events.php">Open</a></div>
                <div class="empty"><p>Manage dojo events, camps and schedules.</p><a class="full-button link-button" href="events.php">Open Events Management</a></div>
            </div>
        </section>

        <section class="section card" id="announcements">
            <div class="card-heading"><h3>Announcements</h3><a href="announcements.php">View All</a></div>
            <?php if ($announcements): ?>
                <div class="announcement-list">
                    <?php foreach ($announcements as $item): ?><div class="announcement"><div class="announcement-icon">!</div><div><strong><?= htmlspecialchars($item['title']) ?></strong><span><?= htmlspecialchars($item['publish_date']) ?></span></div></div><?php endforeach; ?>
                </div>
            <?php else: ?><div class="empty">No active announcements.</div><?php endif; ?>
        </section>

        <section class="section card" id="settings">
            <div class="card-heading"><h3>Master Portal Modules</h3><span class="muted-text">Quick access</span></div>
            <div class="settings-grid">
                <a href="students.php"><span>♙</span><strong>Students</strong></a>
                <a href="attendance.php"><span>✓</span><strong>Attendance</strong></a>
                <a href="fees.php"><span>₹</span><strong>Fees</strong></a>
                <a href="grading.php"><span>🥋</span><strong>Grading</strong></a>
                <a href="tournaments.php"><span>🏆</span><strong>Tournaments</strong></a>
                <a href="events.php"><span>◫</span><strong>Events</strong></a>
                <a href="announcements.php"><span>!</span><strong>Announcements</strong></a>
                <a href="gallery.php"><span>▧</span><strong>Gallery</strong></a>
                <a href="certificates.php"><span>▣</span><strong>Certificates</strong></a>
                <a href="reports.php"><span>◫</span><strong>Reports</strong></a>
                <a href="inventory.php"><span>◈</span><strong>Inventory</strong></a>
                <a href="communication.php"><span>✉</span><strong>Communication</strong></a>
                <a href="profile.php"><span>◉</span><strong>Profile</strong></a>
                <a href="settings.php"><span>⚙</span><strong>Settings</strong></a>
            </div>
        </section>

        <footer class="footer">© <?= date('Y') ?> Mass Dragon Dojo · Master Portal</footer>
    </div>
</main>

<!-- Mobile Bottom Navigation Bar (Sensei One-Handed Smartphone Control) -->
<nav style="display:none;position:fixed;bottom:0;left:0;right:0;height:62px;background:rgba(13,15,18,0.95);backdrop-filter:blur(18px);border-top:1px solid rgba(217,183,93,0.2);z-index:920;justify-content:space-around;align-items:center;padding:0 6px;" class="master-bottom-nav">
    <a href="index.php" style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;color:#f0d584;text-decoration:none;font-size:10px;font-weight:700;">
        <span style="font-size:18px;">⌂</span>Dashboard
    </a>
    <a href="students.php" style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;color:#949aa3;text-decoration:none;font-size:10px;font-weight:600;">
        <span style="font-size:18px;">♙</span>Students
    </a>
    <a href="attendance.php" style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;color:#949aa3;text-decoration:none;font-size:10px;font-weight:600;">
        <span style="font-size:18px;">✓</span>Attendance
    </a>
    <a href="password_requests.php" style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;color:#949aa3;text-decoration:none;font-size:10px;font-weight:600;">
        <span style="font-size:18px;">🔑</span>Requests
    </a>
    <a href="profile.php" style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;color:#949aa3;text-decoration:none;font-size:10px;font-weight:600;">
        <span style="font-size:18px;">◉</span>Profile
    </a>
</nav>

<style>
@media(max-width:850px){
    .master-bottom-nav{display:flex !important;}
    .content{padding-bottom:80px !important;}
}
</style>

<div class="toast" id="toast"></div>
<script src="../js/master.js?v=2"></script>
</body>
</html>