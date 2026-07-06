<?php

/**
 * BlotatoAPI - Service class for the Blotato social media publishing API.
 *
 * Provides static methods for managing social media accounts, creating posts,
 * retrieving analytics, managing schedules, and generating visual content
 * through the Blotato platform.
 */
class BlotatoAPI
{
    /** @var string Blotato API base URL */
    private static string $baseUrl = 'https://backend.blotato.com/v2';

    /** @var int Default cURL timeout in seconds */
    private static int $timeout = 30;

    // -----------------------------------------------------------------------
    // API Key Management
    // -----------------------------------------------------------------------

    /**
     * Retrieve the stored Blotato API key from the settings table.
     *
     * @return string The API key, or empty string if not found.
     */
    public static function getApiKey(): string
    {
        $row = Database::selectOne(
            "SELECT value FROM settings WHERE `key` = ?",
            ['blotato_api_key']
        );

        return $row['value'] ?? '';
    }

    /**
     * Persist the Blotato API key to the settings table.
     *
     * Uses an upsert pattern: inserts a new row if the key does not exist,
     * otherwise updates the existing value.
     *
     * @param string $key The API key to store.
     */
    public static function setApiKey(string $key): void
    {
        $existing = Database::selectOne(
            "SELECT id FROM settings WHERE `key` = ?",
            ['blotato_api_key']
        );

        if ($existing !== null) {
            Database::update(
                'settings',
                ['value' => $key],
                '`key` = ?',
                ['blotato_api_key']
            );
        } else {
            Database::insert('settings', [
                'key'   => 'blotato_api_key',
                'value' => $key,
            ]);
        }
    }

    // -----------------------------------------------------------------------
    // Core HTTP Request
    // -----------------------------------------------------------------------

