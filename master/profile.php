<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('master');

$master_id = (int)$_SESSION['user_id'];
$error = '';
$success = $_SESSION['success_msg'] ?? '';
unset($_SESSION['success_msg']);

$ensure = [
    'alternate_phone' => "ALTER TABLE users ADD COLUMN alternate_phone VARCHAR(30) NULL AFTER phone",
    'bio' => "ALTER TABLE users ADD COLUMN bio TEXT NULL AFTER address"
];
foreach ($ensure as $column => $sql) {
    try {
        $pdo->query("SELECT {$column} FROM users LIMIT 1");
    } catch (Throwable $e) {
        try { $pdo->exec($sql); } catch (Throwable $ignored) {}
    }
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'master' LIMIT 1");
$stmt->execute([$master_id]);
$master = $stmt->fetch();
if (!$master) {
    logout_user($pdo);
}

$stmt = $pdo->prepare("SELECT * FROM dojos WHERE master_id = ? LIMIT 1");
$stmt->execute([$master_id]);
$dojo = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'profile') {
            $first = trim(sanitize_input($_POST['first_name'] ?? ''));
            $last = trim(sanitize_input($_POST['last_name'] ?? ''));
            $phone = trim(sanitize_input($_POST['phone'] ?? ''));
            $alternate = trim(sanitize_input($_POST['alternate_phone'] ?? ''));
            $address = trim(sanitize_input($_POST['address'] ?? ''));
            $bio = trim(sanitize_input($_POST['bio'] ?? ''));

            if ($first === '' || $last === '') {
                $error = 'First name and last name are required.';
            } elseif ($phone !== '' && !preg_match('/^[0-9+()\-\s]{7,20}$/', $phone)) {
                $error = 'Please enter a valid phone number.';
            } elseif ($alternate !== '' && !preg_match('/^[0-9+()\-\s]{7,20}$/', $alternate)) {
                $error = 'Please enter a valid alternate phone number.';
            } else {
                $update = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ?, alternate_phone = ?, address = ?, bio = ?, updated_at = NOW() WHERE id = ? AND role = 'master'");
                $update->execute([$first, $last, $phone !== '' ? $phone : null, $alternate !== '' ? $alternate : null, $address !== '' ? $address : null, $bio !== '' ? $bio : null, $master_id]);
                $_SESSION['user_name'] = $first . ' ' . $last;
                log_audit_action($pdo, $master_id, 'UPDATE', 'users', $master_id, 'Updated Master profile');
                $_SESSION['success_msg'] = 'Profile updated successfully.';
                redirect('/master/profile.php');
            }
        }

        if ($action === 'dojo' && $dojo) {
            $name = trim(sanitize_input($_POST['dojo_name'] ?? ''));
            $location = trim(sanitize_input($_POST['location'] ?? ''));
            $description = trim(sanitize_input($_POST['description'] ?? ''));
            $phone = trim(sanitize_input($_POST['dojo_phone'] ?? ''));
            $email = trim(sanitize_input($_POST['dojo_email'] ?? ''));

            if ($name === '') {
                $error = 'Dojo name is required.';
            } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid dojo email address.';
            } else {
                $update = $pdo->prepare("UPDATE dojos SET name = ?, location = ?, description = ?, phone = ?, email = ? WHERE id = ? AND master_id = ?");
                $update->execute([$name, $location !== '' ? $location : null, $description !== '' ? $description : null, $phone !== '' ? $phone : null, $email !== '' ? $email : null, (int)$dojo['id'], $master_id]);
                log_audit_action($pdo, $master_id, 'UPDATE', 'dojos', (int)$dojo['id'], 'Updated dojo profile');
                $_SESSION['success_msg'] = 'Dojo details updated successfully.';
                redirect('/master/profile.php');
            }
        }

        if ($action === 'password') {
            $current = $_POST['current_password'] ?? '';
            $new = $_POST['new_password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';

            if (!password_verify($current, $master['password_hash'])) {
                $error = 'Current password is incorrect.';
            } elseif (strlen($new) < 8) {
                $error = 'New password must be at least 8 characters.';
            } elseif ($new !== $confirm) {
                $error = 'New passwords do not match.';
            } else {
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $update = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ? AND role = 'master'");
                $update->execute([$hash, $master_id]);
                log_audit_action($pdo, $master_id, 'UPDATE', 'users', $master_id, 'Changed Master password');
                $_SESSION['success_msg'] = 'Password changed successfully.';
                redirect('/master/profile.php');
            }
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$master_id]);
$master = $stmt->fetch();
$stmt = $pdo->prepare("SELECT * FROM dojos WHERE master_id = ? LIMIT 1");
$stmt->execute([$master_id]);
$dojo = $stmt->fetch();

