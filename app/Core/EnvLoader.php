<?php

/**
 * Simple .env file loader — no dependencies required.
 * Parses .env and populates $_ENV, $_SERVER, and putenv().
 */
class EnvLoader
{
    /**
     * Load a .env file into the environment.
     */
    public static function load(string $path): void
    {
        if (!file_exists($path) || !is_readable($path)) {
            error_log("[EnvLoader] .env file not found or not readable: {$path}");
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Skip comments
            if (str_starts_with(trim($line), '#')) {
                continue;
            }

            // Skip lines without an = sign
            if (!str_contains($line, '=')) {
                continue;
            }

            // Split on first = only (value may contain =)
            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value);

            // Remove surrounding quotes
            if (preg_match('/^["\'](.*)["\']\s*$/', $value, $matches)) {
                $value = $matches[1];
            }

            // Set in all relevant superglobals
            putenv("{$key}={$value}");
            $_ENV[$key]  = $value;
            $_SERVER[$key] = $value;
        }
    }
}