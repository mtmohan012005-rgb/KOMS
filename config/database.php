<?php
// database.php - Database connection using PDO

require_once 'config.php';

$host = $_SERVER['DB_HOST'] ?? (getenv('DB_HOST') ?: '127.0.0.1');
$db_name = $_SERVER['DB_NAME'] ?? (getenv('DB_NAME') ?: 'koms_db');
$username = $_SERVER['DB_USER'] ?? (getenv('DB_USER') ?: 'root');
$password = $_SERVER['DB_PASSWORD'] ?? (getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '');
$charset = 'utf8mb4';

// If MariaDB is running locally inside the Docker container, use the unix socket for reliability
if ($host === '127.0.0.1' && file_exists('/run/mysqld/mysqld.sock')) {
    $dsn = "mysql:unix_socket=/run/mysqld/mysqld.sock;dbname=$db_name;charset=$charset";
} else {
    $dsn = "mysql:host=$host;port=3306;dbname=$db_name;charset=$charset";
}
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false, // Important for security (prevents SQL injection)
];

// If using external database, try enabling SSL (required for Aiven)
if ($host !== '127.0.0.1') {
    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
}

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (\PDOException $e) {
    if (APP_ENV === 'development') {
        throw new \PDOException($e->getMessage(), (int)$e->getCode());
    } else {
        die("Database connection failed. Please try again later.");
    }
}
?>
