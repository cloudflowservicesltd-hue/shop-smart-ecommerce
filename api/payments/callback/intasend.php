<?php
require_once __DIR__ . '/../../../app/bootstrap.php';
header('Content-Type: application/json');

$body = json_decode(file_get_contents('php://input'), true);

// Log
@mkdir(ROOT_PATH . '/logs', 0777, true);
file_put_contents(ROOT_PATH . '/logs/intasend_callback.log', date('Y-m-d H:i:s') . " " . json_encode($body) . "\n", FILE_APPEND);

// IntaSend callback structure typically has: {invoice: {state: 'COMPLETE'|'FAILED', ...}, tracking_id, ...}
$state = $body['invoice']['state'] ?? '';
$trackingId = $body['invoice']['tracking_id'] ?? $body['tracking_id'] ?? '';

if (strtolower($state) === 'complete') {
    // Find order by tracking_id stored in transactions reference
    $txn = Database::selectOne("SELECT * FROM transactions WHERE reference = ?", [$trackingId]);
    if ($txn) {
        Database::update('orders', ['payment_status' => 'paid', 'status' => 'processing', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$txn['order_id']]);
        Database::update('transactions', ['status' => 'completed'], 'id = ?', [$txn['id']]);
    }
}

echo json_encode(['status' => 'ok']);