<?php

/**
 * PesapalAPI - Service class for Pesapal v3 payment gateway integration.
 *
 * Supports M-Pesa, Airtel Money, card payments and more across
 * multiple African countries via the Pesapal v3 REST API.
 *
 * Endpoints:
 *   Auth:                POST /api/Auth/RequestToken
 *   Register IPN:        POST /api/URLSetup/RegisterIPN
 *   Get IPN List:        GET  /api/URLSetup/GetIpnList
 *   Submit Order:        POST /api/Transactions/SubmitOrderRequest
 *   Get Transaction:     GET  /api/Transactions/GetTransactionStatus
 */
class PesapalAPI
{
    private static int $timeout = 30;

    // -----------------------------------------------------------------------
    // Base URLs
    // -----------------------------------------------------------------------

    private static function getBaseUrl(): string
    {
        return self::isTestMode()
            ? 'https://cybqa.pesapal.com/pesapalv3'
            : 'https://pay.pesapal.com/v3';
    }

    // -----------------------------------------------------------------------
    // Settings helpers  — reads from DB settings table
    // -----------------------------------------------------------------------

    public static function getConsumerKey(): string
    {
        $key = self::readSetting('pesapal_consumer_key');
        if (empty($key)) {
            $key = self::readSetting('pesapal_key');
        }
        if (empty($key)) {
            $key = env('PESAPAL_CONSUMER_KEY') ?: '';
        }
        return trim($key);
    }

    public static function getConsumerSecret(): string
    {
        $secret = self::readSetting('pesapal_consumer_secret');
        if (empty($secret)) {
            $secret = self::readSetting('pesapal_secret');
        }
        if (empty($secret)) {
            $secret = env('PESAPAL_CONSUMER_SECRET') ?: '';
        }
        return trim($secret);
    }

    public static function isTestMode(): bool
    {
        $testMode = self::readSetting('pesapal_test_mode');
        if ($testMode !== '') {
            return $testMode === '1';
        }
        $env = self::readSetting('pesapal_env');
        return ($env ?: 'sandbox') !== 'production';
    }

    public static function getIpnId(): string
    {
        return trim(self::readSetting('pesapal_ipn_id') ?? '');
    }

    public static function isEnabled(): bool
    {
        return (self::readSetting('pesapal_enabled') ?? '0') === '1';
    }

    public static function isConfigured(): bool
    {
        return !empty(self::getConsumerKey()) && !empty(self::getConsumerSecret());
    }

