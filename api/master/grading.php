<?php
// api/master/grading.php - Belt Promotion & Grading Management for Master
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/api_auth.php';

handle_api_cors();

try {
    $caller = authenticate_api_request($pdo, true);
    require_api_role($caller, ['master', 'super_admin', 'grand_master', 'admin']);

    $masterId = (int)$caller['user_id'];
    $dojoId = $caller['dojo_id'] ?? resolve_user_dojo_id($pdo, $masterId, $caller['role']);

    if (!$dojoId && $caller['role'] !== 'super_admin') {
        send_api_error("No active dojo found associated with this Master account.", [], 404);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $raw = file_get_contents("php://input");
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $data = $_POST;
        }

        $studentId = (int)($data['student_id'] ?? 0);
        $newBelt = trim($data['new_belt'] ?? '');
        $examDate = trim($data['exam_date'] ?? date('Y-m-d'));
        $grade = trim($data['grade'] ?? 'A');
        $remarks = trim($data['remarks'] ?? 'Promoted by Dojo Master');

        if ($studentId <= 0 || empty($newBelt)) {
            send_api_error("student_id and new_belt are required.", [], 400);
        }

        // Verify student belongs to this dojo
        assert_dojo_access($pdo, $caller, $studentId);

        // Previous belt
        $stmt = $pdo->prepare("SELECT new_belt FROM grading_history WHERE student_id = ? ORDER BY exam_date DESC, id DESC LIMIT 1");
        $stmt->execute([$studentId]);
        $prevBelt = $stmt->fetchColumn() ?: 'White Belt';

        // Insert new promotion record
        $pdo->prepare("INSERT INTO grading_history (student_id, dojo_id, previous_belt, new_belt, exam_date, grade, instructor_id, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$studentId, $dojoId, $prevBelt, $newBelt, $examDate, $grade, $masterId, $remarks]);
        $gradingId = $pdo->lastInsertId();

        // Student name
        $stmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) FROM users WHERE id = ?");
        $stmt->execute([$studentId]);
        $studentName = $stmt->fetchColumn() ?: "Student ID $studentId";

        // Audit log
        $pdo->prepare("INSERT INTO audit_logs (user_id, action, module, record_id, description) VALUES (?, 'BELT_UPDATED', 'grading', ?, ?)")
            ->execute([$masterId, $studentId, "Belt updated for $studentName: $prevBelt → $newBelt"]);

        send_api_response([
            'grading_id' => (int)$gradingId,
            'student_id' => $studentId,
            'student_name' => $studentName,
            'previous_belt' => $prevBelt,
            'current_belt' => $newBelt,
            'promotion_date' => $examDate
        ], "Student successfully promoted to $newBelt");

    } else {
        send_api_error("Method not allowed.", [], 405);
    }
} catch (Throwable $e) {
    error_log("Grading API Error: " . $e->getMessage());
    send_api_error("Error promoting student belt: " . $e->getMessage(), [], 500);
}
