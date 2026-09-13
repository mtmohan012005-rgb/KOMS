<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

echo "=== KOMS USER LOGIN AUDIT & VERIFICATION ===\n\n";

$stmt = $pdo->query("SELECT id, member_id, first_name, last_name, email, role, status, dob, password_hash FROM users ORDER BY role, id");
$users = $stmt->fetchAll();

echo "Total registered users found: " . count($users) . "\n\n";

$results = [];

foreach ($users as $u) {
    $candidate_passwords = [
        'password123',
        'password',
        'admin123',
        'master123',
        'student123',
        '123456'
    ];

    if (!empty($u['dob'])) {
        $candidate_passwords[] = date('d.m.Y', strtotime($u['dob']));
        $candidate_passwords[] = date('d-m-Y', strtotime($u['dob']));
        $candidate_passwords[] = date('Y-m-d', strtotime($u['dob']));
        $candidate_passwords[] = date('dmY', strtotime($u['dob']));
    }

    $matched_password = null;
    foreach ($candidate_passwords as $pwd) {
        if (password_verify($pwd, $u['password_hash'])) {
            $matched_password = $pwd;
            break;
        }
    }

    $entry = [
        'id' => (int)$u['id'],
        'member_id' => $u['member_id'],
        'name' => trim($u['first_name'] . ' ' . $u['last_name']),
        'email' => $u['email'],
        'role' => $u['role'],
        'status' => $u['status'],
        'tested_password' => $matched_password,
        'email_login_works' => false,
        'member_id_login_works' => false,
        'api_auth_works' => false
    ];

    if ($matched_password !== null && $u['status'] === 'active') {
        // Test 1: login_user with Email via web auth
        $resEmail = login_user($pdo, $u['email'], $matched_password);
        $entry['email_login_works'] = ($resEmail['success'] === true);

        // Test 2: login_user with Member ID / User ID via web auth
        if (!empty($u['member_id'])) {
            $resMemberId = login_user($pdo, $u['member_id'], $matched_password);
            $entry['member_id_login_works'] = ($resMemberId['success'] === true);
        } else {
            $entry['member_id_login_works'] = false;
        }

        // Test 3: API login query simulation (POST /api/auth/login.php)
        $stmtApi = $pdo->prepare("SELECT id, member_id, email, password_hash, status FROM users WHERE email = ? OR member_id = ? LIMIT 1");
        $stmtApi->execute([$u['member_id'], $u['member_id']]);
        $apiUser = $stmtApi->fetch();
        if ($apiUser && password_verify($matched_password, $apiUser['password_hash'])) {
            $entry['api_auth_works'] = true;
        }
    }

    $results[] = $entry;
}

echo json_encode($results, JSON_PRETTY_PRINT);
