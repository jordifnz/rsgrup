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

    public static function getLastAttempt(int $userId, int $examId): ?array
    {
        return Database::fetch(
            'SELECT * FROM rsgrup_exam_attempts WHERE user_id=? AND exam_id=? ORDER BY created_at DESC LIMIT 1',
            [$userId, $examId]
        ) ?: null;
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
