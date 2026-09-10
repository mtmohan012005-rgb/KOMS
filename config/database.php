<?php
// database.php - Database connection using PDO with cloud env support & automatic setup redirect

require_once __DIR__ . '/config.php';

// Support cloud deployment environment variables (Render, Railway, Aiven, etc.)
$db_url = getenv('MYSQL_URL') ?: getenv('DATABASE_URL');
if ($db_url && strpos($db_url, 'mysql:') !== false) {
    $parts = parse_url($db_url);
    $host = $parts['host'] ?? 'localhost';
    $port = $parts['port'] ?? 3306;
    $username = $parts['user'] ?? 'root';
    $password = $parts['pass'] ?? '';
    $db_name = ltrim($parts['path'] ?? 'koms', '/');
} else {
    $host = getenv('DB_HOST') ?: 'localhost';
    $port = getenv('DB_PORT') ?: 3306;
    $db_name = getenv('DB_NAME') ?: getenv('DB_DATABASE') ?: 'koms';
    $username = getenv('DB_USER') ?: getenv('DB_USERNAME') ?: 'root';
    $password = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '';
}

$charset = 'utf8mb4';
$dsn = "mysql:host=$host;port=$port;dbname=$db_name;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (\PDOException $e) {
    // If running setup.php directly, allow setup to proceed
    $current_script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if ($current_script === 'setup.php') {
        return;
    }

    // Gracefully guide user to setup.php instead of raw fatal error crash
    $setup_url = APP_URL . '/setup.php';
    if (!headers_sent()) {
        header("Location: " . $setup_url . "?error=" . urlencode("Database is not connected. Please verify DB credentials or run 1-Click Setup."));
        exit();
    } else {
        echo '<div style="font-family: sans-serif; background: #080808; color: #fff; padding: 2rem; border: 1px solid #f4bd17; border-radius: 8px; max-width: 600px; margin: 3rem auto; text-align: center;">';
        echo '<h2 style="color: #f4bd17; margin-bottom: 1rem;">Database Setup Required</h2>';
        echo '<p style="color: #bbb;">Could not connect to MySQL server. Please ensure database credentials are configured in your environment.</p>';
        echo '<a href="' . $setup_url . '" style="display: inline-block; background: #f4bd17; color: #000; font-weight: bold; padding: 0.8rem 1.5rem; text-decoration: none; border-radius: 6px; margin-top: 1rem;">Run 1-Click Database Setup</a>';
        echo '</div>';
        exit();
    }
}
