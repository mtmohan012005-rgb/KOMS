<?php
// database.php - KOMS MySQL connection
require_once __DIR__ . '/config.php';

/*
 * KOMS uses the database named `koms`.
 * We intentionally do NOT read DATABASE_URL / MYSQL_URL here because a
 * platform-provided URL can point to a different default database.
 */
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_port = getenv('DB_PORT') ?: 3306;
$db_user = getenv('DB_USER') ?: getenv('DB_USERNAME') ?: 'root';
$db_password = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '';
$db_name = 'koms';

$dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_TIMEOUT            => 8,
];

// Aiven/remote MySQL connection.
if ($db_host !== 'localhost' && $db_host !== '127.0.0.1') {
    if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }
}

try {
    $pdo = new PDO($dsn, $db_user, $db_password, $options);
} catch (PDOException $e) {
    error_log('KOMS database connection failed: ' . $e->getMessage());

    // Never silently switch to another database. The application must use `koms`.
    if (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'setup.php') {
        return;
    }

    http_response_code(503);
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>KOMS Database Unavailable</title></head><body style="margin:0;min-height:100vh;display:grid;place-items:center;background:#080808;color:#fff;font-family:Arial,sans-serif">';
    echo '<div style="width:min(92%,620px);padding:28px;border:1px solid #c61a1a;border-radius:16px;background:#111;text-align:center;box-shadow:0 18px 50px rgba(0,0,0,.55)">';
    echo '<h2 style="margin:0 0 12px;color:#ffcc00">Database Connection Required</h2>';
    echo '<p style="margin:0;color:#bbb;line-height:1.6">KOMS could not connect to the <strong style="color:#fff">koms</strong> MySQL database. Please verify the Render/Aiven database credentials.</p>';
    echo '</div></body></html>';
    exit;
}
?>
