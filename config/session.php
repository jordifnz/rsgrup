<?php
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'admin';
}

function requireLogin(string $redirect = ''): void {
    if (!isLoggedIn()) {
        $ref = $redirect ?: $_SERVER['REQUEST_URI'] ?? '';
        $target = '/login' . ($ref && $ref !== '/login' ? '?ref=' . urlencode($ref) : '');
        header('Location: ' . BASE_URL . $target);
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        http_response_code(403);
        include BASE_PATH . '/public/403.php';
        exit;
    }
}

function setFlash(string $type, string $message): void {
    $_SESSION['flash'][$type] = $message;
}

function getFlash(string $type): ?string {
    $msg = $_SESSION['flash'][$type] ?? null;
    unset($_SESSION['flash'][$type]);
    return $msg;
}
