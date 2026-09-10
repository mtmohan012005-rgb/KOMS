<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('master');

$page_title = 'Master Dashboard';

$stmt = $pdo->prepare("SELECT id, name, status FROM dojos WHERE master_id = ? LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$my_dojo = $stmt->fetch();

require_once '../includes/header.php';

if (!$my_dojo): ?>
    <div class="master-page">
        <div class="master-empty">
            <div class="empty-icon"><i class="fas fa-torii-gate"></i></div>
            <div class="master-kicker">Mass Dragon Dojo • KOMS</div>
            <h1>Set up your dojo</h1>
            <p>Your Master account is ready. Register your dojo to start managing students, attendance, fees and grading.</p>
            <a href="<?= APP_URL ?>/master/register_dojo.php" class="master-btn primary"><i class="fas fa-plus"></i> Register My Dojo</a>
        </div>
    </div>
    <?php require_once '../includes/footer.php'; exit(); ?>
<?php endif;

$dojo_id = (int)$my_dojo['id'];

$studentsStmt = $pdo->prepare("SELECT COUNT(*) FROM dojo_memberships WHERE dojo_id = ? AND status = 'approved'");
$studentsStmt->execute([$dojo_id]);
$active_students_count = (int)$studentsStmt->fetchColumn();

$pendingStmt = $pdo->prepare("SELECT COUNT(*) FROM dojo_memberships WHERE dojo_id = ? AND status = 'pending'");
$pendingStmt->execute([$dojo_id]);
$pending_reqs_count = (int)$pendingStmt->fetchColumn();

$attendanceStmt = $pdo->prepare("SELECT COUNT(*) FROM attendance_entries ae JOIN attendance_sessions ats ON ae.session_id = ats.id WHERE ats.dojo_id = ? AND DATE(ats.session_date) = CURDATE() AND ae.status = 'present'");
$attendanceStmt->execute([$dojo_id]);
$today_present = (int)$attendanceStmt->fetchColumn();

$feeStmt = $pdo->prepare("SELECT COALESCE(SUM(p.amount), 0) FROM payments p JOIN fee_records fr ON p.fee_record_id = fr.id JOIN fee_structures fs ON fr.fee_structure_id = fs.id WHERE fs.dojo_id = ? AND DATE(p.payment_date) = CURDATE()");
try {
    $feeStmt->execute([$dojo_id]);
    $today_payments = (float)$feeStmt->fetchColumn();
} catch (PDOException $e) {
    $today_payments = 0;
}

$recentRequestsStmt = $pdo->prepare("SELECT m.id, u.first_name, u.last_name, u.email, m.created_at FROM dojo_memberships m JOIN users u ON m.student_id = u.id WHERE m.dojo_id = ? AND m.status = 'pending' ORDER BY m.created_at DESC LIMIT 5");
$recentRequestsStmt->execute([$dojo_id]);
$recentRequests = $recentRequestsStmt->fetchAll();

$recentStudentsStmt = $pdo->prepare("SELECT u.first_name, u.last_name, u.email, m.joined_at FROM dojo_memberships m JOIN users u ON m.student_id = u.id WHERE m.dojo_id = ? AND m.status = 'approved' ORDER BY m.joined_at DESC LIMIT 5");
$recentStudentsStmt->execute([$dojo_id]);
$recentStudents = $recentStudentsStmt->fetchAll();
?>

<style>
    .master-page{--m-bg:#070707;--m-card:rgba(15,15,15,.94);--m-border:rgba(255,255,255,.08);--m-muted:#777;--m-text:#f4f4f4;--m-red:#df241b;--m-gold:#ffd21a;margin:-12px -12px -30px;padding:24px 12px 44px;min-height:calc(100vh - 70px);background:radial-gradient(circle at 5% 0%,rgba(205,0,0,.15),transparent 24%),radial-gradient(circle at 95% 0%,rgba(255,196,0,.07),transparent 22%),#070707}.master-shell{max-width:1400px;margin:auto}.master-top{display:flex;justify-content:space-between;align-items:center;gap:18px;flex-wrap:wrap;margin-bottom:22px}.master-kicker{color:var(--m-gold);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:1.8px}.master-title{color:#fff;font-size:30px;font-weight:800;margin:4px 0 4px}.master-subtitle{color:#888;font-size:12px;margin:0}.dojo-status{display:inline-flex;align-items:center;gap:7px;padding:9px 12px;border-radius:999px;font-size:10px;font-weight:700}.dojo-status.approved{color:#6de59a;background:rgba(51,190,105,.1);border:1px solid rgba(51,190,105,.2)}.dojo-status.pending{color:#ffd05a;background:rgba(255,184,0,.1);border:1px solid rgba(255,184,0,.2)}.master-actions{display:flex;gap:9px}.master-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:10px 14px;border-radius:9px;text-decoration:none;font-size:11px;font-weight:700;border:1px solid var(--m-border);color:#ddd;background:rgba(255,255,255,.035)}.master-btn:hover{color:#fff;border-color:rgba(255,210,26,.3)}.master-btn.primary{background:linear-gradient(90deg,#a90a0a,#e12a20);border-color:transparent}.master-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:18px}.m-stat{padding:18px;border:1px solid var(--m-border);border-radius:15px;background:linear-gradient(145deg,#171717,#0b0b0b);box-shadow:0 14px 30px rgba(0,0,0,.22)}.m-icon{width:40px;height:40px;border-radius:11px;display:grid;place-items:center;background:rgba(255,255,255,.055);color:var(--m-gold);margin-bottom:13px}.m-stat:nth-child(2) .m-icon{color:#ff7168;background:rgba(223,36,27,.1)}.m-stat:nth-child(3) .m-icon{color:#65d98c;background:rgba(54,190,105,.1)}.m-stat:nth-child(4) .m-icon{color:#72baff;background:rgba(61,143,255,.1)}.m-label{font-size:10px;color:#777;text-transform:uppercase;letter-spacing:.9px}.m-value{font-size:28px;line-height:1;color:#fff;font-weight:800;margin-top:7px}.m-note{font-size:9px;color:#555;margin-top:7px}.m-grid{display:grid;grid-template-columns:1.35fr .85fr;gap:17px;margin-bottom:17px}.m-panel{background:var(--m-card);border:1px solid var(--m-border);border-radius:15px;overflow:hidden}.m-head{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:16px 18px;border-bottom:1px solid var(--m-border)}.m-head h3{margin:0;color:#fff;font-size:14px}.m-head p{margin:3px 0 0;color:#666;font-size:9px}.m-link{color:#aaa;text-decoration:none;font-size:10px}.m-link:hover{color:#fff}.m-quick{padding:17px;display:grid;grid-template-columns:repeat(2,1fr);gap:10px}.m-quick a{display:flex;align-items:center;gap:10px;min-height:59px;padding:11px;border-radius:11px;background:rgba(255,255,255,.035);border:1px solid rgba(255,255,255,.055);text-decoration:none;color:#ddd}.m-quick a:hover{background:rgba(255,255,255,.06);border-color:rgba(255,210,26,.22);color:#fff}.m-quick i{width:34px;height:34px;display:grid;place-items:center;border-radius:9px;color:#ff7067;background:rgba(210,20,20,.11)}.m-quick strong{font-size:11px;display:block}.m-quick span{font-size:9px;color:#666;display:block;margin-top:3px}.dojo-health{padding:18px}.health-ring{display:flex;align-items:center;gap:14px;padding:15px;border-radius:12px;background:rgba(255,255,255,.035)}.health-ring .circle{width:56px;height:56px;border-radius:50%;display:grid;place-items:center;border:3px solid rgba(72,205,119,.45);color:#73e49b;font-size:11px;font-weight:800}.health-title{color:#eee;font-size:12px;font-weight:700}.health-text{color:#686868;font-size:9px;margin-top:4px;line-height:1.5}.health-meta{display:grid;grid-template-columns:1fr 1fr;gap:9px;margin-top:10px}.health-meta div{padding:11px;border-radius:10px;background:rgba(255,255,255,.03)}.health-meta span{display:block;color:#666;font-size:9px}.health-meta strong{display:block;color:#ddd;font-size:12px;margin-top:4px}.m-table{width:100%;border-collapse:collapse;min-width:560px}.m-table th{padding:12px 15px;color:#5f5f5f;font-size:9px;text-transform:uppercase;letter-spacing:.8px;text-align:left;border-bottom:1px solid var(--m-border)}.m-table td{padding:12px 15px;color:#ccc;font-size:10px;border-bottom:1px solid rgba(255,255,255,.045)}.m-table tr:last-child td{border:0}.person{display:flex;align-items:center;gap:9px}.person-avatar{width:30px;height:30px;border-radius:9px;display:grid;place-items:center;background:#202020;color:var(--m-gold);font-size:9px;font-weight:800}.person-name{color:#eee;font-size:10px;font-weight:700}.person-email{color:#5f5f5f;font-size:8px;margin-top:2px}.request-actions{display:flex;gap:5px}.req-btn{border:0;border-radius:6px;padding:5px 7px;font-size:9px;font-weight:700;cursor:pointer}.req-btn.ok{background:rgba(54,190,105,.12);color:#73df99}.req-btn.no{background:rgba(223,36,27,.11);color:#ff7770}.empty{padding:28px;text-align:center;color:#666;font-size:10px}.table-scroll{overflow-x:auto}.master-empty{max-width:520px;margin:10vh auto;padding:38px 25px;text-align:center;background:rgba(15,15,15,.94);border:1px solid var(--m-border);border-radius:18px}.empty-icon{width:62px;height:62px;margin:0 auto 16px;border-radius:16px;display:grid;place-items:center;background:rgba(255,210,26,.08);color:var(--m-gold);font-size:25px}.master-empty h1{color:#fff;font-size:27px;margin:5px 0 8px}.master-empty p{color:#777;font-size:12px;line-height:1.6;margin:0 0 20px}.master-empty .master-btn{display:inline-flex}@media(max-width:1050px){.master-stats{grid-template-columns:repeat(2,1fr)}.m-grid{grid-template-columns:1fr}}@media(max-width:620px){.master-page{margin:-8px -8px -24px;padding:16px 8px 32px}.master-title{font-size:24px}.master-stats{gap:9px}.m-stat{padding:14px}.m-value{font-size:24px}.m-quick{grid-template-columns:1fr}.master-actions{width:100%}.master-btn{flex:1}.dojo-status{margin-top:2px}}
</style>

<div class="master-page">
    <div class="master-shell">
        <div class="master-top">
            <div>
                <div class="master-kicker">Mass Dragon Dojo • Master Portal</div>
                <h1 class="master-title"><?= htmlspecialchars($my_dojo['name']) ?></h1>
                <p class="master-subtitle">Manage your dojo, students and daily training operations.</p>
            </div>
            <div>
                <?php if ($my_dojo['status'] === 'approved'): ?>
                    <span class="dojo-status approved"><i class="fas fa-circle-check"></i> Active Dojo</span>
                <?php else: ?>
                    <span class="dojo-status pending"><i class="fas fa-hourglass-half"></i> Pending Admin Approval</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="master-stats">
            <div class="m-stat"><div class="m-icon"><i class="fas fa-user-graduate"></i></div><div class="m-label">Active Students</div><div class="m-value"><?= $active_students_count ?></div><div class="m-note">Approved dojo members</div></div>
            <div class="m-stat"><div class="m-icon"><i class="fas fa-user-clock"></i></div><div class="m-label">Join Requests</div><div class="m-value"><?= $pending_reqs_count ?></div><div class="m-note">Waiting for your review</div></div>
            <div class="m-stat"><div class="m-icon"><i class="fas fa-calendar-check"></i></div><div class="m-label">Present Today</div><div class="m-value"><?= $today_present ?></div><div class="m-note">Attendance recorded today</div></div>
            <div class="m-stat"><div class="m-icon"><i class="fas fa-indian-rupee-sign"></i></div><div class="m-label">Today's Payments</div><div class="m-value">₹<?= number_format($today_payments, 0) ?></div><div class="m-note">Payments received today</div></div>
        </div>

        <div class="m-grid">
            <section class="m-panel">
                <div class="m-head"><div><h3>Dojo Management</h3><p>Common actions for daily operations.</p></div></div>
                <div class="m-quick">
                    <a href="students.php"><i class="fas fa-users"></i><div><strong>Manage Students</strong><span>Members and profiles</span></div></a>
                    <a href="requests.php"><i class="fas fa-user-plus"></i><div><strong>Join Requests</strong><span>Approve new students</span></div></a>
                    <a href="attendance.php"><i class="fas fa-calendar-check"></i><div><strong>Attendance</strong><span>Record today's training</span></div></a>
                    <a href="fees.php"><i class="fas fa-wallet"></i><div><strong>Fees & Payments</strong><span>Manage fee records</span></div></a>
                    <a href="grading.php"><i class="fas fa-medal"></i><div><strong>Grading & Belts</strong><span>Track student progress</span></div></a>
                    <a href="edit_dojo.php"><i class="fas fa-pen-to-square"></i><div><strong>Dojo Profile</strong><span>Update dojo information</span></div></a>
                </div>
            </section>

            <section class="m-panel">
                <div class="m-head"><div><h3>Dojo Health</h3><p>At-a-glance operating status.</p></div></div>
                <div class="dojo-health">
                    <div class="health-ring"><div class="circle"><?= $my_dojo['status'] === 'approved' ? 'LIVE' : 'WAIT' ?></div><div><div class="health-title"><?= $my_dojo['status'] === 'approved' ? 'Dojo is active' : 'Approval pending' ?></div><div class="health-text"><?= $my_dojo['status'] === 'approved' ? 'Your dojo can manage members and daily operations.' : 'Admin approval is required before full operations.' ?></div></div></div>
                    <div class="health-meta"><div><span>Students</span><strong><?= $active_students_count ?></strong></div><div><span>Pending</span><strong><?= $pending_reqs_count ?></strong></div></div>
                </div>
            </section>
        </div>

        <section class="m-panel" style="margin-bottom:17px">
            <div class="m-head"><div><h3>Student Join Requests</h3><p>Review the latest requests to join your dojo.</p></div><a href="requests.php" class="m-link">View all <i class="fas fa-arrow-right ms-1"></i></a></div>
            <?php if ($recentRequests): ?>
                <div class="table-scroll"><table class="m-table"><thead><tr><th>Student</th><th>Email</th><th>Requested</th><th>Action</th></tr></thead><tbody>
                <?php foreach ($recentRequests as $req): ?>
                    <tr><td><div class="person"><div class="person-avatar"><?= htmlspecialchars(strtoupper(substr($req['first_name'],0,1).substr($req['last_name'],0,1))) ?></div><div><div class="person-name"><?= htmlspecialchars($req['first_name'].' '.$req['last_name']) ?></div></div></div></td><td><?= htmlspecialchars($req['email']) ?></td><td><?= htmlspecialchars(date('d M Y',strtotime($req['created_at']))) ?></td><td><form method="POST" action="process_request.php" class="request-actions"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>"><input type="hidden" name="request_id" value="<?= (int)$req['id'] ?>"><button class="req-btn ok" name="action" value="approve" type="submit">Approve</button><button class="req-btn no" name="action" value="reject" type="submit">Reject</button></form></td></tr>
                <?php endforeach; ?>
                </tbody></table></div>
            <?php else: ?><div class="empty">No pending join requests.</div><?php endif; ?>
        </section>

        <section class="m-panel">
            <div class="m-head"><div><h3>Recent Students</h3><p>Latest approved members in your dojo.</p></div><a href="students.php" class="m-link">Manage students <i class="fas fa-arrow-right ms-1"></i></a></div>
            <?php if ($recentStudents): ?>
                <div class="table-scroll"><table class="m-table"><thead><tr><th>Student</th><th>Email</th><th>Joined</th></tr></thead><tbody>
                <?php foreach ($recentStudents as $student): ?>
                    <tr><td><div class="person"><div class="person-avatar"><?= htmlspecialchars(strtoupper(substr($student['first_name'],0,1).substr($student['last_name'],0,1))) ?></div><div><div class="person-name"><?= htmlspecialchars($student['first_name'].' '.$student['last_name']) ?></div></div></div></td><td><?= htmlspecialchars($student['email']) ?></td><td><?= !empty($student['joined_at']) ? htmlspecialchars(date('d M Y',strtotime($student['joined_at']))) : '—' ?></td></tr>
                <?php endforeach; ?>
                </tbody></table></div>
            <?php else: ?><div class="empty">No approved students yet.</div><?php endif; ?>
        </section>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
