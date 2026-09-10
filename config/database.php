<?php
// database.php - Database connection using PDO

require_once __DIR__ . '/config.php';

// Read database settings from environment variables.
// Local XAMPP defaults are kept for development.
$host = getenv('DB_HOST');
$host = ($host !== false && trim($host) !== '') ? trim($host) : '127.0.0.1';

$port = getenv('DB_PORT');
$port = ($port !== false && trim($port) !== '') ? trim($port) : '3306';

$db_name = getenv('DB_NAME');
$db_name = ($db_name !== false && trim($db_name) !== '') ? trim($db_name) : 'koms';

$username = getenv('DB_USER');
$username = ($username !== false && trim($username) !== '') ? trim($username) : 'root';

$password = getenv('DB_PASSWORD');
$password = ($password !== false) ? $password : '';

$charset = 'utf8mb4';

// If MariaDB/MySQL is running locally inside the Docker container,
// prefer the Unix socket. Otherwise use TCP.
if ($host === '127.0.0.1' && file_exists('/run/mysqld/mysqld.sock')) {
    $dsn = "mysql:unix_socket=/run/mysqld/mysqld.sock;dbname=" . $db_name . ";charset=" . $charset;
} else {
    $dsn = "mysql:host=" . $host . ";port=" . $port . ";dbname=" . $db_name . ";charset=" . $charset;
}

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// SSL support for external MySQL/MariaDB providers.
if ($host !== '127.0.0.1' && defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
}

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    if (APP_ENV === 'development') {
        throw $e;
    }

    error_log('KOMS database connection failed: ' . $e->getMessage());
    http_response_code(500);
    die('Database connection failed. Please try again later.');
}
?>
