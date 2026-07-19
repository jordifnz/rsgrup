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

function currentUser(): ?array
{
    if (!isLoggedIn()) return null;
    return [
        'id'       => $_SESSION['user_id'],
        'name'     => $_SESSION['user_name']     ?? '',
        'surnames' => $_SESSION['user_surnames'] ?? '',
        'email'    => $_SESSION['user_email']    ?? '',
        'role'     => $_SESSION['user_role']     ?? ROLE_ALUMNO,
        'avatar'   => $_SESSION['user_avatar']   ?? null,
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

/**
 * Guarda datos del usuario en sesión.
 * Acepta el array tal como lo devuelve la BD: id, name, surnames, email, role, avatar
 */
function loginUser(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id']       = $user['id'];
    $_SESSION['user_name']     = $user['name']     ?? '';
    $_SESSION['user_surnames'] = $user['surnames'] ?? '';
    $_SESSION['user_email']    = $user['email']    ?? '';
    $_SESSION['user_role']     = $user['role']     ?? ROLE_ALUMNO;
    $_SESSION['user_avatar']   = $user['avatar']   ?? null;
}

function logoutUser(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(
            session_name(), '',
            time() - 42000,
            $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']
        );
    }
    session_destroy();
}

/**
 * Guarda un mensaje flash.
 * flash('error', ['type'=>'error','message'=>'...'])  -> guarda
 * flash('error')                                       -> lee y borra
 */
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

/**
 * Helper de conveniencia usado en templates.
 * Devuelve el primer mensaje flash disponible (cualquier clave)
 * como array ['type'=>..., 'message'=>...] o null si no hay ninguno.
 */
function getFlash(): ?array
{
    if (empty($_SESSION['_flash'])) return null;
    // Tomamos el primer mensaje disponible
    $key = array_key_first($_SESSION['_flash']);
    $val = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);
    // Normalizar: si es string, envolverlo
    if (is_string($val)) {
        return ['type' => $key, 'message' => $val];
    }
    return is_array($val) ? $val : null;
}

/**
 * Redirige a una URL. Si no empieza por http, antepone BASE_URL.
 */
function redirect(string $url): never
{
    if (!str_starts_with($url, 'http')) {
        $url = (defined('BASE_URL') ? BASE_URL : '') . $url;
    }
    header('Location: ' . $url);
    exit;
}

function getLoginRedirect(string $default = '/dashboard'): string
{
    $url = $_SESSION['login_redirect'] ?? $default;
    unset($_SESSION['login_redirect']);
    return $url;
}
