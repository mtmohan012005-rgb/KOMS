<?php
// config.php - Global configuration settings

define('APP_NAME', 'KOMS - Karate Organization Management System');

// Use Render environment variables in production while keeping local XAMPP defaults.
$appUrl = getenv('APP_URL');
if ($appUrl === false || trim($appUrl) === '') {
    $appUrl = 'http://localhost/koms';
}
define('APP_URL', rtrim($appUrl, '/'));

$appEnv = getenv('APP_ENV');
if ($appEnv === false || trim($appEnv) === '') {
    $appEnv = 'development';
}
define('APP_ENV', strtolower(trim($appEnv)));

// Session configuration
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');

// Secure cookies are enabled automatically for HTTPS/production deployments.
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
    || APP_ENV === 'production';
ini_set('session.cookie_secure', $isHttps ? '1' : '0');

// Error reporting based on environment
if (APP_ENV === 'development') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(0);
}

// Global path constants
define('BASE_PATH', dirname(__DIR__));
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('UPLOADS_PATH', BASE_PATH . '/uploads');

$timezone = getenv('APP_TIMEZONE');
if ($timezone === false || trim($timezone) === '') {
    $timezone = 'UTC';
}
date_default_timezone_set($timezone);
?>
