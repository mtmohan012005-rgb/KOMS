<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('master');

$master_id = (int)($_SESSION['user_id'] ?? 0);

$stmt = $pdo->prepare("SELECT id, name FROM dojos WHERE master_id = ? AND status = 'approved' LIMIT 1");
$stmt->execute([$master_id]);
$dojo = $stmt->fetch();

if (!$dojo) {
    $_SESSION['error_msg'] = 'You need an approved dojo to view reports.';
    redirect('/master/dashboard.php');
}

$dojo_id = (int)$dojo['id'];
$today = date('Y-m-d');
$month_start = date('Y-m-01');
$month_end = date('Y-m-t');

$scalar = function (string $sql, array $params = []) use ($pdo): int {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
};

$student_count = $scalar("SELECT COUNT(*) FROM dojo_memberships WHERE dojo_id = ? AND status = 'approved'", [$dojo_id]);
$attendance_sessions = $scalar("SELECT COUNT(*) FROM attendance_sessions WHERE dojo_id = ?", [$dojo_id]);
$today_sessions = $scalar("SELECT COUNT(*) FROM attendance_sessions WHERE dojo_id = ? AND session_date = ?", [$dojo_id, $today]);
$grading_records = $scalar("SELECT COUNT(*) FROM grading_history WHERE dojo_id = ?", [$dojo_id]);
$tournaments_count = $scalar("SELECT COUNT(*) FROM tournaments WHERE status IN ('upcoming','ongoing')");
$active_announcements = $scalar("SELECT COUNT(*) FROM announcements WHERE dojo_id = ? AND status = 'active' AND publish_date <= ? AND (expiry_date IS NULL OR expiry_date >= ?)", [$dojo_id, $today, $today]);

$payment_stmt = $pdo->prepare("SELECT COALESCE(SUM(p.amount),0) FROM payments p JOIN fee_records fr ON p.fee_record_id = fr.id JOIN dojo_memberships dm ON fr.student_id = dm.student_id WHERE dm.dojo_id = ? AND DATE(p.payment_date) = ?");
$payment_stmt->execute([$dojo_id, $today]);
$today_collected = (float)$payment_stmt->fetchColumn();

$month_payment_stmt = $pdo->prepare("SELECT COALESCE(SUM(p.amount),0) FROM payments p JOIN fee_records fr ON p.fee_record_id = fr.id JOIN dojo_memberships dm ON fr.student_id = dm.student_id WHERE dm.dojo_id = ? AND DATE(p.payment_date) BETWEEN ? AND ?");
$month_payment_stmt->execute([$dojo_id, $month_start, $month_end]);
$month_collected = (float)$month_payment_stmt->fetchColumn();

$present_today = $scalar("SELECT COUNT(*) FROM attendance_entries ae JOIN attendance_sessions s ON ae.session_id = s.id WHERE s.dojo_id = ? AND s.session_date = ? AND ae.status = 'present'", [$dojo_id, $today]);
$attendance_marked_today = $scalar("SELECT COUNT(*) FROM attendance_entries ae JOIN attendance_sessions s ON ae.session_id = s.id WHERE s.dojo_id = ? AND s.session_date = ?", [$dojo_id, $today]);
$attendance_rate = $attendance_marked_today > 0 ? round(($present_today / $attendance_marked_today) * 100) : 0;

$belt_stmt = $pdo->prepare("SELECT COALESCE((SELECT gh.new_belt FROM grading_history gh WHERE gh.student_id = u.id AND gh.dojo_id = ? ORDER BY gh.exam_date DESC, gh.id DESC LIMIT 1), 'White') AS belt, COUNT(*) AS total FROM dojo_memberships m JOIN users u ON m.student_id = u.id WHERE m.dojo_id = ? AND m.status = 'approved' GROUP BY belt ORDER BY total DESC");
$belt_stmt->execute([$dojo_id, $dojo_id]);
$belt_summary = $belt_stmt->fetchAll();

$recent_payments_stmt = $pdo->prepare("SELECT p.amount, p.payment_date, u.first_name, u.last_name, u.member_id FROM payments p JOIN fee_records fr ON p.fee_record_id = fr.id JOIN users u ON fr.student_id = u.id JOIN dojo_memberships dm ON dm.student_id = u.id AND dm.dojo_id = ? ORDER BY p.payment_date DESC, p.id DESC LIMIT 8");
$recent_payments_stmt->execute([$dojo_id]);
$recent_payments = $recent_payments_stmt->fetchAll();

