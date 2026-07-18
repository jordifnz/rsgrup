<?php
class Csrf {
    public static function generate(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verify(string $token): bool {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function field(): string {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::generate()) . '">';
    }

    public static function check(): void {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!self::verify($token)) {
            http_response_code(403);
            die(json_encode(['error' => 'CSRF token mismatch']));
        }
    }
}
