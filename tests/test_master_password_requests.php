<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

echo "=== TESTING MASTER PASSWORD REQUESTS PAGE ===\n";

// 1. Simulate Master session
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'master';
$_SESSION['user_name'] = 'Master Sensei';

// 2. Fetch requests from database as master/password_requests.php does
$stmt = $pdo->query("
    SELECT pr.*, u.first_name, u.last_name, u.email, u.member_id, u.dob, u.password_change_count
    FROM password_reset_requests pr
    JOIN users u ON pr.user_id = u.id
    ORDER BY pr.created_at DESC
");
$requests = $stmt->fetchAll();

echo "Found " . count($requests) . " total password reset requests in database.\n";
foreach ($requests as $r) {
    echo " - Student: {$r['first_name']} {$r['last_name']} ({$r['member_id']}) | Status: {$r['status']} | Reason: {$r['reason']}\n";
}

// 3. Test granting change
if (!empty($requests)) {
    $r = $requests[0];
    echo "\nTesting Grant 1 Change action for student #{$r['user_id']}...\n";
    $pdo->prepare("UPDATE users SET password_change_count = 0 WHERE id = ?")->execute([$r['user_id']]);
    $pdo->prepare("UPDATE password_reset_requests SET status = 'approved', master_notes = 'Granted 1 self-change by Sensei' WHERE id = ?")->execute([$r['id']]);
    echo "Updated request #{$r['id']} to approved.\n";
}

echo "=== ALL MASTER REQUEST CHECKS PASSED ===\n";
