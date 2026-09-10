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

        if (!can_user_change_password($user)) {
            $error_message = 'You have already reached the maximum 1 direct password change. Please submit a request to the Master Portal below.';
        } elseif (!password_verify($current_pass, $user['password_hash'])) {
            $error_message = 'Current password is incorrect.';
        } elseif (strlen($new_pass) < 8) {
            $error_message = 'New password must be at least 8 characters long.';
        } elseif ($new_pass !== $confirm_pass) {
            $error_message = 'New passwords do not match.';
        } elseif (password_verify($new_pass, $user['password_hash'])) {
            $error_message = 'New password must be different from your current password.';
        } else {
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $new_count = ((int)($user['password_change_count'] ?? 0)) + 1;
            $update = $pdo->prepare('UPDATE users SET password_hash = ?, password_change_count = ?, must_change_password = 0, updated_at = NOW() WHERE id = ?');
            if ($update->execute([$new_hash, $new_count, $user_id])) {
                try {
                    log_audit_action($pdo, $user_id, 'UPDATE', 'auth', $user_id, 'User changed account password (Total changes: ' . $new_count . ')');
                } catch (Throwable $e) {
                    // Audit failure must not break a successful password change.
                }
                $_SESSION['success_msg'] = 'Password changed successfully! You have completed your 1-time direct password change. Future password changes must be requested through the Master Portal.';
                redirect('/profile.php');
            }
            $error_message = 'Unable to change your password right now.';
        }
    } elseif (isset($_POST['request_password_reset'])) {
        $reason = trim($_POST['reset_reason'] ?? '');
        if ($reason === '') {
            $error_message = 'Please provide a reason for the password change request.';
        } else {
            $chk = $pdo->prepare("SELECT id FROM password_reset_requests WHERE user_id = ? AND status = 'pending' LIMIT 1");
            $chk->execute([$user_id]);
            if ($chk->fetch()) {
                $error_message = 'You already have a pending password reset request awaiting Master approval.';
            } else {
                $dojo_stmt = $pdo->prepare("SELECT dojo_id FROM dojo_memberships WHERE student_id = ? ORDER BY id DESC LIMIT 1");
                $dojo_stmt->execute([$user_id]);
                $student_dojo_id = (int)$dojo_stmt->fetchColumn() ?: null;

                $ins = $pdo->prepare("INSERT INTO password_reset_requests (user_id, dojo_id, reason, status) VALUES (?, ?, ?, 'pending')");
                if ($ins->execute([$user_id, $student_dojo_id, $reason])) {
                    try {
                        log_audit_action($pdo, $user_id, 'CREATE', 'password_request', $pdo->lastInsertId(), 'Student submitted password change request to Master Portal');
                    } catch (Throwable $e) {}
                    $_SESSION['success_msg'] = 'Your password reset request has been submitted to the Master Portal! Your Dojo Master will review it.';
                    redirect('/profile.php');
                } else {
                    $error_message = 'Unable to submit request right now. Please try again.';
                }
            }
        }
    }
}

