<?php
class CourseModel {
    public static function getAll(bool $onlyActive = false): array {
        $where = $onlyActive ? 'WHERE active = 1' : '';
        return Database::fetchAll("SELECT * FROM rsgrup_courses $where ORDER BY id ASC");
    }

    public static function findById(int $id): ?array {
        return Database::fetch("SELECT * FROM rsgrup_courses WHERE id = ?", [$id]);
    }

    public static function save(array $data): int {
        $data['slug'] = Sanitize::slug($data['title']);
        if (!empty($data['id'])) {
            $id = (int)$data['id'];
            Database::query(
                "UPDATE rsgrup_courses SET title=:title, description=:description, slug=:slug, active=:active WHERE id=:id",
                [':title'=>$data['title'],':description'=>$data['description']??'',':slug'=>$data['slug'],':active'=>$data['active']??1,':id'=>$id]
            );
            return $id;
        }
        return Database::insert(
            "INSERT INTO rsgrup_courses (title, description, slug, active, created_at) VALUES (:title,:description,:slug,:active,NOW())",
            [':title'=>$data['title'],':description'=>$data['description']??'',':slug'=>$data['slug'],':active'=>$data['active']??1]
        );
    }
}
