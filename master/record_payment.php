<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('master');

$record_id = isset($_GET['record_id']) ? (int)$_GET['record_id'] : 0;
if (!$record_id) redirect('/master/fees.php');

// Verify record belongs to master's dojo
$stmt = $pdo->prepare("
    SELECT r.*, s.fee_name, u.first_name, u.last_name, s.dojo_id 
    FROM fee_records r 
    JOIN fee_structures s ON r.fee_structure_id = s.id 
    JOIN users u ON r.student_id = u.id 
    WHERE r.id = ?
");
$stmt->execute([$record_id]);
$record = $stmt->fetch();

// Check if master owns this dojo
$check = $pdo->prepare("SELECT id FROM dojos WHERE id = ? AND master_id = ?");
$check->execute([$record['dojo_id'] ?? 0, $_SESSION['user_id']]);
if (!$check->fetch()) {
    $_SESSION['error_msg'] = "Unauthorized access to fee record.";
    redirect('/master/fees.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error_msg'] = "Invalid form submission.";
    } else {
        $amount_paid = (float)$_POST['amount'];
        $method = sanitize_input($_POST['payment_method']);
        $ref = sanitize_input($_POST['transaction_ref']);
        $date = sanitize_input($_POST['payment_date']);
        $remarks = sanitize_input($_POST['remarks']);
        
        $pdo->beginTransaction();
        try {
            // Insert Payment
            $insert = $pdo->prepare("INSERT INTO payments (fee_record_id, student_id, amount, payment_method, transaction_ref, payment_date, recorded_by, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $insert->execute([$record_id, $record['student_id'], $amount_paid, $method, $ref, $date, $_SESSION['user_id'], $remarks]);
            
            // Calculate total paid so far
            $sum_stmt = $pdo->prepare("SELECT SUM(amount) FROM payments WHERE fee_record_id = ?");
            $sum_stmt->execute([$record_id]);
            $total_paid = $sum_stmt->fetchColumn() ?: 0;
            
            // Determine new status
            $new_status = ($total_paid >= $record['amount_due']) ? 'paid' : 'partially_paid';
            
            $update = $pdo->prepare("UPDATE fee_records SET status = ? WHERE id = ?");
            $update->execute([$new_status, $record_id]);
            
            $pdo->commit();
            $_SESSION['success_msg'] = "Payment recorded successfully.";
            log_audit_action($pdo, $_SESSION['user_id'], 'CREATE', 'payments', $pdo->lastInsertId(), "Recorded payment of $amount_paid for student " . $record['student_id']);
            redirect('/master/fees.php');
            
        } catch (\Exception $e) {
            $pdo->rollBack();
            $_SESSION['error_msg'] = "Error recording payment: " . $e->getMessage();
        }
    }
}

$page_title = 'Record Payment';
require_once '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Record Payment</h5>
                <a href="fees.php" class="btn btn-sm btn-light text-dark">Cancel</a>
            </div>
            <div class="card-body p-4">
                
                <div class="bg-light p-3 rounded mb-4 border">
                    <h5 class="text-primary"><?= htmlspecialchars($record['first_name'] . ' ' . $record['last_name']) ?></h5>
                    <p class="mb-1"><strong>Fee:</strong> <?= htmlspecialchars($record['fee_name']) ?> (<?= date('M Y', strtotime($record['billing_month'])) ?>)</p>
                    <p class="mb-1 text-danger"><strong>Amount Due:</strong> $<?= number_format($record['amount_due'], 2) ?></p>
                </div>
                
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <input type="hidden" name="submit_payment" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label">Payment Amount ($)</label>
                        <input type="number" step="0.01" name="amount" class="form-control" value="<?= htmlspecialchars($record['amount_due']) ?>" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="Cash">Cash</option>
                                <option value="Credit Card">Credit Card</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Check">Check</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date</label>
                            <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Transaction Ref / Receipt No. (Optional)</label>
                        <input type="text" name="transaction_ref" class="form-control">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-success w-100">Submit Payment</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
