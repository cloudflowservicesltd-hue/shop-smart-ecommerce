<?php
// M-Pesa STK Push Callback URL
// This endpoint receives callbacks from Safaricom Daraja API
require_once __DIR__ . '/../../app/bootstrap.php';

$logFile = ROOT_PATH . '/db/mpesa_callback.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) @mkdir($logDir, 0755, true);

$input = json_decode(file_get_contents('php://input'), true);

// Log the callback
@file_put_contents($logFile, date('Y-m-d H:i:s') . ' ' . json_encode($input) . "\n", FILE_APPEND);

$body = $input['Body'] ?? [];
$stkCallback = $body['stkCallback'] ?? [];
$checkoutRequestId = $stkCallback['CheckoutRequestID'] ?? '';
$merchantRequestId = $stkCallback['MerchantRequestID'] ?? '';
$resultCode = $stkCallback['ResultCode'] ?? '';
$resultDesc = $stkCallback['ResultDesc'] ?? '';

if (empty($checkoutRequestId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing CheckoutRequestID']);
    exit;
}

// Extract callback metadata
$mpesaReceipt = '';
$phoneNumber = '';
$amount = 0;

if ($resultCode === 0) {
    $callbackMetadata = $stkCallback['CallbackMetadata']['Item'] ?? [];
    foreach ($callbackMetadata as $item) {
        $name = $item['Name'] ?? '';
        if ($name === 'MpesaReceiptNumber') $mpesaReceipt = $item['Value'] ?? '';
        if ($name === 'PhoneNumber') $phoneNumber = $item['Value'] ?? '';
        if ($name === 'Amount') $amount = (float)($item['Value'] ?? 0);
    }
}

// Store result in session for polling
$sessionKey = 'mpesa_stk_' . $checkoutRequestId;
$existing = Session::get($sessionKey) ?? [];
Session::set($sessionKey, array_merge($existing, [
    'result_code' => $resultCode,
    'result_desc' => $resultDesc,
    'mpesa_receipt' => $mpesaReceipt,
    'phone' => $phoneNumber ?: ($existing['phone'] ?? ''),
    'amount' => $amount ?: ($existing['amount'] ?? 0),
    'MerchantRequestID' => $merchantRequestId,
    'processed_at' => time(),
]));

// Also try to update any pending order that matches
if ($resultCode === 0 && !empty($mpesaReceipt)) {
    try {
        // Look for a pending order with the matching reference
        $orderRef = $existing['orderRef'] ?? '';
        if (!empty($orderRef)) {
            $order = Database::selectOne("SELECT id FROM orders WHERE order_number = ? AND payment_status = 'pending'", [$orderRef]);
            if ($order) {
                Database::update('orders', [
                    'payment_status' => 'paid',
                    'payment_reference' => $mpesaReceipt,
                    'updated_at' => date('Y-m-d H:i:s'),
                ], 'id = ?', [$order['id']]);

                // Record transaction
                $orderData = Database::selectOne("SELECT total FROM orders WHERE id = ?", [$order['id']]);
                Database::insert('transactions', [
                    'order_id' => $order['id'],
                    'payment_method' => 'mpesa',
                    'amount' => $orderData['total'] ?? 0,
                    'reference' => $mpesaReceipt,
                    'status' => 'completed',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    } catch (\Throwable $e) {
        @file_put_contents($logFile, date('Y-m-d H:i:s') . ' DB Error: ' . $e->getMessage() . "\n", FILE_APPEND);
    }
}

// Always respond with 200 to acknowledge the callback
http_response_code(200);
echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);