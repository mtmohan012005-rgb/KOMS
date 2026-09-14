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
        SELECT s.session_date, s.start_time, e.status, e.remarks 
        FROM attendance_entries e 
        JOIN attendance_sessions s ON e.session_id = s.id 
        WHERE e.student_id = ? 
        ORDER BY s.session_date DESC 
        LIMIT 50
    ");
    $stmt->execute([$requested_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    send_api_response($records, "Attendance history fetched successfully");
} catch (Throwable $e) {
    error_log("API Attendance History Error: " . $e->getMessage());
    send_api_error("Unable to fetch attendance history.", [], 500);
}

