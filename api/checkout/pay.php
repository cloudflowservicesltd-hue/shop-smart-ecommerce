<?php
// Customer checkout - initiate payment
require_once __DIR__ . '/../../app/bootstrap.php';
header('Content-Type: application/json');

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Please login']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$method = $input['method'] ?? '';
$orderId = (int)($input['order_id'] ?? 0);
$phone = $input['phone'] ?? '';

if (empty($orderId) || empty($method)) {
    http_response_code(400);
    echo json_encode(['error' => 'Order ID and payment method are required']);
    exit;
}

$order = Database::selectOne("SELECT * FROM orders WHERE id = ? AND customer_id = ?", [$orderId, Auth::id()]);
if (!$order) {
    http_response_code(404);
    echo json_encode(['error' => 'Order not found']);
    exit;
}

if ($order['payment_status'] === 'paid') {
    http_response_code(400);
    echo json_encode(['error' => 'Order is already paid']);
    exit;
}

function getSetting($key) {
    $row = Database::selectOne("SELECT value FROM settings WHERE `key` = ?", [$key]);
    return $row ? $row['value'] : '';
}

// ============ M-PESA ============
if ($method === 'mpesa') {
    $env = getSetting('mpesa_env') ?: 'sandbox';
    $shortcode = getSetting('mpesa_shortcode') ?: '174379';
    $passkey = getSetting('mpesa_passkey') ?: '';
    $consumerKey = getSetting('mpesa_consumer_key') ?: '';
    $consumerSecret = getSetting('mpesa_consumer_secret') ?: '';
    $callbackUrl = getSetting('mpesa_callback_url') ?: (rtrim(getSetting('site_url') ?: 'http://localhost', '/') . '/api/mpesa/callback');

    if (empty($consumerKey) || empty($consumerSecret) || empty($passkey)) {
        http_response_code(400);
        echo json_encode(['error' => 'M-Pesa is not configured']);
        exit;
    }

    // Normalize phone
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (str_starts_with($phone, '0')) $phone = '254' . substr($phone, 1);
    elseif (str_starts_with($phone, '+')) $phone = substr($phone, 1);

    $baseUrl = $env === 'production' ? 'https://api.safaricom.co.ke' : 'https://sandbox.safaricom.co.ke';

    try {
        // OAuth
        $ch = curl_init($baseUrl . '/oauth/v1/generate?grant_type=client_credentials');
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => ['Authorization: Basic ' . base64_encode($consumerKey . ':' . $consumerSecret)],
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $tokenData = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (!isset($tokenData['access_token'])) {
            throw new \Exception('Failed to get M-Pesa access token');
        }
        $accessToken = $tokenData['access_token'];

        $timestamp = date('YmdHis');
        $password = base64_encode($shortcode . $passkey . $timestamp);

        $ch = curl_init($baseUrl . '/mpesa/stkpush/v1/processrequest');
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken, 'Content-Type: application/json'],
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
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_SSL_VERIFYPEER => false,
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
            echo json_encode([
                'success' => true,
                'CheckoutRequestID' => $stkData['CheckoutRequestID'],
                'message' => 'STK push sent! Check your phone to confirm payment.',
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => ($stkData['errorMessage'] ?? 'STK push failed'),
            ]);
        }
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// ============ INTASEND ============
if ($method === 'intasend') {
    $apiKey = getSetting('intasend_key') ?: '';
    $apiSecret = getSetting('intasend_secret') ?: '';

    if (empty($apiKey) || empty($apiSecret)) {
        http_response_code(400);
        echo json_encode(['error' => 'IntaSend is not configured']);
        exit;
    }

    try {
        $payload = [
            'amount' => $order['total'],
            'currency' => 'KES',
            'first_name' => explode(' ', $order['customer_name'])[0] ?? 'Customer',
            'last_name' => explode(' ', $order['customer_name'])[1] ?? '',
            'email' => $order['customer_email'] ?: 'customer@example.com',
            'phone_number' => preg_replace('/[^0-9]/', '', $order['customer_phone'] ?? $phone),
            'redirect_url' => rtrim(getSetting('site_url') ?: 'http://localhost', '/') . '/api/checkout/intasend-verify?order_id=' . $orderId,
            'webhook_url' => rtrim(getSetting('site_url') ?: 'http://localhost', '/') . '/api/checkout/intasend-callback',
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
            echo json_encode([
                'success' => true,
                'redirect' => $data['url'],
                'tracking_id' => $data['tracking_id'] ?? '',
                'message' => 'Redirecting to IntaSend...',
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => $data['error'] ?? $data['message'] ?? 'IntaSend checkout failed',
            ]);
        }
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// ============ PESAPAL ============
if ($method === 'pesapal') {
    $consumerKey = getSetting('pesapal_key') ?: '';
    $consumerSecret = getSetting('pesapal_secret') ?: '';

    if (empty($consumerKey) || empty($consumerSecret)) {
        http_response_code(400);
        echo json_encode(['error' => 'Pesapal is not configured']);
        exit;
    }

    $siteUrl = rtrim(getSetting('site_url') ?: 'http://localhost', '/');
    $env = getSetting('pesapal_env') ?: 'sandbox';
    $apiUrl = $env === 'production'
        ? 'https://pay.pesapal.com/v3'
        : 'https://cybqa.pesapal.com/pesapalv3';

    try {
        // Get Pesapal token
        $ch = curl_init($apiUrl . '/api/Auth/RequestToken');
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'consumer_key' => $consumerKey,
                'consumer_secret' => $consumerSecret,
            ]),
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30,
        ]);
        $tokenData = json_decode(curl_exec($ch), true);
        curl_close($ch);

        $token = $tokenData['token'] ?? '';
        if (empty($token)) {
            throw new \Exception('Failed to get Pesapal token');
        }

        // Submit order
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
                    'first_name' => explode(' ', $order['customer_name'])[0] ?? 'Customer',
                    'last_name' => explode(' ', $order['customer_name'])[1] ?? '',
                ],
            ]),
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30,
        ]);
        $orderData = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (isset($orderData['redirect_url'])) {
            // Store the order tracking id
            Database::update('orders', [
                'payment_reference' => $orderData['order_tracking_id'] ?? '',
            ], 'id = ?', [$orderId]);

            echo json_encode([
                'success' => true,
                'redirect' => $orderData['redirect_url'],
                'order_tracking_id' => $orderData['order_tracking_id'] ?? '',
                'message' => 'Redirecting to Pesapal...',
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => $orderData['error']['message'] ?? 'Pesapal order submission failed',
            ]);
        }
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// ============ STRIPE ============
if ($method === 'stripe') {
    $secretKey = getSetting('stripe_secret') ?: '';
    $publishableKey = getSetting('stripe_key') ?: '';

    if (empty($secretKey) || empty($publishableKey)) {
        http_response_code(400);
        echo json_encode(['error' => 'Stripe is not configured']);
        exit;
    }

    $siteUrl = rtrim(getSetting('site_url') ?: 'http://localhost', '/');

    try {
        // Create a Stripe Checkout Session
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
                'success_url' => $siteUrl . '/api/checkout/stripe-success?session_id={CHECKOUT_SESSION_ID}&order_id=' . $orderId,
                'cancel_url' => $siteUrl . '/checkout/payment',
                'metadata[order_id]' => $orderId,
                'metadata[order_number]' => $order['order_number'],
            ]),
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30,
        ]);
        $sessionData = json_decode(curl_exec($ch), true);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && isset($sessionData['url'])) {
            echo json_encode([
                'success' => true,
                'redirect' => $sessionData['url'],
                'session_id' => $sessionData['id'],
                'message' => 'Redirecting to Stripe...',
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => $sessionData['error']['message'] ?? 'Stripe session creation failed',
            ]);
        }
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// ============ CARD (manual) ============
if ($method === 'card') {
    // Card payments are handled as manual entry (like M-Pesa manual code)
    // The customer enters a card transaction code from the POS terminal
    echo json_encode([
        'success' => true,
        'message' => 'Enter the card transaction code from the payment terminal',
        'requires_code' => true,
    ]);
    exit;
}

echo json_encode(['error' => 'Unsupported payment method: ' . $method]);