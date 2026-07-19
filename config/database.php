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

    // ── Helpers principales ──────────────────────────────────────────

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** Todas las filas como array asociativo. */
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    /** Primera fila o false. */
    public static function fetchOne(string $sql, array $params = []): array|false
    {
        return self::query($sql, $params)->fetch();
    }

    /** Alias de fetchOne() — compatibilidad con modelos. */
    public static function fetch(string $sql, array $params = []): array|false
    {
        return self::fetchOne($sql, $params);
    }

    /** Ejecuta INSERT / UPDATE / DELETE. Devuelve rowCount. */
    public static function execute(string $sql, array $params = []): int
    {
        return self::query($sql, $params)->rowCount();
    }

    /** Valor de la primera columna de la primera fila (p.ej. COUNT). */
    public static function fetchColumn(string $sql, array $params = []): mixed
    {
        return self::query($sql, $params)->fetchColumn();
    }

    /** Último ID insertado. */
    public static function lastInsertId(): string
    {
        return self::getInstance()->lastInsertId();
    }

    // ── CRUD helpers ─────────────────────────────────────────────────

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
        return self::query("DELETE FROM `{$table}` WHERE {$where}", $params)->rowCount();
    }

    public static function count(string $table, string $where = '1', array $params = []): int
    {
        $row = self::fetchOne("SELECT COUNT(*) as n FROM `{$table}` WHERE {$where}", $params);
        return (int)($row['n'] ?? 0);
    }

    public static function beginTransaction(): void { self::getInstance()->beginTransaction(); }
    public static function commit(): void           { self::getInstance()->commit(); }
    public static function rollback(): void         { self::getInstance()->rollBack(); }

    // ── Settings helpers ──────────────────────────────────────────────
    // NOTA: rsgrup_settings usa columnas `key` y `value` (según schema.sql)

    public static function getSetting(string $key, mixed $default = null): mixed
    {
        try {
            $row = self::fetchOne(
                'SELECT `value` FROM rsgrup_settings WHERE `key` = ? LIMIT 1',
                [$key]
            );
            if ($row === false || $row['value'] === null || $row['value'] === '') {
                return $default;
            }
            return $row['value'];
        } catch (\Throwable) {
            return $default;
        }
    }

    public static function getSettings(array $keys = []): array
    {
        try {
            if (empty($keys)) {
                $rows = self::fetchAll('SELECT `key`, `value` FROM rsgrup_settings');
            } else {
                $ph   = implode(',', array_fill(0, count($keys), '?'));
                $rows = self::fetchAll(
                    "SELECT `key`, `value` FROM rsgrup_settings WHERE `key` IN ({$ph})",
                    $keys
                );
            }
            $result = [];
            foreach ($rows as $row) {
                $result[$row['key']] = $row['value'];
            }
            foreach ($keys as $k) {
                if (!array_key_exists($k, $result)) $result[$k] = null;
            }
            return $result;
        } catch (\Throwable) {
            return array_fill_keys($keys, null);
        }
    }

    public static function setSetting(string $key, mixed $value): void
    {
        self::query(
            'INSERT INTO rsgrup_settings (`key`, `value`) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)',
            [$key, $value]
        );
    }
}
