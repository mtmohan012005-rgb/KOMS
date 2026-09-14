<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/api_auth.php';
require_once '../../includes/universal_auth.php';

handle_api_cors();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_api_error("Method not allowed. Use POST.", [], 405);
}

$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input, true);
if (!is_array($data)) {
    $data = $_POST;
}

$login = trim($data['email'] ?? $data['username'] ?? $data['member_id'] ?? $data['login'] ?? '');
$password = trim($data['password'] ?? $data['pass'] ?? '');

if (empty($login) || empty($password)) {
    send_api_error("Email / Student ID and password are required.", [], 400);
}

try {
    $authResult = find_and_verify_koms_user($pdo, $login, $password);
    if (!$authResult['success']) {
        send_api_error($authResult['message'], [$authResult['message']], 401);
    }

    $user = $authResult['user'];
    $dojo_id = (int)($user['dojo_id'] ?? 1);

    // Generate cryptographic token
    $token = create_api_token($user);

    // Audit logging
    try {
        log_audit_action($pdo, (int)$user['id'], 'API_LOGIN', 'auth', (int)$user['id'], 'Mobile/REST API login successful');
    } catch (Throwable $e) {}

    send_api_response([
        "user_id" => (int)$user['id'],
        "member_id" => $user['member_id'] ?: sprintf('MD-%05d', (int)$user['id']),
        "name" => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),
        "email" => $user['email'],
        "role" => $user['role'],
        "dojo_id" => $dojo_id,
        "token" => $token
    ], "Login successful");

} catch (Throwable $e) {
    error_log("API Login Exception: " . $e->getMessage());
    send_api_error("An internal server error occurred.", [], 500);
}