$recent_grading_stmt = $pdo->prepare("SELECT g.exam_date, g.new_belt, u.first_name, u.last_name, u.member_id FROM grading_history g JOIN users u ON g.student_id = u.id WHERE g.dojo_id = ? ORDER BY g.exam_date DESC, g.id DESC LIMIT 8");
$recent_grading_stmt->execute([$dojo_id]);
$recent_grading = $recent_grading_stmt->fetchAll();

$page_title = 'Master Reports';
require_once '../includes/header.php';
?>

<style>
.report-wrap{max-width:1200px;margin:1.5rem auto}.report-hero{border-radius:24px;padding:1.8rem 2rem;color:#fff;background:linear-gradient(135deg,#080808,#202020 58%,#4e0909);box-shadow:0 22px 55px rgba(0,0,0,.15)}
.report-kicker{text-transform:uppercase;letter-spacing:.14em;font-size:.72rem;font-weight:800;color:#ffd25f}.report-title{font-size:clamp(1.7rem,4vw,2.7rem);font-weight:900;margin:.3rem 0}.report-sub{color:rgba(255,255,255,.72)}
.metric{background:#fff;border:0;border-radius:18px;padding:1.1rem 1.2rem;box-shadow:0 12px 34px rgba(17,24,39,.07);height:100%}.metric-label{font-size:.7rem;text-transform:uppercase;letter-spacing:.08em;color:#858585;font-weight:800}.metric-value{font-size:1.75rem;font-weight:900;margin-top:.2rem}.metric-note{font-size:.78rem;color:#888}
.panel{border:0;border-radius:20px;overflow:hidden;box-shadow:0 14px 38px rgba(17,24,39,.08)}.panel .card-header{background:#fff;border:0;padding:1.15rem 1.3rem}.panel .card-body{background:#fff}.report-table th{font-size:.74rem;text-transform:uppercase;letter-spacing:.05em;color:#777;white-space:nowrap}.report-table td{padding-top:.85rem;padding-bottom:.85rem}.member{font-weight:800}.muted{font-size:.76rem;color:#888}.belt-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.72rem .2rem;border-bottom:1px solid #f0f0f0}.belt-row:last-child{border-bottom:0}.bar{height:8px;border-radius:999px;background:#ededed;overflow:hidden;flex:1}.bar>span{display:block;height:100%;background:#222;border-radius:inherit}.print-btn{border-radius:12px}
@media(max-width:768px){.report-wrap{margin:1rem auto}.report-hero{padding:1.35rem}.table-wrap{overflow:auto}.report-table{min-width:680px}}
@media print{.no-print{display:none!important}.report-wrap{max-width:none;margin:0}.report-hero{box-shadow:none;color:#000;background:#eee}.report-sub{color:#333}}
</style>

<div class="report-wrap">
    <section class="report-hero mb-4">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="report-kicker">Master Control • Analytics</div>
                <div class="report-title">Dojo Reports</div>
                <p class="report-sub mb-0">Live operational summary for <?= htmlspecialchars($dojo['name']) ?>.</p>
            </div>
            <button type="button" class="btn btn-light print-btn no-print" onclick="window.print()"><i class="fas fa-print me-2"></i>Print Report</button>
        </div>
    </section>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3"><div class="metric"><div class="metric-label">Students</div><div class="metric-value"><?= $student_count ?></div><div class="metric-note">Approved dojo members</div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="metric"><div class="metric-label">Attendance Rate</div><div class="metric-value"><?= $attendance_rate ?>%</div><div class="metric-note"><?= $present_today ?> present of <?= $attendance_marked_today ?> marked today</div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="metric"><div class="metric-label">Today Collected</div><div class="metric-value">₹<?= number_format($today_collected,2) ?></div><div class="metric-note">Payments received today</div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="metric"><div class="metric-label">This Month</div><div class="metric-value">₹<?= number_format($month_collected,2) ?></div><div class="metric-note"><?= date('M 1', strtotime($month_start)) ?> – <?= date('M j', strtotime($month_end)) ?></div></div></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card panel mb-4">
                <div class="card-header d-flex justify-content-between align-items-center"><div><div class="small text-muted fw-bold text-uppercase">Finance</div><h5 class="mb-0 mt-1">Recent Payments</h5></div><a href="fees.php" class="btn btn-sm btn-outline-dark no-print">Fees</a></div>
                <div class="card-body p-0 table-wrap"><table class="table table-hover align-middle mb-0 report-table"><thead class="table-light"><tr><th>Student</th><th>KOMS ID</th><th>Date</th><th class="text-end">Amount</th></tr></thead><tbody>
                <?php foreach ($recent_payments as $p): ?><tr><td><div class="member"><?= htmlspecialchars($p['first_name'].' '.$p['last_name']) ?></div></td><td class="muted"><?= htmlspecialchars($p['member_id'] ?: '-') ?></td><td><?= date('M j, Y', strtotime($p['payment_date'])) ?></td><td class="text-end fw-bold">₹<?= number_format((float)$p['amount'],2) ?></td></tr><?php endforeach; ?>
                <?php if (!$recent_payments): ?><tr><td colspan="4" class="text-center py-5 text-muted">No payment records yet.</td></tr><?php endif; ?></tbody></table></div>
            </div>

            <div class="card panel">
                <div class="card-header d-flex justify-content-between align-items-center"><div><div class="small text-muted fw-bold text-uppercase">Progression</div><h5 class="mb-0 mt-1">Recent Grading</h5></div><a href="grading.php" class="btn btn-sm btn-outline-dark no-print">Grading</a></div>
                <div class="card-body p-0 table-wrap"><table class="table table-hover align-middle mb-0 report-table"><thead class="table-light"><tr><th>Student</th><th>KOMS ID</th><th>Date</th><th>New Belt</th></tr></thead><tbody>
                <?php foreach ($recent_grading as $g): ?><tr><td><div class="member"><?= htmlspecialchars($g['first_name'].' '.$g['last_name']) ?></div></td><td class="muted"><?= htmlspecialchars($g['member_id'] ?: '-') ?></td><td><?= date('M j, Y', strtotime($g['exam_date'])) ?></td><td><span class="badge bg-dark"><?= htmlspecialchars($g['new_belt']) ?></span></td></tr><?php endforeach; ?>
                <?php if (!$recent_grading): ?><tr><td colspan="4" class="text-center py-5 text-muted">No grading records yet.</td></tr><?php endif; ?></tbody></table></div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card panel mb-4">
                <div class="card-header"><div class="small text-muted fw-bold text-uppercase">Belt Distribution</div><h5 class="mb-0 mt-1">Current Student Belts</h5></div>
                <div class="card-body">
                    <?php $max_belt = $belt_summary ? max(array_column($belt_summary, 'total')) : 1; ?>
                    <?php foreach ($belt_summary as $b): $pct = max(4, round(((int)$b['total'] / $max_belt) * 100)); ?>
                        <div class="belt-row"><div style="min-width:150px;font-weight:700"><?= htmlspecialchars($b['belt']) ?></div><div class="bar"><span style="width:<?= $pct ?>%"></span></div><strong><?= (int)$b['total'] ?></strong></div>
                    <?php endforeach; ?>
                    <?php if (!$belt_summary): ?><div class="text-muted text-center py-4">No student belt data yet.</div><?php endif; ?>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-6"><div class="metric"><div class="metric-label">Sessions</div><div class="metric-value"><?= $attendance_sessions ?></div><div class="metric-note"><?= $today_sessions ?> today</div></div></div>
                <div class="col-6"><div class="metric"><div class="metric-label">Grading</div><div class="metric-value"><?= $grading_records ?></div><div class="metric-note">Total records</div></div></div>
                <div class="col-6"><div class="metric"><div class="metric-label">Tournaments</div><div class="metric-value"><?= $tournaments_count ?></div><div class="metric-note">Upcoming / ongoing</div></div></div>
                <div class="col-6"><div class="metric"><div class="metric-label">Announcements</div><div class="metric-value"><?= $active_announcements ?></div><div class="metric-note">Currently active</div></div></div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
