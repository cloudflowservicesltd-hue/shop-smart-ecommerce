<?php
// Webhook endpoints for payment gateways
header('Content-Type: application/json');
require_once dirname(dirname(__DIR__)) . '/app/bootstrap.php';

$uri = str_replace('/api/webhooks', '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if ($uri === '/mpesa' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    // Log the callback
    file_put_contents(ROOT_PATH . '/storage/logs/mpesa_callback.log', date('Y-m-d H:i:s') . " - " . json_encode($data) . "\n", FILE_APPEND);
    echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    exit;
}

if ($uri === '/stripe' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = file_get_contents('php://input');
    file_put_contents(ROOT_PATH . '/storage/logs/stripe_callback.log', date('Y-m-d H:i:s') . " - " . $payload . "\n", FILE_APPEND);
    http_response_code(200);
    echo json_encode(['status' => 'ok']);
    exit;
}

if ($uri === '/intasend' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    file_put_contents(ROOT_PATH . '/storage/logs/intasend_callback.log', date('Y-m-d H:i:s') . " - " . json_encode($data) . "\n", FILE_APPEND);
    echo json_encode(['status' => 'ok']);
    exit;
}

echo json_encode(['error' => 'Webhook not found']);
http_response_code(404);