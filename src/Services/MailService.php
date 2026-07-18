<?php
declare(strict_types=1);

class MailService
{
    private string $host;
    private int $port;
    private string $user;
    private string $pass;
    private string $fromEmail;
    private string $fromName;

    public function __construct()
    {
        $this->host      = $this->getSetting('smtp_host', 'smtp.gmail.com');
        $this->port      = (int)$this->getSetting('smtp_port', '587');
        $this->user      = $this->getSetting('smtp_user', '');
        $this->pass      = $this->getSetting('smtp_password', '');
        $this->fromEmail = $this->getSetting('smtp_from_email', $this->user);
        $this->fromName  = $this->getSetting('smtp_from_name', 'RSGrup');
    }

    private function getSetting(string $key, string $default = ''): string
    {
        $row = Database::fetch('SELECT value FROM rsgrup_settings WHERE `key`=?', [$key]);
        return $row ? $row['value'] : $default;
    }

    public function send(string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        $boundary = md5(uniqid());
        $headers  = implode("\r\n", [
            "MIME-Version: 1.0",
            "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
            "From: {$this->fromName} <{$this->fromEmail}>",
            "To: {$toName} <{$toEmail}>",
            "X-Mailer: RSGrup/1.0",
        ]);

        $textBody = strip_tags($htmlBody);
        $message  = "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n{$textBody}\r\n";
        $message .= "--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n{$htmlBody}\r\n";
        $message .= "--{$boundary}--";

        // Use PHPMailer if available, otherwise native mail()
        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            return $this->sendViaPHPMailer($toEmail, $toName, $subject, $htmlBody);
        }

        // Native mail with SMTP not supported without extension; log and return false
        error_log("[MailService] PHPMailer not found. Install via: composer require phpmailer/phpmailer");
        return false;
    }

    private function sendViaPHPMailer(string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $this->host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->user;
            $mail->Password   = $this->pass;
            $mail->SMTPSecure = ($this->port === 465) ? 'ssl' : 'tls';
            $mail->Port       = $this->port;
            $mail->CharSet    = 'UTF-8';
            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = strip_tags($htmlBody);
            $mail->send();
            return true;
        } catch (\Throwable $e) {
            error_log('[MailService] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Parse and render a template from settings with variables.
     */
    public static function renderTemplate(string $templateKey, array $vars): array
    {
        $subject  = Database::fetch('SELECT value FROM rsgrup_settings WHERE `key`="email_template_subject"');
        $body     = Database::fetch('SELECT value FROM rsgrup_settings WHERE `key`="{$templateKey}"') 
                    ?? Database::fetch('SELECT value FROM rsgrup_settings WHERE `key`="email_template_body"');

        $subjectText = $subject ? $subject['value'] : 'Inscripción confirmada - RSGrup';
        $bodyText    = $body    ? $body['value']    : self::defaultEmailTemplate();

        foreach ($vars as $k => $v) {
            $subjectText = str_replace('{{'.$k.'}}', (string)$v, $subjectText);
            $bodyText    = str_replace('{{'.$k.'}}', (string)$v, $bodyText);
        }
        return ['subject' => $subjectText, 'body' => $bodyText];
    }

    private static function defaultEmailTemplate(): string
    {
        return '<p>Hola {{nombre}},</p><p>Tu inscripción a <strong>{{entrega}}</strong> ha sido confirmada.</p><p>Puedes acceder a tu contenido en: <a href="{{url}}">{{url}}</a></p><p>Saludos,<br>El equipo de RSGrup</p>';
    }
}
