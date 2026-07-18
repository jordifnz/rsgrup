<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/config/session.php';

spl_autoload_register(function(string $class): void {
    $dirs = [
        BASE_PATH . '/src/Models/',
        BASE_PATH . '/src/Services/',
        BASE_PATH . '/src/Helpers/',
    ];
    foreach ($dirs as $dir) {
        $f = $dir . $class . '.php';
        if (file_exists($f)) { require_once $f; return; }
    }
});

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type, X-API-Token');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

function apiAuth(): void {
    $token = $_SERVER['HTTP_X_API_TOKEN'] ?? str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION'] ?? '');
    if (!$token) apiError(401, 'Token requerido');
    $row = Database::fetch('SELECT id FROM rsgrup_api_tokens WHERE token = ?', [$token]);
    if (!$row) apiError(401, 'Token inválido');
    Database::query('UPDATE rsgrup_api_tokens SET last_used_at=NOW() WHERE id=?', [$row['id']]);
}
function apiError(int $code, string $msg): never {
    http_response_code($code);
    echo json_encode(['error' => $msg, 'timestamp' => date('c')]);
    exit;
}
function apiSuccess(mixed $data, int $code = 200): never {
    http_response_code($code);
    echo json_encode(['data' => $data, 'timestamp' => date('c'), 'count' => is_array($data) ? count($data) : 1]);
    exit;
}
function getFilters(array $fields): array {
    $where = ['1=1']; $params = [];
    foreach ($fields as $f) {
        if (isset($_GET[$f]) && $_GET[$f] !== '') {
            $where[] = "`$f` = ?";
            $params[] = $_GET[$f];
        }
    }
    if (isset($_GET['search']) && $_GET['search'] !== '') {
        $where[] = 'CONCAT_WS(\' \',name,surnames,email) LIKE ?';
        $params[] = '%' . $_GET['search'] . '%';
    }
    $limit  = min((int)($_GET['limit'] ?? 50), 200);
    $offset = (int)($_GET['offset'] ?? 0);
    return [$where, $params, $limit, $offset];
}

apiAuth();

$uri   = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
$segs  = explode('/', $uri);
$res   = $segs[2] ?? '';
$resId = isset($segs[3]) && is_numeric($segs[3]) ? (int)$segs[3] : null;
$sub   = $segs[4] ?? '';

