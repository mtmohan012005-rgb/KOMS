<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('master');

// Check if master already has a dojo
$stmt = $pdo->prepare("SELECT id, status FROM dojos WHERE master_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$existing_dojo = $stmt->fetch();

if ($existing_dojo) {
    if ($existing_dojo['status'] === 'pending') {
        $_SESSION['info_msg'] = "Your dojo registration is currently pending admin approval.";
    } else {
        $_SESSION['info_msg'] = "You already have a registered dojo.";
    }
    redirect('/master/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error_msg'] = "Invalid form submission.";
    } else {
        $name = sanitize_input($_POST['name']);
        $location = sanitize_input($_POST['location']);
        $phone = sanitize_input($_POST['phone']);
        $email = sanitize_input($_POST['email']);
        $training_days = isset($_POST['days']) ? implode(', ', $_POST['days']) : '';
        $timings = sanitize_input($_POST['timings']);
        $desc = sanitize_input($_POST['description']);

        $stmt = $pdo->prepare("INSERT INTO dojos (name, master_id, location, contact_number, email, training_days, training_timings, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        if ($stmt->execute([$name, $_SESSION['user_id'], $location, $phone, $email, $training_days, $timings, $desc])) {
            $_SESSION['success_msg'] = "Dojo registered successfully! It is now pending approval from the Grand Master.";
            log_audit_action($pdo, $_SESSION['user_id'], 'CREATE', 'dojos', $pdo->lastInsertId(), "Registered new dojo: $name");
            redirect('/master/dashboard.php');
        } else {
            $_SESSION['error_msg'] = "Failed to register dojo.";
        }
    }
}

$page_title = 'Register Dojo';
require_once '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Register Your Dojo</h4>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Dojo Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Physical Location / Address</label>
                        <input type="text" name="location" class="form-control" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Training Days</label>
                        <div class="d-flex gap-3 flex-wrap">
                            <?php foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="days[]" value="<?= $day ?>" id="day_<?= $day ?>">
                                <label class="form-check-label" for="day_<?= $day ?>"><?= $day ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Training Timings (e.g., 5:00 PM - 8:00 PM)</label>
                        <input type="text" name="timings" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description / About Dojo</label>
                        <textarea name="description" class="form-control" rows="4"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">Submit Registration for Approval</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
