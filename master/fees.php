<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('master');

// Get active dojo
$stmt = $pdo->prepare("SELECT id FROM dojos WHERE master_id = ? AND status = 'approved' LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$dojo_id = $stmt->fetchColumn();

if (!$dojo_id) redirect('/master/dashboard.php');

// Create new Fee Structure
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_structure'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error_msg'] = "Invalid form submission.";
    } else {
        $name = sanitize_input($_POST['fee_name']);
        $amount = (float)$_POST['amount'];
        $freq = sanitize_input($_POST['frequency']);
        $eff_from = sanitize_input($_POST['effective_from']);
        
        $stmt = $pdo->prepare("INSERT INTO fee_structures (dojo_id, fee_name, amount, frequency, effective_from) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$dojo_id, $name, $amount, $freq, $eff_from])) {
            $_SESSION['success_msg'] = "New fee structure created. It will apply to future bills starting $eff_from.";
            
            // Auto-generate monthly records for the CURRENT month if effective from today or past
            if (strtotime($eff_from) <= time()) {
                $structure_id = $pdo->lastInsertId();
                $current_month = date('Y-m-01');
                
                // Get all active students
                $stu_stmt = $pdo->prepare("SELECT student_id FROM dojo_memberships WHERE dojo_id = ? AND status = 'approved'");
                $stu_stmt->execute([$dojo_id]);
                $students = $stu_stmt->fetchAll();
                
                $insert_record = $pdo->prepare("INSERT IGNORE INTO fee_records (student_id, fee_structure_id, billing_month, amount_due, due_date) VALUES (?, ?, ?, ?, ?)");
                $due_date = date('Y-m-15'); // default due date 15th
                
                foreach ($students as $stu) {
                    $insert_record->execute([$stu['student_id'], $structure_id, $current_month, $amount, $due_date]);
                }
            }
        }
        redirect('/master/fees.php');
    }
}

$page_title = 'Fee Management';
require_once '../includes/header.php';

// Fetch fee structures
$stmt = $pdo->prepare("SELECT * FROM fee_structures WHERE dojo_id = ? ORDER BY effective_from DESC");
$stmt->execute([$dojo_id]);
$structures = $stmt->fetchAll();

// Fetch pending fee records
$stmt = $pdo->prepare("
    SELECT r.*, s.fee_name, u.first_name, u.last_name 
    FROM fee_records r 
    JOIN fee_structures s ON r.fee_structure_id = s.id 
    JOIN users u ON r.student_id = u.id 
    WHERE s.dojo_id = ? AND r.status IN ('pending', 'overdue', 'partially_paid')
    ORDER BY r.due_date ASC
");
$stmt->execute([$dojo_id]);
$pending_records = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Create Fee Structure</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <input type="hidden" name="create_structure" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label">Fee Name (e.g., Monthly Training)</label>
                        <input type="text" name="fee_name" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Amount ($)</label>
                        <input type="number" step="0.01" name="amount" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Frequency</label>
                        <select name="frequency" class="form-select" required>
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="yearly">Yearly</option>
                            <option value="one-time">One-Time</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Effective From Date</label>
                        <input type="date" name="effective_from" class="form-control" required>
                        <small class="text-muted">Changes only affect future records.</small>
                    </div>
                    
                    <button type="submit" class="btn btn-success w-100">Create Structure</button>
                </form>
            </div>
        </div>
        
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Current Structures</h5>
            </div>
            <ul class="list-group list-group-flush">
                <?php foreach ($structures as $st): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0"><?= htmlspecialchars($st['fee_name']) ?></h6>
                        <small class="text-muted">$<?= number_format($st['amount'], 2) ?> / <?= $st['frequency'] ?></small>
                    </div>
                    <span class="badge bg-<?= $st['status'] === 'active' ? 'primary' : 'secondary' ?> rounded-pill">
                        <?= date('M Y', strtotime($st['effective_from'])) ?>
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Outstanding Fee Records</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Student</th>
                                <th>Fee Name</th>
                                <th>Billing Month</th>
                                <th>Due Date</th>
                                <th>Amount Due</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_records as $rec): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($rec['first_name'] . ' ' . $rec['last_name']) ?></td>
                                <td><?= htmlspecialchars($rec['fee_name']) ?></td>
                                <td><?= date('M Y', strtotime($rec['billing_month'])) ?></td>
                                <td class="<?= strtotime($rec['due_date']) < time() ? 'text-danger fw-bold' : '' ?>">
                                    <?= date('M j, Y', strtotime($rec['due_date'])) ?>
                                </td>
                                <td>$<?= number_format($rec['amount_due'], 2) ?></td>
                                <td>
                                    <a href="record_payment.php?record_id=<?= $rec['id'] ?>" class="btn btn-sm btn-success">Record Payment</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($pending_records)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No outstanding fees found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
