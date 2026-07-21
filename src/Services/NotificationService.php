<?php
declare(strict_types=1);

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

    private static function buildVars(array $user, array $delivery): array
    {
        return [
            '{{nombre}}'    => $user['name']      ?? '',
            '{{apellidos}}' => $user['surnames']  ?? '',
            '{{email}}'     => $user['email']     ?? '',
            '{{telefono}}'  => $user['phone']     ?? '',
            '{{entrega}}'   => $delivery['title'] ?? '',
            '{{tipo}}'      => $delivery['type']  ?? '',
            '{{precio}}'    => number_format((float)($delivery['price'] ?? 0), 2, ',', '.') . ' €',
            '{{fecha}}'     => date('d/m/Y H:i'),
            '{{sitio}}'     => defined('BASE_URL') ? BASE_URL : '',
        ];
    }

    private static function sendEmail(array $user, array $delivery, array $vars): void
    {
        try {
            // Plantilla desde settings (columnas `key`/`value`)
            $tplRow  = Database::fetch(
                "SELECT `value` FROM rsgrup_settings WHERE `key` = 'email_template'"
            );
            $subject = 'Inscripción confirmada: ' . ($delivery['title'] ?? '');
            $body    = $tplRow ? $tplRow['value'] : self::defaultEmailTemplate();
            $body    = strtr($body, $vars);

            $toName = trim(($user['name'] ?? '') . ' ' . ($user['surnames'] ?? ''));
            $mail   = new MailService();
            $mail->send($user['email'], $toName, $subject, $body);
        } catch (\Throwable $e) {
            error_log('[NotificationService::sendEmail] ' . $e->getMessage());
        }
    }

    private static function sendWhatsApp(array $user, array $delivery, array $vars): void
    {
        try {
            $tplRow = Database::fetch(
                "SELECT `value` FROM rsgrup_settings WHERE `key` = 'whatsapp_template'"
            );
            $body = $tplRow ? $tplRow['value'] : self::defaultWhatsAppTemplate();
            $body = strtr($body, $vars);

            $phone = $user['phone'] ?? '';
            if ($phone) {
                $wa = new WhatsAppService();
                $wa->send($phone, $body);
            }
        } catch (\Throwable $e) {
            error_log('[NotificationService::sendWhatsApp] ' . $e->getMessage());
        }
    }

    private static function defaultEmailTemplate(): string
    {
        return '<p>Hola {{nombre}} {{apellidos}},</p>'
             . '<p>Tu inscripción a <strong>{{entrega}}</strong> ha sido confirmada el {{fecha}}.</p>'
             . '<p>Accede en <a href="{{sitio}}">{{sitio}}</a>.</p>'
             . '<p>Gracias,<br>El equipo de RSGrup</p>';
    }

    private static function defaultWhatsAppTemplate(): string
    {
        return "Hola {{nombre}}, tu inscripción a *{{entrega}}* ha sido confirmada el {{fecha}}. Accede en {{sitio}}";
    }
}
