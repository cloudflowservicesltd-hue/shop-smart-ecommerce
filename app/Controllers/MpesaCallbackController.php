<?php

/**
 * M-Pesa Callback Controller
 *
 * Handles the main M-Pesa callback URL that receives STK push results
 * from both CloudOne Pesa and Safaricom Daraja.
 */
class MpesaCallbackController extends BaseController
{
    /**
     * Main callback handler.
     * Called from public/api/mpesa/callback.php.
     */
    public function handle(): void
    {
        header('Content-Type: application/json');

        $logFile = ROOT_PATH . '/storage/logs/mpesa_callback.log';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);

        $input = json_decode(file_get_contents('php://input'), true);

        // Log raw callback
        @file_put_contents($logFile, date('Y-m-d H:i:s') . ' RAW: ' . json_encode($input) . "\n", FILE_APPEND);

        // Detect format: CloudOne vs Safaricom Daraja
        $checkoutRequestId = '';
        $resultCode = null;
        $resultDesc = '';
        $mpesaReceipt = '';
        $phoneNumber = '';
        $amount = 0;
        $reference = '';

        if (isset($input['data']['checkout_request_id'])) {
            // CloudOne Pesa format
            $cloudData = $input['data'];
            $checkoutRequestId = $cloudData['checkout_request_id'] ?? '';
            $status = strtolower($cloudData['status'] ?? 'pending');
            $reference = $cloudData['reference'] ?? '';
            $mpesaReceipt = $cloudData['mpesa_receipt_number'] ?? ($cloudData['transaction_code'] ?? ($cloudData['receipt_number'] ?? ''));
            $phoneNumber = $cloudData['phone'] ?? ($cloudData['phone_number'] ?? '');
            $amount = (float)($cloudData['amount'] ?? 0);
            $resultDesc = $cloudData['description'] ?? ($cloudData['result_desc'] ?? $status);

            if ($status === 'success' || $status === 'completed') { $resultCode = 0; }
            elseif ($status === 'failed' || $status === 'cancelled') { $resultCode = 1; }
            else { $resultCode = -1; }

            @file_put_contents($logFile, date('Y-m-d H:i:s') . ' CLOUDONE: id=' . $checkoutRequestId . ' status=' . $status . ' receipt=' . $mpesaReceipt . "\n", FILE_APPEND);
        } else {
            // Safaricom Daraja format
            $body = $input['Body'] ?? [];
            $stkCallback = $body['stkCallback'] ?? [];
            $checkoutRequestId = $stkCallback['CheckoutRequestID'] ?? '';
            $resultCode = $stkCallback['ResultCode'] ?? '';
            $resultDesc = $stkCallback['ResultDesc'] ?? '';

            if ((int)$resultCode === 0) {
                $callbackMetadata = $stkCallback['CallbackMetadata']['Item'] ?? [];
                foreach ($callbackMetadata as $item) {
                    $name = $item['Name'] ?? '';
                    if ($name === 'MpesaReceiptNumber') $mpesaReceipt = $item['Value'] ?? '';
                    if ($name === 'PhoneNumber') $phoneNumber = $item['Value'] ?? '';
                    if ($name === 'Amount') $amount = (float)($item['Value'] ?? 0);
                }
            }

            @file_put_contents($logFile, date('Y-m-d H:i:s') . ' DARAJA: id=' . $checkoutRequestId . ' code=' . $resultCode . ' receipt=' . $mpesaReceipt . "\n", FILE_APPEND);
        }

        if (empty($checkoutRequestId)) {
            @file_put_contents($logFile, date('Y-m-d H:i:s') . ' ERROR: Missing checkout_request_id. Input: ' . json_encode($input) . "\n", FILE_APPEND);
            http_response_code(400);
            echo json_encode(['error' => 'Missing checkout_request_id']);
            exit;
        }

        // Store result in session for POS status polling
        $sessionKey = 'mpesa_stk_' . $checkoutRequestId;
        $existing = Session::get($sessionKey) ?? [];
        Session::set($sessionKey, array_merge($existing, [
            'result_code' => $resultCode, 'result_desc' => $resultDesc,
            'mpesa_receipt' => $mpesaReceipt, 'phone' => $phoneNumber ?: ($existing['phone'] ?? ''),
            'amount' => $amount ?: ($existing['amount'] ?? 0), 'processed_at' => time(),
        ]));

        // Update any pending order that matches
        if ((int)$resultCode === 0 && !empty($mpesaReceipt)) {
            try {
                $orderRef = $existing['orderRef'] ?? $reference;
                if (!empty($orderRef)) {
                    $order = Database::selectOne("SELECT id FROM orders WHERE order_number = ? AND payment_status = 'pending'", [$orderRef]);
                    if ($order) {
                        Database::update('orders', [
                            'payment_status' => 'paid', 'payment_reference' => $mpesaReceipt, 'updated_at' => date('Y-m-d H:i:s'),
                        ], 'id = ?', [$order['id']]);

                        $orderData = Database::selectOne("SELECT total FROM orders WHERE id = ?", [$order['id']]);
                        try {
                            Database::insert('transactions', [
                                'order_id' => $order['id'], 'payment_method' => 'mpesa',
                                'amount' => $orderData['total'] ?? 0, 'reference' => $mpesaReceipt,
                                'status' => 'completed', 'created_at' => date('Y-m-d H:i:s'),
                            ]);
                        } catch (\Throwable $e) {
                            @file_put_contents($logFile, date('Y-m-d H:i:s') . ' TXN Error: ' . $e->getMessage() . "\n", FILE_APPEND);
                        }

                        @file_put_contents($logFile, date('Y-m-d H:i:s') . ' ORDER_UPDATED: id=' . $order['id'] . ' ref=' . $mpesaReceipt . "\n", FILE_APPEND);
                    }
                }
            } catch (\Throwable $e) {
                @file_put_contents($logFile, date('Y-m-d H:i:s') . ' DB Error: ' . $e->getMessage() . "\n", FILE_APPEND);
            }
        }

        http_response_code(200);
        echo json_encode(['status' => 'accepted']);
    }
}