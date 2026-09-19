<?php
// api/master/attendance.php - Attendance Management for Dojo Master
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
        $date = $_GET['date'] ?? date('Y-m-d');

        // Check if session exists for this dojo and date
        $stmt = $pdo->prepare("SELECT id, session_date, start_time, end_time, is_locked FROM attendance_sessions WHERE dojo_id = ? AND session_date = ? LIMIT 1");
        $stmt->execute([$dojoId, $date]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        $sessionId = $session ? (int)$session['id'] : 0;

        // Get all approved students for this dojo
        $stmt = $pdo->prepare("
            SELECT u.id as student_id, CONCAT(u.first_name, ' ', u.last_name) as name, u.member_id
            FROM dojo_memberships dm
            JOIN users u ON dm.student_id = u.id
            WHERE dm.dojo_id = ? AND dm.status = 'approved'
            ORDER BY u.first_name ASC
        ");
        $stmt->execute([$dojoId]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $studentList = [];
        foreach ($students as $s) {
            $sId = (int)$s['student_id'];
            $currentStatus = 'present';
            $remarks = '';

            if ($sessionId > 0) {
                $eStmt = $pdo->prepare("SELECT status, remarks FROM attendance_entries WHERE session_id = ? AND student_id = ? LIMIT 1");
                $eStmt->execute([$sessionId, $sId]);
                $entry = $eStmt->fetch(PDO::FETCH_ASSOC);
                if ($entry) {
                    $currentStatus = $entry['status'];
                    $remarks = $entry['remarks'] ?? '';
                }
            }

            // Current belt
            $bStmt = $pdo->prepare("SELECT new_belt FROM grading_history WHERE student_id = ? ORDER BY exam_date DESC LIMIT 1");
            $bStmt->execute([$sId]);
            $belt = $bStmt->fetchColumn() ?: 'White Belt';

            $studentList[] = [
                'student_id' => $sId,
                'name' => $s['name'],
                'student_code' => $s['member_id'] ?: sprintf("MD-%05d", $sId),
                'belt' => $belt,
                'status' => $currentStatus,
                'remarks' => $remarks
            ];
        }

        send_api_response([
            'session_id' => $sessionId,
            'session_date' => $date,
            'class_title' => 'Karate Training',
            'class_timing' => '06:00 PM - 07:30 PM',
            'students' => $studentList
        ], "Attendance roster loaded successfully");

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $raw = file_get_contents("php://input");
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $data = $_POST;
        }

        $sessionDate = trim($data['session_date'] ?? date('Y-m-d'));
        $startTime = trim($data['start_time'] ?? '18:00:00');
        $endTime = trim($data['end_time'] ?? '19:30:00');
        $entries = $data['entries'] ?? [];

        // Support single student mark: student_id + status
        if (empty($entries) && !empty($data['student_id'])) {
            $entries = [[
                'student_id' => (int)$data['student_id'],
                'status' => strtolower(trim($data['status'] ?? 'present')),
                'remarks' => trim($data['remarks'] ?? 'Attendance marked by Master')
            ]];
        }

        if (empty($entries)) {
            send_api_error("Attendance entries are required.", [], 400);
        }

        // 1. Get or create attendance session
        $stmt = $pdo->prepare("SELECT id FROM attendance_sessions WHERE dojo_id = ? AND session_date = ? LIMIT 1");
        $stmt->execute([$dojoId, $sessionDate]);
        $sessionId = $stmt->fetchColumn();

        if (!$sessionId) {
            $pdo->prepare("INSERT INTO attendance_sessions (dojo_id, instructor_id, session_date, scheduled_day, start_time, end_time, created_by) VALUES (?, ?, ?, DAYNAME(?), ?, ?, ?)")
                ->execute([$dojoId, $masterId, $sessionDate, $sessionDate, $startTime, $endTime, $masterId]);
            $sessionId = $pdo->lastInsertId();
        }

        // 2. Save/Update entries
        $markedCount = 0;
        foreach ($entries as $e) {
            $sId = (int)($e['student_id'] ?? 0);
            $status = strtolower(trim($e['status'] ?? 'present'));
            $remarks = trim($e['remarks'] ?? '');

            if ($sId <= 0) continue;

            // Verify student belongs to this dojo
            $mStmt = $pdo->prepare("SELECT id FROM dojo_memberships WHERE student_id = ? AND dojo_id = ? AND status = 'approved' LIMIT 1");
            $mStmt->execute([$sId, $dojoId]);
            if (!$mStmt->fetch() && $caller['role'] !== 'super_admin') {
                continue; // Skip foreign student
            }

            // Upsert entry
            $chkStmt = $pdo->prepare("SELECT id FROM attendance_entries WHERE session_id = ? AND student_id = ? LIMIT 1");
            $chkStmt->execute([$sessionId, $sId]);
            $existingEntryId = $chkStmt->fetchColumn();

            if ($existingEntryId) {
                $pdo->prepare("UPDATE attendance_entries SET status = ?, remarks = ?, marked_by = ?, marked_at = NOW() WHERE id = ?")
                    ->execute([$status, $remarks, $masterId, $existingEntryId]);
            } else {
                $pdo->prepare("INSERT INTO attendance_entries (session_id, student_id, status, remarks, marked_by, marked_at) VALUES (?, ?, ?, ?, ?, NOW())")
                    ->execute([$sessionId, $sId, $status, $remarks, $masterId]);
            }
            $markedCount++;
        }

        // 3. Log audit activity
        $pdo->prepare("INSERT INTO audit_logs (user_id, action, module, record_id, description) VALUES (?, 'ATTENDANCE_MARKED', 'attendance', ?, ?)")
            ->execute([$masterId, $sessionId, "Master marked attendance for $markedCount students on $sessionDate"]);

        // Fetch updated session totals
        $totStmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as pres FROM attendance_entries WHERE session_id = ?");
        $totStmt->execute([$sessionId]);
        $totRow = $totStmt->fetch(PDO::FETCH_ASSOC);

        send_api_response([
            'session_id' => (int)$sessionId,
            'session_date' => $sessionDate,
            'marked_students_count' => $markedCount,
            'present_count' => (int)($totRow['pres'] ?? 0),
            'total_marked' => (int)($totRow['total'] ?? 0)
        ], "Attendance marked successfully");
    } else {
        send_api_error("Method not allowed.", [], 405);
    }
} catch (Throwable $e) {
    error_log("Attendance API Error: " . $e->getMessage());
    send_api_error("Error saving attendance: " . $e->getMessage(), [], 500);
}
