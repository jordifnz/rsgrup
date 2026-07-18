<?php
declare(strict_types=1);

class Sanitize
{
    public static function string(string $value, int $maxLength = 255): string
    {
        return mb_substr(trim(strip_tags($value)), 0, $maxLength);
    }

    public static function email(string $value): string
    {
        return strtolower(trim(filter_var($value, FILTER_SANITIZE_EMAIL)));
    }

    public static function int(mixed $value): int
    {
        return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    public static function float(mixed $value): float
    {
        return (float) filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    public static function slug(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9\s-]/', '', $value);
        $value = preg_replace('/[\s-]+/', '-', $value);
        return trim($value, '-');
    }

    public static function html(string $value): string
    {
        // Allow safe HTML tags for WYSIWYG content
        $allowed = '<p><br><strong><em><u><ul><ol><li><h1><h2><h3><h4><a><img><blockquote><table><thead><tbody><tr><th><td><span><div>';
        return strip_tags($value, $allowed);
    }

    public static function validateRequired(array $fields, array $data): array
    {
        $errors = [];
        foreach ($fields as $field => $label) {
            if (empty($data[$field])) {
                $errors[$field] = "El campo {$label} es obligatorio.";
            }
        }
        return $errors;
    }

    public static function validateEmail(string $email): bool
    {
        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public static function validatePassword(string $pass): bool
    {
        return strlen($pass) >= 8;
    }
}
