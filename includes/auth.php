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

        if (has_role('super_admin')) redirect('/admin/dashboard.php');
        if (has_role('master')) redirect('/master/dashboard.php');
        if (has_role('senior')) redirect('/senior/dashboard.php');
        if (has_role('student')) redirect('/student/dashboard.php');

        redirect('/index.php');
    }
}

/**
 * Ensure the KOMS member_id column exists for databases created before
 * member IDs were introduced. This keeps the live database compatible
 * without requiring a manual migration step.
 */
function ensure_member_id_column(PDO $pdo): void {
    $check = $pdo->query(
        "SELECT COUNT(*)
         FROM information_schema.columns
         WHERE table_schema = DATABASE()
           AND table_name = 'users'
           AND column_name = 'member_id'"
    );

    if ((int)$check->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN member_id VARCHAR(100) NULL UNIQUE AFTER id");
    }
}

function login_user($pdo, $login, $password) {
    try {
        ensure_member_id_column($pdo);

        $login = trim((string)$login);

        $stmt = $pdo->prepare(
            "SELECT id, first_name, last_name, password_hash, role, status
             FROM users
             WHERE email = ? OR member_id = ?
             LIMIT 1"
        );
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return [
                "success" => false,
                "message" => "Invalid email / User ID or password."
            ];
        }

        if ($user['status'] !== 'active') {
            return [
                "success" => false,
                "message" => "Account is inactive. Please contact administrator."
            ];
        }

        // Prevent session fixation after successful authentication.
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_name'] = trim($user['first_name'] . ' ' . $user['last_name']);
        $_SESSION['user_role'] = $user['role'];

        // Audit logging must never turn a valid login into HTTP 500.
        try {
            log_audit_action(
                $pdo,
                $user['id'],
                'LOGIN',
                'auth',
                null,
                'User logged in successfully'
            );
        } catch (Throwable $audit_error) {
            error_log('KOMS audit log failed during login: ' . $audit_error->getMessage());
        }

        return [
            "success" => true,
            "role" => $user['role']
        ];
    } catch (Throwable $e) {
        error_log('KOMS login error: ' . $e->getMessage());

        return [
            "success" => false,
            "message" => "Login service is temporarily unavailable. Please check the database connection and try again."
        ];
    }
}

function logout_user($pdo) {
    if (is_logged_in()) {
        try {
            log_audit_action(
                $pdo,
                $_SESSION['user_id'],
                'LOGOUT',
                'auth',
                null,
                'User logged out'
            );
        } catch (Throwable $e) {
            error_log('KOMS audit log failed during logout: ' . $e->getMessage());
        }
    }

    $_SESSION = array();

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
}
?>
