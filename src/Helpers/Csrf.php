<?php
declare(strict_types=1);

class Csrf
{
    private const TOKEN_NAME = 'csrf_token';

    public static function generate(): string
    {
        if (empty($_SESSION[self::TOKEN_NAME])) {
            $_SESSION[self::TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::TOKEN_NAME];
    }

    /**
     * Devuelve el campo hidden con el token actual.
     * NO regenera si ya existe un token en sesion, para que multiples
     * formularios en la misma pagina compartan el mismo token.
     * El token se renueva despues de cada verify() exitoso.
     */
    public static function field(): string
    {
        $token = self::generate();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Verifica el token CSRF.
     * Si falla: guarda flash_error y redirige al referer (o /dashboard).
     * Si pasa: renueva el token one-time para la siguiente peticion.
     */
    public static function verify(): void
    {
        $token    = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $expected = $_SESSION[self::TOKEN_NAME] ?? '';

        if (!$expected || !hash_equals($expected, $token)) {
            // Renovar token comprometido
            $_SESSION[self::TOKEN_NAME] = bin2hex(random_bytes(32));

            $isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
                   || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
            if ($isAjax) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Sesion expirada. Recarga la pagina e intentalo de nuevo.']);
                exit;
            }

            $_SESSION['flash_error'] = 'Tu sesion ha expirado o el formulario ya fue enviado. Por favor, intentalo de nuevo.';
            $back = $_SERVER['HTTP_REFERER'] ?? (defined('BASE_URL') ? BASE_URL . '/dashboard' : '/');
            header('Location: ' . $back);
            exit;
        }

        // Renovar token tras uso correcto (one-time)
        $_SESSION[self::TOKEN_NAME] = bin2hex(random_bytes(32));
    }

    /**
     * Version que devuelve bool en lugar de redirigir.
     */
    public static function verifyOrFail(): bool
    {
        $token    = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $expected = $_SESSION[self::TOKEN_NAME] ?? '';
        $ok       = $expected && hash_equals($expected, $token);
        if ($ok) {
            $_SESSION[self::TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $ok;
    }
}
