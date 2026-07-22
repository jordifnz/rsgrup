<?php
declare(strict_types=1);

class EnrollmentController
{
    // ── Vista de una entrega ──────────────────────────────────────────────────────
    public function showDelivery(array $params = []): void
    {
        requireLogin();
        $slug     = $params['slug'] ?? '';
        $delivery = DeliveryModel::findBySlug($slug);

        if (!$delivery) {
            http_response_code(404);
            include BASE_PATH . '/public/404.php';
            return;
        }

        $userId     = $_SESSION['user_id'];
        $enrollment = DeliveryModel::getEnrollment($userId, (int)$delivery['id']);
        $isEnrolled = ($enrollment && $enrollment['status'] === 'active');

        // Calcular si puede inscribirse (para el CTA)
        $canEnroll       = false;
        $canEnrollReason = '';
        if (!$isEnrolled) {
            $check           = DeliveryModel::canEnroll($userId, (int)$delivery['id']);
            $canEnroll       = $check['ok'];
            $canEnrollReason = $check['reason'];
        }

        // Topics con sus exámenes e intentos del alumno
        $topics        = [];
        $topicAttempts = [];   // [topic_id => last_attempt_row]
        $examAvailable = ['available' => true, 'reason' => '', 'next' => null];

        if ($isEnrolled) {
            $topics        = TopicModel::findByDelivery((int)$delivery['id']);
            $topicAttempts = TopicModel::attemptsForDelivery($userId, (int)$delivery['id']);
            $examAvailable = ExamModel::isAvailable();

            // Enriquecer cada topic con datos de examen y estado
            foreach ($topics as &$topic) {
                $tid = (int)$topic['id'];
                $topic['_attempt']      = $topicAttempts[$tid] ?? null;
                $topic['_attempts_all'] = [];
                $topic['_attempt_count'] = 0;
                $topic['_can_retry']    = false;
                $topic['_exam_obj']     = null;

                if ($topic['exam_id']) {
                    $topic['_exam_obj']      = ExamModel::findWithQuestions((int)$topic['exam_id']);
                    $allAttempts             = ExamModel::getAllAttempts($userId, (int)$topic['exam_id']);
                    $topic['_attempts_all']  = $allAttempts;
                    $topic['_attempt_count'] = count($allAttempts);
                    // Sobreescribir con el último intento real
                    $topic['_attempt'] = $allAttempts ? end($allAttempts) : null;

                    $lastAttempt = $topic['_attempt'];
                    if ($lastAttempt && !ExamModel::isPassing((float)$lastAttempt['score']) && $topic['_attempt_count'] < 2) {
                        $topic['_can_retry'] = true;
                    }
                }
            }
            unset($topic);
        }

        $metaTitle = htmlspecialchars($delivery['title']);
        $robots    = 'noindex,nofollow';
        include BASE_PATH . '/templates/student/delivery.php';
    }

    // ── Formulario de confirmación de inscripción ─────────────────────────────────
    public function showEnroll(array $params = []): void
    {
        requireLogin();
        $deliveryId = Sanitize::int($params['id'] ?? 0);
        $delivery   = DeliveryModel::findById($deliveryId);

        if (!$delivery) {
            http_response_code(404);
            include BASE_PATH . '/public/404.php';
            return;
        }

        $userId     = $_SESSION['user_id'];
        $enrollment = DeliveryModel::getEnrollment($userId, $deliveryId);
        if ($enrollment && $enrollment['status'] === 'active') {
            header('Location: ' . BASE_URL . '/entrega/' . $delivery['slug']);
            exit;
        }

        $check = DeliveryModel::canEnroll($userId, $deliveryId);

        $metaTitle = 'Inscripción: ' . htmlspecialchars($delivery['title']);
        $robots    = 'noindex,nofollow';
        include BASE_PATH . '/templates/student/enroll_confirm.php';
    }

