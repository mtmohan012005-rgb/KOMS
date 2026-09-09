<?php
// auth.php - Authentication and Authorization middleware

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        $_SESSION['error_msg'] = "You must be logged in to access this page.";
        redirect('/login.php');
    }
}

function get_current_user_role() {
    return $_SESSION['user_role'] ?? null;
}

function has_role($role) {
    return get_current_user_role() === $role;
}

function require_role($required_role) {
    require_login();
    if (!has_role($required_role)) {
        $_SESSION['error_msg'] = "Unauthorized access.";
        
        // Redirect to appropriate dashboard based on role
        if (has_role('super_admin')) redirect('/admin/dashboard.php');
        if (has_role('master')) redirect('/master/dashboard.php');
        if (has_role('senior')) redirect('/senior/dashboard.php');
        if (has_role('student')) redirect('/student/dashboard.php');
        
        redirect('/index.php');
    }
}

function login_user($pdo, $email, $password) {
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, password_hash, role, status FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        if ($user['status'] !== 'active') {
            return ["success" => false, "message" => "Account is inactive. Please contact administrator."];
        }

        // Prevent session fixation
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_role'] = $user['role'];
        
        log_audit_action($pdo, $user['id'], 'LOGIN', 'auth', null, 'User logged in successfully');

        return ["success" => true, "role" => $user['role']];
    }
    
    return ["success" => false, "message" => "Invalid email or password."];
}

function logout_user($pdo) {
    if (is_logged_in()) {
        log_audit_action($pdo, $_SESSION['user_id'], 'LOGOUT', 'auth', null, 'User logged out');
    }
    
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
?>
