<?php
declare(strict_types=1);

/**
 * MailService — cliente SMTP nativo con sockets PHP.
 * No requiere PHPMailer ni ninguna dependencia externa.
 * Soporta STARTTLS (puerto 587) y SMTPS implícito (puerto 465).
 */
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
        $this->pass      = $this->getSetting('smtp_pass',       '');
        $this->fromEmail = $this->getSetting('smtp_from_email', '') ?: $this->user;
        $this->fromName  = $this->getSetting('smtp_from_name',  'RSGrup');
    }

    private function getSetting(string $key, string $default = ''): string
    {
        $row = Database::fetch(
            'SELECT `value` FROM rsgrup_settings WHERE `key` = ?',
            [$key]
        );
        return $row ? (string)$row['value'] : $default;
    }

    // ── API pública ────────────────────────────────────────────────────────

    public function send(string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        if (!$this->host || !$this->user || !$this->pass) {
            error_log('[MailService] SMTP no configurado (smtp_host, smtp_user, smtp_pass).');
            return false;
        }

        // Si PHPMailer está disponible lo usamos; si no, SMTP nativo
        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            return $this->sendViaPHPMailer($toEmail, $toName, $subject, $htmlBody);
        }

        return $this->sendViaSocket($toEmail, $toName, $subject, $htmlBody);
    }

    // ── SMTP nativo con sockets ────────────────────────────────────────────

    private function sendViaSocket(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody
    ): bool {
        $useSSL     = ($this->port === 465);
        $host       = ($useSSL ? 'ssl://' : '') . $this->host;
        $timeout    = 15;

        $sock = @fsockopen($host, $this->port, $errno, $errstr, $timeout);
        if (!$sock) {
            error_log("[MailService] No se pudo conectar a {$this->host}:{$this->port} — {$errstr} ({$errno})");
            return false;
        }

        try {
            $this->expect($sock, 220, 'Saludo servidor');

            // EHLO
            $this->send_cmd($sock, 'EHLO ' . gethostname());
            $ehlo = $this->read($sock);
            if (!str_starts_with($ehlo, '250')) {
                throw new \RuntimeException('EHLO rechazado: ' . $ehlo);
            }

            // STARTTLS si no es SSL implícito
            if (!$useSSL) {
                $this->send_cmd($sock, 'STARTTLS');
                $this->expect($sock, 220, 'STARTTLS');
                if (!stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \RuntimeException('No se pudo negociar TLS.');
                }
                // EHLO de nuevo tras TLS
                $this->send_cmd($sock, 'EHLO ' . gethostname());
                $this->read($sock);
            }

            // AUTH LOGIN
            $this->send_cmd($sock, 'AUTH LOGIN');
            $this->expect($sock, 334, 'AUTH LOGIN');
            $this->send_cmd($sock, base64_encode($this->user));
            $this->expect($sock, 334, 'Usuario');
            $this->send_cmd($sock, base64_encode($this->pass));
            $this->expect($sock, 235, 'Contraseña');

            // Sobre
            $this->send_cmd($sock, 'MAIL FROM:<' . $this->fromEmail . '>');
            $this->expect($sock, 250, 'MAIL FROM');
            $this->send_cmd($sock, 'RCPT TO:<' . $toEmail . '>');
            $this->expect($sock, 250, 'RCPT TO');
            $this->send_cmd($sock, 'DATA');
            $this->expect($sock, 354, 'DATA');

            // Cabeceras + cuerpo
            $boundary  = '=_' . md5(uniqid('', true));
            $plainBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));
            $plainBody = html_entity_decode($plainBody, ENT_QUOTES, 'UTF-8');

            $fromEncoded = '=?UTF-8?B?' . base64_encode($this->fromName) . '?=';
            $toEncoded   = '=?UTF-8?B?' . base64_encode($toName)         . '?=';
            $subEncoded  = '=?UTF-8?B?' . base64_encode($subject)         . '?=';

            $headers  = "From: {$fromEncoded} <{$this->fromEmail}>\r\n";
            $headers .= "To: {$toEncoded} <{$toEmail}>\r\n";
            $headers .= "Subject: {$subEncoded}\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
            $headers .= "Date: " . date('r') . "\r\n";
            $headers .= "Message-ID: <" . md5(uniqid('', true)) . "@" . $this->host . ">\r\n";

            $body  = "--{$boundary}\r\n";
            $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= chunk_split(base64_encode($plainBody)) . "\r\n";
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Type: text/html; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= chunk_split(base64_encode($htmlBody)) . "\r\n";
            $body .= "--{$boundary}--\r\n";

            fwrite($sock, $headers . "\r\n" . $body . "\r\n.");
            fwrite($sock, "\r\n");
            $this->expect($sock, 250, 'Mensaje enviado');

            $this->send_cmd($sock, 'QUIT');
            fclose($sock);
            return true;

        } catch (\Throwable $e) {
            error_log('[MailService] ' . $e->getMessage());
            @fclose($sock);
            return false;
        }
    }

    /** Envía un comando SMTP (añade CRLF). */
    private function send_cmd($sock, string $cmd): void
    {
        fwrite($sock, $cmd . "\r\n");
    }

    /** Lee la respuesta completa del servidor (puede ser multi-línea). */
    private function read($sock): string
    {
        $response = '';
        while ($line = fgets($sock, 512)) {
            $response .= $line;
            // El último bloque de una respuesta SMTP no tiene guión en posición 4
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        return $response;
    }

    /** Lee respuesta y lanza excepción si el código no coincide. */
    private function expect($sock, int $code, string $step): void
    {
        $resp = $this->read($sock);
        if (!str_starts_with($resp, (string)$code)) {
            throw new \RuntimeException("[{$step}] Esperaba {$code}, recibí: " . trim($resp));
        }
    }

    // ── PHPMailer (opcional, si está instalado via Composer) ───────────────

    private function sendViaPHPMailer(string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
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
            error_log('[MailService] PHPMailer: ' . $e->getMessage());
            return false;
        }
    }

    // ── Helpers de plantilla ───────────────────────────────────────────────

    public static function renderTemplate(string $subjectKey, string $bodyKey, array $vars): array
    {
        $subjectRow = Database::fetch('SELECT `value` FROM rsgrup_settings WHERE `key` = ?', [$subjectKey]);
        $bodyRow    = Database::fetch('SELECT `value` FROM rsgrup_settings WHERE `key` = ?', [$bodyKey]);

        $subject = $subjectRow ? $subjectRow['value'] : 'Inscripción confirmada - RSGrup';
        $body    = $bodyRow    ? $bodyRow['value']    : self::defaultEmailTemplate();

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
             . '<p>Accede en: <a href="{{sitio}}">{{sitio}}</a></p>'
             . '<p>Saludos,<br>El equipo de RSGrup</p>';
    }
}
