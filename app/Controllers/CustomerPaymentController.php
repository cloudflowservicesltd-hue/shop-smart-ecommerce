<?php

/**
 * Customer Payment Controller
 *
 * Handles the full customer-facing payment flow:
 * - Initiating payment from checkout (POST /payment/initiate)
 * - Re-initiating payment for a pending order (POST /order/pay/{id})
 * - Displaying the order pay page (GET /order/pay/{id})
 * - Polling payment status (GET /payment/status)
 * - Payment gateway callbacks: M-Pesa, IntaSend, PayPal, Stripe, PesaPal
 */
class CustomerPaymentController extends BaseController
{
    // ============================================================
    // Shared Payment Helpers
    // ============================================================

    /**
     * Shared curl call with shared-hosting-friendly settings (timeouts, no SSL verify).
     */
    protected function curlRequest(string $url, array $options = [], bool $expectJson = true): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_USERAGENT, 'ShopSmart/1.0');

        foreach ($options as $key => $value) {
            curl_setopt($ch, $key, $value);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        curl_close($ch);

        // If cURL failed entirely
        if ($response === false) {
            return [
                'response'  => '',
                'http_code' => 0,
                'error'     => $curlError ?: 'Connection failed',
                'errno'     => $curlErrno,
                'success'   => false,
            ];
        }

        // If we expect JSON but got HTML (hosting firewall/WAF block page)
        if ($expectJson && $httpCode >= 200 && $httpCode < 600 && preg_match('/^\s*</', $response)) {
            return [
                'response'  => $response,
                'http_code' => $httpCode,
                'error'     => 'Hosting firewall blocked the request to ' . parse_url($url, PHP_URL_HOST) . '. Response is HTML instead of JSON.',
                'errno'     => 0,
                'success'   => false,
            ];
        }

        // Non-2xx HTTP code
        if ($httpCode >= 400) {
            $jsonBody = json_decode($response, true);
            $apiError = $jsonBody['error_description'] ?? ($jsonBody['error'] ?? ($jsonBody['message'] ?? 'HTTP ' . $httpCode));
            return [
                'response'  => $response,
                'http_code' => $httpCode,
                'error'     => $apiError,
                'errno'     => 0,
                'success'   => false,
            ];
        }

        return [
            'response'  => $response,
            'http_code' => $httpCode,
            'error'     => '',
            'errno'     => 0,
            'success'   => true,
        ];
    }

    /**
     * Fetch live KES → target currency exchange rate.
     * Returns multiplier (KES_amount * rate = target_amount) or false on failure.
     */
    protected function fetchLiveRate(string $targetCurrency = 'USD')
    {
        $targetCurrency = strtoupper($targetCurrency);
        if ($targetCurrency === 'KES') return 1.0;

        $apiEndpoints = [
            // Primary: jsDelivr CDN — free, fast, very reliable, no API key needed
            'cdn' => 'https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/kes.json',
            // Fallback: ExchangeRate-API
            'erapi' => 'https://open.er-api.com/v6/latest/KES',
        ];

        foreach ($apiEndpoints as $source => $url) {
            try {
                $resp = false;

                // Method 1: file_get_contents (works when curl is restricted)
                if (function_exists('file_get_contents') && ini_get('allow_url_fopen')) {
                    $ctx = stream_context_create([
                        'http' => [
                            'timeout' => 3,
                            'method' => 'GET',
                            'header' => "User-Agent: PHP\r\n",
                        ],
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                        ],
                    ]);
                    $resp = @file_get_contents($url, false, $ctx);
                    if ($resp === false) {
                        $httpCode = 0;
                    } else {
                        $httpCode = 200;
                    }
                }

                // Method 2: curl fallback (if file_get_contents failed or wasn't available)
                if (($resp === false) && function_exists('curl_init')) {
                    $ch = curl_init($url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
                    $resp = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    if ($resp === false) $httpCode = 0;
                }

                if ($httpCode === 200 && $resp) {
                    $data = json_decode($resp, true);
                    if (!$data) continue;

                    if ($source === 'cdn') {
                        // CDN format: { "kes": { "usd": 0.0077, ... } }
                        $rate = $data['kes'][$targetCurrency] ?? null;
                    } else {
                        // ExchangeRate-API format: { "result": "success", "rates": { "USD": 0.0077, ... } }
                        if (($data['result'] ?? '') !== 'success') continue;
                        $rate = $data['rates'][$targetCurrency] ?? null;
                    }

                    if ($rate !== null && (float)$rate > 0) {
                        return (float)$rate;
                    }
                }
            } catch (\Throwable $e) {
                // Silently continue to next source
                continue;
            }
        }

        return false;
    }

    /**
     * Get PayPal exchange rate — tries live API first, falls back to saved setting.
     */
    protected function getPayPalRate(string $targetCurrency = 'USD'): array
    {
        // Try live rate from currency API
        $liveRate = $this->fetchLiveRate($targetCurrency);
        if ($liveRate !== false && $liveRate > 0) {
            return ['rate' => $liveRate, 'source' => 'live'];
        }
        // Fallback to saved rate
        $saved = (float)(Database::selectOne("SELECT value FROM settings WHERE `key` = 'paypal_exchange_rate'")['value'] ?? '0.0077');
        // Default 0.0077 means "1 KES = 0.0077 USD" (i.e., ~130 KES per USD)
        return ['rate' => $saved > 0 ? $saved : 0.0077, 'source' => 'saved'];
    }

    /**
     * Load checkout data from the current user's cart.
     * Returns: [$cartItems, $subtotal, $totalItems, $tax, $total, $couponDiscount, $shippingCost]
     */
    protected function loadCheckoutData(): array
    {
        $cartItems = Database::select("SELECT c.*, p.name, p.price, p.discount_price, p.slug, p.stock_quantity, (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1) as image FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?", [Auth::id()]);
        if (empty($cartItems)) {
            // Check if this is an API request (don't redirect, return empty)
            $uri = Request::uri();
            if (str_starts_with($uri, '/api/')) {
                return [[], 0, 0, 0, 0, 0, 0];
            }
            Redirect::to('/cart');
        }

        $subtotal = 0;
        foreach ($cartItems as &$item) {
            $effectivePrice = $item['price'];
            if (!empty($item['discount_price']) && $item['discount_price'] < $item['price']) {
                $effectivePrice = $item['discount_price'];
            }
            $item['price'] = $effectivePrice;
            $item['subtotal'] = $effectivePrice * $item['quantity'];
            $subtotal += $item['subtotal'];
        }
        unset($item);

        $totalItems = array_sum(array_column($cartItems, 'quantity'));
        $taxRate = (float)(Database::selectOne("SELECT value FROM settings WHERE `key` = 'tax_rate'")['value'] ?? 16);
        $shippingCost = 0;

        // Coupon
        $couponDiscount = 0;
        $couponCode = Session::get('applied_coupon', null);
        if ($couponCode && $subtotal > 0) {
            $coupon = Database::selectOne("SELECT * FROM coupons WHERE code = ? AND is_active = 1", [$couponCode]);
            if ($coupon) {
                $now = date('Y-m-d H:i:s');
                if ((!$coupon['valid_from'] || $now >= $coupon['valid_from']) && (!$coupon['valid_until'] || $now <= $coupon['valid_until'])
                    && ($coupon['usage_limit'] <= 0 || $coupon['used_count'] < $coupon['usage_limit'])
                    && $subtotal >= ($coupon['min_order_amount'] ?? 0)) {
                    if ($coupon['type'] === 'percentage') {
                        $couponDiscount = $subtotal * ($coupon['value'] / 100);
                    } else {
                        $couponDiscount = $coupon['value'];
                    }
                    if ($coupon['max_discount_amount'] > 0 && $couponDiscount > $coupon['max_discount_amount']) {
                        $couponDiscount = $coupon['max_discount_amount'];
                    }
                }
            }
        }

        $afterDiscount = $subtotal - $couponDiscount;
        $tax = $afterDiscount * ($taxRate / 100);
        $total = $afterDiscount + $tax + $shippingCost;

        return [$cartItems, $subtotal, $totalItems, $taxRate, $tax, $total, $couponDiscount, $shippingCost];
    }

    /**
     * Create order from cart (used by payment initiation).
     */
    protected function createOrderFromCart(): array
    {
        if (!Auth::check()) return ['success' => false, 'message' => 'Please login'];
        $shipping = Session::get('checkout_shipping', []);
        if (empty($shipping)) return ['success' => false, 'message' => 'Missing shipping info'];

        [$cartItems, $subtotal, $totalItems, $taxRate, $tax, $computedTotal, $couponDiscount, $shippingCost] = $this->loadCheckoutData();

        if (empty($cartItems)) return ['success' => false, 'message' => 'Cart is empty'];

        // Recompute discount
        $discount = 0;
        $couponCode = Session::get('applied_coupon', null);
        if ($couponCode && $subtotal > 0) {
            $coupon = Database::selectOne("SELECT * FROM coupons WHERE code = ? AND is_active = 1", [$couponCode]);
            if ($coupon) {
                $now = date('Y-m-d H:i:s');
                if ((!$coupon['valid_from'] || $now >= $coupon['valid_from']) && (!$coupon['valid_until'] || $now <= $coupon['valid_until'])
                    && ($coupon['usage_limit'] <= 0 || $coupon['used_count'] < $coupon['usage_limit'])
                    && $subtotal >= ($coupon['min_order_amount'] ?? 0)) {
                    if ($coupon['type'] === 'percentage') {
                        $discount = $subtotal * ($coupon['value'] / 100);
                    } else {
                        $discount = $coupon['value'];
                    }
                    if ($coupon['max_discount_amount'] > 0 && $discount > $coupon['max_discount_amount']) {
                        $discount = $coupon['max_discount_amount'];
                    }
                    Database::update('coupons', ['used_count' => $coupon['used_count'] + 1, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$coupon['id']]);
                    Session::remove('applied_coupon');
                }
            }
        }

        $afterDiscount = $subtotal - $discount;
        $tax = $afterDiscount * ($taxRate / 100);
        $total = $afterDiscount + $tax + $shippingCost;
        $paymentMethod = $shipping['payment_method'] ?? 'mpesa';
        $customerEmail = $shipping['email'] ?? Auth::user()['email'];
        $customerName = $shipping['name'] ?? Auth::user()['name'];
        $orderNum = 'ORD-' . strtoupper(date('ymd')) . '-' . str_pad(Database::count('orders') + 1, 4, '0', STR_PAD_LEFT);

        $orderId = Database::insert('orders', [
            'order_number' => $orderNum,
            'customer_id' => Auth::id(),
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'customer_phone' => $shipping['phone'] ?? Auth::user()['phone'] ?? '',
            'customer_address' => ($shipping['address'] ?? '') . ($shipping['city'] ? ', ' . $shipping['city'] : ''),
            'notes' => $shipping['notes'] ?? '',
            'status' => 'pending',
            'payment_method' => $paymentMethod,
            'payment_status' => 'pending',
            'subtotal' => $subtotal,
            'tax' => $tax,
            'discount' => $discount,
            'shipping_cost' => $shippingCost,
            'total' => $total,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        foreach ($cartItems as $item) {
            Database::insert('order_items', [
                'order_id' => $orderId,
                'product_id' => $item['product_id'],
                'product_name' => $item['name'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'total' => $item['price'] * $item['quantity'],
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            Database::update('products', ['stock_quantity' => $item['stock_quantity'] - $item['quantity']], 'id = ?', [$item['product_id']]);
        }

        Database::delete('cart', 'user_id = ?', [Auth::id()]);
        Session::set('last_order_id', $orderId);
        Session::set('last_order_num', $orderNum);

        // Send order confirmation email (non-blocking)
        try {
            if (class_exists('Mailer') && Mailer::isConfigured() && !empty($customerEmail)) {
                $storeName = Database::selectOne("SELECT value FROM settings WHERE `key` = 'store_name'")['value'] ?? 'ShopSmart';
                $csRow = Database::selectOne("SELECT value FROM settings WHERE `key` = 'currency_symbol'");
                $name = $customerName; $orderNumber = $orderNum; $total = $total; $currencySymbol = ($csRow && !empty($csRow['value'])) ? $csRow['value'] : 'KSh';
                $orderUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/account/orders";
                $items = Database::select("SELECT product_name, quantity, price, total FROM order_items WHERE order_id = ?", [$orderId]);
                ob_start();
                include ROOT_PATH . '/resources/views/emails/order-confirmation.php';
                $emailBody = ob_get_clean();
                Mailer::send($customerEmail, "Order Confirmation - {$orderNum}", $emailBody);
            }
        } catch (\Throwable $e) {
            error_log('[EMAIL] Order confirmation failed: ' . $e->getMessage());
        }

        return ['success' => true, 'order_id' => $orderId, 'order_number' => $orderNum, 'total' => $total, 'payment_method' => $paymentMethod];
    }

    /**
     * Mark an order as failed.
     */
    protected function markFailed(int $orderId, string $reason = ''): void
    {
        Database::update('orders', [
            'status' => 'failed',
            'payment_status' => 'failed',
            'notes' => 'Payment failed: ' . $reason,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$orderId]);
    }

    // ============================================================
    // Route Methods
    // ============================================================

    /**
     * POST /payment/initiate
     * Initiate payment from checkout — creates order then redirects to payment gateway.
     */
    public function initiate(): void
    {
        header('Content-Type: application/json');

        try {
        if (!Auth::check()) { $this->posJson(['success' => false, 'message' => 'Please login']); return; }

        $paymentMethod = Request::post('payment_method', Session::get('checkout_shipping')['payment_method'] ?? 'mpesa');

        // Check if payment method is enabled
        $enabled = Database::selectOne("SELECT value FROM settings WHERE `key` = ?", [$paymentMethod . '_enabled']);
        if (!$enabled || $enabled['value'] !== '1') {
            $this->posJson(['success' => false, 'message' => ucfirst($paymentMethod) . ' is not available']);
            return;
        }

        // Create the order
        $result = $this->createOrderFromCart();
        if (!$result['success']) {
            $this->posJson($result);
            return;
        }

        $orderId = $result['order_id'];
        $orderTotal = (float)($result['total'] ?? 0);
        $orderNum = $result['order_number'];

        // Validate order total
        if ($orderTotal <= 0) {
            $this->markFailed($orderId, 'Invalid order total: ' . $orderTotal);
            $this->posJson(['success' => false, 'message' => 'Invalid order total. Please contact support.']);
            return;
        }

        $siteUrl = $this->getSiteUrl();

        // Initiate based on payment method
        switch ($paymentMethod) {
            case 'mpesa':
                $phone = Request::post('mpesa_phone', '');
                // Normalize phone number
                $phone = preg_replace('/\s+/', '', $phone);
                if (strpos($phone, '0') === 0) $phone = '+254' . substr($phone, 1);
                if (strpos($phone, '+') !== 0) $phone = '+' . $phone;

                $mpesaPasskey = $this->getSetting('mpesa_passkey');
                $mpesaEnv = $this->getSetting('mpesa_env') ?: 'sandbox';
                $shortCode = $this->getSetting('mpesa_shortcode') ?: '174379';

                // Store phone on order for reference
                Database::update('orders', ['customer_phone' => $phone], 'id = ?', [$orderId]);

                // If credentials are configured, attempt real STK push
                if (!empty($mpesaPasskey) && $mpesaPasskey !== '') {
                    $timestamp = date('YmdHis');
                    $password = base64_encode($shortCode . $mpesaPasskey . $timestamp);
                    $endpoint = $mpesaEnv === 'production'
                        ? 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
                        : 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

                    // Attempt to get access token
                    $consumerKey = $this->getSetting('mpesa_consumer_key');
                    $consumerSecret = $this->getSetting('mpesa_consumer_secret');

                    $accessToken = '';
                    if (!empty($consumerKey) && !empty($consumerSecret)) {
                        $ch = curl_init($endpoint);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json', 'Authorization: Basic ' . base64_encode($consumerKey . ':' . $consumerSecret)]);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                        $response = curl_exec($ch);
                        curl_close($ch);
                        $tokenData = json_decode($response, true);
                        $accessToken = $tokenData['access_token'] ?? '';
                    }

                    if (!empty($accessToken)) {
                        $stkEndpoint = $mpesaEnv === 'production'
                            ? 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest'
                            : 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

                        $savedCallback = $this->getSetting('mpesa_callback');
                        $callbackUrl = !empty($savedCallback) ? $savedCallback : $siteUrl . '/payment/mpesa/callback';

                        $stkBody = json_encode([
                            'BusinessShortCode' => $shortCode,
                            'Password' => $password,
                            'Timestamp' => $timestamp,
                            'TransactionType' => 'CustomerPayBillOnline',
                            'Amount' => (int)round($orderTotal),
                            'PartyA' => preg_replace('/[^0-9]/', '', $phone),
                            'PartyB' => $shortCode,
                            'PhoneNumber' => preg_replace('/[^0-9]/', '', $phone),
                            'CallBackURL' => $callbackUrl,
                            'AccountReference' => $orderNum,
                            'TransactionDesc' => 'Order ' . $orderNum,
                        ]);

                        $ch = curl_init($stkEndpoint);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json', 'Authorization: Bearer ' . $accessToken]);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $stkBody);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                        $stkResponse = curl_exec($ch);
                        curl_close($ch);

                        $stkData = json_decode($stkResponse, true);
                        if (isset($stkData['ResponseCode']) && $stkData['ResponseCode'] === '0') {
                            // STK Push initiated successfully
                            Database::update('orders', [
                                'payment_reference' => $stkData['CheckoutRequestID'] ?? '',
                                'updated_at' => date('Y-m-d H:i:s'),
                            ], 'id = ?', [$orderId]);
                            Session::remove('checkout_shipping');
                            $this->posJson(['success' => true, 'order_id' => $orderId, 'message' => 'STK Push initiated']);
                            return;
                        }
                        // STK Push returned error
                        $this->markFailed($orderId, 'STK Push failed: ' . ($stkData['errorMessage'] ?? $stkData['ResponseDescription'] ?? json_encode($stkData)));
                        $this->posJson(['success' => false, 'message' => 'STK Push failed: ' . ($stkData['errorMessage'] ?? $stkData['ResponseDescription'] ?? 'Unknown error')]);
                        return;
                    }
                    // OAuth failed
                    $this->markFailed($orderId, 'M-Pesa authentication failed');
                    $this->posJson(['success' => false, 'message' => 'M-Pesa authentication failed. Check consumer key and secret.']);
                    return;
                }

                // No credentials — keep as pending (user can pay later)
                Database::update('orders', ['notes' => 'Payment pending: M-Pesa not configured', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$orderId]);
                Session::remove('checkout_shipping');
                $this->posJson(['success' => true, 'order_id' => $orderId, 'pending' => true, 'message' => 'Order placed. Payment is pending — M-Pesa is not configured.']);
                return;

            case 'intasend':
                $intasendSecret = $this->getSetting('intasend_secret');
                $intasendPubKey = $this->getSetting('intasend_publishable');
                if (!empty($intasendSecret) && !empty($intasendPubKey)) {
                    $ch = curl_init('https://payment.intasend.com/api/v1/checkout/');
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Content-Type:application/json',
                        'Authorization: Bearer ' . $intasendSecret,
                        'X-IntaSend-Public-Key: ' . $intasendPubKey,
                    ]);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                        'first_name' => explode(' ', Session::get('checkout_shipping')['name'] ?? 'Customer')[0],
                        'last_name' => explode(' ', Session::get('checkout_shipping')['name'] ?? 'Customer')[1] ?? '',
                        'email' => Session::get('checkout_shipping')['email'] ?? '',
                        'phone_number' => preg_replace('/[^0-9]/', '', Session::get('checkout_shipping')['phone'] ?? ''),
                        'amount' => round($orderTotal, 2),
                        'currency' => 'KES',
                        'api_ref' => $orderNum,
                        'redirect_url' => $siteUrl . '/payment/intasend/callback?order_id=' . $orderId,
                    ]));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    $resp = curl_exec($ch);
                    curl_close($ch);
                    $data = json_decode($resp, true);
                    if (isset($data['url'])) {
                        Database::update('orders', ['payment_reference' => $data['invoice_id'] ?? '', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$orderId]);
                        Session::remove('checkout_shipping');
                        $this->posJson(['success' => true, 'order_id' => $orderId, 'redirect_url' => $data['url']]);
                        return;
                    }
                    $this->markFailed($orderId, 'IntaSend: ' . ($data['message'] ?? json_encode($data)));
                    $this->posJson(['success' => false, 'message' => 'IntaSend error: ' . ($data['message'] ?? json_encode($data))]);
                    return;
                }
                // No credentials — keep as pending (user can pay later)
                Database::update('orders', ['notes' => 'Payment pending: IntaSend not configured', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$orderId]);
                Session::remove('checkout_shipping');
                $this->posJson(['success' => true, 'order_id' => $orderId, 'pending' => true, 'message' => 'Order placed. Payment is pending — IntaSend is not configured.']);
                return;

            case 'paypal':
                $ppClientId = $this->getSetting('paypal_client_id');
                $ppSecret = $this->getSetting('paypal_secret');
                $ppEnv = $this->getSetting('paypal_env') ?: 'sandbox';
                $ppCurrency = $this->getSetting('paypal_currency') ?: 'USD';
                $rateInfo = $this->getPayPalRate($ppCurrency);
                $ppRate = $rateInfo['rate'];
                $rateSource = $rateInfo['source'];

                if (!empty($ppClientId) && !empty($ppSecret)) {
                    $ppBase = $ppEnv === 'production'
                        ? 'https://api-m.paypal.com'
                        : 'https://api-m.sandbox.paypal.com';

                    // Step 1: Get access token
                    $tokenResult = $this->curlRequest($ppBase . '/v1/oauth2/token', [
                        CURLOPT_USERPWD    => $ppClientId . ':' . $ppSecret,
                        CURLOPT_POST       => true,
                        CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
                    ]);

                    if (!$tokenResult['success']) {
                        $this->markFailed($orderId, 'PayPal network error: ' . $tokenResult['error']);
                        $this->posJson(['success' => false, 'message' => 'Cannot reach PayPal servers. Your hosting may block outgoing connections. Error: ' . $tokenResult['error']]);
                        return;
                    }

                    $tokenData = json_decode($tokenResult['response'], true);
                    $accessToken = $tokenData['access_token'] ?? '';

                    if (empty($accessToken)) {
                        $authErr = $tokenData['error_description'] ?? ($tokenData['error'] ?? 'Invalid credentials');
                        $this->markFailed($orderId, 'PayPal auth failed: ' . $authErr);
                        $this->posJson(['success' => false, 'message' => 'PayPal authentication failed: ' . $authErr . '. Check client ID and secret.']);
                        return;
                    }

                    // Step 2: Create PayPal order with converted currency
                    $convertedAmount = round($orderTotal * $ppRate, 2);
                    if ($convertedAmount < 1) $convertedAmount = 1.00; // PayPal minimum

                    $successUrl = $siteUrl . '/payment/paypal/callback?order_id=' . $orderId;
                    $cancelUrl = $siteUrl . '/checkout/payment';

                    $orderResult = $this->curlRequest($ppBase . '/v2/checkout/orders', [
                        CURLOPT_HTTPHEADER  => [
                            'Content-Type: application/json',
                            'Authorization: Bearer ' . $accessToken,
                        ],
                        CURLOPT_POST       => true,
                        CURLOPT_POSTFIELDS => json_encode([
                            'intent' => 'CAPTURE',
                            'purchase_units' => [[
                                'reference_id' => $orderNum,
                                'description' => 'Order ' . $orderNum . ' (KSh ' . number_format($orderTotal, 2) . ')',
                                'amount' => [
                                    'currency_code' => $ppCurrency,
                                    'value' => (string)$convertedAmount,
                                    'breakdown' => [
                                        'item_total' => [
                                            'currency_code' => $ppCurrency,
                                            'value' => (string)$convertedAmount,
                                        ],
                                    ],
                                ],
                            ]],
                            'application_context' => [
                                'brand_name' => $this->getSetting('store_name') ?: 'ShopSmart',
                                'user_action' => 'CONTINUE',
                                'return_url' => $successUrl,
                                'cancel_url' => $cancelUrl,
                            ],
                        ]),
                    ]);

                    if (!$orderResult['success']) {
                        $this->markFailed($orderId, 'PayPal network error: ' . $orderResult['error']);
                        $this->posJson(['success' => false, 'message' => 'Cannot reach PayPal to create order. Error: ' . $orderResult['error']]);
                        return;
                    }

                    $ppOrder = json_decode($orderResult['response'], true);

                    if (isset($ppOrder['links'])) {
                        foreach ($ppOrder['links'] as $link) {
                            if (($link['rel'] ?? '') === 'approve') {
                                Database::update('orders', [
                                    'payment_reference' => $ppOrder['id'] ?? '',
                                    'notes' => 'PayPal order: KSh ' . number_format($orderTotal, 2) . ' → ' . $ppCurrency . ' ' . $convertedAmount . ' (rate: ' . $ppRate . ', ' . $rateSource . ')',
                                    'updated_at' => date('Y-m-d H:i:s'),
                                ], 'id = ?', [$orderId]);
                                Session::remove('checkout_shipping');
                                $this->posJson(['success' => true, 'order_id' => $orderId, 'redirect_url' => $link['href']]);
                                return;
                            }
                        }
                    }
                    $errMsg = $ppOrder['message'] ?? ($ppOrder['details'][0]['description'] ?? 'Could not create order');
                    $this->markFailed($orderId, 'PayPal: ' . $errMsg);
                    $this->posJson(['success' => false, 'message' => 'PayPal error: ' . $errMsg]);
                    return;
                }
                // No credentials — keep as pending (user can pay later)
                Database::update('orders', ['notes' => 'Payment pending: PayPal not configured', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$orderId]);
                Session::remove('checkout_shipping');
                $this->posJson(['success' => true, 'order_id' => $orderId, 'pending' => true, 'message' => 'Order placed. Payment is pending — PayPal is not configured.']);
                return;

            case 'pesapal':
                $ppalConsumerKey = $this->getSetting('pesapal_key');
                $ppalConsumerSecret = $this->getSetting('pesapal_secret');
                $ppalEnv = $this->getSetting('pesapal_env') ?: 'sandbox';
                $ppalIpnId = $this->getSetting('pesapal_ipn_id');
                if (!empty($ppalConsumerKey) && !empty($ppalConsumerSecret)) {
                    $ppalBase = $ppalEnv === 'production'
                        ? 'https://pay.pesapal.com'
                        : 'https://cybqa.pesapal.com';
                    $ch = curl_init($ppalBase . '/api/Auth/RequestToken');
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/json',
                        'Authorization: Basic ' . base64_encode($ppalConsumerKey . ':' . $ppalConsumerSecret),
                    ]);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    $ppalTokenResp = curl_exec($ch);
                    curl_close($ch);
                    $ppalTokenData = json_decode($ppalTokenResp, true);
                    $ppalToken = $ppalTokenData['token'] ?? '';

                    if (!empty($ppalToken)) {
                        $callbackUrl = $siteUrl . '/payment/pesapal/callback?order_id=' . $orderId;
                        $shipping = Session::get('checkout_shipping', []);
                        $ch = curl_init($ppalBase . '/api/Transactions/SubmitOrderRequest');
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            'Content-Type: application/json',
                            'Authorization: Bearer ' . $ppalToken,
                        ]);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                            'id' => $orderNum,
                            'currency' => 'KES',
                            'amount' => round($orderTotal, 2),
                            'description' => 'Order ' . $orderNum,
                            'callback_url' => $callbackUrl,
                            'notification_id' => $ppalIpnId,
                            'billing_address' => [
                                'email_address' => $shipping['email'] ?? '',
                                'phone_number' => preg_replace('/[^0-9]/', '', $shipping['phone'] ?? ''),
                                'first_name' => explode(' ', $shipping['name'] ?? 'Customer')[0],
                                'last_name' => explode(' ', $shipping['name'] ?? 'Customer')[1] ?? '',
                                'country' => 'Kenya',
                            ],
                        ]));
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                        $ppalOrderResp = curl_exec($ch);
                        curl_close($ch);
                        $ppalOrderData = json_decode($ppalOrderResp, true);

                        if (!empty($ppalOrderData['redirect_url'])) {
                            Database::update('orders', ['payment_reference' => $ppalOrderData['order_tracking_id'] ?? '', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$orderId]);
                            Session::remove('checkout_shipping');
                            $this->posJson(['success' => true, 'order_id' => $orderId, 'redirect_url' => $ppalOrderData['redirect_url']]);
                            return;
                        }
                        $this->markFailed($orderId, 'PesaPal: ' . ($ppalOrderData['error']['message'] ?? json_encode($ppalOrderData)));
                        $this->posJson(['success' => false, 'message' => 'PesaPal error: ' . ($ppalOrderData['error']['message'] ?? json_encode($ppalOrderData))]);
                        return;
                    }
                    $this->markFailed($orderId, 'PesaPal authentication failed');
                    $this->posJson(['success' => false, 'message' => 'PesaPal authentication failed. Check consumer key and secret.']);
                    return;
                }
                // No credentials — keep as pending (user can pay later)
                Database::update('orders', ['notes' => 'Payment pending: PesaPal not configured', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$orderId]);
                Session::remove('checkout_shipping');
                $this->posJson(['success' => true, 'order_id' => $orderId, 'pending' => true, 'message' => 'Order placed. Payment is pending — PesaPal is not configured.']);
                return;

            case 'stripe':
                $stripeSecret = $this->getSetting('stripe_secret');
                if (!empty($stripeSecret)) {
                    $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $stripeSecret]);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                        'payment_method_types[]' => 'card',
                        'line_items[0][price_data][currency]' => 'kes',
                        'line_items[0][price_data][product_data][name]' => 'Order ' . $orderNum,
                        'line_items[0][price_data][unit_amount]' => (int)round($orderTotal * 100),
                        'line_items[0][quantity]' => 1,
                        'mode' => 'payment',
                        'success_url' => $siteUrl . '/payment/stripe/success?order_id=' . $orderId,
                        'cancel_url' => $siteUrl . '/checkout/payment',
                    ]));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    $resp = curl_exec($ch);
                    curl_close($ch);
                    $session = json_decode($resp, true);
                    if (isset($session['url'])) {
                        Database::update('orders', ['payment_reference' => $session['id'] ?? '', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$orderId]);
                        Session::remove('checkout_shipping');
                        $this->posJson(['success' => true, 'order_id' => $orderId, 'redirect_url' => $session['url']]);
                        return;
                    }
                    $this->markFailed($orderId, 'Stripe: ' . ($session['error']['message'] ?? json_encode($session)));
                    $this->posJson(['success' => false, 'message' => 'Stripe error: ' . ($session['error']['message'] ?? json_encode($session))]);
                    return;
                }
                // No credentials — keep as pending (user can pay later)
                Database::update('orders', ['notes' => 'Payment pending: Stripe not configured', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$orderId]);
                Session::remove('checkout_shipping');
                $this->posJson(['success' => true, 'order_id' => $orderId, 'pending' => true, 'message' => 'Order placed. Payment is pending — Stripe is not configured.']);
                return;

            default:
                $this->posJson(['success' => false, 'message' => 'Unsupported payment method']);
                return;
        }
        } catch (\Throwable $e) {
            if (isset($orderId)) $this->markFailed($orderId, 'Exception: ' . $e->getMessage());
            $this->posJson(['success' => false, 'message' => 'Payment error: ' . $e->getMessage()]);
        }
    }

    /**
     * POST /order/pay/{id}
     * Re-initiate payment for an existing pending order.
     */
    public function initiateOrderPay(int $id): void
    {
        header('Content-Type: application/json');
        if (!Auth::check()) { $this->posJson(['success' => false, 'message' => 'Please login']); return; }

        $order = Database::selectOne("SELECT * FROM orders WHERE id = ? AND customer_id = ?", [$id, Auth::id()]);
        if (!$order) { $this->posJson(['success' => false, 'message' => 'Order not found']); return; }
        if ($order['payment_status'] !== 'pending') { $this->posJson(['success' => false, 'message' => 'This order is not pending payment']); return; }

        $orderId = $order['id'];
        $orderTotal = (float)($order['total'] ?? 0);
        $orderNum = $order['order_number'];
        $paymentMethod = Request::post('payment_method', $order['payment_method'] ?? 'mpesa');

        // Validate order total — use order's DB total as source of truth
        if ($orderTotal <= 0) {
            $this->posJson(['success' => false, 'message' => 'Invalid order amount. Please contact support.']);
            return;
        }

        // If frontend sends an amount, validate it matches (within 1 KES tolerance for rounding)
        $sentAmount = (float)(Request::post('amount', 0));
        if ($sentAmount > 0 && abs($sentAmount - $orderTotal) > 1) {
            @file_put_contents(ROOT_PATH . '/storage/logs/payment-amount-warn.log',
                date('Y-m-d H:i:s') . " | ORDER_PAY AMOUNT_MISMATCH | order_id={$orderId} | sent={$sentAmount} | db_total={$orderTotal} | method={$paymentMethod}\n",
                FILE_APPEND);
        }
        // Always use the order's DB total as the authoritative amount

        try {
        // Update the payment method on the order in case user switches
        Database::update('orders', ['payment_method' => $paymentMethod, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$orderId]);

        $siteUrl = $this->getSiteUrl();

        switch ($paymentMethod) {
            case 'mpesa':
                $phone = Request::post('mpesa_phone', $order['customer_phone'] ?? '');
                $phone = preg_replace('/\s+/', '', $phone);
                if (strpos($phone, '0') === 0) $phone = '+254' . substr($phone, 1);
                if (strpos($phone, '+') !== 0) $phone = '+' . $phone;

                $mpesaPasskey = $this->getSetting('mpesa_passkey');
                $mpesaEnv = $this->getSetting('mpesa_env') ?: 'sandbox';
                $shortCode = $this->getSetting('mpesa_shortcode') ?: '174379';

                Database::update('orders', ['customer_phone' => $phone], 'id = ?', [$orderId]);

                if (!empty($mpesaPasskey) && $mpesaPasskey !== '') {
                    $timestamp = date('YmdHis');
                    $password = base64_encode($shortCode . $mpesaPasskey . $timestamp);
                    $endpoint = $mpesaEnv === 'production'
                        ? 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
                        : 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

                    $consumerKey = $this->getSetting('mpesa_consumer_key');
                    $consumerSecret = $this->getSetting('mpesa_consumer_secret');

                    $accessToken = '';
                    if (!empty($consumerKey) && !empty($consumerSecret)) {
                        $ch = curl_init($endpoint);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json', 'Authorization: Basic ' . base64_encode($consumerKey . ':' . $consumerSecret)]);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                        $response = curl_exec($ch);
                        curl_close($ch);
                        $tokenData = json_decode($response, true);
                        $accessToken = $tokenData['access_token'] ?? '';
                    }

                    if (!empty($accessToken)) {
                        $stkEndpoint = $mpesaEnv === 'production'
                            ? 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest'
                            : 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

                        $savedCallback = $this->getSetting('mpesa_callback');
                        $callbackUrl = !empty($savedCallback) ? $savedCallback : $siteUrl . '/payment/mpesa/callback';

                        $stkBody = json_encode([
                            'BusinessShortCode' => $shortCode,
                            'Password' => $password,
                            'Timestamp' => $timestamp,
                            'TransactionType' => 'CustomerPayBillOnline',
                            'Amount' => (int)round($orderTotal),
                            'PartyA' => preg_replace('/[^0-9]/', '', $phone),
                            'PartyB' => $shortCode,
                            'PhoneNumber' => preg_replace('/[^0-9]/', '', $phone),
                            'CallBackURL' => $callbackUrl,
                            'AccountReference' => $orderNum,
                            'TransactionDesc' => 'Order ' . $orderNum,
                        ]);

                        $ch = curl_init($stkEndpoint);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json', 'Authorization: Bearer ' . $accessToken]);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $stkBody);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                        $stkResponse = curl_exec($ch);
                        curl_close($ch);

                        $stkData = json_decode($stkResponse, true);
                        if (isset($stkData['ResponseCode']) && $stkData['ResponseCode'] === '0') {
                            Database::update('orders', [
                                'payment_reference' => $stkData['CheckoutRequestID'] ?? '',
                                'status' => 'pending',
                                'updated_at' => date('Y-m-d H:i:s'),
                            ], 'id = ?', [$orderId]);
                            $this->posJson(['success' => true, 'order_id' => $orderId, 'message' => 'STK Push initiated']);
                            return;
                        }
                        $this->markFailed($orderId, 'STK Push failed: ' . ($stkData['errorMessage'] ?? $stkData['ResponseDescription'] ?? json_encode($stkData)));
                        $this->posJson(['success' => false, 'message' => 'STK Push failed: ' . ($stkData['errorMessage'] ?? $stkData['ResponseDescription'] ?? 'Unknown error')]);
                        return;
                    }
                    $this->markFailed($orderId, 'M-Pesa authentication failed');
                    $this->posJson(['success' => false, 'message' => 'M-Pesa authentication failed. Check consumer key and secret.']);
                    return;
                }
                // No credentials
                $this->posJson(['success' => false, 'message' => 'M-Pesa is not configured. Please contact admin.']);
                return;

            case 'intasend':
                $intasendSecret = $this->getSetting('intasend_secret');
                $intasendPubKey = $this->getSetting('intasend_publishable');
                if (!empty($intasendSecret) && !empty($intasendPubKey)) {
                    $ch = curl_init('https://payment.intasend.com/api/v1/checkout/');
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Content-Type:application/json',
                        'Authorization: Bearer ' . $intasendSecret,
                        'X-IntaSend-Public-Key: ' . $intasendPubKey,
                    ]);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                        'first_name' => explode(' ', $order['customer_name'] ?? 'Customer')[0],
                        'last_name' => explode(' ', $order['customer_name'] ?? 'Customer')[1] ?? '',
                        'email' => $order['customer_email'] ?? '',
                        'phone_number' => preg_replace('/[^0-9]/', '', $order['customer_phone'] ?? ''),
                        'amount' => round($orderTotal),
                        'currency' => 'KES',
                        'api_ref' => $orderNum,
                        'redirect_url' => $siteUrl . '/payment/intasend/callback?order_id=' . $orderId,
                    ]));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    $resp = curl_exec($ch);
                    curl_close($ch);
                    $data = json_decode($resp, true);
                    if (isset($data['url'])) {
                        Database::update('orders', ['payment_reference' => $data['invoice_id'] ?? '', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$orderId]);
                        $this->posJson(['success' => true, 'order_id' => $orderId, 'redirect_url' => $data['url']]);
                        return;
                    }
                    $this->markFailed($orderId, 'IntaSend: ' . ($data['message'] ?? json_encode($data)));
                    $this->posJson(['success' => false, 'message' => 'IntaSend error: ' . ($data['message'] ?? json_encode($data))]);
                    return;
                }
                $this->posJson(['success' => false, 'message' => 'IntaSend is not configured. Please contact admin.']);
                return;

            case 'paypal':
                $ppClientId = $this->getSetting('paypal_client_id');
                $ppSecret = $this->getSetting('paypal_secret');
                $ppEnv = $this->getSetting('paypal_env') ?: 'sandbox';
                $ppCurrency = $this->getSetting('paypal_currency') ?: 'USD';
                $rateInfo = $this->getPayPalRate($ppCurrency);
                $ppRate = $rateInfo['rate'];
                $rateSource = $rateInfo['source'];

                if (!empty($ppClientId) && !empty($ppSecret)) {
                    $ppBase = $ppEnv === 'production' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

                    // Step 1: Get access token
                    $tokenResult = $this->curlRequest($ppBase . '/v1/oauth2/token', [
                        CURLOPT_USERPWD    => $ppClientId . ':' . $ppSecret,
                        CURLOPT_POST       => true,
                        CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
                    ]);

                    if (!$tokenResult['success']) {
                        $this->markFailed($orderId, 'PayPal network error: ' . $tokenResult['error']);
                        $this->posJson(['success' => false, 'message' => 'Cannot reach PayPal servers. Your hosting may block outgoing connections. Error: ' . $tokenResult['error']]);
                        return;
                    }

                    $tokenData = json_decode($tokenResult['response'], true);
                    $accessToken = $tokenData['access_token'] ?? '';

                    if (empty($accessToken)) {
                        $authErr = $tokenData['error_description'] ?? ($tokenData['error'] ?? 'Invalid credentials');
                        $this->markFailed($orderId, 'PayPal auth failed: ' . $authErr);
                        $this->posJson(['success' => false, 'message' => 'PayPal authentication failed: ' . $authErr]);
                        return;
                    }

                    // Step 2: Create PayPal order
                    $convertedAmount = round($orderTotal * $ppRate, 2);
                    if ($convertedAmount < 1) $convertedAmount = 1.00;
                    $successUrl = $siteUrl . '/payment/paypal/callback?order_id=' . $orderId;
                    $cancelUrl = $siteUrl . '/account/orders';

                    $orderResult = $this->curlRequest($ppBase . '/v2/checkout/orders', [
                        CURLOPT_HTTPHEADER  => ['Content-Type: application/json', 'Authorization: Bearer ' . $accessToken],
                        CURLOPT_POST       => true,
                        CURLOPT_POSTFIELDS => json_encode([
                            'intent' => 'CAPTURE',
                            'purchase_units' => [[
                                'reference_id' => $orderNum,
                                'description' => 'Order ' . $orderNum . ' (KSh ' . number_format($orderTotal, 2) . ')',
                                'amount' => ['currency_code' => $ppCurrency, 'value' => (string)$convertedAmount, 'breakdown' => ['item_total' => ['currency_code' => $ppCurrency, 'value' => (string)$convertedAmount]]],
                            ]],
                            'application_context' => [
                                'brand_name' => $this->getSetting('store_name') ?: 'ShopSmart',
                                'user_action' => 'CONTINUE',
                                'return_url' => $successUrl,
                                'cancel_url' => $cancelUrl,
                            ],
                        ]),
                    ]);

                    if (!$orderResult['success']) {
                        $this->markFailed($orderId, 'PayPal network error: ' . $orderResult['error']);
                        $this->posJson(['success' => false, 'message' => 'Cannot reach PayPal to create order. Error: ' . $orderResult['error']]);
                        return;
                    }

                    $ppOrder = json_decode($orderResult['response'], true);

                    if (isset($ppOrder['links'])) {
                        foreach ($ppOrder['links'] as $link) {
                            if (($link['rel'] ?? '') === 'approve') {
                                Database::update('orders', ['payment_reference' => $ppOrder['id'] ?? '', 'notes' => 'PayPal order: KSh ' . number_format($orderTotal, 2) . ' → ' . $ppCurrency . ' ' . $convertedAmount, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$orderId]);
                                $this->posJson(['success' => true, 'order_id' => $orderId, 'redirect_url' => $link['href']]);
                                return;
                            }
                        }
                    }
                    $errMsg = $ppOrder['message'] ?? ($ppOrder['details'][0]['description'] ?? 'Could not create order');
                    $this->markFailed($orderId, 'PayPal: ' . $errMsg);
                    $this->posJson(['success' => false, 'message' => 'PayPal error: ' . $errMsg]);
                    return;
                }
                $this->posJson(['success' => false, 'message' => 'PayPal is not configured. Please contact admin.']);
                return;

            case 'pesapal':
                $ppalConsumerKey = $this->getSetting('pesapal_key');
                $ppalConsumerSecret = $this->getSetting('pesapal_secret');
                $ppalEnv = $this->getSetting('pesapal_env') ?: 'sandbox';
                $ppalIpnId = $this->getSetting('pesapal_ipn_id');
                if (!empty($ppalConsumerKey) && !empty($ppalConsumerSecret)) {
                    $ppalBase = $ppalEnv === 'production' ? 'https://pay.pesapal.com' : 'https://cybqa.pesapal.com';
                    $ch = curl_init($ppalBase . '/api/Auth/RequestToken');
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Basic ' . base64_encode($ppalConsumerKey . ':' . $ppalConsumerSecret)]);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    $ppalTokenResp = curl_exec($ch);
                    curl_close($ch);
                    $ppalTokenData = json_decode($ppalTokenResp, true);
                    $ppalToken = $ppalTokenData['token'] ?? '';

                    if (!empty($ppalToken)) {
                        $callbackUrl = $siteUrl . '/payment/pesapal/callback?order_id=' . $orderId;
                        $ch = curl_init($ppalBase . '/api/Transactions/SubmitOrderRequest');
                        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $ppalToken]);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                            'id' => $orderNum,
                            'currency' => 'KES',
                            'amount' => round($orderTotal, 2),
                            'description' => 'Order ' . $orderNum,
                            'callback_url' => $callbackUrl,
                            'notification_id' => $ppalIpnId,
                            'billing_address' => [
                                'email_address' => $order['customer_email'] ?? '',
                                'phone_number' => preg_replace('/[^0-9]/', '', $order['customer_phone'] ?? ''),
                                'first_name' => explode(' ', $order['customer_name'] ?? 'Customer')[0],
                                'last_name' => explode(' ', $order['customer_name'] ?? 'Customer')[1] ?? '',
                                'country' => 'Kenya',
                            ],
                        ]));
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                        $ppalOrderResp = curl_exec($ch);
                        curl_close($ch);
                        $ppalOrderData = json_decode($ppalOrderResp, true);

                        if (!empty($ppalOrderData['redirect_url'])) {
                            Database::update('orders', ['payment_reference' => $ppalOrderData['order_tracking_id'] ?? '', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$orderId]);
                            $this->posJson(['success' => true, 'order_id' => $orderId, 'redirect_url' => $ppalOrderData['redirect_url']]);
                            return;
                        }
                        $this->markFailed($orderId, 'PesaPal: ' . ($ppalOrderData['error']['message'] ?? json_encode($ppalOrderData)));
                        $this->posJson(['success' => false, 'message' => 'PesaPal error: ' . ($ppalOrderData['error']['message'] ?? json_encode($ppalOrderData))]);
                        return;
                    }
                    $this->markFailed($orderId, 'PesaPal authentication failed');
                    $this->posJson(['success' => false, 'message' => 'PesaPal authentication failed']);
                    return;
                }
                $this->posJson(['success' => false, 'message' => 'PesaPal is not configured. Please contact admin.']);
                return;

            case 'stripe':
                $stripeSecret = $this->getSetting('stripe_secret');
                if (!empty($stripeSecret)) {
                    $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $stripeSecret]);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                        'payment_method_types[]' => 'card',
                        'line_items[0][price_data][currency]' => 'kes',
                        'line_items[0][price_data][product_data][name]' => 'Order ' . $orderNum,
                        'line_items[0][price_data][unit_amount]' => (int)round($orderTotal * 100),
                        'line_items[0][quantity]' => 1,
                        'mode' => 'payment',
                        'success_url' => $siteUrl . '/payment/stripe/success?order_id=' . $orderId,
                        'cancel_url' => $siteUrl . '/account/orders',
                    ]));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    $resp = curl_exec($ch);
                    curl_close($ch);
                    $session = json_decode($resp, true);
                    if (isset($session['url'])) {
                        Database::update('orders', ['payment_reference' => $session['id'] ?? '', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$orderId]);
                        $this->posJson(['success' => true, 'order_id' => $orderId, 'redirect_url' => $session['url']]);
                        return;
                    }
                    $this->markFailed($orderId, 'Stripe: ' . ($session['error']['message'] ?? json_encode($session)));
                    $this->posJson(['success' => false, 'message' => 'Stripe error: ' . ($session['error']['message'] ?? json_encode($session))]);
                    return;
                }
                $this->posJson(['success' => false, 'message' => 'Stripe is not configured. Please contact admin.']);
                return;

            default:
                $this->posJson(['success' => false, 'message' => 'Unsupported payment method']);
                return;
        }
        } catch (\Throwable $e) {
            if (isset($orderId)) $this->markFailed($orderId, 'Exception: ' . $e->getMessage());
            $this->posJson(['success' => false, 'message' => 'Payment error: ' . $e->getMessage()]);
        }
    }

    /**
     * GET /order/pay/{id}
     * Display the order pay page for a pending order.
     */
    public function showOrderPay(int $id): void
    {
        if (!Auth::check()) Redirect::to('/login');
        $order = Database::selectOne("SELECT * FROM orders WHERE id = ? AND customer_id = ?", [$id, Auth::id()]);
        if (!$order) { http_response_code(404); View::render('errors/404'); return; }
        if ($order['payment_status'] !== 'pending') { Session::flash('error', 'This order is not pending payment'); Redirect::to('/account/orders'); }
        $orderItems = Database::select("SELECT * FROM order_items WHERE order_id = ?", [$id]);
        ob_start();
        include ROOT_PATH . '/resources/views/customer/order-pay.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/app.php';
    }

    /**
     * GET /payment/status
     * Check payment status (for polling).
     */
    public function status(): void
    {
        header('Content-Type: application/json');
        $orderId = (int)Request::query('order_id', 0);
        if (!$orderId) { echo json_encode(['paid' => false]); return; }
        $order = Database::selectOne("SELECT payment_status FROM orders WHERE id = ?", [$orderId]);
        echo json_encode(['paid' => ($order['payment_status'] ?? 'pending') === 'paid']);
    }

    /**
     * POST /payment/mpesa/callback
     * M-Pesa STK push webhook from Safaricom.
     */
    public function mpesaCallback(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $body = $input['Body']['stkCallback'] ?? null;
        if (!$body) return;

        $resultCode = $body['ResultCode'] ?? 1;
        $checkoutId = $body['CheckoutRequestID'] ?? '';
        $merchantReqId = $body['MerchantRequestID'] ?? '';

        if ($resultCode === 0) {
            $metadata = $body['CallbackMetadata'] ?? [];
            $items = $metadata['Item'] ?? [];
            $mpesaRef = '';
            $amount = 0;
            $phone = '';
            foreach ($items as $item) {
                if (($item['Name'] ?? '') === 'MpesaReceiptNumber') $mpesaRef = $item['Value'] ?? '';
                if (($item['Name'] ?? '') === 'Amount') $amount = $item['Value'] ?? 0;
                if (($item['Name'] ?? '') === 'PhoneNumber') $phone = $item['Value'] ?? '';
            }
            $order = Database::selectOne("SELECT id FROM orders WHERE payment_reference = ?", [$checkoutId]);
            if ($order) {
                Database::update('orders', [
                    'payment_status' => 'paid',
                    'status' => 'processing',
                    'payment_reference' => $mpesaRef ?: $checkoutId,
                    'updated_at' => date('Y-m-d H:i:s'),
                ], 'id = ?', [$order['id']]);
                $this->processReferralCommission($order['id']);
            }
        } else {
            $order = Database::selectOne("SELECT id FROM orders WHERE payment_reference = ?", [$checkoutId]);
            if ($order) {
                Database::update('orders', [
                    'payment_status' => 'failed',
                    'status' => 'cancelled',
                    'updated_at' => date('Y-m-d H:i:s'),
                ], 'id = ?', [$order['id']]);
            }
        }
        echo json_encode(['ResultCode' => 0]);
    }

    /**
     * GET /payment/intasend/callback
     * IntaSend payment redirect callback.
     */
    public function intasendCallback(): void
    {
        $orderId = (int)Request::query('order_id', 0);
        $invoiceId = Request::query('invoice_id', '');
        if ($orderId) {
            Database::update('orders', [
                'payment_status' => 'paid',
                'status' => 'processing',
                'payment_reference' => $invoiceId,
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$orderId]);
            $this->processReferralCommission($orderId);
        }
        Session::set('last_order_id', $orderId);
        Redirect::to('/order-success');
    }

    /**
     * GET /payment/paypal/callback
     * PayPal return callback after approval.
     */
    public function paypalCallback(): void
    {
        $orderId = (int)Request::query('order_id', 0);
        $token = Request::query('token', '');

        if ($orderId && $token) {
            $order = Database::selectOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
            if ($order && $order['payment_status'] !== 'paid') {
                // Capture the payment via PayPal API
                $ppClientId = $this->getSetting('paypal_client_id');
                $ppSecret = $this->getSetting('paypal_secret');
                $ppEnv = $this->getSetting('paypal_env') ?: 'sandbox';
                $ppBase = $ppEnv === 'production'
                    ? 'https://api-m.paypal.com'
                    : 'https://api-m.sandbox.paypal.com';

                if (!empty($ppClientId) && !empty($ppSecret)) {
                    $tokenResult = $this->curlRequest($ppBase . '/v1/oauth2/token', [
                        CURLOPT_USERPWD    => $ppClientId . ':' . $ppSecret,
                        CURLOPT_POST       => true,
                        CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
                    ]);
                    $tokenData = json_decode($tokenResult['response'], true);
                    $accessToken = $tokenData['access_token'] ?? '';

                    if (!empty($accessToken)) {
                        $captureResult = $this->curlRequest($ppBase . '/v2/checkout/orders/' . urlencode($token) . '/capture', [
                            CURLOPT_HTTPHEADER  => [
                                'Content-Type: application/json',
                                'Authorization: Bearer ' . $accessToken,
                            ],
                            CURLOPT_POST       => true,
                            CURLOPT_POSTFIELDS => '{}',
                        ]);
                        $captureData = json_decode($captureResult['response'], true);
                        $captureStatus = $captureData['status'] ?? '';

                        if ($captureStatus === 'COMPLETED') {
                            $captureId = $captureData['purchase_units'][0]['payments']['captures'][0]['id'] ?? $token;
                            Database::update('orders', [
                                'payment_status' => 'paid',
                                'status' => 'processing',
                                'payment_reference' => $captureId,
                                'updated_at' => date('Y-m-d H:i:s'),
                            ], 'id = ?', [$orderId]);

                            Database::insert('transactions', [
                                'order_id' => $orderId,
                                'payment_method' => 'paypal',
                                'amount' => $order['total'],
                                'reference' => $captureId,
                                'status' => 'completed',
                                'created_at' => date('Y-m-d H:i:s'),
                            ]);

                            $this->processReferralCommission($orderId);
                        } else {
                            $this->posLog('PAYPAL_CAPTURE_FAILED', ['order_id' => $orderId, 'response' => $captureData]);
                        }
                    }
                } else {
                    // No credentials — still mark as paid for testing
                    Database::update('orders', [
                        'payment_status' => 'paid',
                        'status' => 'processing',
                        'payment_reference' => $token,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ], 'id = ?', [$orderId]);
                    $this->processReferralCommission($orderId);
                }
            }
        }
        Session::set('last_order_id', $orderId);
        Redirect::to('/order-success');
    }

    /**
     * GET /payment/stripe/success
     * Stripe checkout success redirect.
     */
    public function stripeSuccess(): void
    {
        $orderId = (int)Request::query('order_id', 0);
        if ($orderId) {
            Database::update('orders', [
                'payment_status' => 'paid',
                'status' => 'processing',
                'payment_reference' => Request::query('session_id', 'stripe'),
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$orderId]);
            $this->processReferralCommission($orderId);
        }
        Session::set('last_order_id', $orderId);
        Redirect::to('/order-success');
    }

    /**
     * GET /payment/pesapal/redirect
     * PesaPal redirect after payment.
     */
    public function pesapalRedirect(): void
    {
        $orderId = (int)Request::query('order_id', 0);
        // In production, this would redirect to PesaPal iframe/checkout
        // For demo, mark as paid and redirect to success
        if ($orderId) {
            Database::update('orders', [
                'payment_status' => 'paid',
                'status' => 'processing',
                'payment_reference' => 'pesapal',
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$orderId]);
            $this->processReferralCommission($orderId);
        }
        Session::set('last_order_id', $orderId);
        Redirect::to('/order-success');
    }
}