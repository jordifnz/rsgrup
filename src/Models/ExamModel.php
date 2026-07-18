<?php
class ExamModel {
    public static function findById(int $id): ?array {
        return Database::fetch("SELECT * FROM rsgrup_exams WHERE id = ?", [$id]);
    }

    public static function getWithQuestions(int $examId): ?array {
        $exam = self::findById($examId);
        if (!$exam) return null;
        $questions = Database::fetchAll(
            "SELECT * FROM rsgrup_exam_questions WHERE exam_id = ? ORDER BY sort_order ASC",
            [$examId]
        );
        foreach ($questions as &$q) {
            $q['answers'] = Database::fetchAll(
                "SELECT * FROM rsgrup_exam_answers WHERE question_id = ? ORDER BY id ASC",
                [$q['id']]
            );
        }
        $exam['questions'] = $questions;
        return $exam;
    }

    /**
     * Corrige el intento y guarda la nota.
     * $responses = ['question_id' => [answer_id, ...], ...]
     */
    public static function grade(int $userId, int $deliveryId, int $examId, array $responses): float {
        $questions = Database::fetchAll(
            "SELECT * FROM rsgrup_exam_questions WHERE exam_id = ?",
            [$examId]
        );
        $total   = count($questions);
        $correct = 0;

        $attemptId = Database::insert(
            "INSERT INTO rsgrup_exam_attempts (user_id, delivery_id, exam_id, score, created_at) VALUES (?,?,?,0,NOW())",
            [$userId, $deliveryId, $examId]
        );

        foreach ($questions as $q) {
            $correctAnswers = Database::fetchAll(
                "SELECT id FROM rsgrup_exam_answers WHERE question_id = ? AND is_correct = 1",
                [$q['id']]
            );
            $correctIds = array_column($correctAnswers, 'id');
            $given      = array_map('intval', (array)($responses[$q['id']] ?? []));

            sort($correctIds);
            sort($given);
            $isCorrect = ($correctIds === $given) ? 1 : 0;
            if ($isCorrect) $correct++;

            Database::insert(
                "INSERT INTO rsgrup_exam_attempt_answers (attempt_id, question_id, is_correct, created_at) VALUES (?,?,?,NOW())",
                [$attemptId, $q['id'], $isCorrect]
            );
            foreach ($given as $answerId) {
                Database::insert(
                    "INSERT INTO rsgrup_exam_attempt_answers_selected (attempt_id, question_id, answer_id) VALUES (?,?,?)",
                    [$attemptId, $q['id'], $answerId]
                );
            }
        }

        $score = $total > 0 ? round(($correct / $total) * 10, 2) : 0.0;
        Database::query("UPDATE rsgrup_exam_attempts SET score = ? WHERE id = ?", [$score, $attemptId]);
        return $score;
    }

    public static function getAll(): array {
        return Database::fetchAll(
            "SELECT ex.*, d.title as delivery_title FROM rsgrup_exams ex LEFT JOIN rsgrup_deliveries d ON d.exam_id=ex.id ORDER BY ex.id DESC"
        );
    }
}
