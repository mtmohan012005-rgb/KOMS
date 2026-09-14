<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/api_auth.php';

handle_api_cors();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_api_error("Method not allowed. Use POST.", [], 405);
}

$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input);

$login = trim($data->email ?? $data->username ?? $data->member_id ?? '');
$password = $data->password ?? '';

if (empty($login) || empty($password)) {
    send_api_error("Email / Member ID and password are required.", [], 400);
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
        send_api_error("Invalid credentials.", ["Invalid email/member ID or password."], 401);
    }

    if ($user['status'] !== 'active') {
        send_api_error("Account is inactive or suspended.", ["Contact administrator."], 403);
    }

    // Resolve user's associated dojo
    $dojo_id = resolve_user_dojo_id($pdo, (int)$user['id'], $user['role']);
    $user['dojo_id'] = $dojo_id;

    // Generate cryptographic token
    $token = create_api_token($user);

    // Audit logging
    try {
        log_audit_action($pdo, (int)$user['id'], 'API_LOGIN', 'auth', (int)$user['id'], 'Mobile/REST API login successful');
    } catch (Throwable $e) {
        // Suppress audit failure in API response
    }

    send_api_response([
        "user_id" => (int)$user['id'],
        "member_id" => $user['member_id'],
        "name" => trim($user['first_name'] . ' ' . $user['last_name']),
        "email" => $user['email'],
        "role" => $user['role'],
        "dojo_id" => $dojo_id,
        "token" => $token
    ], "Login successful");

} catch (Throwable $e) {
    error_log("API Login Exception: " . $e->getMessage());
    send_api_error("An internal server error occurred.", [], 500);
}

