<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('master');

// Get master's active dojo
$stmt = $pdo->prepare("SELECT id FROM dojos WHERE master_id = ? AND status = 'approved' LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$dojo_id = $stmt->fetchColumn();

if (!$dojo_id) {
    $_SESSION['error_msg'] = "You need an active dojo to manage attendance.";
    redirect('/master/dashboard.php');
}

// Handle session creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_session'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error_msg'] = "Invalid form submission.";
    } else {
        $date = sanitize_input($_POST['session_date']);
        $day = date('D', strtotime($date));
        $start = sanitize_input($_POST['start_time']);
        $end = sanitize_input($_POST['end_time']);

        // Check if session already exists for this date
        $check = $pdo->prepare("SELECT id FROM attendance_sessions WHERE dojo_id = ? AND session_date = ?");
        $check->execute([$dojo_id, $date]);
        
        if ($check->fetch()) {
            $_SESSION['error_msg'] = "A training session already exists for this date.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO attendance_sessions (dojo_id, instructor_id, session_date, scheduled_day, start_time, end_time, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$dojo_id, $_SESSION['user_id'], $date, $day, $start, $end, $_SESSION['user_id']])) {
                $_SESSION['success_msg'] = "Attendance session created. You can now mark student attendance.";
                redirect('/master/mark_attendance.php?session_id=' . $pdo->lastInsertId());
            }
        }
    }
}

$page_title = 'Attendance Management';
require_once '../includes/header.php';

// Fetch existing sessions
$stmt = $pdo->prepare("SELECT * FROM attendance_sessions WHERE dojo_id = ? ORDER BY session_date DESC LIMIT 30");
$stmt->execute([$dojo_id]);
$sessions = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Create Training Session</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <input type="hidden" name="create_session" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="session_date" class="form-control" value="<?= date('Y-m-d') ?>" required max="<?= date('Y-m-d') ?>">
                    </div>
                    
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Start Time</label>
                            <input type="time" name="start_time" class="form-control">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">End Time</label>
                            <input type="time" name="end_time" class="form-control">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">Create & Mark Attendance</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Recent Sessions</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Day</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sessions as $s): ?>
                            <tr>
                                <td class="fw-bold"><?= date('M j, Y', strtotime($s['session_date'])) ?></td>
                                <td><?= htmlspecialchars($s['scheduled_day']) ?></td>
                                <td>
                                    <?= $s['start_time'] ? date('g:i A', strtotime($s['start_time'])) : '--' ?> to 
                                    <?= $s['end_time'] ? date('g:i A', strtotime($s['end_time'])) : '--' ?>
                                </td>
                                <td>
                                    <?php if ($s['is_locked']): ?>
                                        <span class="badge bg-secondary"><i class="fas fa-lock"></i> Locked</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Open</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="mark_attendance.php?session_id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <?= $s['is_locked'] ? 'View' : 'Mark' ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
