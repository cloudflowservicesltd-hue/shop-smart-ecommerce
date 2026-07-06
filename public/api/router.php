<?php
/**
 * Shared API router entry point.
 *
 * Each API endpoint file (e.g. public/api/pos/health.php) sets
 * $_SERVER['REQUEST_URI'] to the desired route and requires this file.
 *
 * For dynamic routes like /api/pos/holds/5 the endpoint file reads
 * PATH_INFO and appends it to the base URI.
 */

// Prevent direct access — must be required from an endpoint file
if (!isset($_API_BASE_URI)) {
    http_response_code(403);
    exit('Direct access denied');
}

// Bootstrap the app (only once)
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 2));
    ob_start();
    require ROOT_PATH . '/app/bootstrap.php';
    require_once ROOT_PATH . '/database/install.php';
    require ROOT_PATH . '/routes/web.php';

    // Register middleware (same as public/index.php)
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
        if (Auth::check()) {
            Redirect::to('/');
        }
        return true;
    });
    $router->addMiddleware('admin', function() {
        if (!Auth::check() || !Auth::isAdmin()) {
            Session::flash('error', 'Access denied. Admin privileges required.');
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

// Build the final URI: base URI + any PATH_INFO from the .php file URL
// Preserve the original query string (e.g. ?search=foo&category=2)
$pathInfo = $_SERVER['PATH_INFO'] ?? '';
$queryString = $_SERVER['QUERY_STRING'] ?? '';
$dispatchUri = $_API_BASE_URI . $pathInfo . ($queryString ? '?' . $queryString : '');

// Override REQUEST_URI so the router matches the correct route
$_SERVER['REQUEST_URI'] = $dispatchUri;

// Clean any stray output from bootstrap/routes
while (ob_get_level()) ob_end_clean();

$dispatchedMethod = $_SERVER['REQUEST_METHOD'];

try {
    $result = $router->dispatch($dispatchedMethod, $dispatchUri);

    // If no route matched, return 404 JSON
    if ($result === null) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'API endpoint not found', 'uri' => $dispatchUri]);
        exit;
    }
} catch (\Throwable $e) {
    // Log the exception
    $logMsg = date('Y-m-d H:i:s') . " | API_EXCEPTION | uri={$dispatchUri} | method={$dispatchedMethod} | err={$e->getMessage()} | file={$e->getFile()}:{$e->getLine()}\n";
    @file_put_contents(ROOT_PATH . '/storage/logs/api-router.log', $logMsg, FILE_APPEND | LOCK_EX);

    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
    exit;
}