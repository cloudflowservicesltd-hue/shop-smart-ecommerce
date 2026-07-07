<?php
// Pesapal IPN callback
require_once __DIR__ . '/../../app/bootstrap.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$orderTrackingId = $input['order_tracking_id'] ?? $_GET['OrderTrackingId'] ?? '';
$merchantReference = $input['merchant_reference'] ?? $_GET['OrderMerchantReference'] ?? '';
$notificationType = $input['notification_type'] ?? '';

if (empty($orderTrackingId)) {
    echo json_encode(['error' => 'Missing order tracking ID']);
    exit;
}

// Get Pesapal token to verify
function getSetting($key) {
    $row = Database::selectOne("SELECT value FROM settings WHERE `key` = ?", [$key]);
    return $row ? $row['value'] : '';
}

$consumerKey = getSetting('pesapal_key') ?: '';
$consumerSecret = getSetting('pesapal_secret') ?: '';
$env = getSetting('pesapal_env') ?: 'sandbox';
$apiUrl = $env === 'production'
    ? 'https://pay.pesapal.com/v3'
    : 'https://cybqa.pesapal.com/pesapalv3';

// Get token
$ch = curl_init($apiUrl . '/api/Auth/RequestToken');
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode(['consumer_key' => $consumerKey, 'consumer_secret' => $consumerSecret]),
    CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30,
]);
$tokenData = json_decode(curl_exec($ch), true);
curl_close($ch);
$token = $tokenData['token'] ?? '';

if (empty($token)) {
    echo json_encode(['error' => 'Auth failed']);
    exit;
}

// Get transaction status
$ch = curl_init($apiUrl . '/api/Transactions/GetTransactionStatus?orderTrackingId=' . urlencode($orderTrackingId));
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30,
]);
$statusData = json_decode(curl_exec($ch), true);
curl_close($ch);

$paymentStatus = $statusData['status_code'] ?? '';
$transactionRef = $statusData['payment_method'] ?? '';

if ($paymentStatus === '1' || $paymentStatus === '2') {
    // Completed or confirmed
    $order = Database::selectOne("SELECT id, order_number FROM orders WHERE order_number = ?", [$merchantReference]);
    if ($order) {
        Database::update('orders', [
            'payment_status' => 'paid',
            'payment_reference' => $transactionRef,
            'status' => 'processing',
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$order['id']]);

        Database::insert('transactions', [
            'order_id' => $order['id'],
            'payment_method' => 'pesapal',
            'amount' => 0, // we don't have it here
            'reference' => $orderTrackingId,
            'status' => 'completed',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}

echo json_encode(['status' => 'received']);
http_response_code(200);