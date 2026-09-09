<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$dojo_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$dojo_id) {
    redirect('/find_dojo.php');
}

$stmt = $pdo->prepare("SELECT d.*, u.first_name, u.last_name FROM dojos d JOIN users u ON d.master_id = u.id WHERE d.id = ? AND d.status = 'approved'");
$stmt->execute([$dojo_id]);
$dojo = $stmt->fetch();

if (!$dojo) {
    $_SESSION['error_msg'] = "Dojo not found or not active.";
    redirect('/find_dojo.php');
}

// Check membership status if logged in
$membership = null;
if (is_logged_in() && has_role('student')) {
    $stmt = $pdo->prepare("SELECT status FROM dojo_memberships WHERE student_id = ? AND dojo_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$_SESSION['user_id'], $dojo_id]);
    $membership = $stmt->fetchColumn();
}

// Handle join request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'join') {
    require_role('student');
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error_msg'] = "Invalid form submission.";
    } elseif ($membership === 'pending' || $membership === 'approved') {
        $_SESSION['error_msg'] = "You already have a request or are a member of this dojo.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO dojo_memberships (student_id, dojo_id, status) VALUES (?, ?, 'pending')");
        $stmt->execute([$_SESSION['user_id'], $dojo_id]);
        $_SESSION['success_msg'] = "Join request submitted successfully. Please wait for the Master's approval.";
        redirect("/dojo_details.php?id=$dojo_id");
    }
}

$page_title = $dojo['name'];
require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm mb-4 border-0 border-top border-primary border-4">
            <div class="card-body p-4">
                <h2 class="display-5 text-primary mb-1"><?= htmlspecialchars($dojo['name']) ?></h2>
                <h5 class="text-muted mb-4">Master <?= htmlspecialchars($dojo['first_name'] . ' ' . $dojo['last_name']) ?></h5>
                
                <h4>About</h4>
                <p><?= nl2br(htmlspecialchars($dojo['description'] ?: 'No description provided.')) ?></p>
                
                <h4 class="mt-4">Experience & Achievements</h4>
                <p><?= nl2br(htmlspecialchars($dojo['experience'] ?: 'Not specified.')) ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title border-bottom pb-2">Information</h5>
                <ul class="list-unstyled mb-4">
                    <li class="mb-2"><i class="fas fa-map-marker-alt text-primary w-20 me-2"></i> <?= htmlspecialchars($dojo['location']) ?></li>
                    <li class="mb-2"><i class="fas fa-phone text-success w-20 me-2"></i> <?= htmlspecialchars($dojo['contact_number'] ?: 'N/A') ?></li>
                    <li class="mb-2"><i class="fas fa-envelope text-info w-20 me-2"></i> <?= htmlspecialchars($dojo['email'] ?: 'N/A') ?></li>
                    <li class="mb-2"><i class="fas fa-calendar-alt text-warning w-20 me-2"></i> <?= htmlspecialchars($dojo['training_days'] ?: 'N/A') ?></li>
                    <li class="mb-2"><i class="fas fa-clock text-secondary w-20 me-2"></i> <?= htmlspecialchars($dojo['training_timings'] ?: 'N/A') ?></li>
                </ul>

                <?php if (!is_logged_in()): ?>
                    <div class="alert alert-info">Please <a href="<?= APP_URL ?>/login.php">login</a> as a student to join this dojo.</div>
                <?php elseif (has_role('student')): ?>
                    <?php if ($membership === 'approved'): ?>
                        <div class="alert alert-success text-center"><i class="fas fa-check-circle"></i> You are an active member</div>
                    <?php elseif ($membership === 'pending'): ?>
                        <div class="alert alert-warning text-center"><i class="fas fa-clock"></i> Request Pending Approval</div>
                    <?php else: ?>
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            <input type="hidden" name="action" value="join">
                            <button type="submit" class="btn btn-primary w-100 btn-lg">Request to Join Dojo</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
