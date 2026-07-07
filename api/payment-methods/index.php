<?php
ini_set('display_errors', '0');
header('Content-Type: application/json');

try {
    if (!defined('ROOT_PATH')) {
        require_once dirname(dirname(__DIR__)) . '/app/bootstrap.php';
    }

    if (!Auth::check() || !Auth::isAdmin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit;
    }

    $method = $_SERVER['REQUEST_METHOD'];
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = str_replace('/api/payment-methods', '', $uri);

    // GET /api/payment-methods — List all custom payment methods
    if ($uri === '' && $method === 'GET') {
        $methods = Database::select("SELECT * FROM custom_payment_methods ORDER BY sort_order ASC, id ASC");
        echo json_encode(['success' => true, 'methods' => $methods]);
        exit;
    }

    // POST /api/payment-methods — Create a new payment method
    if ($uri === '' && $method === 'POST') {
        $name = trim($_POST['name'] ?? '');
        $icon = trim($_POST['icon'] ?? 'credit-card');
        $color = trim($_POST['color'] ?? 'gray');

        if (empty($name)) {
            http_response_code(400);
            echo json_encode(['error' => 'Name is required']);
            exit;
        }

        $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name));
        $slug = trim($slug, '-');
        if (empty($slug)) $slug = 'method-' . time();

        // Check slug uniqueness
        $exists = Database::selectOne("SELECT id FROM custom_payment_methods WHERE slug = ?", [$slug]);
        if ($exists) {
            $slug .= '-' . time();
        }

        $maxSort = Database::selectOne("SELECT COALESCE(MAX(sort_order), 0) + 1 as next FROM custom_payment_methods")['next'] ?? 1;

        Database::insert('custom_payment_methods', [
            'name' => $name,
            'slug' => $slug,
            'icon' => $icon,
            'color' => $color,
            'is_active' => 1,
            'sort_order' => (int)$maxSort,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        echo json_encode(['success' => true, 'message' => 'Payment method added']);
        exit;
    }

    // PUT /api/payment-methods/{id} — Update (via POST with _method)
    if (preg_match('#^/(\d+)$#', $uri, $m) && ($method === 'PUT' || $method === 'POST')) {
        $id = (int)$m[1];
        $name = trim($_POST['name'] ?? '');
        $icon = trim($_POST['icon'] ?? '');
        $color = trim($_POST['color'] ?? '');
        $isActive = $_POST['is_active'] ?? null;

        $update = ['updated_at' => date('Y-m-d H:i:s')];
        if ($name !== '') $update['name'] = $name;
        if ($icon !== '') $update['icon'] = $icon;
        if ($color !== '') $update['color'] = $color;
        if ($isActive !== null) $update['is_active'] = $isActive === '1' || $isActive === 'true' ? 1 : 0;

        Database::update('custom_payment_methods', $update, 'id = ?', [$id]);
        echo json_encode(['success' => true, 'message' => 'Updated']);
        exit;
    }

    // DELETE /api/payment-methods/{id}
    if (preg_match('#^/(\d+)$#', $uri, $m) && $method === 'DELETE') {
        Database::delete('custom_payment_methods', 'id = ?', [(int)$m[1]]);
        echo json_encode(['success' => true, 'message' => 'Deleted']);
        exit;
    }

    http_response_code(404);
    echo json_encode(['error' => 'Not found']);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}