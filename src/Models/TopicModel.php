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

    /** Todos los temas (incluso inactivos), para el panel admin */
    public static function allByDelivery(int $deliveryId): array
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

    public static function findByExamId(int $examId): ?array
    {
        return Database::fetch(
            'SELECT * FROM rsgrup_topics WHERE exam_id=?',
            [$examId]
        ) ?: null;
    }

    /**
     * Devuelve true si el alumno ya tiene un intento aprobado en el examen
     * del tema indicado.
     */
    public static function isPassed(int $userId, int $topicId): bool
    {
        $topic = self::findById($topicId);
        if (!$topic || !$topic['exam_id']) return false;
        $attempt = ExamModel::getLastAttempt($userId, (int)$topic['exam_id']);
        return $attempt && ExamModel::isPassing((float)$attempt['score']);
    }

    /**
     * Genera un nombre de fichero seguro para el PDF de un tema.
     */
    public static function pdfFilename(string $title, string $dir): string
    {
        $base = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $title);
        $base = preg_replace('/\s+/', '_', trim((string)$base));
        $base = preg_replace('/[^A-Za-z0-9_\-]/', '', (string)$base);
        $base = trim((string)$base, '_-') ?: 'tema';
        $filename = $base . '.pdf';
        if (!file_exists($dir . '/' . $filename)) return $filename;
        $i = 2;
        do { $filename = $base . '_' . $i . '.pdf'; $i++; } while (file_exists($dir . '/' . $filename));
        return $filename;
    }
}