    // ── Iniciar inscripción / pago ────────────────────────────────────────────────
    public function initiate(array $params = []): void
    {
        requireLogin();
        Csrf::verify();

        $deliveryId = Sanitize::int($_POST['delivery_id'] ?? 0);
        $userId     = $_SESSION['user_id'];
        $delivery   = DeliveryModel::findById($deliveryId);

        if (!$delivery) {
            $_SESSION['flash_error'] = 'Entrega no encontrada.';
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $check = DeliveryModel::canEnroll($userId, $deliveryId);
        if (!$check['ok']) {
            $_SESSION['flash_error'] = $check['reason'];
            header('Location: ' . BASE_URL . '/inscribir/' . $deliveryId);
            exit;
        }

        if ($delivery['type'] === 'practica') {
            DeliveryModel::createEnrollment($userId, $deliveryId, null, 'active');
            ActivityLogger::log($userId, 'enrollment', 'Inscripción práctica: ' . $delivery['title']);
            NotificationService::send($userId, $delivery);
            $_SESSION['flash_success'] = 'Inscrito correctamente. El pago se realizará presencialmente.';
            header('Location: ' . BASE_URL . '/entrega/' . $delivery['slug']);
            exit;
        }

        $paypal  = new PayPalService();
        $orderId = $paypal->createOrder($delivery, $userId);

        if (!$orderId) {
            $_SESSION['flash_error'] = 'No se ha podido conectar con PayPal. Comprueba la configuración o inténtalo más tarde.';
            header('Location: ' . BASE_URL . '/inscribir/' . $deliveryId);
            exit;
        }

        DeliveryModel::createEnrollment($userId, $deliveryId, $orderId, 'pending');
        $approveUrl = $paypal->getApproveUrl($orderId);
        header('Location: ' . $approveUrl);
        exit;
    }

    // ── Callback PayPal: éxito ────────────────────────────────────────────────────
    public function paypalSuccess(array $params = []): void
    {
        requireLogin();
        $orderId = $_GET['token'] ?? '';
        if (!$orderId) { header('Location: ' . BASE_URL . '/dashboard'); exit; }

        $paypal   = new PayPalService();
        $captured = $paypal->captureOrder($orderId);

        if ($captured) {
            $enrollment = DeliveryModel::findEnrollmentByOrder($orderId);
            if ($enrollment) {
                DeliveryModel::activateEnrollment($orderId);
                $userId   = $_SESSION['user_id'];
                $delivery = DeliveryModel::findById($enrollment['delivery_id']);
                ActivityLogger::log($userId, 'enrollment', 'Inscripción confirmada: ' . $delivery['title']);
                NotificationService::send($userId, $delivery);
                $_SESSION['flash_success'] = '¡Inscripción completada! Ya puedes acceder al contenido.';
                header('Location: ' . BASE_URL . '/entrega/' . $delivery['slug']);
                exit;
            }
        }

        $_SESSION['flash_error'] = 'No se pudo confirmar el pago. Contacta con soporte.';
        header('Location: ' . BASE_URL . '/dashboard');
        exit;
    }

    // ── Callback PayPal: cancelación ──────────────────────────────────────────────
    public function paypalCancel(array $params = []): void
    {
        requireLogin();
        $orderId = $_GET['token'] ?? '';
        if ($orderId) DeliveryModel::cancelEnrollment($orderId);
        $_SESSION['flash_error'] = 'Pago cancelado.';
        header('Location: ' . BASE_URL . '/dashboard');
        exit;
    }

    // ── Descarga de PDF privado de un TOPIC ──────────────────────────────────────
    public function downloadTopicPdf(array $params = []): void
    {
        requireLogin();
        $topicId = Sanitize::int($params['id'] ?? 0);
        $userId  = $_SESSION['user_id'];

        $topic = TopicModel::findById($topicId);
        if (!$topic || !$topic['active']) {
            http_response_code(404);
            echo 'Tema no encontrado.';
            exit;
        }

        // Verificar que el alumno está inscrito en la entrega del topic
        $enrollment = DeliveryModel::getEnrollment($userId, (int)$topic['delivery_id']);
        if (!$enrollment || $enrollment['status'] !== 'active') {
            http_response_code(403);
            echo 'Acceso denegado.';
            exit;
        }

        $pdfFile = $topic['pdf_file'] ?? '';
        if (!$pdfFile) { echo 'PDF no disponible.'; exit; }

        $file = BASE_PATH . '/private_files/' . $pdfFile;
        if (!file_exists($file)) { echo 'Archivo no encontrado.'; exit; }

        ActivityLogger::log($userId, 'pdf_download', 'Descarga PDF tema: ' . $topic['title']);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Content-Length: ' . filesize($file));
        header('X-Accel-Buffering: no');
        readfile($file);
        exit;
    }

    // ── Descarga de PDF legacy (por enrollment_id) ────────────────────────────────
    public function downloadPdf(array $params = []): void
    {
        requireLogin();
        $enrollId   = Sanitize::int($params['id'] ?? 0);
        $userId     = $_SESSION['user_id'];
        $enrollment = DeliveryModel::getEnrollmentById($enrollId);

        if (!$enrollment || (int)$enrollment['user_id'] !== $userId || $enrollment['status'] !== 'active') {
            http_response_code(403);
            echo 'Acceso denegado.';
            exit;
        }

        $delivery = DeliveryModel::findById($enrollment['delivery_id']);
        $pdfFile  = $delivery['pdf_file'] ?? '';
        if (!$pdfFile) { echo 'PDF no disponible.'; exit; }

        $file = BASE_PATH . '/private_files/' . $pdfFile;
        if (!file_exists($file)) { echo 'Archivo no encontrado.'; exit; }

        ActivityLogger::log($userId, 'pdf_download', 'Descarga PDF: ' . $delivery['title']);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Content-Length: ' . filesize($file));
        header('X-Accel-Buffering: no');
        readfile($file);
        exit;
    }

    // ── Descarga de adjunto adicional de un topic ─────────────────────────────────
    public function downloadAttachment(array $params = []): void
    {
        requireLogin();
        $attachId = Sanitize::int($params['id'] ?? 0);
        $userId   = $_SESSION['user_id'];

        // Cargar el adjunto
        $att = Database::fetch(
            'SELECT ta.*, t.delivery_id, t.active AS topic_active, t.title AS topic_title
             FROM rsgrup_topic_attachments ta
             JOIN rsgrup_topics t ON t.id = ta.topic_id
             WHERE ta.id = ?',
            [$attachId]
        );

        if (!$att || !$att['topic_active']) {
            http_response_code(404);
            echo 'Adjunto no encontrado.';
            exit;
        }

        // Verificar que el alumno está inscrito en la entrega del topic
        $enrollment = DeliveryModel::getEnrollment($userId, (int)$att['delivery_id']);
        if (!$enrollment || $enrollment['status'] !== 'active') {
            http_response_code(403);
            echo 'Acceso denegado.';
            exit;
        }

        $file = BASE_PATH . '/private_files/attachments/' . $att['filename'];
        if (!file_exists($file)) {
            http_response_code(404);
            echo 'Archivo no encontrado.';
            exit;
        }

        ActivityLogger::log($userId, 'attachment_download',
            'Descarga adjunto: ' . ($att['description'] ?: $att['original_name']) . ' (tema: ' . $att['topic_title'] . ')');

        $mime = mime_content_type($file) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $att['original_name'] . '"');
        header('Content-Length: ' . filesize($file));
        header('X-Accel-Buffering: no');
        readfile($file);
        exit;
    }

