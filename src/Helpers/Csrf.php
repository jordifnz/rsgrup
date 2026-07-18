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
        $token = self::generate();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }

    public static function verify(): void
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!hash_equals($_SESSION[self::TOKEN_NAME] ?? '', $token)) {
            http_response_code(403);
            die(json_encode(['error' => 'CSRF token inválido']));
        }
    }

    public static function verifyOrFail(): bool
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return hash_equals($_SESSION[self::TOKEN_NAME] ?? '', $token);
    }
}
