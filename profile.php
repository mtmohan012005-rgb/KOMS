<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

require_login();

$user_id = $_SESSION['user_id'];
$page_title = 'My Profile';

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    redirect('/logout.php');
}

// Fetch student profile for DPDP compliance if applicable
$student_profile = null;
if ($user['role'] === 'student') {
    $sp_stmt = $pdo->prepare("SELECT * FROM student_profiles WHERE user_id = ?");
    $sp_stmt->execute([$user_id]);
    $student_profile = $sp_stmt->fetch();
}

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error_msg'] = "Invalid form submission.";
    } else {
        $first_name = sanitize_input($_POST['first_name']);
        $last_name = sanitize_input($_POST['last_name']);
        $phone = sanitize_input($_POST['phone']);
        $address = sanitize_input($_POST['address']);
        $emergency_contact = sanitize_input($_POST['emergency_contact']);

        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ?, address = ?, emergency_contact = ? WHERE id = ?");
        if ($stmt->execute([$first_name, $last_name, $phone, $address, $emergency_contact, $user_id])) {
            $_SESSION['user_name'] = "$first_name $last_name";
            $_SESSION['success_msg'] = "Profile details updated successfully.";
            log_audit_action($pdo, $user_id, 'UPDATE', 'profile', $user_id, 'User updated personal profile details');
            redirect('/profile.php');
        } else {
            $_SESSION['error_msg'] = "Failed to update profile.";
        }
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error_msg'] = "Invalid form submission.";
    } else {
        $current_pass = $_POST['current_password'];
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];

        if (!password_verify($current_pass, $user['password_hash'])) {
            $_SESSION['error_msg'] = "Current password is incorrect.";
        } elseif ($new_pass !== $confirm_pass) {
            $_SESSION['error_msg'] = "New passwords do not match.";
        } elseif (strlen($new_pass) < 8) {
            $_SESSION['error_msg'] = "New password must be at least 8 characters long.";
        } else {
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            if ($stmt->execute([$new_hash, $user_id])) {
                $_SESSION['success_msg'] = "Password changed successfully.";
                log_audit_action($pdo, $user_id, 'UPDATE', 'auth', $user_id, 'User changed account password');
                redirect('/profile.php');
            } else {
                $_SESSION['error_msg'] = "Failed to update password.";
            }
        }
    }
}

require_once 'includes/header.php';

// Determine dashboard redirect URL based on role
$dash_url = '/index.php';
if ($user['role'] === 'super_admin') $dash_url = '/admin/dashboard.php';
elseif ($user['role'] === 'master') $dash_url = '/master/dashboard.php';
elseif ($user['role'] === 'senior') $dash_url = '/senior/dashboard.php';
elseif ($user['role'] === 'student') $dash_url = '/student/dashboard.php';
?>

<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h2>User Profile & Security</h2>
            <p class="text-muted">Manage your personal information and account credentials.</p>
        </div>
        <a href="<?= APP_URL . $dash_url ?>" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>
    </div>
</div>

<div class="row">
    <!-- Profile Card & Information -->
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body text-center p-4">
                <div class="mb-3">
                    <i class="fas fa-user-circle fa-5x text-primary"></i>
                </div>
                <h4><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h4>
                <p class="text-muted mb-2"><?= htmlspecialchars($user['email']) ?></p>
                <span class="badge bg-primary text-uppercase px-3 py-2">
                    <?= str_replace('_', ' ', $user['role']) ?>
                </span>

                <hr class="my-4">

                <div class="text-start">
                    <p class="mb-2"><strong><i class="fas fa-calendar-alt text-muted me-2"></i>Date of Birth:</strong> <?= $user['dob'] ? date('M j, Y', strtotime($user['dob'])) : 'Not set' ?></p>
                    <p class="mb-2"><strong><i class="fas fa-venus-mars text-muted me-2"></i>Gender:</strong> <?= ucfirst($user['gender'] ?? 'Not specified') ?></p>
                    <p class="mb-2"><strong><i class="fas fa-shield-alt text-muted me-2"></i>Status:</strong> <span class="badge bg-success"><?= ucfirst($user['status']) ?></span></p>
                    <p class="mb-0"><strong><i class="fas fa-clock text-muted me-2"></i>Member Since:</strong> <?= date('M Y', strtotime($user['created_at'])) ?></p>
                </div>
            </div>
        </div>

        <?php if ($student_profile && $student_profile['is_minor']): ?>
        <div class="card shadow-sm border-0 border-start border-warning border-4">
            <div class="card-body">
                <h5 class="card-title text-warning"><i class="fas fa-user-shield me-2"></i>DPDP Act 2023 Compliance</h5>
                <p class="small text-muted mb-2">This account belongs to a minor with Verifiable Parental Consent (VPC).</p>
                <ul class="list-unstyled small mb-0">
                    <li><strong>Parent/Guardian:</strong> <?= htmlspecialchars($student_profile['parent_name'] ?: 'Not recorded') ?></li>
                    <li><strong>Parent Contact:</strong> <?= htmlspecialchars($student_profile['parent_contact'] ?: 'Not recorded') ?></li>
                    <li><strong>Consent Method:</strong> <span class="badge bg-info text-uppercase"><?= str_replace('_', ' ', $student_profile['parent_consent_method'] ?: 'Pending') ?></span></li>
                    <li><strong>Artifact Ref:</strong> <code><?= htmlspecialchars($student_profile['parent_consent_artifact'] ?: 'N/A') ?></code></li>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Edit Profile & Change Password Forms -->
    <div class="col-md-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-edit me-2 text-primary"></i>Edit Profile Details</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <input type="hidden" name="update_profile" value="1">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Email Address (Read-only)</label>
                            <input type="email" class="form-control bg-light" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Emergency Contact Info</label>
                        <input type="text" name="emergency_contact" class="form-control" value="<?= htmlspecialchars($user['emergency_contact'] ?? '') ?>" placeholder="Name & Phone Number">
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save Profile Changes</button>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-key me-2 text-danger"></i>Change Password</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <input type="hidden" name="change_password" value="1">

                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" minlength="8" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" minlength="8" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-outline-danger"><i class="fas fa-shield-alt me-2"></i>Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
