<?php

// Project Root
define('ROOT_PATH', dirname(__DIR__));

// Load Composer autoloader (guzzle, php-jwt, phpmailer, etc.)
$vendorAutoload = ROOT_PATH . '/vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
}

// Load environment variables from .env (zero-dependency loader)
require_once ROOT_PATH . '/app/Core/EnvLoader.php';
EnvLoader::load(ROOT_PATH . '/.env');

// Load configuration
$config = require ROOT_PATH . '/config/app.php';
define('APP_CONFIG', $config);

// Error reporting - always log to file, display based on debug mode
$logFile = ROOT_PATH . '/storage/logs/error.log';
error_reporting(E_ALL);
ini_set('display_errors', 0); // We handle display ourselves via ErrorHandler
ini_set('log_errors', 1);
ini_set('error_log', $logFile);

// Custom error handler: log to file AND display via styled error page (debug mode)
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) return;

    $type = match($severity) {
        E_WARNING, E_USER_WARNING => 'WARNING',
        E_NOTICE, E_USER_NOTICE => 'NOTICE',
        E_DEPRECATED, E_USER_DEPRECATED => 'DEPRECATED',
        E_STRICT => 'STRICT',
        default => 'ERROR',
    };
    $timestamp = date('Y-m-d H:i:s');
    $uri = $_SERVER['REQUEST_URI'] ?? 'cli';
    $logMsg = "[$timestamp] $type: $message in $file on line $line | URI: $uri\n";
    @file_put_contents(ROOT_PATH . '/storage/logs/error.log', $logMsg, FILE_APPEND | LOCK_EX);

    // On debug mode, display errors in output (non-fatal only — fatal handled by shutdown)
    // Fatal errors (E_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR) bubble through to the shutdown handler
    if (in_array($severity, [E_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        return false; // Let PHP's internal handler + shutdown function deal with it
    }

    // On debug mode, show a visible error banner in the output (for warnings/notices during development)
    // Skip HTML output for API/JSON requests to avoid corrupting JSON responses
    $isApiRequest = str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/')
        || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));
    if ((APP_CONFIG['debug'] ?? false) && !$isApiRequest) {
        echo "<div style=\"position:fixed;bottom:0;left:0;right:0;z-index:99999;background:#fef2f2;border-top:2px solid #ef4444;padding:10px 16px;font:13px/1.5 monospace;color:#991b1b;\"><strong>[$type]</strong> $message <span style=\"color:#6b7280;\">in $file:$line</span></div>";
    }

    return true; // Suppress default PHP error display
});

// Shutdown handler for fatal errors — renders a styled 500 page
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error === null) return;
    if (!in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR])) return;

    // Log it
    $timestamp = date('Y-m-d H:i:s');
    $uri = $_SERVER['REQUEST_URI'] ?? 'cli';
    $logMsg = "[$timestamp] FATAL {$error['type']}: {$error['message']} in {$error['file']} on line {$error['line']} | URI: $uri\n";
    @file_put_contents(ROOT_PATH . '/storage/logs/error.log', $logMsg, FILE_APPEND | LOCK_EX);

    // Render styled error page (clear any partial output first)
    if (ob_get_level()) ob_end_clean();

    // For API/JSON/AJAX requests, return JSON error instead of HTML
    $isApiRequest = str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/')
        || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));
    if ($isApiRequest) {
        if (!headers_sent()) { http_response_code(500); header('Content-Type: application/json'); }
        echo json_encode(['success' => false, 'error' => 'A server error occurred']);
        return;
    }

    if (class_exists('ErrorHandler')) {
        $details = "{$error['type']}: {$error['message']}\nFile: {$error['file']}:{$error['line']}";
        ErrorHandler::render(500, 'Server Error', "A critical error occurred. Our team has been notified.", $details, true);
    } else {
        if (!headers_sent()) http_response_code(500);
        echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>500 Error</title></head><body style='font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#fef2f2;color:#991b1b;'><div style='text-align:center;padding:2rem;'><h1 style='font-size:3rem;margin:0 0 0.5rem;'>500</h1><p style='color:#6b7280;'>Internal Server Error</p><a href='/' style='color:#d97706;'>Back to Home</a></div></body></html>";
    }
});

// Exception handler for uncaught exceptions
set_exception_handler(function(\Throwable $e) {
    $timestamp = date('Y-m-d H:i:s');
    $uri = $_SERVER['REQUEST_URI'] ?? 'cli';
    $logMsg = "[$timestamp] UNCAUGHT EXCEPTION: " . get_class($e) . ": {$e->getMessage()} in {$e->getFile()}:{$e->getLine()} | URI: $uri\n";
    $logMsg .= "  Trace: " . $e->getTraceAsString() . "\n";
    @file_put_contents(ROOT_PATH . '/storage/logs/error.log', $logMsg, FILE_APPEND | LOCK_EX);

    if (ob_get_level()) ob_end_clean();

    // For AJAX/XHR/fetch requests, return JSON error
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));
    if ($isAjax) {
        if (!headers_sent()) { http_response_code(500); header('Content-Type: application/json'); }
        echo json_encode(['error' => 'Server error']);
        return;
    }

    if (class_exists('ErrorHandler')) {
        $details = get_class($e) . ': ' . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n\n" . $e->getTraceAsString();
        ErrorHandler::render(500, 'Server Error', "Something went wrong on our end. Our team has been notified.", $details, true);
    } else {
        if (!headers_sent()) http_response_code(500);
        echo "<h1>500 - Server Error</h1><p>" . htmlspecialchars($e->getMessage()) . "</p>";
    }
});

// Timezone
date_default_timezone_set($config['timezone']);

// Autoload core classes
$coreFiles = glob(ROOT_PATH . '/app/Core/*.php');
foreach ($coreFiles as $file) {
    require_once $file;
}

// Start session
$sessionConfig = $config['session'] ?? [];
if (!empty($sessionConfig['save_path'])) {
    session_save_path($sessionConfig['save_path']);
} else {
    $sessionDir = ROOT_PATH . '/storage/sessions';
    if (!is_dir($sessionDir)) mkdir($sessionDir, 0755, true);
    session_save_path($sessionDir);
}
if (!empty($sessionConfig['name'])) {
    session_name($sessionConfig['name']);
}
ini_set('session.cookie_path', '/');
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.use_trans_sid', 0);
Session::start();

// Ensure upload directories exist
$dirs = [
    ROOT_PATH . '/public/uploads/products',
    ROOT_PATH . '/public/uploads/categories',
    ROOT_PATH . '/public/uploads/brands',
    ROOT_PATH . '/public/uploads/settings',
    ROOT_PATH . '/storage/logs',
    ROOT_PATH . '/storage/cache',
    ROOT_PATH . '/storage/sessions',
];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) mkdir($dir, 0755, true);
}

// Global router instance
$router = new Router();