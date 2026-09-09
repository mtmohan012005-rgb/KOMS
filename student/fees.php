<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('student');

$page_title = 'My Fees & Payments';
require_once '../includes/header.php';

// Fetch fee records
$stmt = $pdo->prepare("
    SELECT r.*, s.fee_name, s.frequency 
    FROM fee_records r 
    JOIN fee_structures s ON r.fee_structure_id = s.id 
    WHERE r.student_id = ? 
    ORDER BY r.due_date DESC
");
$stmt->execute([$_SESSION['user_id']]);
$records = $stmt->fetchAll();

// Fetch payment history
$stmt_pay = $pdo->prepare("
    SELECT p.*, r.billing_month, s.fee_name 
    FROM payments p 
    JOIN fee_records r ON p.fee_record_id = r.id 
    JOIN fee_structures s ON r.fee_structure_id = s.id 
    WHERE p.student_id = ? 
    ORDER BY p.payment_date DESC
");
$stmt_pay->execute([$_SESSION['user_id']]);
$payments = $stmt_pay->fetchAll();
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2>My Fees & Payments</h2>
        <p class="text-muted">View your billing history and outstanding fees.</p>
    </div>
</div>

<div class="row">
    <div class="col-md-7 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">Fee Records</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Billing Month</th>
                                <th>Description</th>
                                <th>Amount Due</th>
                                <th>Due Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $r): ?>
                            <tr>
                                <td class="fw-bold"><?= date('M Y', strtotime($r['billing_month'])) ?></td>
                                <td><?= htmlspecialchars($r['fee_name']) ?></td>
                                <td>$<?= number_format($r['amount_due'], 2) ?></td>
                                <td><?= date('M j, Y', strtotime($r['due_date'])) ?></td>
                                <td>
                                    <?php 
                                    $bg = ['pending'=>'warning', 'partially_paid'=>'info', 'paid'=>'success', 'overdue'=>'danger', 'waived'=>'secondary'][$r['status']];
                                    ?>
                                    <span class="badge bg-<?= $bg ?> text-uppercase"><?= str_replace('_', ' ', $r['status']) ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($records)): ?>
                                <tr><td colspan="5" class="text-center py-3 text-muted">No fee records found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-5 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">Payment History</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php foreach ($payments as $p): ?>
                    <div class="list-group-item py-3">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1 text-success">+$<?= number_format($p['amount'], 2) ?></h6>
                            <small class="text-muted"><?= date('M j, Y', strtotime($p['payment_date'])) ?></small>
                        </div>
                        <p class="mb-1 small text-muted">For: <?= htmlspecialchars($p['fee_name']) ?> (<?= date('M Y', strtotime($p['billing_month'])) ?>)</p>
                        <small class="text-muted d-block">Method: <?= htmlspecialchars($p['payment_method']) ?> <?= $p['transaction_ref'] ? '| Ref: ' . htmlspecialchars($p['transaction_ref']) : '' ?></small>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($payments)): ?>
                        <div class="list-group-item text-center text-muted py-4">No payments recorded yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