$latest_password_request = null;
if ($user['role'] === 'student') {
    $req_stmt = $pdo->prepare("SELECT * FROM password_reset_requests WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $req_stmt->execute([$user_id]);
    $latest_password_request = $req_stmt->fetch();
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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div><i class="fas fa-lock text-danger me-2"></i>Security &amp; Password</div>
                    <?php if ($user['role'] === 'student'): ?>
                        <?php if (can_user_change_password($user)): ?>
                            <span class="badge bg-success"><i class="fas fa-shield-check me-1"></i> 1 Direct Change Allowed</span>
                        <?php else: ?>
                            <span class="badge bg-danger"><i class="fas fa-lock me-1"></i> 1-Time Change Limit Reached</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if ($user['role'] === 'student' && !can_user_change_password($user)): ?>
                        <!-- 1-Time Limit Reached: Must Request Master Portal -->
                        <div class="alert alert-dark border-danger mb-4" style="background: rgba(198, 26, 26, 0.08); border-left: 4px solid #c61a1a;">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-shield-halved fa-2x text-danger me-3 mt-1"></i>
                                <div>
                                    <h6 class="fw-bold text-danger mb-1">Direct Password Change Limit Reached</h6>
                                    <p class="small text-muted mb-0">
                                        As a student, you have already used your <strong>1-time direct password change</strong>. 
                                        To change or reset your password again, academy policy requires requesting approval from the <strong>Master Portal</strong>.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <?php if ($latest_password_request && $latest_password_request['status'] === 'pending'): ?>
                            <div class="card border-warning mb-3">
                                <div class="card-body bg-light">
                                    <div class="d-flex align-items-center">
                                        <div class="spinner-border spinner-border-sm text-warning me-2" role="status"></div>
                                        <h6 class="mb-0 fw-bold text-dark">Password Reset Request Pending Review</h6>
                                    </div>
                                    <p class="small text-muted mt-2 mb-1">
                                        Submitted on: <strong><?= date('M d, Y - h:i A', strtotime($latest_password_request['created_at'])) ?></strong>
                                    </p>
                                    <div class="small p-2 bg-white rounded border">
                                        <strong>Your submitted reason:</strong> <?= htmlspecialchars($latest_password_request['reason'] ?? 'Not specified') ?>
                                    </div>
                                    <div class="small text-secondary mt-2">
                                        <i class="fas fa-info-circle text-warning me-1"></i> Your Dojo Master / Sensei will review and approve this request shortly.
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php if ($latest_password_request && $latest_password_request['status'] === 'rejected'): ?>
                                <div class="alert alert-warning small mb-3">
                                    <i class="fas fa-circle-exclamation me-1"></i> Previous request rejected: <?= htmlspecialchars($latest_password_request['master_notes'] ?? 'Please contact your Sensei.') ?>
                                </div>
                            <?php endif; ?>

                            <form method="post">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                <input type="hidden" name="request_password_reset" value="1">
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Reason for Password Change Request <span class="text-danger">*</span></label>
                                    <textarea name="reset_reason" class="form-control" rows="3" placeholder="e.g. Forgot password, device change, or routine security reset" required></textarea>
                                    <div class="form-text">Your request will be routed directly to your Dojo Master in the Master Portal.</div>
                                </div>
                                <button type="submit" class="btn btn-warning px-4 fw-bold">
                                    <i class="fas fa-paper-plane me-2"></i>Submit Request to Master Portal
                                </button>
                            </form>
                        <?php endif; ?>

                    <?php else: ?>
                        <!-- Allowed to Change Password Directly -->
                        <?php if ($user['role'] === 'student'): ?>
                            <div class="alert alert-info d-flex align-items-center mb-3">
                                <i class="fas fa-info-circle fa-2x text-primary me-3"></i>
                                <div class="small">
                                    <strong>Default Password Policy:</strong> Your initial password is your <strong>Date of Birth (DD.MM.YYYY)</strong>. 
                                    You can change it <strong>once</strong> directly. Subsequent changes will require a request to the <strong>Master Portal</strong>.
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="security-box mb-3">
                                <strong>Protect your account.</strong>
                                <div class="small text-muted mt-1">Use a unique password of at least 8 characters. Changing your password updates the stored password hash.</div>
                            </div>
                        <?php endif; ?>

                        <form method="post" autocomplete="off">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                            <input type="hidden" name="change_password" value="1">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Current Password</label>
                                <input type="password" name="current_password" class="form-control" autocomplete="current-password" placeholder="<?= $user['role'] === 'student' && ((int)($user['password_change_count'] ?? 0) === 0) ? 'Enter your Date of Birth (DD.MM.YYYY)' : 'Enter current password' ?>" required>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">New Password</label>
                                    <input type="password" name="new_password" class="form-control" minlength="8" autocomplete="new-password" placeholder="At least 8 characters" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Confirm New Password</label>
                                    <input type="password" name="confirm_password" class="form-control" minlength="8" autocomplete="new-password" placeholder="Repeat new password" required>
                                </div>
                            </div>
                            <button class="btn btn-outline-danger mt-4 px-4">
                                <i class="fas fa-key me-2"></i>Update Password <?= $user['role'] === 'student' ? '(1-Time Direct Change)' : '' ?>
                            </button>
                        </form>
                    <?php endif; ?>
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
