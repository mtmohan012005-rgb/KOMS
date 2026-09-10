<?php
// config.php - Global configuration settings

define('APP_NAME', 'KOMS - Karate Organization Management System');
if (!defined('APP_URL')) {
    if (getenv('APP_URL')) {
        define('APP_URL', rtrim(getenv('APP_URL'), '/'));
    } elseif (isset($_SERVER['HTTP_HOST'])) {
        $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            || (strpos($_SERVER['HTTP_HOST'], 'onrender.com') !== false);
        $protocol = $is_https ? 'https://' : 'http://';
        $script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $base = ($script_dir === '/' || $script_dir === '.') ? '' : $script_dir;
        $base = preg_replace('#/(admin|master|senior|student|api.*)#', '', $base);
        define('APP_URL', rtrim($protocol . $_SERVER['HTTP_HOST'] . $base, '/'));
    } else {
        define('APP_URL', 'https://koms-backend.onrender.com');
    }
}
define('APP_ENV', getenv('APP_ENV') ?: ((isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'onrender.com') !== false) ? 'production' : 'development'));

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
$is_secure_conn = (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'onrender.com') !== false);
ini_set('session.cookie_secure', $is_secure_conn ? 1 : 0);

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
