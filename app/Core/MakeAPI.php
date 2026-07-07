<?php

/**
 * MakeAPI - Service class for Make.com (formerly Integromat) webhook integration.
 *
 * Allows the store to send event data (orders, products, etc.) to Make.com
 * webhooks so admins can build automation workflows.
 *
 * Make.com uses webhook URLs — no API key needed for outgoing webhooks.
 * This class also supports Make's API for advanced use (with an API key).
 */
class MakeAPI
{
    private static int $timeout = 30;

    // -----------------------------------------------------------------------
    // Settings helpers
    // -----------------------------------------------------------------------

    public static function getWebhookUrl(): string
    {
        $row = Database::selectOne(
            "SELECT value FROM settings WHERE `key` = ?",
            ['make_webhook_url']
        );
        return trim($row['value'] ?? '');
    }

    public static function getApiKey(): string
    {
        $row = Database::selectOne(
            "SELECT value FROM settings WHERE `key` = ?",
            ['make_api_key']
        );
        return trim($row['value'] ?? '');
    }

    public static function isEventEnabled(string $event): bool
    {
        $row = Database::selectOne(
            "SELECT value FROM settings WHERE `key` = ?",
            ['make_event_' . $event]
        );
        return ($row['value'] ?? '0') === '1';
    }

    /**
     * Get all webhook events and their enabled status.
     */
    public static function getEvents(): array
    {
        $events = [
            'new_order'        => 'New Order Placed',
            'order_paid'       => 'Order Payment Received',
            'order_shipped'    => 'Order Marked as Shipped',
            'new_product'      => 'New Product Added',
            'product_updated'  => 'Product Updated',
            'new_customer'     => 'New Customer Registered',
            'low_stock'        => 'Low Stock Alert',
        ];

        $result = [];
        foreach ($events as $key => $label) {
            $row = Database::selectOne(
                "SELECT value FROM settings WHERE `key` = ?",
                ['make_event_' . $key]
            );
            $result[$key] = [
                'label'   => $label,
                'enabled' => ($row['value'] ?? '0') === '1',
            ];
        }
        return $result;
    }

    // -----------------------------------------------------------------------
    // Core HTTP
    // -----------------------------------------------------------------------

    /**
     * Send data to a Make.com webhook URL.
     */
    public static function sendWebhook(string $url, array $data, string $event = ''): array
    {
        if (empty($url)) {
            return ['success' => false, 'error' => 'Webhook URL is not configured'];
        }

        $payload = [
            'event'       => $event,
            'timestamp'   => date('c'),
            'store_name'  => Database::selectOne("SELECT value FROM settings WHERE `key` = 'store_name'")['value'] ?? 'ShopSmart',
            'data'        => $data,
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::$timeout,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => 'cURL error: ' . $error];
        }

        $decoded = json_decode($response, true);

        // Log the webhook call
        self::logWebhook($event, $url, $payload, $httpCode, $response);

