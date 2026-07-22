<?php
declare(strict_types=1);

$_configPath = dirname(__DIR__) . '/config/config.php';
if (!is_file($_configPath)) {
    http_response_code(500);
    echo '<h1>Error de configuración</h1><p>No se encuentra config/config.php</p>';
    exit;
}
require_once $_configPath;
unset($_configPath);

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/config/session.php';

require_once BASE_PATH . '/src/Helpers/Router.php';
require_once BASE_PATH . '/src/Helpers/Csrf.php';
require_once BASE_PATH . '/src/Helpers/Sanitize.php';

require_once BASE_PATH . '/src/Models/UserModel.php';
require_once BASE_PATH . '/src/Models/TopicModel.php';
require_once BASE_PATH . '/src/Models/DeliveryModel.php';
require_once BASE_PATH . '/src/Models/ExamModel.php';

require_once BASE_PATH . '/src/Services/ActivityLogger.php';
require_once BASE_PATH . '/src/Services/MailService.php';
require_once BASE_PATH . '/src/Services/WhatsAppService.php';
require_once BASE_PATH . '/src/Services/PayPalService.php';
require_once BASE_PATH . '/src/Services/CertificateService.php';
require_once BASE_PATH . '/src/Services/NotificationService.php';

require_once BASE_PATH . '/src/Controllers/AuthController.php';
require_once BASE_PATH . '/src/Controllers/DashboardController.php';
require_once BASE_PATH . '/src/Controllers/EnrollmentController.php';
require_once BASE_PATH . '/src/Controllers/AdminController.php';

startSession();

$router = new Router();

// --- AUTH ---
$router->get('/login',     [AuthController::class, 'showLogin']);
$router->post('/login',    [AuthController::class, 'login']);
$router->get('/registro',  [AuthController::class, 'showRegister']);
$router->post('/registro', [AuthController::class, 'register']);
$router->get('/logout',    [AuthController::class, 'logout']);
$router->post('/logout',   [AuthController::class, 'logout']);

// --- STUDENT ---
$router->get('/',                [DashboardController::class, 'index']);
$router->get('/dashboard',       [DashboardController::class, 'index']);
$router->get('/perfil',          [DashboardController::class, 'profile']);
$router->post('/perfil/guardar', [DashboardController::class, 'updateProfile']);

// Entregas e inscripciones
$router->get('/entrega/{slug}',               [EnrollmentController::class, 'showDelivery']);
$router->get('/inscribir/{id}',               [EnrollmentController::class, 'showEnroll']);
$router->post('/inscribir',                   [EnrollmentController::class, 'initiate']);
$router->get('/paypal/success',               [EnrollmentController::class, 'paypalSuccess']);
$router->get('/paypal/cancel',                [EnrollmentController::class, 'paypalCancel']);
$router->get('/descargar-pdf/topic/{id}',     [EnrollmentController::class, 'downloadTopicPdf']);
$router->get('/descargar-pdf/{id}',           [EnrollmentController::class, 'downloadPdf']);
$router->get('/descargar-titulo',             [EnrollmentController::class, 'downloadCertificate']);
$router->post('/examen/enviar',               [EnrollmentController::class, 'submitExam']);

// --- ADMIN ---
$router->get('/admin',                                                [AdminController::class, 'dashboard']);

// Cursos
$router->get('/admin/cursos',                                         [AdminController::class, 'courses']);
$router->post('/admin/cursos/guardar',                                [AdminController::class, 'saveCourse']);

// Entregas (nivel inscripción)
$router->get('/admin/entregas',                                       [AdminController::class, 'deliveries']);
$router->post('/admin/entregas/guardar',                              [AdminController::class, 'saveDelivery']);
$router->post('/admin/entregas/{id}/eliminar',                        [AdminController::class, 'deleteDelivery']);
$router->get('/admin/entregas/{id}/inscritos',                        [AdminController::class, 'deliveryEnrolled']);
$router->post('/admin/entregas/{id}/baja/{enrollment_id}',            [AdminController::class, 'deliveryUnenroll']);

// Temas (ex-Entregas)
$router->get('/admin/temas',                                          [AdminController::class, 'topics']);
$router->post('/admin/temas/guardar',                                 [AdminController::class, 'saveTopic']);
$router->post('/admin/temas/{id}/eliminar',                           [AdminController::class, 'deleteTopic']);

// Exámenes
$router->get('/admin/examenes',                                       [AdminController::class, 'exams']);
$router->get('/admin/examenes/nuevo',                                 [AdminController::class, 'examEditor']);
$router->get('/admin/examenes/{id}/editar',                           [AdminController::class, 'examEditor']);
$router->post('/admin/examenes/guardar',                              [AdminController::class, 'saveExam']);
$router->post('/admin/examenes/{id}/eliminar',                        [AdminController::class, 'deleteExam']);

// Usuarios
$router->get('/admin/usuarios',                                       [AdminController::class, 'users']);
$router->get('/admin/usuarios/{id}',                                  [AdminController::class, 'userDetail']);
$router->post('/admin/usuarios/guardar',                              [AdminController::class, 'saveUser']);
$router->post('/admin/usuarios/{id}/baja/{enrollment_id}',            [AdminController::class, 'userUnenroll']);

$router->get('/admin/actividad',                                      [AdminController::class, 'activity']);
$router->get('/admin/settings',                                       [AdminController::class, 'settings']);
$router->post('/admin/settings/guardar',                              [AdminController::class, 'saveSettings']);
$router->post('/admin/settings/token/crear',                          [AdminController::class, 'createApiToken']);
$router->post('/admin/settings/token/{id}/eliminar',                  [AdminController::class, 'deleteApiToken']);
$router->post('/admin/api-tokens/crear',                              [AdminController::class, 'createApiToken']);
$router->post('/admin/api-tokens/{id}/eliminar',                      [AdminController::class, 'deleteApiToken']);

// Títulos masivos
$router->get('/admin/titulos-masivos',                                [AdminController::class, 'titlesBulk']);
$router->post('/admin/titulos-masivos/generar',                       [AdminController::class, 'titlesBulkGenerate']);

$router->dispatch();
