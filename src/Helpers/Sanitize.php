<?php
class Sanitize {
    public static function string(string $value): string {
        return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
    }

    public static function email(string $email): string|false {
        return filter_var(trim($email), FILTER_VALIDATE_EMAIL);
    }

    public static function int(mixed $value): int {
        return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    public static function float(mixed $value): float {
        return (float) filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    public static function slug(string $value): string {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9\s-]/', '', $value);
        return preg_replace('/[\s-]+/', '-', $value);
    }
}
