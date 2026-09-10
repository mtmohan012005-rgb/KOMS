<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"));

$login = trim($data->email ?? $data->username ?? $data->member_id ?? '');
$password = $data->password ?? '';

if (!empty($login) && !empty($password)) {
    $stmt = $pdo->prepare("SELECT id, member_id, first_name, last_name, email, role, password_hash, status FROM users WHERE email = ? OR member_id = ? LIMIT 1");
    $stmt->execute([$login, $login]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        if ($user['status'] === 'active') {
            $token = bin2hex(random_bytes(16)); 
            
            http_response_code(200);
            echo json_encode([
                "status" => "success",
                "message" => "Login successful",
                "token" => $token,
                "user" => [
                    "id" => (int)$user['id'],
                    "member_id" => $user['member_id'],
                    "first_name" => $user['first_name'],
                    "last_name" => $user['last_name'],
                    "role" => $user['role'],
                    "email" => $user['email']
                ]
            ]);
        } else {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "Account is inactive or suspended"]);
        }
    } else {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Invalid email / User ID or password"]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Email / User ID and password are required"]);
}
?>
