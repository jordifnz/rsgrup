<?php
declare(strict_types=1);

class UserModel
{
    public static function findById(int $id): ?array
    {
        return Database::fetch('SELECT * FROM rsgrup_users WHERE id=?', [$id]) ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        return Database::fetch('SELECT * FROM rsgrup_users WHERE email=?', [$email]) ?: null;
    }

    public static function create(array $data): int
    {
        $hash = password_hash($data['password'], PASSWORD_BCRYPT);
        Database::execute(
            'INSERT INTO rsgrup_users
             (name, surnames, email, phone, address, postal_code, city, province,
              instagram, tiktok, password, role, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())',
            [
                $data['name'],
                $data['surnames']    ?? '',
                $data['email'],
                $data['phone']       ?? '',
                $data['address']     ?? '',
                $data['postal_code'] ?? '',
                $data['city']        ?? '',
                $data['province']    ?? '',
                $data['instagram']   ?? '',
                $data['tiktok']      ?? '',
                $hash,
                $data['role']        ?? 'alumno',
            ]
        );
        return (int) Database::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $sets  = [];
        $binds = [];

        $simple = ['name','surnames','email','phone','address','postal_code',
                   'city','province','instagram','tiktok','role','avatar'];
        foreach ($simple as $field) {
            if (array_key_exists($field, $data)) {
                $sets[]  = "`{$field}`=?";
                $binds[] = $data[$field];
            }
        }

        if (!empty($data['password'])) {
            $sets[]  = '`password`=?';
            $binds[] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        if (empty($sets)) return;

        $sets[]  = 'updated_at=NOW()';
        $binds[] = $id;
        Database::execute(
            'UPDATE rsgrup_users SET ' . implode(',', $sets) . ' WHERE id=?',
            $binds
        );
    }

    public static function getInitials(array $user): string
    {
        $n = mb_substr($user['name']     ?? '', 0, 1);
        $s = mb_substr($user['surnames'] ?? '', 0, 1);
        return strtoupper($n . $s);
    }
}
