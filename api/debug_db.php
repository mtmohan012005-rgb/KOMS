<?php
// api/debug_db.php - Health & Database Inspector for Render Backend
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    $count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $students = $pdo->query("SELECT id, member_id, email, role, status, LEFT(password_hash, 10) as hash_prefix FROM users LIMIT 25")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'connected',
        'db_host' => getenv('DB_HOST') ?: 'default',
        'db_name' => getenv('DB_NAME') ?: 'koms',
        'total_users' => $count,
        'users' => $students
    ], JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
