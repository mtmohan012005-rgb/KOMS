<?php
// database.php - Database connection using PDO with cloud env support, SSL, and local fallback

require_once __DIR__ . '/config.php';

/*
 * Prefer explicit DB_* variables when they are present.
 * Render is configured with DB_NAME=koms, while some platforms expose a
 * DATABASE_URL whose path can point at a different default database.
 * Using DATABASE_URL first caused KOMS to connect to defaultdb instead of koms.
 */
$has_explicit_db_config = getenv('DB_HOST') !== false
    || getenv('DB_NAME') !== false
    || getenv('DB_USER') !== false
    || getenv('DB_PASSWORD') !== false
    || getenv('DB_PORT') !== false;

$db_url = (!$has_explicit_db_config)
    ? (getenv('MYSQL_URL') ?: getenv('DATABASE_URL'))
    : false;

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
    PDO::ATTR_TIMEOUT             => 5,
];

// If connecting to a remote cloud database (e.g. Aiven Cloud), enable SSL
// while allowing the provider's certificate chain to be handled by the platform.
if ($host !== 'localhost' && $host !== '127.0.0.1') {
    if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }
}

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (\PDOException $e) {
    // If a remote connection fails, attempt the container's internal MariaDB fallback.
    if ($host !== 'localhost' && $host !== '127.0.0.1') {
        try {
            $fallback_dsn = "mysql:host=127.0.0.1;port=3306;dbname=koms;charset=utf8mb4";
            $pdo = new PDO($fallback_dsn, 'root', '', [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT             => 3,
            ]);
            return;
        } catch (\PDOException $e_local) {
            // Local fallback also not ready; continue to the friendly setup handler below.
        }
    }

    // If running setup.php directly, allow setup to proceed.
    $current_script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if ($current_script === 'setup.php') {
        return;
    }

    $setup_url = APP_URL . '/setup.php';
    if (!headers_sent()) {
        header("Location: " . $setup_url . "?error=" . urlencode("Database connection could not be established. Please check credentials or run 1-Click Setup."));
        exit();
    }

    echo '<div style="font-family: sans-serif; background: #080808; color: #fff; padding: 2rem; border: 1px solid #f4bd17; border-radius: 8px; max-width: 600px; margin: 3rem auto; text-align: center;">';
    echo '<h2 style="color: #f4bd17; margin-bottom: 1rem;">Database Setup Required</h2>';
    echo '<p style="color: #bbb;">Could not connect to MySQL server. Please ensure database credentials are configured in your environment.</p>';
    echo '<a href="' . htmlspecialchars($setup_url, ENT_QUOTES, 'UTF-8') . '" style="display: inline-block; background: #f4bd17; color: #000; font-weight: bold; padding: 0.8rem 1.5rem; text-decoration: none; border-radius: 6px; margin-top: 1rem;">Run 1-Click Database Setup</a>';
    echo '</div>';
    exit();
}
