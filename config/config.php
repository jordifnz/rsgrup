<?php
define('APP_NAME', 'RSGrup');
define('APP_VERSION', '1.0.0');
define('BASE_PATH', dirname(__DIR__));
define('PUBLIC_PATH', BASE_PATH . '/public');
define('UPLOAD_PATH', PUBLIC_PATH . '/uploads');
define('BASE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));

// DB
define('DB_HOST', '82.223.107.197');
define('DB_PORT', '3306');
define('DB_NAME', 'dbapprsgrup');
define('DB_USER', 'adminrsgrup');
define('DB_PASS', 'Abcde12345!)');
define('DB_CHARSET', 'utf8mb4');

// Session
define('SESSION_NAME', 'rsgrup_session');
define('SESSION_LIFETIME', 7200);

// Timezone
date_default_timezone_set('Europe/Madrid');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', BASE_PATH . '/logs/error.log');
