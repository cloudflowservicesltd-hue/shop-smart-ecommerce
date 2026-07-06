<?php

/**
 * Multi-provider AI Chat Service
 * Supports: OpenAI (ChatGPT), Anthropic (Claude), DeepSeek, MiniMax
 */
class AIChat
{
    private static ?string $lastError = null;
    private static ?string $lastProvider = null;

    /**
     * Send a chat message to the configured AI provider.
     * Returns ['success' => bool, 'content' => string, 'provider' => string, 'usage' => array].
     */
    public static function chat(array $messages, ?string $provider = null, ?string $systemPrompt = null): array
    {
        self::$lastError = null;
        self::$lastProvider = null;

        $config = self::getProviderConfig($provider);
        if (!$config) {
            return ['success' => false, 'content' => '', 'provider' => $provider, 'error' => 'No AI provider configured. Please set up API keys in Marketing > AI Settings.'];
        }

        $providerName = $config['provider'];
        self::$lastProvider = $providerName;

        // Prepend system prompt if provided
        if ($systemPrompt) {
            array_unshift($messages, ['role' => 'system', 'content' => $systemPrompt]);
        }

        switch ($providerName) {
            case 'openai':
                return self::callOpenAI($messages, $config);
            case 'anthropic':
                return self::callAnthropic($messages, $config);
            case 'deepseek':
                return self::callDeepSeek($messages, $config);
            case 'minimax':
                return self::callMiniMax($messages, $config);
            default:
                return ['success' => false, 'content' => '', 'provider' => $providerName, 'error' => "Unknown provider: $providerName"];
        }
    }

    /**
     * Quick text completion (shorthand).
     */
    public static function complete(string $prompt, ?string $provider = null, ?string $systemPrompt = null): string
    {
        $result = self::chat([['role' => 'user', 'content' => $prompt]], $provider, $systemPrompt);
        return $result['success'] ? $result['content'] : 'Error: ' . ($result['error'] ?? 'Unknown');
    }

    /**
     * Get the resolved provider config from database settings.
     */
    public static function getProviderConfig(?string $provider = null): ?array
    {
        $activeProvider = $provider ?: self::getSetting('ai_active_provider', '');

        if (empty($activeProvider)) {
            // Try to find any configured provider
            foreach (['openai', 'anthropic', 'deepseek', 'minimax'] as $p) {
                $key = self::getSetting("ai_{$p}_api_key", '');
                if (!empty($key)) {
                    $activeProvider = $p;
                    break;
                }
            }
        }

        if (empty($activeProvider)) return null;

        $apiKey = self::getSetting("ai_{$activeProvider}_api_key", '');
        if (empty($apiKey)) return null;

        $model = self::getSetting("ai_{$activeProvider}_model", self::defaultModel($activeProvider));

        return [
            'provider' => $activeProvider,
            'api_key'  => $apiKey,
            'model'    => $model,
        ];
    }

    /**
     * Get all provider configs (for admin settings display).
     */
    public static function getAllProviders(): array
    {
        return [
            'openai' => [
                'name'    => 'OpenAI (ChatGPT)',
                'icon'    => '✨',
                'color'   => 'emerald',
                'api_key' => self::getSetting('ai_openai_api_key', ''),
                'model'   => self::getSetting('ai_openai_model', 'gpt-4o-mini'),
                'models'  => ['gpt-4o', 'gpt-4o-mini', 'gpt-4-turbo', 'gpt-3.5-turbo'],
                'docs'    => 'https://platform.openai.com/api-keys',
            ],
            'anthropic' => [
                'name'    => 'Anthropic (Claude)',
                'icon'    => '🧠',
                'color'   => 'orange',
                'api_key' => self::getSetting('ai_anthropic_api_key', ''),
                'model'   => self::getSetting('ai_anthropic_model', 'claude-sonnet-4-20250514'),
                'models'  => ['claude-sonnet-4-20250514', 'claude-3-5-sonnet-20241022', 'claude-3-5-haiku-20241022', 'claude-3-opus-20240229'],
                'docs'    => 'https://console.anthropic.com/settings/keys',
            ],
            'deepseek' => [
                'name'    => 'DeepSeek',
                'icon'    => '🔵',
                'color'   => 'blue',
                'api_key' => self::getSetting('ai_deepseek_api_key', ''),
                'model'   => self::getSetting('ai_deepseek_model', 'deepseek-chat'),
                'models'  => ['deepseek-chat', 'deepseek-reasoner'],
                'docs'    => 'https://platform.deepseek.com/api_keys',
            ],
            'minimax' => [
                'name'    => 'MiniMax',
                'icon'    => '⚡',
                'color'   => 'purple',
                'api_key' => self::getSetting('ai_minimax_api_key', ''),
                'model'   => self::getSetting('ai_minimax_model', 'MiniMax-Text-01'),
                'models'  => ['MiniMax-Text-01', 'abab6.5-chat'],
                'docs'    => 'https://www.minimaxi.com/en/platform/apikey',
            ],
        ];
    }

