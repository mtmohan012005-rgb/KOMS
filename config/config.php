<?php
// config.php - Global configuration settings

define('APP_NAME', 'KOMS - Karate Organization Management System');
if (!defined('APP_URL')) {
    if (isset($_SERVER['HTTP_HOST'])) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $base = ($script_dir === '/' || $script_dir === '.') ? '' : $script_dir;
        $base = preg_replace('#/(admin|master|senior|student|api.*)#', '', $base);
        define('APP_URL', rtrim($protocol . $_SERVER['HTTP_HOST'] . $base, '/'));
    } else {
        define('APP_URL', 'http://localhost/koms');
    }
}
define('APP_ENV', 'development'); // 'development' or 'production'

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// Error reporting based on environment
if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// Global path constants
define('BASE_PATH', dirname(__DIR__));
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('UPLOADS_PATH', BASE_PATH . '/uploads');

date_default_timezone_set('UTC'); // Set appropriate timezone
?>
