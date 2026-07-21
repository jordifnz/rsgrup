<?php
declare(strict_types=1);

class EnrollmentController
{
    // ── Vista de una entrega ────────────────────────────────────────────────
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
        $canEnroll  = false;
        $canEnrollReason = '';
        if (!$isEnrolled) {
            $check = DeliveryModel::canEnroll($userId, (int)$delivery['id']);
            $canEnroll       = $check['ok'];
            $canEnrollReason = $check['reason'];
        }

        // Examen y último intento (solo si inscrito)
        $exam    = null;
        $attempt = null;
        if ($isEnrolled && $delivery['exam_id']) {
            $exam    = ExamModel::findWithQuestions((int)$delivery['exam_id']);
            $attempt = ExamModel::getLastAttempt($userId, (int)$delivery['exam_id']);
        }

        $metaTitle = htmlspecialchars($delivery['title']);
        $robots    = 'noindex,nofollow';
        include BASE_PATH . '/templates/student/delivery.php';
    }

    // ── Formulario de confirmación de inscripción ───────────────────────────
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

        $userId = $_SESSION['user_id'];

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

    // ── Iniciar inscripción / pago ──────────────────────────────────────────
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

    // ── Callback PayPal: éxito ──────────────────────────────────────────────
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

    // ── Callback PayPal: cancelación ────────────────────────────────────────
    public function paypalCancel(array $params = []): void
    {
        requireLogin();
        $orderId = $_GET['token'] ?? '';
        if ($orderId) DeliveryModel::cancelEnrollment($orderId);
        $_SESSION['flash_error'] = 'Pago cancelado.';
        header('Location: ' . BASE_URL . '/dashboard');
        exit;
    }

    // ── Descarga de PDF privado ─────────────────────────────────────────────
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

    // ── Descarga del título/certificado ────────────────────────────────────
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

    // ── Envío de examen ─────────────────────────────────────────────────────
    public function submitExam(array $params = []): void
    {
        requireLogin();
        Csrf::verify();

        $examId  = Sanitize::int($_POST['exam_id'] ?? 0);
        $userId  = $_SESSION['user_id'];
        $answers = $_POST['answers'] ?? [];

        $exam = ExamModel::findById($examId);
        if (!$exam) { http_response_code(404); exit; }

        // La relación es delivery.exam_id → exams.id, no al revés.
        $delivery = DeliveryModel::findByExamId($examId);
        if (!$delivery) { http_response_code(404); exit; }

        $enrollment = DeliveryModel::getEnrollment($userId, (int)$delivery['id']);
        if (!$enrollment || $enrollment['status'] !== 'active') {
            http_response_code(403); exit;
        }

        if (ExamModel::getLastAttempt($userId, $examId)) {
            $_SESSION['flash_error'] = 'Ya has realizado este examen.';
            header('Location: ' . BASE_URL . '/entrega/' . $delivery['slug']);
            exit;
        }

        $score = ExamModel::evaluate($examId, $answers);
        // Pasamos enrollment_id para satisfacer la FK fk_attempts_enrollment
        ExamModel::saveAttempt($userId, $examId, (int)$enrollment['id'], $answers, $score);
        ActivityLogger::log($userId, 'exam_submitted', "Examen '{$exam['title']}' - Nota: {$score}");

        $_SESSION['flash_success'] = "Examen enviado. Tu nota: {$score}%";
        header('Location: ' . BASE_URL . '/entrega/' . $delivery['slug']);
        exit;
    }
}
