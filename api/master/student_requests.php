<?php
// api/master/student_requests.php - Manage Student Join Requests for Master's Dojo
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/api_auth.php';

handle_api_cors();

try {
    $caller = authenticate_api_request($pdo, true);
    require_api_role($caller, ['master', 'super_admin', 'grand_master', 'admin']);

    $masterId = (int)$caller['user_id'];
    $dojoId = $caller['dojo_id'] ?? resolve_user_dojo_id($pdo, $masterId, $caller['role']);

    if (!$dojoId) {
        send_api_error("No active dojo found associated with this Master account.", [], 404);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Fetch all pending requests for this dojo
        $stmt = $pdo->prepare("
            SELECT 
                dm.id as request_id,
                dm.student_id,
                dm.status,
                DATE_FORMAT(dm.created_at, '%d %b %Y') as request_date,
                u.first_name,
                u.last_name,
                u.member_id,
                u.email,
                u.phone,
                u.dob,
                u.gender,
                u.blood_group
            FROM dojo_memberships dm
            JOIN users u ON dm.student_id = u.id
            WHERE dm.dojo_id = ? AND dm.status = 'pending'
            ORDER BY dm.created_at DESC
        ");
        $stmt->execute([$dojoId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $requests = [];
        $idx = 1;
        foreach ($rows as $r) {
            $requests[] = [
                'index' => $idx++,
                'request_id' => (int)$r['request_id'],
                'student_id' => (int)$r['student_id'],
                'student_name' => trim($r['first_name'] . ' ' . $r['last_name']),
                'student_code' => $r['member_id'] ?: sprintf("MD-%05d", (int)$r['student_id']),
                'email' => $r['email'],
                'phone' => $r['phone'],
                'date' => $r['request_date'],
                'training_level' => 'Beginner',
                'experience' => 'None / White Belt',
                'status' => 'Pending'
            ];
        }

        send_api_response($requests, "Pending student requests fetched successfully");
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $raw = file_get_contents("php://input");
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $data = $_POST;
        }

        $action = strtolower(trim($data['action'] ?? ''));
        $requestId = (int)($data['request_id'] ?? 0);
        $studentId = (int)($data['student_id'] ?? 0);

        if (!$requestId && !$studentId) {
            send_api_error("request_id or student_id is required.", [], 400);
        }

        // Verify that this request belongs to a dojo owned by the authenticated master
        if ($requestId > 0) {
            $stmt = $pdo->prepare("SELECT dm.id, dm.student_id, dm.dojo_id, d.master_id FROM dojo_memberships dm JOIN dojos d ON dm.dojo_id = d.id WHERE dm.id = ? LIMIT 1");
            $stmt->execute([$requestId]);
        } else {
            $stmt = $pdo->prepare("SELECT dm.id, dm.student_id, dm.dojo_id, d.master_id FROM dojo_memberships dm JOIN dojos d ON dm.dojo_id = d.id WHERE dm.student_id = ? AND (d.master_id = ? OR dm.dojo_id = ?) LIMIT 1");
            $stmt->execute([$studentId, $masterId, $dojoId]);
        }
        $membership = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$membership) {
            send_api_error("Student request not found.", [], 404);
        }

        if ((int)$membership['master_id'] !== $masterId && (int)$membership['dojo_id'] !== $dojoId && $caller['role'] !== 'super_admin') {
            send_api_error("Access denied: You cannot process requests for another dojo.", [], 403);
        }

        $targetStudentId = (int)$membership['student_id'];
        $membershipId = (int)$membership['id'];

        if ($action === 'approve') {
            // 1. Approve membership
            $pdo->prepare("UPDATE dojo_memberships SET status = 'approved', joined_at = NOW() WHERE id = ?")
                ->execute([$membershipId]);

            // 2. Activate user
            $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?")
                ->execute([$targetStudentId]);

            // 3. Log activity
            $pdo->prepare("INSERT INTO audit_logs (user_id, action, module, record_id, description) VALUES (?, 'STUDENT_APPROVED', 'dojo', ?, ?)")
                ->execute([$masterId, $targetStudentId, "Approved student request for student ID $targetStudentId into Dojo $dojoId"]);

            send_api_response([
                'student_id' => $targetStudentId,
                'status' => 'Active',
                'dojo_id' => $dojoId,
                'master_id' => $masterId
            ], "Student request approved and member activated in dojo.");
        } elseif ($action === 'reject') {
            $reason = trim($data['reason'] ?? 'Application rejected by Dojo Master.');

            $pdo->prepare("UPDATE dojo_memberships SET status = 'rejected' WHERE id = ?")
                ->execute([$membershipId]);

            $pdo->prepare("INSERT INTO audit_logs (user_id, action, module, record_id, description) VALUES (?, 'STUDENT_REJECTED', 'dojo', ?, ?)")
                ->execute([$masterId, $targetStudentId, "Rejected student request for student ID $targetStudentId. Reason: $reason"]);

            send_api_response([
                'student_id' => $targetStudentId,
                'status' => 'Rejected'
            ], "Student request rejected.");
        } else {
            send_api_error("Invalid action. Must be 'approve' or 'reject'.", [], 400);
        }
    } else {
        send_api_error("Method not allowed.", [], 405);
    }
} catch (Throwable $e) {
    error_log("Student Requests API Error: " . $e->getMessage());
    send_api_error("Error processing student request: " . $e->getMessage(), [], 500);
}
