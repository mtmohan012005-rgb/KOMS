<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('student');

$page_title = 'Student Dashboard';
require_once '../includes/header.php';

// Get student's dojo membership
$stmt = $pdo->prepare("
    SELECT d.id, d.name, m.status 
    FROM dojo_memberships m 
    JOIN dojos d ON m.dojo_id = d.id 
    WHERE m.student_id = ? 
    ORDER BY m.created_at DESC LIMIT 1
");
$stmt->execute([$_SESSION['user_id']]);
$membership = $stmt->fetch();

?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">My Dashboard</h1>
</div>

<?php if (!$membership): ?>
    <div class="alert alert-info shadow-sm">
        <h4 class="alert-heading">Welcome to KOMS!</h4>
        <p>You haven't joined a Dojo yet. To start training and tracking your progress, you need to find and join a Dojo.</p>
        <hr>
        <a href="<?= APP_URL ?>/find_dojo.php" class="btn btn-primary">Find a Dojo</a>
    </div>
<?php elseif ($membership['status'] === 'pending'): ?>
    <div class="alert alert-warning shadow-sm">
        <h4 class="alert-heading">Membership Pending</h4>
        <p>Your request to join <strong><?= htmlspecialchars($membership['name']) ?></strong> is currently pending approval from the Dojo Master. You will be notified once a decision is made.</p>
    </div>
<?php elseif ($membership['status'] === 'approved'): ?>
    
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm h-100 border-0 border-start border-primary border-4">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase fw-bold mb-2">My Dojo</h6>
                    <h4 class="card-title text-primary"><?= htmlspecialchars($membership['name']) ?></h4>
                    <a href="my_dojo.php" class="btn btn-sm btn-outline-primary mt-2">View Dojo Info</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm h-100 border-0 border-start border-success border-4">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase fw-bold mb-2">Current Rank</h6>
                    <?php
                    // Fetch latest belt
                    $belt_stmt = $pdo->prepare("SELECT new_belt FROM grading_history WHERE student_id = ? ORDER BY exam_date DESC LIMIT 1");
                    $belt_stmt->execute([$_SESSION['user_id']]);
                    $belt = $belt_stmt->fetchColumn();
                    ?>
                    <h4 class="card-title text-success"><?= $belt ? htmlspecialchars($belt) : 'White Belt (Novice)' ?></h4>
                    <a href="grading.php" class="btn btn-sm btn-outline-success mt-2">View History</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm h-100 border-0 border-start border-warning border-4">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase fw-bold mb-2">Outstanding Fees</h6>
                    <?php
                    // Calculate outstanding fees
                    $fee_stmt = $pdo->prepare("SELECT SUM(amount_due) FROM fee_records WHERE student_id = ? AND status IN ('pending', 'overdue')");
                    $fee_stmt->execute([$_SESSION['user_id']]);
                    $fees = $fee_stmt->fetchColumn();
                    ?>
                    <h4 class="card-title text-warning">$<?= number_format($fees ?: 0, 2) ?></h4>
                    <a href="fees.php" class="btn btn-sm btn-outline-warning mt-2">Pay Now</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Announcements</h5>
                    <a href="announcements.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php
                    $ann_query = $pdo->prepare("
                        SELECT * FROM announcements 
                        WHERE status = 'active' AND (level = 'global' OR dojo_id = ?) 
                        ORDER BY publish_date DESC LIMIT 3
                    ");
                    $ann_query->execute([$membership['id'] ?? 0]);
                    $recent_anns = $ann_query->fetchAll();

                    if (count($recent_anns) > 0):
                        foreach ($recent_anns as $ra):
                    ?>
                        <div class="mb-3 pb-2 border-bottom">
                            <div class="d-flex justify-content-between">
                                <h6 class="fw-bold mb-1"><?= htmlspecialchars($ra['title']) ?></h6>
                                <small class="text-muted"><?= date('M j', strtotime($ra['publish_date'])) ?></small>
                            </div>
                            <p class="small text-muted mb-0"><?= htmlspecialchars(substr($ra['content'], 0, 100)) ?>...</p>
                        </div>
                    <?php 
                        endforeach;
                    else: 
                    ?>
                        <p class="text-muted text-center my-4">No recent announcements.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Recent Attendance</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $att_stmt = $pdo->prepare("
                                    SELECT s.session_date, e.status 
                                    FROM attendance_entries e 
                                    JOIN attendance_sessions s ON e.session_id = s.id 
                                    WHERE e.student_id = ? 
                                    ORDER BY s.session_date DESC LIMIT 5
                                ");
                                $att_stmt->execute([$_SESSION['user_id']]);
                                $attendances = $att_stmt->fetchAll();
                                
                                if (count($attendances) > 0):
                                    foreach ($attendances as $att):
                                        $badge = $att['status'] === 'present' ? 'success' : ($att['status'] === 'absent' ? 'danger' : 'warning');
                                ?>
                                <tr>
                                    <td><?= date('M j, Y', strtotime($att['session_date'])) ?></td>
                                    <td><span class="badge bg-<?= $badge ?> text-uppercase"><?= $att['status'] ?></span></td>
                                </tr>
                                <?php 
                                    endforeach;
                                else:
                                ?>
                                <tr>
                                    <td colspan="2" class="text-center text-muted my-3">No attendance records found.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
