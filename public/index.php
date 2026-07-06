<?php

// Entry point - Works on LiteSpeed, Apache, Nginx+PHP-FPM, and PHP built-in server
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Start output buffering immediately to catch any stray output from bootstrap/routes
ob_start();

// Load bootstrap
require __DIR__ . '/../app/bootstrap.php';

// Auto-run pending database migrations (creates tables on first request)
require_once ROOT_PATH . '/database/install.php';

// Load routes
require ROOT_PATH . '/routes/web.php';

// Load API routes — delegate to standalone API files with a clean output buffer
if (str_starts_with($uri, '/api/')) {
    $apiPath = substr($uri, 5); // remove '/api/'
    $apiFile = ROOT_PATH . '/api/' . $apiPath . '.php';
    if (file_exists($apiFile)) {
        // Discard any stray output from bootstrap/routes before handing off to the API file
        while (ob_get_level()) ob_end_clean();
        require $apiFile;
        exit;
    }
    $apiDirFile = ROOT_PATH . '/api/' . rtrim($apiPath, '/') . '/index.php';
    if (file_exists($apiDirFile)) {
        while (ob_get_level()) ob_end_clean();
        require $apiDirFile;
        exit;
    }
}

// Middleware definitions
$router->addMiddleware('csrf', function() {
    if (Request::isPost() && !isset($_POST['_token'])) {
        Session::flash('error', 'CSRF token missing.');
        Redirect::back();
    }
    return true;
});
$router->addMiddleware('auth', function() {
    if (!Auth::check()) {
        Session::flash('error', 'Please login to continue.');
        Redirect::to('/login');
    }
    return true;
});
$router->addMiddleware('guest', function() {
    if (Auth::check()) Redirect::to('/');
    return true;
});
$router->addMiddleware('admin', function() {
    if (!Auth::check() || !Auth::isAdmin()) {
        Session::flash('error', 'Access denied. Admin privileges required.');
        Redirect::to('/');
    }
    return true;
});
$router->addMiddleware('cashier', function() {
    if (!Auth::check() || !Auth::hasRole(['admin', 'super_admin', 'cashier'])) {
        Session::flash('error', 'Access denied.');
        Redirect::to('/');
    }
    return true;
});
$router->addMiddleware('super_admin', function() {
    if (!Auth::check() || !Auth::isSuperAdmin()) {
        Session::flash('error', 'Super admin access required.');
        Redirect::to('/');
    }
    return true;
});

// Dispatch — clean any stray output before rendering the page
while (ob_get_level()) ob_end_clean();
$dispatchedUri = Request::uri();
$dispatchedMethod = Request::method();

// Debug log
$debug = $router->debugRoutes();
$routeCount = $debug['count'];
$samplePatterns = array_slice($debug['patterns'], 0, 3);
@file_put_contents(ROOT_PATH . '/storage/logs/route-debug.log',
    date('Y-m-d H:i:s') . " | routes={$routeCount} | sample=[" . implode(',', $samplePatterns) . "] | uri={$dispatchedUri} | method={$dispatchedMethod}\n",
    FILE_APPEND);

try {
    $result = $router->dispatch($dispatchedMethod, $dispatchedUri);
} catch (\Throwable $e) {
    // Log the exception
    $logMsg = date('Y-m-d H:i:s') . " | EXCEPTION | uri={$dispatchedUri} | err={$e->getMessage()} | file={$e->getFile()}:{$e->getLine()}\n";
    if ($e->getTraceAsString()) $logMsg .= "  Trace: " . $e->getTraceAsString() . "\n";
    @file_put_contents(ROOT_PATH . '/storage/logs/error.log', $logMsg, FILE_APPEND | LOCK_EX);
    @file_put_contents(ROOT_PATH . '/storage/logs/route-debug.log',
        date('Y-m-d H:i:s') . " | EXCEPTION | uri={$dispatchedUri} | err={$e->getMessage()} | file={$e->getFile()}:{$e->getLine()}\n",
        FILE_APPEND);

    // For AJAX/XHR/fetch requests, return JSON error instead of HTML
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));
    if ($isAjax) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Server error']);
        exit;
    }

    // Show styled 500 error page for normal requests
    if (class_exists('ErrorHandler')) {
        $details = get_class($e) . ': ' . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n\n" . $e->getTraceAsString();
        ErrorHandler::render(500, 'Server Error', "Something went wrong on our end. Our team has been notified.", $details, true);
    } else {
        http_response_code(500);
        echo "<h1>500 - Server Error</h1><p>" . htmlspecialchars($e->getMessage()) . "</p>";
    }
    exit;
}

