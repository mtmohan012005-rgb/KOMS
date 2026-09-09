<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('master');

$stmt = $pdo->prepare("SELECT id FROM dojos WHERE master_id = ? AND status = 'approved' LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$dojo_id = $stmt->fetchColumn();
if (!$dojo_id) redirect('/master/dashboard.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_grading'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error_msg'] = "Invalid form submission.";
    } else {
        $student_id = (int)$_POST['student_id'];
        $new_belt = sanitize_input($_POST['new_belt']);
        $date = sanitize_input($_POST['exam_date']);
        $grade = sanitize_input($_POST['grade']);
        $remarks = sanitize_input($_POST['remarks']);

        // Fetch previous belt
        $prev_stmt = $pdo->prepare("SELECT new_belt FROM grading_history WHERE student_id = ? ORDER BY exam_date DESC LIMIT 1");
        $prev_stmt->execute([$student_id]);
        $prev_belt = $prev_stmt->fetchColumn();

        $stmt = $pdo->prepare("INSERT INTO grading_history (student_id, dojo_id, previous_belt, new_belt, exam_date, grade, instructor_id, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$student_id, $dojo_id, $prev_belt, $new_belt, $date, $grade, $_SESSION['user_id'], $remarks])) {
            $_SESSION['success_msg'] = "Grading record added successfully. Historical data preserved.";
            redirect('/master/grading.php');
        } else {
            $_SESSION['error_msg'] = "Failed to add record.";
        }
    }
}

$page_title = 'Grading & Belts';
require_once '../includes/header.php';

// Fetch active students
$stu_stmt = $pdo->prepare("SELECT u.id, u.first_name, u.last_name FROM dojo_memberships m JOIN users u ON m.student_id = u.id WHERE m.dojo_id = ? AND m.status = 'approved' ORDER BY u.first_name");
$stu_stmt->execute([$dojo_id]);
$students = $stu_stmt->fetchAll();

// Fetch recent grading history for this dojo
$hist_stmt = $pdo->prepare("SELECT g.*, u.first_name, u.last_name FROM grading_history g JOIN users u ON g.student_id = u.id WHERE g.dojo_id = ? ORDER BY g.exam_date DESC LIMIT 50");
$hist_stmt->execute([$dojo_id]);
$history = $hist_stmt->fetchAll();
?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Add Grading Record</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <input type="hidden" name="add_grading" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label">Select Student</label>
                        <select name="student_id" class="form-select" required>
                            <option value="" disabled selected>Choose...</option>
                            <?php foreach ($students as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">New Belt Awarded</label>
                        <select name="new_belt" class="form-select" required>
                            <option value="White">White</option>
                            <option value="Yellow">Yellow</option>
                            <option value="Orange">Orange</option>
                            <option value="Green">Green</option>
                            <option value="Blue">Blue</option>
                            <option value="Purple">Purple</option>
                            <option value="Brown">Brown</option>
                            <option value="Black (1st Dan)">Black (1st Dan)</option>
                            <option value="Black (2nd Dan)">Black (2nd Dan)</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Exam Date</label>
                        <input type="date" name="exam_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Grade / Score</label>
                        <input type="text" name="grade" class="form-control" placeholder="e.g., A, 95%, Pass">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">Save Grading Record</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Recent Grading History</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Student</th>
                                <th>Exam Date</th>
                                <th>Progression</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $h): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($h['first_name'] . ' ' . $h['last_name']) ?></td>
                                <td><?= date('M j, Y', strtotime($h['exam_date'])) ?></td>
                                <td>
                                    <?php if ($h['previous_belt']): ?>
                                        <span class="text-muted"><?= htmlspecialchars($h['previous_belt']) ?></span> <i class="fas fa-arrow-right mx-1 text-primary"></i> 
                                    <?php endif; ?>
                                    <span class="fw-bold text-success"><?= htmlspecialchars($h['new_belt']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($h['grade'] ?: '-') ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($history)): ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">No grading records found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
