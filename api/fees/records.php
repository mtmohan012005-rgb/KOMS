<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once '../../config/database.php';

$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;

if ($student_id > 0) {
    $stmt = $pdo->prepare("
        SELECT r.id, r.billing_month, r.amount_due, r.due_date, r.status, s.fee_name 
        FROM fee_records r 
        JOIN fee_structures s ON r.fee_structure_id = s.id 
        WHERE r.student_id = ? 
        ORDER BY r.due_date DESC
    ");
    $stmt->execute([$student_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "message" => "Fee records fetched",
        "data" => $records
    ]);
} else {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "student_id is required", "errors" => []]);
}
?>
