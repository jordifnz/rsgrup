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

    public static function field(): string
    {
        // Regenera siempre un token fresco en cada renderizado de formulario
        $_SESSION[self::TOKEN_NAME] = bin2hex(random_bytes(32));
        $token = $_SESSION[self::TOKEN_NAME];
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Verifica el token CSRF.
     * Si falla: guarda flash_error y redirige al referer (o /dashboard).
     * Si pasa: invalida el token one-time para evitar reúso.
     */
    public static function verify(): void
    {
        $token    = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $expected = $_SESSION[self::TOKEN_NAME] ?? '';

        if (!$expected || !hash_equals($expected, $token)) {
            // Invalidar token comprometido
            unset($_SESSION[self::TOKEN_NAME]);

            // Si es petición AJAX devolver JSON
            $isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
                   || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
            if ($isAjax) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Sesión expirada. Recarga la página e inténtalo de nuevo.']);
                exit;
            }

            // Petición normal: flash + redirect
            $_SESSION['flash_error'] = 'Tu sesión ha expirado o el formulario ya fue enviado. Por favor, inténtalo de nuevo.';
            $back = $_SERVER['HTTP_REFERER'] ?? (defined('BASE_URL') ? BASE_URL . '/dashboard' : '/');
            header('Location: ' . $back);
            exit;
        }

        // One-time: invalidar tras uso correcto
        unset($_SESSION[self::TOKEN_NAME]);
    }

    /**
     * Versión que devuelve bool en lugar de redirigir (para uso programático).
     */
    public static function verifyOrFail(): bool
    {
        $token    = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $expected = $_SESSION[self::TOKEN_NAME] ?? '';
        $ok       = $expected && hash_equals($expected, $token);
        if ($ok) {
            unset($_SESSION[self::TOKEN_NAME]);
        }
        return $ok;
    }
}
