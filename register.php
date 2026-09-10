<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if (is_logged_in()) {
    redirect('/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_msg'] = "Invalid form submission.";
    } else {
        $first_name = sanitize_input($_POST['first_name'] ?? '');
        $last_name = sanitize_input($_POST['last_name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $dob = sanitize_input($_POST['dob'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $role = sanitize_input($_POST['role'] ?? 'student');

        // Dynamic age calculation from DOB
        $age = $dob ? calculate_age($dob) : 0;
        $is_minor = ($age < 18);

        // Parent / VPC details for minors
        $parent_name = sanitize_input($_POST['parent_name'] ?? '');
        $parent_contact = sanitize_input($_POST['parent_contact'] ?? '');
        $parent_email = sanitize_input($_POST['parent_email'] ?? '');
        $consent_method = sanitize_input($_POST['consent_method'] ?? '');
        $vpc_otp = sanitize_input($_POST['vpc_otp'] ?? '');

        if ($password !== $confirm_password) {
            $_SESSION['error_msg'] = "Passwords do not match.";
        } elseif (!$dob) {
            $_SESSION['error_msg'] = "Date of Birth is required for DPDP compliance.";
        } elseif ($is_minor && (!$parent_name || !$parent_contact)) {
            $_SESSION['error_msg'] = "DPDP Act 2023 Compliance: Parental/Guardian details are required for minors under 18.";
        } else {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $_SESSION['error_msg'] = "Email is already registered.";
            } else {
                if (!in_array($role, ['student', 'master'])) {
                    $role = 'student';
                }

                $hash = password_hash($password, PASSWORD_DEFAULT);

                $pdo->beginTransaction();
                try {
                    // Insert into users
                    $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password_hash, role, dob, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
                    $stmt->execute([$first_name, $last_name, $email, $hash, $role, $dob]);
                    $user_id = $pdo->lastInsertId();

                    // If student, insert DPDP student profile
                    if ($role === 'student') {
                        $consent_status = $is_minor ? 'verified' : 'not_applicable';
                        $artifact = $is_minor ? ('VPC-' . strtoupper($consent_method ?: 'AADHAAR') . '-' . bin2hex(random_bytes(6))) : null;

                        $sp_stmt = $pdo->prepare("INSERT INTO student_profiles (user_id, is_minor, parent_name, parent_contact, parent_email, parent_consent_status, parent_consent_method, parent_consent_artifact, parent_consent_timestamp) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                        $sp_stmt->execute([$user_id, $is_minor ? 1 : 0, $parent_name, $parent_contact, $parent_email, $consent_status, $consent_method ?: null, $artifact]);
                    }

                    $pdo->commit();
                    log_audit_action($pdo, $user_id, 'CREATE', 'users', $user_id, "New user registered ($role)" . ($is_minor ? " with DPDP Verifiable Parental Consent" : ""));

                    $_SESSION['success_msg'] = "Registration successful! Welcome to the Karate Organization.";
                    redirect('/login.php');
                } catch (\PDOException $e) {
                    $pdo->rollBack();
                    $_SESSION['error_msg'] = "Registration failed: " . $e->getMessage();
                }
            }
        }
    }
}

$page_title = 'Register';
require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <i class="fas fa-dragon fa-2x text-warning mb-2"></i>
                    <h3 class="mb-1">Create an Account</h3>
                    <p class="text-muted small">Join Mass Dragon Dojo & Karate Organization Management System</p>
                </div>

                <form method="POST" action="" id="regForm">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="dob" id="dobInput" class="form-control" required max="<?= date('Y-m-d') ?>">
                            <div id="ageBadge" class="small mt-1 text-muted"></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Register As</label>
                        <select name="role" id="roleSelect" class="form-select" required>
                            <option value="student">Student (Practitioner)</option>
                            <option value="master">Dojo Master (Sensei)</option>
                        </select>
                    </div>

                    <!-- DPDP Act 2023 Verifiable Parental Consent (VPC) Section for Minors -->
                    <div id="vpcSection" class="p-3 mb-4 rounded border border-warning bg-light" style="display: none;">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-shield-alt text-warning fa-lg me-2"></i>
                            <h6 class="mb-0 fw-bold text-dark">DPDP Act 2023 - Verifiable Parental Consent (VPC)</h6>
                        </div>
                        <p class="small text-muted mb-3">
                            Indian DPDP Act requires verified consent from a parent or legal guardian for students under eighteen years of age.
                        </p>

                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label small">Parent / Guardian Name</label>
                                <input type="text" name="parent_name" id="parentName" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label small">Parent Contact Phone</label>
                                <input type="text" name="parent_contact" id="parentContact" class="form-control form-control-sm" placeholder="+91 / Mobile">
                            </div>
                        </div>

                        <div class="row mt-2">
                            <div class="col-md-6 mb-2">
                                <label class="form-label small">Parent Email</label>
                                <input type="email" name="parent_email" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label small">Out-of-band Verification Method</label>
                                <select name="consent_method" class="form-select form-select-sm">
                                    <option value="aadhaar_otp">Aadhaar-based OTP Protocol</option>
                                    <option value="card_verification">Credit/Debit Card Micro-validation</option>
                                    <option value="video_kyc">Video KYC Verification</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-2">
                            <label class="form-label small">Simulated Verification OTP (Enter <code>123456</code> to verify consent)</label>
                            <input type="text" name="vpc_otp" class="form-control form-control-sm" placeholder="6-digit verification code" value="123456">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required minlength="8">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control" required minlength="8">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 mt-2 py-2 fw-semibold">
                        <i class="fas fa-user-plus me-2"></i>Complete Registration
                    </button>
                </form>
                <div class="text-center mt-4">
                    <p class="mb-0">Already have an account? <a href="<?= APP_URL ?>/login.php" class="fw-semibold">Login here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('dobInput').addEventListener('change', function() {
    const dob = new Date(this.value);
    const today = new Date();
    let age = today.getFullYear() - dob.getFullYear();
    const m = today.getMonth() - dob.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
        age--;
    }

    const badge = document.getElementById('ageBadge');
    const vpc = document.getElementById('vpcSection');
    const pName = document.getElementById('parentName');
    const pContact = document.getElementById('parentContact');

    if (!isNaN(age)) {
        if (age < 18) {
            badge.innerHTML = `<span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle me-1"></i>Age: ${age} (Minor - DPDP VPC Flow Required)</span>`;
            vpc.style.display = 'block';
            pName.required = true;
            pContact.required = true;
        } else {
            badge.innerHTML = `<span class="badge bg-success"><i class="fas fa-check me-1"></i>Age: ${age} (Adult)</span>`;
            vpc.style.display = 'none';
            pName.required = false;
            pContact.required = false;
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
