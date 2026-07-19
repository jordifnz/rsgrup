<?php
declare(strict_types=1);

class AuthController
{
    public function showLogin(array $params = []): void
    {
        if (isset($_SESSION['user_id'])) {
            $redirect = $_SESSION['intended_url'] ?? BASE_URL . '/dashboard';
            unset($_SESSION['intended_url']);
            header('Location: ' . $redirect);
            exit;
        }
        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);
        include BASE_PATH . '/templates/auth/login.php';
    }

    public function login(array $params = []): void
    {
        Csrf::verify();
        $email    = Sanitize::email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $user = UserModel::findByEmail($email);

        // La columna en BD es `password`, no `password_hash`
        if (!$user || empty($user['password']) || !password_verify($password, $user['password'])) {
            ActivityLogger::log(null, 'login_failed', "Intento fallido: {$email}", $email);
            $_SESSION['flash_error'] = 'Email o contraseña incorrectos.';
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        // Start session
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['name'];

        ActivityLogger::log($user['id'], 'login_success', 'Login satisfactorio');

        $redirect = $_SESSION['intended_url'] ?? BASE_URL . '/dashboard';
        unset($_SESSION['intended_url']);
        header('Location: ' . $redirect);
        exit;
    }

    public function showRegister(array $params = []): void
    {
        if (isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
        $error = $_SESSION['flash_error']  ?? null;
        $old   = $_SESSION['flash_old']    ?? [];
        unset($_SESSION['flash_error'], $_SESSION['flash_old']);
        include BASE_PATH . '/templates/auth/register.php';
    }

    public function register(array $params = []): void
    {
        Csrf::verify();

        $name      = Sanitize::string($_POST['name']        ?? '');
        $surnames  = Sanitize::string($_POST['surnames']    ?? '');
        $email     = Sanitize::email($_POST['email']        ?? '');
        $phone     = Sanitize::string($_POST['phone']       ?? '', 20);
        $address   = Sanitize::string($_POST['address']     ?? '');
        $postal    = Sanitize::string($_POST['postal_code'] ?? '', 10);
        $city      = Sanitize::string($_POST['city']        ?? '');
        $province  = Sanitize::string($_POST['province']    ?? '');
        $instagram = Sanitize::string($_POST['instagram']   ?? '', 100);
        $tiktok    = Sanitize::string($_POST['tiktok']      ?? '', 100);
        $password  = $_POST['password']  ?? '';
        $password2 = $_POST['password2'] ?? '';

        // Honeypot anti-bot
        if (!empty($_POST['website'])) {
            header('Location: ' . BASE_URL . '/registro');
            exit;
        }

        $errors = [];
        if (!$name)                             $errors[] = 'El nombre es obligatorio.';
        if (!$surnames)                         $errors[] = 'Los apellidos son obligatorios.';
        if (!Sanitize::validateEmail($email))   $errors[] = 'El email no es válido.';
        if (!Sanitize::validatePassword($password)) $errors[] = 'La contraseña debe tener mínimo 8 caracteres.';
        if ($password !== $password2)           $errors[] = 'Las contraseñas no coinciden.';
        if (UserModel::findByEmail($email))     $errors[] = 'Ese email ya está registrado.';

        if ($errors) {
            $_SESSION['flash_error'] = implode('<br>', $errors);
            $_SESSION['flash_old']   = $_POST;
            header('Location: ' . BASE_URL . '/registro');
            exit;
        }

        $userId = UserModel::create([
            'name'        => $name,
            'surnames'    => $surnames,
            'email'       => $email,
            'phone'       => $phone,
            'address'     => $address,
            'postal_code' => $postal,
            'city'        => $city,
            'province'    => $province,
            'instagram'   => $instagram,
            'tiktok'      => $tiktok,
            'password'    => $password,
            'role'        => 'alumno',
        ]);

        ActivityLogger::log($userId, 'user_registered', "Nuevo registro: {$email}");

        $_SESSION['user_id']   = $userId;
        $_SESSION['user_role'] = 'alumno';
        $_SESSION['user_name'] = $name;

        $redirect = $_SESSION['intended_url'] ?? BASE_URL . '/dashboard';
        unset($_SESSION['intended_url']);
        header('Location: ' . $redirect);
        exit;
    }

    public function logout(array $params = []): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        ActivityLogger::log($userId, 'logout', 'Sesión cerrada');
        session_destroy();
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
}
