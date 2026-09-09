<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->student_id) && !empty($data->dojo_id)) {
    // Check if the student is already requested/joined
    $check_query = "SELECT id FROM dojo_members WHERE user_id = :student_id LIMIT 1";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":student_id", $data->student_id);
    $check_stmt->execute();
    
    if($check_stmt->rowCount() > 0) {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "You are already in a dojo or have a pending request."));
        exit();
    }
    
    // Create new join request (status = pending)
    $query = "INSERT INTO dojo_members (dojo_id, user_id, status, join_date) VALUES (:dojo_id, :student_id, 'pending', NOW())";
    $stmt = $db->prepare($query);

    $stmt->bindParam(":dojo_id", $data->dojo_id);
    $stmt->bindParam(":student_id", $data->student_id);

    if($stmt->execute()) {
        http_response_code(201);
        echo json_encode(array("success" => true, "message" => "Join request sent successfully! Awaiting Master approval."));
    } else {
        http_response_code(503);
        echo json_encode(array("success" => false, "message" => "Unable to send join request."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Incomplete data. Provide student_id and dojo_id."));
}
?>
