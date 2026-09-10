<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('master');

$record_id = isset($_GET['record_id']) ? (int)$_GET['record_id'] : 0;
if (!$record_id) {
    redirect('/master/fees.php');
}

$stmt = $pdo->prepare("SELECT r.*, s.fee_name, s.dojo_id, u.first_name, u.last_name FROM fee_records r JOIN fee_structures s ON r.fee_structure_id = s.id JOIN users u ON r.student_id = u.id WHERE r.id = ? LIMIT 1");
$stmt->execute([$record_id]);
$record = $stmt->fetch();

if (!$record) {
    $_SESSION['error_msg'] = 'Fee record not found.';
    redirect('/master/fees.php');
}

$check = $pdo->prepare("SELECT id, name FROM dojos WHERE id = ? AND master_id = ? LIMIT 1");
$check->execute([(int)$record['dojo_id'], (int)$_SESSION['user_id']]);
$dojo = $check->fetch();

if (!$dojo) {
    $_SESSION['error_msg'] = 'Unauthorized access to this fee record.';
    redirect('/master/fees.php');
}

$paid_stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE fee_record_id = ?");
$paid_stmt->execute([$record_id]);
$total_paid = (float)$paid_stmt->fetchColumn();
$remaining = max(0, (float)$record['amount_due'] - $total_paid);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission. Please try again.';
    } elseif ($record['status'] === 'paid' || $remaining <= 0) {
        $error = 'This fee record is already fully paid.';
    } else {
        $amount_paid = round((float)($_POST['amount'] ?? 0), 2);
        $method = sanitize_input($_POST['payment_method'] ?? 'Cash');
        $ref = sanitize_input($_POST['transaction_ref'] ?? '');
        $date = sanitize_input($_POST['payment_date'] ?? date('Y-m-d'));
        $remarks = sanitize_input($_POST['remarks'] ?? '');

        $allowed_methods = ['Cash', 'UPI', 'Bank Transfer', 'Card', 'Cheque'];
        if ($amount_paid <= 0) {
            $error = 'Payment amount must be greater than ₹0.';
        } elseif ($amount_paid > $remaining) {
            $error = 'Payment cannot be greater than the remaining balance of ₹' . number_format($remaining, 2) . '.';
        } elseif (!in_array($method, $allowed_methods, true)) {
            $error = 'Please select a valid payment method.';
        } elseif (!DateTime::createFromFormat('Y-m-d', $date)) {
            $error = 'Please enter a valid payment date.';
        } else {
            try {
                $pdo->beginTransaction();

                $insert = $pdo->prepare("INSERT INTO payments (fee_record_id, student_id, amount, payment_method, transaction_ref, payment_date, recorded_by, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $insert->execute([$record_id, $record['student_id'], $amount_paid, $method, $ref, $date, $_SESSION['user_id'], $remarks]);
                $payment_id = $pdo->lastInsertId();

                $sum_stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE fee_record_id = ?");
                $sum_stmt->execute([$record_id]);
                $new_total_paid = (float)$sum_stmt->fetchColumn();
                $new_status = $new_total_paid >= (float)$record['amount_due'] ? 'paid' : 'partially_paid';

                $update = $pdo->prepare("UPDATE fee_records SET status = ? WHERE id = ?");
                $update->execute([$new_status, $record_id]);

                $pdo->commit();

                log_audit_action($pdo, $_SESSION['user_id'], 'CREATE', 'payments', (int)$payment_id, 'Recorded fee payment for student ' . $record['student_id']);
                $_SESSION['success_msg'] = 'Payment of ₹' . number_format($amount_paid, 2) . ' recorded successfully.';
                redirect('/master/fees.php');
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Unable to record the payment. Please try again.';
                error_log('KOMS payment error: ' . $e->getMessage());
            }
        }
    }
}

$page_title = 'Record Payment';
require_once '../includes/header.php';
?>

