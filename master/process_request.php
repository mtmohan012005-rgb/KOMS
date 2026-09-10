<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_role('master');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_msg'] = "Invalid CSRF verification token.";
        redirect('/master/dashboard.php');
    }

    $request_id = (int)($_POST['request_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if (!$request_id || !in_array($action, ['approve', 'reject'])) {
        $_SESSION['error_msg'] = "Invalid action parameters.";
        redirect('/master/dashboard.php');
    }

    // Verify master owns this dojo
    $stmt = $pdo->prepare("SELECT id FROM dojos WHERE master_id = ? AND status = 'approved' LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $dojo_id = $stmt->fetchColumn();

    if (!$dojo_id) {
        $_SESSION['error_msg'] = "You do not have an approved dojo to manage.";
        redirect('/master/dashboard.php');
    }

    $status = ($action === 'approve') ? 'approved' : 'rejected';

    // Update request
    $update = $pdo->prepare("UPDATE dojo_memberships SET status = ?, joined_at = IF(?='approved', NOW(), NULL) WHERE id = ? AND dojo_id = ? AND status = 'pending'");
    if ($update->execute([$status, $status, $request_id, $dojo_id])) {
        $_SESSION['success_msg'] = "Student join request has been {$status} successfully.";
        log_audit_action($pdo, $_SESSION['user_id'], 'UPDATE', 'memberships', $request_id, "Master {$status} student join request #$request_id");
    } else {
        $_SESSION['error_msg'] = "Failed to process the request.";
    }
}

redirect('/master/dashboard.php');
?>