    /**
     * Execute an HTTP request against the Blotato API.
     *
     * @param string $method   HTTP method (GET, POST, PATCH, DELETE).
     * @param string $endpoint API endpoint path (e.g. "/users/me").
     * @param array  $data     Request payload for POST/PATCH or query params for GET.
     *
     * @return array Parsed JSON response as an associative array.
     *
     * @throws Exception On HTTP errors, connection failures, or invalid JSON responses.
     */
    public static function request(string $method, string $endpoint, array $data = []): array
    {
        $apiKey = self::getApiKey();
        if ($apiKey === '') {
            throw new Exception('Blotato API key is not configured.');
        }

        $url = rtrim(self::$baseUrl, '/') . '/' . ltrim($endpoint, '/');

        // Append query parameters for GET requests
        $upperMethod = strtoupper($method);
        if ($upperMethod === 'GET' && !empty($data)) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($data);
        }

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::$timeout,
            CURLOPT_CUSTOMREQUEST  => $upperMethod,
            CURLOPT_HTTPHEADER     => [
                'blotato-api-key: ' . $apiKey,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
        ]);

        // Attach JSON body for POST / PATCH / PUT
        if (in_array($upperMethod, ['POST', 'PATCH', 'PUT'], true) && !empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_SLASHES));
        }

        $responseBody = curl_exec($ch);
        $httpCode     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError    = curl_error($ch);
        curl_close($ch);

        // Handle cURL-level failures
        if ($curlError !== '') {
            throw new Exception("Blotato API request failed: {$curlError}");
        }

        // Decode the response body
        $decoded = json_decode($responseBody, true);
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Blotato API returned invalid JSON (HTTP {$httpCode}).");
        }

        // Map common HTTP error codes to friendly messages
        if ($httpCode >= 400) {
            $message = $decoded['message'] ?? $decoded['error'] ?? $responseBody ?? 'Unknown error';

            return match ($httpCode) {
                401 => throw new Exception('Invalid Blotato API key.'),
                429 => throw new Exception('Rate limit exceeded. Please wait before retrying.'),
                422 => throw new Exception("Validation error (422): {$message}"),
                default => throw new Exception("Blotato API error ({$httpCode}): {$message}"),
            };
        }

        return $decoded;
    }

    // -----------------------------------------------------------------------
    // User & Connection
    // -----------------------------------------------------------------------

    /**
     * Test the API connection by fetching the authenticated user profile.
     *
     * @return array User info returned by GET /users/me.
     * @throws Exception If the connection fails or the key is invalid.
     */
    public static function testConnection(): array
    {
        return self::request('GET', '/users/me');
    }

    // -----------------------------------------------------------------------
    // Accounts
    // -----------------------------------------------------------------------

    /**
     * List all social media accounts connected to the authenticated user.
     *
     * @param string|null $platform Optional platform name to filter results (e.g. "facebook", "twitter").
     *
     * @return array List of account objects.
     */
    public static function getAccounts(?string $platform = null): array
    {
        $params = [];
        if ($platform !== null) {
            $params['platform'] = $platform;
        }

        return self::request('GET', '/users/me/accounts', $params);
    }

    /**
     * List sub-accounts for a given account (Facebook pages, LinkedIn pages, YouTube playlists, etc.).
     *
     * @param string $accountId The parent account identifier.
     *
     * @return array List of sub-account objects.
     */
    public static function getSubaccounts(string $accountId): array
    {
        return self::request('GET', "/users/me/accounts/{$accountId}/subaccounts");
    }

    /**
     * Retrieve Pinterest boards available for a given account.
     *
     * @param string $accountId The Pinterest account identifier.
     *
     * @return array List of board objects.
     */
    public static function getPinterestBoards(string $accountId): array
    {
        return self::request('GET', '/social/pinterest/boards', [
            'accountId' => $accountId,
        ]);
    }

    // -----------------------------------------------------------------------
    // Posts
    // -----------------------------------------------------------------------

    /**
     * Create a new social media post.
     *
     * @param array $postData Post creation payload. Expected structure:
     *   - post.accountId   string  The account to publish to.
     *   - post.content      array   Content object with "text", "mediaUrls", "platform".
     *   - post.target       array   Target object with "targetType" (and optional sub-account IDs).
     *   - scheduledTime     string  (optional) ISO-8601 datetime to schedule the post.
     *   - useNextFreeSlot   bool    (optional) Auto-pick the next available scheduling slot.
     *
     * @return array The created post submission details.
     * @throws Exception On validation or API errors.
     */
    public static function createPost(array $postData): array
    {
        return self::request('POST', '/posts', $postData);
    }

    /**
     * Retrieve the current status of a post submission.
     *
     * @param string $postSubmissionId The submission ID returned by createPost().
     *
     * @return array Post status details.
     */
    public static function getPostStatus(string $postSubmissionId): array
    {
        return self::request('GET', "/posts/{$postSubmissionId}");
    }

    /**
     * List recent post submissions.
     *
     * @param int $limit Maximum number of posts to return (default 20).
     *
     * @return array Paginated list of post submissions.
     */
    public static function listPosts(int $limit = 20): array
    {
        return self::request('GET', '/posts', ['limit' => $limit]);
    }

    // -----------------------------------------------------------------------
    // Analytics
    // -----------------------------------------------------------------------

    /**
     * Retrieve top-performing posts sorted by a given metric.
     *
     * @param string      $sortBy  Metric to sort by (default 'views_count').
     * @param int         $limit   Maximum number of results (default 10).
     * @param string|null $platform Optional platform filter.
     *
     * @return array Top posts analytics data.
     */
    public static function listTopPosts(
        string $sortBy = 'views_count',
        int $limit = 10,
        ?string $platform = null
    ): array {
        $params = [
            'sortBy' => $sortBy,
            'limit'  => $limit,
        ];

        if ($platform !== null) {
            $params['platform'] = $platform;
        }

        return self::request('GET', '/analytics', $params);
    }

    /**
     * Get detailed analytics for a specific post.
     *
     * @param string $postId The post identifier.
     *
     * @return array Analytics data for the post.
     */
    public static function getPostAnalytics(string $postId): array
    {
        return self::request('GET', "/posts/{$postId}/analytics");
    }

    // -----------------------------------------------------------------------
    // Video Templates & Visuals
    // -----------------------------------------------------------------------

    /**
     * List available video/visual templates.
     *
     * @return array Collection of template objects.
     */
    public static function listTemplates(): array
    {
        return self::request('GET', '/videos/templates');
    }

    /**
     * Create a visual from a template using a text prompt.
     *
     * @param string $templateId The template to use.
     * @param string $prompt     Text prompt describing the desired visual.
     * @param bool   $render     Whether to immediately render the visual (default true).
     *
     * @return array Visual creation details, including a creation ID for polling status.
     */
    public static function createVisual(string $templateId, string $prompt, bool $render = true): array
    {
        return self::request('POST', '/videos/from-templates', [
            'templateId' => $templateId,
            'prompt'     => $prompt,
            'render'     => $render,
        ]);
    }

    /**
     * Check the status of an in-progress visual creation.
     *
     * @param string $id The visual creation ID from createVisual().
     *
     * @return array Status and result details.
     */
    public static function getVisualStatus(string $id): array
    {
        return self::request('GET', "/videos/creations/{$id}");
    }

    // -----------------------------------------------------------------------
    // Source Resolution
    // -----------------------------------------------------------------------

    /**
     * Submit a source (URL or text) for resolution/extraction.
     *
     * @param string $sourceType One of the supported source type identifiers.
     * @param string $urlOrText The URL or raw text to resolve.
     *
     * @return array Source resolution job details, including an ID for polling.
     */
    public static function extractSource(string $sourceType, string $urlOrText): array
    {
        return self::request('POST', '/source-resolutions-v3', [
            'sourceType' => $sourceType,
            'urlOrText'  => $urlOrText,
        ]);
    }

    /**
     * Check the status of a pending source resolution job.
     *
     * @param string $id The source resolution ID from extractSource().
     *
     * @return array Resolution status and extracted data.
     */
    public static function getSourceStatus(string $id): array
    {
        return self::request('GET', "/source-resolutions-v3/{$id}");
    }

    // -----------------------------------------------------------------------
    // Schedules
    // -----------------------------------------------------------------------

    /**
     * List all configured posting schedules.
     *
     * @param int $limit Maximum number of schedules to return (default 20).
     *
     * @return array Collection of schedule objects.
     */
    public static function listSchedules(int $limit = 20): array
    {
        return self::request('GET', '/schedules', ['limit' => $limit]);
    }

    /**
     * Retrieve details of a specific schedule.
     *
     * @param string $id The schedule identifier.
     *
     * @return array Schedule details.
     */
    public static function getSchedule(string $id): array
    {
        return self::request('GET', "/schedules/{$id}");
    }

    /**
     * Update an existing schedule with a partial patch.
     *
     * @param string $id    The schedule identifier.
     * @param array  $patch Associative array of fields to update.
     *
     * @return void
     * @throws Exception On validation or API errors.
     */
    public static function updateSchedule(string $id, array $patch): void
    {
        self::request('PATCH', "/schedules/{$id}", $patch);
    }

    /**
     * Delete a schedule permanently.
     *
     * @param string $id The schedule identifier.
     *
     * @return void
     * @throws Exception If the schedule cannot be deleted.
     */
    public static function deleteSchedule(string $id): void
    {
        self::request('DELETE', "/schedules/{$id}");
    }

    // -----------------------------------------------------------------------
    // Media
    // -----------------------------------------------------------------------

    /**
     * Upload media to Blotato by providing a public URL.
     *
     * @param string $url A publicly accessible URL pointing to the media file.
     *
     * @return array Uploaded media details, including the Blotato media URL.
     */
    public static function uploadMediaFromUrl(string $url): array
    {
        return self::request('POST', '/media', ['url' => $url]);
    }
}