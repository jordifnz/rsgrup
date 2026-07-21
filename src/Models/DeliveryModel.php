<?php
declare(strict_types=1);

class DeliveryModel
{
    public static function findById(int $id): ?array
    {
        return Database::fetch('SELECT * FROM rsgrup_deliveries WHERE id=?', [$id]) ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::fetch('SELECT * FROM rsgrup_deliveries WHERE slug=? AND active=1', [$slug]) ?: null;
    }

    /**
     * Returns all deliveries with enrollment status for a given user.
     */
    public static function getAllWithEnrollmentStatus(int $userId): array
    {
        return Database::fetchAll(
            'SELECT d.*,
                    en.id AS enrollment_id, en.status AS enrollment_status,
                    ea.score AS exam_score
             FROM rsgrup_deliveries d
             LEFT JOIN rsgrup_enrollments en ON en.delivery_id=d.id AND en.user_id=?
             LEFT JOIN rsgrup_exams ex ON ex.id=d.exam_id
             LEFT JOIN rsgrup_exam_attempts ea ON ea.exam_id=ex.id AND ea.user_id=?
             WHERE d.active=1
             ORDER BY d.sort_order ASC',
            [$userId, $userId]
        );
    }

    /**
     * Checks if a user can enroll in a delivery.
     */
    public static function canEnroll(int $userId, int $deliveryId): array
    {
        $delivery = self::findById($deliveryId);
        if (!$delivery) return ['ok' => false, 'reason' => 'Entrega no encontrada.'];

        // Check not already actively enrolled
        $existing = self::getEnrollment($userId, $deliveryId);
        if ($existing && $existing['status'] === 'active') {
            return ['ok' => false, 'reason' => 'Ya estás inscrito en esta entrega.'];
        }

        // Matricula: no prerequisites
        if ($delivery['type'] === 'matricula') {
            return ['ok' => true, 'reason' => ''];
        }

        // All others require active matricula
        $matricula = Database::fetch(
            'SELECT en.* FROM rsgrup_enrollments en
             JOIN rsgrup_deliveries d ON d.id=en.delivery_id
             WHERE en.user_id=? AND d.type="matricula" AND en.status="active"',
            [$userId]
        );
        if (!$matricula) {
            return ['ok' => false, 'reason' => 'Debes completar la matrícula antes de inscribirte a cualquier entrega.'];
        }

        // Previous deliveries by sort_order must all be actively enrolled
        $previous = Database::fetchAll(
            'SELECT d.*, en.status AS enrollment_status, ea.score
             FROM rsgrup_deliveries d
             LEFT JOIN rsgrup_enrollments en ON en.delivery_id=d.id AND en.user_id=?
             LEFT JOIN rsgrup_exams ex ON ex.id=d.exam_id
             LEFT JOIN rsgrup_exam_attempts ea ON ea.exam_id=ex.id AND ea.user_id=?
             WHERE d.sort_order < ? AND d.active=1 AND d.type != "matricula"
             ORDER BY d.sort_order ASC',
            [$userId, $userId, $delivery['sort_order']]
        );

        foreach ($previous as $prev) {
            if (($prev['enrollment_status'] ?? '') !== 'active') {
                return ['ok' => false, 'reason' => 'Debes inscribirte y completar las entregas anteriores en orden.'];
            }
            if ($prev['type'] === 'entrega' && $prev['score'] === null && $prev['exam_id']) {
                return ['ok' => false, 'reason' => 'Debes realizar el exámen de la entrega anterior antes de continuar.'];
            }
        }

        // Practica: all entregas must be completed
        if ($delivery['type'] === 'practica') {
            $pendingEntregas = Database::fetchColumn(
                'SELECT COUNT(*) FROM rsgrup_deliveries d
                 LEFT JOIN rsgrup_enrollments en ON en.delivery_id=d.id AND en.user_id=?
                 WHERE d.type="entrega" AND d.active=1 AND (en.status IS NULL OR en.status != "active")',
                [$userId]
            );
            if ($pendingEntregas > 0) {
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

    /**
     * Crea o actualiza una inscripción.
     *
     * Si ya existe una fila (user_id, delivery_id) con status 'pending' o 'cancelled'
     * (p.ej. el usuario fue a PayPal, canceló y reintenta), se actualiza en lugar de
     * insertar una nueva, evitando el error de clave duplicada en uq_enrollment.
     *
     * Si la fila ya existe con status 'active', canEnroll() ya habrá bloqueado la llamada.
     */
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

    public static function hasCompletedAll(int $userId): bool
    {
        $pending = Database::fetchColumn(
            'SELECT COUNT(*) FROM rsgrup_deliveries d
             LEFT JOIN rsgrup_enrollments en ON en.delivery_id=d.id AND en.user_id=?
             LEFT JOIN rsgrup_exams ex ON ex.id=d.exam_id
             LEFT JOIN rsgrup_exam_attempts ea ON ea.exam_id=ex.id AND ea.user_id=?
             WHERE d.active=1
               AND d.type="entrega"
               AND (en.status IS NULL OR en.status!="active"
                    OR (ex.id IS NOT NULL AND ea.id IS NULL))',
            [$userId, $userId]
        );
        return (int)$pending === 0;
    }
}
