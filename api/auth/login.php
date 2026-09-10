<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once '../../config/database.php';

$data = json_decode(file_get_contents("php://input"));

$login = trim($data->email ?? $data->username ?? $data->member_id ?? '');
$password = $data->password ?? '';

if (!empty($login) && !empty($password)) {
    $stmt = $pdo->prepare("SELECT id, member_id, first_name, last_name, email, role, password_hash, status FROM users WHERE email = ? OR member_id = ? LIMIT 1");
    $stmt->execute([$login, $login]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        if ($user['status'] === 'active') {
            $token = bin2hex(random_bytes(16)); // Mock session token
            
            echo json_encode([
                "success" => true,
                "message" => "Login successful",
                "data" => [
                    "user_id" => (int)$user['id'],
                    "member_id" => $user['member_id'],
                    "name" => trim($user['first_name'] . ' ' . $user['last_name']),
                    "email" => $user['email'],
                    "role" => $user['role'],
                    "token" => $token
                ]
            ]);
        } else {
            http_response_code(403);
            echo json_encode(["success" => false, "message" => "Account is inactive", "errors" => []]);
        }
    } else {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Invalid credentials", "errors" => []]);
    }
} else {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Email / User ID and password required", "errors" => []]);
}
?>
