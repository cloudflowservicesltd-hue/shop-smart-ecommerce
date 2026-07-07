<?php
require_once __DIR__ . '/../../../app/bootstrap.php';
header('Content-Type: application/json');

// Read the callback JSON body
$body = json_decode(file_get_contents('php://input'), true);

// Log the callback for debugging
file_put_contents(ROOT_PATH . '/logs/mpesa_callback.log', date('Y-m-d H:i:s') . " " . json_encode($body) . "\n", FILE_APPEND);

// STK Push callback structure: {Body: {stkCallback: {MerchantRequestID, CheckoutRequestID, ResultCode, ResultDesc, CallbackMetadata: {Item: [...]}}}}
if (isset($body['Body']['stkCallback'])) {
    $stk = $body['Body']['stkCallback'];
    $checkoutId = $stk['CheckoutRequestID'] ?? '';
    $resultCode = $stk['ResultCode'] ?? '';

    if ($resultCode == 0 && isset($stk['CallbackMetadata']['Item'])) {
        // Extract M-Pesa receipt number
        $items = $stk['CallbackMetadata']['Item'];
        $mpesaReceipt = '';
        $amount = 0;
        $phone = '';
        foreach ($items as $item) {
            if (($item['Name'] ?? '') === 'MpesaReceiptNumber') $mpesaReceipt = $item['Value'] ?? '';
            if (($item['Name'] ?? '') === 'Amount') $amount = $item['Value'] ?? 0;
            if (($item['Name'] ?? '') === 'PhoneNumber') $phone = $item['Value'] ?? '';
        }

        // Find the order associated with this checkout request
        // The session-based lookup may not work in callback, so we also try to match by amount+recent pending order
        // For now, just log and return success
        file_put_contents(ROOT_PATH . '/logs/mpesa_callback.log', date('Y-m-d H:i:s') . " SUCCESS: Receipt=$mpesaReceipt Amount=$amount Phone=$phone\n", FILE_APPEND);
    }
}

// Always return 200 to acknowledge
echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);