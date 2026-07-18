<?php
declare(strict_types=1);

function startSession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
        // Regenerate session ID periodically to prevent fixation
        if (!isset($_SESSION['_initiated'])) {
            session_regenerate_id(true);
            $_SESSION['_initiated'] = true;
        }
    }
}

function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

function currentUser(): array|null
{
    if (!isLoggedIn()) return null;
    return [
        'id'     => $_SESSION['user_id'],
        'name'   => $_SESSION['user_name'] ?? '',
        'email'  => $_SESSION['user_email'] ?? '',
        'role'   => $_SESSION['user_role'] ?? ROLE_ALUMNO,
        'avatar' => $_SESSION['user_avatar'] ?? null,
    ];
}

function isAdmin(): bool
{
    return ($_SESSION['user_role'] ?? '') === ROLE_ADMIN;
}

function requireLogin(string $redirectTo = '/login'): void
{
    if (!isLoggedIn()) {
        $referer = $_SERVER['REQUEST_URI'] ?? '';
        if ($referer && $referer !== '/login') {
            $_SESSION['login_redirect'] = $referer;
        }
        redirect($redirectTo);
    }
}

function requireAdmin(): void
{
    requireLogin();
    if (!isAdmin()) {
        redirect('/dashboard');
    }
}

function loginUser(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id']     = $user['id'];
    $_SESSION['user_name']   = $user['nombre'] . ' ' . $user['apellidos'];
    $_SESSION['user_email']  = $user['email'];
    $_SESSION['user_role']   = $user['rol'];
    $_SESSION['user_avatar'] = $user['avatar'] ?? null;
}

function logoutUser(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function flash(string $key, mixed $value = null): mixed
{
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }
    $val = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $val;
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function getLoginRedirect(string $default = '/dashboard'): string
{
    $url = $_SESSION['login_redirect'] ?? $default;
    unset($_SESSION['login_redirect']);
    return $url;
}
