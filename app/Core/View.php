<?php

class View
{
    private static array $sharedData = [];

    public static function share(string $key, mixed $value): void
    {
        self::$sharedData[$key] = $value;
    }

    public static function render(string $view, array $data = []): void
    {
        $data = array_merge(self::$sharedData, $data);

        // Extract variables for the view
        extract($data);

        $viewFile = ROOT_PATH . "/resources/views/{$view}.php";

        if (!file_exists($viewFile)) {
            if (class_exists('ErrorHandler')) {
                ErrorHandler::render(500, 'View Error', "The view file could not be found.", "Missing: resources/views/{$view}.php", true);
            } else {
                http_response_code(500);
                echo "<h1>View not found: {$view}</h1>";
            }
            return;
        }

        ob_start();
        include $viewFile;
        $content = ob_get_clean();
        echo $content;
    }

    public static function layout(string $layout, string $view, array $data = []): void
    {
        $data['content'] = $view;
        self::render($layout, $data);
    }

    public static function component(string $name, array $data = []): void
    {
        self::render("partials/{$name}", $data);
    }

    // Helper functions available in all views
    public static function e(mixed $value): string
    {
        if ($value === null || $value === '') return '';
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    public static function formatMoney(?float $amount): string
    {
        $config = require ROOT_PATH . '/config/app.php';
        return $config['currency_symbol'] . ' ' . number_format((float)($amount ?? 0), 2);
    }

    public static function formatDate(?string $date): string
    {
        if (!$date) return '';
        return date('M d, Y', strtotime($date));
    }

    public static function timeAgo(?string $date): string
    {
        if (!$date) return '';
        $diff = time() - strtotime($date);
        if ($diff < 60) return 'just now';
        if ($diff < 3600) return floor($diff / 60) . 'm ago';
        if ($diff < 86400) return floor($diff / 3600) . 'h ago';
        if ($diff < 604800) return floor($diff / 86400) . 'd ago';
        return date('M d, Y', strtotime($date));
    }

    public static function truncate(?string $text, int $length = 100): string
    {
        if (!$text) return '';
        return strlen($text) > $length ? substr($text, 0, $length) . '...' : $text;
    }

    public static function isActive(string $path): string
    {
        $current = '/' . trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
        if ($path === '/') return $current === '/' ? 'active' : '';
        return str_starts_with($current, $path) ? 'active' : '';
    }

    public static function asset(string $path): string
    {
        return "/assets/{$path}";
    }

    public static function csrf(): string
    {
        if (!Session::has('csrf_token')) {
            Session::set('csrf_token', bin2hex(random_bytes(32)));
        }
        return '<input type="hidden" name="_token" value="' . Session::get('csrf_token') . '">';
    }

    public static function pagination(array $paginator, string $baseUrl): string
    {
        if ($paginator['last_page'] <= 1) return '';

        $html = '<nav class="flex items-center justify-center gap-1 mt-6">';
        $html .= '<span class="text-sm text-gray-500 mr-2">Page ' . $paginator['current_page'] . ' of ' . $paginator['last_page'] . '</span>';

        for ($i = 1; $i <= $paginator['last_page']; $i++) {
            $active = $i === $paginator['current_page']
                ? 'bg-emerald-600 text-white'
                : 'bg-white text-gray-700 hover:bg-emerald-50 border border-gray-300';
            $html .= '<a href="' . $baseUrl . '?page=' . $i . '" class="px-3 py-1.5 text-sm rounded-lg ' . $active . '">' . $i . '</a>';
        }

        $html .= '</nav>';
        return $html;
    }
}

// Global helper functions
function e(mixed $value): string { return View::e($value); }
function formatMoney(?float $amount): string { return View::formatMoney($amount); }
function formatDate(?string $date): string { return View::formatDate($date); }
function asset(string $path): string { return View::asset($path); }
function csrf(): string { return View::csrf(); }
function csrf_token(): ?string { return Session::get('csrf_token'); }
function isActive(string $path): string { return View::isActive($path); }
function timeAgo(?string $date): string { return View::timeAgo($date); }
function truncate(?string $text, int $length = 100): string { return View::truncate($text, $length); }