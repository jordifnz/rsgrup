<?php
declare(strict_types=1);

class ExamModel
{
    public static function findById(int $id): ?array
    {
        return Database::fetch('SELECT * FROM rsgrup_exams WHERE id=?', [$id]) ?: null;
    }

    public static function findWithQuestions(int $id): ?array
    {
        $exam = self::findById($id);
        if (!$exam) return null;
        $exam['questions'] = self::getQuestions($id);
        return $exam;
    }

    public static function getQuestions(int $examId): array
    {
        $questions = Database::fetchAll(
            'SELECT * FROM rsgrup_exam_questions WHERE exam_id=? ORDER BY sort_order ASC, id ASC',
            [$examId]
        );
        foreach ($questions as &$q) {
            $q['answers'] = Database::fetchAll(
                'SELECT * FROM rsgrup_exam_answers WHERE question_id=? ORDER BY sort_order ASC, id ASC',
                [$q['id']]
            );
        }
        return $questions;
    }

    public static function saveQuestions(int $examId, array $questions): void
    {
        // Borrar las preguntas antiguas (cascade borra respuestas)
        Database::execute('DELETE FROM rsgrup_exam_questions WHERE exam_id=?', [$examId]);
        foreach ($questions as $order => $q) {
            $title = trim($q['title'] ?? '');
            if ($title === '') continue;
            $answerType = ($q['answer_type'] ?? 'radio') === 'checkbox' ? 'checkbox' : 'radio';
            Database::execute(
                'INSERT INTO rsgrup_exam_questions (exam_id, title, description, answer_type, sort_order, created_at)
                 VALUES (?,?,?,?,?,NOW())',
                [$examId, $title, $q['description'] ?? null, $answerType, (int)$order]
            );
            $qId = (int) Database::lastInsertId();
            foreach (($q['answers'] ?? []) as $ai => $a) {
                $text = trim($a['text'] ?? '');
                if ($text === '') continue;
                Database::execute(
                    'INSERT INTO rsgrup_exam_answers (question_id, text, is_correct, sort_order)
                     VALUES (?,?,?,?)',
                    [$qId, $text, isset($a['is_correct']) ? 1 : 0, (int)$ai]
                );
            }
        }
    }

    /** Calcula la nota (0-100) */
    public static function evaluate(int $examId, array $submitted): float
    {
        $questions = self::getQuestions($examId);
        if (!$questions) return 0.0;
        $correct = 0;
        foreach ($questions as $q) {
            $qId        = (int)$q['id'];
            $submitted1 = isset($submitted[$qId])
                ? (is_array($submitted[$qId]) ? $submitted[$qId] : [$submitted[$qId]])
                : [];
            $submitted1 = array_map('intval', $submitted1);
            $correctIds = array_map('intval', array_column(
                array_filter($q['answers'], fn($a) => (int)$a['is_correct'] === 1),
                'id'
            ));
            sort($submitted1);
            sort($correctIds);
            if ($submitted1 === $correctIds) $correct++;
        }
        return round(($correct / count($questions)) * 100, 2);
    }

    public static function saveAttempt(
        int $userId, int $examId, int $enrollmentId, array $submitted, float $score,
        ?int $topicId = null
    ): int {
        Database::execute(
            'INSERT INTO rsgrup_exam_attempts
                 (user_id, exam_id, topic_id, enrollment_id, score, total_q, correct_q, submitted_at, created_at)
             VALUES (?,?,?,?,?,
                     (SELECT COUNT(*) FROM rsgrup_exam_questions WHERE exam_id=?),
                     (SELECT COUNT(*) FROM rsgrup_exam_questions WHERE exam_id=?),
                     NOW(), NOW())',
            [$userId, $examId, $topicId, $enrollmentId, $score, $examId, $examId]
        );
        return (int) Database::lastInsertId();
    }

    public static function getLastAttempt(int $userId, int $examId): ?array
    {
        return Database::fetch(
            'SELECT * FROM rsgrup_exam_attempts WHERE user_id=? AND exam_id=? ORDER BY created_at DESC LIMIT 1',
            [$userId, $examId]
        ) ?: null;
    }

    public static function getAllAttempts(int $userId, int $examId): array
    {
        return Database::fetchAll(
            'SELECT * FROM rsgrup_exam_attempts WHERE user_id=? AND exam_id=? ORDER BY created_at ASC',
            [$userId, $examId]
        );
    }

    public static function getAttemptCount(int $userId, int $examId): int
    {
        return (int) Database::fetchColumn(
            'SELECT COUNT(*) FROM rsgrup_exam_attempts WHERE user_id=? AND exam_id=?',
            [$userId, $examId]
        );
    }

    public static function passingScore(): float
    {
        $v = Database::fetchColumn(
            'SELECT value FROM rsgrup_settings WHERE `key`="passing_score" LIMIT 1'
        );
        return $v !== false ? (float)$v : 60.0;
    }

    public static function isPassing(float $score): bool
    {
        return $score >= self::passingScore();
    }

    /**
     * Determina si los exámenes están disponibles según la configuración de exam_schedule.
     */
    public static function isAvailable(): array
    {
        $schedule = Database::fetchColumn(
            'SELECT value FROM rsgrup_settings WHERE `key`="exam_schedule" LIMIT 1'
        ) ?: 'always';

        if ($schedule === 'always') {
            return ['available' => true, 'reason' => '', 'next' => null];
        }

        $now = new DateTimeImmutable('now', new DateTimeZone('Europe/Madrid'));
        $today = (int) $now->format('N'); // 1=Mon, 7=Sun

        if ($schedule === 'last_saturday') {
            if ($today === 6) { // Sábado
                return ['available' => true, 'reason' => '', 'next' => null];
            }
            $daysUntilSat = (6 - $today + 7) % 7 ?: 7;
            $next = $now->modify("+{$daysUntilSat} days")->format('d/m/Y');
            return ['available' => false, 'reason' => 'Los exámenes sólo se pueden realizar los sábados.', 'next' => $next];
        }

        if ($schedule === 'custom_days') {
            $rawDays = Database::fetchColumn(
                'SELECT value FROM rsgrup_settings WHERE `key`="exam_custom_days" LIMIT 1'
            ) ?: '';
            $allowed = array_map('intval', array_filter(explode(',', $rawDays), 'is_numeric'));
            $todayDow = $today % 7; // PHP N: 1-7, queremos 0=Dom..6=Sáb
            $todayDow = (int)$now->format('w');
            if (in_array($todayDow, $allowed, true)) {
                return ['available' => true, 'reason' => '', 'next' => null];
            }
            // Calcular próxima fecha
            $next = null;
            for ($i = 1; $i <= 7; $i++) {
                $candidate = (int)$now->modify("+{$i} days")->format('w');
                if (in_array($candidate, $allowed, true)) {
                    $next = $now->modify("+{$i} days")->format('d/m/Y');
                    break;
                }
            }
            return ['available' => false, 'reason' => 'Los exámenes no están disponibles hoy.', 'next' => $next];
        }

        return ['available' => true, 'reason' => '', 'next' => null];
    }
}
