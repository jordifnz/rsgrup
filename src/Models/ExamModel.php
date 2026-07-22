<?php
declare(strict_types=1);

class ExamModel
{
    /** Nota mínima para aprobar (porcentaje). Configurable desde rsgrup_settings. */
    public static function passingScore(): float
    {
        $val = Database::fetchColumn("SELECT value FROM rsgrup_settings WHERE `key`='exam_passing_score'");
        return ($val !== null && $val !== false) ? (float)$val : 50.0;
    }

    public static function isPassing(float $score): bool
    {
        return $score >= self::passingScore();
    }

    public static function findById(int $id): ?array
    {
        return Database::fetch('SELECT * FROM rsgrup_exams WHERE id=?', [$id]) ?: null;
    }

    public static function findWithQuestions(int $id): ?array
    {
        $exam = self::findById($id);
        if (!$exam) return null;
        $exam['questions'] = Database::fetchAll(
            'SELECT * FROM rsgrup_exam_questions WHERE exam_id=? ORDER BY sort_order ASC', [$id]
        );
        foreach ($exam['questions'] as &$q) {
            $q['answers'] = Database::fetchAll(
                'SELECT * FROM rsgrup_exam_answers WHERE question_id=? ORDER BY id ASC', [$q['id']]
            );
        }
        return $exam;
    }

    /** Último intento del alumno para este examen. */
    public static function getLastAttempt(int $userId, int $examId): ?array
    {
        return Database::fetch(
            'SELECT * FROM rsgrup_exam_attempts WHERE user_id=? AND exam_id=? ORDER BY created_at DESC LIMIT 1',
            [$userId, $examId]
        ) ?: null;
    }

    /** Número total de intentos del alumno para este examen. */
    public static function getAttemptCount(int $userId, int $examId): int
    {
        return (int) Database::fetchColumn(
            'SELECT COUNT(*) FROM rsgrup_exam_attempts WHERE user_id=? AND exam_id=?',
            [$userId, $examId]
        );
    }

    /** Todos los intentos del alumno, del más reciente al más antiguo. */
    public static function getAllAttempts(int $userId, int $examId): array
    {
        return Database::fetchAll(
            'SELECT * FROM rsgrup_exam_attempts WHERE user_id=? AND exam_id=? ORDER BY created_at DESC',
            [$userId, $examId]
        ) ?: [];
    }

    // ── Disponibilidad del examen ──────────────────────────────────────────
    /**
     * Devuelve true si los exámenes están disponibles ahora mismo.
     *
     * Modos configurables (clave exam_schedule en rsgrup_settings):
     *   - last_saturday  → solo el último sábado de cada mes (por defecto)
     *   - always         → siempre accesible
     *   - custom_days    → solo los días de la semana en exam_custom_days
     *                      (cadena con números 0=dom…6=sáb separados por coma)
     *
     * @return array{available: bool, reason: string, next: string|null}
     */
    public static function isAvailable(): array
    {
        $schedule   = Database::fetchColumn("SELECT value FROM rsgrup_settings WHERE `key`='exam_schedule'") ?: 'last_saturday';
        $customDays = Database::fetchColumn("SELECT value FROM rsgrup_settings WHERE `key`='exam_custom_days'") ?: '';

        if ($schedule === 'always') {
            return ['available' => true, 'reason' => '', 'next' => null];
        }

        $now     = new DateTimeImmutable('now', new DateTimeZone('Europe/Madrid'));
        $today   = (int)$now->format('w'); // 0=dom, 6=sáb
        $dayNum  = (int)$now->format('j');
        $month   = (int)$now->format('n');
        $year    = (int)$now->format('Y');

        if ($schedule === 'last_saturday') {
            // Último sábado del mes = último día del mes en que format('w')===6
            $lastDay     = (int)(new DateTimeImmutable("last day of {$year}-{$month}-01"))->format('j');
            $lastSatDay  = $lastDay;
            while ((int)(new DateTimeImmutable("{$year}-{$month}-{$lastSatDay}"))->format('w') !== 6) {
                $lastSatDay--;
            }
            $available = ($dayNum === $lastSatDay);
            if ($available) {
                return ['available' => true, 'reason' => '', 'next' => null];
            }
            // Calcular próximo último sábado
            $nextMonth  = $month === 12 ? 1  : $month + 1;
            $nextYear   = $month === 12 ? $year + 1 : $year;
            $nlastDay   = (int)(new DateTimeImmutable("last day of {$nextYear}-{$nextMonth}-01"))->format('j');
            $nlastSat   = $nlastDay;
            while ((int)(new DateTimeImmutable("{$nextYear}-{$nextMonth}-{$nlastSat}"))->format('w') !== 6) {
                $nlastSat--;
            }
            $nextDate = (new DateTimeImmutable("{$nextYear}-{$nextMonth}-{$nlastSat}"))->format('d/m/Y');
            return [
                'available' => false,
                'reason'    => 'Los exámenes solo están disponibles el último sábado de cada mes.',
                'next'      => $nextDate,
            ];
        }

        if ($schedule === 'custom_days') {
            $allowed = array_filter(array_map('intval', explode(',', $customDays)), fn($d) => $d >= 0 && $d <= 6);
            if (in_array($today, $allowed, true)) {
                return ['available' => true, 'reason' => '', 'next' => null];
            }
            $names = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
            $dayNames = implode(', ', array_map(fn($d) => $names[$d], $allowed));
            return [
                'available' => false,
                'reason'    => 'Los exámenes solo están disponibles los días: ' . $dayNames . '.',
                'next'      => null,
            ];
        }

        // Fallback: siempre disponible
        return ['available' => true, 'reason' => '', 'next' => null];
    }

