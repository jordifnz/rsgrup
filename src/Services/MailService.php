<?php
declare(strict_types=1);

class MailService
{
    private string $host;
    private int    $port;
    private string $user;
    private string $pass;
    private string $fromEmail;
    private string $fromName;

    public function __construct()
    {
        $this->host      = $this->getSetting('smtp_host',       'smtp.gmail.com');
        $this->port      = (int)$this->getSetting('smtp_port',  '587');
        $this->user      = $this->getSetting('smtp_user',       '');
        $this->pass      = $this->getSetting('smtp_password',   '');
        $this->fromEmail = $this->getSetting('smtp_from_email', '') ?: $this->user;
        $this->fromName  = $this->getSetting('smtp_from_name',  'RSGrup');
    }

    /**
     * Lee un valor de rsgrup_settings.
     * La tabla usa columnas setting_key / setting_value.
     */
    private function getSetting(string $key, string $default = ''): string
    {
        $row = Database::fetch(
            'SELECT setting_value FROM rsgrup_settings WHERE setting_key = ?',
            [$key]
        );
        return $row ? (string)$row['setting_value'] : $default;
    }

    /**
     * Envía un e-mail HTML.
     *
     * @param string $toEmail   Dirección destino
     * @param string $toName    Nombre destino
     * @param string $subject   Asunto
     * @param string $htmlBody  Cuerpo HTML
     */
    public function send(string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            return $this->sendViaPHPMailer($toEmail, $toName, $subject, $htmlBody);
        }

        error_log('[MailService] PHPMailer no encontrado. Instálalo: composer require phpmailer/phpmailer');
        return false;
    }

    private function sendViaPHPMailer(
        string $toEmail, string $toName, string $subject, string $htmlBody
    ): bool {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $this->host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->user;
            $mail->Password   = $this->pass;
            $mail->SMTPSecure = ($this->port === 465)
                ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
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
     * Renderiza una plantilla almacenada en settings sustituyendo variables {{key}}.
     *
     * @param  string   $subjectKey  setting_key del asunto
     * @param  string   $bodyKey     setting_key del cuerpo HTML
     * @param  array    $vars        [ 'nombre' => '...', 'entrega' => '...', ... ]
     * @return array{subject:string, body:string}
     */
    public static function renderTemplate(string $subjectKey, string $bodyKey, array $vars): array
    {
        $subjectRow = Database::fetch(
            'SELECT setting_value FROM rsgrup_settings WHERE setting_key = ?', [$subjectKey]
        );
        $bodyRow = Database::fetch(
            'SELECT setting_value FROM rsgrup_settings WHERE setting_key = ?', [$bodyKey]
        );

        $subject = $subjectRow ? $subjectRow['setting_value'] : 'Inscripción confirmada - RSGrup';
        $body    = $bodyRow    ? $bodyRow['setting_value']    : self::defaultEmailTemplate();

        foreach ($vars as $k => $v) {
            $subject = str_replace('{{' . $k . '}}', (string)$v, $subject);
            $body    = str_replace('{{' . $k . '}}', (string)$v, $body);
        }
        return ['subject' => $subject, 'body' => $body];
    }

    private static function defaultEmailTemplate(): string
    {
        return '<p>Hola {{nombre}},</p>'
             . '<p>Tu inscripción a <strong>{{entrega}}</strong> ha sido confirmada el {{fecha}}.</p>'
             . '<p>Accede a tu cuenta en: <a href="{{sitio}}">{{sitio}}</a></p>'
             . '<p>Saludos,<br>El equipo de RSGrup</p>';
    }
}
