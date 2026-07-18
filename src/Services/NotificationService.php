<?php
declare(strict_types=1);

class NotificationService
{
    public static function send(int $userId, array $delivery): void
    {
        $user = UserModel::findById($userId);
        if (!$user) return;

        $vars = [
            'nombre'   => $user['name'] . ' ' . $user['surnames'],
            'email'    => $user['email'],
            'entrega'  => $delivery['title'],
            'tipo'     => $delivery['type'],
            'precio'   => number_format((float)$delivery['price'], 2, ',', '.') . ' €',
            'url'      => BASE_URL . '/entrega/' . $delivery['slug'],
            'fecha'    => date('d/m/Y H:i'),
        ];

        // Email notification
        if ($delivery['notify_email']) {
            $tpl  = MailService::renderTemplate('email_template_body', $vars);
            $mail = new MailService();
            $mail->send($user['email'], $user['name'], $tpl['subject'], $tpl['body']);
        }

        // WhatsApp notification
        if ($delivery['notify_whatsapp'] && !empty($user['phone'])) {
            $message = WhatsAppService::renderTemplate($vars);
            $wa      = new WhatsAppService();
            $wa->send($user['phone'], $message);
        }
    }
}
