<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/api_auth.php';
require_once '../includes/universal_auth.php';

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
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => "Email / User ID and password are required",
        "data" => null,
        "errors" => ["Email / User ID and password are required"]
    ]);
    exit();
}

try {
    $authResult = find_and_verify_koms_user($pdo, $login, $password);
    if (!$authResult['success']) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "status" => "error",
            "message" => $authResult['message'],
            "data" => null,
            "errors" => [$authResult['message']]
        ]);
        exit();
    }

    $user = $authResult['user'];
    $dojo_id = (int)($user['dojo_id'] ?? 1);
    $user['dojo_id'] = $dojo_id;
    $token = create_api_token($user);

    try {
        log_audit_action($pdo, (int)$user['id'], 'API_LOGIN_LEGACY', 'auth', (int)$user['id'], 'Legacy API login successful');
    } catch (Throwable $e) {}

    http_response_code(200);
    echo json_encode([
        "success" => true,
        "status" => "success",
        "message" => "Login successful",
        "token" => $token,
        "data" => [
            "user_id" => (int)$user['id'],
            "member_id" => $user['member_id'],
            "name" => trim($user['first_name'] . ' ' . $user['last_name']),
            "email" => $user['email'],
            "role" => $user['role'],
            "dojo_id" => $dojo_id,
            "token" => $token
        ],
        "user" => [
            "id" => (int)$user['id'],
            "member_id" => $user['member_id'],
            "first_name" => $user['first_name'],
            "last_name" => $user['last_name'],
            "role" => $user['role'],
            "email" => $user['email']
        ],
        "errors" => []
    ], JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
    error_log("API Legacy Login Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => "Internal server error",
        "data" => null,
        "errors" => ["Internal server error"]
    ]);
}

