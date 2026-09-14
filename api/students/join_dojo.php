<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/api_auth.php';
require_once '../../includes/realtime.php';

$caller = authenticate_api_request($pdo, true);
require_api_role($caller, ['student']);

$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input);

$dojo_id = isset($data->dojo_id) ? (int)$data->dojo_id : 0;
$student_id = (int)$caller['user_id'];

if ($dojo_id <= 0) {
    send_api_error("dojo_id is required.", [], 400);
}

try {
    // Verify target dojo exists and is approved/active
    $dojo_stmt = $pdo->prepare("SELECT id, name, status, master_id FROM dojos WHERE id = ? LIMIT 1");
    $dojo_stmt->execute([$dojo_id]);
    $dojo = $dojo_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dojo || $dojo['status'] !== 'approved') {
        send_api_error("Dojo is not available for membership requests.", [], 400);
    }

    // Check if student already has a pending or approved membership
    $check_stmt = $pdo->prepare("SELECT id, status FROM dojo_memberships WHERE student_id = ? AND status IN ('pending', 'approved') LIMIT 1");
    $check_stmt->execute([$student_id]);
    $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $status_text = $existing['status'] === 'approved' ? 'already an active member of a dojo' : 'already have a pending request';
        send_api_error("You {$status_text}.", [], 400);
    }

    // Insert new membership request
    $stmt = $pdo->prepare("INSERT INTO dojo_memberships (student_id, dojo_id, status, created_at) VALUES (?, ?, 'pending', NOW())");
    $stmt->execute([$student_id, $dojo_id]);
    $request_id = (int)$pdo->lastInsertId();

    // Audit log
    try {
        log_audit_action($pdo, $student_id, 'REQUEST_MEMBERSHIP', 'dojo_memberships', $request_id, "Requested membership to {$dojo['name']}");
    } catch (Throwable $e) {}

    // Realtime notification to master
    try {
        push_realtime_event($pdo, 'membership_request', [
            'membership_id' => $request_id,
            'student_id' => $student_id,
            'student_name' => $caller['name'],
            'dojo_id' => $dojo_id,
            'dojo_name' => $dojo['name']
        ], (int)$dojo['master_id'], 'master', $dojo_id);
    } catch (Throwable $e) {}

    send_api_response([
        "membership_id" => $request_id,
        "dojo_id" => $dojo_id,
        "status" => "pending"
    ], "Join request sent successfully. Awaiting Master approval.", 201);

} catch (Throwable $e) {
    error_log("Join Dojo API Error: " . $e->getMessage());
    send_api_error("Unable to submit membership request.", [], 500);
}

