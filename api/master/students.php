<?php
// api/master/students.php - List Registered Students for Master's Dojo
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

    $sql = "
        SELECT 
            u.id as student_id,
            CONCAT(u.first_name, ' ', u.last_name) as name,
            u.first_name,
            u.last_name,
            u.member_id as student_code,
            u.email,
            u.phone,
            u.dob,
            u.gender,
            u.blood_group,
            DATE_FORMAT(u.date_of_joining, '%d %b %Y') as date_of_joining,
            u.status as user_status
        FROM dojo_memberships dm
        JOIN users u ON dm.student_id = u.id
        WHERE dm.status = 'approved'
    ";
    $params = [];

    if ($caller['role'] !== 'super_admin') {
        $sql .= " AND dm.dojo_id = ?";
        $params[] = $dojoId;
    }

    $sql .= " ORDER BY u.first_name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $students = [];
    $idx = 1;
    foreach ($rows as $r) {
        $sId = (int)$r['student_id'];

        // Latest belt
        $bStmt = $pdo->prepare("SELECT new_belt FROM grading_history WHERE student_id = ? ORDER BY exam_date DESC LIMIT 1");
        $bStmt->execute([$sId]);
        $belt = $bStmt->fetchColumn() ?: 'White Belt';
        $cleanBelt = str_ireplace(' belt', '', $belt);

        // Training level
        $trainingLevel = 'Beginner';
        if (stripos($belt, 'yellow') !== false || stripos($belt, 'orange') !== false) {
            $trainingLevel = 'Intermediate';
        } elseif (stripos($belt, 'green') !== false || stripos($belt, 'blue') !== false || stripos($belt, 'brown') !== false || stripos($belt, 'black') !== false) {
            $trainingLevel = 'Advanced';
        }

        // Attendance %
        $aStmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as pres FROM attendance_entries WHERE student_id = ?");
        $aStmt->execute([$sId]);
        $aRow = $aStmt->fetch(PDO::FETCH_ASSOC);
        $sTot = (int)($aRow['total'] ?? 0);
        $sPres = (int)($aRow['pres'] ?? 0);
        $attPct = $sTot > 0 ? (int)round(($sPres / $sTot) * 100) : 75;

        // Pending fees
        $fStmt = $pdo->prepare("SELECT COALESCE(SUM(amount_due), 0) FROM fee_records WHERE student_id = ? AND status != 'paid'");
        $fStmt->execute([$sId]);
        $pendingFee = (float)$fStmt->fetchColumn();

        $students[] = [
            'index' => $idx++,
            'student_id' => $sId,
            'name' => $r['name'],
            'first_name' => $r['first_name'],
            'last_name' => $r['last_name'],
            'student_code' => $r['student_code'] ?: sprintf("MD-%05d", $sId),
            'belt' => $belt,
            'clean_belt' => $cleanBelt,
            'training_level' => $trainingLevel,
            'attendance_percentage' => $attPct,
            'status' => 'Active',
            'phone' => $r['phone'] ?: 'N/A',
            'email' => $r['email'],
            'pending_fees' => $pendingFee,
            'date_of_joining' => $r['date_of_joining'] ?: '01 Aug 2026'
        ];
    }

    send_api_response($students, "Registered students fetched successfully");

} catch (Throwable $e) {
    error_log("Master Students API Error: " . $e->getMessage());
    send_api_error("Error loading registered students: " . $e->getMessage(), [], 500);
}
