<?php
declare(strict_types=1);

class ActivityLogger
{
    public static function log(?int $userId, string $action, string $description = '', string $email = ''): void
    {
        $ip = self::getIp();
        try {
            Database::execute(
                'INSERT INTO rsgrup_activity_log (user_id,action,description,ip_address,created_at) VALUES (?,?,?,?,NOW())',
                [$userId, $action, $description, $ip]
            );
        } catch (\Throwable $e) {
            // Silent fail - don't break the app for logging
            error_log('[ActivityLogger] ' . $e->getMessage());
        }
    }

    private static function getIp(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','HTTP_X_REAL_IP','REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                return explode(',', $_SERVER[$key])[0];
            }
        }
        return 'unknown';
    }
}
