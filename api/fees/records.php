<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/api_auth.php';

$caller = authenticate_api_request($pdo, true);

$requested_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;

// Default to caller's own ID if caller is a student
if ($requested_id <= 0) {
    if ($caller['role'] === 'student') {
        $requested_id = $caller['user_id'];
    } else {
        send_api_error("student_id is required.", [], 400);
    }
}

// Enforce strict authorization and dojo boundary checks
assert_dojo_access($pdo, $caller, $requested_id);

try {
    $stmt = $pdo->prepare("
        SELECT r.id, r.billing_month, r.amount_due, r.due_date, r.status, s.fee_name 
        FROM fee_records r 
        JOIN fee_structures s ON r.fee_structure_id = s.id 
        WHERE r.student_id = ? 
        ORDER BY r.due_date DESC
        LIMIT 50
    ");
    $stmt->execute([$requested_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    send_api_response($records, "Fee records fetched successfully");
} catch (Throwable $e) {
    error_log("API Fee Records Error: " . $e->getMessage());
    send_api_error("Unable to fetch fee records.", [], 500);
}