switch ($res) {

  case 'users':
    if ($resId) {
        $u = Database::fetch('SELECT id,name,surnames,email,phone,city,province,role,created_at FROM rsgrup_users WHERE id=?', [$resId]);
        if (!$u) apiError(404, 'Usuario no encontrado');
        $u['enrollments'] = Database::fetchAll(
            'SELECT en.*,d.title as delivery_title,d.type FROM rsgrup_enrollments en JOIN rsgrup_deliveries d ON d.id=en.delivery_id WHERE en.user_id=? ORDER BY en.id',
            [$resId]
        );
        apiSuccess($u);
    }
    [$where,$params,$limit,$offset] = getFilters(['role']);
    $rows = Database::fetchAll(
        'SELECT id,name,surnames,email,phone,role,created_at FROM rsgrup_users WHERE '.implode(' AND ',$where).' ORDER BY id DESC LIMIT '.$limit.' OFFSET '.$offset,
        $params
    );
    apiSuccess($rows);

  case 'courses':
    if ($resId) {
        $c = Database::fetch('SELECT * FROM rsgrup_courses WHERE id=?', [$resId]);
        if (!$c) apiError(404, 'Curso no encontrado');
        $c['deliveries'] = Database::fetchAll('SELECT * FROM rsgrup_deliveries WHERE course_id=? AND active=1 ORDER BY sort_order', [$resId]);
        apiSuccess($c);
    }
    apiSuccess(Database::fetchAll('SELECT * FROM rsgrup_courses WHERE active=1'));

  case 'deliveries':
    if ($resId) {
        $d = Database::fetch('SELECT d.*,c.title as course_title FROM rsgrup_deliveries d LEFT JOIN rsgrup_courses c ON c.id=d.course_id WHERE d.id=?', [$resId]);
        if (!$d) apiError(404, 'Entrega no encontrada');
        $d['enrollments_count'] = Database::fetch('SELECT COUNT(*) as n FROM rsgrup_enrollments WHERE delivery_id=? AND status="active"', [$resId])['n'];
        apiSuccess($d);
    }
    [$where,$params,$limit,$offset] = getFilters(['course_id','type','payment_type','active']);
    $rows = Database::fetchAll(
        'SELECT d.*,c.title as course_title FROM rsgrup_deliveries d LEFT JOIN rsgrup_courses c ON c.id=d.course_id WHERE '.implode(' AND ',$where).' ORDER BY d.sort_order LIMIT '.$limit.' OFFSET '.$offset,
        $params
    );
    apiSuccess($rows);

  case 'enrollments':
    if ($resId) {
        $e = Database::fetch(
            'SELECT en.*,u.name,u.surnames,u.email,d.title as delivery_title,d.type FROM rsgrup_enrollments en JOIN rsgrup_users u ON u.id=en.user_id JOIN rsgrup_deliveries d ON d.id=en.delivery_id WHERE en.id=?',
            [$resId]
        );
        $e ? apiSuccess($e) : apiError(404, 'Inscripción no encontrada');
    }
    [$where,$params,$limit,$offset] = getFilters(['user_id','delivery_id','status','payment_method']);
    $rows = Database::fetchAll(
        'SELECT en.*,u.name,u.surnames,u.email,d.title as delivery_title FROM rsgrup_enrollments en JOIN rsgrup_users u ON u.id=en.user_id JOIN rsgrup_deliveries d ON d.id=en.delivery_id WHERE '.implode(' AND ',$where).' ORDER BY en.id DESC LIMIT '.$limit.' OFFSET '.$offset,
        $params
    );
    apiSuccess($rows);

  case 'exams':
    if ($resId) {
        $exam = ExamModel::getWithQuestions($resId);
        $exam ? apiSuccess($exam) : apiError(404, 'Examen no encontrado');
    }
    apiSuccess(ExamModel::getAll());

  case 'attempts':
    if ($resId) {
        $a = Database::fetch('SELECT ea.*,u.name,u.surnames FROM rsgrup_exam_attempts ea JOIN rsgrup_users u ON u.id=ea.user_id WHERE ea.id=?', [$resId]);
        if (!$a) apiError(404, 'Intento no encontrado');
        $a['answers'] = Database::fetchAll('SELECT * FROM rsgrup_exam_attempt_answers WHERE attempt_id=?', [$resId]);
        apiSuccess($a);
    }
    [$where,$params,$limit,$offset] = getFilters(['user_id','exam_id','enrollment_id']);
    $rows = Database::fetchAll(
        'SELECT ea.*,u.name,u.surnames FROM rsgrup_exam_attempts ea JOIN rsgrup_users u ON u.id=ea.user_id WHERE '.implode(' AND ',$where).' ORDER BY ea.id DESC LIMIT '.$limit.' OFFSET '.$offset,
        $params
    );
    apiSuccess($rows);

  case 'activity':
    [$where,$params,$limit,$offset] = getFilters(['user_id','action']);
    $rows = Database::fetchAll(
        'SELECT al.*,u.name,u.email FROM rsgrup_activity_log al LEFT JOIN rsgrup_users u ON u.id=al.user_id WHERE '.implode(' AND ',$where).' ORDER BY al.id DESC LIMIT '.$limit.' OFFSET '.$offset,
        $params
    );
    apiSuccess($rows);

  default:
    apiError(404, 'Endpoint no encontrado. Disponibles: users, courses, deliveries, enrollments, exams, attempts, activity');
}
