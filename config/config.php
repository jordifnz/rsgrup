<?php
declare(strict_types=1);

// ─── Timezone ───────────────────────────────────────────────────────────────
date_default_timezone_set('Europe/Madrid');

// ─── Environment ─────────────────────────────────────────────────────────────
define('APP_ENV',     getenv('APP_ENV') ?: 'production');
define('APP_DEBUG',   APP_ENV === 'development');
define('APP_NAME',    'RSGrup');
define('APP_VERSION', '1.0.0');

// ─── Paths ───────────────────────────────────────────────────────────────────
define('ROOT_PATH',      dirname(__DIR__));
define('BASE_PATH',      ROOT_PATH);          // alias — usado en index.php e includes
define('SRC_PATH',       ROOT_PATH . '/src');
define('TEMPLATES_PATH', ROOT_PATH . '/templates');
define('PUBLIC_PATH',    ROOT_PATH . '/public');
define('UPLOADS_PATH',   PUBLIC_PATH . '/uploads');
define('LOGS_PATH',      ROOT_PATH . '/logs');

// ─── Base URL ─────────────────────────────────────────────────────────────────
// Detecta automáticamente: http o https, host y subdirectorio si lo hubiera
$_scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$_host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
// Si el DocumentRoot apunta a /public no hay subdirectorio; si no, ajustar aquí:
define('BASE_URL', rtrim($_scheme . '://' . $_host, '/'));
unset($_scheme, $_host);

// ─── Database ─────────────────────────────────────────────────────────────────
define('DB_HOST',    '82.223.107.197');
define('DB_PORT',    '3306');
define('DB_NAME',    'dbapprsgrup');
define('DB_USER',    'adminrsgrup');
define('DB_PASS',    'Abcde12345!)');
define('DB_CHARSET', 'utf8mb4');
define('DB_PREFIX',  'rsgrup_');

// ─── Session ──────────────────────────────────────────────────────────────────
define('SESSION_LIFETIME', 7200);
define('SESSION_NAME',     'rsgrup_sess');

// ─── Security ─────────────────────────────────────────────────────────────────
define('CSRF_TOKEN_LENGTH', 32);
define('BCRYPT_COST',       12);

// ─── Uploads ──────────────────────────────────────────────────────────────────
define('UPLOAD_MAX_SIZE',   20 * 1024 * 1024);
define('ALLOWED_IMG_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
define('ALLOWED_PDF_TYPES', ['application/pdf']);
define('ALLOWED_PNG_TYPES', ['image/png']);

// ─── Roles ────────────────────────────────────────────────────────────────────
define('ROLE_ADMIN',  'admin');
define('ROLE_ALUMNO', 'alumno');

// ─── Delivery types ───────────────────────────────────────────────────────────
define('TYPE_MATRICULA', 'matricula');
define('TYPE_ENTREGA',   'entrega');
define('TYPE_PRACTICA',  'practica');

// ─── Payment types ────────────────────────────────────────────────────────────
define('PAYMENT_ONLINE',     'online');
define('PAYMENT_PRESENCIAL', 'presencial');

// ─── Enrollment statuses ──────────────────────────────────────────────────────
define('STATUS_PENDING',   'pending');
define('STATUS_ACTIVE',    'active');
define('STATUS_CANCELLED', 'cancelled');

// ─── Autoloader ───────────────────────────────────────────────────────────────
spl_autoload_register(function (string $class): void {
    $map = [
        'Controllers\\' => SRC_PATH . '/Controllers/',
        'Models\\'      => SRC_PATH . '/Models/',
        'Services\\'    => SRC_PATH . '/Services/',
        'Helpers\\'     => SRC_PATH . '/Helpers/',
    ];
    foreach ($map as $prefix => $dir) {
        if (str_starts_with($class, $prefix)) {
            $file = $dir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (is_file($file)) require_once $file;
            return;
        }
    }
});

// ─── Error handling ───────────────────────────────────────────────────────────
if (APP_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
    set_error_handler(function (int $errno, string $errstr, string $file, int $line): bool {
        error_log("[RSGrup][$errno] $errstr in $file:$line");
        return true;
    });
    set_exception_handler(function (\Throwable $e): void {
        error_log('[RSGrup] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        http_response_code(500);
        $tpl = TEMPLATES_PATH . '/errors/500.php';
        if (is_file($tpl)) {
            include $tpl;
        } else {
            echo '<h1>Error interno del servidor</h1><p>Por favor, inténtalo de nuevo más tarde.</p>';
        }
        exit;
    });
}
