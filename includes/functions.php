<?php
// functions.php - Global utility functions

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function calculate_age($dob) {
    $dobObject = new DateTime($dob);
    $now = new DateTime();
    return $now->diff($dobObject)->y;
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        return true;
    }
    return false;
}

function log_audit_action($pdo, $user_id, $action, $module, $record_id, $description) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, module, record_id, description, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $action, $module, $record_id, $description, $ip]);
}

function display_alert() {
    if (isset($_SESSION['success_msg'])) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . $_SESSION['success_msg'] . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        unset($_SESSION['success_msg']);
    }
    if (isset($_SESSION['error_msg'])) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . $_SESSION['error_msg'] . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        unset($_SESSION['error_msg']);
    }
}

function redirect($url) {
    header("Location: " . APP_URL . $url);
    exit();
}
?>
