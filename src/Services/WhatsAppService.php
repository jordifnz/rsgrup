<?php
declare(strict_types=1);

class WhatsAppService
{
    private string $apiUrl;
    private string $apiToken;
    private string $instance;

    public function __construct()
    {
        $this->apiUrl   = $this->getSetting('evolution_api_url', '');
        $this->apiToken = $this->getSetting('evolution_api_token', '');
        $this->instance = $this->getSetting('evolution_instance', '');
    }

    private function getSetting(string $key, string $default = ''): string
    {
        $row = Database::fetch('SELECT value FROM rsgrup_settings WHERE `key`=?', [$key]);
        return $row ? $row['value'] : $default;
    }

    public function send(string $phone, string $message): bool
    {
        if (!$this->apiUrl || !$this->apiToken || !$this->instance) {
            error_log('[WhatsAppService] Evolution API not configured.');
            return false;
        }

        // Normalize phone: remove spaces, dashes, +
        $phone = preg_replace('/[^0-9]/', '', $phone);
        // Ensure country code (Spain: 34)
        if (strlen($phone) === 9) $phone = '34' . $phone;

        $url     = rtrim($this->apiUrl, '/') . '/message/sendText/' . urlencode($this->instance);
        $payload = json_encode([
            'number'  => $phone . '@s.whatsapp.net',
            'options' => ['delay' => 1200, 'presence' => 'composing'],
            'textMessage' => ['text' => $message],
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
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        }
        error_log('[WhatsAppService] HTTP ' . $httpCode . ': ' . $response);
        return false;
    }

    public static function renderTemplate(array $vars): string
    {
        $tpl = Database::fetch('SELECT value FROM rsgrup_settings WHERE `key`="whatsapp_template"');
        $text = $tpl ? $tpl['value'] : 'Hola {{nombre}}, tu inscripción a {{entrega}} ha sido confirmada. Accede en: {{url}}';
        foreach ($vars as $k => $v) {
            $text = str_replace('{{'.$k.'}}', (string)$v, $text);
        }
        return $text;
    }
}
