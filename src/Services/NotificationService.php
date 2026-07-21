<?php
declare(strict_types=1);

class NotificationService
{
    public static function send(int $userId, array $delivery): void
    {
        $notifyEmail    = !empty($delivery['notify_email']);
        // MySQL devuelve TINYINT como string; usamos cast int para comparar
        $notifyWhatsApp = (int)($delivery['notify_whatsapp'] ?? 0) === 1;

        if (!$notifyEmail && !$notifyWhatsApp) {
            return;
        }

        $user = UserModel::findById($userId);
        if (!$user) {
            error_log('[NotificationService] Usuario no encontrado: ' . $userId);
            return;
        }

        // Título del curso (JOIN por course_id si existe en la entrega)
        $courseTitle = '';
        if (!empty($delivery['course_id'])) {
            $course = Database::fetch(
                'SELECT title FROM rsgrup_courses WHERE id = ?',
                [(int)$delivery['course_id']]
            );
            $courseTitle = $course['title'] ?? '';
        }

        $vars = self::buildVars($user, $delivery, $courseTitle);

        if ($notifyEmail) {
            self::sendEmail($user, $delivery, $vars);
        }
        if ($notifyWhatsApp) {
            self::sendWhatsApp($user, $vars);
        }
    }

    // -----------------------------------------------------------------------

    private static function buildVars(array $user, array $delivery, string $courseTitle = ''): array
    {
        return [
            '{{nombre}}'       => $user['name']      ?? '',
            '{{apellidos}}'    => $user['surnames']  ?? '',
            '{{email}}'        => $user['email']     ?? '',
            '{{telefono}}'     => $user['phone']     ?? '',
            '{{entrega}}'      => $delivery['title'] ?? '',
            '{{tipo}}'         => $delivery['type']  ?? '',
            '{{precio}}'       => number_format((float)($delivery['price'] ?? 0), 2, ',', '.') . ' €',
            '{{fecha}}'        => date('d/m/Y H:i'),
            '{{sitio}}'        => defined('BASE_URL') ? BASE_URL : '',
            '{{curso_titulo}}' => $courseTitle,
        ];
    }

    private static function sendEmail(array $user, array $delivery, array $vars): void
    {
        try {
            $subjectRow = Database::fetch(
                "SELECT `value` FROM rsgrup_settings WHERE `key` = 'email_template_subject'"
            );
            $bodyRow = Database::fetch(
                "SELECT `value` FROM rsgrup_settings WHERE `key` = 'email_template_body'"
            );

            $subject = $subjectRow ? $subjectRow['value'] : 'Inscripción confirmada: {{entrega}}';
            $body    = $bodyRow    ? $bodyRow['value']    : self::defaultEmailTemplate();

            $subject = strtr($subject, $vars);
            $body    = strtr($body,    $vars);

            $toName = trim(($user['name'] ?? '') . ' ' . ($user['surnames'] ?? ''));
            $mail   = new MailService();
            $mail->send($user['email'], $toName, $subject, $body);

        } catch (\Throwable $e) {
            error_log('[NotificationService::sendEmail] ' . $e->getMessage());
        }
    }

    private static function sendWhatsApp(array $user, array $vars): void
    {
        try {
            $phone = trim($user['phone'] ?? '');
            if ($phone === '') {
                error_log('[NotificationService::sendWhatsApp] Usuario ID '
                    . ($user['id'] ?? '?') . ' no tiene teléfono en su perfil.');
                return;
            }

            $tplRow = Database::fetch(
                "SELECT `value` FROM rsgrup_settings WHERE `key` = 'whatsapp_template'"
            );
            $body = $tplRow ? $tplRow['value'] : self::defaultWhatsAppTemplate();
            $body = strtr($body, $vars);

            $wa = new WhatsAppService();
            $ok = $wa->send($phone, $body);

            if (!$ok) {
                error_log('[NotificationService::sendWhatsApp] WhatsAppService::send() devolvió false '
                    . 'para usuario ID ' . ($user['id'] ?? '?') . ' phone=' . $phone);
            }
        } catch (\Throwable $e) {
            error_log('[NotificationService::sendWhatsApp] ' . $e->getMessage());
        }
    }

    // -----------------------------------------------------------------------

    private static function defaultEmailTemplate(): string
    {
        return '<p>Hola {{nombre}} {{apellidos}},</p>'
             . '<p>Tu inscripción a <strong>{{entrega}}</strong> ({{curso_titulo}}) '
             . 'ha sido confirmada el {{fecha}}.</p>'
             . '<p>Accede en <a href="{{sitio}}">{{sitio}}</a>.</p>'
             . '<p>Gracias,<br>El equipo de RSGrup</p>';
    }

    private static function defaultWhatsAppTemplate(): string
    {
        return "Hola {{nombre}}, tu inscripción a *{{entrega}}* ({{curso_titulo}}) "
             . "ha sido confirmada el {{fecha}}. Accede en {{sitio}}";
    }
}