    // ── Descarga del título/certificado ──────────────────────────────────────────
    public function downloadCertificate(array $params = []): void
    {
        requireLogin();
        $userId = $_SESSION['user_id'];

        if (!DeliveryModel::hasCompletedAll($userId)) {
            http_response_code(403);
            echo 'No has completado todas las entregas y exámenes.';
            exit;
        }

        $user = UserModel::findById($userId);
        $cert = new CertificateService();
        $cert->generate($user);
        exit;
    }

    // ── Envío de examen ───────────────────────────────────────────────────────────
    public function submitExam(array $params = []): void
    {
        requireLogin();
        Csrf::verify();

        $examId  = Sanitize::int($_POST['exam_id'] ?? 0);
        $userId  = $_SESSION['user_id'];
        $answers = $_POST['answers'] ?? [];

        $exam = ExamModel::findById($examId);
        if (!$exam) { http_response_code(404); exit; }

        // Buscar el topic que tiene vinculado este examen
        $topic = TopicModel::findByExamId($examId);
        if (!$topic) { http_response_code(404); exit; }

        // Buscar la entrega del topic
        $delivery = DeliveryModel::findById((int)$topic['delivery_id']);
        if (!$delivery) { http_response_code(404); exit; }

        $enrollment = DeliveryModel::getEnrollment($userId, (int)$delivery['id']);
        if (!$enrollment || $enrollment['status'] !== 'active') {
            http_response_code(403); exit;
        }

        // Verificar ventana de disponibilidad
        $avail = ExamModel::isAvailable();
        if (!$avail['available']) {
            $_SESSION['flash_error'] = $avail['reason']
                . ($avail['next'] ? ' Próxima fecha: ' . $avail['next'] . '.' : '');
            header('Location: ' . BASE_URL . '/entrega/' . $delivery['slug']);
            exit;
        }

        // Verificar intentos
        $lastAttempt  = ExamModel::getLastAttempt($userId, $examId);
        $attemptCount = ExamModel::getAttemptCount($userId, $examId);

        if ($lastAttempt) {
            if (ExamModel::isPassing((float)$lastAttempt['score'])) {
                $_SESSION['flash_error'] = 'Ya aprobaste este examen con un ' . $lastAttempt['score'] . '%. No es necesario repetirlo.';
                header('Location: ' . BASE_URL . '/entrega/' . $delivery['slug']);
                exit;
            }
            if ($attemptCount >= 2) {
                $_SESSION['flash_error'] = 'Ya has agotado los 2 intentos permitidos para este examen.';
                header('Location: ' . BASE_URL . '/entrega/' . $delivery['slug']);
                exit;
            }
        }

        $score = ExamModel::evaluate($examId, $answers);
        ExamModel::saveAttempt($userId, $examId, (int)$enrollment['id'], $answers, $score);
        ActivityLogger::log($userId, 'exam_submitted',
            "Examen '{$exam['title']}' - Intento #" . ($attemptCount + 1) . " - Nota: {$score}");

        $passing   = ExamModel::isPassing($score);
        $threshold = ExamModel::passingScore();

        if ($passing) {
            $_SESSION['flash_success'] = "¡Enhorabuena! Has aprobado el examen con un {$score}%.";
        } else {
            $newCount = $attemptCount + 1;
            if ($newCount < 2) {
                $_SESSION['flash_error'] = "Has obtenido un {$score}% (mínimo para aprobar: {$threshold}%). Tienes una segunda oportunidad para realizarlo.";
            } else {
                $_SESSION['flash_error'] = "Has obtenido un {$score}% (mínimo para aprobar: {$threshold}%). Has agotado los 2 intentos permitidos.";
            }
        }

        header('Location: ' . BASE_URL . '/entrega/' . $delivery['slug']);
        exit;
    }
}
