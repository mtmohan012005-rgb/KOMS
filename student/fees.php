<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('student');

$user_id = (int)$_SESSION['user_id'];
$page_title = 'My Fees & Payments';

try {
    $pdo->prepare("UPDATE fee_records SET status = 'overdue' WHERE student_id = ? AND status = 'pending' AND due_date < CURRENT_DATE")
        ->execute([$user_id]);
} catch (PDOException $e) {
}

$stmt = $pdo->prepare("
    SELECT r.id, r.billing_month, r.amount_due, r.due_date, r.status,
           s.fee_name, s.frequency, COALESCE(SUM(p.amount), 0) AS amount_paid
    FROM fee_records r
    INNER JOIN fee_structures s ON r.fee_structure_id = s.id
    LEFT JOIN payments p ON p.fee_record_id = r.id
    WHERE r.student_id = ?
    GROUP BY r.id, r.billing_month, r.amount_due, r.due_date, r.status, s.fee_name, s.frequency
    ORDER BY r.due_date DESC, r.id DESC
");
$stmt->execute([$user_id]);
$records = $stmt->fetchAll();

$stmt_pay = $pdo->prepare("
    SELECT p.id, p.amount, p.payment_method, p.transaction_ref, p.payment_date, p.remarks,
           r.billing_month, s.fee_name
    FROM payments p
    INNER JOIN fee_records r ON p.fee_record_id = r.id
    INNER JOIN fee_structures s ON r.fee_structure_id = s.id
    WHERE p.student_id = ?
    ORDER BY p.payment_date DESC, p.id DESC
");
$stmt_pay->execute([$user_id]);
$payments = $stmt_pay->fetchAll();

$total_due = 0.0;
$total_paid = 0.0;
$total_outstanding = 0.0;
$overdue_amount = 0.0;
$pending_count = 0;

foreach ($records as $record) {
    $due = (float)$record['amount_due'];
    $paid = (float)$record['amount_paid'];
    $balance = max(0, $due - $paid);

    $total_due += $due;
    $total_paid += $paid;
    $total_outstanding += $balance;

    if ($record['status'] === 'overdue') {
        $overdue_amount += $balance;
    }

    if (in_array($record['status'], ['pending', 'partially_paid', 'overdue'], true) && $balance > 0) {
        $pending_count++;
    }
}

$payment_count = count($payments);

function fee_status_class(string $status): string
{
    return [
        'pending' => 'pending',
        'partially_paid' => 'partial',
        'paid' => 'paid',
        'overdue' => 'overdue',
        'waived' => 'waived',
    ][$status] ?? 'pending';
}
?>

<style>
    .fees-page {
        --fee-gold: #f4bd17;
        --fee-red: #e50914;
        --fee-bg: #070707;
        --fee-line: rgba(255,255,255,.08);
        color: #f5f5f5;
        margin: -1.5rem -.75rem 0;
        padding: 1.5rem .75rem 3rem;
        background: radial-gradient(circle at 85% 0%, rgba(229,9,20,.11), transparent 34%), radial-gradient(circle at 0% 15%, rgba(244,189,23,.08), transparent 30%), var(--fee-bg);
        min-height: 100vh;
    }
    .fees-shell { max-width: 1180px; margin: 0 auto; }
    .fees-hero {
        background: linear-gradient(135deg, rgba(244,189,23,.12), rgba(229,9,20,.08) 45%, rgba(255,255,255,.02));
        border: 1px solid rgba(244,189,23,.18); border-radius: 20px; padding: 1.75rem;
        box-shadow: 0 18px 55px rgba(0,0,0,.35); margin-bottom: 1.25rem;
    }
    .fees-kicker { display:inline-flex; align-items:center; gap:.45rem; font-size:.75rem; letter-spacing:.16em; text-transform:uppercase; color:#cfcfcf; margin-bottom:.5rem; }
    .fees-kicker i { color:var(--fee-gold); }
    .fees-hero h1 { margin:0; font-weight:900; letter-spacing:-.02em; }
    .fees-hero p { color:#a7a7a7; margin:.55rem 0 0; }
    .stat-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.9rem; margin-bottom:1.25rem; }
    .fee-stat { background:rgba(18,18,18,.92); border:1px solid var(--fee-line); border-radius:16px; padding:1rem; position:relative; overflow:hidden; }
    .fee-stat::after { content:''; position:absolute; inset:auto -25px -30px auto; width:90px; height:90px; border-radius:50%; background:rgba(244,189,23,.08); }
    .fee-stat .label { color:#8f8f8f; text-transform:uppercase; letter-spacing:.08em; font-size:.7rem; font-weight:700; }
    .fee-stat .value { margin-top:.3rem; font-size:1.55rem; font-weight:900; }
    .fee-stat .sub { color:#777; font-size:.8rem; margin-top:.15rem; }
    .fee-panel { background:rgba(18,18,18,.95); border:1px solid var(--fee-line); border-radius:18px; overflow:hidden; box-shadow:0 12px 40px rgba(0,0,0,.25); height:100%; }
    .fee-panel-head { display:flex; justify-content:space-between; align-items:center; gap:1rem; padding:1rem 1.1rem; border-bottom:1px solid var(--fee-line); background:rgba(255,255,255,.015); }
    .fee-panel-head h3 { margin:0; font-size:1rem; font-weight:800; }
    .fee-panel-head span { color:#7e7e7e; font-size:.8rem; }
    .fee-table-wrap { overflow-x:auto; }
    .fee-table { width:100%; min-width:760px; border-collapse:collapse; }
    .fee-table th { padding:.8rem 1rem; text-align:left; font-size:.72rem; text-transform:uppercase; letter-spacing:.07em; color:#777; background:#0f0f0f; border-bottom:1px solid var(--fee-line); }
    .fee-table td { padding:.9rem 1rem; color:#ddd; border-bottom:1px solid rgba(255,255,255,.05); vertical-align:middle; }
    .fee-table tr:last-child td { border-bottom:0; }
    .fee-table tbody tr:hover { background:rgba(255,255,255,.02); }
    .month-title { font-weight:800; color:#fff; }
    .month-sub { display:block; color:#777; font-size:.75rem; margin-top:.15rem; }
    .money { font-weight:800; }
    .balance { color:var(--fee-gold); font-weight:900; }
    .fee-badge { display:inline-flex; align-items:center; border-radius:999px; padding:.35rem .6rem; font-size:.68rem; font-weight:800; letter-spacing:.05em; text-transform:uppercase; }
    .fee-badge.paid { background:rgba(34,197,94,.13); color:#71e39a; }
    .fee-badge.pending { background:rgba(244,189,23,.13); color:#f6cf59; }
    .fee-badge.partial { background:rgba(59,130,246,.13); color:#7fb2ff; }
    .fee-badge.overdue { background:rgba(229,9,20,.14); color:#ff7a82; }
    .fee-badge.waived { background:rgba(148,163,184,.13); color:#c7d0dc; }
    .payment-list { display:flex; flex-direction:column; }
    .payment-row { padding:1rem 1.1rem; border-bottom:1px solid rgba(255,255,255,.05); }
    .payment-row:last-child { border-bottom:0; }
    .payment-top { display:flex; justify-content:space-between; gap:1rem; align-items:center; }
    .payment-amount { color:#74e39a; font-weight:900; font-size:1.05rem; }
    .payment-date { color:#777; font-size:.75rem; }
    .payment-name { color:#eee; font-weight:700; margin-top:.25rem; }
    .payment-meta { color:#777; font-size:.75rem; margin-top:.2rem; }
    .empty-state { padding:2.5rem 1rem; text-align:center; color:#777; }
    .empty-state i { font-size:2rem; color:#555; margin-bottom:.7rem; }
    .empty-state strong { display:block; color:#aaa; }
    .fee-note { margin-top:1rem; padding:.85rem 1rem; border-radius:12px; border:1px dashed rgba(244,189,23,.22); background:rgba(244,189,23,.05); color:#9b9b9b; font-size:.78rem; }
    @media (max-width:992px) { .stat-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width:576px) { .fees-page { margin:-1rem -.75rem 0; padding-top:1rem; } .fees-hero { padding:1.25rem; } .stat-grid { grid-template-columns:1fr; } }
</style>

<div class="fees-page">
    <div class="fees-shell">
        <section class="fees-hero">
            <div class="fees-kicker"><i class="fas fa-shield-alt"></i> KOMS Student Finance</div>
            <h1>My Fees &amp; Payments</h1>
            <p>Track your training fees, payment history, due dates, and outstanding balance in one place.</p>
        </section>

        <section class="stat-grid">
            <div class="fee-stat"><div class="label">Total billed</div><div class="value">₹<?= number_format($total_due, 2) ?></div><div class="sub"><?= count($records) ?> fee record(s)</div></div>
            <div class="fee-stat"><div class="label">Total paid</div><div class="value">₹<?= number_format($total_paid, 2) ?></div><div class="sub"><?= $payment_count ?> payment(s)</div></div>
            <div class="fee-stat"><div class="label">Outstanding</div><div class="value">₹<?= number_format($total_outstanding, 2) ?></div><div class="sub"><?= $pending_count ?> open balance(s)</div></div>
            <div class="fee-stat"><div class="label">Overdue</div><div class="value">₹<?= number_format($overdue_amount, 2) ?></div><div class="sub">Requires attention</div></div>
        </section>

        <div class="row g-4">
            <div class="col-lg-8">
                <section class="fee-panel">
                    <div class="fee-panel-head"><h3><i class="fas fa-file-invoice-dollar me-2" style="color:#f4bd17"></i>Fee Records</h3><span><?= count($records) ?> record(s)</span></div>
                    <div class="fee-table-wrap">
                        <table class="fee-table">
                            <thead><tr><th>Billing period</th><th>Fee</th><th>Due</th><th>Paid</th><th>Balance</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php if ($records): ?>
                                    <?php foreach ($records as $record): ?>
                                        <?php $balance = max(0, (float)$record['amount_due'] - (float)$record['amount_paid']); ?>
                                        <tr>
                                            <td><span class="month-title"><?= date('M Y', strtotime($record['billing_month'])) ?></span><span class="month-sub">Due <?= date('M j, Y', strtotime($record['due_date'])) ?></span></td>
                                            <td><span class="month-title"><?= htmlspecialchars($record['fee_name']) ?></span><span class="month-sub"><?= htmlspecialchars(ucfirst($record['frequency'])) ?></span></td>
                                            <td class="money">₹<?= number_format((float)$record['amount_due'], 2) ?></td>
                                            <td class="money">₹<?= number_format((float)$record['amount_paid'], 2) ?></td>
                                            <td class="balance">₹<?= number_format($balance, 2) ?></td>
                                            <td><span class="fee-badge <?= fee_status_class($record['status']) ?>"><?= htmlspecialchars(str_replace('_', ' ', $record['status'])) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6"><div class="empty-state"><i class="fas fa-receipt"></i><strong>No fee records yet</strong>Your dojo has not added any fee records for your account.</div></td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <div class="col-lg-4">
                <section class="fee-panel">
                    <div class="fee-panel-head"><h3><i class="fas fa-history me-2" style="color:#f4bd17"></i>Payment History</h3><span><?= $payment_count ?> payment(s)</span></div>
                    <div class="payment-list">
                        <?php if ($payments): ?>
                            <?php foreach ($payments as $payment): ?>
                                <div class="payment-row">
                                    <div class="payment-top"><span class="payment-amount">+₹<?= number_format((float)$payment['amount'], 2) ?></span><span class="payment-date"><?= date('M j, Y', strtotime($payment['payment_date'])) ?></span></div>
                                    <div class="payment-name"><?= htmlspecialchars($payment['fee_name']) ?></div>
                                    <div class="payment-meta"><?= date('M Y', strtotime($payment['billing_month'])) ?> · <?= htmlspecialchars($payment['payment_method']) ?><?php if (!empty($payment['transaction_ref'])): ?> · Ref <?= htmlspecialchars($payment['transaction_ref']) ?><?php endif; ?></div>
                                    <?php if (!empty($payment['remarks'])): ?><div class="payment-meta"><?= htmlspecialchars($payment['remarks']) ?></div><?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state"><i class="fas fa-wallet"></i><strong>No payments yet</strong>Recorded payments will appear here.</div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </div>

        <div class="fee-note"><i class="fas fa-info-circle me-1"></i>Payments shown here are records entered by your dojo management. Contact your Master for corrections or receipt confirmation.</div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
