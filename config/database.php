<?php
declare(strict_types=1);

class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
            );
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                ]);
            } catch (PDOException $e) {
                error_log('DB Connection failed: ' . $e->getMessage());
                http_response_code(503);
                die('Error de conexión a la base de datos. Por favor contacte al administrador.');
            }
        }
        return self::$instance;
    }

    // ── Helpers genéricos ───────────────────────────────────────────────────

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function fetchOne(string $sql, array $params = []): array|false
    {
        return self::query($sql, $params)->fetch();
    }

    public static function insert(string $table, array $data): int|string
    {
        $cols   = implode(', ', array_keys($data));
        $places = implode(', ', array_fill(0, count($data), '?'));
        self::query("INSERT INTO `{$table}` ({$cols}) VALUES ({$places})", array_values($data));
        return self::getInstance()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set  = implode(', ', array_map(fn($k) => "`{$k}` = ?", array_keys($data)));
        $stmt = self::query("UPDATE `{$table}` SET {$set} WHERE {$where}", [...array_values($data), ...$whereParams]);
        return $stmt->rowCount();
    }

    public static function delete(string $table, string $where, array $params = []): int
    {
        $stmt = self::query("DELETE FROM `{$table}` WHERE {$where}", $params);
        return $stmt->rowCount();
    }

    public static function count(string $table, string $where = '1', array $params = []): int
    {
        $row = self::fetchOne("SELECT COUNT(*) as n FROM `{$table}` WHERE {$where}", $params);
        return (int)($row['n'] ?? 0);
    }

    public static function beginTransaction(): void { self::getInstance()->beginTransaction(); }
    public static function commit(): void           { self::getInstance()->commit(); }
    public static function rollback(): void         { self::getInstance()->rollBack(); }

    // ── Settings helpers ──────────────────────────────────────────────────

    /**
     * Obtiene el valor de un setting por su clave.
     * Devuelve $default si la clave no existe o está vacía.
     *
     * Uso:  Database::getSetting('whatsapp_contact_number', '')
     */
    public static function getSetting(string $key, mixed $default = null): mixed
    {
        try {
            $row = self::fetchOne(
                'SELECT setting_value FROM rsgrup_settings WHERE setting_key = ? LIMIT 1',
                [$key]
            );
            if ($row === false || $row['setting_value'] === null || $row['setting_value'] === '') {
                return $default;
            }
            return $row['setting_value'];
        } catch (\Throwable) {
            // Si la tabla aún no existe (primera instalación antes del SQL), devolver default
            return $default;
        }
    }

    /**
     * Obtiene múltiples settings de una vez como array asociativo.
     * Si se pasa un array de claves, solo devuelve esas.
     * Si no se pasa nada, devuelve todos los settings.
     *
     * Uso:  $cfg = Database::getSettings(['smtp_host','smtp_port','smtp_user']);
     *       $all = Database::getSettings();
     */
    public static function getSettings(array $keys = []): array
    {
        try {
            if (empty($keys)) {
                $rows = self::fetchAll('SELECT setting_key, setting_value FROM rsgrup_settings');
            } else {
                $placeholders = implode(',', array_fill(0, count($keys), '?'));
                $rows = self::fetchAll(
                    "SELECT setting_key, setting_value FROM rsgrup_settings WHERE setting_key IN ({$placeholders})",
                    $keys
                );
            }
            $result = [];
            foreach ($rows as $row) {
                $result[$row['setting_key']] = $row['setting_value'];
            }
            // Rellenar con null las claves pedidas que no existan en BD
            foreach ($keys as $k) {
                if (!array_key_exists($k, $result)) {
                    $result[$k] = null;
                }
            }
            return $result;
        } catch (\Throwable) {
            return array_fill_keys($keys, null);
        }
    }

    /**
     * Guarda (INSERT o UPDATE) un setting.
     *
     * Uso:  Database::setSetting('smtp_host', 'smtp.gmail.com')
     */
    public static function setSetting(string $key, mixed $value): void
    {
        self::query(
            'INSERT INTO rsgrup_settings (setting_key, setting_value)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
            [$key, $value]
        );
    }
}
