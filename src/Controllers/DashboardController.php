<?php
declare(strict_types=1);

class DashboardController
{
    public function index(array $params = []): void
    {
        requireLogin();
        $user      = UserModel::findById($_SESSION['user_id']);
        $deliveries = DeliveryModel::getAllWithEnrollmentStatus($_SESSION['user_id']);
        $metaTitle = 'Dashboard';
        $robots    = 'noindex,nofollow';
        include BASE_PATH . '/templates/student/dashboard.php';
    }

    public function profile(array $params = []): void
    {
        requireLogin();
        $user      = UserModel::findById($_SESSION['user_id']);
        $metaTitle = 'Mi Perfil';
        $robots    = 'noindex,nofollow';
        include BASE_PATH . '/templates/student/profile.php';
    }

    public function updateProfile(array $params = []): void
    {
        requireLogin();
        Csrf::verify();

        $userId = $_SESSION['user_id'];
        $data   = [
            'name'        => Sanitize::string($_POST['name'] ?? ''),
            'surnames'    => Sanitize::string($_POST['surnames'] ?? ''),
            'email'       => Sanitize::email($_POST['email'] ?? ''),
            'phone'       => Sanitize::string($_POST['phone'] ?? '', 20),
            'address'     => Sanitize::string($_POST['address'] ?? ''),
            'postal_code' => Sanitize::string($_POST['postal_code'] ?? '', 10),
            'city'        => Sanitize::string($_POST['city'] ?? ''),
            'province'    => Sanitize::string($_POST['province'] ?? ''),
            'instagram'   => Sanitize::string($_POST['instagram'] ?? '', 100),
            'tiktok'      => Sanitize::string($_POST['tiktok'] ?? '', 100),
        ];

        // Avatar upload
        if (!empty($_FILES['avatar']['tmp_name'])) {
            $ext    = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp'];
            if (in_array($ext, $allowed)) {
                $dest = BASE_PATH . '/public/uploads/avatars/' . $userId . '.' . $ext;
                move_uploaded_file($_FILES['avatar']['tmp_name'], $dest);
                $data['avatar'] = '/uploads/avatars/' . $userId . '.' . $ext;
            }
        }

        // Password change
        $newPass = $_POST['new_password'] ?? '';
        if ($newPass) {
            $user = UserModel::findById($userId);
            if (!password_verify($_POST['current_password'] ?? '', $user['password_hash'])) {
                $_SESSION['flash_error'] = 'La contraseña actual no es correcta.';
                header('Location: ' . BASE_URL . '/perfil');
                exit;
            }
            if (!Sanitize::validatePassword($newPass)) {
                $_SESSION['flash_error'] = 'La nueva contraseña debe tener mínimo 8 caracteres.';
                header('Location: ' . BASE_URL . '/perfil');
                exit;
            }
            $data['password'] = $newPass;
        }

        UserModel::update($userId, $data);
        ActivityLogger::log($userId, 'profile_updated', 'Perfil actualizado');

        $_SESSION['user_name'] = $data['name'];
        $_SESSION['flash_success'] = 'Perfil actualizado correctamente.';
        header('Location: ' . BASE_URL . '/perfil');
        exit;
    }
}
