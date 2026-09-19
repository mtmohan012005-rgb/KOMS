<?php
// api/admin/master_requests.php - Grand Master Dojo & Master Approvals
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/api_auth.php';

handle_api_cors();

try {
    $caller = authenticate_api_request($pdo, true);
    require_api_role($caller, ['super_admin', 'grand_master', 'admin']);
    $adminId = (int)$caller['user_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $raw_input = file_get_contents("php://input");
        $data = json_decode($raw_input, true) ?: $_POST;

        $requestId = (int)($data['request_id'] ?? $data['dojo_id'] ?? 0);
        $action = strtolower(trim($data['action'] ?? '')); // 'approve' or 'reject'

        if ($requestId <= 0 || !in_array($action, ['approve', 'reject'], true)) {
            send_api_error("Invalid dojo/request ID or action.", [], 400);
        }

        // Fetch target dojo
        $stmt = $pdo->prepare("SELECT id, name, master_id, status FROM dojos WHERE id = ? LIMIT 1");
        $stmt->execute([$requestId]);
        $dojo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$dojo) {
            send_api_error("Dojo application not found.", [], 404);
        }

        $newStatus = ($action === 'approve') ? 'approved' : 'rejected';
        $masterId = (int)$dojo['master_id'];

        if ($action === 'approve') {
            // Approve dojo
            $pdo->prepare("UPDATE dojos SET status = 'approved', approval_date = NOW(), updated_at = NOW() WHERE id = ?")
                ->execute([$requestId]);

            // Activate master user account
            if ($masterId > 0) {
                $pdo->prepare("UPDATE users SET status = 'active', updated_at = NOW() WHERE id = ?")
                    ->execute([$masterId]);
            }

            // Audit log
            $pdo->prepare("INSERT INTO audit_logs (user_id, action, module, record_id, description) VALUES (?, 'DOJO_APPROVED', 'dojos', ?, ?)")
                ->execute([$adminId, $requestId, "Grand Master approved dojo: {$dojo['name']} (Master ID: $masterId)"]);

            send_api_response([
                'dojo_id' => $requestId,
                'name' => $dojo['name'],
                'status' => 'approved',
                'master_id' => $masterId,
                'updated_at' => date('Y-m-d H:i:s')
            ], "Dojo '{$dojo['name']}' approved successfully. It is now published to Common UI.");
        } else {
            $pdo->prepare("UPDATE dojos SET status = 'rejected', updated_at = NOW() WHERE id = ?")
                ->execute([$requestId]);

            $pdo->prepare("INSERT INTO audit_logs (user_id, action, module, record_id, description) VALUES (?, 'DOJO_REJECTED', 'dojos', ?, ?)")
                ->execute([$adminId, $requestId, "Grand Master rejected dojo: {$dojo['name']}"]);

            send_api_response([
                'dojo_id' => $requestId,
                'name' => $dojo['name'],
                'status' => 'rejected',
                'updated_at' => date('Y-m-d H:i:s')
            ], "Dojo request rejected.");
        }
    } else {
        // GET list of pending dojo/master requests
        $stmt = $pdo->prepare("
            SELECT 
                d.id,
                d.name as dojo_name,
                d.location,
                d.contact_number,
                d.email,
                d.training_days,
                d.training_timings,
                d.description,
                DATE_FORMAT(d.created_at, '%d %b %Y') as applied_on,
                d.status,
                d.master_id,
                u.first_name,
                u.last_name,
                u.member_id as master_code
            FROM dojos d
            LEFT JOIN users u ON d.master_id = u.id
            WHERE d.status = 'pending'
            ORDER BY d.created_at DESC
        ");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $requests = [];
        foreach ($rows as $r) {
            $masterName = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
            if (empty($masterName)) $masterName = "Sensei Master";
            $requests[] = [
                'id' => (int)$r['id'],
                'name' => $masterName,
                'master_id' => (int)$r['master_id'],
                'master_code' => $r['master_code'] ?? '',
                'dojo_name' => $r['dojo_name'],
                'location' => $r['location'],
                'phone' => $r['contact_number'] ?? '',
                'applied_on' => $r['applied_on'] ?? date('d M Y'),
                'status' => ucfirst($r['status']),
                'training_schedule' => ($r['training_days'] ?? '') . ' • ' . ($r['training_timings'] ?? '')
            ];
        }

        send_api_response($requests, "Pending dojo applications retrieved successfully");
    }
} catch (Throwable $e) {
    error_log("Grand Master Requests API Error: " . $e->getMessage());
    send_api_error("Error: " . $e->getMessage(), [], 500);
}
