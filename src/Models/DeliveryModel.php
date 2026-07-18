<?php
class DeliveryModel {
    public static function getAll(int $courseId = 0): array {
        $sql = "SELECT d.*, e.title as exam_title, c.title as course_title
                FROM rsgrup_deliveries d
                LEFT JOIN rsgrup_exams e ON e.id = d.exam_id
                LEFT JOIN rsgrup_courses c ON c.id = d.course_id";
        $params = [];
        if ($courseId) {
            $sql .= " WHERE d.course_id = ?";
            $params[] = $courseId;
        }
        $sql .= " ORDER BY d.sort_order ASC";
        return Database::fetchAll($sql, $params);
    }

    public static function findById(int $id): ?array {
        return Database::fetch(
            "SELECT d.*, e.title as exam_title, c.title as course_title
             FROM rsgrup_deliveries d
             LEFT JOIN rsgrup_exams e ON e.id = d.exam_id
             LEFT JOIN rsgrup_courses c ON c.id = d.course_id
             WHERE d.id = ?",
            [$id]
        );
    }

    /**
     * Verifica si el usuario puede inscribirse a esta entrega.
     * Reglas:
     *  - Matrícula: siempre accesible (primera inscripción posible)
     *  - Entrega/Práctica: requiere matrícula activa
     *  - Entrega: requiere que todas las anteriores (sort_order menor) estén activas
     *  - Práctica: requiere todas las entregas completadas (incluyendo exámenes aprobados)
     */
    public static function canEnroll(int $userId, int $deliveryId): array {
        $delivery = self::findById($deliveryId);
        if (!$delivery) return ['can' => false, 'reason' => 'Entrega no encontrada'];
        if (!$delivery['active']) return ['can' => false, 'reason' => 'Entrega no disponible'];

        // Ya inscrito?
        $existing = Database::fetch(
            "SELECT id FROM rsgrup_enrollments WHERE user_id = ? AND delivery_id = ? AND status = 'active'",
            [$userId, $deliveryId]
        );
        if ($existing) return ['can' => false, 'reason' => 'Ya estás inscrito en esta entrega'];

        // Matrícula: siempre puede inscribirse si no lo está ya
        if ($delivery['type'] === 'matricula') {
            return ['can' => true, 'reason' => ''];
        }

        // Verificar matrícula activa
        $hasMatricula = Database::fetch(
            "SELECT en.id FROM rsgrup_enrollments en
             JOIN rsgrup_deliveries d ON d.id = en.delivery_id
             WHERE en.user_id = ? AND d.type = 'matricula' AND d.course_id = ? AND en.status = 'active'",
            [$userId, $delivery['course_id']]
        );
        if (!$hasMatricula) return ['can' => false, 'reason' => 'Debes matricularte primero'];

        // Práctica: requiere TODAS las entregas completadas (PDF descargado + exámen aprobado)
        if ($delivery['type'] === 'practica') {
            $pending = Database::fetch(
                "SELECT COUNT(*) as c FROM rsgrup_deliveries d
                 LEFT JOIN rsgrup_enrollments en ON en.delivery_id = d.id AND en.user_id = ? AND en.status = 'active'
                 WHERE d.course_id = ? AND d.type = 'entrega' AND en.id IS NULL",
                [$userId, $delivery['course_id']]
            );
            if (($pending['c'] ?? 0) > 0) return ['can' => false, 'reason' => 'Debes completar todas las entregas antes de inscribirte a la práctica'];

            // Verificar exámenes aprobados en todas las entregas
            $failedExams = Database::fetch(
                "SELECT COUNT(*) as c FROM rsgrup_deliveries d
                 JOIN rsgrup_enrollments en ON en.delivery_id = d.id AND en.user_id = ? AND en.status = 'active'
                 LEFT JOIN (
                   SELECT delivery_id, MAX(score) as best_score
                   FROM rsgrup_exam_attempts WHERE user_id = ? GROUP BY delivery_id
                 ) ea ON ea.delivery_id = d.id
                 WHERE d.course_id = ? AND d.type = 'entrega' AND d.exam_id IS NOT NULL
                 AND (ea.best_score IS NULL OR ea.best_score < 50)",
                [$userId, $userId, $delivery['course_id']]
            );
            if (($failedExams['c'] ?? 0) > 0) return ['can' => false, 'reason' => 'Debes aprobar todos los exámenes antes'];

            return ['can' => true, 'reason' => ''];
        }

        // Entrega: requiere que las anteriores estén inscritas y exámen aprobado
        $previous = Database::fetchAll(
            "SELECT d.id, d.exam_id FROM rsgrup_deliveries d
             WHERE d.course_id = ? AND d.type = 'entrega' AND d.sort_order < ?
             ORDER BY d.sort_order ASC",
            [$delivery['course_id'], $delivery['sort_order']]
        );
        foreach ($previous as $prev) {
            $enrolled = Database::fetch(
                "SELECT id FROM rsgrup_enrollments WHERE user_id = ? AND delivery_id = ? AND status = 'active'",
                [$userId, $prev['id']]
            );
            if (!$enrolled) return ['can' => false, 'reason' => 'Debes inscribirte y completar las entregas anteriores'];

            if ($prev['exam_id']) {
                $passed = Database::fetch(
                    "SELECT id FROM rsgrup_exam_attempts WHERE user_id = ? AND delivery_id = ? AND score >= 50",
                    [$userId, $prev['id']]
                );
                if (!$passed) return ['can' => false, 'reason' => 'Debes aprobar el exámen de las entregas anteriores'];
            }
        }

        return ['can' => true, 'reason' => ''];
    }

    public static function save(array $data): int {
        if (!empty($data['id'])) {
            $id = (int)$data['id'];
            unset($data['id']);
            $fields = implode(',', array_map(fn($k) => "`$k`=:$k", array_keys($data)));
            $params = array_combine(array_map(fn($k) => ":$k", array_keys($data)), array_values($data));
            $params[':id'] = $id;
            Database::query("UPDATE rsgrup_deliveries SET $fields WHERE id=:id", $params);
            return $id;
        }
        $cols = implode(',', array_map(fn($k) => "`$k`", array_keys($data)));
        $vals = implode(',', array_map(fn($k) => ":$k", array_keys($data)));
        $params = array_combine(array_map(fn($k) => ":$k", array_keys($data)), array_values($data));
        return Database::insert("INSERT INTO rsgrup_deliveries ($cols) VALUES ($vals)", $params);
    }
}
