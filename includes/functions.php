<?php
// functions.php - Global utility functions

function sanitize_input($data) {
    $data = trim((string)$data);
    $data = stripslashes($data);
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

function calculate_age($dob) {
    $dobObject = new DateTime($dob);
    $now = new DateTime();
    return $now->diff($dobObject)->y;
}

/**
 * Detect whether the current request is HTTPS, including Render's proxy header.
 */
function is_secure_request() {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        || (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'onrender.com') !== false);
}

/**
 * Generate a CSRF token using both the PHP session and a same-site cookie.
 * The cookie fallback prevents false token mismatches when a PHP session is
 * recreated while the browser is still holding the current login page.
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        if (!empty($_COOKIE['koms_csrf_token']) && preg_match('/^[a-f0-9]{64}$/', $_COOKIE['koms_csrf_token'])) {
            $_SESSION['csrf_token'] = $_COOKIE['koms_csrf_token'];
        } else {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    $token = $_SESSION['csrf_token'];

    if (!headers_sent()) {
        setcookie('koms_csrf_token', $token, [
            'expires'  => time() + 7200,
            'path'     => '/',
            'secure'   => is_secure_request(),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    return $token;
}

/**
 * Validate the submitted token against the session token or the protected
 * CSRF cookie. On success the validated token is restored into the session.
 */
function verify_csrf_token($token) {
    if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
        return false;
    }

    $sessionToken = $_SESSION['csrf_token'] ?? '';
    $cookieToken = $_COOKIE['koms_csrf_token'] ?? '';

    $valid = false;

    if (is_string($sessionToken) && hash_equals($sessionToken, $token)) {
        $valid = true;
    } elseif (is_string($cookieToken) && preg_match('/^[a-f0-9]{64}$/', $cookieToken) && hash_equals($cookieToken, $token)) {
        $valid = true;
        $_SESSION['csrf_token'] = $cookieToken;
    }

    return $valid;
}

function log_audit_action($pdo, $user_id, $action, $module, $record_id, $description) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, module, record_id, description, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $action, $module, $record_id, $description, $ip]);
}

function display_alert() {
    if (isset($_SESSION['success_msg'])) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['success_msg']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        unset($_SESSION['success_msg']);
    }
    if (isset($_SESSION['error_msg'])) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . htmlspecialchars($_SESSION['error_msg']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        unset($_SESSION['error_msg']);
    }
}

function redirect($url) {
    header("Location: " . APP_URL . $url);
    exit();
}

/**
 * Format a Date of Birth (YYYY-MM-DD or other formats) into the student's default initial password (DD.MM.YYYY).
 */
function format_dob_password(?string $dob): ?string {
    if (!$dob) return null;
    $trimmed = trim($dob);
    if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $trimmed)) {
        return $trimmed;
    }
    $ts = strtotime($trimmed);
    if ($ts === false) return null;
    return date('d.m.Y', $ts);
}

/**
 * Check whether a user is eligible to directly change their password.
 * Students have a strict maximum limit of 1 self-service change.
 */
function can_user_change_password(array $user): bool {
    if (($user['role'] ?? '') !== 'student') {
        return true;
    }
    return (int)($user['password_change_count'] ?? 0) < 1;
}

/**
 * Fetch the latest pending password reset request for a given user.
 */
function get_pending_password_reset_request(PDO $pdo, int $userId): ?array {
    try {
        $stmt = $pdo->prepare("SELECT * FROM password_reset_requests WHERE user_id = ? AND status = 'pending' ORDER BY id DESC LIMIT 1");
        $stmt->execute([$userId]);
        $res = $stmt->fetch();
        return $res ?: null;
    } catch (Throwable $e) {
        return null;
    }
}
?>
