<?php

class ErrorHandler
{
    private static array $errorIcons = [
        400 => 'alert-circle',
        401 => 'lock',
        403 => 'shield-alert',
        404 => 'search-x',
        405 => 'ban',
        419 => 'key-round',
        429 => 'timer',
        500 => 'server-crash',
        502 => 'wifi-off',
        503 => 'wrench',
        504 => 'clock',
    ];

    private static array $errorColors = [
        400 => ['bg' => 'bg-amber-50', 'ring' => 'ring-amber-200', 'icon' => 'text-amber-500', 'btn' => 'bg-amber-600 hover:bg-amber-700', 'accent' => '#d97706'],
        401 => ['bg' => 'bg-orange-50', 'ring' => 'ring-orange-200', 'icon' => 'text-orange-500', 'btn' => 'bg-orange-600 hover:bg-orange-700', 'accent' => '#ea580c'],
        403 => ['bg' => 'bg-red-50', 'ring' => 'ring-red-200', 'icon' => 'text-red-500', 'btn' => 'bg-red-600 hover:bg-red-700', 'accent' => '#dc2626'],
        404 => ['bg' => 'bg-amber-50', 'ring' => 'ring-amber-200', 'icon' => 'text-amber-500', 'btn' => 'bg-amber-600 hover:bg-amber-700', 'accent' => '#d97706'],
        405 => ['bg' => 'bg-violet-50', 'ring' => 'ring-violet-200', 'icon' => 'text-violet-500', 'btn' => 'bg-violet-600 hover:bg-violet-700', 'accent' => '#7c3aed'],
        419 => ['bg' => 'bg-rose-50', 'ring' => 'ring-rose-200', 'icon' => 'text-rose-500', 'btn' => 'bg-rose-600 hover:bg-rose-700', 'accent' => '#e11d48'],
        429 => ['bg' => 'bg-yellow-50', 'ring' => 'ring-yellow-200', 'icon' => 'text-yellow-500', 'btn' => 'bg-yellow-600 hover:bg-yellow-700', 'accent' => '#ca8a04'],
        500 => ['bg' => 'bg-red-50', 'ring' => 'ring-red-200', 'icon' => 'text-red-500', 'btn' => 'bg-red-600 hover:bg-red-700', 'accent' => '#dc2626'],
        502 => ['bg' => 'bg-slate-50', 'ring' => 'ring-slate-200', 'icon' => 'text-slate-500', 'btn' => 'bg-slate-600 hover:bg-slate-700', 'accent' => '#475569'],
        503 => ['bg' => 'bg-orange-50', 'ring' => 'ring-orange-200', 'icon' => 'text-orange-500', 'btn' => 'bg-orange-600 hover:bg-orange-700', 'accent' => '#ea580c'],
        504 => ['bg' => 'bg-slate-50', 'ring' => 'ring-slate-200', 'icon' => 'text-slate-500', 'btn' => 'bg-slate-600 hover:bg-slate-700', 'accent' => '#475569'],
    ];

