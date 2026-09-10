<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('master');

// Get master's dojo
$stmt = $pdo->prepare("SELECT * FROM dojos WHERE master_id = ? LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$dojo = $stmt->fetch();

if (!$dojo) {
    $_SESSION['error_msg'] = "No dojo found. Please register your dojo first.";
    redirect('/master/register_dojo.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_msg'] = "Invalid form submission.";
    } else {
        $name = sanitize_input($_POST['name']);
        $location = sanitize_input($_POST['location']);
        $phone = sanitize_input($_POST['phone']);
        $email = sanitize_input($_POST['email']);
        $training_days = isset($_POST['days']) ? implode(', ', $_POST['days']) : '';
        $timings = sanitize_input($_POST['timings']);
        $desc = sanitize_input($_POST['description']);

        $stmt = $pdo->prepare("UPDATE dojos SET name = ?, location = ?, contact_number = ?, email = ?, training_days = ?, training_timings = ?, description = ? WHERE id = ? AND master_id = ?");
        if ($stmt->execute([$name, $location, $phone, $email, $training_days, $timings, $desc, $dojo['id'], $_SESSION['user_id']])) {
            $_SESSION['success_msg'] = "Dojo profile updated successfully.";
            log_audit_action($pdo, $_SESSION['user_id'], 'UPDATE', 'dojos', $dojo['id'], "Updated dojo profile: $name");
            redirect('/master/dashboard.php');
        } else {
            $_SESSION['error_msg'] = "Failed to update dojo.";
        }
    }
}

$page_title = 'Edit Dojo Profile';
require_once '../includes/header.php';

$active_days = array_map('trim', explode(',', $dojo['training_days'] ?? ''));
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0 text-primary"><i class="fas fa-edit me-2"></i>Edit Dojo Profile</h4>
                <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">Back to Dashboard</a>
            </div>
            <div class="card-body p-4">
                <div class="mb-3 d-flex align-items-center gap-2">
                    <strong>Status:</strong>
                    <span class="badge bg-<?= $dojo['status'] === 'approved' ? 'success' : 'warning text-dark' ?> text-uppercase">
                        <?= $dojo['status'] ?>
                    </span>
                </div>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

                    <div class="mb-3">
                        <label class="form-label">Dojo Name</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($dojo['name']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Location / Address</label>
                        <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($dojo['location']) ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($dojo['contact_number'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($dojo['email'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Training Days</label>
                        <div class="d-flex gap-3 flex-wrap">
                            <?php foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="days[]" value="<?= $day ?>" id="day_<?= $day ?>" <?= in_array($day, $active_days) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="day_<?= $day ?>"><?= $day ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Training Timings</label>
                        <input type="text" name="timings" class="form-control" value="<?= htmlspecialchars($dojo['training_timings'] ?? '') ?>" placeholder="e.g., 5:00 PM - 8:00 PM">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description / About Dojo</label>
                        <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($dojo['description'] ?? '') ?></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-2"></i>Save Changes</button>
                        <a href="dashboard.php" class="btn btn-light px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
