<?php

/**
 * PublerAPI - Service class for the Publer social media publishing API.
 *
 * Provides methods to connect, fetch accounts, and publish product posts
 * through the Publer platform (https://publer.io).
 */
class PublerAPI
{
    private static string $baseUrl = 'https://publer.io/api/v1';

    private static int $timeout = 30;

    // -----------------------------------------------------------------------
    // API Key Management
    // -----------------------------------------------------------------------

    public static function getApiKey(): string
    {
        $row = Database::selectOne(
            "SELECT value FROM settings WHERE `key` = ?",
            ['publer_api_key']
        );
        return $row['value'] ?? '';
    }

    public static function setApiKey(string $key): void
    {
        $existing = Database::selectOne(
            "SELECT id FROM settings WHERE `key` = ?",
            ['publer_api_key']
        );
        if ($existing) {
            Database::update('settings', ['value' => $key], 'id = ?', [$existing['id']]);
        } else {
            Database::insert('settings', ['`key`' => 'publer_api_key', 'value' => $key]);
        }
    }

    // -----------------------------------------------------------------------
    // Core HTTP
    // -----------------------------------------------------------------------

    private static function request(string $method, string $endpoint, array $data = [], bool $jsonBody = true): array
    {
        $apiKey = self::getApiKey();
        if (empty($apiKey)) {
            throw new \RuntimeException('Publer API key is not configured');
        }

        $url = rtrim(self::$baseUrl, '/') . '/' . ltrim($endpoint, '/');
        $ch = curl_init($url);

        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Accept: application/json',
        ];

        if ($jsonBody && $method !== 'GET') {
            $headers[] = 'Content-Type: application/json';
            $body = json_encode($data);
        } else {
            $body = $jsonBody ? '' : http_build_query($data);
            if (!$jsonBody && $method !== 'GET') {
                $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            }
        }

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => $body ?: null,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::$timeout,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException('cURL error: ' . $error);
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            $msg = $decoded['error'] ?? $decoded['message'] ?? "HTTP $httpCode";
            throw new \RuntimeException($msg);
        }

        return $decoded ?: [];
    }

    // -----------------------------------------------------------------------
    // Accounts
    // -----------------------------------------------------------------------

    public static function getAccounts(): array
    {
        try {
            return self::request('GET', 'accounts');
        } catch (\Throwable $e) {
            return [];
        }
    }

    // -----------------------------------------------------------------------
    // Post Management
    // -----------------------------------------------------------------------

    /**
     * Create and publish a product post to social media via Publer.
     *
     * @param array $payload Post configuration:
     *   - text: string Post caption
     *   - media_urls: array Image URLs
     *   - platforms: array e.g. ['facebook','twitter','instagram']
     *   - account_ids: array Account IDs per platform
     *   - link: string Product URL (optional)
     *   - schedule_at: string ISO date for scheduled post (optional)
     *
     * @return array API response
     */
    public static function createPost(array $payload): array
    {
        return self::request('POST', 'posts', $payload);
    }

    /**
     * Quick publish: product data → formatted social post.
     */
    public static function publishProduct(array $product, array $platforms, array $accountIds, string $link = ''): array
    {
        $storeName = Database::selectOne("SELECT value FROM settings WHERE `key` = 'store_name'")['value'] ?? 'ShopSmart';
        $siteUrl = Database::selectOne("SELECT value FROM settings WHERE `key` = 'site_url'")['value'] ?? '';

        $price = $product['sale_price'] ?? $product['price'] ?? 0;
        $currencySymbol = Database::selectOne("SELECT value FROM settings WHERE `key` = 'currency_symbol'")['value'] ?? 'KSh';

        $text = "🔥 {$product['name']}\n\n";
        $text .= "💰 " . $currencySymbol . ' ' . number_format((float)$price, 0) . "\n";
        if (!empty($product['description'])) {
            $text .= "\n" . mb_substr(strip_tags($product['description']), 0, 200) . "\n";
        }
        $text .= "\n🛒 Shop now: " . ($link ?: $siteUrl . '/product/' . ($product['slug'] ?? $product['id']));
        $text .= "\n\n #" . str_replace(' ', '', $product['name']) . " #{$storeName} #Sale #Deals";

        $mediaUrls = [];
        if (!empty($product['image'])) {
            $mediaUrls[] = $product['image'];
        }
        if (!empty($product['images'])) {
            $images = is_array($product['images']) ? $product['images'] : json_decode($product['images'], true);
            if (is_array($images)) {
                foreach (array_slice($images, 0, 4) as $img) {
                    if (is_string($img) && !in_array($img, $mediaUrls)) {
                        $mediaUrls[] = $img;
                    }
                }
            }
        }

        return self::createPost([
            'text'        => $text,
            'media_urls'  => $mediaUrls,
            'platforms'   => $platforms,
            'account_ids' => $accountIds,
            'link'        => $link,
        ]);
    }

    // -----------------------------------------------------------------------
    // Test Connection
    // -----------------------------------------------------------------------

    public static function testConnection(): bool
    {
        try {
            $result = self::request('GET', 'accounts');
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get products from database for marketing.
     */
    public static function getProductsForMarketing(int $limit = 50): array
    {
        return Database::select(
            "SELECT p.id, p.name, p.slug, p.price, p.sale_price, p.description, p.image, p.images, p.status,
                    c.name AS category_name
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.status = 'active'
             ORDER BY p.created_at DESC
             LIMIT ?",
            [$limit]
        );
    }
}