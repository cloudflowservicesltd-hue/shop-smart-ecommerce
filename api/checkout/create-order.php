<?php
require_once __DIR__ . '/../../app/bootstrap.php';
header('Content-Type: application/json');

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Please login']);
    exit;
}

$cartItems = Database::select("SELECT c.*, p.name, p.price, p.stock_quantity FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?", [Auth::id()]);
if (empty($cartItems)) { http_response_code(400); echo json_encode(['error' => 'Cart is empty']); exit; }

$shipping = Session::get('checkout_shipping', []);
if (empty($shipping)) { http_response_code(400); echo json_encode(['error' => 'No shipping info']); exit; }

$taxRate = (float)(Database::selectOne("SELECT value FROM settings WHERE `key` = 'tax_rate'")['value'] ?? 16);
$subtotal = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cartItems));
$tax = $subtotal * ($taxRate / 100);
$total = $subtotal + $tax;
$orderNum = 'ORD-' . strtoupper(date('ymd')) . '-' . str_pad(Database::count('orders') + 1, 4, '0', STR_PAD_LEFT);
$now = date('Y-m-d H:i:s');

try {
    $orderId = Database::insert('orders', [
        'order_number' => $orderNum,
        'customer_id' => Auth::id(),
        'customer_name' => $shipping['name'] ?? Auth::user()['name'],
        'customer_email' => $shipping['email'] ?? Auth::user()['email'],
        'customer_phone' => $shipping['phone'] ?? '',
        'customer_address' => ($shipping['address'] ?? '') . ', ' . ($shipping['city'] ?? ''),
        'status' => 'pending',
        'payment_method' => $shipping['payment_method'] ?? 'mpesa',
        'payment_status' => 'pending',
        'subtotal' => $subtotal,
        'tax_amount' => $tax,
        'total' => $total,
        'notes' => $shipping['notes'] ?? '',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    foreach ($cartItems as $item) {
        Database::insert('order_items', [
            'order_id' => $orderId, 'product_id' => $item['product_id'],
            'product_name' => $item['name'], 'quantity' => $item['quantity'],
            'price' => $item['price'], 'total' => $item['price'] * $item['quantity'],
            'created_at' => $now,
        ]);
        Database::update('products', ['stock_quantity' => $item['stock_quantity'] - $item['quantity'], 'updated_at' => $now], 'id = ?', [$item['product_id']]);
    }

    Database::delete('cart', 'user_id = ?', [Auth::id()]);
    Session::set('last_order_id', $orderId);
    Session::set('last_order_num', $orderNum);

    echo json_encode([
        'success' => true,
        'order_id' => $orderId,
        'order_number' => $orderNum,
        'total' => $total,
        'payment_method' => $shipping['payment_method'] ?? 'mpesa',
    ]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to create order: ' . $e->getMessage()]);
}