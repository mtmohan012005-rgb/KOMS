<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

require_login();

$user_id = (int)$_SESSION['user_id'];
$page_title = 'My Profile';

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    redirect('/logout.php');
}

$student_profile = null;
if ($user['role'] === 'student') {
    $sp_stmt = $pdo->prepare('SELECT * FROM student_profiles WHERE user_id = ? LIMIT 1');
    $sp_stmt->execute([$user_id]);
    $student_profile = $sp_stmt->fetch();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid form submission. Please refresh and try again.';
    } elseif (isset($_POST['update_profile'])) {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $emergency_contact = trim($_POST['emergency_contact'] ?? '');

        if ($first_name === '' || $last_name === '') {
            $error_message = 'First name and last name are required.';
        } elseif (mb_strlen($first_name) > 100 || mb_strlen($last_name) > 100) {
            $error_message = 'Name fields are too long.';
        } else {
            $update = $pdo->prepare('UPDATE users SET first_name = ?, last_name = ?, phone = ?, address = ?, emergency_contact = ? WHERE id = ?');
            if ($update->execute([$first_name, $last_name, $phone, $address, $emergency_contact, $user_id])) {
                $_SESSION['user_name'] = $first_name . ' ' . $last_name;
                try {
                    log_audit_action($pdo, $user_id, 'UPDATE', 'profile', $user_id, 'User updated personal profile details');
                } catch (Throwable $e) {
                    // Audit failure must not break a successful profile update.
                }
                $_SESSION['success_msg'] = 'Profile details updated successfully.';
                redirect('/profile.php');
            }
            $error_message = 'Unable to update your profile right now.';
        }
    } elseif (isset($_POST['change_password'])) {
        $current_pass = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        if (!password_verify($current_pass, $user['password_hash'])) {
            $error_message = 'Current password is incorrect.';
        } elseif (strlen($new_pass) < 8) {
            $error_message = 'New password must be at least 8 characters long.';
        } elseif ($new_pass !== $confirm_pass) {
            $error_message = 'New passwords do not match.';
        } elseif (password_verify($new_pass, $user['password_hash'])) {
            $error_message = 'New password must be different from your current password.';
        } else {
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $update = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            if ($update->execute([$new_hash, $user_id])) {
                try {
                    log_audit_action($pdo, $user_id, 'UPDATE', 'auth', $user_id, 'User changed account password');
                } catch (Throwable $e) {
                    // Audit failure must not break a successful password change.
                }
                $_SESSION['success_msg'] = 'Password changed successfully.';
                redirect('/profile.php');
            }
            $error_message = 'Unable to change your password right now.';
        }
    }
}

$dash_url = '/index.php';
switch ($user['role']) {
    case 'super_admin': $dash_url = '/admin/dashboard.php'; break;
    case 'master': $dash_url = '/master/dashboard.php'; break;
    case 'senior': $dash_url = '/senior/dashboard.php'; break;
    case 'student': $dash_url = '/student/dashboard.php'; break;
}

require_once 'includes/header.php';
?>

