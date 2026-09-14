<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/api_auth.php';

handle_api_cors();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_api_error("Method not allowed. Use POST.", [], 405);
}

$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input);

$login = trim($data->email ?? $data->username ?? $data->member_id ?? '');
$password = $data->password ?? '';

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
    $stmt = $pdo->prepare(
        "SELECT id, member_id, first_name, last_name, email, role, password_hash, status 
         FROM users 
         WHERE email = ? OR member_id = ? 
         LIMIT 1"
    );
    $stmt->execute([$login, $login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "status" => "error",
            "message" => "Invalid email / User ID or password",
            "data" => null,
            "errors" => ["Invalid credentials"]
        ]);
        exit();
    }

    if ($user['status'] !== 'active') {
        http_response_code(403);
        echo json_encode([
            "success" => false,
            "status" => "error",
            "message" => "Account is inactive or suspended",
            "data" => null,
            "errors" => ["Account inactive"]
        ]);
        exit();
    }

    $dojo_id = resolve_user_dojo_id($pdo, (int)$user['id'], $user['role']);
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

