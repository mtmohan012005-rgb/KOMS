<?php
// api/master/achievements.php - Add and Manage Student Achievements for Master
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
        $title = trim($data['title'] ?? '');
        $competitionEvent = trim($data['competition_event'] ?? 'Karate Championship');
        $positionResult = trim($data['position_result'] ?? '1st Place');
        $achievementDate = trim($data['achievement_date'] ?? date('Y-m-d'));
        $description = trim($data['description'] ?? '');

        if ($studentId <= 0 || empty($title)) {
            send_api_error("student_id and title are required.", [], 400);
        }

        // Verify student belongs to this dojo
        assert_dojo_access($pdo, $caller, $studentId);

        $pdo->prepare("INSERT INTO achievements (student_id, title, description, competition_event, position_result, achievement_date, added_by) VALUES (?, ?, ?, ?, ?, ?, ?)")
            ->execute([$studentId, $title, $description, $competitionEvent, $positionResult, $achievementDate, $masterId]);
        $achievementId = $pdo->lastInsertId();

        // Student name
        $stmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) FROM users WHERE id = ?");
        $stmt->execute([$studentId]);
        $studentName = $stmt->fetchColumn() ?: "Student ID $studentId";

        // Audit Log
        $pdo->prepare("INSERT INTO audit_logs (user_id, action, module, record_id, description) VALUES (?, 'ACHIEVEMENT_ADDED', 'achievements', ?, ?)")
            ->execute([$masterId, $studentId, "Achievement added for $studentName ($title)"]);

        // Total count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM achievements WHERE student_id = ?");
        $stmt->execute([$studentId]);
        $totalCount = (int)$stmt->fetchColumn();

        send_api_response([
            'achievement_id' => (int)$achievementId,
            'student_id' => $studentId,
            'student_name' => $studentName,
            'title' => $title,
            'total_achievements' => $totalCount
        ], "Achievement added successfully");

    } else {
        send_api_error("Method not allowed.", [], 405);
    }
} catch (Throwable $e) {
    error_log("Achievements API Error: " . $e->getMessage());
    send_api_error("Error adding achievement: " . $e->getMessage(), [], 500);
}