    private static function readSetting(string $key): string
    {
        try {
            $row = Database::selectOne("SELECT value FROM settings WHERE `key` = ?", [$key]);
            return $row ? trim($row['value']) : '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    // -----------------------------------------------------------------------
    // Core HTTP helpers
    // -----------------------------------------------------------------------

    private static function curlPost(string $url, array $payload, string $bearerToken = ''): array
    {
        $ch = curl_init($url);
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        if ($bearerToken) {
            $headers[] = 'Authorization: Bearer ' . $bearerToken;
        }
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::$timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT      => 'ShopSmart/1.0',
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            self::log('curl_error', ['url' => $url, 'error' => $error]);
            return ['success' => false, 'error' => 'cURL error: ' . $error, 'http_code' => 0];
        }

        // Log the raw response for debugging
        self::log('api_response', [
            'url'       => $url,
            'http_code' => $httpCode,
            'response'  => substr($response ?? '', 0, 2000),
        ]);

        $decoded = json_decode($response, true);

        // If JSON decode failed, the response might be HTML or malformed
        if ($decoded === null && !empty($response)) {
            self::log('json_decode_failed', ['url' => $url, 'raw_response' => substr($response, 0, 500)]);
            return [
                'success'   => false,
                'error'     => 'Invalid JSON response from Pesapal. The server may have returned an error page.',
                'http_code' => $httpCode,
                'data'      => null,
                'raw'       => $response,
            ];
        }

        return [
            'success'   => $httpCode >= 200 && $httpCode < 300,
            'http_code' => $httpCode,
            'data'      => $decoded,
            'raw'       => $response,
        ];
    }

    private static function curlGet(string $url, string $bearerToken): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Authorization: Bearer ' . $bearerToken,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::$timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT      => 'ShopSmart/1.0',
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => 'cURL error: ' . $error, 'http_code' => 0];
        }

        self::log('api_response', [
            'url'       => $url,
            'http_code' => $httpCode,
            'response'  => substr($response ?? '', 0, 2000),
        ]);

        $decoded = json_decode($response, true);

        if ($decoded === null && !empty($response)) {
            return [
                'success'   => false,
                'error'     => 'Invalid JSON response from Pesapal.',
                'http_code' => $httpCode,
                'data'      => null,
                'raw'       => $response,
            ];
        }

        return [
            'success'   => $httpCode >= 200 && $httpCode < 300,
            'http_code' => $httpCode,
            'data'      => $decoded,
            'raw'       => $response,
        ];
    }

    // -----------------------------------------------------------------------
    // API Methods
    // -----------------------------------------------------------------------

    /**
     * Get OAuth bearer token using consumer_key and consumer_secret.
     *
     * @return array ['success' => bool, 'token' => string, 'error' => string, 'http_code' => int]
     */
    public static function getAuthToken(): array
    {
        $consumerKey    = self::getConsumerKey();
        $consumerSecret = self::getConsumerSecret();
        $baseUrl        = self::getBaseUrl();
        $authUrl        = $baseUrl . '/api/Auth/RequestToken';

        if (empty($consumerKey) || empty($consumerSecret)) {
            return ['success' => false, 'token' => '', 'error' => 'Consumer key and secret are required', 'http_code' => 0];
        }

        self::log('auth_request', [
            'url'          => $authUrl,
            'has_key'      => !empty($consumerKey),
            'has_secret'   => !empty($consumerSecret),
            'test_mode'    => self::isTestMode(),
        ]);

        // Pesapal v3 sends credentials in the request body as JSON
        $result = self::curlPost($authUrl, [
            'consumer_key'    => $consumerKey,
            'consumer_secret' => $consumerSecret,
        ]);

        // If the request itself failed (cURL error, non-2xx)
        if (!$result['success']) {
            $httpCode = $result['http_code'];
            $rawResp = $result['raw'] ?? '';

            // Try to extract error message from response body
            $errDetail = '';
            if (!empty($rawResp)) {
                $errData = json_decode($rawResp, true);
                if ($errData) {
                    // Pesapal may return: {"error":"...","error_description":"..."}
                    $errDetail = $errData['error_description'] ?? $errData['error'] ?? $errData['message'] ?? '';
                }
            }

            if (empty($errDetail)) {
                $errDetail = $result['error'] ?? 'HTTP ' . $httpCode;
            }

            // Special handling for common HTTP errors
            if ($httpCode === 0) {
                return ['success' => false, 'token' => '', 'error' => 'Could not connect to Pesapal. Check your server\'s firewall/outbound rules. Error: ' . $errDetail, 'http_code' => $httpCode];
            }
            if ($httpCode === 401) {
                return ['success' => false, 'token' => '', 'error' => 'Invalid consumer key or secret. Double-check your credentials in the Pesapal Developer Portal.', 'http_code' => $httpCode];
            }
            if ($httpCode === 400) {
                return ['success' => false, 'token' => '', 'error' => 'Bad request: ' . $errDetail, 'http_code' => $httpCode];
            }

            return ['success' => false, 'token' => '', 'error' => $errDetail, 'http_code' => $httpCode];
        }

        // Request succeeded (2xx) — extract token
        $data = $result['data'] ?? [];

        // Pesapal v3 returns: {"token":"eyJ...","expiryDate":"2024-..."}
        // But also check for alternative field names just in case
        $token = $data['token'] ?? $data['access_token'] ?? $data['Token'] ?? '';

        if (empty($token)) {
            // Log the full response for debugging
            self::log('auth_no_token', [
                'http_code'  => $result['http_code'],
                'response'   => $result['raw'] ?? '',
                'data_keys'  => is_array($data) ? array_keys($data) : 'not-array',
            ]);

            $respSnippet = substr($result['raw'] ?? 'empty response', 0, 300);
            return [
                'success'   => false,
                'token'     => '',
                'error'     => 'No token in response. Pesapal returned HTTP ' . $result['http_code'] . ' but the response body does not contain a token. Response: ' . $respSnippet,
                'http_code' => $result['http_code'],
            ];
        }

        return [
            'success'   => true,
            'token'     => $token,
            'expires'   => $data['expiryDate'] ?? $data['expires'] ?? '',
            'http_code' => $result['http_code'],
        ];
    }

    /**
     * Register an IPN (Instant Payment Notification) callback URL with Pesapal.
     *
     * @param string $notificationUrl The public URL Pesapal will send status notifications to
     * @param string $method         HTTP method for IPN: 'GET' or 'POST' (default: POST)
     * @return array ['success' => bool, 'ipn_id' => string, 'error' => string]
     */
    public static function registerIPN(string $notificationUrl, string $method = 'POST'): array
    {
        $auth = self::getAuthToken();
        if (!$auth['success']) {
            return ['success' => false, 'ipn_id' => '', 'error' => 'Auth failed: ' . $auth['error']];
        }

        $baseUrl = self::getBaseUrl();
        $result  = self::curlPost($baseUrl . '/api/URLSetup/RegisterIPN', [
            'url'                    => $notificationUrl,
            'ipn_notification_type'  => $method,
        ], $auth['token']);

        if (!$result['success']) {
            $errMsg = '';
            if (is_array($result['data'] ?? null)) {
                $errMsg = $result['data']['error']['message'] ?? ($result['data']['message'] ?? '');
            }
            if (empty($errMsg)) {
                $errMsg = $result['error'] ?? 'IPN registration failed';
            }
            return ['success' => false, 'ipn_id' => '', 'error' => $errMsg, 'http_code' => $result['http_code']];
        }

        $ipnId = $result['data']['ipn_id'] ?? '';
        if (empty($ipnId)) {
            return ['success' => false, 'ipn_id' => '', 'error' => 'No ipn_id in response. Response: ' . substr($result['raw'] ?? '', 0, 300), 'http_code' => $result['http_code']];
        }

        // Save the IPN ID to settings for later use
        self::saveSetting('pesapal_ipn_id', $ipnId, 'payments');

        return [
            'success'     => true,
            'ipn_id'      => $ipnId,
            'url'         => $result['data']['url'] ?? $notificationUrl,
            'status'      => $result['data']['ipn_status_description'] ?? '',
            'http_code'   => $result['http_code'],
        ];
    }

    /**
     * Get list of all registered IPN URLs.
     *
     * @return array ['success' => bool, 'ipns' => array, 'error' => string]
     */
    public static function getIpnList(): array
    {
        $auth = self::getAuthToken();
        if (!$auth['success']) {
            return ['success' => false, 'ipns' => [], 'error' => 'Auth failed: ' . $auth['error']];
        }

        $baseUrl = self::getBaseUrl();
        $result  = self::curlGet($baseUrl . '/api/URLSetup/GetIpnList', $auth['token']);

        if (!$result['success']) {
            return ['success' => false, 'ipns' => [], 'error' => $result['error'] ?? 'Failed to get IPN list'];
        }

        return [
            'success' => true,
            'ipns'    => is_array($result['data']) ? $result['data'] : [],
        ];
    }

    /**
     * Submit an order to Pesapal for payment.
     *
     * @param array  $order        Order data: id, order_number, total_amount, customer_name, customer_email, customer_phone
     * @param string $callbackUrl  The URL Pesapal redirects the user to after payment
     * @param string $ipnId        The IPN notification ID (from registerIPN or Pesapal dashboard)
     * @param string $currency     Currency code (default: KES)
     * @return array ['success' => bool, 'redirect_url' => string, 'tracking_id' => string, 'error' => string]
     */
    public static function submitOrder(array $order, string $callbackUrl, string $ipnId, string $currency = 'KES'): array
    {
        $auth = self::getAuthToken();
        if (!$auth['success']) {
            return ['success' => false, 'redirect_url' => '', 'tracking_id' => '', 'error' => 'Auth failed: ' . $auth['error']];
        }

        $baseUrl = self::getBaseUrl();
        $nameParts = explode(' ', $order['customer_name'] ?? 'Customer', 2);

        $payload = [
            'id'              => $order['order_number'] ?? (string)$order['id'],
            'currency'        => $currency,
            'amount'          => round((float)($order['total_amount'] ?? 0), 2),
            'description'     => 'Order ' . ($order['order_number'] ?? $order['id']),
            'callback_url'    => $callbackUrl,
            'notification_id' => $ipnId,
            'billing_address' => [
                'email_address' => $order['customer_email'] ?? '',
                'phone_number'  => preg_replace('/[^0-9]/', '', $order['customer_phone'] ?? ''),
                'first_name'    => $nameParts[0],
                'last_name'     => $nameParts[1] ?? '',
                'country'       => 'Kenya',
            ],
        ];

        $result = self::curlPost($baseUrl . '/api/Transactions/SubmitOrderRequest', $payload, $auth['token']);

        if (!$result['success']) {
            $errMsg = '';
            if (is_array($result['data'] ?? null)) {
                $errMsg = $result['data']['error']['message'] ?? ($result['data']['message'] ?? '');
            }
            if (empty($errMsg)) {
                $errMsg = $result['error'] ?? 'Order submission failed';
            }
            return ['success' => false, 'redirect_url' => '', 'tracking_id' => '', 'error' => $errMsg, 'http_code' => $result['http_code']];
        }

        $redirectUrl = $result['data']['redirect_url'] ?? '';
        $trackingId  = $result['data']['order_tracking_id'] ?? '';

        if (empty($redirectUrl)) {
            $errMsg = '';
            if (is_array($result['data'] ?? null)) {
                $errMsg = $result['data']['error']['message'] ?? '';
            }
            if (empty($errMsg)) {
                $errMsg = json_encode($result['data']);
            }
            return ['success' => false, 'redirect_url' => '', 'tracking_id' => '', 'error' => 'No redirect URL: ' . $errMsg];
        }

        return [
            'success'      => true,
            'redirect_url' => $redirectUrl,
            'tracking_id'  => $trackingId,
        ];
    }

    /**
     * Get the payment status of a transaction from Pesapal.
     *
     * @param string $trackingId The order_tracking_id returned from submitOrder
     * @return array ['success' => bool, 'status_code' => string, 'payment_status' => string, 'status_description' => string, 'error' => string]
     */
    public static function getTransactionStatus(string $trackingId): array
    {
        $auth = self::getAuthToken();
        if (!$auth['success']) {
            return ['success' => false, 'status_code' => '', 'payment_status' => '', 'status_description' => '', 'error' => 'Auth failed: ' . $auth['error']];
        }

        $baseUrl = self::getBaseUrl();
        $url     = $baseUrl . '/api/Transactions/GetTransactionStatus?orderTrackingId=' . urlencode($trackingId);
        $result  = self::curlGet($url, $auth['token']);

        if (!$result['success']) {
            return ['success' => false, 'status_code' => '', 'payment_status' => '', 'status_description' => '', 'error' => $result['error'] ?? 'Failed to get transaction status'];
        }

        $data = $result['data'];

        return [
            'success'            => true,
            'status_code'        => $data['status_code'] ?? '',
            'payment_status'     => $data['payment_status_description'] ?? ($data['status_code'] ?? ''),
            'status_description' => $data['payment_status_description'] ?? '',
            'payment_method'     => $data['payment_method'] ?? '',
            'tracking_id'        => $data['order_tracking_id'] ?? $trackingId,
            'merchant_reference' => $data['order_merchant_reference'] ?? '',
            'amount'             => $data['amount'] ?? 0,
            'currency'           => $data['currency'] ?? '',
            'raw'                => $data,
        ];
    }

    // -----------------------------------------------------------------------
    // Convenience: complete checkout flow
    // -----------------------------------------------------------------------

    /**
     * Automatically register IPN if not already set, then submit order.
     *
     * @param array  $order        Order data
     * @param string $callbackUrl  Redirect URL after payment
     * @param string $ipnUrl       IPN notification URL
     * @param string $currency     Currency code
     * @return array
     */
    public static function checkout(array $order, string $callbackUrl, string $ipnUrl, string $currency = 'KES'): array
    {
        // Get or register IPN ID
        $ipnId = self::getIpnId();

        if (empty($ipnId)) {
            // Try to register IPN automatically
            $ipnResult = self::registerIPN($ipnUrl);
            if ($ipnResult['success']) {
                $ipnId = $ipnResult['ipn_id'];
            }
            // If registration fails, continue without IPN (callback URL still works)
        }

        return self::submitOrder($order, $callbackUrl, $ipnId, $currency);
    }

    // -----------------------------------------------------------------------
    // Test Connection
    // -----------------------------------------------------------------------

    /**
     * Test the Pesapal API connection by requesting an auth token.
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public static function testConnection(): array
    {
        if (!self::isConfigured()) {
            return ['success' => false, 'message' => 'Consumer key and secret are required'];
        }

        $result = self::getAuthToken();

        if ($result['success']) {
            $env = self::isTestMode() ? 'Sandbox' : 'Production';
            return ['success' => true, 'message' => "Connection successful! ({$env}) Token expires: " . ($result['expires'] ?? 'N/A')];
        }

        return ['success' => false, 'message' => $result['error'] ?? 'Connection failed'];
    }

    // -----------------------------------------------------------------------
    // Settings persistence
    // -----------------------------------------------------------------------

    private static function saveSetting(string $key, string $value, string $group = 'payments'): void
    {
        try {
            $existing = Database::selectOne("SELECT id FROM settings WHERE `key` = ?", [$key]);
            if ($existing) {
                Database::update('settings', ['value' => $value, 'updated_at' => date('Y-m-d H:i:s')], '`key` = ?', [$key]);
            } else {
                Database::insert('settings', [
                    'key'        => $key,
                    'value'      => $value,
                    'group_name' => $group,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        } catch (\Throwable $e) {
            // Silently fail
        }
    }

    // -----------------------------------------------------------------------
    // Logging
    // -----------------------------------------------------------------------

    private static function log(string $event, array $data = []): void
    {
        try {
            $logDir = ROOT_PATH . '/logs';
            if (!is_dir($logDir)) mkdir($logDir, 0755, true);
            $logEntry = date('Y-m-d H:i:s') . " [Pesapal][{$event}] " . json_encode($data, JSON_UNESCAPED_SLASHES) . "\n";
            file_put_contents($logDir . '/pesapal.log', $logEntry, FILE_APPEND);
        } catch (\Throwable $e) {
            // Silent fail for logging
        }
    }
}