$page_title = 'Master Profile';
require_once '../includes/header.php';
?>
<style>
.mp-wrap{max-width:1180px;margin:1.5rem auto}.mp-hero{background:linear-gradient(135deg,#080808,#202020 60%,#5b0b0b);color:#fff;border-radius:24px;padding:1.8rem 2rem;box-shadow:0 22px 52px rgba(0,0,0,.15)}.mp-kicker{text-transform:uppercase;letter-spacing:.14em;font-size:.72rem;font-weight:800;color:#ffd45d}.mp-title{font-size:clamp(1.8rem,4vw,2.6rem);font-weight:900;margin:.25rem 0}.mp-card{border:0;border-radius:20px;overflow:hidden;box-shadow:0 14px 38px rgba(17,24,39,.08)}.mp-card .card-header{background:#fff;border:0;padding:1.2rem 1.3rem}.mp-card .card-body{padding:1.3rem;background:#fff}.form-control{border-radius:12px;padding:.72rem .85rem}.label{font-size:.84rem;font-weight:800}.identity{border-radius:18px;padding:1.25rem;background:#f7f7f7}.identity-id{font-family:ui-monospace,Consolas,monospace;font-weight:800;font-size:.82rem;word-break:break-word}.meta{color:#777;font-size:.82rem}.danger-zone{background:#fff4f4;border:1px solid #ffd9d9;border-radius:15px;padding:1rem}.section-note{color:#777;font-size:.83rem}.nav-pills .nav-link{border-radius:999px;font-weight:700;color:#333}.nav-pills .nav-link.active{background:#111}
</style>
<div class="mp-wrap">
    <section class="mp-hero mb-4">
        <div class="mp-kicker">Master Control • Account</div>
        <div class="mp-title">Master Profile & Dojo Settings</div>
        <p class="mb-0" style="color:rgba(255,255,255,.72)">Manage your personal details, dojo information and account security from one place.</p>
    </section>

    <?php if ($error): ?><div class="alert alert-danger border-0 shadow-sm mb-4"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success border-0 shadow-sm mb-4"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card mp-card h-100">
                <div class="card-body">
                    <div class="identity">
                        <div class="small text-muted text-uppercase fw-bold">KOMS Master Account</div>
                        <h4 class="fw-black mt-2 mb-1"><?= htmlspecialchars($master['first_name'].' '.$master['last_name']) ?></h4>
                        <div class="meta mb-3"><?= htmlspecialchars($master['email']) ?></div>
                        <div class="small text-muted">KOMS User ID</div>
                        <div class="identity-id mt-1"><?= htmlspecialchars($master['member_id'] ?: ('KOMS-'.$master['id'])) ?></div>
                    </div>
                    <div class="mt-4">
                        <div class="small text-muted text-uppercase fw-bold mb-2">Role</div>
                        <span class="badge bg-dark rounded-pill px-3 py-2">MASTER</span>
                    </div>
                    <?php if ($dojo): ?>
                    <div class="mt-4">
                        <div class="small text-muted text-uppercase fw-bold mb-1">Approved Dojo</div>
                        <div class="fw-bold"><?= htmlspecialchars($dojo['name']) ?></div>
                        <div class="meta"><?= htmlspecialchars($dojo['location'] ?: 'Location not set') ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <ul class="nav nav-pills gap-2 mb-3">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#personal" type="button">Personal</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#dojo" type="button">Dojo</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#security" type="button">Security</button></li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="personal">
                    <div class="card mp-card">
                        <div class="card-header"><h5 class="mb-1">Personal Information</h5><div class="section-note">Update the details shown in your Master Portal account.</div></div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                <input type="hidden" name="action" value="profile">
                                <div class="row g-3">
                                    <div class="col-md-6"><label class="label mb-1">First Name</label><input name="first_name" class="form-control" value="<?= htmlspecialchars($master['first_name']) ?>" required></div>
                                    <div class="col-md-6"><label class="label mb-1">Last Name</label><input name="last_name" class="form-control" value="<?= htmlspecialchars($master['last_name']) ?>" required></div>
                                    <div class="col-md-6"><label class="label mb-1">Email</label><input class="form-control" value="<?= htmlspecialchars($master['email']) ?>" disabled></div>
                                    <div class="col-md-6"><label class="label mb-1">Date of Birth</label><input class="form-control" value="<?= htmlspecialchars($master['dob'] ?: 'Not set') ?>" disabled></div>
                                    <div class="col-md-6"><label class="label mb-1">Phone</label><input name="phone" class="form-control" value="<?= htmlspecialchars($master['phone'] ?? '') ?>"></div>
                                    <div class="col-md-6"><label class="label mb-1">Alternate Phone</label><input name="alternate_phone" class="form-control" value="<?= htmlspecialchars($master['alternate_phone'] ?? '') ?>"></div>
                                    <div class="col-12"><label class="label mb-1">Address</label><textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($master['address'] ?? '') ?></textarea></div>
                                    <div class="col-12"><label class="label mb-1">Bio / Instructor Profile</label><textarea name="bio" class="form-control" rows="4" maxlength="1500" placeholder="Training experience, specialization, achievements..."><?= htmlspecialchars($master['bio'] ?? '') ?></textarea></div>
                                </div>
                                <button class="btn btn-dark mt-4"><i class="fas fa-save me-2"></i>Save Personal Details</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="dojo">
                    <div class="card mp-card">
                        <div class="card-header"><h5 class="mb-1">Dojo Information</h5><div class="section-note">These details are used across your Master Portal dojo modules.</div></div>
                        <div class="card-body">
                            <?php if ($dojo): ?>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                <input type="hidden" name="action" value="dojo">
                                <div class="row g-3">
                                    <div class="col-12"><label class="label mb-1">Dojo Name</label><input name="dojo_name" class="form-control" value="<?= htmlspecialchars($dojo['name']) ?>" required></div>
                                    <div class="col-md-6"><label class="label mb-1">Location</label><input name="location" class="form-control" value="<?= htmlspecialchars($dojo['location'] ?? '') ?>"></div>
                                    <div class="col-md-6"><label class="label mb-1">Phone</label><input name="dojo_phone" class="form-control" value="<?= htmlspecialchars($dojo['phone'] ?? '') ?>"></div>
                                    <div class="col-12"><label class="label mb-1">Email</label><input type="email" name="dojo_email" class="form-control" value="<?= htmlspecialchars($dojo['email'] ?? '') ?>"></div>
                                    <div class="col-12"><label class="label mb-1">Description</label><textarea name="description" class="form-control" rows="5"><?= htmlspecialchars($dojo['description'] ?? '') ?></textarea></div>
                                </div>
                                <button class="btn btn-dark mt-4"><i class="fas fa-building me-2"></i>Save Dojo Details</button>
                            </form>
                            <?php else: ?>
                                <div class="alert alert-warning mb-0">No approved dojo is linked to this Master account yet.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="security">
                    <div class="card mp-card">
                        <div class="card-header"><h5 class="mb-1">Change Password</h5><div class="section-note">Use a strong password and do not reuse it elsewhere.</div></div>
                        <div class="card-body">
                            <form method="POST" autocomplete="off">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                                <input type="hidden" name="action" value="password">
                                <div class="mb-3"><label class="label mb-1">Current Password</label><input type="password" name="current_password" class="form-control" required></div>
                                <div class="mb-3"><label class="label mb-1">New Password</label><input type="password" name="new_password" class="form-control" minlength="8" required></div>
                                <div class="mb-3"><label class="label mb-1">Confirm New Password</label><input type="password" name="confirm_password" class="form-control" minlength="8" required></div>
                                <button class="btn btn-dark"><i class="fas fa-lock me-2"></i>Change Password</button>
                            </form>
                            <div class="danger-zone mt-4"><div class="fw-bold text-danger"><i class="fas fa-shield-halved me-2"></i>Account Security</div><div class="small text-muted mt-1">Your account uses the shared KOMS authentication system and role-based access controls.</div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>