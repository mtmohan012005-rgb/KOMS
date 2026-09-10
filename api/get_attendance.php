<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once '../config/database.php';

$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;

if ($student_id > 0) {
    $stmt = $pdo->prepare("
        SELECT s.session_date, e.status, e.remarks 
        FROM attendance_entries e 
        JOIN attendance_sessions s ON e.session_id = s.id 
        WHERE e.student_id = ? 
        ORDER BY s.session_date DESC 
        LIMIT 20
    ");
    $stmt->execute([$student_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "attendance" => $records
    ]);
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "student_id is required"]);
}
?>
