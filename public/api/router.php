<?php
/**
 * Shared API router entry point.
 * Each API endpoint file sets $_API_BASE_URI and requires this file.
 */
if (!isset($_API_BASE_URI)) {
    http_response_code(403);
    exit('Direct access denied');
}

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 2));
    ob_start();
    require ROOT_PATH . '/app/bootstrap.php';
    require_once ROOT_PATH . '/database/install.php';
    require ROOT_PATH . '/routes/web.php';

    $router->addMiddleware('csrf', function() {
        if (Request::isPost() && !isset($_POST['_token'])) {
            Session::flash('error', 'CSRF token missing.');
            Redirect::back();
        }
        return true;
    });
    $router->addMiddleware('auth', function() {
        if (!Auth::check()) {
            Session::flash('error', 'Please login to continue.');
            Redirect::to('/login');
        }
        return true;
    });
    $router->addMiddleware('guest', function() {
        if (Auth::check()) { Redirect::to('/'); }
        return true;
    });
    $router->addMiddleware('admin', function() {
        if (!Auth::check() || !Auth::isAdmin()) {
            Session::flash('error', 'Access denied.');
            Redirect::to('/');
        }
        return true;
    });
    $router->addMiddleware('cashier', function() {
        if (!Auth::check() || !Auth::hasRole(['admin', 'super_admin', 'cashier'])) {
            Session::flash('error', 'Access denied.');
            Redirect::to('/');
        }
        return true;
    });
    $router->addMiddleware('super_admin', function() {
        if (!Auth::check() || !Auth::isSuperAdmin()) {
            Session::flash('error', 'Super admin access required.');
            Redirect::to('/');
        }
        return true;
    });
}

$pathInfo = $_SERVER['PATH_INFO'] ?? '';
$queryString = $_SERVER['QUERY_STRING'] ?? '';
$dispatchUri = $_API_BASE_URI . $pathInfo . ($queryString ? '?' . $queryString : '');
$_SERVER['REQUEST_URI'] = $dispatchUri;

while (ob_get_level()) ob_end_clean();

try {
    $result = $router->dispatch($_SERVER['REQUEST_METHOD'], $dispatchUri);
    if ($result === null) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'API endpoint not found', 'uri' => $dispatchUri]);
        exit;
    }
} catch (\Throwable $e) {
    @file_put_contents(ROOT_PATH . '/storage/logs/api-router.log',
        date('Y-m-d H:i:s') . " | API_EXCEPTION | uri={$dispatchUri} | err={$e->getMessage()}\n",
        FILE_APPEND | LOCK_EX);
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
    exit;
}