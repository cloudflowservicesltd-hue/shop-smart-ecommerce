<?php
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
$amount = (float)($input['amount'] ?? 0);
$paymentMethodId = $input['payment_method_id'] ?? '';

// Validate order
$order = Database::selectOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
if (!$order) { http_response_code(404); echo json_encode(['error' => 'Order not found']); exit; }
if ($order['customer_id'] != Auth::id()) { http_response_code(403); echo json_encode(['error' => 'Access denied']); exit; }
if ($order['payment_status'] === 'paid') { http_response_code(400); echo json_encode(['error' => 'Order already paid']); exit; }

$now = date('Y-m-d H:i:s');

try {
    if ($method === 'mpesa') {
        $key = Database::selectOne("SELECT value FROM settings WHERE `key` = 'mpesa_consumer_key'")['value'] ?? '';
        $secret = Database::selectOne("SELECT value FROM settings WHERE `key` = 'mpesa_consumer_secret'")['value'] ?? '';
        $passkey = Database::selectOne("SELECT value FROM settings WHERE `key` = 'mpesa_passkey'")['value'] ?? '';
        $shortcode = Database::selectOne("SELECT value FROM settings WHERE `key` = 'mpesa_shortcode'")['value'] ?? '';
        $env = Database::selectOne("SELECT value FROM settings WHERE `key` = 'mpesa_env'")['value'] ?? 'sandbox';

        if (!$key || !$secret || !$passkey || !$shortcode) {
            http_response_code(400);
            echo json_encode(['error' => 'M-Pesa not configured. Contact admin.']);
            exit;
        }

        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strpos($phone, '0') === 0) $phone = '254' . substr($phone, 1);
        if (strpos($phone, '7') === 0 || strpos($phone, '1') === 0) $phone = '254' . $phone;

        $baseUrl = $env === 'production' ? 'https://api.safaricom.co.ke' : 'https://sandbox.safaricom.co.ke';

        $tokenResp = http_get($baseUrl . '/oauth/v1/generate?grant_type=client_credentials', [
            'Authorization' => 'Basic ' . base64_encode($key . ':' . $secret),
        ]);
        $tokenResp = $tokenResp ? json_decode($tokenResp, true) : [];
        $token = $tokenResp['access_token'] ?? '';
        if (!$token) { http_response_code(500); echo json_encode(['error' => 'Failed to get M-Pesa token']); exit; }

        $timestamp = date('YmdHis');
        $password = base64_encode($shortcode . $passkey . $timestamp);
        $callbackUrl = Database::selectOne("SELECT value FROM settings WHERE `key` = 'mpesa_callback'")['value'] ?? (($_SERVER['HTTPS'] ?? '') === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/api/payments/callback/mpesa';

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
            Database::update('orders', ['payment_method' => 'mpesa', 'customer_phone' => $phone, 'updated_at' => $now], 'id = ?', [$orderId]);
            Session::set('mpesa_stk_order_' . $orderId, ['checkout_id' => $stkResp['CheckoutRequestID'], 'phone' => $phone]);
            echo json_encode(['success' => true, 'checkout_request_id' => $stkResp['CheckoutRequestID']]);
        } else {
            $errMsg = $stkResp['errorMessage'] ?? $stkResp['ResponseDescription'] ?? 'STK push failed';
            http_response_code(400);
            echo json_encode(['error' => $errMsg]);
        }

    } elseif ($method === 'stripe') {
        $stripeSecret = Database::selectOne("SELECT value FROM settings WHERE `key` = 'stripe_secret'")['value'] ?? '';
        if (!$stripeSecret) { http_response_code(400); echo json_encode(['error' => 'Stripe not configured. Contact admin.']); exit; }
        if (!$paymentMethodId) { http_response_code(400); echo json_encode(['error' => 'Payment method ID required']); exit; }

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
            http_response_code(400);
            echo json_encode(['error' => $resp['error']['message'] ?? 'Stripe error']);
        } elseif ($resp['status'] === 'succeeded') {
            Database::update('orders', ['payment_status' => 'paid', 'payment_method' => 'stripe', 'payment_reference' => ($resp['id'] ?? ''), 'status' => 'processing', 'updated_at' => $now], 'id = ?', [$orderId]);
            Database::insert('transactions', ['order_id' => $orderId, 'payment_method' => 'stripe', 'amount' => $amount, 'reference' => $resp['id'] ?? '', 'status' => 'completed', 'created_at' => $now]);
            echo json_encode(['success' => true]);
        } elseif ($resp['status'] === 'requires_action') {
            echo json_encode(['success' => false, 'requires_action' => true, 'client_secret' => $resp['client_secret'], 'payment_intent_id' => $resp['id']]);
        } else {
            echo json_encode(['success' => false, 'status' => $resp['status'] ?? '', 'client_secret' => $resp['client_secret'] ?? '']);
        }

    } elseif ($method === 'intasend') {
        $intakey = Database::selectOne("SELECT value FROM settings WHERE `key` = 'intasend_key'")['value'] ?? '';
        $intasecret = Database::selectOne("SELECT value FROM settings WHERE `key` = 'intasend_secret'")['value'] ?? '';
        if (!$intakey || !$intasecret) { http_response_code(400); echo json_encode(['error' => 'IntaSend not configured. Contact admin.']); exit; }

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
            'redirect_url' => (($_SERVER['HTTPS'] ?? '') === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/order-success',
            'callback_url' => (($_SERVER['HTTPS'] ?? '') === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/api/payments/callback/intasend',
        ]);

        $respBody = http_post('https://payment.intasend.com/api/v1/payment/collection/', $body, [
            'Authorization: Bearer ' . base64_encode($intakey . ':' . $intasecret),
        ]);
        $resp = $respBody ? json_decode($respBody, true) : [];

        if (isset($resp['url']) || isset($resp['charge_url'])) {
            $redirectUrl = $resp['url'] ?? $resp['charge_url'] ?? '';
            $trackingId = $resp['tracking_id'] ?? $resp['invoice']['tracking_id'] ?? '';
            Database::update('orders', ['payment_method' => 'intasend', 'updated_at' => $now], 'id = ?', [$orderId]);
            Database::insert('transactions', ['order_id' => $orderId, 'payment_method' => 'intasend', 'amount' => $amount, 'reference' => $trackingId, 'status' => 'pending', 'created_at' => $now]);
            echo json_encode(['success' => true, 'redirect_url' => $redirectUrl]);
        } else {
            $err = $resp['error'] ?? $resp['message'] ?? 'IntaSend error';
            http_response_code(400);
            echo json_encode(['error' => is_array($err) ? json_encode($err) : $err]);
        }

    } elseif ($method === 'pesapal') {
        $pesaKey = Database::selectOne("SELECT value FROM settings WHERE `key` = 'pesapal_key'")['value'] ?? '';
        $pesaSecret = Database::selectOne("SELECT value FROM settings WHERE `key` = 'pesapal_secret'")['value'] ?? '';
        if (!$pesaKey || !$pesaSecret) { http_response_code(400); echo json_encode(['error' => 'Pesapal not configured. Contact admin.']); exit; }

        $tokenResp = http_post('https://cybqa.pesapal.com/pesapalv3/api/Auth/RequestToken',
            json_encode(['consumer_key' => $pesaKey, 'consumer_secret' => $pesaSecret])
        );
        $tokenResp = $tokenResp ? json_decode($tokenResp, true) : [];
        $pesaToken = $tokenResp['token'] ?? '';
        if (!$pesaToken) { http_response_code(500); echo json_encode(['error' => 'Failed to get Pesapal token']); exit; }

        $user = Auth::user();
        $nameParts = explode(' ', $user['name'] ?? '', 2);
        $callbackUrl = (($_SERVER['HTTPS'] ?? '') === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/api/payments/callback/pesapal';

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
                'country' => 'KE', 'city' => '', 'state' => '',
            ],
        ]);

        $respBody = http_post('https://cybqa.pesapal.com/pesapalv3/api/Transactions/SubmitOrderRequest', $orderBody, [
            'Authorization: Bearer ' . $pesaToken,
        ]);
        $resp = $respBody ? json_decode($respBody, true) : [];

        if (isset($resp['redirect_url'])) {
            Database::update('orders', ['payment_method' => 'pesapal', 'payment_reference' => $resp['order_tracking_id'] ?? '', 'updated_at' => $now], 'id = ?', [$orderId]);
            echo json_encode(['success' => true, 'redirect_url' => $resp['redirect_url'], 'order_tracking_id' => $resp['order_tracking_id'] ?? '']);
        } else {
            $err = $resp['error'] ?? $resp['message'] ?? 'Pesapal error';
            http_response_code(400);
            echo json_encode(['error' => is_array($err) ? json_encode($err) : $err]);
        }

    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Payment method not supported']);
    }

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Payment error: ' . $e->getMessage()]);
}