    /**
     * Save AI provider settings.
     */
    public static function saveSettings(array $data): void
    {
        $fields = [
            'ai_active_provider',
            'ai_openai_api_key', 'ai_openai_model',
            'ai_anthropic_api_key', 'ai_anthropic_model',
            'ai_deepseek_api_key', 'ai_deepseek_model',
            'ai_minimax_api_key', 'ai_minimax_model',
        ];

        foreach ($fields as $key) {
            if (!array_key_exists($key, $data)) continue;
            $val = $data[$key];
            $existing = Database::selectOne("SELECT id FROM settings WHERE `key` = ?", [$key]);
            if ($existing) {
                Database::update('settings', ['value' => $val, 'updated_at' => date('Y-m-d H:i:s')], '`key` = ?', [$key]);
            } else {
                Database::insert('settings', [
                    'key' => $key, 'value' => $val, 'group_name' => 'ai',
                    'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    public static function getLastError(): ?string { return self::$lastError; }
    public static function getLastProvider(): ?string { return self::$lastProvider; }

    // ──────────────────────────────────────────────
    //  Provider API Implementations
    // ──────────────────────────────────────────────

    private static function callOpenAI(array $messages, array $config): array
    {
        $payload = json_encode([
            'model'       => $config['model'],
            'messages'    => $messages,
            'max_tokens'  => 4096,
            'temperature' => 0.7,
        ]);

        $result = self::curlPost('https://api.openai.com/v1/chat/completions', $payload, [
            'Authorization: Bearer ' . $config['api_key'],
            'Content-Type: application/json',
        ]);

        if (!$result['success']) {
            self::$lastError = $result['error'];
            return ['success' => false, 'content' => '', 'provider' => 'openai', 'error' => 'Network error: ' . $result['error']];
        }

        $data = json_decode($result['response'], true);
        if (!$data || isset($data['error'])) {
            $errMsg = $data['error']['message'] ?? json_encode($data);
            self::$lastError = $errMsg;
            return ['success' => false, 'content' => '', 'provider' => 'openai', 'error' => $errMsg];
        }

        $content = $data['choices'][0]['message']['content'] ?? '';
        $usage = $data['usage'] ?? [];
        return [
            'success'  => true,
            'content'  => $content,
            'provider' => 'openai',
            'model'    => $config['model'],
            'usage'    => $usage,
        ];
    }

    private static function callAnthropic(array $messages, array $config): array
    {
        // Anthropic: system prompt is separate from messages
        $systemPrompt = '';
        $anthropicMessages = [];
        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $systemPrompt = $msg['content'];
            } else {
                $anthropicMessages[] = $msg;
            }
        }

        $payload = json_encode([
            'model'       => $config['model'],
            'max_tokens'  => 4096,
            'system'      => $systemPrompt ?: 'You are a helpful marketing assistant.',
            'messages'    => $anthropicMessages,
        ]);

        $result = self::curlPost('https://api.anthropic.com/v1/messages', $payload, [
            'x-api-key: ' . $config['api_key'],
            'Content-Type: application/json',
            'anthropic-version: 2023-06-01',
        ]);

        if (!$result['success']) {
            self::$lastError = $result['error'];
            return ['success' => false, 'content' => '', 'provider' => 'anthropic', 'error' => 'Network error: ' . $result['error']];
        }

        $data = json_decode($result['response'], true);
        if (!$data || isset($data['error'])) {
            $errMsg = $data['error']['message'] ?? json_encode($data);
            self::$lastError = $errMsg;
            return ['success' => false, 'content' => '', 'provider' => 'anthropic', 'error' => $errMsg];
        }

        $content = '';
        if (isset($data['content']) && is_array($data['content'])) {
            foreach ($data['content'] as $block) {
                if (($block['type'] ?? '') === 'text') {
                    $content .= $block['text'];
                }
            }
        }

        return [
            'success'  => true,
            'content'  => $content,
            'provider' => 'anthropic',
            'model'    => $config['model'],
            'usage'    => ['input_tokens' => $data['usage']['input_tokens'] ?? 0, 'output_tokens' => $data['usage']['output_tokens'] ?? 0],
        ];
    }

    private static function callDeepSeek(array $messages, array $config): array
    {
        // DeepSeek uses OpenAI-compatible API
        $payload = json_encode([
            'model'       => $config['model'],
            'messages'    => $messages,
            'max_tokens'  => 4096,
            'temperature' => 0.7,
        ]);

        $result = self::curlPost('https://api.deepseek.com/v1/chat/completions', $payload, [
            'Authorization: Bearer ' . $config['api_key'],
            'Content-Type: application/json',
        ]);

        if (!$result['success']) {
            self::$lastError = $result['error'];
            return ['success' => false, 'content' => '', 'provider' => 'deepseek', 'error' => 'Network error: ' . $result['error']];
        }

        $data = json_decode($result['response'], true);
        if (!$data || isset($data['error'])) {
            $errMsg = $data['error']['message'] ?? json_encode($data);
            self::$lastError = $errMsg;
            return ['success' => false, 'content' => '', 'provider' => 'deepseek', 'error' => $errMsg];
        }

        $content = $data['choices'][0]['message']['content'] ?? '';
        return [
            'success'  => true,
            'content'  => $content,
            'provider' => 'deepseek',
            'model'    => $config['model'],
            'usage'    => $data['usage'] ?? [],
        ];
    }

    private static function callMiniMax(array $messages, array $config): array
    {
        // MiniMax uses OpenAI-compatible API
        $payload = json_encode([
            'model'       => $config['model'],
            'messages'    => $messages,
            'max_tokens'  => 4096,
            'temperature' => 0.7,
        ]);

        $result = self::curlPost('https://api.minimax.chat/v1/text/chatcompletion_v2', $payload, [
            'Authorization: Bearer ' . $config['api_key'],
            'Content-Type: application/json',
        ]);

        if (!$result['success']) {
            self::$lastError = $result['error'];
            return ['success' => false, 'content' => '', 'provider' => 'minimax', 'error' => 'Network error: ' . $result['error']];
        }

        $data = json_decode($result['response'], true);
        if (!$data || isset($data['error'])) {
            $errMsg = $data['error']['message'] ?? json_encode($data);
            self::$lastError = $errMsg;
            return ['success' => false, 'content' => '', 'provider' => 'minimax', 'error' => $errMsg];
        }

        $content = $data['choices'][0]['message']['content'] ?? '';
        return [
            'success'  => true,
            'content'  => $content,
            'provider' => 'minimax',
            'model'    => $config['model'],
            'usage'    => $data['usage'] ?? [],
        ];
    }

    // ──────────────────────────────────────────────
    //  Helpers
    // ──────────────────────────────────────────────

    private static function curlPost(string $url, string $jsonPayload, array $headers, int $timeout = 60): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $jsonPayload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_USERAGENT      => 'ShopSmart/1.0',
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        curl_close($ch);

        if ($response === false) {
            return ['success' => false, 'response' => '', 'http_code' => 0, 'error' => "cURL error ($curlErrno): $curlError"];
        }

        return ['success' => true, 'response' => $response, 'http_code' => $httpCode, 'error' => ''];
    }

    private static function getSetting(string $key, string $default = ''): string
    {
        try {
            $row = Database::selectOne("SELECT value FROM settings WHERE `key` = ?", [$key]);
            return !empty($row['value']) ? $row['value'] : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    private static function defaultModel(string $provider): string
    {
        return match($provider) {
            'openai'    => 'gpt-4o-mini',
            'anthropic' => 'claude-sonnet-4-20250514',
            'deepseek'  => 'deepseek-chat',
            'minimax'   => 'MiniMax-Text-01',
            default     => 'gpt-4o-mini',
        };
    }
}