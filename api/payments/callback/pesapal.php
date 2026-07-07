<?php
require_once __DIR__ . '/../../../app/bootstrap.php';
header('Content-Type: application/json');

$body = json_decode(file_get_contents('php://input'), true);

// Log
@mkdir(ROOT_PATH . '/logs', 0777, true);
file_put_contents(ROOT_PATH . '/logs/pesapal_callback.log', date('Y-m-d H:i:s') . " " . json_encode($body) . "\n", FILE_APPEND);

// Pesapal sends: {order_tracking_id, order_merchant_reference, order_notification_type: 'ORDER_COMPLETED'|'ORDER_FAILED', ...}
$trackingId = $body['order_tracking_id'] ?? '';
$merchantRef = $body['order_merchant_reference'] ?? '';
$status = $body['order_notification_type'] ?? '';

if ($status === 'ORDER_COMPLETED' && $merchantRef) {
    $order = Database::selectOne("SELECT * FROM orders WHERE order_number = ?", [$merchantRef]);
    if ($order && $order['payment_status'] !== 'paid') {
        Database::update('orders', ['payment_status' => 'paid', 'status' => 'processing', 'payment_reference' => $trackingId, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$order['id']]);
        Database::insert('transactions', ['order_id' => $order['id'], 'payment_method' => 'pesapal', 'amount' => $order['total'], 'reference' => $trackingId, 'status' => 'completed', 'created_at' => date('Y-m-d H:i:s')]);
    }
}

echo json_encode(['status' => 'ok']);