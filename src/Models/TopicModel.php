<?php
declare(strict_types=1);

class TopicModel
{
    public static function findById(int $id): ?array
    {
        return Database::fetch('SELECT * FROM rsgrup_topics WHERE id=?', [$id]) ?: null;
    }

    /** Temas activos de una entrega, con título del examen vinculado */
    public static function findByDelivery(int $deliveryId): array
    {
        return Database::fetchAll(
            'SELECT t.*, e.title AS exam_title
             FROM rsgrup_topics t
             LEFT JOIN rsgrup_exams e ON e.id = t.exam_id
             WHERE t.delivery_id = ? AND t.active = 1
             ORDER BY t.sort_order ASC, t.id ASC',
            [$deliveryId]
        );
    }

    /** Todos los temas de una entrega (admin, sin filtro active) */
    public static function findAllByDelivery(int $deliveryId): array
    {
        return Database::fetchAll(
            'SELECT t.*, e.title AS exam_title
             FROM rsgrup_topics t
             LEFT JOIN rsgrup_exams e ON e.id = t.exam_id
             WHERE t.delivery_id = ?
             ORDER BY t.sort_order ASC, t.id ASC',
            [$deliveryId]
        );
    }

    public static function create(array $data): int
    {
        Database::execute(
            'INSERT INTO rsgrup_topics
                 (delivery_id, exam_id, title, description, pdf_file, sort_order, active, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,NOW(),NOW())',
            [
                $data['delivery_id'],
                $data['exam_id']    ?? null,
                $data['title'],
                $data['description'] ?? null,
                $data['pdf_file']   ?? null,
                $data['sort_order'] ?? 0,
                $data['active']     ?? 1,
            ]
        );
        return (int) Database::lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::execute(
            'UPDATE rsgrup_topics
             SET delivery_id=?, exam_id=?, title=?, description=?, pdf_file=?,
                 sort_order=?, active=?, updated_at=NOW()
             WHERE id=?',
            [
                $data['delivery_id'],
                $data['exam_id']    ?? null,
                $data['title'],
                $data['description'] ?? null,
                $data['pdf_file']   ?? null,
                $data['sort_order'] ?? 0,
                $data['active']     ?? 1,
                $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        // Borrar también los adjuntos del tema
        $attachments = self::attachmentsForTopic($id);
        foreach ($attachments as $att) {
            $path = BASE_PATH . '/private_files/attachments/' . $att['filename'];
            if (file_exists($path)) @unlink($path);
        }
        Database::execute('DELETE FROM rsgrup_topic_attachments WHERE topic_id=?', [$id]);
        Database::execute('DELETE FROM rsgrup_topics WHERE id=?', [$id]);
    }

    public static function findByExamId(int $examId): ?array
    {
        return Database::fetch('SELECT * FROM rsgrup_topics WHERE exam_id=?', [$examId]) ?: null;
    }

    public static function attemptsForDelivery(int $userId, int $deliveryId): array
    {
        $rows = Database::fetchAll(
            'SELECT ea.*, t.id AS topic_id
             FROM rsgrup_exam_attempts ea
             JOIN rsgrup_topics t ON t.exam_id = ea.exam_id
                                  AND t.delivery_id = ?
             WHERE ea.user_id = ?
             ORDER BY ea.created_at ASC',
            [$deliveryId, $userId]
        );
        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['topic_id']] = $r;
        }
        return $map;
    }

    /** Adjuntos adicionales de un tema, ordenados por sort_order */
    public static function attachmentsForTopic(int $topicId): array
    {
        try {
            return Database::fetchAll(
                'SELECT * FROM rsgrup_topic_attachments WHERE topic_id=? ORDER BY sort_order ASC, id ASC',
                [$topicId]
            );
        } catch (\Throwable) {
            return [];
        }
    }

    /** Adjuntos de todos los temas de una entrega, indexados por topic_id */
    public static function attachmentsForDelivery(int $deliveryId): array
    {
        try {
            $rows = Database::fetchAll(
                'SELECT ta.* FROM rsgrup_topic_attachments ta
                 JOIN rsgrup_topics t ON t.id = ta.topic_id
                 WHERE t.delivery_id = ?
                 ORDER BY ta.topic_id ASC, ta.sort_order ASC, ta.id ASC',
                [$deliveryId]
            );
            $map = [];
            foreach ($rows as $r) {
                $map[(int)$r['topic_id']][] = $r;
            }
            return $map;
        } catch (\Throwable) {
            return [];
        }
    }
}
