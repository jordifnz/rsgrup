<?php
declare(strict_types=1);

class AuthController
{
    public function showLogin(array $params = []): void
    {
        if (isLoggedIn()) {
            redirect(getLoginRedirect('/dashboard'));
        }

        include BASE_PATH . '/templates/auth/login.php';
    }

    public function login(array $params = []): void
    {
        Csrf::verify();

        $email    = Sanitize::email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            flash('error', ['type' => 'error', 'message' => 'Introduce email y contraseña.']);
            redirect('/login');
        }

        $user = UserModel::findByEmail($email);

        if (!$user || empty($user['password']) || !password_verify($password, $user['password'])) {
            ActivityLogger::log(null, 'login_failed', "Intento fallido: {$email}");
            flash('error', ['type' => 'error', 'message' => 'Email o contraseña incorrectos.']);
            redirect('/login');
        }

        // Iniciar sesión con la función central de session.php
        loginUser($user);

        ActivityLogger::log($user['id'], 'login_success', 'Login satisfactorio');

        redirect(getLoginRedirect('/dashboard'));
    }

    public function showRegister(array $params = []): void
    {
        if (isLoggedIn()) {
            redirect('/dashboard');
        }

        include BASE_PATH . '/templates/auth/register.php';
    }

    public function register(array $params = []): void
    {
        Csrf::verify();

        // Honeypot anti-bot
        if (!empty($_POST['website'])) {
            redirect('/registro');
        }

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

        $errors = [];
        if (!$name)                                  $errors[] = 'El nombre es obligatorio.';
        if (!$surnames)                              $errors[] = 'Los apellidos son obligatorios.';
        if (!Sanitize::validateEmail($email))        $errors[] = 'El email no es válido.';
        if (!Sanitize::validatePassword($password))  $errors[] = 'La contraseña debe tener mínimo 8 caracteres.';
        if ($password !== $password2)                $errors[] = 'Las contraseñas no coinciden.';
        if (UserModel::findByEmail($email))          $errors[] = 'Ese email ya está registrado.';

        if ($errors) {
            flash('error', ['type' => 'error', 'message' => implode('<br>', $errors)]);
            flash('old',   $_POST);
            redirect('/registro');
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

        // Cargar el usuario recién creado para tener todos los campos
        $user = UserModel::findById($userId);
        loginUser($user);

        redirect(getLoginRedirect('/dashboard'));
    }

    public function logout(array $params = []): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        ActivityLogger::log($userId, 'logout', 'Sesión cerrada');
        logoutUser();
        redirect('/login');
    }
}
