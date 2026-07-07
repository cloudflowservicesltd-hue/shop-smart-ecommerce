<?php
// Stripe success callback
require_once __DIR__ . '/../../app/bootstrap.php';

$sessionId = $_GET['session_id'] ?? '';
$orderId = (int)($_GET['order_id'] ?? 0);

if (empty($sessionId) || empty($orderId)) {
    Session::flash('error', 'Invalid payment callback');
    Redirect::to('/checkout/payment');
    exit;
}

$secretKey = Database::selectOne("SELECT value FROM settings WHERE `key` = 'stripe_secret'");
$secretKey = $secretKey ? $secretKey['value'] : '';

if (empty($secretKey)) {
    Session::flash('error', 'Stripe is not configured');
    Redirect::to('/checkout/payment');
    exit;
}

// Verify the session
$ch = curl_init('https://api.stripe.com/v1/checkout/sessions/' . $sessionId . '?expand[]=payment_intent');
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $secretKey],
    CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30,
]);
$sessionData = json_decode(curl_exec($ch), true);
curl_close($ch);

$paymentIntent = $sessionData['payment_intent'] ?? [];

if (($paymentIntent['status'] ?? '') === 'succeeded') {
    // Mark order as paid
    Database::update('orders', [
        'payment_status' => 'paid',
        'payment_reference' => $paymentIntent['id'] ?? $sessionId,
        'status' => 'processing',
        'updated_at' => date('Y-m-d H:i:s'),
    ], 'id = ? AND customer_id = ?', [$orderId, Auth::id()]);

    // Record transaction
    $order = Database::selectOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
    if ($order) {
        Database::insert('transactions', [
            'order_id' => $orderId,
            'payment_method' => 'stripe',
            'amount' => $order['total'],
            'reference' => $paymentIntent['id'] ?? $sessionId,
            'status' => 'completed',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    Session::set('last_order_id', $orderId);
    Session::set('last_order_num', $order['order_number'] ?? '');
    Redirect::to('/order-success');
} else {
    Session::flash('error', 'Payment was not completed. Please try again.');
    Redirect::to('/checkout/payment');
}