<?php

/**
 * IntaSend Payment Gateway Service
 *
 * Wraps the IntaSend PHP SDK for creating checkouts and verifying payments.
 * Supports M-Pesa, card payments, and bank transfers in Kenya.
 *
 * Settings keys used from the `settings` DB table:
 *   - intasend_publishable_key   (also falls back to .env INTASEND_PUBLISHABLE_KEY)
 *   - intasend_secret            (secret key — REQUIRED for server-side operations)
 *   - intasend_test_mode         (bool string "1"/"0", also falls back to .env INTASEND_TEST_ENVIRONMENT)
 */
class IntaSendAPI
{
    /**
     * Build and return the credentials array required by the IntaSend SDK.
     * The SDK init() expects: publishable_key, token (secret key), test.
     * Priority: DB settings > .env values.
     */
    public static function getCredentials(): array
    {
        $pubKey = '';
        $secretKey = '';
        $testMode = true;

        try {
            $row = Database::selectOne("SELECT value FROM settings WHERE `key` = ?", ['intasend_publishable_key']);
            if ($row && !empty($row['value'])) {
                $pubKey = trim($row['value']);
            }
            $secretRow = Database::selectOne("SELECT value FROM settings WHERE `key` = ?", ['intasend_secret']);
            if ($secretRow && !empty($secretRow['value'])) {
                $secretKey = trim($secretRow['value']);
            }
            $testRow = Database::selectOne("SELECT value FROM settings WHERE `key` = ?", ['intasend_test_mode']);
            if ($testRow && $testRow['value'] !== '') {
                $testMode = $testRow['value'] === '1';
            }
        } catch (\Throwable $e) {
            // DB not available, fall through to .env
        }

        // Fall back to .env
        if (empty($pubKey)) {
            $pubKey = env('INTASEND_PUBLISHABLE_KEY', '');
        }
        if (empty($secretKey)) {
            $secretKey = env('INTASEND_SECRET_KEY', '');
        }

        return [
            'publishable_key' => $pubKey,
            'token'           => $secretKey,  // SDK expects secret key as 'token'
            'test'            => $testMode,
        ];
    }

    /**
     * Check if IntaSend is configured (has both keys).
     */
    public static function isConfigured(): bool
    {
        $creds = self::getCredentials();
        return !empty($creds['publishable_key']) && !empty($creds['token']);
    }

    /**
     * Get the secret key (for direct API calls).
     */
    private static function getSecretKey(): string
    {
        $creds = self::getCredentials();
        return $creds['token'] ?? '';
    }

    /**
     * Create an IntaSend checkout session for an order.
     *
     * Uses the IntaSend SDK if available, otherwise falls back to direct API.
     *
     * @param array  $order       Order row from DB
     * @param string $redirectUrl The URL IntaSend redirects to after payment
     * @return array ['success' => bool, 'url' => string, 'invoice_id' => string, 'error' => string]
     */
    public static function createCheckout(array $order, string $redirectUrl): array
    {
        $credentials = self::getCredentials();

        if (empty($credentials['publishable_key'])) {
            return ['success' => false, 'url' => '', 'invoice_id' => '', 'error' => 'IntaSend publishable key not configured'];
        }

        if (empty($credentials['token'])) {
            return ['success' => false, 'url' => '', 'invoice_id' => '', 'error' => 'IntaSend secret key not configured. Add it in Admin → Settings.'];
        }

        try {
            // Use the IntaSend SDK if available
            if (class_exists(\IntaSend\IntaSendPHP\Checkout::class)) {
                $result = self::createCheckoutViaSDK($credentials, $order, $redirectUrl);
                // If SDK fails with auth error, fall through to direct API
                if ($result['success'] || stripos($result['error'] ?? '', 'authentication') === false) {
                    return $result;
                }
                // SDK auth failed — try direct API as fallback
            }

            // Direct API call (primary fallback)
            return self::createCheckoutViaAPI($credentials, $order, $redirectUrl);
        } catch (\Throwable $e) {
            // Log the error
            self::log('checkout_error', ['error' => $e->getMessage(), 'order_id' => $order['id'] ?? 0]);
            return ['success' => false, 'url' => '', 'invoice_id' => '', 'error' => 'IntaSend error: ' . $e->getMessage()];
        }
    }