<style>
    .payment-wrap{max-width:1050px;margin:1.5rem auto}.payment-hero{padding:1.7rem 1.9rem;border-radius:24px;background:linear-gradient(135deg,#080808 0%,#1e1e1e 60%,#4a0808 100%);color:#fff;box-shadow:0 20px 50px rgba(0,0,0,.14)}
    .kicker{font-size:.7rem;letter-spacing:.14em;text-transform:uppercase;font-weight:800;color:#f5c84b}.hero-title{font-weight:900;font-size:clamp(1.65rem,4vw,2.55rem);margin:.25rem 0}.hero-sub{color:rgba(255,255,255,.72);margin:0}
    .panel{border:0;border-radius:20px;overflow:hidden;box-shadow:0 15px 38px rgba(17,24,39,.08)}.panel .card-header{background:#fff;border:0;padding:1.2rem 1.35rem}.panel .card-body{padding:1.35rem;background:#fff}
    .summary{border-radius:16px;background:#f7f7f7;padding:1rem}.summary-label{font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;color:#777;font-weight:800}.summary-value{font-size:1.2rem;font-weight:900}.balance{font-size:1.8rem;font-weight:950}.form-label{font-weight:700;font-size:.88rem}.form-control,.form-select{border-radius:12px;padding:.72rem .85rem}.form-control:focus,.form-select:focus{border-color:#111;box-shadow:0 0 0 .18rem rgba(17,17,17,.08)}
    .method-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:.5rem}.method-grid input{display:none}.method-grid label{cursor:pointer;text-align:center;border:1px solid #ddd;border-radius:12px;padding:.65rem .4rem;font-size:.78rem;font-weight:700;background:#fff}.method-grid input:checked+label{background:#111;color:#fff;border-color:#111}.helper{font-size:.78rem;color:#7b7b7b}
    @media(max-width:700px){.method-grid{grid-template-columns:repeat(2,1fr)}}
</style>

<div class="payment-wrap">
    <section class="payment-hero mb-4">
        <div class="kicker">Master Control • Fees</div>
        <div class="hero-title">Record a Student Payment</div>
        <p class="hero-sub">Capture the payment safely, update the fee status automatically, and keep a clear audit trail.</p>
    </section>

    <?php if ($error): ?>
        <div class="alert alert-danger border-0 shadow-sm mb-4"><i class="fas fa-circle-exclamation me-2"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card panel">
                <div class="card-header"><div class="text-uppercase text-muted" style="font-size:.72rem;font-weight:800;letter-spacing:.08em;">Payment Entry</div><h5 class="mb-0 mt-1">New Payment</h5></div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                        <input type="hidden" name="submit_payment" value="1">

                        <div class="mb-3">
                            <label class="form-label">Amount Paid (₹)</label>
                            <input type="number" min="0.01" max="<?= htmlspecialchars(number_format($remaining, 2, '.', '')) ?>" step="0.01" name="amount" class="form-control form-control-lg" value="<?= htmlspecialchars(number_format($remaining, 2, '.', '')) ?>" required>
                            <div class="helper mt-1">Maximum allowed for this record: ₹<?= number_format($remaining, 2) ?></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label d-block">Payment Method</label>
                            <div class="method-grid">
                                <?php foreach (['Cash','UPI','Bank Transfer','Card','Cheque'] as $method): ?>
                                    <div>
                                        <input type="radio" name="payment_method" value="<?= htmlspecialchars($method) ?>" id="method_<?= strtolower(str_replace(' ', '_', $method)) ?>" <?= $method === 'Cash' ? 'checked' : '' ?>>
                                        <label for="method_<?= strtolower(str_replace(' ', '_', $method)) ?>"><i class="fas fa-<?= $method === 'Cash' ? 'money-bill-wave' : ($method === 'UPI' ? 'mobile-screen-button' : ($method === 'Card' ? 'credit-card' : 'building-columns')) ?> d-block mb-1"></i><?= htmlspecialchars($method) ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Payment Date</label>
                                <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Transaction / Receipt No.</label>
                                <input type="text" name="transaction_ref" class="form-control" maxlength="100" placeholder="Optional">
                            </div>
                        </div>

                        <div class="mt-3 mb-4">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="3" maxlength="1000" placeholder="Add any useful payment note."></textarea>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-dark px-4" <?= $remaining <= 0 ? 'disabled' : '' ?>><i class="fas fa-check me-2"></i>Save Payment</button>
                            <a href="fees.php" class="btn btn-outline-secondary px-4">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card panel mb-4">
                <div class="card-header"><div class="text-uppercase text-muted" style="font-size:.72rem;font-weight:800;letter-spacing:.08em;">Fee Summary</div><h5 class="mb-0 mt-1">Student Account</h5></div>
                <div class="card-body">
                    <h4 class="fw-bold mb-1"><?= htmlspecialchars($record['first_name'] . ' ' . $record['last_name']) ?></h4>
                    <div class="text-muted mb-3"><?= htmlspecialchars($record['fee_name']) ?> • <?= date('M Y', strtotime($record['billing_month'])) ?></div>
                    <div class="summary mb-2"><div class="summary-label">Amount Due</div><div class="summary-value">₹<?= number_format((float)$record['amount_due'], 2) ?></div></div>
                    <div class="summary mb-2"><div class="summary-label">Paid So Far</div><div class="summary-value text-success">₹<?= number_format($total_paid, 2) ?></div></div>
                    <div class="summary"><div class="summary-label">Remaining Balance</div><div class="balance <?= $remaining > 0 ? 'text-danger' : 'text-success' ?>">₹<?= number_format($remaining, 2) ?></div></div>
                </div>
            </div>

            <div class="card panel">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-shield-halved me-2"></i>Payment Safety</h6></div>
                <div class="card-body small text-muted">
                    The amount is checked against the remaining balance. After saving, the fee record changes to <strong>Paid</strong> or <strong>Partially Paid</strong> automatically.
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
