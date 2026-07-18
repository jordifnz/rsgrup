<?php
declare(strict_types=1);

class PayPalService
{
    private string $clientId;
    private string $secret;
    private string $baseUrl;
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

    private function getSetting(string $key, string $default = ''): string
    {
        $row = Database::fetch('SELECT value FROM rsgrup_settings WHERE `key`=?', [$key]);
        return $row ? $row['value'] : $default;
    }

    private function getAccessToken(): string
    {
        if ($this->accessToken) return $this->accessToken;

        $ch = curl_init($this->baseUrl . '/v1/oauth2/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_USERPWD        => $this->clientId . ':' . $this->secret,
            CURLOPT_POSTFIELDS     => 'grant_type=client_credentials',
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);
        $this->accessToken = $response['access_token'] ?? '';
        return $this->accessToken;
    }

    public function createOrder(array $delivery, int $userId): string
    {
        $token    = $this->getAccessToken();
        $price    = number_format((float)$delivery['price'], 2, '.', '');
        $returnUrl = BASE_URL . '/paypal/success';
        $cancelUrl = BASE_URL . '/paypal/cancel';

        $payload = json_encode([
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => 'delivery_' . $delivery['id'] . '_user_' . $userId,
                'description'  => $delivery['title'],
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
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);
        return $response['id'] ?? '';
    }

    public function getApproveUrl(string $orderId): string
    {
        $token = $this->getAccessToken();
        $ch    = curl_init($this->baseUrl . '/v2/checkout/orders/' . $orderId);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);
        foreach ($response['links'] ?? [] as $link) {
            if ($link['rel'] === 'approve') return $link['href'];
        }
        return BASE_URL . '/dashboard';
    }

    public function captureOrder(string $orderId): bool
    {
        $token = $this->getAccessToken();
        $ch    = curl_init($this->baseUrl . '/v2/checkout/orders/' . $orderId . '/capture');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => '{}',
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = json_decode(curl_exec($ch), true);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return isset($response['status']) && $response['status'] === 'COMPLETED';
    }
}