    /**
     * Create checkout using the IntaSend PHP SDK.
     */
    private static function createCheckoutViaSDK(array $credentials, array $order, string $redirectUrl): array
    {
        $customer = new \IntaSend\IntaSendPHP\Customer();
        $nameParts = explode(' ', trim($order['customer_name'] ?? 'Customer'), 2);
        $customer->first_name = $nameParts[0];
        $customer->last_name = $nameParts[1] ?? '';
        $customer->email = $order['customer_email'] ?? '';
        $customer->country = 'KE';

        $checkout = new \IntaSend\IntaSendPHP\Checkout();
        // Pass full credentials including token (secret key)
        $checkout->init($credentials);

        $siteUrl = self::getSiteUrl();
        $amount = round((float)($order['total'] ?? 0), 2);
        $currency = 'KES';
        $refOrderNumber = $order['order_number'] ?? ('order-' . ($order['id'] ?? 0));

        $resp = $checkout->create(
            $amount,
            $currency,
            $customer,
            $siteUrl,
            $redirectUrl,
            $refOrderNumber,
            null,      // phone_number (optional)
            null,      // method (optional)
            'BUSINESS-PAYS',  // card_tariff
            'BUSINESS-PAYS',  // mobile_tariff
            null       // wallet_id (optional)
        );

        if (isset($resp->url)) {
            return [
                'success'    => true,
                'url'        => $resp->url,
                'invoice_id' => $resp->invoice_id ?? '',
                'error'      => '',
            ];
        }

        // SDK returned an error — extract message
        $errMsg = '';
        if (is_object($resp)) {
            // Check for SDK error structure
            if (isset($resp->type) && isset($resp->errors)) {
                $errors = $resp->errors;
                if (is_array($errors) && !empty($errors)) {
                    $firstErr = $errors[0];
                    $errMsg = ($firstErr->detail ?? $firstErr->code ?? json_encode($errors));
                } else {
                    $errMsg = $resp->message ?? json_encode($resp);
                }
            } else {
                $errMsg = $resp->message ?? json_encode($resp);
            }
        } elseif (is_array($resp)) {
            $errMsg = $resp['message'] ?? json_encode($resp);
        } else {
            $errMsg = (string)$resp;
        }

        self::log('sdk_error', ['error' => $errMsg, 'response' => json_encode($resp)]);

        return ['success' => false, 'url' => '', 'invoice_id' => '', 'error' => $errMsg];
    }

