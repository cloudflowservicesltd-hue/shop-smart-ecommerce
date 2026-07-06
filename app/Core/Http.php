<?php
/**
 * HTTP helper using file_get_contents (curl extension not available in this env).
 */

function http_get(string $url, array $headers = []): string|false {
    $ctx = stream_context_create(['http' => [
        'method' => 'GET',
        'header' => array_map(fn($k, $v) => "$k: $v", array_keys($headers), $headers),
        'timeout' => 30,
        'ignore_errors' => true,
    ]]);
    return file_get_contents($url, false, $ctx);
}

function http_post(string $url, string $body, array $headers = []): string|false {
    $headers[] = 'Content-Type: application/json';
    $defaultHeaders = [];
    foreach ($headers as $h) {
        $parts = explode(':', $h, 2);
        if (count($parts) === 2) {
            $defaultHeaders[] = trim($parts[0]) . ': ' . trim($parts[1]);
        }
    }
    $ctx = stream_context_create(['http' => [
        'method' => 'POST',
        'header' => $defaultHeaders,
        'content' => $body,
        'timeout' => 30,
        'ignore_errors' => true,
    ]]);
    return file_get_contents($url, false, $ctx);
}

function http_post_form(string $url, string $body, array $headers = [], string $userpwd = ''): string|false {
    $allHeaders = array_merge(['Content-Type: application/x-www-form-urlencoded'], $headers);
    $ctx = stream_context_create(['http' => [
        'method' => 'POST',
        'header' => $allHeaders,
        'content' => $body,
        'timeout' => 30,
        'ignore_errors' => true,
    ]]);
    // For basic auth with user:pass, append to URL
    if ($userpwd) {
        $url = str_replace('https://', "https://{$userpwd}@", $url);
    }
    return file_get_contents($url, false, $ctx);
}