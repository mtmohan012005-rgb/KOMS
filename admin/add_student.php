<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('super_admin');

$page_title = 'Add Student';
$error = '';
$created = false;
$generated_email = 'lsairohan@koms.local';
$generated_password = 'KOMS@SaiRohan2026';

function add_student_ensure_profile_fields(PDO $pdo): void {
    $columns = [
        'alternate_mobile' => 'VARCHAR(20) NULL',
        'blood_group' => 'VARCHAR(20) NULL',
        'father_name' => 'VARCHAR(150) NULL',
        'mother_name' => 'VARCHAR(150) NULL',
        'date_of_joining' => 'DATE NULL'
    ];
    foreach ($columns as $column => $definition) {
        $check = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'student_profiles' AND column_name = ?");
        $check->execute([$column]);
        if ((int)$check->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE student_profiles ADD COLUMN `$column` $definition");
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security token mismatch. Please reload and try again.';
    } else {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $dob = $_POST['dob'] ?? null;
        $gender = $_POST['gender'] ?? 'male';
        $phone = trim($_POST['phone'] ?? '');
        $alternate_mobile = trim($_POST['alternate_mobile'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $father_name = trim($_POST['father_name'] ?? '');
        $mother_name = trim($_POST['mother_name'] ?? '');
        $blood_group = trim($_POST['blood_group'] ?? '');
        $date_of_joining = $_POST['date_of_joining'] ?? null;
        $email = strtolower(trim($_POST['email'] ?? $generated_email));
        $password = $_POST['password'] ?? $generated_password;

        if ($first_name === '' || $last_name === '' || !$dob || $email === '' || $password === '') {
            $error = 'Please complete all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid login email address.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } else {
            try {
                add_student_ensure_profile_fields($pdo);

                $exists = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
                $exists->execute([$email]);
                if ($exists->fetch()) {
                    $error = 'That login email already exists. Use a different email.';
                } else {
                    $pdo->beginTransaction();

                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare('INSERT INTO users (first_name, last_name, email, password_hash, role, dob, gender, phone, address, status) VALUES (?, ?, ?, ?, \'student\', ?, ?, ?, ?, \'active\')');
                    $stmt->execute([$first_name, $last_name, $email, $password_hash, $dob, $gender, $phone, $address]);
                    $new_user_id = (int)$pdo->lastInsertId();

                    $profile = $pdo->prepare('INSERT INTO student_profiles (user_id, is_minor, parent_name, parent_contact, parent_email, parent_consent_status, alternate_mobile, blood_group, father_name, mother_name, date_of_joining, medical_notes) VALUES (?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?)');
                    $is_minor = (strtotime($dob) > strtotime('-18 years')) ? 1 : 0;
                    $parent_name = trim('Father: ' . $father_name . ' | Mother: ' . $mother_name);
                    $parent_contact = $alternate_mobile;
                    $consent_status = $is_minor ? 'pending' : 'not_applicable';
                    $medical_notes = $blood_group !== '' ? 'Blood group: ' . $blood_group : null;
                    $profile->execute([$new_user_id, $is_minor, $parent_name, $parent_contact, $consent_status, $alternate_mobile, $blood_group, $father_name, $mother_name, $date_of_joining ?: null, $medical_notes]);

                    try {
                        log_audit_action($pdo, (int)$_SESSION['user_id'], 'create', 'users', $new_user_id, 'Created student account for ' . $first_name . ' ' . $last_name);
                    } catch (Throwable $audit_error) {
                        // Account creation remains successful even if audit logging is unavailable.
                    }

                    $pdo->commit();
                    $created = true;
                    $generated_email = $email;
                    $generated_password = $password;
                }
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Unable to create student: ' . $e->getMessage();
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Add Student • KOMS</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
body{background:#f4f5f7;font-family:Inter,Arial,sans-serif}.page{max-width:1050px;margin:32px auto;padding:0 16px}.box{background:#fff;border-radius:22px;box-shadow:0 18px 50px rgba(0,0,0,.08);border:1px solid #ececef}.hero{background:linear-gradient(135deg,#111,#2d0909);color:#fff;padding:26px;border-radius:22px 22px 0 0}.hero h1{font-weight:900;margin:0}.muted{color:#888}.form-label{font-size:.78rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em}.login-box{background:#f8f8f9;border-radius:15px;padding:16px}.login-box code{font-size:1rem}.btn-danger{background:#b51218;border-color:#b51218}.success{border-left:5px solid #16a36a;background:#eefaf4}.error{border-left:5px solid #df3a44;background:#fff1f2}
</style></head>
<body>
<div class="page"><div class="box">
<div class="hero"><div class="text-uppercase small opacity-75">Mass Dragon Dojo • KOMS</div><h1>Add Student</h1><div class="mt-2 opacity-75">Create a student login and profile from one secure form.</div></div>
<div class="p-4 p-lg-5">
<?php if ($created): ?>
<div class="alert success"><h5 class="fw-bold text-success">Student created successfully</h5><p class="mb-2">The new student account has been stored in the KOMS database.</p><div class="login-box"><div><span class="muted">Login email</span><br><code><?= htmlspecialchars($generated_email) ?></code></div><div class="mt-3"><span class="muted">Temporary password</span><br><code><?= htmlspecialchars($generated_password) ?></code></div><div class="small text-muted mt-3">This email is a KOMS login identity; it is not an externally hosted mailbox.</div></div></div>
<?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="post" autocomplete="off">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
<div class="row g-3">
<div class="col-md-6"><label class="form-label">First name *</label><input class="form-control" name="first_name" value="L." required></div>
<div class="col-md-6"><label class="form-label">Last name *</label><input class="form-control" name="last_name" value="Sai Rohan" required></div>
<div class="col-md-4"><label class="form-label">Date of birth *</label><input class="form-control" type="date" name="dob" value="2012-10-20" required></div>
<div class="col-md-4"><label class="form-label">Gender</label><select class="form-select" name="gender"><option value="male" selected>Male</option><option value="female">Female</option><option value="other">Other</option></select></div>
<div class="col-md-4"><label class="form-label">Blood group</label><input class="form-control" name="blood_group" value="A1+ve"></div>
<div class="col-md-6"><label class="form-label">Mobile number</label><input class="form-control" name="phone" value="8939319656"></div>
<div class="col-md-6"><label class="form-label">Alternate mobile</label><input class="form-control" name="alternate_mobile" value="9841882666"></div>
<div class="col-md-6"><label class="form-label">Father's name</label><input class="form-control" name="father_name" value="Lingadhurai. S"></div>
<div class="col-md-6"><label class="form-label">Mother's name</label><input class="form-control" name="mother_name" value="Patturani. L"></div>
<div class="col-md-6"><label class="form-label">Date of joining</label><input class="form-control" type="date" name="date_of_joining" value="2026-08-01"></div>
<div class="col-12"><label class="form-label">Address</label><textarea class="form-control" name="address" rows="3">J.K. builders 2nd floor, Rangangar 1st main, Old Perungalathur, Chennai</textarea></div>
<div class="col-md-6"><label class="form-label">KOMS login email *</label><input class="form-control" type="email" name="email" value="<?= htmlspecialchars($generated_email) ?>" required></div>
<div class="col-md-6"><label class="form-label">Temporary password *</label><input class="form-control" type="text" name="password" value="<?= htmlspecialchars($generated_password) ?>" required></div>
</div>
<div class="d-flex flex-wrap gap-2 mt-4"><button class="btn btn-danger px-4" type="submit"><i class="fa-solid fa-user-plus me-2"></i>Create Student</button><a class="btn btn-outline-dark" href="users.php">Back to Users</a><a class="btn btn-outline-secondary" href="<?= APP_URL ?>/admin/dashboard.php">Dashboard</a></div>
</form>
</div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