    /**
     * Create checkout via direct API call.
     * This is the most reliable method — uses Bearer token auth with secret key.
     */
    private static function createCheckoutViaAPI(array $credentials, array $order, string $redirectUrl): array
    {
        $secretKey = $credentials['token'] ?? '';
        if (empty($secretKey)) {
            return ['success' => false, 'url' => '', 'invoice_id' => '', 'error' => 'IntaSend secret key not configured'];
        }

        $nameParts = explode(' ', trim($order['customer_name'] ?? 'Customer'), 2);
        $phone = preg_replace('/[^0-9]/', '', $order['customer_phone'] ?? '');

        $payload = json_encode([
            'first_name'    => $nameParts[0],
            'last_name'     => $nameParts[1] ?? '',
            'email'         => $order['customer_email'] ?? '',
            'phone_number'  => $phone,
            'amount'        => round((float)($order['total'] ?? 0), 2),
            'currency'      => 'KES',
            'api_ref'       => $order['order_number'] ?? '',
            'redirect_url'  => $redirectUrl,
            'card_tariff'   => 'BUSINESS-PAYS',
            'mobile_tariff' => 'BUSINESS-PAYS',
        ]);

        // Determine base URL based on test mode
        $testMode = $credentials['test'] ?? true;
        $baseUrl = $testMode
            ? 'https://sandbox.intasend.com/api/v1/checkout/'
            : 'https://payment.intasend.com/api/v1/checkout/';

        $ch = curl_init($baseUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $secretKey,
                'X-IntaSend-Public-Key: ' . $credentials['publishable_key'],
            ],
        ]);

        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($resp === false) {
            self::log('api_curl_error', ['error' => $curlError, 'url' => $baseUrl]);
            return ['success' => false, 'url' => '', 'invoice_id' => '', 'error' => 'Connection failed: ' . $curlError];
        }

        $data = json_decode($resp, true);

        // Log the response for debugging
        self::log('api_response', ['http_code' => $httpCode, 'response' => $resp]);

        if (isset($data['url'])) {
            return [
                'success'    => true,
                'url'        => $data['url'],
                'invoice_id' => $data['invoice_id'] ?? '',
                'error'      => '',
            ];
        }

        // Extract error message
        $errMsg = '';
        if (isset($data['type']) && isset($data['errors'])) {
            // IntaSend error format: {"type":"client_error","errors":[{"code":"...","detail":"..."}]}
            $errors = is_array($data['errors']) ? $data['errors'] : [];
            if (!empty($errors)) {
                $firstErr = $errors[0];
                $errMsg = ($firstErr['detail'] ?? $firstErr['code'] ?? '') . ($firstErr['attr'] ? ' (' . $firstErr['attr'] . ')' : '');
            }
        }
        if (empty($errMsg)) {
            $errMsg = $data['message'] ?? $data['error'] ?? json_encode($data);
        }

        return ['success' => false, 'url' => '', 'invoice_id' => '', 'error' => $errMsg];
    }

    /**
     * Verify a payment by tracking ID (invoice ID).
     *
     * @param string $trackingId The invoice_id from IntaSend
     * @return array ['success' => bool, 'state' => string, 'amount' => float, 'error' => string]
     */
    public static function verifyPayment(string $trackingId): array
    {
        if (empty($trackingId)) {
            return ['success' => false, 'state' => '', 'amount' => 0, 'error' => 'No tracking ID provided'];
        }

        $credentials = self::getCredentials();
        $secretKey = $credentials['token'] ?? '';

        if (empty($secretKey)) {
            return ['success' => false, 'state' => '', 'amount' => 0, 'error' => 'IntaSend secret key not configured'];
        }

        $testMode = $credentials['test'] ?? true;
        $baseUrl = $testMode
            ? 'https://sandbox.intasend.com/api/v1/checkout/'
            : 'https://payment.intasend.com/api/v1/checkout/';

        $url = $baseUrl . '?invoice_id=' . urlencode($trackingId);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Authorization: Bearer ' . $secretKey,
                'X-IntaSend-Public-Key: ' . $credentials['publishable_key'],
            ],
        ]);

        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($resp === false) {
            return ['success' => false, 'state' => '', 'amount' => 0, 'error' => 'Connection failed: ' . $curlError];
        }

        $data = json_decode($resp, true);

        // Check if payment is complete
        $state = $data['state'] ?? $data['status'] ?? '';
        if (in_array(strtolower($state), ['completed', 'paid', 'success'])) {
            return [
                'success' => true,
                'state'   => $state,
                'amount'  => (float)($data['amount'] ?? 0),
                'error'   => '',
            ];
        }

        return [
            'success' => false,
            'state'   => $state,
            'amount'  => (float)($data['amount'] ?? 0),
            'error'   => 'Payment not completed. State: ' . $state,
        ];
    }

    /**
     * Test the IntaSend API connection.
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public static function testConnection(): array
    {
        $credentials = self::getCredentials();

        if (empty($credentials['publishable_key'])) {
            return ['success' => false, 'message' => 'Publishable key is required'];
        }
        if (empty($credentials['token'])) {
            return ['success' => false, 'message' => 'Secret key is required'];
        }

        // Try making a small checkout request to verify credentials
        // We use the collection endpoint which is lighter
        $testMode = $credentials['test'] ?? true;
        $baseUrl = $testMode
            ? 'https://sandbox.intasend.com'
            : 'https://payment.intasend.com';

        $ch = curl_init($baseUrl . '/api/v1/checkout/');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                'amount'       => 1,
                'currency'     => 'KES',
                'email'        => 'test@intasend.com',
                'redirect_url' => self::getSiteUrl() . '/',
                'api_ref'      => 'test-' . time(),
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $credentials['token'],
                'X-IntaSend-Public-Key: ' . $credentials['publishable_key'],
            ],
        ]);

        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($resp === false) {
            return ['success' => false, 'message' => 'Connection failed: ' . $curlError];
        }

        $data = json_decode($resp, true);
        $env = $testMode ? 'Sandbox' : 'Live';

        // HTTP 200-201 with a URL means auth works
        if ($httpCode >= 200 && $httpCode < 300 && isset($data['url'])) {
            return ['success' => true, 'message' => "Connection successful! ({$env}) Authentication verified. Checkout API responded with HTTP {$httpCode}."];
        }

        // HTTP 401/403 = auth issue
        if ($httpCode === 401 || $httpCode === 403) {
            return ['success' => false, 'message' => "Authentication failed (HTTP {$httpCode}). Check that both your Publishable Key and Secret Key are correct and match the {$env} environment."];
        }

        // Other errors
        $errMsg = '';
        if (isset($data['type']) && isset($data['errors'])) {
            $errors = is_array($data['errors']) ? $data['errors'] : [];
            $errMsg = $errors[0]['detail'] ?? $errors[0]['code'] ?? json_encode($errors);
        }
        if (empty($errMsg)) {
            $errMsg = $data['message'] ?? "HTTP {$httpCode}: " . substr($resp, 0, 200);
        }

        // If we got any 2xx response, credentials are valid even if checkout failed for other reasons
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'message' => "Connection successful! ({$env}) API responded HTTP {$httpCode}. Note: {$errMsg}"];
        }

        return ['success' => false, 'message' => "Error (HTTP {$httpCode}): {$errMsg}"];
    }

    /**
     * Get the site URL from .env or settings.
     */
    private static function getSiteUrl(): string
    {
        $url = env('APP_URL', '');
        if (!empty($url)) return rtrim($url, '/');

        try {
            $row = Database::selectOne("SELECT value FROM settings WHERE `key` = ?", ['site_url']);
            if ($row && !empty($row['value'])) return rtrim($row['value'], '/');
        } catch (\Throwable $e) {}

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    }

    /**
     * Log API activity for debugging.
     */
    private static function log(string $event, array $data = []): void
    {
        try {
            $logDir = ROOT_PATH . '/logs';
            if (!is_dir($logDir)) mkdir($logDir, 0755, true);
            $logEntry = date('Y-m-d H:i:s') . " [IntaSend][{$event}] " . json_encode($data, JSON_UNESCAPED_SLASHES) . "\n";
            file_put_contents($logDir . '/intasend.log', $logEntry, FILE_APPEND);
        } catch (\Throwable $e) {
            // Silent fail for logging
        }
    }
}