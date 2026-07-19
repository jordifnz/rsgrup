<?php
declare(strict_types=1);

class DashboardController
{
    public function index(array $params = []): void
    {
        requireLogin();

        $userId = (int)$_SESSION['user_id'];
        $user   = UserModel::findById($userId);

        // Todas las entregas con estado de inscripción para este usuario
        $rawDeliveries = DeliveryModel::getAllWithEnrollmentStatus($userId);

        // Enriquecer cada entrega con can_enroll y enrollment normalizado
        foreach ($rawDeliveries as &$d) {
            $check          = DeliveryModel::canEnroll($userId, (int)$d['id']);
            $d['can_enroll'] = $check['ok'];
            $d['enrollment'] = [
                'id'     => $d['enrollment_id']     ?? null,
                'status' => $d['enrollment_status'] ?? null,
            ];
            $d['exam_score'] = $d['exam_score'] ?? null;
        }
        unset($d);

        // Agrupar por curso para que la plantilla itere $courses
        $coursesMap = [];
        foreach ($rawDeliveries as $d) {
            $cid = (int)($d['course_id'] ?? 0);
            if (!isset($coursesMap[$cid])) {
                $course = Database::fetch(
                    'SELECT id, title, description FROM rsgrup_courses WHERE id = ? LIMIT 1',
                    [$cid]
                );
                $coursesMap[$cid] = [
                    'id'          => $cid,
                    'title'       => $course['title']       ?? 'Curso',
                    'description' => $course['description'] ?? '',
                    'deliveries'  => [],
                ];
            }
            $coursesMap[$cid]['deliveries'][] = $d;
        }
        $courses = array_values($coursesMap);

        // ¿Puede descargar el título?
        $canDownloadCertificate = DeliveryModel::hasCompletedAll($userId);

        $metaTitle = 'Dashboard';
        $robots    = 'noindex,nofollow';
        include BASE_PATH . '/templates/student/dashboard.php';
    }

    public function profile(array $params = []): void
    {
        requireLogin();
        $user      = UserModel::findById((int)$_SESSION['user_id']);
        $metaTitle = 'Mi Perfil';
        $robots    = 'noindex,nofollow';
        include BASE_PATH . '/templates/student/profile.php';
    }

    public function updateProfile(array $params = []): void
    {
        requireLogin();
        Csrf::verify();

        $userId = (int)$_SESSION['user_id'];
        $data   = [
            'name'        => Sanitize::string($_POST['name']        ?? ''),
            'surnames'    => Sanitize::string($_POST['surnames']    ?? ''),
            'email'       => Sanitize::email($_POST['email']        ?? ''),
            'phone'       => Sanitize::string($_POST['phone']       ?? '', 20),
            'address'     => Sanitize::string($_POST['address']     ?? ''),
            'postal_code' => Sanitize::string($_POST['postal_code'] ?? '', 10),
            'city'        => Sanitize::string($_POST['city']        ?? ''),
            'province'    => Sanitize::string($_POST['province']    ?? ''),
            'instagram'   => Sanitize::string($_POST['instagram']   ?? '', 100),
            'tiktok'      => Sanitize::string($_POST['tiktok']      ?? '', 100),
        ];

        // Avatar upload
        if (!empty($_FILES['avatar']['tmp_name'])) {
            $ext     = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($ext, $allowed, true)) {
                $dest = BASE_PATH . '/public/uploads/avatars/' . $userId . '.' . $ext;
                move_uploaded_file($_FILES['avatar']['tmp_name'], $dest);
                $data['avatar'] = '/uploads/avatars/' . $userId . '.' . $ext;
            }
        }

        // Cambio de contraseña — columna `password`, no `password_hash`
        $newPass = $_POST['new_password'] ?? '';
        if ($newPass) {
            $user = UserModel::findById($userId);
            if (!password_verify($_POST['current_password'] ?? '', $user['password'] ?? '')) {
                flash('error', ['type' => 'error', 'message' => 'La contraseña actual no es correcta.']);
                redirect('/perfil');
            }
            if (!Sanitize::validatePassword($newPass)) {
                flash('error', ['type' => 'error', 'message' => 'La nueva contraseña debe tener mínimo 8 caracteres.']);
                redirect('/perfil');
            }
            $data['password'] = $newPass; // UserModel::update() hará el hash
        }

        UserModel::update($userId, $data);
        ActivityLogger::log($userId, 'profile_updated', 'Perfil actualizado');

        // Actualizar nombre en sesión
        $_SESSION['user_name']     = $data['name'];
        $_SESSION['user_surnames'] = $data['surnames'];

        flash('success', ['type' => 'success', 'message' => 'Perfil actualizado correctamente.']);
        redirect('/perfil');
    }
}
