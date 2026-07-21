<?php
declare(strict_types=1);

class WhatsAppService
{
    private string $apiUrl;
    private string $apiToken;
    private string $instance;

    public function __construct()
    {
        $this->apiUrl   = $this->getSetting('evolution_api_url',   '');
        $this->apiToken = $this->getSetting('evolution_api_token', '');
        $this->instance = $this->getSetting('evolution_instance',  '');
    }

    private function getSetting(string $key, string $default = ''): string
    {
        $row = Database::fetch(
            'SELECT `value` FROM rsgrup_settings WHERE `key` = ?',
            [$key]
        );
        return $row ? (string)$row['value'] : $default;
    }

    /**
     * Normaliza el teléfono a formato internacional sin + ni espacios.
     * Ejemplos: "612 345 678" → "34612345678"
     *           "+34 612 345 678" → "34612345678"
     *           "0034612345678"  → "34612345678"
     *           "34612345678"    → "34612345678"  (ya correcto)
     */
    private function normalizePhone(string $phone): string
    {
        // Quitar todo lo que no sea dígito
        $digits = preg_replace('/[^0-9]/', '', $phone);

        // Quitar prefijo 0034
        if (str_starts_with($digits, '0034')) {
            $digits = substr($digits, 4);
        }
        // Quitar prefijo 34 si va seguido de 9 dígitos (número español sin el 34)
        if (str_starts_with($digits, '34') && strlen($digits) === 11) {
            $digits = substr($digits, 2);
        }
        // Si quedan 9 dígitos, añadir prefijo España
        if (strlen($digits) === 9) {
            $digits = '34' . $digits;
        }

        return $digits;
    }

    public function send(string $phone, string $message): bool
    {
        if (!$this->apiUrl || !$this->apiToken || !$this->instance) {
            error_log('[WhatsAppService] Evolution API no configurada (evolution_api_url, evolution_api_token, evolution_instance).');
            return false;
        }

        $phone = $this->normalizePhone($phone);
        if (strlen($phone) < 10) {
            error_log('[WhatsAppService] Teléfono inválido tras normalización: ' . $phone);
            return false;
        }

        $url     = rtrim($this->apiUrl, '/') . '/message/sendText/' . urlencode($this->instance);
        $payload = json_encode([
            'number' => $phone . '@s.whatsapp.net',
            'text'   => $message,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'apikey: ' . $this->apiToken,
            ],
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            error_log('[WhatsAppService] cURL error: ' . $curlErr);
            return false;
        }
        if ($httpCode < 200 || $httpCode >= 300) {
            error_log('[WhatsAppService] HTTP ' . $httpCode . ': ' . $response);
            return false;
        }
        return true;
    }

    public static function renderTemplate(array $vars): string
    {
        $tpl  = Database::fetch('SELECT `value` FROM rsgrup_settings WHERE `key` = ?', ['whatsapp_template']);
        $text = $tpl
            ? $tpl['value']
            : 'Hola {{nombre}}, tu inscripción a {{entrega}} ha sido confirmada el {{fecha}}. Accede en: {{sitio}}';
        foreach ($vars as $k => $v) {
            $text = str_replace('{{' . $k . '}}', (string)$v, $text);
        }
        return $text;
    }
}