<style>
    .profile-page{max-width:1180px;margin:1.5rem auto 3rem}.profile-hero{background:linear-gradient(135deg,#090909,#1b1b1b 60%,#4a0909);color:#fff;border-radius:24px;padding:1.8rem;position:relative;overflow:hidden;box-shadow:0 20px 55px rgba(0,0,0,.16)}
    .profile-hero:after{content:'';position:absolute;right:-80px;top:-100px;width:260px;height:260px;border-radius:50%;background:radial-gradient(circle,rgba(229,9,20,.35),transparent 68%)}
    .avatar{width:82px;height:82px;border-radius:24px;background:linear-gradient(135deg,#fff,#e7e7e7);color:#111;display:flex;align-items:center;justify-content:center;font-size:1.7rem;font-weight:900;flex:0 0 auto}
    .eyebrow{font-size:.72rem;letter-spacing:.14em;text-transform:uppercase;font-weight:800;color:#f4bd17}.hero-title{font-size:clamp(1.8rem,4vw,2.8rem);font-weight:900;margin:.3rem 0}.hero-copy{color:rgba(255,255,255,.72);margin:0}.profile-card{border:0;border-radius:20px;overflow:hidden;box-shadow:0 14px 38px rgba(17,24,39,.08)}.profile-card .card-header{background:#fff;border:0;padding:1.1rem 1.25rem;font-weight:800}.profile-card .card-body{padding:1.35rem}
    .info-pill{background:#f7f7f7;border-radius:14px;padding:.9rem 1rem;margin-bottom:.65rem}.info-label{font-size:.68rem;text-transform:uppercase;letter-spacing:.08em;color:#888;font-weight:800}.info-value{font-weight:700;margin-top:.15rem;word-break:break-word}.role-badge{display:inline-flex;align-items:center;border-radius:999px;padding:.4rem .7rem;background:#111;color:#fff;font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em}.security-box{background:linear-gradient(135deg,#fff8f8,#fff);border:1px solid rgba(229,9,20,.1);border-radius:16px;padding:1rem}.btn-dark-red{background:linear-gradient(90deg,#9d0b0b,#e02323);border:0;color:#fff}.btn-dark-red:hover{color:#fff;opacity:.94}.minor-box{border-left:4px solid #f4bd17;background:#fffaf0}
    @media(max-width:767px){.profile-page{margin-top:1rem}.profile-hero{padding:1.25rem;border-radius:18px}.avatar{width:66px;height:66px;border-radius:19px}}
</style>

<div class="profile-page">
    <section class="profile-hero mb-4">
        <div class="d-flex flex-wrap align-items-center gap-3 position-relative" style="z-index:2">
            <div class="avatar">
                <?= htmlspecialchars(strtoupper(substr($user['first_name'] ?? 'U',0,1).substr($user['last_name'] ?? '',0,1)) ?: 'U') ?>
            </div>
            <div>
                <div class="eyebrow">KOMS • Account Center</div>
                <div class="hero-title">My Profile & Security</div>
                <p class="hero-copy">Keep your account details current and protect your KOMS credentials.</p>
            </div>
        </div>
    </section>

    <?php if ($error_message): ?>
        <div class="alert alert-danger profile-card border-0 mb-4"><i class="fas fa-circle-exclamation me-2"></i><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card profile-card mb-4">
                <div class="card-body text-center">
                    <div class="avatar mx-auto mb-3"><?= htmlspecialchars(strtoupper(substr($user['first_name'] ?? 'U',0,1).substr($user['last_name'] ?? '',0,1)) ?: 'U') ?></div>
                    <h3 class="fw-bold mb-1"><?= htmlspecialchars($user['first_name'].' '.$user['last_name']) ?></h3>
                    <p class="text-muted mb-3"><?= htmlspecialchars($user['email']) ?></p>
                    <span class="role-badge"><i class="fas fa-user-shield me-2"></i><?= htmlspecialchars(str_replace('_',' ',$user['role'])) ?></span>
                    <hr class="my-4">
                    <div class="text-start">
                        <div class="info-pill"><div class="info-label">Phone</div><div class="info-value"><?= htmlspecialchars($user['phone'] ?: 'Not set') ?></div></div>
                        <div class="info-pill"><div class="info-label">Date of Birth</div><div class="info-value"><?= $user['dob'] ? date('M j, Y',strtotime($user['dob'])) : 'Not set' ?></div></div>
                        <div class="info-pill"><div class="info-label">Gender</div><div class="info-value"><?= htmlspecialchars(ucfirst($user['gender'] ?: 'Not specified')) ?></div></div>
                        <div class="info-pill"><div class="info-label">Account Status</div><div class="info-value"><span class="badge bg-success"><?= htmlspecialchars(ucfirst($user['status'])) ?></span></div></div>
                        <div class="info-pill mb-0"><div class="info-label">Member Since</div><div class="info-value"><?= date('M Y',strtotime($user['created_at'])) ?></div></div>
                    </div>
                </div>
            </div>

            <?php if ($student_profile && $student_profile['is_minor']): ?>
                <div class="card profile-card minor-box">
                    <div class="card-body">
                        <h5 class="fw-bold text-warning-emphasis"><i class="fas fa-user-shield me-2"></i>Minor Account</h5>
                        <p class="small text-muted">Parental consent information recorded for this student profile.</p>
                        <div class="small"><strong>Parent/Guardian:</strong> <?= htmlspecialchars($student_profile['parent_name'] ?: 'Not recorded') ?></div>
                        <div class="small mt-1"><strong>Consent:</strong> <?= htmlspecialchars(ucfirst($student_profile['parent_consent_status'])) ?></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-8">
            <div class="card profile-card mb-4">
                <div class="card-header"><i class="fas fa-user-pen text-danger me-2"></i>Personal Information</div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                        <input type="hidden" name="update_profile" value="1">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label fw-semibold">First Name</label><input name="first_name" class="form-control" maxlength="100" value="<?= htmlspecialchars($user['first_name']) ?>" required></div>
                            <div class="col-md-6"><label class="form-label fw-semibold">Last Name</label><input name="last_name" class="form-control" maxlength="100" value="<?= htmlspecialchars($user['last_name']) ?>" required></div>
                            <div class="col-md-6"><label class="form-label fw-semibold">Email</label><input type="email" class="form-control bg-light" value="<?= htmlspecialchars($user['email']) ?>" readonly></div>
                            <div class="col-md-6"><label class="form-label fw-semibold">Phone</label><input name="phone" class="form-control" maxlength="20" value="<?= htmlspecialchars($user['phone'] ?? '') ?>"></div>
                            <div class="col-12"><label class="form-label fw-semibold">Address</label><textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($user['address'] ?? '') ?></textarea></div>
                            <div class="col-12"><label class="form-label fw-semibold">Emergency Contact</label><input name="emergency_contact" class="form-control" maxlength="100" value="<?= htmlspecialchars($user['emergency_contact'] ?? '') ?>" placeholder="Name and phone number"></div>
                        </div>
                        <button class="btn btn-dark-red mt-4 px-4"><i class="fas fa-check me-2"></i>Save Changes</button>
                    </form>
                </div>
            </div>

            <div class="card profile-card mb-4">
                <div class="card-header"><i class="fas fa-lock text-danger me-2"></i>Security</div>
                <div class="card-body">
                    <div class="security-box mb-3"><strong>Protect your account.</strong><div class="small text-muted mt-1">Use a unique password of at least 8 characters. Changing your password updates the stored password hash.</div></div>
                    <form method="post" autocomplete="off">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                        <input type="hidden" name="change_password" value="1">
                        <div class="mb-3"><label class="form-label fw-semibold">Current Password</label><input type="password" name="current_password" class="form-control" autocomplete="current-password" required></div>
                        <div class="row g-3"><div class="col-md-6"><label class="form-label fw-semibold">New Password</label><input type="password" name="new_password" class="form-control" minlength="8" autocomplete="new-password" required></div><div class="col-md-6"><label class="form-label fw-semibold">Confirm New Password</label><input type="password" name="confirm_password" class="form-control" minlength="8" autocomplete="new-password" required></div></div>
                        <button class="btn btn-outline-danger mt-4 px-4"><i class="fas fa-key me-2"></i>Update Password</button>
                    </form>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a href="<?= htmlspecialchars(APP_URL.$dash_url) ?>" class="btn btn-outline-dark"><i class="fas fa-arrow-left me-2"></i>Back to Dashboard</a>
                <a href="<?= htmlspecialchars(APP_URL.'/logout.php') ?>" class="btn btn-outline-secondary"><i class="fas fa-right-from-bracket me-2"></i>Sign Out</a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
