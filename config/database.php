<?php
// database.php - Database connection using PDO

require_once 'config.php';

$host = 'localhost';
$db_name = 'koms';
$username = 'root'; // Update with your MySQL username
$password = '';     // Update with your MySQL password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false, // Important for security (prevents SQL injection)
];

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
