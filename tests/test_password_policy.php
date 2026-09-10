<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

echo "=== TESTING KOMS PASSWORD POLICY & MASTER PORTAL RESET WORKFLOW ===\n\n";

$errors = [];
$passes = [];

function assert_test($condition, $testName) {
    global $errors, $passes;
    if ($condition) {
        $passes[] = $testName;
        echo "✅ PASS: $testName\n";
    } else {
        $errors[] = $testName;
        echo "❌ FAIL: $testName\n";
    }
}

// 1. Check user columns and table
$stmt = $pdo->query("DESCRIBE users");
$cols = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
assert_test(in_array('password_change_count', $cols), "Column 'password_change_count' exists in users table");
assert_test(in_array('must_change_password', $cols), "Column 'must_change_password' exists in users table");

$tblCheck = $pdo->query("SHOW TABLES LIKE 'password_reset_requests'")->fetchColumn();
assert_test(!empty($tblCheck), "Table 'password_reset_requests' exists");

// 2. Test User ID and DOB Login
$testUserId = 'sairohan2012.koms';
$testDobPass = '20.10.2012';

// Ensure Sai Rohan has initial state
$pdo->prepare("UPDATE users SET password_change_count = 0, must_change_password = 1 WHERE member_id = ?")->execute([$testUserId]);

$loginResult = login_user($pdo, $testUserId, $testDobPass);
assert_test($loginResult['success'] === true, "Login with User ID ($testUserId) and DOB ($testDobPass)");

// 3. Test Gmail Login
$adminLogin = login_user($pdo, 'admin@gmail.com', 'password123');
assert_test($adminLogin['success'] === true, "Login with Gmail (admin@gmail.com)");

// 4. Test 1-Time Password Change Limit Logic
$studentStmt = $pdo->prepare("SELECT * FROM users WHERE member_id = ? LIMIT 1");
$studentStmt->execute([$testUserId]);
$student = $studentStmt->fetch();

assert_test(can_user_change_password($student) === true, "Student with password_change_count = 0 CAN change password");

// Perform first-time change
$newPass = 'NewSecretPass2026!';
$newHash = password_hash($newPass, PASSWORD_DEFAULT);
$pdo->prepare("UPDATE users SET password_hash = ?, password_change_count = 1, must_change_password = 0 WHERE id = ?")->execute([$newHash, $student['id']]);

// Re-fetch student
$studentStmt->execute([$testUserId]);
$student = $studentStmt->fetch();

assert_test((int)$student['password_change_count'] === 1, "Student password_change_count is now 1");
assert_test(can_user_change_password($student) === false, "Student with password_change_count = 1 CANNOT change password directly (Blocked)");

// Verify login with new password
$loginWithNew = login_user($pdo, $testUserId, $newPass);
assert_test($loginWithNew['success'] === true, "Login with newly updated password succeeded");

// 5. Test Password Reset Request to Master Portal
$pdo->prepare("DELETE FROM password_reset_requests WHERE user_id = ?")->execute([$student['id']]);

$ins = $pdo->prepare("INSERT INTO password_reset_requests (user_id, reason, status) VALUES (?, 'Forgot new password after 1-time change', 'pending')");
$ins->execute([$student['id']]);
$reqId = (int)$pdo->lastInsertId();

assert_test($reqId > 0, "Password reset request created successfully (ID: $reqId)");

$pendingReq = get_pending_password_reset_request($pdo, $student['id']);
assert_test(!empty($pendingReq) && $pendingReq['status'] === 'pending', "get_pending_password_reset_request retrieves pending request");

// 6. Test Master Approval: Grant 1 new self-service change
$masterId = (int)$pdo->query("SELECT id FROM users WHERE role = 'master' LIMIT 1")->fetchColumn();
if (!$masterId) $masterId = 1;

// Master approves and grants change
$pdo->prepare("UPDATE users SET password_change_count = 0 WHERE id = ?")->execute([$student['id']]);
$pdo->prepare("UPDATE password_reset_requests SET status = 'approved', reviewed_by = ?, reviewed_at = NOW() WHERE id = ?")->execute([$masterId, $reqId]);

$studentStmt->execute([$testUserId]);
$student = $studentStmt->fetch();
assert_test((int)$student['password_change_count'] === 0, "Master approval reset student password_change_count to 0");
assert_test(can_user_change_password($student) === true, "Student can now perform 1 self-service change again");

// 7. Test Reset to DOB Password Option
$dobFormatted = format_dob_password($student['dob']);
assert_test($dobFormatted === '20.10.2012', "format_dob_password correctly formats DOB: $dobFormatted");

$dobHash = password_hash($dobFormatted, PASSWORD_DEFAULT);
$pdo->prepare("UPDATE users SET password_hash = ?, password_change_count = 0, must_change_password = 1 WHERE id = ?")->execute([$dobHash, $student['id']]);

$loginDobAgain = login_user($pdo, $testUserId, $dobFormatted);
assert_test($loginDobAgain['success'] === true, "Login with restored DOB password ($dobFormatted) succeeded");

echo "\n=== TEST SUMMARY: " . count($passes) . " PASSED, " . count($errors) . " FAILED ===\n";
if (!empty($errors)) {
    exit(1);
}
echo "ALL TESTS PASSED PERFECTLY!\n";
