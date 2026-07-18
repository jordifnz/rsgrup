<?php
class UserModel {
    public static function findByEmail(string $email): ?array {
        return Database::fetch("SELECT * FROM rsgrup_users WHERE email = ? LIMIT 1", [$email]);
    }

    public static function findById(int $id): ?array {
        return Database::fetch("SELECT * FROM rsgrup_users WHERE id = ? LIMIT 1", [$id]);
    }

    public static function create(array $data): int {
        return Database::insert(
            "INSERT INTO rsgrup_users (name, surnames, email, password, phone, address, postal_code, city, province, instagram, tiktok, role, created_at)
             VALUES (:name,:surnames,:email,:password,:phone,:address,:postal_code,:city,:province,:instagram,:tiktok,:role, NOW())",
            [
                ':name'        => $data['name'],
                ':surnames'    => $data['surnames'],
                ':email'       => $data['email'],
                ':password'    => password_hash($data['password'], PASSWORD_BCRYPT, ['cost'=>12]),
                ':phone'       => $data['phone'] ?? null,
                ':address'     => $data['address'] ?? null,
                ':postal_code' => $data['postal_code'] ?? null,
                ':city'        => $data['city'] ?? null,
                ':province'    => $data['province'] ?? null,
                ':instagram'   => $data['instagram'] ?? null,
                ':tiktok'      => $data['tiktok'] ?? null,
                ':role'        => $data['role'] ?? 'alumno',
            ]
        );
    }

    public static function update(int $id, array $data): void {
        $fields = [];
        $params = [];
        foreach ($data as $k => $v) {
            $fields[] = "`$k` = :$k";
            $params[":$k"] = $v;
        }
        $params[':id'] = $id;
        Database::query("UPDATE rsgrup_users SET " . implode(',', $fields) . " WHERE id = :id", $params);
    }

    public static function verifyPassword(string $plain, string $hash): bool {
        return password_verify($plain, $hash);
    }

    public static function getInitials(array $user): string {
        $n = mb_substr($user['name'] ?? '', 0, 1);
        $s = mb_substr($user['surnames'] ?? '', 0, 1);
        return mb_strtoupper($n . $s);
    }

    public static function getAll(array $filters = []): array {
        $where = ['1=1'];
        $params = [];
        if (!empty($filters['search'])) {
            $where[] = "(name LIKE :s OR surnames LIKE :s OR email LIKE :s)";
            $params[':s'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['role'])) {
            $where[] = "role = :role";
            $params[':role'] = $filters['role'];
        }
        return Database::fetchAll("SELECT * FROM rsgrup_users WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC", $params);
    }
}
