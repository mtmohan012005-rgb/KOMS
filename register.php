<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if (is_logged_in()) {
    redirect('/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error_msg'] = "Invalid form submission.";
    } else {
        $first_name = sanitize_input($_POST['first_name']);
        $last_name = sanitize_input($_POST['last_name']);
        $email = sanitize_input($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $role = sanitize_input($_POST['role']);

        if ($password !== $confirm_password) {
            $_SESSION['error_msg'] = "Passwords do not match.";
        } else {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $_SESSION['error_msg'] = "Email is already registered.";
            } else {
                // Ensure only students or masters can register publicly
                if (!in_array($role, ['student', 'master'])) {
                    $role = 'student';
                }

                $hash = password_hash($password, PASSWORD_DEFAULT);
                
                try {
                    $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password_hash, role) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$first_name, $last_name, $email, $hash, $role]);
                    
                    $_SESSION['success_msg'] = "Registration successful! Please login.";
                    redirect('/login.php');
                } catch (\PDOException $e) {
                    $_SESSION['error_msg'] = "An error occurred during registration.";
                }
            }
        }
    }
}

$page_title = 'Register';
require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h3 class="text-center mb-4">Create an Account</h3>
                <form method="POST" action="">
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
                    
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Register As</label>
                        <select name="role" class="form-select" required>
                            <option value="student">Student</option>
                            <option value="master">Dojo Master</option>
                        </select>
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
                    
                    <button type="submit" class="btn btn-primary w-100 mt-3">Register</button>
                </form>
                <div class="text-center mt-3">
                    <p>Already have an account? <a href="<?= APP_URL ?>/login.php">Login here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
