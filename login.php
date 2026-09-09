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
        $email = sanitize_input($_POST['email']);
        $password = $_POST['password'];

        $result = login_user($pdo, $email, $password);
        
        if ($result['success']) {
            $_SESSION['success_msg'] = "Welcome back!";
            // Redirect based on role
            if ($result['role'] === 'super_admin') redirect('/admin/dashboard.php');
            if ($result['role'] === 'master') redirect('/master/dashboard.php');
            if ($result['role'] === 'senior') redirect('/senior/dashboard.php');
            if ($result['role'] === 'student') redirect('/student/dashboard.php');
            redirect('/index.php');
        } else {
            $_SESSION['error_msg'] = $result['message'];
        }
    }
}

$page_title = 'Login';
require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm border-0">
            <div class="card-body p-5">
                <h3 class="text-center mb-4">Account Login</h3>
                
                <!-- Demo Credentials Helper -->
                <div class="alert alert-info py-2 small">
                    <strong>Demo Logins (Password: password123):</strong><br>
                    Admin: admin@koms.com<br>
                    Master: master@koms.com<br>
                    Student: student@koms.com
                </div>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" required autofocus>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 mb-3">Login</button>
                </form>
                <div class="text-center">
                    <p class="mb-1"><a href="#" class="text-decoration-none">Forgot password?</a></p>
                    <p>Don't have an account? <a href="<?= APP_URL ?>/register.php">Register</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
