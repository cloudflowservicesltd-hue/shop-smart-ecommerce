<?php

/**
 * Payment Controller
 *
 * Handles payment initiation and gateway callbacks for M-Pesa, Stripe,
 * IntaSend, and Pesapal.
 */
class PaymentController extends BaseController
{
    /**
     * Initiate a payment for a given order using the specified gateway.
     *
     * Supported methods: mpesa, stripe, intasend, pesapal.
     */
    public function initiate(): void
    {
        header('Content-Type: application/json');

        if (!Auth::check()) {
            $this->posJson(['error' => 'Please login'], 401);
            return;
        }

        $input = $this->posInput();
        $method = $input['method'] ?? '';
        $orderId = (int)($input['order_id'] ?? 0);
        $phone = $input['phone'] ?? '';
        $amount = (float)($input['amount'] ?? 0);
        $paymentMethodId = $input['payment_method_id'] ?? '';

        // Validate order
        $order = Database::selectOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
        if (!$order) {
            $this->posJson(['error' => 'Order not found'], 404);
            return;
        }
        if ($order['customer_id'] != Auth::id()) {
            $this->posJson(['error' => 'Access denied'], 403);
            return;
        }
        if ($order['payment_status'] === 'paid') {
            $this->posJson(['error' => 'Order already paid'], 400);
            return;
        }

        $now = date('Y-m-d H:i:s');
        $siteUrl = $this->getSiteUrl();

        try {
            // ─── M-PESA ───────────────────────────────────────────────────
            if ($method === 'mpesa') {
                $key = $this->getSetting('mpesa_consumer_key');
                $secret = $this->getSetting('mpesa_consumer_secret');
                $passkey = $this->getSetting('mpesa_passkey');
                $shortcode = $this->getSetting('mpesa_shortcode');
                $env = $this->getSetting('mpesa_env') ?: 'sandbox';

                if (!$key || !$secret || !$passkey || !$shortcode) {
                    $this->posJson(['error' => 'M-Pesa not configured. Contact admin.'], 400);
                    return;
                }

                // Normalize the phone number to 254XXXXXXXXX format
                $phone = $this->normalizePhone($phone);
                if (str_starts_with($phone, '7') || str_starts_with($phone, '1')) {
                    $phone = '254' . $phone;
                }

                $baseUrl = $env === 'production'
                    ? 'https://api.safaricom.co.ke'
                    : 'https://sandbox.safaricom.co.ke';

                // Get OAuth access token
                $tokenResp = http_get($baseUrl . '/oauth/v1/generate?grant_type=client_credentials', [
                    'Authorization' => 'Basic ' . base64_encode($key . ':' . $secret),
                ]);
                $tokenResp = $tokenResp ? json_decode($tokenResp, true) : [];
                $token = $tokenResp['access_token'] ?? '';

                if (!$token) {
                    $this->posJson(['error' => 'Failed to get M-Pesa token'], 500);
                    return;
                }

                // Build STK push request
                $timestamp = date('YmdHis');
                $password = base64_encode($shortcode . $passkey . $timestamp);
                $callbackUrl = $this->getSetting('mpesa_callback') ?: $siteUrl . '/api/payments/callback/mpesa';

                $stkBody = json_encode([
                    'BusinessShortCode' => $shortcode,
                    'Password' => $password,
                    'Timestamp' => $timestamp,
                    'TransactionType' => 'CustomerPayBillOnline',
                    'Amount' => (int)round($amount),
                    'PartyA' => $phone,
                    'PartyB' => $shortcode,
                    'PhoneNumber' => $phone,
                    'CallBackURL' => $callbackUrl,
                    'AccountReference' => $order['order_number'],
                    'TransactionDesc' => 'Order ' . $order['order_number'],
                ]);

                $stkResp = http_post($baseUrl . '/mpesa/stkpush/v1/processrequest', $stkBody, [
                    'Authorization: Bearer ' . $token,
                ]);
                $stkResp = $stkResp ? json_decode($stkResp, true) : [];

                if (isset($stkResp['CheckoutRequestID'])) {
                    Database::update('orders', [
                        'payment_method' => 'mpesa',
                        'customer_phone' => $phone,
                        'updated_at' => $now,
                    ], 'id = ?', [$orderId]);

                    Session::set('mpesa_stk_order_' . $orderId, [
                        'checkout_id' => $stkResp['CheckoutRequestID'],
                        'phone' => $phone,
                    ]);

                    $this->posJson([
                        'success' => true,
                        'checkout_request_id' => $stkResp['CheckoutRequestID'],
                    ]);
                } else {
                    $errMsg = $stkResp['errorMessage'] ?? $stkResp['ResponseDescription'] ?? 'STK push failed';
                    $this->posJson(['error' => $errMsg], 400);
                }

            // ─── STRIPE ───────────────────────────────────────────────────
            } elseif ($method === 'stripe') {
                $stripeSecret = $this->getSetting('stripe_secret');

                if (!$stripeSecret) {
                    $this->posJson(['error' => 'Stripe not configured. Contact admin.'], 400);
                    return;
                }
                if (!$paymentMethodId) {
                    $this->posJson(['error' => 'Payment method ID required'], 400);
                    return;
                }

                $params = http_build_query([
                    'amount' => (int)round($amount * 100),
                    'currency' => 'kes',
                    'payment_method' => $paymentMethodId,
                    'confirm' => 'true',
                    'metadata[order_id]' => $orderId,
                ]);

                $respBody = http_post_form('https://api.stripe.com/v1/payment_intents', $params, [], $stripeSecret);
                $resp = $respBody ? json_decode($respBody, true) : [];

                if (isset($resp['error'])) {
                    $this->posJson(['error' => $resp['error']['message'] ?? 'Stripe error'], 400);
                } elseif ($resp['status'] === 'succeeded') {
                    Database::update('orders', [
                        'payment_status' => 'paid',
                        'payment_method' => 'stripe',
                        'payment_reference' => $resp['id'] ?? '',
                        'status' => 'processing',
                        'updated_at' => $now,
                    ], 'id = ?', [$orderId]);

                    Database::insert('transactions', [
                        'order_id' => $orderId,
                        'payment_method' => 'stripe',
                        'amount' => $amount,
                        'reference' => $resp['id'] ?? '',
                        'status' => 'completed',
                        'created_at' => $now,
                    ]);

                    $this->posJson(['success' => true]);
                } elseif ($resp['status'] === 'requires_action') {
                    $this->posJson([
                        'success' => false,
                        'requires_action' => true,
                        'client_secret' => $resp['client_secret'],
                        'payment_intent_id' => $resp['id'],
                    ]);
                } else {
                    $this->posJson([
                        'success' => false,
                        'status' => $resp['status'] ?? '',
                        'client_secret' => $resp['client_secret'] ?? '',
                    ]);
                }

            // ─── INTASEND ─────────────────────────────────────────────────
            } elseif ($method === 'intasend') {
                $intakey = $this->getSetting('intasend_key');
                $intasecret = $this->getSetting('intasend_secret');

                if (!$intakey || !$intasecret) {
                    $this->posJson(['error' => 'IntaSend not configured. Contact admin.'], 400);
                    return;
                }

                $user = Auth::user();
                $nameParts = explode(' ', $user['name'] ?? '', 2);

                $body = json_encode([
                    'first_name' => $nameParts[0] ?? '',
                    'last_name' => $nameParts[1] ?? '',
                    'email' => $user['email'] ?? '',
                    'phone_number' => $phone ?: ($user['phone'] ?? ''),
                    'amount' => round($amount, 2),
                    'currency' => 'KES',
                    'api_ref' => $order['order_number'],
                    'redirect_url' => $siteUrl . '/order-success',
                    'callback_url' => $siteUrl . '/api/payments/callback/intasend',
                ]);

                $respBody = http_post('https://payment.intasend.com/api/v1/payment/collection/', $body, [
                    'Authorization: Bearer ' . base64_encode($intakey . ':' . $intasecret),
                ]);
                $resp = $respBody ? json_decode($respBody, true) : [];

                if (isset($resp['url']) || isset($resp['charge_url'])) {
                    $redirectUrl = $resp['url'] ?? $resp['charge_url'] ?? '';
                    $trackingId = $resp['tracking_id'] ?? $resp['invoice']['tracking_id'] ?? '';

                    Database::update('orders', [
                        'payment_method' => 'intasend',
                        'updated_at' => $now,
                    ], 'id = ?', [$orderId]);

                    Database::insert('transactions', [
                        'order_id' => $orderId,
                        'payment_method' => 'intasend',
                        'amount' => $amount,
                        'reference' => $trackingId,
                        'status' => 'pending',
                        'created_at' => $now,
                    ]);

                    $this->posJson(['success' => true, 'redirect_url' => $redirectUrl]);
                } else {
                    $err = $resp['error'] ?? $resp['message'] ?? 'IntaSend error';
                    $this->posJson(['error' => is_array($err) ? json_encode($err) : $err], 400);
                }

            // ─── PESAPAL ──────────────────────────────────────────────────
            } elseif ($method === 'pesapal') {
                $pesaKey = $this->getSetting('pesapal_key');
                $pesaSecret = $this->getSetting('pesapal_secret');

                if (!$pesaKey || !$pesaSecret) {
                    $this->posJson(['error' => 'Pesapal not configured. Contact admin.'], 400);
                    return;
                }

                // Get Pesapal auth token
                $tokenResp = http_post(
                    'https://cybqa.pesapal.com/pesapalv3/api/Auth/RequestToken',
                    json_encode(['consumer_key' => $pesaKey, 'consumer_secret' => $pesaSecret])
                );
                $tokenResp = $tokenResp ? json_decode($tokenResp, true) : [];
                $pesaToken = $tokenResp['token'] ?? '';

                if (!$pesaToken) {
                    $this->posJson(['error' => 'Failed to get Pesapal token'], 500);
                    return;
                }

                $user = Auth::user();
                $nameParts = explode(' ', $user['name'] ?? '', 2);
                $callbackUrl = $siteUrl . '/api/payments/callback/pesapal';

                $orderBody = json_encode([
                    'id' => $order['order_number'],
                    'currency' => 'KES',
                    'amount' => round($amount, 2),
                    'description' => 'Order ' . $order['order_number'],
                    'callback_url' => $callbackUrl,
                    'notification_id' => '',
                    'billing_address' => [
                        'email_address' => $user['email'] ?? '',
                        'phone_number' => $phone ?: ($user['phone'] ?? ''),
                        'first_name' => $nameParts[0] ?? '',
                        'last_name' => $nameParts[1] ?? '',
                        'country' => 'KE',
                        'city' => '',
                        'state' => '',
                    ],
                ]);

                $respBody = http_post(
                    'https://cybqa.pesapal.com/pesapalv3/api/Transactions/SubmitOrderRequest',
                    $orderBody,
                    ['Authorization: Bearer ' . $pesaToken]
                );
                $resp = $respBody ? json_decode($respBody, true) : [];

                if (isset($resp['redirect_url'])) {
                    Database::update('orders', [
                        'payment_method' => 'pesapal',
                        'payment_reference' => $resp['order_tracking_id'] ?? '',
                        'updated_at' => $now,
                    ], 'id = ?', [$orderId]);

                    $this->posJson([
                        'success' => true,
                        'redirect_url' => $resp['redirect_url'],
                        'order_tracking_id' => $resp['order_tracking_id'] ?? '',
                    ]);
                } else {
                    $err = $resp['error'] ?? $resp['message'] ?? 'Pesapal error';
                    $this->posJson(['error' => is_array($err) ? json_encode($err) : $err], 400);
                }

            // ─── UNKNOWN METHOD ───────────────────────────────────────────
            } else {
                $this->posJson(['error' => 'Payment method not supported'], 400);
            }

        } catch (\Throwable $e) {
            $this->posLog('PAYMENT_INITIATE_ERROR', [
                'method' => $method,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            $this->posJson(['error' => 'Payment error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * M-Pesa STK Push callback.
     *
     * Receives the Daraja STK callback payload, extracts the receipt number,
     * amount, and phone number, and logs them for reconciliation.
     */
    public function mpesaCallback(): void
    {
        header('Content-Type: application/json');

        $body = json_decode(file_get_contents('php://input'), true);

        // Log the full callback for debugging
        $logPath = ROOT_PATH . '/logs/mpesa_callback.log';
        @mkdir(dirname($logPath), 0777, true);
        file_put_contents($logPath, date('Y-m-d H:i:s') . ' ' . json_encode($body) . "\n", FILE_APPEND);

        // Daraja STK callback structure:
        // {Body: {stkCallback: {MerchantRequestID, CheckoutRequestID, ResultCode,
        //   ResultDesc, CallbackMetadata: {Item: [...]}}}}
        if (isset($body['Body']['stkCallback'])) {
            $stk = $body['Body']['stkCallback'];
            $checkoutId = $stk['CheckoutRequestID'] ?? '';
            $resultCode = (int)($stk['ResultCode'] ?? -1);

            if ($resultCode == 0 && isset($stk['CallbackMetadata']['Item'])) {
                $items = $stk['CallbackMetadata']['Item'];
                $mpesaReceipt = '';
                $amount = 0;
                $phone = '';

                foreach ($items as $item) {
                    $name = $item['Name'] ?? '';
                    if ($name === 'MpesaReceiptNumber') {
                        $mpesaReceipt = (string)($item['Value'] ?? '');
                    }
                    if ($name === 'Amount') {
                        $amount = (int)($item['Value'] ?? 0);
                    }
                    if ($name === 'PhoneNumber') {
                        $phone = (string)($item['Value'] ?? '');
                    }
                }

                $this->posLog('MPESA_CALLBACK_SUCCESS', [
                    'checkout_id' => $checkoutId,
                    'receipt' => $mpesaReceipt,
                    'amount' => $amount,
                    'phone' => $phone,
                ]);

                // Log the extracted details
                file_put_contents($logPath, date('Y-m-d H:i:s') . " SUCCESS: Receipt=$mpesaReceipt Amount=$amount Phone=$phone CheckoutID=$checkoutId\n", FILE_APPEND);

                // Attempt to find and update the associated order
                // The checkout_id was stored in the session during initiate(),
                // but callbacks come from Safaricom's servers (no session).
                // Try to locate a recent pending order matching this checkout.
                $this->completeMpesaOrder($checkoutId, $mpesaReceipt, $amount, $phone);
            } else {
                $this->posLog('MPESA_CALLBACK_FAILED', [
                    'checkout_id' => $checkoutId,
                    'result_code' => $resultCode,
                    'result_desc' => $stk['ResultDesc'] ?? '',
                ]);
                file_put_contents($logPath, date('Y-m-d H:i:s') . " FAILED: CheckoutID=$checkoutId ResultCode=$resultCode\n", FILE_APPEND);
            }
        }

        // Always return 200 with ResultCode 0 to acknowledge receipt
        echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    /**
     * Attempt to mark an order as paid after a successful M-Pesa callback.
     */
    private function completeMpesaOrder(
        string $checkoutId,
        string $mpesaReceipt,
        int $amount,
        string $phone
    ): void {
        try {
            // Look for a recent order in 'pending' status that was initiated via M-Pesa
            // and whose amount matches (orders are in cents in DB sometimes, so cast carefully).
            // We also try matching by the AccountReference stored in the transaction description.
            $order = Database::selectOne(
                "SELECT o.*, t.id AS txn_id
                   FROM orders o
                   LEFT JOIN transactions t ON t.order_id = o.id AND t.payment_method = 'mpesa' AND t.status = 'pending'
                  WHERE o.payment_method = 'mpesa'
                    AND o.payment_status != 'paid'
                  ORDER BY o.id DESC
                  LIMIT 1"
            );

            if ($order) {
                $now = date('Y-m-d H:i:s');

                Database::update('orders', [
                    'payment_status' => 'paid',
                    'status' => 'processing',
                    'payment_reference' => $mpesaReceipt,
                    'customer_phone' => $phone,
                    'updated_at' => $now,
                ], 'id = ?', [$order['id']]);

                // Update existing pending transaction or insert a new one
                if ($order['txn_id']) {
                    Database::update('transactions', [
                        'status' => 'completed',
                        'reference' => $mpesaReceipt,
                    ], 'id = ?', [$order['txn_id']]);
                } else {
                    Database::insert('transactions', [
                        'order_id' => $order['id'],
                        'payment_method' => 'mpesa',
                        'amount' => $amount,
                        'reference' => $mpesaReceipt,
                        'status' => 'completed',
                        'created_at' => $now,
                    ]);
                }

                $this->processReferralCommission((int)$order['id']);
            }
        } catch (\Throwable $e) {
            $this->posLog('MPESA_COMPLETE_ORDER_ERROR', [
                'checkout_id' => $checkoutId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * IntaSend payment callback.
     *
     * Receives the IntaSend webhook payload. When the invoice state is
     * "complete", the associated order and transaction are updated.
     */
    public function intasendCallback(): void
    {
        header('Content-Type: application/json');

        $body = json_decode(file_get_contents('php://input'), true);

        // Log the full callback for debugging
        $logPath = ROOT_PATH . '/logs/intasend_callback.log';
        @mkdir(dirname($logPath), 0777, true);
        file_put_contents($logPath, date('Y-m-d H:i:s') . ' ' . json_encode($body) . "\n", FILE_APPEND);

        // IntaSend callback structure: {invoice: {state: 'COMPLETE'|'FAILED', ...}, tracking_id, ...}
        $state = $body['invoice']['state'] ?? '';
        $trackingId = $body['invoice']['tracking_id'] ?? $body['tracking_id'] ?? '';

        if (strtolower($state) === 'complete') {
            // Find the transaction by the tracking_id stored as its reference
            $txn = Database::selectOne("SELECT * FROM transactions WHERE reference = ?", [$trackingId]);

            if ($txn) {
                $now = date('Y-m-d H:i:s');

                Database::update('orders', [
                    'payment_status' => 'paid',
                    'status' => 'processing',
                    'updated_at' => $now,
                ], 'id = ?', [$txn['order_id']]);

                Database::update('transactions', [
                    'status' => 'completed',
                ], 'id = ?', [$txn['id']]);

                $this->posLog('INTASEND_CALLBACK_SUCCESS', [
                    'tracking_id' => $trackingId,
                    'order_id' => $txn['order_id'],
                    'txn_id' => $txn['id'],
                ]);

                $this->processReferralCommission((int)$txn['order_id']);
            } else {
                $this->posLog('INTASEND_CALLBACK_NO_TXN', ['tracking_id' => $trackingId]);
            }
        } else {
            $this->posLog('INTASEND_CALLBACK_INCOMPLETE', [
                'state' => $state,
                'tracking_id' => $trackingId,
            ]);
        }

        echo json_encode(['status' => 'ok']);
    }

    /**
     * Pesapal payment callback.
     *
     * Receives the Pesapal IPN payload. When the notification type is
     * "ORDER_COMPLETED", the associated order is marked as paid and a
     * transaction record is created.
     */
    public function pesapalCallback(): void
    {
        header('Content-Type: application/json');

        $body = json_decode(file_get_contents('php://input'), true);

        // Log the full callback for debugging
        $logPath = ROOT_PATH . '/logs/pesapal_callback.log';
        @mkdir(dirname($logPath), 0777, true);
        file_put_contents($logPath, date('Y-m-d H:i:s') . ' ' . json_encode($body) . "\n", FILE_APPEND);

        // Pesapal sends: {order_tracking_id, order_merchant_reference,
        //   order_notification_type: 'ORDER_COMPLETED'|'ORDER_FAILED', ...}
        $trackingId = $body['order_tracking_id'] ?? '';
        $merchantRef = $body['order_merchant_reference'] ?? '';
        $status = $body['order_notification_type'] ?? '';

        if ($status === 'ORDER_COMPLETED' && $merchantRef) {
            // Find order by its order_number (used as the merchant reference)
            $order = Database::selectOne("SELECT * FROM orders WHERE order_number = ?", [$merchantRef]);

            if ($order && $order['payment_status'] !== 'paid') {
                $now = date('Y-m-d H:i:s');

                Database::update('orders', [
                    'payment_status' => 'paid',
                    'status' => 'processing',
                    'payment_reference' => $trackingId,
                    'updated_at' => $now,
                ], 'id = ?', [$order['id']]);

                Database::insert('transactions', [
                    'order_id' => $order['id'],
                    'payment_method' => 'pesapal',
                    'amount' => $order['total'],
                    'reference' => $trackingId,
                    'status' => 'completed',
                    'created_at' => $now,
                ]);

                $this->posLog('PESAPAL_CALLBACK_SUCCESS', [
                    'tracking_id' => $trackingId,
                    'merchant_ref' => $merchantRef,
                    'order_id' => $order['id'],
                ]);

                $this->processReferralCommission((int)$order['id']);
            } else {
                $this->posLog('PESAPAL_CALLBACK_DUPLICATE', [
                    'tracking_id' => $trackingId,
                    'merchant_ref' => $merchantRef,
                ]);
            }
        } else {
            $this->posLog('PESAPAL_CALLBACK_INCOMPLETE', [
                'tracking_id' => $trackingId,
                'merchant_ref' => $merchantRef,
                'notification_type' => $status,
            ]);
        }

        echo json_encode(['status' => 'ok']);
    }
}