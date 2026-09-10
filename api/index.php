<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    $dbStatus = isset($pdo) && $pdo ? 'connected' : 'disconnected';
    $dojoCount = isset($pdo) && $pdo ? (int)$pdo->query("SELECT COUNT(*) FROM dojos")->fetchColumn() : 0;
    $userCount = isset($pdo) && $pdo ? (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() : 0;
} catch (\Exception $e) {
    $dbStatus = 'error: ' . $e->getMessage();
    $dojoCount = 0;
    $userCount = 0;
}

echo json_encode([
    'system' => 'Karate Organization Management System (KOMS)',
    'version' => '2.0.0-PROD',
    'status' => 'operational',
    'compliance' => [
        'framework' => 'DPDP Act 2023 (Digital Personal Data Protection)',
        'vpc_enforced' => true,
        'age_threshold' => 18,
        'retention_policy' => 'Strict audit trail with soft delete / immutability'
    ],
    'database' => [
        'status' => $dbStatus,
        'dojos_count' => $dojoCount,
        'registered_users' => $userCount,
        'members' => (isset($pdo) && $pdo) ? $pdo->query("SELECT id, member_id, email, role, status FROM users LIMIT 20")->fetchAll() : []
    ],
    'endpoints' => [
        'auth' => '/api/auth/login.php',
        'get_dojos' => '/api/get_dojos.php',
        'get_attendance' => '/api/get_attendance.php',
        'tournaments' => '/api/tournaments/',
        'fees' => '/api/fees/'
    ],
    'timestamp' => date('c')
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
