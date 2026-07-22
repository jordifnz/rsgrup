<?php
declare(strict_types=1);

/**
 * DeliveryModel — nivel de inscripción.
 * Una Entrega pertenece a un Curso, tiene precio y tipo.
 * Contiene varios Temas (TopicModel) a través de rsgrup_delivery_topics.
 */
class DeliveryModel
{
    // ── Finders ───────────────────────────────────────────────

    public static function findById(int $id): ?array
    {
        return Database::fetch('SELECT * FROM rsgrup_deliveries WHERE id=?', [$id]) ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::fetch(
            'SELECT * FROM rsgrup_deliveries WHERE slug=? AND active=1',
            [$slug]
        ) ?: null;
    }

    /** Entregas con estado de inscripción para un alumno */
    public static function getAllWithEnrollmentStatus(int $userId): array
    {
        return Database::fetchAll(
            'SELECT d.*,
                    en.id     AS enrollment_id,
                    en.status AS enrollment_status
             FROM rsgrup_deliveries d
             LEFT JOIN rsgrup_enrollments en
                    ON en.delivery_id = d.id AND en.user_id = ?
             WHERE d.active = 1
             ORDER BY d.sort_order ASC',
            [$userId]
        );
    }

    // ── Inscripciones ──────────────────────────────────────────

    public static function canEnroll(int $userId, int $deliveryId): array
    {
        $delivery = self::findById($deliveryId);
        if (!$delivery) return ['ok' => false, 'reason' => 'Entrega no encontrada.'];

        $existing = self::getEnrollment($userId, $deliveryId);
        if ($existing && $existing['status'] === 'active') {
            return ['ok' => false, 'reason' => 'Ya estás inscrito en esta entrega.'];
        }

        if ($delivery['type'] === 'matricula') {
            return ['ok' => true, 'reason' => ''];
        }

        // Requiere matrícula activa
        $matricula = Database::fetch(
            'SELECT en.* FROM rsgrup_enrollments en
             JOIN rsgrup_deliveries d ON d.id = en.delivery_id
             WHERE en.user_id=? AND d.type="matricula" AND en.status="active"',
            [$userId]
        );
        if (!$matricula) {
            return ['ok' => false, 'reason' => 'Debes completar la matrícula antes de inscribirte a cualquier entrega.'];
        }

        // Las entregas anteriores (por sort_order) deben estar activas
        $previous = Database::fetchAll(
            'SELECT d.*, en.status AS enrollment_status
             FROM rsgrup_deliveries d
             LEFT JOIN rsgrup_enrollments en ON en.delivery_id = d.id AND en.user_id = ?
             WHERE d.sort_order < ? AND d.active = 1 AND d.type != "matricula"
             ORDER BY d.sort_order ASC',
            [$userId, $delivery['sort_order']]
        );

        foreach ($previous as $prev) {
            if (($prev['enrollment_status'] ?? '') !== 'active') {
                return ['ok' => false, 'reason' => 'Debes inscribirte y completar las entregas anteriores en orden.'];
            }
        }

        // Práctica: todas las entregas (tipo entrega) deben estar completadas
        if ($delivery['type'] === 'practica') {
            $pending = Database::fetchColumn(
                'SELECT COUNT(*) FROM rsgrup_deliveries d
                 LEFT JOIN rsgrup_enrollments en ON en.delivery_id = d.id AND en.user_id = ?
                 WHERE d.type="entrega" AND d.active=1
                   AND (en.status IS NULL OR en.status != "active")',
                [$userId]
            );
            if ($pending > 0) {
                return ['ok' => false, 'reason' => 'Debes completar todas las entregas antes de inscribirte en la práctica.'];
            }
        }

        return ['ok' => true, 'reason' => ''];
    }

    public static function getEnrollment(int $userId, int $deliveryId): ?array
    {
        return Database::fetch(
            'SELECT * FROM rsgrup_enrollments WHERE user_id=? AND delivery_id=?',
            [$userId, $deliveryId]
        ) ?: null;
    }

    public static function getEnrollmentById(int $id): ?array
    {
        return Database::fetch('SELECT * FROM rsgrup_enrollments WHERE id=?', [$id]) ?: null;
    }

    public static function createEnrollment(
        int $userId, int $deliveryId, ?string $orderId, string $status
    ): void {
        Database::execute(
            'INSERT INTO rsgrup_enrollments
                 (user_id, delivery_id, paypal_order_id, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                 paypal_order_id = VALUES(paypal_order_id),
                 status          = VALUES(status),
                 updated_at      = NOW()',
            [$userId, $deliveryId, $orderId, $status]
        );
    }

    public static function findEnrollmentByOrder(string $orderId): ?array
    {
        return Database::fetch(
            'SELECT * FROM rsgrup_enrollments WHERE paypal_order_id=?',
            [$orderId]
        ) ?: null;
    }

    public static function activateEnrollment(string $orderId): void
    {
        Database::execute(
            'UPDATE rsgrup_enrollments SET status="active", updated_at=NOW() WHERE paypal_order_id=?',
            [$orderId]
        );
    }

    public static function cancelEnrollment(string $orderId): void
    {
        Database::execute(
            'UPDATE rsgrup_enrollments SET status="cancelled", updated_at=NOW() WHERE paypal_order_id=?',
            [$orderId]
        );
    }

    /**
     * El alumno ha completado todas las entregas activas cuando
     * está inscrito (active) en todas ellas Y todos los temas con
     * examen vinculado tienen al menos un intento aprobado.
     */
    public static function hasCompletedAll(int $userId): bool
    {
        // Todas las entregas activas deben tener inscripción activa
        $pendingDeliveries = Database::fetchColumn(
            'SELECT COUNT(*) FROM rsgrup_deliveries d
             LEFT JOIN rsgrup_enrollments en
                    ON en.delivery_id = d.id AND en.user_id = ?
             WHERE d.active = 1
               AND (en.status IS NULL OR en.status != "active")',
            [$userId]
        );
        if ((int)$pendingDeliveries > 0) return false;

        // Todos los temas con examen deben tener intento aprobado
        $pendingExams = Database::fetchColumn(
            'SELECT COUNT(*)
             FROM rsgrup_topics t
             JOIN rsgrup_delivery_topics dt ON dt.topic_id = t.id
             JOIN rsgrup_deliveries d       ON d.id = dt.delivery_id AND d.active = 1
             JOIN rsgrup_enrollments en     ON en.delivery_id = d.id AND en.user_id = ? AND en.status = "active"
             WHERE t.exam_id IS NOT NULL
               AND NOT EXISTS (
                   SELECT 1 FROM rsgrup_exam_attempts ea
                   WHERE ea.exam_id = t.exam_id AND ea.user_id = ?
                     AND ea.score >= (
                         SELECT COALESCE(CAST(value AS DECIMAL(5,2)), 60)
                         FROM rsgrup_settings WHERE `key` = "passing_score"
                     )
               )',
            [$userId, $userId]
        );
        return (int)$pendingExams === 0;
    }
}
