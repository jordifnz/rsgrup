<?php
declare(strict_types=1);

define('START_TIME', microtime(true));

require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/config/session.php';

// Autoload
spl_autoload_register(function (string $class): void {
    $dirs = [
        BASE_PATH . '/src/Controllers/',
        BASE_PATH . '/src/Models/',
        BASE_PATH . '/src/Services/',
        BASE_PATH . '/src/Helpers/',
    ];
    foreach ($dirs as $dir) {
        $file = $dir . $class . '.php';
        if (file_exists($file)) { require_once $file; return; }
    }
});

startSession();

$router = new Router();

// --- Auth ---
$router->get('/login',    [AuthController::class, 'showLogin']);
$router->post('/login',   [AuthController::class, 'login']);
$router->get('/registro', [AuthController::class, 'showRegister']);
$router->post('/registro',[AuthController::class, 'register']);
$router->get('/logout',   [AuthController::class, 'logout']);

// --- Student ---
$router->get('/',                  [DashboardController::class, 'index']);
$router->get('/dashboard',         [DashboardController::class, 'index']);
$router->get('/perfil',            [DashboardController::class, 'profile']);
$router->post('/perfil/guardar',   [DashboardController::class, 'updateProfile']);

// --- Entregas ---
$router->get('/entrega/{slug}',        [EnrollmentController::class, 'showDelivery']);
$router->post('/inscribir',            [EnrollmentController::class, 'initiate']);
$router->get('/paypal/success',        [EnrollmentController::class, 'paypalSuccess']);
$router->get('/paypal/cancel',         [EnrollmentController::class, 'paypalCancel']);
$router->get('/descargar-pdf/{id}',    [EnrollmentController::class, 'downloadPdf']);
$router->get('/descargar-titulo',      [EnrollmentController::class, 'downloadCertificate']);
$router->post('/examen/enviar',        [EnrollmentController::class, 'submitExam']);

// --- Admin ---
$router->get('/admin',                              [AdminController::class, 'dashboard']);
$router->get('/admin/cursos',                       [AdminController::class, 'courses']);
$router->post('/admin/cursos/guardar',              [AdminController::class, 'saveCourse']);
$router->get('/admin/entregas',                     [AdminController::class, 'deliveries']);
$router->post('/admin/entregas/guardar',            [AdminController::class, 'saveDelivery']);
$router->post('/admin/entregas/{id}/eliminar',      [AdminController::class, 'deleteDelivery']);
$router->get('/admin/examenes',                     [AdminController::class, 'exams']);
$router->get('/admin/examenes/nuevo',               [AdminController::class, 'newExam']);
$router->get('/admin/examenes/{id}/editar',         [AdminController::class, 'editExam']);
$router->post('/admin/examenes/guardar',            [AdminController::class, 'saveExam']);
$router->post('/admin/examenes/{id}/eliminar',      [AdminController::class, 'deleteExam']);
$router->get('/admin/usuarios',                     [AdminController::class, 'users']);
$router->get('/admin/usuarios/{id}',                [AdminController::class, 'viewUser']);
$router->post('/admin/usuarios/guardar',            [AdminController::class, 'saveUser']);
$router->get('/admin/actividad',                    [AdminController::class, 'activity']);
$router->get('/admin/settings',                     [AdminController::class, 'settings']);
$router->post('/admin/settings/guardar',            [AdminController::class, 'saveSettings']);
$router->post('/admin/api-tokens/crear',            [AdminController::class, 'createApiToken']);
$router->post('/admin/api-tokens/{id}/eliminar',    [AdminController::class, 'deleteApiToken']);

// --- API ---
// Handled by api/v1/index.php via .htaccess rewrite

$router->dispatch();
