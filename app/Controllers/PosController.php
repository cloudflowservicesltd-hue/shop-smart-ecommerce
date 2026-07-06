<?php



class PosController extends BaseController
{
    public function cart(): void
    {
        try {
            header('Content-Type: application/json');
            if (!$this->requireAuth()) return;
            $cart = json_decode(Request::post('cart', '[]'), true) ?: [];
            Session::set('pos_cart', $cart);
            $this->posJson(['success' => true]);
        } catch (\Throwable $e) {
            header('Content-Type: application/json');
            $this->posJson(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function products(): void
    {
        try {
            header('Content-Type: application/json');
            if (!$this->requireAuth()) return;
            $catId = Request::query('category', '');
            $search = trim(Request::query('search', ''));
            $params = [];

            if ($search !== '') {
                $searchParam = '%' . $search . '%';
                $sql = "SELECT p.*, (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1) as image FROM products p WHERE p.is_active = 1 AND p.stock_quantity > 0 AND (p.name LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ?) ORDER BY p.name";
                $params = [$searchParam, $searchParam, $searchParam];
                if ($catId !== '' && $catId !== '0') {
                    $sql = "SELECT p.*, (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1) as image FROM products p WHERE p.is_active = 1 AND p.stock_quantity > 0 AND p.category_id = ? AND (p.name LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ?) ORDER BY p.name";
                    $params = [$catId, $searchParam, $searchParam, $searchParam];
                }
                $products = Database::select($sql, $params);
            } elseif ($catId !== '' && $catId !== '0') {
                $products = Database::select("SELECT p.*, (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1) as image FROM products p WHERE p.is_active = 1 AND p.stock_quantity > 0 AND p.category_id = ? ORDER BY p.name", [$catId]);
            } else {
                $products = Database::select("SELECT p.*, (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1) as image FROM products p WHERE p.is_active = 1 AND p.stock_quantity > 0 ORDER BY p.name");
            }
            $this->posJson($products);
        } catch (\Throwable $e) {
            header('Content-Type: application/json');
            $this->posJson([]);
        }
    }

    public function checkout(): void
    {
        $this->posLog('CHECKOUT_START', ['uri' => $_SERVER['REQUEST_URI'] ?? '', 'content_type' => $_SERVER['CONTENT_TYPE'] ?? '']);
        try {
            header('Content-Type: application/json');
            if (!Auth::check()) { $this->posLog('CHECKOUT_AUTH_FAIL'); $this->posJson(['success'=>false,'error'=>'Not authenticated'], 401); return; }

            $input = $this->posInput();
            $this->posLog('CHECKOUT_INPUT_PARSED', ['keys' => array_keys($input)]);

            $cartRaw = $input['cart'] ?? '[]';
            $cart = is_string($cartRaw) ? json_decode($cartRaw, true) : $cartRaw;
            if (!is_array($cart)) $cart = [];
            if (empty($cart)) { $this->posLog('CHECKOUT_EMPTY_CART'); $this->posJson(['success'=>false,'error'=>'Cart is empty']); return; }

            $method = $input['method'] ?? 'cash';
            $subtotal = (float)($input['subtotal'] ?? 0);
            $tax = (float)($input['tax'] ?? 0);
            $total = (float)($input['total'] ?? 0);
            $orderNum = $input['orderNum'] ?? ('POS-' . date('YmdHis'));
            $amountReceived = (float)($input['amount_received'] ?? 0);
            $changeAmount = (float)($input['change_amount'] ?? 0);
            $transactionCode = $input['transaction_code'] ?? '';
            $stkCheckoutId = $input['stk_checkout_id'] ?? '';
            $userId = Auth::id();
            $now = date('Y-m-d H:i:s');

            Database::getConnection()->beginTransaction();

            $requiredOrderCols = [
                'order_number' => $orderNum,
                'customer_name' => 'Walk-in Customer',
                'status' => 'completed',
                'payment_method' => $method,
                'payment_status' => 'paid',
                'payment_reference' => $transactionCode ?: $stkCheckoutId ?: $orderNum,
                'subtotal' => $subtotal, 'tax' => $tax, 'discount' => 0, 'shipping_cost' => 0,
                'total' => $total, 'is_pos' => 1, 'created_at' => $now, 'updated_at' => $now,
            ];
            $optionalOrderCols = [
                'customer_id' => null, 'cashier_id' => $userId,
                'amount_received' => $amountReceived, 'change_amount' => $changeAmount,
                'referral_code' => null, 'notes' => '',
            ];

            try {
                $orderId = $this->posSafeInsert('orders', $requiredOrderCols, $optionalOrderCols);
            } catch (\Throwable $e) {
                $this->posLog('CHECKOUT_ORDER_INSERT_FAIL', ['error' => $e->getMessage()]);
                throw new \RuntimeException('Failed to create order: ' . $e->getMessage(), 0, $e);
            }

            foreach ($cart as $idx => $item) {
                try {
                    $pid = (int)($item['id'] ?? 0);
                    $qty = (int)($item['qty'] ?? 1);
                    $price = (float)($item['price'] ?? 0);
                    $itemTotal = $price * $qty;
                    $itemName = $item['name'] ?? 'Unknown';

                    $costPrice = 0;
                    try {
                        $prod = Database::selectOne("SELECT cost_price, stock_quantity FROM products WHERE id = ?", [$pid]);
                        if ($prod) $costPrice = (float)($prod['cost_price'] ?? 0);
                    } catch (\Throwable $pe) {}

                    $reqItemCols = [
                        'order_id' => $orderId, 'product_id' => $pid,
                        'product_name' => $itemName, 'quantity' => $qty,
                        'price' => $price, 'total' => $itemTotal, 'created_at' => $now,
                    ];
                    $optItemCols = ['cost_price' => $costPrice];
                    $this->posSafeInsert('order_items', $reqItemCols, $optItemCols);

                    try {
                        Database::query("UPDATE `products` SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?", [$qty, $pid, $qty]);
                    } catch (\Throwable $stockErr) {
                        $this->posLog('CHECKOUT_STOCK_UPDATE_FAIL', ['product_id' => $pid, 'error' => $stockErr->getMessage()]);
                    }
                } catch (\Throwable $itemErr) {
                    $this->posLog('CHECKOUT_ITEM_FAIL', ['idx' => $idx, 'error' => $itemErr->getMessage()]);
                    throw new \RuntimeException('Failed to add item "' . ($item['name'] ?? 'unknown') . '": ' . $itemErr->getMessage(), 0, $itemErr);
                }
            }

            try {
                $gateway = match($method) {
                    'mpesa' => 'mpesa', 'card' => 'card', 'pesapal' => 'pesapal', default => 'cash',
                };
                $txCols = [
                    'order_id' => $orderId, 'payment_method' => $method,
                    'amount' => $total, 'currency' => 'KES',
                    'reference' => $transactionCode ?: $stkCheckoutId ?: $orderNum,
                    'status' => 'completed', 'gateway' => $gateway, 'created_at' => $now,
                ];
                $this->posSafeInsert('transactions', $txCols);
            } catch (\Throwable $txErr) {
                $this->posLog('CHECKOUT_TX_FAIL', ['error' => $txErr->getMessage()]);
            }

            try {
                $commEnabled = Database::selectOne("SELECT value FROM settings WHERE `key` = 'commission_enabled'");
                if ($commEnabled && $commEnabled['value'] === '1') {
                    $commPct = (float)(Database::selectOne("SELECT value FROM settings WHERE `key` = 'commission_percentage'")['value'] ?? 0);
                    if ($commPct > 0) {
                        $commAmount = round($total * ($commPct / 100), 2);
                        Database::insert('commissions', [
                            'user_id' => $userId, 'order_id' => $orderId, 'order_number' => $orderNum,
                            'amount' => $commAmount, 'percentage' => $commPct, 'order_total' => $total,
                            'status' => 'pending', 'created_at' => $now,
                        ]);
                    }
                }
            } catch (\Throwable $commErr) {
                $this->posLog('CHECKOUT_COMMISSION_FAIL', ['error' => $commErr->getMessage()]);
            }

            Session::set('pos_cart', []);
            Database::getConnection()->commit();

            $this->posLog('CHECKOUT_SUCCESS', ['order_id' => $orderId, 'order_number' => $orderNum, 'total' => $total]);
            $this->posJson(['success'=>true,'order_id'=>$orderId,'order_number'=>$orderNum]);

        } catch (\Throwable $e) {
            try { if (Database::getConnection()->inTransaction()) Database::getConnection()->rollBack(); } catch (\Throwable $re) {}
            $this->posLog('CHECKOUT_FATAL', ['error' => $e->getMessage(), 'file' => basename($e->getFile()), 'line' => $e->getLine()]);
            $this->posJson(['success' => false, 'error' => 'Checkout failed: ' . $e->getMessage(), 'debug_file' => basename($e->getFile()), 'debug_line' => $e->getLine()], 500);
        }
    }

    public function stkPush(): void
    {
        try {
            header('Content-Type: application/json');
            if (!Auth::check()) { $this->posLog('STK_AUTH_FAIL'); $this->posJson(['error'=>'Access denied'], 403); return; }

            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true) ?? [];
            $this->posLog('DARAJA_STK_START', ['phone' => substr($input['phone'] ?? '', 0, 6) . '***', 'amount' => $input['amount'] ?? 0]);

            $phone = $input['phone'] ?? '';
            $amount = (float)($input['amount'] ?? 0);
            $orderRef = $input['orderRef'] ?? ('POS-' . time());

            if (empty($phone) || $amount <= 0) {
                $this->posLog('DARAJA_STK_MISSING_PARAMS');
                $this->posJson(['error' => 'Phone number and amount are required']);
            }

            $env = $this->getSetting('mpesa_env') ?: 'sandbox';
            $shortcode = $this->getSetting('mpesa_shortcode') ?: '174379';
            $passkey = $this->getSetting('mpesa_passkey') ?: '';
            $consumerKey = $this->getSetting('mpesa_consumer_key') ?: '';
            $consumerSecret = $this->getSetting('mpesa_consumer_secret') ?: '';
            $callbackUrl = $this->getSetting('mpesa_callback_url') ?: '';
            if (empty($callbackUrl)) {
                $siteUrl = $this->getSiteUrl();
                $callbackUrl = $siteUrl . '/api/mpesa/callback';
            }

            if (empty($consumerKey) || empty($consumerSecret) || empty($passkey)) {
                $this->posLog('DARAJA_STK_NOT_CONFIGURED');
                $this->posJson(['error' => 'Daraja M-Pesa is not configured. Please set up credentials in Payment Settings.']);
            }

            $phone = $this->normalizePhone($phone);
            if (!str_starts_with($phone, '254') || strlen($phone) !== 12) {
                $this->posLog('DARAJA_STK_BAD_PHONE', ['phone' => $phone]);
                $this->posJson(['error' => 'Invalid phone number. Use format 254XXXXXXXXX']);
            }

            $baseUrl = $env === 'production' ? 'https://api.safaricom.co.ke' : 'https://sandbox.safaricom.co.ke';

            $credentials = base64_encode($consumerKey . ':' . $consumerSecret);
            $ch = curl_init($baseUrl . '/oauth/v1/generate?grant_type=client_credentials');
            curl_setopt_array($ch, [
                CURLOPT_HTTPHEADER => ['Authorization: Basic ' . $credentials],
                CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $tokenResponse = curl_exec($ch);
            $tokenHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $tokenErr = curl_error($ch);
            curl_close($ch);
            $tokenData = json_decode($tokenResponse, true);

            if (!isset($tokenData['access_token'])) {
                $this->posLog('DARAJA_TOKEN_FAIL', ['http_code' => $tokenHttpCode, 'curl_err' => $tokenErr, 'response' => substr($tokenResponse, 0, 300)]);
                throw new \Exception('Failed to get Daraja access token: ' . ($tokenData['error_description'] ?? $tokenErr ?: 'Unknown error'));
            }
            $accessToken = $tokenData['access_token'];

            $timestamp = date('YmdHis');
            $password = base64_encode($shortcode . $passkey . $timestamp);

            $stkPayload = [
                'BusinessShortCode' => $shortcode, 'Password' => $password, 'Timestamp' => $timestamp,
                'TransactionType' => 'CustomerPayBillOnline',
                'Amount' => (int)round($amount), 'PartyA' => $phone, 'PartyB' => $shortcode,
                'PhoneNumber' => $phone, 'CallBackURL' => $callbackUrl,
                'AccountReference' => substr($orderRef, 0, 12),
                'TransactionDesc' => 'POS Payment ' . $orderRef,
            ];

            $ch = curl_init($baseUrl . '/mpesa/stkpush/v1/processrequest');
            curl_setopt_array($ch, [
                CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken, 'Content-Type: application/json'],
                CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($stkPayload),
                CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $stkResponse = curl_exec($ch);
            $stkHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $stkCurlErr = curl_error($ch);
            curl_close($ch);
            $stkData = json_decode($stkResponse, true);

            $this->posLog('DARAJA_STK_RESPONSE', ['http_code' => $stkHttpCode, 'curl_err' => $stkCurlErr, 'response' => substr($stkResponse ?? '', 0, 500)]);

            if (isset($stkData['ResponseCode']) && $stkData['ResponseCode'] === '0') {
                Session::set('mpesa_stk_' . $stkData['CheckoutRequestID'], [
                    'CheckoutRequestID' => $stkData['CheckoutRequestID'],
                    'MerchantRequestID' => $stkData['MerchantRequestID'],
                    'provider' => 'darja',
                    'phone' => $phone, 'amount' => $amount, 'orderRef' => $orderRef, 'created_at' => time(),
                ]);
                $this->posJson([
                    'success' => true,
                    'CheckoutRequestID' => $stkData['CheckoutRequestID'],
                    'MerchantRequestID' => $stkData['MerchantRequestID'],
                    'amount' => (int)round($amount),
                    'message' => 'Daraja STK push sent to ' . $phone . '. Please confirm on your phone.',
                ]);
            } else {
                $errorCode = $stkData['errorCode'] ?? 'UNKNOWN';
                $errorMessage = $stkData['errorMessage'] ?? 'STK push request failed';
                $this->posLog('DARAJA_STK_REJECTED', ['error_code' => $errorCode, 'error_msg' => $errorMessage]);
                $this->posJson(['success' => false, 'error' => "Daraja error ({$errorCode}): {$errorMessage}"]);
            }
        } catch (\Throwable $e) {
            $this->posLog('DARAJA_STK_EXCEPTION', ['error' => $e->getMessage(), 'file' => basename($e->getFile()), 'line' => $e->getLine()]);
            $this->posJson(['error' => 'Daraja STK push failed: ' . $e->getMessage()], 500);
        }
    }

    public function cloudoneStkPush(): void
    {
        try {
            header('Content-Type: application/json');
            if (!Auth::check()) { $this->posLog('CLOUDONE_AUTH_FAIL'); $this->posJson(['error'=>'Access denied'], 403); return; }

            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true) ?? [];
            $this->posLog('CLOUDONE_STK_START', ['phone' => substr($input['phone'] ?? '', 0, 6) . '***', 'amount' => $input['amount'] ?? 0]);

            $phone = $input['phone'] ?? '';
            $amount = (float)($input['amount'] ?? 0);
            $orderRef = $input['orderRef'] ?? ('POS-' . time());

            if (empty($phone) || $amount <= 0) {
                $this->posLog('CLOUDONE_STK_MISSING_PARAMS');
                $this->posJson(['error' => 'Phone number and amount are required']);
            }

            $publicKey  = $this->ensureSetting('cloudone_public_key', 'pk_e0da32787ef75753d83e2d73930f5d1a781c2fad987518b0');
            $secretKey  = $this->ensureSetting('cloudone_secret_key', 'sk_632fdb502bc16febecb23550152c3ef02ad6f1ce416960d39197dffc50c1a40e');
            $apiUrl     = $this->ensureSetting('cloudone_api_url', 'https://pesa.cloudonehost.top/api/v1/stk/push');

            if (empty($publicKey) || empty($secretKey)) {
                $this->posLog('CLOUDONE_STK_NOT_CONFIGURED');
                $this->posJson(['error' => 'CloudOne is not configured.']);
            }

            $phone = $this->normalizePhone($phone);
            if (!str_starts_with($phone, '254') || strlen($phone) !== 12) {
                $this->posLog('CLOUDONE_STK_BAD_PHONE', ['phone' => $phone]);
                $this->posJson(['error' => 'Invalid phone number. Use format 254XXXXXXXXX or 07XXXXXXXX']);
            }

            $callbackUrl = $this->getSetting('cloudone_callback_url');
            if (empty($callbackUrl)) {
                $siteUrl = $this->getSiteUrl();
                $callbackUrl = $siteUrl . '/api/mpesa/callback';
            }

            $payload = json_encode([
                'amount' => (int)round($amount), 'phone' => $phone,
                'reference' => $orderRef, 'description' => 'POS Payment ' . $orderRef,
                'callback_url' => $callbackUrl,
            ]);

            $this->posLog('CLOUDONE_STK_SENDING', ['url' => $apiUrl, 'phone' => substr($phone, 0, 6) . '***', 'amount' => (int)round($amount)]);

            $ch = curl_init($apiUrl);
            curl_setopt_array($ch, [
                CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload,
                CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'X-API-KEY: ' . $publicKey,
                    'X-API-SECRET: ' . $secretKey,
                ],
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr  = curl_error($ch);
            curl_close($ch);
            $data = json_decode($response, true);

            $this->posLog('CLOUDONE_STK_RESPONSE', ['http_code' => $httpCode, 'response' => substr($response ?? '', 0, 500)]);

            if ($curlErr) { throw new \Exception('cURL error: ' . $curlErr); }

            if (!empty($data['success']) && isset($data['data']['checkout_request_id'])) {
                $checkoutId = $data['data']['checkout_request_id'];
                Session::set('mpesa_stk_' . $checkoutId, [
                    'CheckoutRequestID' => $checkoutId, 'provider' => 'cloudone',
                    'phone' => $phone, 'amount' => $amount, 'orderRef' => $orderRef, 'created_at' => time(),
                ]);
                $this->posJson([
                    'success' => true, 'CheckoutRequestID' => $checkoutId,
                    'amount' => (int)round($amount),
                    'message' => 'CloudOne STK push sent to ' . $phone . '. Please confirm on your phone.',
                ]);
            } else {
                $errorMsg = $data['message'] ?? ($data['error'] ?? 'STK push request failed');
                $this->posLog('CLOUDONE_STK_REJECTED', ['response' => $data]);
                $this->posJson(['success' => false, 'error' => 'CloudOne error: ' . $errorMsg]);
            }
        } catch (\Throwable $e) {
            $this->posLog('CLOUDONE_STK_EXCEPTION', ['error' => $e->getMessage()]);
            $this->posJson(['error' => 'CloudOne STK push failed: ' . $e->getMessage()], 500);
        }
    }

    public function stkStatus(): void
    {
        header('Content-Type: application/json');
        if (!$this->requireAuth()) return;
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $checkoutRequestId = $input['CheckoutRequestID'] ?? '';
        if (empty($checkoutRequestId)) { $this->posJson(['error' => 'CheckoutRequestID is required']); return; }
        $sessionKey = 'mpesa_stk_' . $checkoutRequestId;
        $stored = Session::get($sessionKey);
        if ($stored && isset($stored['result_code'])) {
            $this->posJson(['status' => $stored['result_code'] === 0 ? 'success' : 'failed', 'resultCode' => $stored['result_code'], 'resultDesc' => $stored['result_desc'] ?? '', 'transactionCode' => $stored['mpesa_receipt'] ?? '']);
        }
        if ($stored && (time() - ($stored['created_at'] ?? 0)) > 120) {
            $this->posJson(['status' => 'failed', 'resultCode' => 'timeout', 'resultDesc' => 'STK push expired.']);
        }
        $this->posJson(['status' => 'pending', 'message' => 'Waiting for customer to confirm on phone...']);
    }

    public function holds(): void
    {
        try {
            header('Content-Type: application/json');
            if (!$this->requireAuth()) return;
            $userId = Auth::id();
            $holds = Database::select("SELECT id, user_id, cashier_name, items_count, total, customer_name, notes, created_at FROM pos_holds WHERE user_id = ? ORDER BY created_at DESC", [$userId]);
            $this->posJson(['success'=>true,'holds'=>$holds]);
        } catch (\Throwable $e) {
            @file_put_contents(ROOT_PATH . '/storage/logs/pos-api.log', date('Y-m-d H:i:s') . " | GET /api/pos/holds ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
            header('Content-Type: application/json');
            $this->posJson(['success'=>true,'holds'=>[]]);
        }
    }

    public function holdStore(): void
    {
        try {
            header('Content-Type: application/json');
            if (!$this->requireAuth()) return;
            $cart = json_decode(Request::post('cart', '[]'), true) ?: [];
            if (empty($cart)) { $this->posJson(['success'=>false,'error'=>'Cart is empty']); return; }
            $notes = Request::post('notes', '');
            $customerName = Request::post('customer_name', '');
            $userId = Auth::id();
            $userName = Auth::user()['name'] ?? 'Cashier';
            $subtotal = array_sum(array_map(fn($i) => (float)($i['price'] ?? 0) * (int)($i['qty'] ?? 0), $cart));
            $taxRate = (float)(Database::selectOne("SELECT value FROM settings WHERE `key` = 'tax_rate'")['value'] ?? 16);
            $tax = $subtotal * ($taxRate / 100);
            $total = $subtotal + $tax;
            Database::insert('pos_holds', [
                'user_id' => $userId, 'cashier_name' => $userName,
                'cart_data' => json_encode($cart), 'items_count' => count($cart),
                'subtotal' => $subtotal, 'tax' => $tax, 'total' => $total,
                'customer_name' => $customerName, 'notes' => $notes,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $this->posJson(['success'=>true]);
        } catch (\Throwable $e) {
            header('Content-Type: application/json');
            $this->posJson(['success'=>false,'error'=>$e->getMessage()]);
        }
    }

    public function holdRestore($id): void
    {
        try {
            header('Content-Type: application/json');
            if (!$this->requireAuth()) return;
            $userId = Auth::id();
            $hold = Database::selectOne("SELECT * FROM pos_holds WHERE id = ? AND user_id = ?", [$id, $userId]);
            if (!$hold) { $this->posJson(['success'=>false,'error'=>'Hold not found']); return; }
            $cart = json_decode($hold['cart_data'], true) ?: [];
            Session::set('pos_cart', $cart);
            Database::delete('pos_holds', 'id = ?', [$id]);
            $this->posJson(['success'=>true,'cart'=>$cart]);
        } catch (\Throwable $e) {
            header('Content-Type: application/json');
            $this->posJson(['success'=>false,'error'=>$e->getMessage()]);
        }
    }

    public function holdDelete($id): void
    {
        try {
            header('Content-Type: application/json');
            if (!$this->requireAuth()) return;
            $userId = Auth::id();
            Database::delete('pos_holds', 'id = ? AND user_id = ?', [$id, $userId]);
            $this->posJson(['success'=>true]);
        } catch (\Throwable $e) {
            header('Content-Type: application/json');
            $this->posJson(['success'=>false,'error'=>$e->getMessage()]);
        }
    }

    public function health(): void
    {
        header('Content-Type: application/json');
        $this->posJson([
            'success' => true, 'auth' => Auth::check(),
            'user_id' => Auth::id(),
            'user_role' => Auth::hasRole(['admin','super_admin','cashier']) ? Session::get('user_role') : 'none',
            'session_id' => session_id(),
            'routes_registered' => true,
            'time' => date('Y-m-d H:i:s'),
        ]);
    }
}