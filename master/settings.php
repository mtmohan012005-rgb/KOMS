<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('master');

$master_id = (int)($_SESSION['user_id'] ?? 0);
$stmt = $pdo->prepare("SELECT id, name, location, phone, email, description, status FROM dojos WHERE master_id = ? AND status = 'approved' LIMIT 1");
$stmt->execute([$master_id]);
$dojo = $stmt->fetch();

if (!$dojo) {
    $_SESSION['error_msg'] = 'You need an approved dojo to access settings.';
    redirect('/master/dashboard.php');
}

$dojo_id = (int)$dojo['id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'dojo') {
            $name = trim($_POST['name'] ?? '');
            $location = trim($_POST['location'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if ($name === '' || $location === '') {
                $error = 'Dojo name and location are required.';
            } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid dojo email address.';
            } elseif (strlen($name) > 150 || strlen($location) > 255 || strlen($phone) > 30 || strlen($email) > 150) {
                $error = 'One or more fields are too long.';
            } else {
                $update = $pdo->prepare("UPDATE dojos SET name = ?, location = ?, phone = ?, email = ?, description = ? WHERE id = ? AND master_id = ? AND status = 'approved'");
                $update->execute([$name, $location, $phone, $email, $description, $dojo_id, $master_id]);
                log_audit_action($pdo, $master_id, 'UPDATE', 'dojos', $dojo_id, 'Updated Master dojo settings');
                $success = 'Dojo settings updated successfully.';

                $dojo['name'] = $name;
                $dojo['location'] = $location;
                $dojo['phone'] = $phone;
                $dojo['email'] = $email;
                $dojo['description'] = $description;
            }
        } elseif ($action === 'password') {
            $current = $_POST['current_password'] ?? '';
            $new = $_POST['new_password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';

            $user_stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ? AND role = 'master' AND status = 'active' LIMIT 1");
            $user_stmt->execute([$master_id]);
            $user = $user_stmt->fetch();

            if (!$user || !password_verify($current, $user['password_hash'])) {
                $error = 'Current password is incorrect.';
            } elseif (strlen($new) < 8) {
                $error = 'New password must be at least 8 characters.';
            } elseif ($new !== $confirm) {
                $error = 'New passwords do not match.';
            } elseif (password_verify($new, $user['password_hash'])) {
                $error = 'New password must be different from the current password.';
            } else {
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $update = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ? AND role = 'master'");
                $update->execute([$hash, $master_id]);
                log_audit_action($pdo, $master_id, 'UPDATE', 'users', $master_id, 'Changed Master account password');
                $success = 'Password changed successfully.';
            }
        }
    }
}

$page_title = 'Master Settings';
require_once '../includes/header.php';
?>

<style>
.settings-wrap{max-width:1180px;margin:1.5rem auto}.settings-hero{background:linear-gradient(135deg,#080808,#202020 58%,#4d0808);color:#fff;border-radius:24px;padding:1.8rem 2rem;box-shadow:0 22px 50px rgba(0,0,0,.14)}.settings-kicker{font-size:.72rem;letter-spacing:.14em;text-transform:uppercase;font-weight:800;color:#ffd15c}.settings-title{font-size:clamp(1.7rem,4vw,2.7rem);font-weight:900;margin:.3rem 0 .45rem}.settings-card{border:0;border-radius:20px;overflow:hidden;box-shadow:0 15px 38px rgba(17,24,39,.08)}.settings-card .card-header{background:#fff;border:0;padding:1.2rem 1.35rem}.settings-card .card-body{background:#fff;padding:1.35rem}.form-control{border-radius:12px;padding:.72rem .85rem}.form-control:focus{border-color:#111;box-shadow:0 0 0 .18rem rgba(17,17,17,.08)}.setting-label{font-size:.84rem;font-weight:800}.security-box{background:#f7f7f7;border-radius:15px;padding:1rem 1.1rem}.muted-note{color:#777;font-size:.82rem}@media(max-width:768px){.settings-hero{padding:1.35rem}.settings-wrap{margin:1rem auto}}
</style>

<div class="settings-wrap">
    <section class="settings-hero mb-4">
        <div class="settings-kicker">Master Control • Configuration</div>
        <div class="settings-title">Portal Settings</div>
        <p class="mb-0" style="color:rgba(255,255,255,.72)">Manage your dojo information and secure your Master account.</p>
    </section>

    <?php if ($error): ?><div class="alert alert-danger border-0 shadow-sm mb-4"><i class="fas fa-circle-exclamation me-2"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success border-0 shadow-sm mb-4"><i class="fas fa-circle-check me-2"></i><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card settings-card">
                <div class="card-header"><div class="text-uppercase text-muted" style="font-size:.72rem;font-weight:800;letter-spacing:.08em">Dojo Settings</div><h5 class="mb-0 mt-1">Public Dojo Information</h5></div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                        <input type="hidden" name="action" value="dojo">
                        <div class="mb-3"><label class="setting-label mb-1">Dojo Name</label><input class="form-control" name="name" value="<?= htmlspecialchars($dojo['name']) ?>" maxlength="150" required></div>
                        <div class="mb-3"><label class="setting-label mb-1">Location</label><input class="form-control" name="location" value="<?= htmlspecialchars($dojo['location'] ?? '') ?>" maxlength="255" required></div>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="setting-label mb-1">Phone</label><input class="form-control" name="phone" value="<?= htmlspecialchars($dojo['phone'] ?? '') ?>" maxlength="30"></div>
                            <div class="col-md-6"><label class="setting-label mb-1">Email</label><input type="email" class="form-control" name="email" value="<?= htmlspecialchars($dojo['email'] ?? '') ?>" maxlength="150"></div>
                        </div>
                        <div class="mt-3 mb-3"><label class="setting-label mb-1">Description</label><textarea class="form-control" name="description" rows="5" maxlength="2000" placeholder="Write a short description about your dojo..."><?= htmlspecialchars($dojo['description'] ?? '') ?></textarea></div>
                        <button class="btn btn-dark"><i class="fas fa-save me-2"></i>Save Dojo Settings</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card settings-card mb-4">
                <div class="card-header"><div class="text-uppercase text-muted" style="font-size:.72rem;font-weight:800;letter-spacing:.08em">Security</div><h5 class="mb-0 mt-1">Change Password</h5></div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                        <input type="hidden" name="action" value="password">
                        <div class="mb-3"><label class="setting-label mb-1">Current Password</label><input type="password" class="form-control" name="current_password" autocomplete="current-password" required></div>
                        <div class="mb-3"><label class="setting-label mb-1">New Password</label><input type="password" class="form-control" name="new_password" minlength="8" autocomplete="new-password" required></div>
                        <div class="mb-3"><label class="setting-label mb-1">Confirm New Password</label><input type="password" class="form-control" name="confirm_password" minlength="8" autocomplete="new-password" required></div>
                        <button class="btn btn-dark w-100"><i class="fas fa-key me-2"></i>Update Password</button>
                    </form>
                </div>
            </div>

            <div class="security-box">
                <div class="fw-bold mb-1"><i class="fas fa-shield-halved me-2"></i>Account Security</div>
                <div class="muted-note">Use a unique password of at least 8 characters. Changes are recorded in the KOMS audit log.</div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
