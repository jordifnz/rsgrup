<?php
declare(strict_types=1);

class DashboardController
{
    public function index(array $params = []): void
    {
        requireLogin();

        $userId = (int)$_SESSION['user_id'];
        $user   = UserModel::findById($userId);

        if (empty($user)) {
            $user = [
                'id'       => $userId,
                'name'     => $_SESSION['user_name']     ?? '',
                'surnames' => $_SESSION['user_surnames'] ?? '',
                'email'    => $_SESSION['user_email']    ?? '',
                'role'     => $_SESSION['user_role']     ?? ROLE_ALUMNO,
                'avatar'   => $_SESSION['user_avatar']   ?? null,
            ];
        }

        $rawDeliveries = DeliveryModel::getAllWithEnrollmentStatus($userId);

        foreach ($rawDeliveries as &$d) {
            $check           = DeliveryModel::canEnroll($userId, (int)$d['id']);
            $d['can_enroll'] = $check['ok'];
            $d['enrollment'] = [
                'id'     => $d['enrollment_id']     ?? null,
                'status' => $d['enrollment_status'] ?? null,
            ];
            $d['exam_score'] = $d['exam_score'] ?? null;
        }
        unset($d);

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

        $canDownloadCertificate = DeliveryModel::hasCompletedAll($userId);

        $metaTitle = 'Dashboard';
        $robots    = 'noindex,nofollow';
        include BASE_PATH . '/templates/student/dashboard.php';
    }

    public function profile(array $params = []): void
    {
        requireLogin();
        $user = UserModel::findById((int)$_SESSION['user_id']);
        if (empty($user)) {
            $user = [
                'id'       => (int)$_SESSION['user_id'],
                'name'     => $_SESSION['user_name']     ?? '',
                'surnames' => $_SESSION['user_surnames'] ?? '',
                'email'    => $_SESSION['user_email']    ?? '',
                'role'     => $_SESSION['user_role']     ?? ROLE_ALUMNO,
                'avatar'   => $_SESSION['user_avatar']   ?? null,
            ];
        }
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

        // ── Eliminar avatar ─────────────────────────────────────────────
        if (($_POST['delete_avatar'] ?? '0') === '1') {
            // Borrar el archivo físico si existe
            $current = UserModel::findById($userId);
            if (!empty($current['avatar'])) {
                $filePath = BASE_PATH . '/public' . $current['avatar'];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
            $data['avatar'] = null;
            $_SESSION['user_avatar'] = null;
        }
        // ── Subir nuevo avatar ─────────────────────────────────────────
        elseif (!empty($_FILES['avatar']['tmp_name'])) {
            $ext     = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($ext, $allowed, true)) {
                // Borrar avatar anterior si existe
                $current = UserModel::findById($userId);
                if (!empty($current['avatar'])) {
                    $old = BASE_PATH . '/public' . $current['avatar'];
                    if (file_exists($old)) @unlink($old);
                }
                $filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
                $dest     = BASE_PATH . '/public/uploads/avatars/' . $filename;
                move_uploaded_file($_FILES['avatar']['tmp_name'], $dest);
                $data['avatar']          = '/uploads/avatars/' . $filename;
                $_SESSION['user_avatar'] = $data['avatar'];
            }
        }

        // ── Cambio de contraseña ──────────────────────────────────────
        $newPass = $_POST['new_password'] ?? '';
        if ($newPass !== '') {
            $dbUser = UserModel::findById($userId);
            if (!password_verify($_POST['current_password'] ?? '', $dbUser['password'] ?? '')) {
                $_SESSION['flash_error'] = 'La contraseña actual no es correcta.';
                header('Location: ' . BASE_URL . '/perfil'); exit;
            }
            if (strlen($newPass) < 8) {
                $_SESSION['flash_error'] = 'La nueva contraseña debe tener mínimo 8 caracteres.';
                header('Location: ' . BASE_URL . '/perfil'); exit;
            }
            $data['password'] = $newPass;
        }

        UserModel::update($userId, $data);
        ActivityLogger::log($userId, 'profile_updated', 'Perfil actualizado');

        $_SESSION['user_name']     = $data['name'];
        $_SESSION['user_surnames'] = $data['surnames'];
        if (isset($data['avatar'])) {
            $_SESSION['user_avatar'] = $data['avatar'];
        }

        $_SESSION['flash_success'] = 'Perfil actualizado correctamente.';
        header('Location: ' . BASE_URL . '/perfil'); exit;
    }
}
