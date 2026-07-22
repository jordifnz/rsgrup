<?php
declare(strict_types=1);

/**
 * TopicModel — antes DeliveryModel.
 * Un Tema pertenece a un Curso y tiene exam_id + pdf_file.
 * Los alumnos NO se inscriben a Temas, sino a Entregas.
 */
class TopicModel
{
    public static function findById(int $id): ?array
    {
        return Database::fetch('SELECT * FROM rsgrup_topics WHERE id=?', [$id]) ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::fetch('SELECT * FROM rsgrup_topics WHERE slug=? AND active=1', [$slug]) ?: null;
    }

    public static function findByExamId(int $examId): ?array
    {
        return Database::fetch('SELECT * FROM rsgrup_topics WHERE exam_id=?', [$examId]) ?: null;
    }

    /** Todos los temas de una entrega concreta, ordenados */
    public static function getByDelivery(int $deliveryId): array
    {
        return Database::fetchAll(
            'SELECT t.*, dt.sort_order AS pivot_sort
             FROM rsgrup_topics t
             JOIN rsgrup_delivery_topics dt ON dt.topic_id = t.id
             WHERE dt.delivery_id = ? AND t.active = 1
             ORDER BY dt.sort_order ASC, t.sort_order ASC',
            [$deliveryId]
        );
    }

    /** Todos los temas activos (para el selector admin) */
    public static function getAll(): array
    {
        return Database::fetchAll(
            'SELECT t.*, c.title AS course_title
             FROM rsgrup_topics t
             LEFT JOIN rsgrup_courses c ON c.id = t.course_id
             WHERE t.active = 1
             ORDER BY t.sort_order ASC',
        );
    }

    /** Todos los temas (incluidos inactivos) para el panel admin */
    public static function getAllAdmin(): array
    {
        return Database::fetchAll(
            'SELECT t.*, c.title AS course_title,
                    e.title AS exam_title
             FROM rsgrup_topics t
             LEFT JOIN rsgrup_courses c ON c.id = t.course_id
             LEFT JOIN rsgrup_exams   e ON e.id = t.exam_id
             ORDER BY t.sort_order ASC, t.id ASC'
        );
    }
}