    /**
     * Evaluate submitted answers against correct answers.
     * Returns score as percentage (0-100).
     */
    public static function evaluate(int $examId, array $submitted): float
    {
        $questions = Database::fetchAll(
            'SELECT * FROM rsgrup_exam_questions WHERE exam_id=? ORDER BY sort_order ASC', [$examId]
        );
        if (!$questions) return 0.0;

        $correct = 0;
        foreach ($questions as $q) {
            $qid     = $q['id'];
            $answers = Database::fetchAll('SELECT * FROM rsgrup_exam_answers WHERE question_id=?', [$qid]);

            $correctIds   = array_column(array_filter($answers, fn($a) => $a['is_correct']), 'id');
            $submittedIds = isset($submitted[$qid]) ? (array)$submitted[$qid] : [];
            $submittedIds = array_map('intval', $submittedIds);

            sort($correctIds);
            sort($submittedIds);

            if ($correctIds === $submittedIds) $correct++;
        }

        return round(($correct / count($questions)) * 100, 2);
    }

    /**
     * Guarda el intento de examen.
     * $enrollmentId es obligatorio por la FK fk_attempts_enrollment.
     */
    public static function saveAttempt(
        int $userId, int $examId, int $enrollmentId, array $submitted, float $score
    ): int {
        Database::execute(
            'INSERT INTO rsgrup_exam_attempts (user_id, exam_id, enrollment_id, score, created_at)
             VALUES (?, ?, ?, ?, NOW())',
            [$userId, $examId, $enrollmentId, $score]
        );
        $attemptId = (int) Database::lastInsertId();

        foreach ($submitted as $questionId => $answerIds) {
            foreach ((array)$answerIds as $answerId) {
                Database::execute(
                    'INSERT INTO rsgrup_exam_attempt_answers (attempt_id, question_id, answer_id)
                     VALUES (?, ?, ?)',
                    [$attemptId, (int)$questionId, (int)$answerId]
                );
            }
        }
        return $attemptId;
    }

    public static function saveQuestions(int $examId, array $questions): void
    {
        // Borrar preguntas y respuestas existentes
        $existing = Database::fetchAll('SELECT id FROM rsgrup_exam_questions WHERE exam_id=?', [$examId]);
        foreach ($existing as $q) {
            Database::execute('DELETE FROM rsgrup_exam_answers WHERE question_id=?', [$q['id']]);
        }
        Database::execute('DELETE FROM rsgrup_exam_questions WHERE exam_id=?', [$examId]);

        foreach ($questions as $idx => $q) {
            $title = Sanitize::string($q['title'] ?? '');
            if (!$title) continue;

            // El formulario envía answer_type (radio|checkbox)
            $type = ($q['answer_type'] ?? $q['type'] ?? 'radio') === 'checkbox' ? 'checkbox' : 'radio';
            $sort = (int)$idx;

            Database::execute(
                'INSERT INTO rsgrup_exam_questions (exam_id, title, answer_type, sort_order, created_at)
                 VALUES (?, ?, ?, ?, NOW())',
                [$examId, $title, $type, $sort]
            );
            $qid = (int) Database::lastInsertId();

            if (!empty($q['answers']) && is_array($q['answers'])) {
                foreach ($q['answers'] as $a) {
                    $aText     = Sanitize::string($a['text'] ?? '');
                    if (!$aText) continue;
                    $isCorrect = !empty($a['is_correct']) ? 1 : 0;
                    Database::execute(
                        'INSERT INTO rsgrup_exam_answers (question_id, text, is_correct) VALUES (?, ?, ?)',
                        [$qid, $aText, $isCorrect]
                    );
                }
            }
        }
    }
}
