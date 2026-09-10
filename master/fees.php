<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('master');

$master_id = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT id, name, status FROM dojos WHERE master_id = ? LIMIT 1");
$stmt->execute([$master_id]);
$dojo = $stmt->fetch();

if (!$dojo || $dojo['status'] !== 'approved') {
    $_SESSION['info_msg'] = 'An approved dojo is required to manage fees and payments.';
    redirect('/master/dashboard.php');
}

$dojo_id = (int)$dojo['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_structure'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_msg'] = 'Invalid form submission.';
    } else {
        $name = sanitize_input($_POST['fee_name'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);
        $freq = sanitize_input($_POST['frequency'] ?? 'monthly');
        $eff_from = sanitize_input($_POST['effective_from'] ?? '');
        $allowed_freq = ['monthly', 'quarterly', 'yearly', 'one-time'];

        if ($name === '' || $amount <= 0 || !in_array($freq, $allowed_freq, true) || !$eff_from) {
            $_SESSION['error_msg'] = 'Please enter a valid fee name, amount, frequency and effective date.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO fee_structures (dojo_id, fee_name, amount, frequency, effective_from) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$dojo_id, $name, $amount, $freq, $eff_from])) {
                $structure_id = (int)$pdo->lastInsertId();

                if (strtotime($eff_from) <= time()) {
                    $current_month = date('Y-m-01');
                    $due_date = date('Y-m-15');
                    $stu_stmt = $pdo->prepare("SELECT student_id FROM dojo_memberships WHERE dojo_id = ? AND status = 'approved'");
                    $stu_stmt->execute([$dojo_id]);
                    $students = $stu_stmt->fetchAll();
                    $insert_record = $pdo->prepare("INSERT IGNORE INTO fee_records (student_id, fee_structure_id, billing_month, amount_due, due_date) VALUES (?, ?, ?, ?, ?)");
                    foreach ($students as $stu) {
                        $insert_record->execute([(int)$stu['student_id'], $structure_id, $current_month, $amount, $due_date]);
                    }
                }

                try {
                    log_audit_action($pdo, $master_id, 'CREATE', 'fee_structures', $structure_id, "Created fee structure: $name");
                } catch (Throwable $audit_error) {
                    error_log('KOMS audit log failed while creating fee structure: ' . $audit_error->getMessage());
                }
                $_SESSION['success_msg'] = "Fee structure created successfully. Current eligible students were billed automatically when applicable.";
            } else {
                $_SESSION['error_msg'] = 'Failed to create the fee structure.';
            }
        }
    }
    redirect('/master/fees.php');
}

// Keep pending records visually accurate as dates pass.
$pdo->prepare("UPDATE fee_records r JOIN fee_structures s ON r.fee_structure_id = s.id SET r.status = 'overdue' WHERE s.dojo_id = ? AND r.status = 'pending' AND r.due_date < CURDATE()")->execute([$dojo_id]);

$page_title = 'Fees & Payments';
require_once '../includes/header.php';

$stmt = $pdo->prepare("SELECT * FROM fee_structures WHERE dojo_id = ? ORDER BY effective_from DESC, id DESC");
$stmt->execute([$dojo_id]);
$structures = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM dojo_memberships WHERE dojo_id = ? AND status = 'approved'");
$stmt->execute([$dojo_id]);
$active_students = (int)$stmt->fetchColumn();

// Outstanding is the actual remaining balance, not the original billed amount.
$stmt = $pdo->prepare("SELECT COALESCE(SUM(GREATEST(r.amount_due - COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.fee_record_id = r.id), 0), 0)), 0) FROM fee_records r JOIN fee_structures s ON r.fee_structure_id = s.id WHERE s.dojo_id = ? AND r.status IN ('pending','overdue','partially_paid')");
$stmt->execute([$dojo_id]);
$outstanding_total = (float)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(p.amount), 0) FROM payments p JOIN fee_records r ON p.fee_record_id = r.id JOIN fee_structures s ON r.fee_structure_id = s.id WHERE s.dojo_id = ? AND p.payment_date = CURDATE()");
$stmt->execute([$dojo_id]);
$today_collected = (float)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM fee_records r JOIN fee_structures s ON r.fee_structure_id = s.id WHERE s.dojo_id = ? AND r.status = 'paid'");
$stmt->execute([$dojo_id]);
$paid_records = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT r.*, s.fee_name, u.first_name, u.last_name, u.member_id,
    COALESCE((SELECT SUM(p2.amount) FROM payments p2 WHERE p2.fee_record_id = r.id), 0) AS paid_amount
    FROM fee_records r
    JOIN fee_structures s ON r.fee_structure_id = s.id
    JOIN users u ON r.student_id = u.id
    WHERE s.dojo_id = ? AND r.status IN ('pending', 'overdue', 'partially_paid')
    ORDER BY r.due_date ASC, u.first_name ASC, u.last_name ASC");
