<?php
// api/master/schedule.php - Manage Weekly Dojo Class Schedule for Master
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

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->prepare("SELECT id, name, location, training_days, training_timings FROM dojos WHERE id = ?");
        $stmt->execute([$dojoId]);
        $dojo = $stmt->fetch(PDO::FETCH_ASSOC);

        $scheduleList = [
            ['day' => 'Tue', 'day_full' => 'Tuesday', 'title' => 'Karate Training', 'timing' => '06:00 PM - 07:30 PM', 'type' => 'Regular'],
            ['day' => 'Thu', 'day_full' => 'Thursday', 'title' => 'Karate Training', 'timing' => '06:00 PM - 07:30 PM', 'type' => 'Kata & Sparring'],
            ['day' => 'Sat', 'day_full' => 'Saturday', 'title' => 'Karate Training', 'timing' => '05:00 PM - 06:30 PM', 'type' => 'Kumite Practice']
        ];

        send_api_response([
            'dojo_id' => $dojoId,
            'dojo_name' => $dojo['name'] ?? 'Main Dojo',
            'training_days' => $dojo['training_days'] ?? 'Tuesday, Thursday, Saturday',
            'training_timings' => $dojo['training_timings'] ?? '06:00 PM - 07:30 PM (Tue, Thu), 05:00 PM - 06:30 PM (Sat)',
            'classes' => $scheduleList
        ], "Schedule loaded successfully");

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $raw = file_get_contents("php://input");
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $data = $_POST;
        }

        $trainingDays = trim($data['training_days'] ?? '');
        $trainingTimings = trim($data['training_timings'] ?? '');

        if (empty($trainingDays) && empty($trainingTimings)) {
            send_api_error("training_days or training_timings is required.", [], 400);
        }

        $pdo->prepare("UPDATE dojos SET training_days = COALESCE(NULLIF(?, ''), training_days), training_timings = COALESCE(NULLIF(?, ''), training_timings) WHERE id = ?")
            ->execute([$trainingDays, $trainingTimings, $dojoId]);

        // Audit log
        $pdo->prepare("INSERT INTO audit_logs (user_id, action, module, record_id, description) VALUES (?, 'SCHEDULE_UPDATED', 'schedule', ?, ?)")
            ->execute([$masterId, $dojoId, "Master updated training schedule for Dojo $dojoId"]);

        send_api_response([
            'dojo_id' => $dojoId,
            'training_days' => $trainingDays,
            'training_timings' => $trainingTimings
        ], "Training schedule updated successfully");

    } else {
        send_api_error("Method not allowed.", [], 405);
    }
} catch (Throwable $e) {
    error_log("Schedule API Error: " . $e->getMessage());
    send_api_error("Error updating schedule: " . $e->getMessage(), [], 500);
}
