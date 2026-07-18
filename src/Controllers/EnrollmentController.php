<?php
declare(strict_types=1);

class EnrollmentController
{
    public function showDelivery(array $params = []): void
    {
        requireLogin();
        $slug     = $params['slug'] ?? '';
        $delivery = DeliveryModel::findBySlug($slug);
        if (!$delivery) { http_response_code(404); include BASE_PATH.'/public/404.php'; return; }

        $userId     = $_SESSION['user_id'];
        $enrollment = DeliveryModel::getEnrollment($userId, $delivery['id']);
        $exam       = $delivery['exam_id'] ? ExamModel::findById($delivery['exam_id']) : null;
        $attempt    = $exam ? ExamModel::getLastAttempt($userId, $exam['id']) : null;
        $canDownload = $enrollment && $enrollment['status'] === 'active';
        $canTakeExam = $canDownload && $exam && !$attempt;
        $metaTitle  = htmlspecialchars($delivery['title']);
        $robots     = 'noindex,nofollow';
        include BASE_PATH . '/templates/student/delivery.php';
    }

    public function initiate(array $params = []): void
    {
        requireLogin();
        Csrf::verify();

        $deliveryId = Sanitize::int($_POST['delivery_id'] ?? 0);
        $userId     = $_SESSION['user_id'];
        $delivery   = DeliveryModel::findById($deliveryId);

        if (!$delivery) { $_SESSION['flash_error'] = 'Entrega no encontrada.'; header('Location: '.BASE_URL.'/dashboard'); exit; }

        // Check enrollment prerequisites
        $check = DeliveryModel::canEnroll($userId, $deliveryId);
        if (!$check['ok']) {
            $_SESSION['flash_error'] = $check['reason'];
            header('Location: '.BASE_URL.'/dashboard');
            exit;
        }

        // Práctica: no PayPal, just enroll
        if ($delivery['type'] === 'practica') {
            DeliveryModel::createEnrollment($userId, $deliveryId, null, 'active');
            ActivityLogger::log($userId, 'enrollment', 'Inscripción práctica: '.$delivery['title']);
            NotificationService::send($userId, $delivery);
            $_SESSION['flash_success'] = 'Inscrito correctamente a la práctica.';
            header('Location: '.BASE_URL.'/entrega/'.$delivery['slug']);
            exit;
        }

        // PayPal order
        $paypal  = new PayPalService();
        $orderId = $paypal->createOrder($delivery, $userId);
        DeliveryModel::createEnrollment($userId, $deliveryId, $orderId, 'pending');
        $approveUrl = $paypal->getApproveUrl($orderId);
        header('Location: ' . $approveUrl);
        exit;
    }

    public function paypalSuccess(array $params = []): void
    {
        requireLogin();
        $orderId = $_GET['token'] ?? '';
        if (!$orderId) { header('Location: '.BASE_URL.'/dashboard'); exit; }

        $paypal   = new PayPalService();
        $captured = $paypal->captureOrder($orderId);

        if ($captured) {
            $enrollment = DeliveryModel::findEnrollmentByOrder($orderId);
            if ($enrollment) {
                DeliveryModel::activateEnrollment($orderId);
                $userId   = $_SESSION['user_id'];
                $delivery = DeliveryModel::findById($enrollment['delivery_id']);
                ActivityLogger::log($userId, 'enrollment', 'Inscripción confirmada: '.$delivery['title']);
                NotificationService::send($userId, $delivery);
                $_SESSION['flash_success'] = '\u00a1Inscripción completada! Ya puedes acceder al contenido.';
                header('Location: '.BASE_URL.'/entrega/'.$delivery['slug']);
                exit;
            }
        }

        $_SESSION['flash_error'] = 'No se pudo confirmar el pago. Contacta con soporte.';
        header('Location: '.BASE_URL.'/dashboard');
        exit;
    }

    public function paypalCancel(array $params = []): void
    {
        requireLogin();
        $orderId = $_GET['token'] ?? '';
        if ($orderId) DeliveryModel::cancelEnrollment($orderId);
        $_SESSION['flash_error'] = 'Pago cancelado.';
        header('Location: '.BASE_URL.'/dashboard');
        exit;
    }

    public function downloadPdf(array $params = []): void
    {
        requireLogin();
        $enrollId = Sanitize::int($params['id'] ?? 0);
        $userId   = $_SESSION['user_id'];
        $enrollment = DeliveryModel::getEnrollmentById($enrollId);

        if (!$enrollment || $enrollment['user_id'] != $userId || $enrollment['status'] !== 'active') {
            http_response_code(403); echo 'Acceso denegado.'; exit;
        }

        $delivery = DeliveryModel::findById($enrollment['delivery_id']);
        if (!$delivery['pdf_path']) { echo 'PDF no disponible.'; exit; }

        $file = BASE_PATH . '/private_files/' . $delivery['pdf_path'];
        if (!file_exists($file)) { echo 'Archivo no encontrado.'; exit; }

        ActivityLogger::log($userId, 'pdf_download', 'Descarga PDF: '.$delivery['title']);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="'.basename($file).'"');
        header('Content-Length: '.filesize($file));
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

        // Verify enrolled
        $exam     = ExamModel::findById($examId);
        $enrollment = DeliveryModel::getEnrollment($userId, $exam['delivery_id']);
        if (!$enrollment || $enrollment['status'] !== 'active') {
            http_response_code(403); exit;
        }

        // Prevent re-take
        if (ExamModel::getLastAttempt($userId, $examId)) {
            $_SESSION['flash_error'] = 'Ya has realizado este exámen.';
            header('Location: '.BASE_URL.'/entrega/'.DeliveryModel::findById($exam['delivery_id'])['slug']);
            exit;
        }

        $score = ExamModel::evaluate($examId, $answers);
        ExamModel::saveAttempt($userId, $examId, $answers, $score);
        ActivityLogger::log($userId, 'exam_submitted', "Exámen '{$exam['title']}' - Nota: {$score}");

        $delivery = DeliveryModel::findById($exam['delivery_id']);
        $_SESSION['flash_success'] = "Exámen enviado. Tu nota: {$score}%";
        header('Location: '.BASE_URL.'/entrega/'.$delivery['slug']);
        exit;
    }
}
