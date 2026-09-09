<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('student');

$page_title = 'My Grading History';
require_once '../includes/header.php';

// Fetch grading history
$stmt = $pdo->prepare("
    SELECT g.*, u.first_name, u.last_name 
    FROM grading_history g 
    JOIN users u ON g.instructor_id = u.id 
    WHERE g.student_id = ? 
    ORDER BY g.exam_date DESC
");
$stmt->execute([$_SESSION['user_id']]);
$history = $stmt->fetchAll();

$current_belt = !empty($history) ? $history[0]['new_belt'] : 'White (Beginner)';
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-8">
        <h2>Grading & Belt Progression</h2>
        <p class="text-muted">Track your martial arts journey and historical exam results.</p>
    </div>
    <div class="col-md-4 text-md-end">
        <div class="card bg-dark text-white border-0 shadow d-inline-block p-3 text-center">
            <h6 class="text-white-50 mb-1 text-uppercase">Current Rank</h6>
            <h3 class="mb-0 text-warning"><?= htmlspecialchars($current_belt) ?></h3>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <?php if (!empty($history)): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Exam Date</th>
                            <th>Progression</th>
                            <th>Instructor</th>
                            <th>Grade/Score</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $h): ?>
                        <tr>
                            <td class="fw-bold"><?= date('F j, Y', strtotime($h['exam_date'])) ?></td>
                            <td>
                                <?php if ($h['previous_belt']): ?>
                                    <span class="text-secondary"><?= htmlspecialchars($h['previous_belt']) ?></span> 
                                    <i class="fas fa-long-arrow-alt-right mx-2 text-primary"></i> 
                                <?php endif; ?>
                                <span class="badge bg-primary fs-6"><?= htmlspecialchars($h['new_belt']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($h['first_name'] . ' ' . $h['last_name']) ?></td>
                            <td><strong><?= htmlspecialchars($h['grade'] ?: 'N/A') ?></strong></td>
                            <td class="text-muted small"><?= nl2br(htmlspecialchars($h['remarks'] ?: '-')) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-medal fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No grading history yet.</h5>
                <p>Keep training hard! Your master will record your belt exams here.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