$stmt->execute([$dojo_id]);
$pending_records = $stmt->fetchAll();
?>

<style>
    .fees-wrap { max-width: 1220px; margin: 1.5rem auto; }
    .fees-hero { border-radius: 24px; padding: 1.75rem 2rem; color:#fff; background:linear-gradient(135deg,#070707,#1b1b1b 58%,#4b0909); box-shadow:0 22px 50px rgba(0,0,0,.15); }
    .fees-kicker { color:#f4c95d; text-transform:uppercase; letter-spacing:.13em; font-size:.72rem; font-weight:800; }
    .fees-title { font-size:clamp(1.7rem,4vw,2.7rem); font-weight:900; margin:.3rem 0; }
    .stat-card, .panel { border:0; border-radius:20px; background:#fff; box-shadow:0 14px 35px rgba(17,24,39,.08); }
    .stat-card { padding:1.2rem; height:100%; }
    .stat-label { color:#777; font-size:.75rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; }
    .stat-value { font-weight:900; font-size:1.65rem; margin-top:.2rem; }
    .panel .card-header { background:#fff; border:0; padding:1.15rem 1.3rem; }
    .panel .card-body { padding:1.3rem; }
    .form-control,.form-select { border-radius:12px; padding:.72rem .85rem; }
    .fee-badge { border-radius:999px; padding:.4rem .7rem; font-size:.72rem; font-weight:800; }
    .member-id{display:inline-flex;border-radius:999px;padding:.25rem .55rem;background:#fff4cf;color:#705200;font-size:.68rem;font-weight:800;margin-top:.25rem}
</style>

<div class="fees-wrap">
    <section class="fees-hero mb-4">
        <div class="fees-kicker">Master Control • Finance</div>
        <div class="fees-title">Fees & Payments</div>
        <p class="mb-0" style="color:rgba(255,255,255,.72);">Create fee structures, monitor outstanding balances and record student payments for <?= htmlspecialchars($dojo['name']) ?>.</p>
    </section>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-label">Active Students</div><div class="stat-value"><?= number_format($active_students) ?></div></div></div>
        <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-label">Outstanding</div><div class="stat-value">₹<?= number_format($outstanding_total, 2) ?></div></div></div>
        <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-label">Collected Today</div><div class="stat-value">₹<?= number_format($today_collected, 2) ?></div></div></div>
        <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-label">Paid Records</div><div class="stat-value"><?= number_format($paid_records) ?></div></div></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card panel">
                <div class="card-header"><div class="text-uppercase text-muted" style="font-size:.72rem;font-weight:800;letter-spacing:.08em;">Billing Setup</div><h5 class="mb-0 mt-1">Create Fee Structure</h5></div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                        <input type="hidden" name="create_structure" value="1">
                        <div class="mb-3"><label class="form-label fw-bold">Fee Name</label><input type="text" name="fee_name" class="form-control" placeholder="Monthly Training Fee" required></div>
                        <div class="mb-3"><label class="form-label fw-bold">Amount (₹)</label><input type="number" min="0.01" step="0.01" name="amount" class="form-control" placeholder="1000" required></div>
                        <div class="mb-3"><label class="form-label fw-bold">Frequency</label><select name="frequency" class="form-select" required><option value="monthly">Monthly</option><option value="quarterly">Quarterly</option><option value="yearly">Yearly</option><option value="one-time">One-Time</option></select></div>
                        <div class="mb-3"><label class="form-label fw-bold">Effective From</label><input type="date" name="effective_from" class="form-control" value="<?= date('Y-m-d') ?>" required><div class="form-text">A structure effective today can automatically create the current billing records.</div></div>
                        <button type="submit" class="btn btn-dark w-100"><i class="fas fa-plus-circle me-2"></i>Create Fee Structure</button>
                    </form>
                </div>
            </div>

            <div class="card panel mt-4">
                <div class="card-header"><div class="text-uppercase text-muted" style="font-size:.72rem;font-weight:800;letter-spacing:.08em;">Configured Plans</div><h5 class="mb-0 mt-1">Fee Structures</h5></div>
                <div class="card-body p-0">
                    <?php if ($structures): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($structures as $st): ?>
                                <div class="list-group-item py-3">
                                    <div class="d-flex justify-content-between gap-2"><strong><?= htmlspecialchars($st['fee_name']) ?></strong><span class="fee-badge bg-light text-dark"><?= htmlspecialchars(ucfirst($st['frequency'])) ?></span></div>
                                    <div class="small text-muted mt-1">₹<?= number_format((float)$st['amount'], 2) ?> • Effective <?= date('M j, Y', strtotime($st['effective_from'])) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="p-4 text-center text-muted">No fee structures created yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card panel">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div><div class="text-uppercase text-muted" style="font-size:.72rem;font-weight:800;letter-spacing:.08em;">Collection Queue</div><h5 class="mb-0 mt-1">Outstanding Fee Records</h5></div>
                    <a href="index.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Master Dashboard</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light"><tr><th>Student</th><th>KOMS ID</th><th>Fee</th><th>Billing</th><th>Due</th><th>Remaining</th><th>Status</th><th></th></tr></thead>
                            <tbody>
                            <?php foreach ($pending_records as $rec): ?>
                                <?php $remaining = max(0, (float)$rec['amount_due'] - (float)$rec['paid_amount']); ?>
                                <tr>
                                    <td><div class="fw-bold"><?= htmlspecialchars($rec['first_name'].' '.$rec['last_name']) ?></div><small class="text-muted">Student #<?= (int)$rec['student_id'] ?></small></td>
                                    <td><span class="member-id"><?= htmlspecialchars($rec['member_id'] ?: 'Not assigned') ?></span></td>
                                    <td><?= htmlspecialchars($rec['fee_name']) ?></td>
                                    <td><?= date('M Y', strtotime($rec['billing_month'])) ?></td>
                                    <td class="<?= strtotime($rec['due_date']) < time() ? 'text-danger fw-bold' : '' ?>"><?= date('M j, Y', strtotime($rec['due_date'])) ?></td>
                                    <td><strong>₹<?= number_format($remaining,2) ?></strong><div class="small text-muted">paid ₹<?= number_format((float)$rec['paid_amount'],2) ?></div></td>
                                    <td><span class="fee-badge <?= $rec['status']==='overdue' ? 'bg-danger text-white' : 'bg-warning text-dark' ?>"><?= htmlspecialchars(ucwords(str_replace('_',' ',$rec['status']))) ?></span></td>
                                    <td><a href="record_payment.php?record_id=<?= (int)$rec['id'] ?>" class="btn btn-sm btn-success">Payment</a></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$pending_records): ?><tr><td colspan="8" class="text-center py-5 text-muted"><i class="fas fa-circle-check fa-2x d-block mb-2"></i>No outstanding fees found.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-md-6"><a class="card panel text-decoration-none h-100" href="record_payment.php"><div class="card-body"><div class="text-muted small text-uppercase fw-bold">Quick Action</div><h6 class="mt-2 mb-1 text-dark"><i class="fas fa-money-bill-wave me-2"></i>Record Payment</h6><p class="text-muted small mb-0">Add a payment against an existing fee record.</p></div></a></div>
                <div class="col-md-6"><a class="card panel text-decoration-none h-100" href="students.php"><div class="card-body"><div class="text-muted small text-uppercase fw-bold">Quick Action</div><h6 class="mt-2 mb-1 text-dark"><i class="fas fa-users me-2"></i>View Students</h6><p class="text-muted small mb-0">Review active dojo members before billing follow-up.</p></div></a></div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
