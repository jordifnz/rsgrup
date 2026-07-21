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
     * Normaliza el teléfono al formato que Evolution API necesita:
     * número E.164 sin "+", sin espacios ni guiones.
     *
     * Ejemplos:
     *   "612 345 678"        → "34612345678"  (móvil ES sin prefijo)
     *   "+34 612 345 678"    → "34612345678"
     *   "0034612345678"      → "34612345678"
     *   "34612345678"        → "34612345678"  (ya correcto)
     *   "+52 55 1234 5678"   → "525512345678" (México, 12 dígitos)
     */
    public function normalizePhone(string $phone): string
    {
        // 1. Quitar todo excepto dígitos
        $d = preg_replace('/[^0-9]/', '', $phone);

        // 2. Eliminar prefijo de marcación internacional 00
        if (str_starts_with($d, '00')) {
            $d = substr($d, 2);
        }

        // 3. Si son exactamente 9 dígitos y empieza por 6, 7 o 9 → móvil/fijo español sin prefijo
        if (strlen($d) === 9 && preg_match('/^[679]/', $d)) {
            $d = '34' . $d;
        }

        // 4. Si tras quitar el 00 quedan 9 dígitos genéricos, añadir 34
        //    (cubre el caso de que el usuario haya guardado sólo el número local)
        if (strlen($d) === 9) {
            $d = '34' . $d;
        }

        return $d;
    }

    public function send(string $phone, string $message): bool
    {
        // Validar configuración
        if ($this->apiUrl === '' || $this->apiToken === '' || $this->instance === '') {
            error_log('[WhatsAppService] Evolution API no configurada. '
                . 'evolution_api_url=' . ($this->apiUrl ?: '(vacío)') . ' '
                . 'evolution_api_token=' . ($this->apiToken ? '(ok)' : '(vacío)') . ' '
                . 'evolution_instance=' . ($this->instance ?: '(vacío)'));
            return false;
        }

        $normalized = $this->normalizePhone($phone);

        if (strlen($normalized) < 10) {
            error_log('[WhatsAppService] Teléfono inválido tras normalización. '
                . 'original=' . $phone . ' normalizado=' . $normalized);
            return false;
        }

        // Evolution API v2: POST /message/sendText/{instance}
        // El destinatario va como "<número>@s.whatsapp.net"
        $url     = rtrim($this->apiUrl, '/') . '/message/sendText/' . urlencode($this->instance);
        $payload = json_encode([
            'number'  => $normalized . '@s.whatsapp.net',
            'text'    => $message,
        ], JSON_UNESCAPED_UNICODE);

        error_log('[WhatsAppService] Enviando a ' . $normalized . '@s.whatsapp.net via ' . $url);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'apikey: ' . $this->apiToken,
            ],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr !== '') {
            error_log('[WhatsAppService] cURL error: ' . $curlErr);
            return false;
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            error_log('[WhatsAppService] HTTP ' . $httpCode . ' response: ' . $response);
            return false;
        }

        error_log('[WhatsAppService] Enviado OK. HTTP ' . $httpCode . ' response: ' . $response);
        return true;
    }
}
