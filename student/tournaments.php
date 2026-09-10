<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('student');

// Get active dojo ID
$stmt = $pdo->prepare("SELECT dojo_id FROM dojo_memberships WHERE student_id = ? AND status = 'approved' LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$dojo_id = $stmt->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error_msg'] = "Invalid form submission.";
    } elseif (!$dojo_id) {
        $_SESSION['error_msg'] = "You must be an active member of a dojo to register.";
    } else {
        $t_id = (int)$_POST['tournament_id'];
        
        $check = $pdo->prepare("SELECT id FROM tournament_registrations WHERE tournament_id = ? AND student_id = ?");
        $check->execute([$t_id, $_SESSION['user_id']]);
        
        if ($check->fetch()) {
            $_SESSION['error_msg'] = "You have already registered for this tournament.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO tournament_registrations (tournament_id, student_id, dojo_id) VALUES (?, ?, ?)");
            $stmt->execute([$t_id, $_SESSION['user_id'], $dojo_id]);
            $_SESSION['success_msg'] = "Registration submitted. Pending review by your Master.";
            redirect('/student/tournaments.php');
        }
    }
}

$page_title = 'Tournaments';
require_once '../includes/header.php';

// Fetch available tournaments (published, future date)
$stmt = $pdo->query("
    SELECT * FROM tournaments 
    WHERE status IN ('published', 'registration_open') 
    AND registration_deadline >= CURRENT_DATE 
    ORDER BY event_date ASC
");
$available = $stmt->fetchAll();

// Fetch student's registrations
$reg_stmt = $pdo->prepare("
    SELECT r.id as reg_id, r.status as reg_status, r.tournament_id, t.* 
    FROM tournament_registrations r 
    JOIN tournaments t ON r.tournament_id = t.id 
    WHERE r.student_id = ?
");
$reg_stmt->execute([$_SESSION['user_id']]);
$my_regs = $reg_stmt->fetchAll();
$registered_ids = array_column($my_regs, 'tournament_id'); // tournament IDs the student registered for
?>

<div class="row mb-4">
    <div class="col-12">
        <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#available">Available Tournaments</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#my-regs">My Registrations</button>
            </li>
        </ul>
        
        <div class="tab-content">
            <!-- Available -->
            <div class="tab-pane fade show active" id="available">
                <div class="row">
                    <?php foreach ($available as $t): ?>
                        <?php if(in_array($t['id'], $registered_ids)) continue; ?>
                        <div class="col-md-6 mb-4">
                            <div class="card shadow-sm h-100 border-top border-primary border-4">
                                <div class="card-body">
                                    <h4 class="card-title text-primary"><?= htmlspecialchars($t['name']) ?></h4>
                                    <p class="card-text text-muted mb-3"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($t['venue']) ?></p>
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <small class="text-muted d-block">Event Date</small>
                                            <strong><?= date('M j, Y', strtotime($t['event_date'])) ?></strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Deadline</small>
                                            <strong class="text-danger"><?= date('M j, Y', strtotime($t['registration_deadline'])) ?></strong>
                                        </div>
                                    </div>
                                    <p class="small"><?= nl2br(htmlspecialchars($t['description'])) ?></p>
                                    
                                    <?php if ($dojo_id): ?>
                                    <form method="POST" action="">
                                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                        <input type="hidden" name="register" value="1">
                                        <input type="hidden" name="tournament_id" value="<?= $t['id'] ?>">
                                        <button type="submit" class="btn btn-outline-primary w-100">Register</button>
                                    </form>
                                    <?php else: ?>
                                    <button class="btn btn-secondary w-100" disabled>Join a Dojo First</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($available)): ?>
                        <div class="col-12"><p class="text-muted">No upcoming tournaments available.</p></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- My Regs -->
            <div class="tab-pane fade" id="my-regs">
                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tournament</th>
                                    <th>Event Date</th>
                                    <th>Venue</th>
                                    <th>Selection Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($my_regs as $r): ?>
                                <tr>
                                    <td class="fw-bold"><?= htmlspecialchars($r['name']) ?></td>
                                    <td><?= date('M j, Y', strtotime($r['event_date'])) ?></td>
                                    <td><?= htmlspecialchars($r['venue']) ?></td>
                                    <td>
                                        <?php 
                                        $bg = ['pending_review'=>'warning text-dark', 'selected'=>'success', 'rejected'=>'danger'][$r['reg_status']];
                                        ?>
                                        <span class="badge bg-<?= $bg ?> text-uppercase"><?= str_replace('_', ' ', $r['reg_status']) ?></span>
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
</div>

<?php require_once '../includes/footer.php'; ?>
