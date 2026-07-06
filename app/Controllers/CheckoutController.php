<?php

/**
 * Checkout Controller
 *
 * Handles customer-facing checkout operations: order creation,
 * payment initiation for multiple gateways, and payment callbacks.
 */
class CheckoutController extends BaseController
{
    /**
     * Create an order from the user's cart.
     * Requires auth. Reads cart items, shipping info from session,
     * calculates tax, generates order number, persists order + items,
     * deducts stock, and clears the cart.
     */
    public function createOrder(): void
    {
        header('Content-Type: application/json');

        if (!Auth::check()) {
            $this->posJson(['error' => 'Please login'], 401);
            return;
        }

        $cartItems = Database::select(
            "SELECT c.*, p.name, p.price, p.stock_quantity FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?",
            [Auth::id()]
        );

        if (empty($cartItems)) {
            $this->posJson(['error' => 'Cart is empty'], 400);
            return;
        }

        $shipping = Session::get('checkout_shipping', []);
        if (empty($shipping)) {
            $this->posJson(['error' => 'No shipping info'], 400);
            return;
        }

        $taxRate = (float)($this->getSetting('tax_rate') ?: 16);
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
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $item['price'] * $item['quantity'],
                    'created_at' => $now,
                ]);
                Database::update(
                    'products',
                    ['stock_quantity' => $item['stock_quantity'] - $item['quantity'], 'updated_at' => $now],
                    'id = ?',
                    [$item['product_id']]
                );
            }

            Database::delete('cart', 'user_id = ?', [Auth::id()]);
            Session::set('last_order_id', $orderId);
            Session::set('last_order_num', $orderNum);

            $this->posJson([
                'success' => true,
                'order_id' => $orderId,
                'order_number' => $orderNum,
                'total' => $total,
                'payment_method' => $shipping['payment_method'] ?? 'mpesa',
            ]);
        } catch (\Throwable $e) {
            $this->posLog('CREATE_ORDER_FAILED', ['error' => $e->getMessage()]);
            $this->posJson(['error' => 'Failed to create order: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Initiate payment for an order.
     * Routes to the appropriate payment gateway based on the `method` input.
     * Supported methods: mpesa, intasend, pesapal, stripe, card.
     */
    public function pay(): void
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

        if (empty($orderId) || empty($method)) {
            $this->posJson(['error' => 'Order ID and payment method are required'], 400);
            return;
        }

        $order = Database::selectOne(
            "SELECT * FROM orders WHERE id = ? AND customer_id = ?",
            [$orderId, Auth::id()]
        );

        if (!$order) {
            $this->posJson(['error' => 'Order not found'], 404);
            return;
        }

        if ($order['payment_status'] === 'paid') {
            $this->posJson(['error' => 'Order is already paid'], 400);
            return;
        }

        // Route to the correct payment handler
        switch ($method) {
            case 'mpesa':
                $this->payMpesa($order, $phone);
                break;
            case 'intasend':
                $this->payIntasend($order, $phone);
                break;
            case 'pesapal':
                $this->payPesapal($order);
                break;
            case 'stripe':
                $this->payStripe($order);
                break;
            case 'card':
                $this->payCard($order);
                break;
            default:
                $this->posJson(['error' => 'Unsupported payment method: ' . $method], 400);
                break;
        }
    }

    /**
     * Pesapal IPN callback.
     * Receives order_tracking_id and merchant_reference, verifies the
     * transaction status with Pesapal, and updates the order if paid.
     */
    public function pesapalCallback(): void
    {
        header('Content-Type: application/json');

        $input = $this->posInput();
        $orderTrackingId = $input['order_tracking_id'] ?? $_GET['OrderTrackingId'] ?? '';
        $merchantReference = $input['merchant_reference'] ?? $_GET['OrderMerchantReference'] ?? '';
        $notificationType = $input['notification_type'] ?? '';

        if (empty($orderTrackingId)) {
            $this->posJson(['error' => 'Missing order tracking ID'], 400);
            return;
        }

        $this->posLog('PESAPAL_IPN_RECEIVED', [
            'order_tracking_id' => $orderTrackingId,
            'merchant_reference' => $merchantReference,
            'notification_type' => $notificationType,
        ]);

        $consumerKey = $this->getSetting('pesapal_key');
        $consumerSecret = $this->getSetting('pesapal_secret');
        $env = $this->getSetting('pesapal_env') ?: 'sandbox';
        $apiUrl = $env === 'production'
            ? 'https://pay.pesapal.com/v3'
            : 'https://cybqa.pesapal.com/pesapalv3';

        // Get Pesapal auth token
        $ch = curl_init($apiUrl . '/api/Auth/RequestToken');
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'consumer_key' => $consumerKey,
                'consumer_secret' => $consumerSecret,
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);
        $tokenData = json_decode(curl_exec($ch), true);
        curl_close($ch);
        $token = $tokenData['token'] ?? '';

        if (empty($token)) {
            $this->posLog('PESAPAL_IPN_AUTH_FAILED', ['response' => $tokenData]);
            $this->posJson(['error' => 'Auth failed'], 500);
            return;
        }

        // Get transaction status from Pesapal
        $ch = curl_init($apiUrl . '/api/Transactions/GetTransactionStatus?orderTrackingId=' . urlencode($orderTrackingId));
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);
        $statusData = json_decode(curl_exec($ch), true);
        curl_close($ch);

        $paymentStatus = $statusData['status_code'] ?? '';
        $transactionRef = $statusData['payment_method'] ?? '';

        $this->posLog('PESAPAL_IPN_STATUS', [
            'order_tracking_id' => $orderTrackingId,
            'status_code' => $paymentStatus,
            'payment_method' => $transactionRef,
        ]);

        // status_code 1 = completed, 2 = confirmed
        if ($paymentStatus === '1' || $paymentStatus === '2') {
            $order = Database::selectOne(
                "SELECT id, order_number FROM orders WHERE order_number = ?",
                [$merchantReference]
            );

            if ($order) {
                $now = date('Y-m-d H:i:s');

                Database::update('orders', [
                    'payment_status' => 'paid',
                    'payment_reference' => $transactionRef,
                    'status' => 'processing',
                    'updated_at' => $now,
                ], 'id = ?', [$order['id']]);

                Database::insert('transactions', [
                    'order_id' => $order['id'],
                    'payment_method' => 'pesapal',
                    'amount' => 0,
                    'reference' => $orderTrackingId,
                    'status' => 'completed',
                    'created_at' => $now,
                ]);

                $this->processReferralCommission($order['id']);

                $this->posLog('PESAPAL_IPN_ORDER_PAID', [
                    'order_id' => $order['id'],
                    'order_number' => $order['order_number'],
                ]);
            }
        }

        $this->posJson(['status' => 'received']);
    }

    /**
     * Stripe success callback.
     * Verifies the Stripe Checkout Session's payment_intent status.
     * If succeeded, marks the order as paid and redirects to /order-success.
     * If failed, redirects back to /checkout/payment.
     */
    public function stripeSuccess(): void
    {
        $sessionId = $_GET['session_id'] ?? '';
        $orderId = (int)($_GET['order_id'] ?? 0);

        if (empty($sessionId) || empty($orderId)) {
            Session::flash('error', 'Invalid payment callback');
            Redirect::to('/checkout/payment');
            return;
        }

        $secretKey = $this->getSetting('stripe_secret');

        if (empty($secretKey)) {
            Session::flash('error', 'Stripe is not configured');
            Redirect::to('/checkout/payment');
            return;
        }

        // Verify the session with Stripe
        $ch = curl_init('https://api.stripe.com/v1/checkout/sessions/' . $sessionId . '?expand[]=payment_intent');
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $secretKey],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);
        $sessionData = json_decode(curl_exec($ch), true);
        curl_close($ch);

        $paymentIntent = $sessionData['payment_intent'] ?? [];

        if (($paymentIntent['status'] ?? '') === 'succeeded') {
            $now = date('Y-m-d H:i:s');

            // Mark order as paid
            Database::update('orders', [
                'payment_status' => 'paid',
                'payment_reference' => $paymentIntent['id'] ?? $sessionId,
                'status' => 'processing',
                'updated_at' => $now,
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
                    'created_at' => $now,
                ]);

                $this->processReferralCommission($orderId);
            }

            Session::set('last_order_id', $orderId);
            Session::set('last_order_num', $order['order_number'] ?? '');
            Redirect::to('/order-success');
        } else {
            Session::flash('error', 'Payment was not completed. Please try again.');
            Redirect::to('/checkout/payment');
        }
    }

    // =========================================================================
    // Private payment method handlers
    // =========================================================================

    /**
     * Initiate M-PESA STK push payment via Daraja API.
     */
    private function payMpesa(array $order, string $phone): void
    {
        $env = $this->getSetting('mpesa_env') ?: 'sandbox';
        $shortcode = $this->getSetting('mpesa_shortcode') ?: '174379';
        $passkey = $this->getSetting('mpesa_passkey');
        $consumerKey = $this->getSetting('mpesa_consumer_key');
        $consumerSecret = $this->getSetting('mpesa_consumer_secret');
        $siteUrl = $this->getSiteUrl();
        $callbackUrl = $this->getSetting('mpesa_callback_url') ?: $siteUrl . '/api/mpesa/callback';

        if (empty($consumerKey) || empty($consumerSecret) || empty($passkey)) {
            $this->posJson(['error' => 'M-Pesa is not configured'], 400);
            return;
        }

        // Normalize the phone number to 254XXXXXXXXX format
        $phone = $this->normalizePhone($phone);

        $baseUrl = $env === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';

        try {
            // Step 1: Get OAuth access token
            $ch = curl_init($baseUrl . '/oauth/v1/generate?grant_type=client_credentials');
            curl_setopt_array($ch, [
                CURLOPT_HTTPHEADER => [
                    'Authorization: Basic ' . base64_encode($consumerKey . ':' . $consumerSecret),
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $tokenData = json_decode(curl_exec($ch), true);
            curl_close($ch);

            if (!isset($tokenData['access_token'])) {
                throw new \Exception('Failed to get M-Pesa access token');
            }
            $accessToken = $tokenData['access_token'];

            // Step 2: Initiate STK push
            $timestamp = date('YmdHis');
            $password = base64_encode($shortcode . $passkey . $timestamp);

            $ch = curl_init($baseUrl . '/mpesa/stkpush/v1/processrequest');
            curl_setopt_array($ch, [
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $accessToken,
                    'Content-Type: application/json',
                ],
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'BusinessShortCode' => $shortcode,
                    'Password' => $password,
                    'Timestamp' => $timestamp,
                    'TransactionType' => 'CustomerPayBillOnline',
                    'Amount' => (int)round($order['total']),
                    'PartyA' => $phone,
                    'PartyB' => $shortcode,
                    'PhoneNumber' => $phone,
                    'CallBackURL' => $callbackUrl,
                    'AccountReference' => substr($order['order_number'], 0, 12),
                    'TransactionDesc' => 'Order ' . $order['order_number'],
                ]),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $stkData = json_decode(curl_exec($ch), true);
            curl_close($ch);

            if (isset($stkData['ResponseCode']) && $stkData['ResponseCode'] === '0') {
                Session::set('mpesa_stk_' . $stkData['CheckoutRequestID'], [
                    'CheckoutRequestID' => $stkData['CheckoutRequestID'],
                    'orderRef' => $order['order_number'],
                    'phone' => $phone,
                    'amount' => $order['total'],
                    'created_at' => time(),
                ]);

                $this->posJson([
                    'success' => true,
                    'CheckoutRequestID' => $stkData['CheckoutRequestID'],
                    'message' => 'STK push sent! Check your phone to confirm payment.',
                ]);
            } else {
                $this->posLog('MPESA_STK_FAILED', [
                    'order_id' => $order['id'],
                    'response' => $stkData,
                ]);
                $this->posJson([
                    'success' => false,
                    'error' => $stkData['errorMessage'] ?? 'STK push failed',
                ]);
            }
        } catch (\Throwable $e) {
            $this->posLog('MPESA_STK_ERROR', [
                'order_id' => $order['id'],
                'error' => $e->getMessage(),
            ]);
            $this->posJson(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Initiate IntaSend checkout payment.
     * Creates a hosted checkout page via the IntaSend API and returns the redirect URL.
     */
    private function payIntasend(array $order, string $phone): void
    {
        $apiKey = $this->getSetting('intasend_key');
        $apiSecret = $this->getSetting('intasend_secret');

        if (empty($apiKey) || empty($apiSecret)) {
            $this->posJson(['error' => 'IntaSend is not configured'], 400);
            return;
        }

        $siteUrl = $this->getSiteUrl();

        try {
            $nameParts = explode(' ', $order['customer_name']);
            $payload = [
                'amount' => $order['total'],
                'currency' => 'KES',
                'first_name' => $nameParts[0] ?? 'Customer',
                'last_name' => $nameParts[1] ?? '',
                'email' => $order['customer_email'] ?: 'customer@example.com',
                'phone_number' => preg_replace('/[^0-9]/', '', $order['customer_phone'] ?? $phone),
                'redirect_url' => $siteUrl . '/api/checkout/intasend-verify?order_id=' . $order['id'],
                'webhook_url' => $siteUrl . '/api/checkout/intasend-callback',
                'ref' => $order['order_number'],
            ];

            $ch = curl_init('https://api.intasend.com/api/v1/checkout/');
            curl_setopt_array($ch, [
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . base64_encode($apiKey . ':' . $apiSecret),
                    'Content-Type: application/json',
                    'X-IntaSend-Public-API-Key: ' . $apiKey,
                ],
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $data = json_decode($response, true);

            if ($httpCode === 201 && isset($data['url'])) {
                $this->posJson([
                    'success' => true,
                    'redirect' => $data['url'],
                    'tracking_id' => $data['tracking_id'] ?? '',
                    'message' => 'Redirecting to IntaSend...',
                ]);
            } else {
                $this->posLog('INTASEND_CHECKOUT_FAILED', [
                    'order_id' => $order['id'],
                    'http_code' => $httpCode,
                    'response' => $data,
                ]);
                $this->posJson([
                    'success' => false,
                    'error' => $data['error'] ?? $data['message'] ?? 'IntaSend checkout failed',
                ]);
            }
        } catch (\Throwable $e) {
            $this->posLog('INTASEND_ERROR', [
                'order_id' => $order['id'],
                'error' => $e->getMessage(),
            ]);
            $this->posJson(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Initiate Pesapal payment.
     * Gets a Pesapal auth token, submits the order, and returns the redirect URL.
     */
    private function payPesapal(array $order): void
    {
        $consumerKey = $this->getSetting('pesapal_key');
        $consumerSecret = $this->getSetting('pesapal_secret');

        if (empty($consumerKey) || empty($consumerSecret)) {
            $this->posJson(['error' => 'Pesapal is not configured'], 400);
            return;
        }

        $siteUrl = $this->getSiteUrl();
        $env = $this->getSetting('pesapal_env') ?: 'sandbox';
        $apiUrl = $env === 'production'
            ? 'https://pay.pesapal.com/v3'
            : 'https://cybqa.pesapal.com/pesapalv3';

        try {
            // Step 1: Get Pesapal auth token
            $ch = curl_init($apiUrl . '/api/Auth/RequestToken');
            curl_setopt_array($ch, [
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'consumer_key' => $consumerKey,
                    'consumer_secret' => $consumerSecret,
                ]),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
            ]);
            $tokenData = json_decode(curl_exec($ch), true);
            curl_close($ch);

            $token = $tokenData['token'] ?? '';
            if (empty($token)) {
                throw new \Exception('Failed to get Pesapal token');
            }

            // Step 2: Submit order to Pesapal
            $nameParts = explode(' ', $order['customer_name']);
            $ch = curl_init($apiUrl . '/api/Transactions/SubmitOrderRequest');
            curl_setopt_array($ch, [
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $token,
                    'Content-Type: application/json',
                ],
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'id' => $order['order_number'],
                    'currency' => 'KES',
                    'amount' => $order['total'],
                    'description' => 'Order ' . $order['order_number'],
                    'callback_url' => $siteUrl . '/api/checkout/pesapal-callback',
                    'notification_id' => '',
                    'billing_address' => [
                        'email_address' => $order['customer_email'] ?: 'customer@example.com',
                        'phone_number' => preg_replace('/[^0-9]/', '', $order['customer_phone'] ?? ''),
                        'country_code' => 'KE',
                        'first_name' => $nameParts[0] ?? 'Customer',
                        'last_name' => $nameParts[1] ?? '',
                    ],
                ]),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
            ]);
            $orderData = json_decode(curl_exec($ch), true);
            curl_close($ch);

            if (isset($orderData['redirect_url'])) {
                // Store the order tracking id on the order
                Database::update('orders', [
                    'payment_reference' => $orderData['order_tracking_id'] ?? '',
                ], 'id = ?', [$order['id']]);

                $this->posJson([
                    'success' => true,
                    'redirect' => $orderData['redirect_url'],
                    'order_tracking_id' => $orderData['order_tracking_id'] ?? '',
                    'message' => 'Redirecting to Pesapal...',
                ]);
            } else {
                $this->posLog('PESAPAL_SUBMIT_FAILED', [
                    'order_id' => $order['id'],
                    'response' => $orderData,
                ]);
                $this->posJson([
                    'success' => false,
                    'error' => $orderData['error']['message'] ?? 'Pesapal order submission failed',
                ]);
            }
        } catch (\Throwable $e) {
            $this->posLog('PESAPAL_ERROR', [
                'order_id' => $order['id'],
                'error' => $e->getMessage(),
            ]);
            $this->posJson(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Initiate Stripe Checkout Session payment.
     * Creates a Stripe Checkout Session and returns the redirect URL.
     */
    private function payStripe(array $order): void
    {
        $secretKey = $this->getSetting('stripe_secret');
        $publishableKey = $this->getSetting('stripe_key');

        if (empty($secretKey) || empty($publishableKey)) {
            $this->posJson(['error' => 'Stripe is not configured'], 400);
            return;
        }

        $siteUrl = $this->getSiteUrl();

        try {
            $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
            curl_setopt_array($ch, [
                CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $secretKey],
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query([
                    'payment_method_types[]' => 'card',
                    'line_items[0][price_data][currency]' => 'kes',
                    'line_items[0][price_data][product_data][name]' => 'Order ' . $order['order_number'],
                    'line_items[0][price_data][unit_amount]' => (int)round($order['total'] * 100),
                    'line_items[0][quantity]' => 1,
                    'mode' => 'payment',
                    'success_url' => $siteUrl . '/api/checkout/stripe-success?session_id={CHECKOUT_SESSION_ID}&order_id=' . $order['id'],
                    'cancel_url' => $siteUrl . '/checkout/payment',
                    'metadata[order_id]' => $order['id'],
                    'metadata[order_number]' => $order['order_number'],
                ]),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
            ]);
            $sessionData = json_decode(curl_exec($ch), true);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && isset($sessionData['url'])) {
                $this->posJson([
                    'success' => true,
                    'redirect' => $sessionData['url'],
                    'session_id' => $sessionData['id'],
                    'message' => 'Redirecting to Stripe...',
                ]);
            } else {
                $this->posLog('STRIPE_SESSION_FAILED', [
                    'order_id' => $order['id'],
                    'http_code' => $httpCode,
                    'response' => $sessionData,
                ]);
                $this->posJson([
                    'success' => false,
                    'error' => $sessionData['error']['message'] ?? 'Stripe session creation failed',
                ]);
            }
        } catch (\Throwable $e) {
            $this->posLog('STRIPE_ERROR', [
                'order_id' => $order['id'],
                'error' => $e->getMessage(),
            ]);
            $this->posJson(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Card payment (manual entry).
     * Returns a flag indicating a transaction code is required from a POS terminal.
     */
    private function payCard(array $order): void
    {
        $this->posJson([
            'success' => true,
            'message' => 'Enter the card transaction code from the payment terminal',
            'requires_code' => true,
        ]);
    }
}