<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->email) && !empty($data->password)) {
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, role, password_hash, status FROM users WHERE email = ?");
    $stmt->execute([$data->email]);
    $user = $stmt->fetch();

    if ($user && password_verify($data->password, $user['password_hash'])) {
        if ($user['status'] === 'active') {
            // In a real app, generate a JWT token. Here we use a mock token for simplicity.
            $token = bin2hex(random_bytes(16)); 
            
            http_response_code(200);
            echo json_encode([
                "status" => "success",
                "message" => "Login successful",
                "token" => $token,
                "user" => [
                    "id" => $user['id'],
                    "first_name" => $user['first_name'],
                    "last_name" => $user['last_name'],
                    "role" => $user['role']
                ]
            ]);
        } else {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "Account is inactive or suspended"]);
        }
    } else {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Invalid email or password"]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Email and password are required"]);
}
?>
