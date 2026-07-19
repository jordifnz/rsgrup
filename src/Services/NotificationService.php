<?php
declare(strict_types=1);

/**
 * NotificationService
 * Envía notificaciones de inscripción por e-mail y/o WhatsApp
 * según la configuración de cada entrega.
 */
class NotificationService
{
    public static function send(int $userId, array $delivery): void
    {
        if (empty($delivery['notify_email']) && empty($delivery['notify_whatsapp'])) {
            return;
        }

        $user = UserModel::findById($userId);
        if (!$user) return;

        $vars = self::buildVars($user, $delivery);

        if (!empty($delivery['notify_email'])) {
            self::sendEmail($user, $delivery, $vars);
        }

        if (!empty($delivery['notify_whatsapp'])) {
            self::sendWhatsApp($user, $delivery, $vars);
        }
    }

    // -------------------------------------------------------------------------
    private static function buildVars(array $user, array $delivery): array
    {
        return [
            '{{nombre}}'        => $user['first_name'] ?? '',
            '{{apellidos}}'     => $user['last_name']  ?? '',
            '{{email}}'         => $user['email']      ?? '',
            '{{telefono}}'      => $user['phone']      ?? '',
            '{{entrega}}'       => $delivery['title']  ?? '',
            '{{tipo}}'          => $delivery['type']   ?? '',
            '{{precio}}'        => number_format((float)($delivery['price'] ?? 0), 2, ',', '.') . ' €',
            '{{fecha}}'         => date('d/m/Y H:i'),
            '{{sitio}}'         => defined('BASE_URL') ? BASE_URL : '',
        ];
    }

    private static function sendEmail(array $user, array $delivery, array $vars): void
    {
        try {
            $db = Database::getInstance();

            // Plantilla de e-mail desde settings
            $tplRow = $db->fetchOne(
                "SELECT setting_value FROM rsgrup_settings WHERE setting_key = 'email_template_enrollment'"
            );
            $subject = 'Inscripción confirmada: ' . $delivery['title'];
            $body    = $tplRow ? $tplRow['setting_value'] : self::defaultEmailTemplate();
            $body    = strtr($body, $vars);

            $mail = new MailService();
            $mail->send($user['email'], $subject, $body);
        } catch (\Throwable $e) {
            // Notificación no crítica: loguear pero no romper el flujo
            error_log('[NotificationService::sendEmail] ' . $e->getMessage());
        }
    }

    private static function sendWhatsApp(array $user, array $delivery, array $vars): void
    {
        try {
            $db = Database::getInstance();

            $tplRow = $db->fetchOne(
                "SELECT setting_value FROM rsgrup_settings WHERE setting_key = 'whatsapp_template_enrollment'"
            );
            $body = $tplRow ? $tplRow['setting_value'] : self::defaultWhatsAppTemplate();
            $body = strtr($body, $vars);

            $phone = $user['phone'] ?? '';
            if ($phone) {
                $wa = new WhatsAppService();
                $wa->sendText($phone, $body);
            }
        } catch (\Throwable $e) {
            error_log('[NotificationService::sendWhatsApp] ' . $e->getMessage());
        }
    }

    private static function defaultEmailTemplate(): string
    {
        return '<p>Hola {{nombre}} {{apellidos}},</p>'
             . '<p>Tu inscripción a <strong>{{entrega}}</strong> ha sido confirmada el {{fecha}}.</p>'
             . '<p>Accede a tu cuenta en <a href="{{sitio}}">{{sitio}}</a> para continuar.</p>'
             . '<p>Gracias,<br>El equipo de RSGrup</p>';
    }

    private static function defaultWhatsAppTemplate(): string
    {
        return "Hola {{nombre}}, tu inscripción a *{{entrega}}* ha sido confirmada el {{fecha}}. Accede en {{sitio}}";
    }
}