        return [
            'success'   => $httpCode >= 200 && $httpCode < 300,
            'http_code' => $httpCode,
            'response'  => $decoded,
        ];
    }

    /**
     * Send event to the configured webhook URL.
     */
    public static function triggerEvent(string $event, array $data): array
    {
        if (!self::isEventEnabled($event)) {
            return ['success' => false, 'error' => "Event '$event' is disabled"];
        }

        $url = self::getWebhookUrl();
        if (empty($url)) {
            return ['success' => false, 'error' => 'Make.com webhook URL is not configured'];
        }

        return self::sendWebhook($url, $data, $event);
    }

    // -----------------------------------------------------------------------
    // Convenience: trigger events from controllers
    // -----------------------------------------------------------------------

    public static function triggerNewOrder(array $order): void
    {
        $items = Database::select(
            "SELECT oi.*, p.name as product_name, p.image as product_image
             FROM order_items oi
             LEFT JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = ?",
            [$order['id'] ?? 0]
        );
        self::triggerEvent('new_order', [
            'order'       => $order,
            'items'       => $items,
            'customer'    => Auth::user() ?: [],
            'total_items' => count($items),
        ]);
    }

    public static function triggerOrderPaid(array $order): void
    {
        self::triggerEvent('order_paid', ['order' => $order]);
    }

    public static function triggerOrderShipped(array $order): void
    {
        self::triggerEvent('order_shipped', ['order' => $order]);
    }

    public static function triggerNewProduct(array $product): void
    {
        self::triggerEvent('new_product', ['product' => $product]);
    }

    public static function triggerProductUpdated(array $product): void
    {
        self::triggerEvent('product_updated', ['product' => $product]);
    }

    public static function triggerNewCustomer(array $user): void
    {
        self::triggerEvent('new_customer', ['customer' => $user]);
    }

    public static function triggerLowStock(array $product): void
    {
        self::triggerEvent('low_stock', ['product' => $product]);
    }

    // -----------------------------------------------------------------------
    // Test Connection
    // -----------------------------------------------------------------------

    /**
     * Send a test webhook to verify the connection works.
     */
    public static function testConnection(string $url = ''): array
    {
        $webhookUrl = $url ?: self::getWebhookUrl();
        if (empty($webhookUrl)) {
            return ['success' => false, 'error' => 'No webhook URL provided'];
        }

        return self::sendWebhook($webhookUrl, [
            'test'         => true,
            'message'      => 'Connection test from ShopSmart',
            'server_time'  => date('c'),
        ], 'test');
    }

    // -----------------------------------------------------------------------
    // Make.com REST API (optional, for advanced scenarios)
    // -----------------------------------------------------------------------

    /**
     * Call Make.com REST API (requires API key).
     */
    public static function apiCall(string $method, string $endpoint, array $data = []): array
    {
        $apiKey = self::getApiKey();
        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'Make.com API key is not configured'];
        }

        $url = 'https://api.make.com/v2/' . ltrim($endpoint, '/');
        $ch = curl_init($url);

        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Accept: application/json',
        ];

        $body = null;
        if ($method !== 'GET' && !empty($data)) {
            $headers[] = 'Content-Type: application/json';
            $body = json_encode($data);
        }

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::$timeout,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => 'cURL error: ' . $error];
        }

        $decoded = json_decode($response, true);

        return [
            'success'   => $httpCode >= 200 && $httpCode < 300,
            'http_code' => $httpCode,
            'response'  => $decoded,
        ];
    }

    /**
     * List Make scenarios (requires API key).
     */
    public static function getScenarios(): array
    {
        return self::apiCall('GET', 'scenarios');
    }

    /**
     * Run a Make scenario (requires API key).
     */
    public static function runScenario(string $scenarioId): array
    {
        return self::apiCall('POST', "scenarios/{$scenarioId}/run");
    }

    // -----------------------------------------------------------------------
    // Webhook Log
    // -----------------------------------------------------------------------

    private static function logWebhook(string $event, string $url, array $payload, int $httpCode, string $response): void
    {
        try {
            Database::insert('make_webhook_log', [
                'event'      => $event,
                'url'        => $url,
                'payload'    => json_encode($payload),
                'http_code'  => $httpCode,
                'response'   => mb_substr($response, 0, 5000),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // Silently fail logging
        }
    }

    /**
     * Get recent webhook logs.
     */
    public static function getWebhookLogs(int $limit = 50): array
    {
        try {
            return Database::select(
                "SELECT * FROM make_webhook_log ORDER BY id DESC LIMIT ?",
                [$limit]
            );
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Clear old webhook logs (keep last 100).
     */
    public static function clearOldLogs(): void
    {
        try {
            Database::delete(
                "make_webhook_log",
                "id NOT IN (SELECT id FROM (SELECT id FROM make_webhook_log ORDER BY id DESC LIMIT 100) tmp)"
            );
        } catch (\Throwable $e) {
            // Silently fail
        }
    }
}