<?php

class Request
{
    public static function method(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    public static function isGet(): bool
    {
        return self::method() === 'GET';
    }

    public static function isPost(): bool
    {
        return self::method() === 'POST';
    }

    public static function all(): array
    {
        return array_merge($_GET, $_POST);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $_POST[$key] ?? $default;
    }

    public static function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    public static function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public static function file(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }

    public static function has(string $key): bool
    {
        return isset($_GET[$key]) || isset($_POST[$key]);
    }

    public static function hasFile(string $key): bool
    {
        return isset($_FILES[$key]) && $_FILES[$key]['error'] !== UPLOAD_ERR_NO_FILE;
    }

    public static function header(string $key, mixed $default = null): mixed
    {
        $header = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $_SERVER[$header] ?? $default;
    }

    public static function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public static function userAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    public static function uri(): string
    {
        $uri = '/' . trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/\\');
        // Strip /public prefix if present (LiteSpeed rewrite artifact)
        $uri = preg_replace('#^/public#', '', $uri);
        return '/' . trim($uri, '/');
    }

    public static function only(array $keys): array
    {
        return array_intersect_key(self::all(), array_flip($keys));
    }

    public static function except(array $keys): array
    {
        return array_diff_key(self::all(), array_flip($keys));
    }

    public static function input(string $key, mixed $default = null): mixed
    {
        $json = json_decode(file_get_contents('php://input'), true);
        if ($json !== null) return $json[$key] ?? $default;
        return self::get($key, $default);
    }

    public static function json(): array
    {
        $json = json_decode(file_get_contents('php://input'), true);
        return is_array($json) ? $json : [];
    }
}