if ($result === null || $result === false) {
    // Normalize the URI for fallback matching
    $cleanUri = '/' . trim($uri, '/\\');
    $cleanUri = preg_replace('#^/public#', '', $cleanUri);
    $cleanUri = '/' . trim($cleanUri, '/');

    @file_put_contents(ROOT_PATH . '/storage/logs/route-debug.log',
        date('Y-m-d H:i:s') . " | 404 FALLBACK | clean={$cleanUri} | dispatch={$dispatchedUri} | result=" . var_export($result, true) . "\n",
        FILE_APPEND);

    // ============================================
    // Direct fallback routing (bypasses Router class)
    // ============================================
    $handled = false;

    // POST routes
    if ($dispatchedMethod === 'POST') {
        if ($cleanUri === '/newsletter') {
            $email = trim(Request::post('email', ''));
            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $exists = Database::selectOne("SELECT id FROM newsletter_subscribers WHERE email = ?", [$email]);
                if (!$exists) {
                    Database::insert('newsletter_subscribers', ['email' => $email, 'created_at' => date('Y-m-d H:i:s'), 'is_active' => 1]);
                }
                Session::flash('success', 'Thanks for subscribing! You\'ll hear from us soon.');
            } else {
                Session::flash('error', 'Please enter a valid email address.');
            }
            Redirect::back();
        }
        if ($cleanUri === '/login') {
            $email = Request::post('email', '');
            $password = Request::post('password', '');
            if (Auth::attempt($email, $password)) {
                $user = Auth::user();
                if (in_array($user['role'], ['super_admin', 'admin'])) Redirect::to('/admin');
                elseif ($user['role'] === 'cashier') Redirect::to('/pos');
                else Redirect::to('/');
            }
            Session::flash('error', 'Invalid email or password');
            Redirect::to('/login');
        }
        if ($cleanUri === '/logout') { Auth::logout(); Redirect::to('/'); }
        // Admin order status update
        if (preg_match('#^/admin/orders/\d+/status$#', $cleanUri)) {
            if (!Auth::check() || !Auth::isAdmin()) { Session::flash('error', 'Access denied.'); Redirect::to('/'); }
            $pathParts = explode('/', trim($cleanUri, '/'));
            $id = (int)$pathParts[2];
            $newStatus = Request::post('status', '');
            $update = ['status' => $newStatus, 'updated_at' => date('Y-m-d H:i:s')];
            if (in_array($newStatus, ['paid', 'completed', 'delivered'])) {
                $update['payment_status'] = 'paid';
            }
            Database::update('orders', $update, 'id = ?', [$id]);
            Session::flash('success', 'Order status updated to ' . ucfirst($newStatus));
            Redirect::to('/admin/orders/' . $id);
        }
        // Admin order notes update
        if (preg_match('#^/admin/orders/\d+/notes$#', $cleanUri)) {
            if (!Auth::check() || !Auth::isAdmin()) { Session::flash('error', 'Access denied.'); Redirect::to('/'); }
            $pathParts = explode('/', trim($cleanUri, '/'));
            $id = (int)$pathParts[2];
            Database::update('orders', [
                'notes' => Request::post('notes', ''),
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$id]);
            Session::flash('success', 'Order notes updated');
            Redirect::to('/admin/orders/' . $id);
        }

        if ($cleanUri === '/cart/add') {
            // Minimal cart add
            $productId = (int)Request::post('product_id', 0);
            $qty = (int)Request::post('quantity', 1);
            $prodCheck = Database::selectOne("SELECT id, stock_quantity, product_status FROM products WHERE id = ? AND is_active = 1 AND (product_status IS NULL OR product_status IN ('active','out_of_stock_returning'))", [$productId]);
            if (!$prodCheck || ($prodCheck['stock_quantity'] ?? 0) <= 0 || ($prodCheck['product_status'] ?? 'active') !== 'active') { Session::flash('error', 'Product not available for purchase.'); Redirect::back(); }
            $userId = Auth::id() ?? session_id();
            $existing = Database::selectOne("SELECT * FROM cart WHERE product_id = ? AND (user_id = ? OR session_id = ?)", [$productId, $userId, session_id()]);
            if ($existing) {
                Database::update('cart', ['quantity' => $existing['quantity'] + $qty], 'id = ?', [$existing['id']]);
            } else {
                Database::insert('cart', ['product_id' => $productId, 'quantity' => $qty, 'user_id' => Auth::id() ?: null, 'session_id' => Auth::id() ? null : session_id(), 'created_at' => date('Y-m-d H:i:s')]);
            }
            Redirect::back();
        }
        if ($cleanUri === '/admin/categories/store') {
            if (!Auth::check() || !Auth::isAdmin()) { Session::flash('error', 'Access denied.'); Redirect::to('/'); }
            $data = [
                'name' => Request::post('name', ''),
                'slug' => Request::post('slug', ''),
                'description' => Request::post('description', ''),
                'parent_id' => Request::post('parent_id') ?: null,
                'is_active' => Request::post('is_active') ? 1 : 0,
                'sort_order' => (int)Request::post('sort_order', 0),
                'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
            ];
            $image = FileUpload::handle('image', 'categories');
            if ($image) $data['image'] = $image;
            Database::insert('categories', $data);
            Session::flash('success', 'Category created');
            Redirect::to('/admin/categories');
        }
        if (preg_match('#^/admin/categories/(\d+)/update$#', $cleanUri, $m)) {
            if (!Auth::check() || !Auth::isAdmin()) { Session::flash('error', 'Access denied.'); Redirect::to('/'); }
            $data = [
                'name' => Request::post('name', ''),
                'slug' => Request::post('slug', ''),
                'description' => Request::post('description', ''),
                'parent_id' => Request::post('parent_id') ?: null,
                'is_active' => Request::post('is_active') ? 1 : 0,
                'sort_order' => (int)Request::post('sort_order', 0),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $image = FileUpload::handle('image', 'categories');
            if ($image) $data['image'] = $image;
            Database::update('categories', $data, 'id = ?', [$m[1]]);
            Session::flash('success', 'Category updated');
            Redirect::to('/admin/categories');
        }
        if (preg_match('#^/admin/categories/(\d+)/delete$#', $cleanUri, $m)) {
            if (!Auth::check() || !Auth::isAdmin()) { Session::flash('error', 'Access denied.'); Redirect::to('/'); }
            Database::delete('categories', 'id = ?', [$m[1]]);
            Session::flash('success', 'Category deleted');
            Redirect::to('/admin/categories');
        }
        if ($cleanUri === '/admin/brands/store') {
            if (!Auth::check() || !Auth::isAdmin()) { Session::flash('error', 'Access denied.'); Redirect::to('/'); }
            $data = [
                'name' => Request::post('name', ''),
                'slug' => Request::post('slug', ''),
                'description' => Request::post('description', ''),
                'is_active' => Request::post('is_active') ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $logo = FileUpload::handle('logo', 'brands');
            if ($logo) $data['logo'] = $logo;
            Database::insert('brands', $data);
            Session::flash('success', 'Brand created');
            Redirect::to('/admin/brands');
        }
        if (preg_match('#^/admin/brands/(\d+)/delete$#', $cleanUri, $m)) {
            if (!Auth::check() || !Auth::isAdmin()) { Session::flash('error', 'Access denied.'); Redirect::to('/'); }
            Database::delete('brands', 'id = ?', [$m[1]]);
            Session::flash('success', 'Brand deleted');
            Redirect::to('/admin/brands');
        }
        if (preg_match('#^/admin/brands/(\d+)/update$#', $cleanUri, $m)) {
            if (!Auth::check() || !Auth::isAdmin()) { Session::flash('error', 'Access denied.'); Redirect::to('/'); }
            $data = [
                'name' => Request::post('name', ''),
                'slug' => Request::post('slug', ''),
                'description' => Request::post('description', ''),
                'is_active' => Request::post('is_active') ? 1 : 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $logo = FileUpload::handle('logo', 'brands');
            if ($logo) $data['logo'] = $logo;
            Database::update('brands', $data, 'id = ?', [$m[1]]);
            Session::flash('success', 'Brand updated');
            Redirect::to('/admin/brands');
        }
        if (preg_match('#^/admin/users/(\d+)/update$#', $cleanUri, $m)) {
            if (!Auth::check() || !Auth::isAdmin()) { Session::flash('error', 'Access denied.'); Redirect::to('/'); }
            $data = [
                'name' => Request::post('name', ''),
                'email' => Request::post('email', ''),
                'role' => Request::post('role', 'customer'),
                'is_active' => Request::post('is_active') ? 1 : 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            if (!Auth::isSuperAdmin() && $data['role'] === 'super_admin') {
                $data['role'] = 'admin';
            }
            $password = Request::post('password', '');
            if ($password) {
                $data['password'] = password_hash($password, PASSWORD_DEFAULT);
            }
            Database::update('users', $data, 'id = ?', [$m[1]]);
            Session::flash('success', 'User updated');
            Redirect::to('/admin/users');
        }

        // Checkout - Step 1: Save shipping
        if ($cleanUri === '/checkout/shipping') {
            if (!Auth::check()) Redirect::to('/login');
            $shipping = [
                'name'    => Request::post('name', ''),
                'email'   => Request::post('email', ''),
                'phone'   => Request::post('phone', ''),
                'city'    => Request::post('city', ''),
                'address' => Request::post('address', ''),
                'notes'   => Request::post('notes', ''),
            ];
            Session::set('checkout_shipping', $shipping);
            Redirect::to('/checkout/payment');
            $handled = true;
        }

        // Checkout - Step 2: Save payment method
        if ($cleanUri === '/checkout/payment') {
            if (!Auth::check()) Redirect::to('/login');
            $shipping = Session::get('checkout_shipping', []);
            $shipping['payment_method'] = Request::post('payment_method', 'mpesa');
            Session::set('checkout_shipping', $shipping);
            Redirect::to('/checkout/review');
            $handled = true;
        }

        // Checkout - Step 3: Place order
        if ($cleanUri === '/checkout/review') {
            if (!Auth::check()) Redirect::to('/login');
            $shipping = Session::get('checkout_shipping', []);
            if (empty($shipping)) Redirect::to('/checkout/shipping');

            $cartItems = Database::select("SELECT c.*, p.name, p.price, p.discount_price, p.slug, p.stock_quantity, (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1) as image FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?", [Auth::id()]);
            if (empty($cartItems)) Redirect::to('/cart');

            $subtotal = 0;
            foreach ($cartItems as &$item) {
                $effectivePrice = $item['price'];
                if (!empty($item['discount_price']) && $item['discount_price'] < $item['price']) {
                    $effectivePrice = $item['discount_price'];
                }
                $item['price'] = $effectivePrice;
                $subtotal += $effectivePrice * $item['quantity'];
            }
            unset($item);

            $taxRate = (float)(Database::selectOne("SELECT value FROM settings WHERE `key` = 'tax_rate'")['value'] ?? 16);
            $shippingCost = 0;

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
            $orderNum = 'ORD-' . strtoupper(date('ymd')) . '-' . str_pad(Database::count('orders') + 1, 4, '0', STR_PAD_LEFT);

            $orderId = Database::insert('orders', [
                'order_number' => $orderNum,
                'customer_id' => Auth::id(),
                'customer_name' => $shipping['name'] ?? Auth::user()['name'],
                'customer_email' => $shipping['email'] ?? Auth::user()['email'],
                'customer_phone' => $shipping['phone'] ?? Auth::user()['phone'] ?? '',
                'customer_address' => ($shipping['address'] ?? '') . ($shipping['city'] ? ', ' . $shipping['city'] : ''),
                'notes' => $shipping['notes'] ?? '',
                'status' => 'pending',
                'payment_method' => $shipping['payment_method'] ?? 'mpesa',
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
            Session::remove('checkout_shipping');
            Session::set('last_order_id', $orderId);
            Session::set('last_order_num', $orderNum);
            Redirect::to('/order-success');
            $handled = true;
        }
    }

    // Payment API fallbacks
    if ($cleanUri === '/payment/initiate' && $dispatchedMethod === 'POST') {
        header('Content-Type: application/json');
        if (!Auth::check()) { echo json_encode(['success' => false, 'message' => 'Please login']); $handled = true; return; }
        $shipping = Session::get('checkout_shipping', []);
        if (empty($shipping)) { echo json_encode(['success' => false, 'message' => 'Missing shipping info']); $handled = true; return; }
        $paymentMethod = Request::post('payment_method', $shipping['payment_method'] ?? 'mpesa');
        $enabled = Database::selectOne("SELECT value FROM settings WHERE `key` = ?", [$paymentMethod . '_enabled']);
        if (!$enabled || $enabled['value'] !== '1') { echo json_encode(['success' => false, 'message' => ucfirst($paymentMethod) . ' is not available']); $handled = true; return; }
        $userId = Auth::id();
        $cartItems = Database::select("SELECT c.*, p.stock_quantity, p.name, (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1) as image FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ? ORDER BY c.created_at DESC", [$userId]);
        $subtotal = 0;
        foreach ($cartItems as $item) { $subtotal += $item['price'] * $item['quantity']; }
        $taxRate = (float)(Database::selectOne("SELECT value FROM settings WHERE `key` = 'tax_rate'")['value'] ?? 16);
        $couponDiscount = 0; $couponCode = Session::get('applied_coupon', null);
        if ($couponCode && $subtotal > 0) {
            $coupon = Database::selectOne("SELECT * FROM coupons WHERE code = ? AND is_active = 1", [$couponCode]);
            if ($coupon) {
                $now = date('Y-m-d H:i:s');
                if ((!$coupon['valid_from'] || $now >= $coupon['valid_from']) && (!$coupon['valid_until'] || $now <= $coupon['valid_until']) && ($coupon['usage_limit'] <= 0 || $coupon['used_count'] < $coupon['usage_limit']) && $subtotal >= ($coupon['min_order_amount'] ?? 0)) {
                    $couponDiscount = $coupon['type'] === 'percentage' ? $subtotal * ($coupon['value'] / 100) : $coupon['value'];
                    if ($coupon['max_discount_amount'] > 0 && $couponDiscount > $coupon['max_discount_amount']) $couponDiscount = $coupon['max_discount_amount'];
                    Database::update('coupons', ['used_count' => $coupon['used_count'] + 1, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$coupon['id']]);
                    Session::remove('applied_coupon');
                }
            }
        }
        $afterDiscount = $subtotal - $couponDiscount;
        $tax = $afterDiscount * ($taxRate / 100);
        $total = $afterDiscount + $tax;
        $orderNum = 'ORD-' . strtoupper(date('ymd')) . '-' . str_pad(Database::count('orders') + 1, 4, '0', STR_PAD_LEFT);
        $orderId = Database::insert('orders', [
            'order_number' => $orderNum, 'customer_id' => Auth::id(),
            'customer_name' => $shipping['name'] ?? '', 'customer_email' => $shipping['email'] ?? '',
            'customer_phone' => $shipping['phone'] ?? '', 'customer_address' => ($shipping['address'] ?? '') . ($shipping['city'] ? ', ' . $shipping['city'] : ''),
            'notes' => $shipping['notes'] ?? '', 'status' => 'pending', 'payment_method' => $paymentMethod, 'payment_status' => 'pending',
            'subtotal' => $subtotal, 'tax' => $tax, 'discount' => $couponDiscount, 'shipping_cost' => 0, 'total' => $total,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        foreach ($cartItems as $item) {
            Database::insert('order_items', ['order_id' => $orderId, 'product_id' => $item['product_id'], 'product_name' => $item['name'], 'quantity' => $item['quantity'], 'price' => $item['price'], 'total' => $item['price'] * $item['quantity'], 'created_at' => date('Y-m-d H:i:s')]);
            Database::update('products', ['stock_quantity' => $item['stock_quantity'] - $item['quantity']], 'id = ?', [$item['product_id']]);
        }
        Database::delete('cart', 'user_id = ?', [Auth::id()]);
        Session::remove('checkout_shipping');
        Session::set('last_order_id', $orderId);
        Session::set('last_order_num', $orderNum);
        Database::update('orders', ['payment_status' => 'paid', 'status' => 'processing', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$orderId]);
        echo json_encode(['success' => true, 'order_id' => $orderId, 'message' => 'Order placed (demo mode)']);
        $handled = true;
    }
    if ($cleanUri === '/payment/status' && $dispatchedMethod === 'GET') {
        header('Content-Type: application/json');
        $orderId = (int)Request::query('order_id', 0);
        $order = $orderId ? Database::selectOne("SELECT payment_status FROM orders WHERE id = ?", [$orderId]) : null;
        echo json_encode(['paid' => ($order['payment_status'] ?? 'pending') === 'paid']);
        $handled = true;
    }
    if (preg_match('#^/payment/(intasend|paypal|stripe)/callback$#', $cleanUri) && $dispatchedMethod === 'GET') {
        $orderId = (int)Request::query('order_id', 0);
        if ($orderId) Database::update('orders', ['payment_status' => 'paid', 'status' => 'processing', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$orderId]);
        Session::set('last_order_id', $orderId);
        Redirect::to('/order-success');
        $handled = true;
    }
    if ($cleanUri === '/payment/pesapal/redirect' && $dispatchedMethod === 'GET') {
        $orderId = (int)Request::query('order_id', 0);
        if ($orderId) Database::update('orders', ['payment_status' => 'paid', 'status' => 'processing', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$orderId]);
        Session::set('last_order_id', $orderId);
        Redirect::to('/order-success');
        $handled = true;
    }

    // POS Terminal
    if ($cleanUri === '/pos' && $dispatchedMethod === 'GET') {
        if (!Auth::check() || !Auth::hasRole(['admin', 'super_admin', 'cashier'])) {
            Session::flash('error', 'Access denied.');
            Redirect::to('/');
        }
        Session::set('pos_cart', Session::get('pos_cart', []));
        include ROOT_PATH . '/resources/views/pos/terminal.php';
        $handled = true;
    }

    // Home page
    if ($cleanUri === '/' && $dispatchedMethod === 'GET') {
        $featuredProducts = Database::select("SELECT p.*, (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1) as image, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.is_featured = 1 AND p.is_active = 1 AND (p.product_status IS NULL OR p.product_status IN ('active','out_of_stock_returning')) ORDER BY p.created_at DESC LIMIT 8");
        $newProducts = Database::select("SELECT p.*, (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1) as image FROM products p WHERE p.is_active = 1 AND (p.product_status IS NULL OR p.product_status IN ('active','out_of_stock_returning')) ORDER BY p.created_at DESC LIMIT 12");
        $categories = Database::select("SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id AND is_active = 1 AND (product_status IS NULL OR product_status IN ('active','out_of_stock_returning'))) as product_count FROM categories c WHERE c.parent_id IS NULL AND c.is_active = 1 ORDER BY c.sort_order LIMIT 8");
        $pageTitle = 'ShopSmart - AI-Powered Ecommerce & POS';
        ob_start();
        include ROOT_PATH . '/resources/views/customer/home.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/app.php';
        $handled = true;
    }

    // Login page
    if ($cleanUri === '/login' && $dispatchedMethod === 'GET') {
        if (Auth::check()) Redirect::to('/');
        include ROOT_PATH . '/resources/views/auth/login.php';
        $handled = true;
    }

    // Register page
    if ($cleanUri === '/register' && $dispatchedMethod === 'GET') {
        if (Auth::check()) Redirect::to('/');
        include ROOT_PATH . '/resources/views/auth/register.php';
        $handled = true;
    }

    // Products listing
    if ($cleanUri === '/products' && $dispatchedMethod === 'GET') {
        $page = (int)Request::query('page', 1);
        $search = Request::query('q', '');
        $where = "p.is_active = 1 AND (p.product_status IS NULL OR p.product_status IN ('active','out_of_stock_returning'))";
        $params = [];
        if ($search) { $where .= " AND (p.name LIKE ? OR p.description LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
        $sort = Request::query('sort', 'newest');
        $orderBy = match($sort) { 'price_asc' => 'p.price ASC', 'price_desc' => 'p.price DESC', 'popular' => 'p.created_at DESC', default => 'p.created_at DESC' };
        $paginated = Database::paginate("products p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN brands b ON p.brand_id = b.id", $page, 12, $where, $params, $orderBy);
        foreach ($paginated['data'] as &$p) {
            $img = Database::selectOne("SELECT image_path FROM product_images WHERE product_id = ? AND is_primary = 1", [$p['id']]);
            $p['image'] = $img['image_path'] ?? null;
            $rating = Database::selectOne("SELECT AVG(rating) as avg, COUNT(*) as cnt FROM reviews WHERE product_id = ? AND is_approved = 1", [$p['id']]);
            $p['avg_rating'] = round($rating['avg'] ?? 0, 1);
            $p['review_count'] = (int)($rating['cnt'] ?? 0);
        }
        $products = $paginated['data'];
        $pagination = $paginated;
        $totalProducts = $paginated['total'];
        $categories = Database::select("SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id IN (SELECT id FROM categories WHERE id = c.id OR parent_id = c.id) AND is_active = 1) as product_count FROM categories c WHERE c.parent_id IS NULL AND c.is_active = 1 ORDER BY c.name");
        $brands = Database::select("SELECT b.*, (SELECT COUNT(*) FROM products WHERE brand_id = b.id AND is_active = 1) as product_count FROM brands b WHERE b.is_active = 1 ORDER BY b.name");
        $query = $search;
        $pageTitle = 'Products - ShopSmart';
        ob_start();
        include ROOT_PATH . '/resources/views/customer/products.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/app.php';
        $handled = true;
    }

    // Checkout pages
    if (preg_match('#^/checkout(/shipping|/payment|/review|)?$#', $cleanUri) && $dispatchedMethod === 'GET') {
        if (!Auth::check()) { Session::flash('error', 'Please login to checkout'); Redirect::to('/login'); }

        // Determine step
        $currentStep = 'shipping';
        if ($cleanUri === '/checkout/payment') $currentStep = 'payment';
        elseif ($cleanUri === '/checkout/review') $currentStep = 'review';

        // Validate step progression
        $shipping = Session::get('checkout_shipping', []);
        if ($currentStep === 'payment' && (empty($shipping['name']) || empty($shipping['address']))) {
            Redirect::to('/checkout/shipping');
        }
        if ($currentStep === 'review' && (empty($shipping['name']) || empty($shipping['address']))) {
            Redirect::to('/checkout/shipping');
        }
        if ($currentStep === 'review' && empty($shipping['payment_method'])) {
            Redirect::to('/checkout/payment');
        }

        // Load cart data
        $cartItems = Database::select("SELECT c.*, p.name, p.price, p.discount_price, p.slug, p.stock_quantity, (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1) as image FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?", [Auth::id()]);
        if (empty($cartItems)) Redirect::to('/cart');

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

        $pageTitle = 'Checkout - ShopSmart';
        ob_start();
        include ROOT_PATH . '/resources/views/customer/checkout.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/app.php';
        $handled = true;
    }

    // Order success page
    if ($cleanUri === '/order-success' && $dispatchedMethod === 'GET') {
        $orderId = Session::get('last_order_id');
        $orderNum = Session::get('last_order_num');
        if (!$orderId) Redirect::to('/');
        $order = Database::selectOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
        $items = Database::select("SELECT * FROM order_items WHERE order_id = ?", [$orderId]);
        ob_start();
        include ROOT_PATH . '/resources/views/customer/order-success.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/app.php';
        $handled = true;
    }

    // Cart page
    if ($cleanUri === '/cart' && $dispatchedMethod === 'GET') {
        $userId = Auth::id();
        $sessionId = session_id();
        if ($userId) {
            $cartItems = Database::select("SELECT c.*, p.name, p.price, p.discount_price, p.slug, (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1) as image, b.name as brand_name FROM cart c JOIN products p ON c.product_id = p.id LEFT JOIN brands b ON p.brand_id = b.id WHERE c.user_id = ? ORDER BY c.created_at DESC", [$userId]);
        } else {
            $cartItems = Database::select("SELECT c.*, p.name, p.price, p.discount_price, p.slug, (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1) as image, b.name as brand_name FROM cart c JOIN products p ON c.product_id = p.id LEFT JOIN brands b ON p.brand_id = b.id WHERE c.session_id = ? ORDER BY c.created_at DESC", [$sessionId]);
        }
        $subtotal = 0;
        foreach ($cartItems as &$item) {
            $effectivePrice = $item['price'];
            if (!empty($item['discount_price']) && $item['discount_price'] < $item['price']) {
                $item['original_price'] = $item['price'];
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
        $couponDiscount = 0;
        $couponError = '';
        $appliedCoupon = '';
        $couponCode = Session::get('applied_coupon', null);
        if ($couponCode && $subtotal > 0) {
            $coupon = Database::selectOne("SELECT * FROM coupons WHERE code = ? AND is_active = 1", [$couponCode]);
            if ($coupon) {
                $now = date('Y-m-d H:i:s');
                $validFrom = $coupon['valid_from'] ?? null;
                $validUntil = $coupon['valid_until'] ?? null;
                if (($validFrom && $now < $validFrom) || ($validUntil && $now > $validUntil)) {
                    $couponError = 'This coupon has expired or is not yet valid.';
                    Session::remove('applied_coupon');
                } elseif ($coupon['usage_limit'] > 0 && $coupon['used_count'] >= $coupon['usage_limit']) {
                    $couponError = 'This coupon has reached its usage limit.';
                    Session::remove('applied_coupon');
                } elseif ($subtotal < ($coupon['min_order_amount'] ?? 0)) {
                    $couponError = 'Minimum order amount is ' . formatMoney($coupon['min_order_amount']) . ' for this coupon.';
                    Session::remove('applied_coupon');
                } else {
                    $appliedCoupon = $coupon['code'];
                    if ($coupon['type'] === 'percentage') {
                        $couponDiscount = $subtotal * ($coupon['value'] / 100);
                    } else {
                        $couponDiscount = $coupon['value'];
                    }
                    if ($coupon['max_discount_amount'] > 0 && $couponDiscount > $coupon['max_discount_amount']) {
                        $couponDiscount = $coupon['max_discount_amount'];
                    }
                }
            } else {
                Session::remove('applied_coupon');
            }
        }
        $afterDiscount = $subtotal - $couponDiscount;
        $tax = $afterDiscount * ($taxRate / 100);
        $total = $afterDiscount + $tax + $shippingCost;
        $pageTitle = 'Shopping Cart - ShopSmart';
        ob_start();
        include ROOT_PATH . '/resources/views/customer/cart.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/app.php';
        $handled = true;
    }

    // Account page
    if ($cleanUri === '/account' && $dispatchedMethod === 'GET') {
        if (!Auth::check()) Redirect::to('/login');
        $orders = Database::select("SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC LIMIT 5", [Auth::id()]);
        ob_start();
        include ROOT_PATH . '/resources/views/customer/account.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/app.php';
        $handled = true;
    }

    // Search page
    if ($cleanUri === '/search' && $dispatchedMethod === 'GET') {
        $q = Request::query('q', '');
        $page = (int)Request::query('page', 1);
        $where = "p.is_active = 1 AND (p.product_status IS NULL OR p.product_status IN ('active','out_of_stock_returning')) AND (p.name LIKE ? OR p.description LIKE ?)";
        $params = ["%$q%", "%$q%"];
        $paginated = Database::paginate("products p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN brands b ON p.brand_id = b.id", $page, 12, $where, $params, 'p.created_at DESC');
        foreach ($paginated['data'] as &$p) {
            $img = Database::selectOne("SELECT image_path FROM product_images WHERE product_id = ? AND is_primary = 1", [$p['id']]);
            $p['image'] = $img['image_path'] ?? null;
            $rating = Database::selectOne("SELECT AVG(rating) as avg, COUNT(*) as cnt FROM reviews WHERE product_id = ? AND is_approved = 1", [$p['id']]);
            $p['avg_rating'] = round($rating['avg'] ?? 0, 1);
            $p['review_count'] = (int)($rating['cnt'] ?? 0);
        }
        $products = $paginated['data'];
        $pagination = $paginated;
        $totalProducts = $paginated['total'];
        $query = $q;
        $categories = Database::select("SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id IN (SELECT id FROM categories WHERE id = c.id OR parent_id = c.id) AND is_active = 1) as product_count FROM categories c WHERE c.parent_id IS NULL AND c.is_active = 1 ORDER BY c.name");
        $brands = Database::select("SELECT b.*, (SELECT COUNT(*) FROM products WHERE brand_id = b.id AND is_active = 1) as product_count FROM brands b WHERE b.is_active = 1 ORDER BY b.name");
        $pageTitle = 'Search: ' . ($q ?: 'Products') . ' - ShopSmart';
        ob_start();
        include ROOT_PATH . '/resources/views/customer/search.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/app.php';
        $handled = true;
    }

    // Categories page
    if ($cleanUri === '/categories' && $dispatchedMethod === 'GET') {
        $categories = Database::select("SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id AND is_active = 1 AND (product_status IS NULL OR product_status IN ('active','out_of_stock_returning'))) as product_count FROM categories c WHERE c.parent_id IS NULL AND is_active = 1 ORDER BY sort_order");
        $subCategories = Database::select("SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id AND is_active = 1 AND (product_status IS NULL OR product_status IN ('active','out_of_stock_returning'))) as product_count FROM categories c WHERE c.parent_id IS NOT NULL AND c.is_active = 1 ORDER BY name");
        $pageTitle = 'Categories - ShopSmart';
        ob_start();
        include ROOT_PATH . '/resources/views/customer/categories.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/app.php';
        $handled = true;
    }

    // Admin dashboard
    if (preg_match('#^/admin/?$#', $cleanUri) && $dispatchedMethod === 'GET') {
        if (!Auth::check() || !Auth::isAdmin()) Redirect::to('/');
        ob_start();
        include ROOT_PATH . '/resources/views/admin/dashboard.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
        $handled = true;
    }

    if (!$handled) {
        // Dynamic route matching for category and product pages
        // Category: /category/{slug}
        if (preg_match('#^/category/([^/]+)$#', $cleanUri, $m)) {
            $slug = $m[1];

            // Find category and all child IDs (recursive)
            $category = Database::selectOne("SELECT * FROM categories WHERE slug = ? AND is_active = 1", [$slug]);
            if ($category) {
                $catIds = [(int)$category['id']];
                // Recursively get child category IDs
                $childCats = Database::select("SELECT id FROM categories WHERE parent_id = ? AND is_active = 1", [$category['id']]);
                foreach ($childCats as $child) {
                    $catIds[] = (int)$child['id'];
                    // One more level deep
                    $grandCats = Database::select("SELECT id FROM categories WHERE parent_id = ? AND is_active = 1", [$child['id']]);
                    foreach ($grandCats as $gc) $catIds[] = (int)$gc['id'];
                }
                $catIdList = implode(',', array_fill(0, count($catIds), '?'));
            } else {
                $catIdList = '?';
                $catIds = [0];
            }

            // Build WHERE clause
            $page = (int)Request::query('page', 1);
            $where = "p.is_active = 1 AND (p.product_status IS NULL OR p.product_status IN ('active','out_of_stock_returning')) AND p.category_id IN ({$catIdList})";
            $params = $catIds;

            // Brand filter
            $brandSlug = Request::query('brand', '');
            if ($brandSlug) {
                $where .= " AND b.slug = ?";
                $params[] = $brandSlug;
            }

            // Price filter
            $minPrice = Request::query('min_price', '');
            $maxPrice = Request::query('max_price', '');
            if ($minPrice !== '') { $where .= " AND COALESCE(p.discount_price, p.price) >= ?"; $params[] = (float)$minPrice; }
            if ($maxPrice !== '' && $maxPrice < PHP_INT_MAX) { $where .= " AND COALESCE(p.discount_price, p.price) <= ?"; $params[] = (float)$maxPrice; }

            // Search filter
            $search = Request::query('q', '');
            if ($search) { $where .= " AND (p.name LIKE ? OR p.description LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

            // Sort
            $sort = Request::query('sort', 'newest');
            $orderBy = match($sort) {
                'price_asc' => 'COALESCE(p.discount_price, p.price) ASC',
                'price_desc' => 'COALESCE(p.discount_price, p.price) DESC',
                'popular' => 'p.created_at DESC',
                'rating' => '(SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND is_approved = 1) DESC',
                default => 'p.created_at DESC',
            };

            // Paginate with brand join
            $from = "products p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN brands b ON p.brand_id = b.id";
            $paginated = Database::paginate($from, $page, 12, $where, $params, $orderBy);

            // Enrich products with image, rating, wishlist status
            foreach ($paginated['data'] as &$p) {
                $img = Database::selectOne("SELECT image_path FROM product_images WHERE product_id = ? AND is_primary = 1", [$p['id']]);
                $p['image'] = $img['image_path'] ?? null;
                $rating = Database::selectOne("SELECT AVG(rating) as avg, COUNT(*) as cnt FROM reviews WHERE product_id = ? AND is_approved = 1", [$p['id']]);
                $p['avg_rating'] = round($rating['avg'] ?? 0, 1);
                $p['review_count'] = (int)($rating['cnt'] ?? 0);
            }

            $products = $paginated['data'];
            $pagination = $paginated;
            $totalProducts = $paginated['total'];

            // Sidebar data with product counts
            $categories = Database::select("SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id IN (SELECT id FROM categories WHERE id = c.id OR parent_id = c.id) AND is_active = 1) as product_count FROM categories c WHERE c.parent_id IS NULL AND c.is_active = 1 ORDER BY c.name");
            $brands = Database::select("SELECT b.*, (SELECT COUNT(*) FROM products WHERE brand_id = b.id AND is_active = 1) as product_count FROM brands b WHERE b.is_active = 1 ORDER BY b.name");

            $currentCategory = $category;
            $selectedCategory = $category['id'] ?? null;
            $pageTitle = ($category['name'] ?? 'Category') . ' - ShopSmart';

            ob_start();
            include ROOT_PATH . '/resources/views/customer/products.php';
            $content = ob_get_clean();
            include ROOT_PATH . '/resources/views/layouts/app.php';
            $handled = true;
        }

        // Product: /product/{slug}
        if (preg_match('#^/product/([^/]+)$#', $cleanUri, $m)) {
            $slug = $m[1];
            $product = Database::selectOne("SELECT p.*, c.name as category_name, c.slug as category_slug, b.name as brand_name FROM products p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN brands b ON p.brand_id = b.id WHERE p.slug = ? AND p.is_active = 1 AND (p.product_status IS NULL OR p.product_status IN ('active','out_of_stock_returning'))", [$slug]);
            if ($product) {
                $product['images'] = Database::select("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order", [$product['id']]);
                $product['reviews'] = Database::select("SELECT r.*, u.name as user_name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? AND r.is_approved = 1 ORDER BY r.created_at DESC", [$product['id']]);
                $related = Database::select("SELECT p.*, (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1) as image FROM products p WHERE p.category_id = ? AND p.id != ? AND p.is_active = 1 ORDER BY RAND() LIMIT 4", [$product['category_id'], $product['id']]);
                $pageTitle = $product['name'] . ' - ShopSmart';
                ob_start();
                include ROOT_PATH . '/resources/views/customer/product-detail.php';
                $content = ob_get_clean();
                include ROOT_PATH . '/resources/views/layouts/app.php';
                $handled = true;
            }
        }

        // Admin order detail: /admin/orders/{id}
        if (preg_match('#^/admin/orders/(\d+)$#', $cleanUri, $m) && $dispatchedMethod === 'GET') {
            if (!Auth::check() || !Auth::isAdmin()) Redirect::to('/');
            $id = $m[1];
            $order = Database::selectOne("SELECT * FROM orders WHERE id = ?", [$id]);
            if ($order) {
                $items = Database::select("SELECT * FROM order_items WHERE order_id = ?", [$id]);
                $customer = $order['customer_id'] ? Database::selectOne("SELECT * FROM users WHERE id = ?", [$order['customer_id']]) : null;
                $transactions = Database::select("SELECT * FROM transactions WHERE order_id = ? ORDER BY created_at DESC", [$id]);
                $breadcrumbs = [['Orders', '/admin/orders'], ['Order ' . $order['order_number'], '']];
                ob_start();
                include ROOT_PATH . '/resources/views/admin/order-detail.php';
                $content = ob_get_clean();
                include ROOT_PATH . '/resources/views/layouts/admin.php';
                $handled = true;
            }
        }

        // Admin customer detail: /admin/customers/{id}
        if (preg_match('#^/admin/customers/(\d+)$#', $cleanUri, $m) && $dispatchedMethod === 'GET') {
            if (!Auth::check() || !Auth::isAdmin()) Redirect::to('/');
            $id = $m[1];
            $customer = Database::selectOne("SELECT * FROM users WHERE id = ? AND role = 'customer'", [$id]);
            if ($customer) {
                $orders = Database::select("SELECT o.*, (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count FROM orders o WHERE o.customer_id = ? ORDER BY o.created_at DESC LIMIT 20", [$id]);
                $stats = Database::selectOne("SELECT COUNT(*) as total, COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total ELSE 0 END), 0) as spent, COALESCE(AVG(CASE WHEN payment_status = 'paid' THEN total END), 0) as avg_order FROM orders WHERE customer_id = ?", [$id]);
                $breadcrumbs = [['Customers', '/admin/customers'], [e($customer['name']), '']];
                ob_start();
                include ROOT_PATH . '/resources/views/admin/customer-detail.php';
                $content = ob_get_clean();
                include ROOT_PATH . '/resources/views/layouts/admin.php';
                $handled = true;
            }
        }

        // Admin sub-pages
        if (!$handled && preg_match('#^/admin/(.+)$#', $cleanUri, $m) && $dispatchedMethod === 'GET') {
            if (!Auth::check() || !Auth::isAdmin()) Redirect::to('/');
            $adminPage = $m[1];
            $viewMap = [
                'products' => 'admin/products.php',
                'products/create' => 'admin/product-form.php',
                'categories' => 'admin/categories.php',
                'brands' => 'admin/brands.php',
                'inventory' => 'admin/inventory.php',
                'orders' => 'admin/orders.php',
                'pos-sales' => 'admin/pos-sales.php',
                'customers' => 'admin/customers.php',
                'users' => 'admin/users.php',
                'reports' => 'admin/reports.php',
                'settings' => 'admin/settings.php',
                'ai-marketing' => 'admin/marketing/ai-marketing.php',
                'marketing/facebook' => 'admin/marketing/facebook.php',
                'marketing/whatsapp' => 'admin/marketing/whatsapp.php',
                'marketing/email' => 'admin/marketing/email.php',
                'payments' => 'admin/payments/payments.php',
                'payment-settings' => 'admin/payments/settings.php',
                'api-integrations' => 'admin/api-integrations.php',
            ];
            if (isset($viewMap[$adminPage])) {
                $breadcrumbs = [[ucwords(str_replace(['-', '/'], ' ', $adminPage)), '']];
                ob_start();
                include ROOT_PATH . '/resources/views/' . $viewMap[$adminPage];
                $content = ob_get_clean();
                include ROOT_PATH . '/resources/views/layouts/admin.php';
                $handled = true;
            }
        }
    }

    // POS API fallback (if Router didn't match — safety net)
    if (!$handled && (str_starts_with($cleanUri, '/api/pos/') || str_starts_with($cleanUri, '/api/commissions/'))) {
        header('Content-Type: application/json');
        $handled = true;
        @file_put_contents(ROOT_PATH . '/storage/logs/route-debug.log',
            date('Y-m-d H:i:s') . " | POS_FALLBACK | uri={$cleanUri} | method={$dispatchedMethod} | auth=" . (Auth::check() ? 'Y' : 'N') . " | session=" . session_id() . "\n",
            FILE_APPEND);
        try {
            // Health check doesn't need auth
            if ($cleanUri === '/api/pos/health' && $dispatchedMethod === 'GET') {
                echo json_encode(['success'=>true,'auth'=>Auth::check(),'user_id'=>Auth::id(),'user_role'=>Auth::check()?Session::get('user_role'):'none','session_id'=>session_id(),'fallback'=>true,'time'=>date('Y-m-d H:i:s')]);
                exit;
            }
            if (!Auth::check()) { echo json_encode(['success'=>false,'error'=>'Not authenticated']); exit; }
            $uid = Auth::id();
            $now = date('Y-m-d H:i:s');

            if ($cleanUri === '/api/pos/holds' && $dispatchedMethod === 'GET') {
                $h = Database::select("SELECT id, user_id, cashier_name, items_count, total, customer_name, notes, created_at FROM pos_holds WHERE user_id = ? ORDER BY created_at DESC", [$uid]);
                echo json_encode(['success'=>true,'holds'=>$h]);
                exit;
            }
            if ($cleanUri === '/api/pos/holds' && $dispatchedMethod === 'POST') {
                $cart = json_decode(Request::post('cart', '[]'), true) ?: [];
                if (empty($cart)) { echo json_encode(['success'=>false,'error'=>'Cart is empty']); exit; }
                $sub = array_sum(array_map(fn($i) => (float)($i['price'] ?? 0) * (int)($i['qty'] ?? 0), $cart));
                $taxRate = (float)(Database::selectOne("SELECT value FROM settings WHERE `key` = 'tax_rate'")['value'] ?? 16);
                Database::insert('pos_holds', ['user_id'=>$uid,'cashier_name'=>Auth::user()['name']??'Cashier','cart_data'=>json_encode($cart),'items_count'=>count($cart),'subtotal'=>$sub,'tax'=>$sub*($taxRate/100),'total'=>$sub*(1+$taxRate/100),'customer_name'=>'','notes'=>Request::post('notes',''),'created_at'=>$now]);
                echo json_encode(['success'=>true]); exit;
            }
            if (preg_match('#^/api/pos/holds/restore/(\d+)$#', $cleanUri, $m) && $dispatchedMethod === 'POST') {
                $hold = Database::selectOne("SELECT * FROM pos_holds WHERE id = ? AND user_id = ?", [$m[1], $uid]);
                if (!$hold) { echo json_encode(['success'=>false,'error'=>'Not found']); exit; }
                Session::set('pos_cart', json_decode($hold['cart_data'], true) ?: []);
                Database::delete('pos_holds', 'id = ?', [$m[1]]);
                echo json_encode(['success'=>true,'cart'=>json_decode($hold['cart_data'], true)?:[]]); exit;
            }
            if (preg_match('#^/api/pos/holds/(\d+)$#', $cleanUri, $m) && $dispatchedMethod === 'DELETE') {
                Database::delete('pos_holds', 'id = ? AND user_id = ?', [$m[1], $uid]);
                echo json_encode(['success'=>true]); exit;
            }
            if ($cleanUri === '/api/pos/checkout' && $dispatchedMethod === 'POST') {
                echo json_encode(['success'=>false,'error'=>'Use the main POS terminal']); exit;
            }
            if ($cleanUri === '/api/commissions/balance' && $dispatchedMethod === 'GET') {
                $p = (float)(Database::selectOne("SELECT COALESCE(SUM(amount),0) as t FROM commissions WHERE status='pending' AND user_id=?", [$uid])['t']??0);
                $pd = (float)(Database::selectOne("SELECT COALESCE(SUM(amount),0) as t FROM commissions WHERE status='paid' AND user_id=?", [$uid])['t']??0);
                echo json_encode(['success'=>true,'pending'=>$p,'paid'=>$pd,'earned'=>$p+$pd]); exit;
            }
            echo json_encode(['success'=>false,'error'=>'Unknown endpoint']);
        } catch (\Throwable $e) {
            echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
        }
        exit;
    }

    // If still not handled, show styled 404
    if (!$handled) {
        if (class_exists('ErrorHandler')) {
            ErrorHandler::render(404, 'Page Not Found', "The page you're looking for doesn't exist or has been moved.");
        } else {
            http_response_code(404);
            View::render('errors/404');
        }
    }
}