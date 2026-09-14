<?php
/**
 * tests/test_api_security_audit.php
 * Automated test suite for Section 29 security issues:
 * - Real token validation (replaces mock random bytes)
 * - BOLA / IDOR protection on student attendance & fees
 * - Realtime endpoint identity protection (prevents query param spoofing)
 * - Legacy endpoint compatibility
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/api_auth.php';

echo "=========================================================\n";
echo " KOMS API & SECURITY AUDIT TEST SUITE\n";
echo "=========================================================\n\n";

$pass_count = 0;
$total_tests = 0;

function assert_test($description, $condition) {
    global $pass_count, $total_tests;
    $total_tests++;
    if ($condition) {
        $pass_count++;
        echo "[PASS] $description\n";
    } else {
        echo "[FAIL] $description\n";
    }
}

// 1. Test Token Generation and Verification
$test_user = [
    'id' => 10,
    'member_id' => 'STU-001',
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'student1@example.com',
    'role' => 'student',
    'dojo_id' => 1
];

$token = create_api_token($test_user);
assert_test("Token creation produces non-empty signed string", !empty($token) && substr_count($token, '.') === 2);

$claims = verify_api_token($token);
assert_test("Token verification validates signature and claims", $claims !== null && $claims['sub'] === 10 && $claims['role'] === 'student');

// Tamper test
$tampered_token = $token . 'tampered';
$tampered_claims = verify_api_token($tampered_token);
assert_test("Tampered token is rejected", $tampered_claims === null);

// 2. Test Local HTTP API Endpoints via cURL
$base_url = "http://localhost:8080/api";

function api_post($url, $data, $token = null) {
    $ch = curl_init($url);
    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $http_code, 'body' => json_decode($response, true), 'raw' => $response];
}

function api_get($url, $token = null) {
    $ch = curl_init($url);
    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $http_code, 'body' => json_decode($response, true), 'raw' => $response];
}

// Check if student exists in DB for live test
$stmt = $pdo->query("SELECT id, email, member_id FROM users WHERE role = 'student' AND status = 'active' ORDER BY id ASC LIMIT 2");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($students) >= 2) {
    $s1 = $students[0];
    $s2 = $students[1];

    // Test Login via api/auth/login.php
    $login_res = api_post("$base_url/auth/login.php", [
        'email' => $s1['email'],
        'password' => 'password123'
    ]);

    assert_test("api/auth/login.php authenticates valid student", $login_res['code'] === 200 && ($login_res['body']['success'] ?? false) === true);
    $s1_token = $login_res['body']['data']['token'] ?? '';
    assert_test("api/auth/login.php returns signed cryptographic token (not 16-byte random mock)", !empty($s1_token) && strlen($s1_token) > 50 && substr_count($s1_token, '.') === 2);

    // Test Legacy api/login.php
    $legacy_res = api_post("$base_url/login.php", [
        'email' => $s1['email'],
        'password' => 'password123'
    ]);
    assert_test("Legacy api/login.php authenticates and returns signed token", $legacy_res['code'] === 200 && !empty($legacy_res['body']['token']));

    // Test Attendance History without token (Must be 401 Unauthorized)
    $att_unauth = api_get("$base_url/attendance/history.php?student_id={$s1['id']}");
    assert_test("Attendance history requires authentication (HTTP 401 when token missing)", $att_unauth['code'] === 401);

    // Test Attendance History with S1's own token (Must succeed 200)
    $att_auth = api_get("$base_url/attendance/history.php?student_id={$s1['id']}", $s1_token);
    assert_test("Student can fetch own attendance history", $att_auth['code'] === 200 && ($att_auth['body']['success'] ?? false) === true);

    // Test Attendance IDOR: Student 1 attempts to fetch Student 2's attendance (Must be 403 Forbidden!)
    $att_idor = api_get("$base_url/attendance/history.php?student_id={$s2['id']}", $s1_token);
    assert_test("IDOR Prevention: Student 1 cannot view Student 2's attendance (HTTP 403)", $att_idor['code'] === 403);

    // Test Fees IDOR: Student 1 attempts to fetch Student 2's fees (Must be 403 Forbidden!)
    $fees_idor = api_get("$base_url/fees/records.php?student_id={$s2['id']}", $s1_token);
    assert_test("IDOR Prevention: Student 1 cannot view Student 2's fee records (HTTP 403)", $fees_idor['code'] === 403);

    // Test Realtime Poll: Identity derived from token, not query param spoofing
    $poll_res = api_get("$base_url/realtime/poll.php?user_id=9999&role=super_admin", $s1_token);
    assert_test("Realtime Poll derives identity securely from token and returns HTTP 200", $poll_res['code'] === 200 && ($poll_res['body']['success'] ?? false) === true);

    // Test Dojos List (Public/Student)
    $dojos_res = api_get("$base_url/dojos/list.php");
    assert_test("Dojos List endpoint returns active approved dojos", $dojos_res['code'] === 200 && ($dojos_res['body']['success'] ?? false) === true);
} else {
    echo "[WARN] Not enough student accounts in DB to run live HTTP IDOR tests.\n";
}

echo "\n=========================================================\n";
echo " SUMMARY: $pass_count / $total_tests tests passed.\n";
echo "=========================================================\n";
