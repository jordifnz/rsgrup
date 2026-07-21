<?php
declare(strict_types=1);

class PayPalService
{
    private string  $clientId;
    private string  $secret;
    private string  $baseUrl;
    private ?string $accessToken = null;

    public function __construct()
    {
        $this->clientId = $this->getSetting('paypal_client_id');
        $this->secret   = $this->getSetting('paypal_secret');
        $mode           = $this->getSetting('paypal_mode', 'sandbox');
        $this->baseUrl  = $mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    // La tabla usa setting_key / setting_value
    private function getSetting(string $key, string $default = ''): string
    {
        $row = Database::fetch(
            'SELECT setting_value FROM rsgrup_settings WHERE setting_key = ?',
            [$key]
        );
        return $row ? (string)$row['setting_value'] : $default;
    }

    private function getAccessToken(): string
    {
        if ($this->accessToken) return $this->accessToken;

        if (!$this->clientId || !$this->secret) {
            error_log('[PayPalService] client_id o secret no configurados en Ajustes.');
            return '';
        }

        $ch = curl_init($this->baseUrl . '/v1/oauth2/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_USERPWD        => $this->clientId . ':' . $this->secret,
            CURLOPT_POSTFIELDS     => 'grant_type=client_credentials',
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $raw      = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            error_log('[PayPalService::getAccessToken] cURL error: ' . $curlErr);
            return '';
        }

        $response = json_decode($raw, true);
        if ($httpCode !== 200 || empty($response['access_token'])) {
            error_log('[PayPalService::getAccessToken] HTTP ' . $httpCode . ' - ' . $raw);
            return '';
        }

        $this->accessToken = $response['access_token'];
        return $this->accessToken;
    }

    public function createOrder(array $delivery, int $userId): string
    {
        $token = $this->getAccessToken();
        if (!$token) {
            error_log('[PayPalService::createOrder] Sin access token, abortando.');
            return '';
        }

        $price     = number_format((float)($delivery['price'] ?? 0), 2, '.', '');
        $returnUrl = BASE_URL . '/paypal/success';
        $cancelUrl = BASE_URL . '/paypal/cancel';

        $payload = json_encode([
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => 'delivery_' . $delivery['id'] . '_user_' . $userId,
                'description'  => $delivery['title'] ?? 'RSGrup',
                'amount'       => ['currency_code' => 'EUR', 'value' => $price],
            ]],
            'application_context' => [
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
                'brand_name' => 'RSGrup',
                'user_action' => 'PAY_NOW',
            ],
        ]);

        $ch = curl_init($this->baseUrl . '/v2/checkout/orders');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $raw      = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            error_log('[PayPalService::createOrder] cURL error: ' . $curlErr);
            return '';
        }

        $response = json_decode($raw, true);
        if (empty($response['id'])) {
            error_log('[PayPalService::createOrder] HTTP ' . $httpCode . ' - ' . $raw);
            return '';
        }

        return $response['id'];
    }

    /**
     * Devuelve la URL de aprobación de PayPal para un orderId.
     * El link 'approve' ya viene incluido en la respuesta de createOrder,
     * así que lo extraemos directamente sin hacer una segunda llamada a la API.
     */
    public function getApproveUrl(string $orderId): string
    {
        if (!$orderId) return BASE_URL . '/dashboard';

        $token = $this->getAccessToken();
        $ch    = curl_init($this->baseUrl . '/v2/checkout/orders/' . urlencode($orderId));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $raw      = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            error_log('[PayPalService::getApproveUrl] cURL error: ' . $curlErr);
            return BASE_URL . '/dashboard';
        }

        $response = json_decode($raw, true);
        foreach ($response['links'] ?? [] as $link) {
            if (($link['rel'] ?? '') === 'approve') return $link['href'];
        }

        error_log('[PayPalService::getApproveUrl] No approve link. HTTP ' . $httpCode . ' - ' . $raw);
        return BASE_URL . '/dashboard';
    }

    public function captureOrder(string $orderId): bool
    {
        if (!$orderId) return false;

        $token = $this->getAccessToken();
        $ch    = curl_init($this->baseUrl . '/v2/checkout/orders/' . urlencode($orderId) . '/capture');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => '{}',
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $raw      = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            error_log('[PayPalService::captureOrder] cURL error: ' . $curlErr);
            return false;
        }

        $response = json_decode($raw, true);
        if (($response['status'] ?? '') !== 'COMPLETED') {
            error_log('[PayPalService::captureOrder] HTTP ' . $httpCode . ' - ' . $raw);
            return false;
        }
        return true;
    }
}