    /**
     * Render a fully self-contained styled error page.
     * No dependency on layouts, database, or any other system.
     */
    public static function render(int $code, string $title, string $message, ?string $details = null, bool $showDetails = false): void
    {
        if (!headers_sent()) {
            http_response_code($code);
        }

        $colors = self::$errorColors[$code] ?? self::$errorColors[500];
        $iconName = self::$errorIcons[$code] ?? 'alert-triangle';
        $isDebug = $showDetails && defined('APP_CONFIG') && (APP_CONFIG['debug'] ?? false);

        // Log the error
        self::log($code, $title, $message, $details);

        $debugBlock = '';
        if ($isDebug && $details) {
            $safeDetails = htmlspecialchars($details, ENT_QUOTES, 'UTF-8');
            $debugBlock = <<<HTML
            <div class="mt-8 max-w-2xl mx-auto w-full">
                <button onclick="document.getElementById('debug-info').classList.toggle('hidden'); this.querySelector('span:last-child').textContent = document.getElementById('debug-info').classList.contains('hidden') ? 'Show Details' : 'Hide Details';"
                    class="inline-flex items-center gap-2 text-sm text-gray-400 hover:text-gray-600 transition-colors cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                    <span class="first-letter:uppercase">Show Details</span>
                </button>
                <div id="debug-info" class="hidden mt-3 p-4 bg-gray-900 rounded-xl text-left overflow-x-auto">
                    <pre class="text-sm text-green-400 font-mono whitespace-pre-wrap break-words">{$safeDetails}</pre>
                </div>
            </div>
HTML;
        }

        // Build SVG icon inline (no external dependency)
        $svgIcon = self::getSvgIcon($iconName, $colors['accent']);

        echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$code} - {$title} | ShopSmart</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🛒</text></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #fefce8 0%, #fff7ed 30%, #fff1f2 60%, #faf5ff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            overflow: hidden;
            position: relative;
        }
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(circle at 20% 20%, rgba(251, 191, 36, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(244, 63, 94, 0.06) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, rgba(168, 85, 247, 0.04) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }
        .error-container {
            position: relative;
            z-index: 1;
            text-align: center;
            max-width: 520px;
            width: 100%;
            animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-12px); }
        }
        @keyframes pulse-ring {
            0% { transform: scale(1); opacity: 0.6; }
            50% { transform: scale(1.08); opacity: 0.3; }
            100% { transform: scale(1); opacity: 0.6; }
        }
        @keyframes shake {
            0%, 100% { transform: rotate(0deg); }
            10%, 30%, 50%, 70%, 90% { transform: rotate(-3deg); }
            20%, 40%, 60%, 80% { transform: rotate(3deg); }
        }
        @keyframes spin-slow {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        @keyframes bounce-subtle {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        .error-code-wrapper {
            position: relative;
            width: 200px;
            height: 200px;
            margin: 0 auto 2rem;
            animation: float 4s ease-in-out infinite;
        }
        .error-code-bg {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            background: white;
            box-shadow:
                0 20px 60px -15px rgba(0, 0, 0, 0.1),
                0 0 0 1px rgba(0, 0, 0, 0.04);
        }
        .error-code-ring {
            position: absolute;
            inset: -8px;
            border-radius: 50%;
            border: 3px dashed;
            border-color: {$colors['accent']}33;
            animation: spin-slow 25s linear infinite;
        }
        .error-code-ring-2 {
            position: absolute;
            inset: -18px;
            border-radius: 50%;
            border: 2px dotted;
            border-color: {$colors['accent']}1a;
            animation: spin-slow 40s linear infinite reverse;
        }
        .error-code-inner {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 0;
        }
        .error-code-number {
            font-family: 'Poppins', sans-serif;
            font-weight: 800;
            font-size: 4.5rem;
            line-height: 1;
            background: linear-gradient(135deg, {$colors['accent']}, {$colors['accent']}aa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .error-icon-float {
            position: absolute;
            width: 48px;
            height: 48px;
            background: white;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 24px -4px rgba(0, 0, 0, 0.1), 0 0 0 1px rgba(0, 0, 0, 0.04);
        }
        .error-icon-float.top { top: -8px; left: 50%; transform: translateX(-50%); animation: bounce-subtle 3s ease-in-out infinite; }
        .error-icon-float.right { right: -8px; top: 50%; transform: translateY(-50%); animation: bounce-subtle 3s ease-in-out infinite 0.5s; }
        .error-icon-float.bottom { bottom: -8px; left: 50%; transform: translateX(-50%); animation: bounce-subtle 3s ease-in-out infinite 1s; }
        .error-icon-float.left { left: -8px; top: 50%; transform: translateY(-50%); animation: bounce-subtle 3s ease-in-out infinite 1.5s; }
        .error-icon-float svg { width: 22px; height: 22px; }
        .error-icon-float.top svg { color: {$colors['accent']}; }
        .error-icon-float.right svg { color: #f59e0b; }
        .error-icon-float.bottom svg { color: #ec4899; }
        .error-icon-float.left svg { color: #8b5cf6; }
        .error-title {
            font-family: 'Poppins', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.5rem;
        }
        .error-message {
            color: #6b7280;
            font-size: 0.95rem;
            line-height: 1.7;
            margin-bottom: 2rem;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }
        .error-actions {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
        }
        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.85rem 2rem;
            background: {$colors['accent']};
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
            border-radius: 0.75rem;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px -2px {$colors['accent']}44;
            border: none;
            cursor: pointer;
        }
        .btn-primary:hover {
            filter: brightness(1.1);
            box-shadow: 0 6px 20px -2px {$colors['accent']}66;
            transform: translateY(-1px);
        }
        .btn-primary:active { transform: translateY(0); }
        .btn-primary svg { width: 18px; height: 18px; }
        .btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.85rem 2rem;
            background: white;
            color: #374151;
            font-weight: 500;
            font-size: 0.9rem;
            border-radius: 0.75rem;
            text-decoration: none;
            transition: all 0.2s ease;
            border: 1px solid #e5e7eb;
            cursor: pointer;
        }
        .btn-secondary:hover {
            background: #f9fafb;
            border-color: #d1d5db;
            transform: translateY(-1px);
        }
        .btn-secondary svg { width: 18px; height: 18px; }
        .error-id {
            margin-top: 2.5rem;
            font-size: 0.75rem;
            color: #9ca3af;
            font-family: 'SF Mono', 'Fira Code', monospace;
        }
        .particles {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }
        .particle {
            position: absolute;
            border-radius: 50%;
            opacity: 0.15;
            animation: float-particle linear infinite;
        }
        @keyframes float-particle {
            0% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
            10% { opacity: 0.15; }
            90% { opacity: 0.15; }
            100% { transform: translateY(-10vh) rotate(720deg); opacity: 0; }
        }
        .debug-toggle {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.8rem;
            color: #9ca3af;
            cursor: pointer;
            border: none;
            background: none;
            transition: color 0.2s;
            margin-top: 2rem;
        }
        .debug-toggle:hover { color: #6b7280; }
        .debug-toggle svg { width: 16px; height: 16px; }
        .debug-box {
            display: none;
            margin-top: 0.75rem;
            padding: 1rem;
            background: #1f2937;
            border-radius: 0.75rem;
            text-align: left;
            overflow-x: auto;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        .debug-box.visible { display: block; }
        .debug-box pre {
            color: #4ade80;
            font-size: 0.8rem;
            font-family: 'SF Mono', 'Fira Code', 'Consolas', monospace;
            white-space: pre-wrap;
            word-break: break-all;
        }
        @media (min-width: 640px) {
            .error-actions { flex-direction: row; justify-content: center; }
            .error-title { font-size: 1.75rem; }
        }
    </style>
</head>
<body>
    <!-- Floating particles -->
    <div class="particles" id="particles"></div>

    <div class="error-container">
        <div class="error-code-wrapper">
            <div class="error-code-ring-2"></div>
            <div class="error-code-ring"></div>
            <div class="error-code-bg"></div>
            <div class="error-code-inner">
                <div class="error-code-number">{$code}</div>
            </div>
            <div class="error-icon-float top">{$svgIcon}</div>
            <div class="error-icon-float right">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            </div>
            <div class="error-icon-float bottom">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
            <div class="error-icon-float left">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
            </div>
        </div>

        <h1 class="error-title">{$title}</h1>
        <p class="error-message">{$message}</p>

        <div class="error-actions">
            <a href="/" class="btn-primary">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Back to Home
            </a>
            <a href="/products" class="btn-secondary">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                Browse Products
            </a>
        </div>

        {$debugBlock}

        <div class="error-id">ref: {$code}-{$title} | " . date('Y-m-d H:i:s') . "</div>
    </div>

    <script>
        // Create floating particles
        (function() {
            const container = document.getElementById('particles');
            if (!container) return;
            const colors = ['{$colors['accent']}', '#f59e0b', '#ec4899', '#8b5cf6', '#3b82f6'];
            for (let i = 0; i < 20; i++) {
                const p = document.createElement('div');
                p.className = 'particle';
                const size = Math.random() * 6 + 3;
                p.style.cssText = 'width:' + size + 'px;height:' + size + 'px;left:' + (Math.random() * 100) + '%;background:' + colors[i % colors.length] + ';animation-duration:' + (Math.random() * 15 + 10) + 's;animation-delay:' + (Math.random() * 10) + 's;';
                container.appendChild(p);
            }
        })();
        // Debug toggle
        document.querySelectorAll('.debug-toggle').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var box = this.nextElementSibling;
                if (box) box.classList.toggle('visible');
                var span = this.querySelector('span:last-child');
                if (span) span.textContent = box && box.classList.contains('visible') ? 'Hide Details' : 'Show Details';
            });
        });
    </script>
</body>
</html>
HTML;
    }

    /**
     * Log error to file.
     */
    public static function log(int $code, string $title, string $message, ?string $details = null): void
    {
        if (!defined('ROOT_PATH')) return;
        $logDir = ROOT_PATH . '/storage/logs';
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
        $timestamp = date('Y-m-d H:i:s');
        $uri = $_SERVER['REQUEST_URI'] ?? 'cli';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $logMsg = "[$timestamp] [{$code}] {$title}: {$message} | URI: {$uri} | IP: {$ip}";
        if ($details) $logMsg .= "\n  Details: {$details}";
        $logMsg .= "\n";
        @file_put_contents($logDir . '/error.log', $logMsg, FILE_APPEND | LOCK_EX);
    }

    /**
     * Get inline SVG icon for error type.
     */
    private static function getSvgIcon(string $name, string $color): string
    {
        $icons = [
            'alert-circle' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:' . $color . '"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
            'lock' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:' . $color . '"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>',
            'shield-alert' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:' . $color . '"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/></svg>',
            'search-x' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:' . $color . '"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7"/></svg>',
            'ban' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:' . $color . '"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>',
            'key-round' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:' . $color . '"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/></svg>',
            'timer' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:' . $color . '"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
            'server-crash' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:' . $color . '"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>',
            'wifi-off' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:' . $color . '"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18M8.283 9.717a5 5 0 017.434 0M12 12l.01.01M1.394 4.394a16.1 16.1 0 0122.212 0M5.1 8.1a11.05 11.05 0 0113.8 0M16.5 14.5a4 4 0 01-5 0"/></svg>',
            'wrench' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:' . $color . '"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/></svg>',
            'clock' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:' . $color . '"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
            'alert-triangle' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:' . $color . '"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>',
        ];
        return $icons[$name] ?? $icons['alert-triangle'];
    }
}