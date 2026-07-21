<?php
declare(strict_types=1);

class EnrollmentController
{
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

        // Si ya está activamente inscrito, redirigir directamente
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

        // Práctica: pago presencial, inscribir directamente
        if ($delivery['type'] === 'practica') {
            DeliveryModel::createEnrollment($userId, $deliveryId, null, 'active');
            ActivityLogger::log($userId, 'enrollment', 'Inscripción práctica: ' . $delivery['title']);
            NotificationService::send($userId, $delivery);
            $_SESSION['flash_success'] = 'Inscrito correctamente. El pago se realizará presencialmente.';
            header('Location: ' . BASE_URL . '/entrega/' . $delivery['slug']);
            exit;
        }

        // Matrícula / Entrega: PayPal
        $paypal  = new PayPalService();
        $orderId = $paypal->createOrder($delivery, $userId);

        // Si PayPal falla, volver al formulario con error claro
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

    public function paypalCancel(array $params = []): void
    {
        requireLogin();
        $orderId = $_GET['token'] ?? '';
        if ($orderId) DeliveryModel::cancelEnrollment($orderId);
        $_SESSION['flash_error'] = 'Pago cancelado.';
        header('Location: ' . BASE_URL . '/dashboard');
        exit;
    }

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

    public function submitExam(array $params = []): void
    {
        requireLogin();
        Csrf::verify();

        $examId  = Sanitize::int($_POST['exam_id'] ?? 0);
        $userId  = $_SESSION['user_id'];
        $answers = $_POST['answers'] ?? [];

        $exam = ExamModel::findById($examId);
        if (!$exam) { http_response_code(404); exit; }

        $enrollment = DeliveryModel::getEnrollment($userId, $exam['delivery_id']);
        if (!$enrollment || $enrollment['status'] !== 'active') {
            http_response_code(403); exit;
        }

        if (ExamModel::getLastAttempt($userId, $examId)) {
            $_SESSION['flash_error'] = 'Ya has realizado este examen.';
            $delivery = DeliveryModel::findById($exam['delivery_id']);
            header('Location: ' . BASE_URL . '/entrega/' . $delivery['slug']);
            exit;
        }

        $score = ExamModel::evaluate($examId, $answers);
        ExamModel::saveAttempt($userId, $examId, $answers, $score);
        ActivityLogger::log($userId, 'exam_submitted', "Examen '{$exam['title']}' - Nota: {$score}");

        $delivery = DeliveryModel::findById($exam['delivery_id']);
        $_SESSION['flash_success'] = "Examen enviado. Tu nota: {$score}%";
        header('Location: ' . BASE_URL . '/entrega/' . $delivery['slug']);
        exit;
    }
}
