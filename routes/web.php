<?php

// ============================================================
// Self-Heal: Ensure required columns/tables exist
// ============================================================
try {
    $db = Database::getConnection();
    try { $db->exec("ALTER TABLE `orders` ADD COLUMN `cashier_id` INT UNSIGNED DEFAULT NULL AFTER `is_pos`"); } catch (\Throwable $e) {}
    try { $db->exec("ALTER TABLE `orders` ADD COLUMN `referral_code` VARCHAR(50) DEFAULT NULL AFTER `cashier_id`"); } catch (\Throwable $e) {}
    try { $db->exec("ALTER TABLE `users` ADD COLUMN `last_login` DATETIME DEFAULT NULL AFTER `updated_at`"); } catch (\Throwable $e) {}
    try { $db->exec("ALTER TABLE `users` ADD COLUMN `referral_code` VARCHAR(50) DEFAULT NULL AFTER `country`"); } catch (\Throwable $e) {}
    try { $db->exec("ALTER TABLE `users` ADD COLUMN `menu_permissions` TEXT DEFAULT NULL AFTER `referral_code`"); } catch (\Throwable $e) {}
    try { $db->exec("ALTER TABLE `promo_banners` ADD COLUMN `image_url` TEXT DEFAULT NULL AFTER `icon`"); } catch (\Throwable $e) {}
    try { $db->exec("ALTER TABLE `orders` ADD COLUMN `receiver_name` VARCHAR(255) DEFAULT NULL AFTER `shipping_longitude`"); } catch (\Throwable $e) {}
    try { $db->exec("ALTER TABLE `orders` ADD COLUMN `apartment` VARCHAR(255) DEFAULT NULL AFTER `receiver_name`"); } catch (\Throwable $e) {}
    try { $db->exec("ALTER TABLE `orders` ADD COLUMN `street` VARCHAR(255) DEFAULT NULL AFTER `apartment`"); } catch (\Throwable $e) {}
    try { $db->exec("ALTER TABLE `orders` ADD COLUMN `house_no` VARCHAR(255) DEFAULT NULL AFTER `street`"); } catch (\Throwable $e) {}
    try { $db->exec("ALTER TABLE `orders` ADD COLUMN `landmark` VARCHAR(255) DEFAULT NULL AFTER `house_no`"); } catch (\Throwable $e) {}
    try { $db->exec("ALTER TABLE `orders` ADD COLUMN `delivery_instructions` TEXT DEFAULT NULL AFTER `landmark`"); } catch (\Throwable $e) {}
    $db->exec("CREATE TABLE IF NOT EXISTS `referral_withdrawals` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT UNSIGNED NOT NULL,
        `amount` DOUBLE NOT NULL DEFAULT 0,
        `status` ENUM('pending','approved','rejected','paid') NOT NULL DEFAULT 'pending',
        `payment_details` TEXT DEFAULT NULL,
        `admin_notes` TEXT DEFAULT NULL,
        `processed_at` DATETIME DEFAULT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_user_id` (`user_id`),
        INDEX `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("CREATE TABLE IF NOT EXISTS `pos_holds` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT UNSIGNED DEFAULT NULL,
        `cashier_name` VARCHAR(255) NOT NULL DEFAULT '',
        `cart_data` LONGTEXT NOT NULL,
        `items_count` INT UNSIGNED NOT NULL DEFAULT 0,
        `subtotal` DOUBLE NOT NULL DEFAULT 0,
        `tax` DOUBLE NOT NULL DEFAULT 0,
        `total` DOUBLE NOT NULL DEFAULT 0,
        `customer_name` VARCHAR(255) NOT NULL DEFAULT '',
        `notes` TEXT DEFAULT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("CREATE TABLE IF NOT EXISTS `commissions` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT UNSIGNED NOT NULL,
        `order_id` INT UNSIGNED DEFAULT NULL,
        `order_number` VARCHAR(100) NOT NULL DEFAULT '',
        `amount` DOUBLE NOT NULL DEFAULT 0,
        `percentage` DOUBLE NOT NULL DEFAULT 0,
        `order_total` DOUBLE NOT NULL DEFAULT 0,
        `status` ENUM('pending','paid') NOT NULL DEFAULT 'pending',
        `paid_at` DATETIME DEFAULT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("CREATE TABLE IF NOT EXISTS `custom_payment_methods` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `slug` VARCHAR(100) NOT NULL,
        `icon` VARCHAR(100) NOT NULL DEFAULT 'credit-card',
        `color` VARCHAR(50) NOT NULL DEFAULT 'gray',
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `sort_order` INT NOT NULL DEFAULT 0,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("CREATE TABLE IF NOT EXISTS `cities` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(255) NOT NULL,
        `shipping_cost` DOUBLE NOT NULL DEFAULT 0,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `sort_order` INT NOT NULL DEFAULT 0,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("CREATE TABLE IF NOT EXISTS `page_views` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `session_id` VARCHAR(255) NOT NULL,
        `user_id` INT UNSIGNED DEFAULT NULL,
        `url` VARCHAR(500) NOT NULL,
        `referrer` VARCHAR(500) DEFAULT NULL,
        `user_agent` VARCHAR(500) DEFAULT NULL,
        `ip_address` VARCHAR(45) DEFAULT NULL,
        `country` VARCHAR(100) DEFAULT NULL,
        `device_type` VARCHAR(50) DEFAULT NULL,
        `browser` VARCHAR(100) DEFAULT NULL,
        `os` VARCHAR(100) DEFAULT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_created_at` (`created_at`),
        INDEX `idx_url` (`url`(255)),
        INDEX `idx_session_id` (`session_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("CREATE TABLE IF NOT EXISTS `blog_posts` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(255) NOT NULL,
        `slug` VARCHAR(255) NOT NULL,
        `excerpt` TEXT DEFAULT NULL,
        `content` LONGTEXT NOT NULL,
        `featured_image` VARCHAR(500) DEFAULT NULL,
        `author_id` INT UNSIGNED DEFAULT NULL,
        `is_published` TINYINT(1) NOT NULL DEFAULT 0,
        `meta_title` VARCHAR(255) DEFAULT NULL,
        `meta_description` VARCHAR(500) DEFAULT NULL,
        `views` INT UNSIGNED NOT NULL DEFAULT 0,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `slug` (`slug`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("CREATE TABLE IF NOT EXISTS `referrals` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `referrer_id` INT UNSIGNED NOT NULL,
        `referred_id` INT UNSIGNED DEFAULT NULL,
        `referral_code` VARCHAR(50) NOT NULL,
        `ip_address` VARCHAR(45) DEFAULT NULL,
        `status` ENUM('pending','completed','paid') NOT NULL DEFAULT 'pending',
        `order_id` INT UNSIGNED DEFAULT NULL,
        `order_total` DOUBLE NOT NULL DEFAULT 0,
        `commission_amount` DOUBLE NOT NULL DEFAULT 0,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `completed_at` DATETIME DEFAULT NULL,
        UNIQUE KEY `referral_code` (`referral_code`),
        INDEX `idx_referrer_id` (`referrer_id`),
        INDEX `idx_referred_id` (`referred_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $usersWithoutRef = Database::select("SELECT u.id FROM users u LEFT JOIN referrals r ON r.referrer_id = u.id AND r.referred_id IS NULL WHERE r.id IS NULL");
    foreach ($usersWithoutRef as $u) {
        $code = 'REF' . strtoupper(substr(md5($u['id'] . time() . mt_rand()), 0, 8));
        try { Database::insert('referrals', ['referrer_id' => $u['id'], 'referral_code' => $code, 'created_at' => date('Y-m-d H:i:s')]); } catch(\Throwable $e) {}
    }
    $db->exec("CREATE TABLE IF NOT EXISTS `make_webhook_log` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `event` VARCHAR(100) DEFAULT NULL,
        `url` VARCHAR(500) DEFAULT NULL,
        `payload` LONGTEXT DEFAULT NULL,
        `http_code` INT DEFAULT NULL,
        `response` LONGTEXT DEFAULT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_event` (`event`),
        INDEX `idx_created` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (\Throwable $e) {
    @file_put_contents(ROOT_PATH . '/storage/logs/pos-api.log', date('Y-m-d H:i:s') . " | SELF-HEAL ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
}

// Track referral visits via ?ref=CODE
$refCode = Request::query('ref', '');
if ($refCode) {
    $ref = Database::selectOne("SELECT * FROM referrals WHERE referral_code = ?", [$refCode]);
    if ($ref) {
        // Save to both session and cookie
        Session::set('referral_code', $refCode);
        setcookie('referral_code', $refCode, time() + 86400 * 90, '/');
        // If user is not logged in, redirect to register
        if (!Auth::check()) {
            Redirect::to('/register?ref=' . urlencode($refCode));
            exit;
        }
    }
}

// ============================================================
// Global Helper Functions
// ============================================================
if (!function_exists('getStoreName')) {
    function getStoreName(): string {
        try {
            $row = Database::selectOne("SELECT value FROM settings WHERE `key` = 'store_name'");
            return $row ? $row['value'] : 'ShopSmart';
        } catch (\Throwable $e) {
            return 'ShopSmart';
        }
    }
}

if (!function_exists('getCurrencySymbol')) {
    function getCurrencySymbol(): string {
        try {
            $row = Database::selectOne("SELECT value FROM settings WHERE `key` = 'currency_symbol'");
            return $row && !empty($row['value']) ? $row['value'] : 'KSh';
        } catch (\Throwable $e) {
            return 'KSh';
        }
    }
}

if (!function_exists('getShippingThreshold')) {
    function getShippingThreshold(): float {
        try {
            $row = Database::selectOne("SELECT value FROM settings WHERE `key` = 'shipping_threshold'");
            return $row && !empty($row['value']) ? (float)$row['value'] : 5000;
        } catch (\Throwable $e) {
            return 5000;
        }
    }
}

if (!function_exists('formatMoney')) {
    function formatMoney($amount, $decimals = 2): string {
        return getCurrencySymbol() . ' ' . number_format((float)$amount, $decimals);
    }
}

// ============================================================
// POS Terminal Pages
// ============================================================
$router->get('/pos', 'PosPageController@terminal');
$router->get('/pos/holds', 'PosPageController@holds');
$router->get('/cashier', 'PosPageController@cashier');

// ============================================================
// POS API Routes (Controllers)
// ============================================================
$router->post('/api/pos/cart', 'PosController@cart');
$router->get('/api/pos/products', 'PosController@products');
$router->post('/api/pos/checkout', 'PosController@checkout');
$router->post('/api/pos/stk-push', 'PosController@stkPush');
$router->post('/api/pos/cloudone-stk-push', 'PosController@cloudoneStkPush');
$router->post('/api/pos/stk-status', 'PosController@stkStatus');
$router->get('/api/pos/holds', 'PosController@holds');
$router->post('/api/pos/holds', 'PosController@holdStore');
$router->post('/api/pos/holds/restore/{id}', 'PosController@holdRestore');
$router->delete('/api/pos/holds/{id}', 'PosController@holdDelete');
$router->get('/api/pos/health', 'PosController@health');

// ============================================================
// Commission API Routes
// ============================================================
$router->get('/api/commissions/balance', 'CommissionController@balance');
$router->get('/api/commissions/history', 'CommissionController@history');
$router->post('/api/commissions/pay/{id}', 'CommissionController@pay');

// ============================================================
// Checkout API Routes
// ============================================================
$router->post('/api/checkout/create-order', 'CheckoutController@createOrder');
$router->post('/api/checkout/pay', 'CheckoutController@pay');
$router->post('/api/checkout/pesapal-callback', 'CheckoutController@pesapalCallback');
$router->get('/api/checkout/stripe-success', 'CheckoutController@stripeSuccess');

// ============================================================
// Payment API Routes
// ============================================================
$router->post('/api/payments/initiate', 'PaymentController@initiate');
$router->post('/api/payments/callback/mpesa', 'PaymentController@mpesaCallback');
$router->post('/api/payments/callback/intasend', 'PaymentController@intasendCallback');
$router->post('/api/payments/callback/pesapal', 'PaymentController@pesapalCallback');

// ============================================================
// Payment Methods API Routes
// ============================================================
$router->get('/api/payment-methods', 'PaymentMethodController@index');
$router->post('/api/payment-methods', 'PaymentMethodController@store');
$router->put('/api/payment-methods/{id}', 'PaymentMethodController@update');
$router->post('/api/payment-methods/{id}', 'PaymentMethodController@update');
$router->delete('/api/payment-methods/{id}', 'PaymentMethodController@delete');

// ============================================================
// M-Pesa Callback
// ============================================================
$router->post('/api/mpesa/callback', 'MpesaCallbackController@handle');
$router->post('/api/mpesa/callback.php', 'MpesaCallbackController@handle');

// ============================================================
// Webhook API Routes
// ============================================================
$router->post('/api/webhooks/mpesa', 'WebhookController@mpesa');
$router->post('/api/webhooks/stripe', 'WebhookController@stripe');
$router->post('/api/webhooks/intasend', 'WebhookController@intasend');

// ============================================================
// Settings API Routes
// ============================================================
$router->get('/api/settings', 'SettingsController@publicSettings');

// ============================================================
// Public / Customer Routes
// ============================================================
$router->get('/', 'HomeController@index');
$router->get('/products', 'ProductController@index');
$router->get('/product/{slug}', 'ProductController@show');
$router->get('/category/{slug}', 'ProductController@category');
$router->get('/categories', 'ProductController@allCategories');
$router->get('/search', 'ProductController@search');

// Cart
$router->get('/cart', 'CartController@index');
$router->post('/cart/add', 'CartController@add');
$router->post('/cart/update', 'CartController@update');
$router->post('/cart/remove/{id}', 'CartController@remove');
$router->post('/cart/coupon', 'CartController@coupon');

// Exchange rate API (small utility — kept inline)
$router->get('/api/exchange-rate', function() {
    header('Content-Type: application/json');
    $currency = strtoupper(Request::query('currency', 'USD'));
    $cdnUrl = 'https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/kes.json';
    $ch = curl_init($cdnUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $rate = false;
    if ($httpCode >= 200 && $httpCode < 300 && $response) {
        $data = json_decode($response, true);
        $target = strtolower($currency);
        if (isset($data['kes'][$target])) {
            $rate = (float)$data['kes'][$target];
        }
    }
    if ($rate === false) {
        $apiUrl = "https://v6.exchangerate-api.com/v6/latest/KES";
        $ch2 = curl_init($apiUrl);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch2, CURLOPT_TIMEOUT, 10);
        $resp2 = curl_exec($ch2);
        $code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
        curl_close($ch2);
        if ($code2 >= 200 && $code2 < 300 && $resp2) {
            $d2 = json_decode($resp2, true);
            if (isset($d2['rates'][$currency])) {
                $rate = (float)$d2['rates'][$currency];
            }
        }
    }
    if ($rate !== false && $rate > 0) {
        $kesPerUnit = round(1 / $rate, 2);
        echo json_encode(['success' => true, 'rate' => $rate, 'kes_per_unit' => $kesPerUnit, 'currency' => $currency, 'formatted' => "1 {$currency} = {$kesPerUnit} KES"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Could not fetch exchange rate. Using saved rate.']);
    }
});

// Payment initiation (customer checkout flow)
$router->post('/payment/initiate', 'CustomerPaymentController@initiate');
$router->post('/order/pay/{id}', 'CustomerPaymentController@initiateOrderPay');
$router->get('/order/pay/{id}', 'CustomerPaymentController@showOrderPay');
$router->get('/payment/status', 'CustomerPaymentController@status');
$router->post('/payment/mpesa/callback', 'CustomerPaymentController@mpesaCallback');
$router->get('/payment/intasend/callback', 'CustomerPaymentController@intasendCallback');
$router->get('/payment/intasend/checkout/{id}', 'CustomerPaymentController@intasendCheckout');
$router->get('/payment/paypal/callback', 'CustomerPaymentController@paypalCallback');
$router->post('/payment/paypal/create-order', 'CustomerPaymentController@paypalCreateOrder');
$router->post('/payment/paypal/capture', 'CustomerPaymentController@paypalCapture');
$router->get('/payment/stripe/success', 'CustomerPaymentController@stripeSuccess');
$router->get('/payment/pesapal/redirect', 'CustomerPaymentController@pesapalRedirect');
$router->get('/payment/pesapal/checkout/{order_id}', 'CustomerPaymentController@pesapalCheckout');
$router->get('/payment/pesapal/callback', 'CustomerPaymentController@pesapalCallback');
$router->post('/payment/pesapal/ipn', 'CustomerPaymentController@pesapalIPN');

// Checkout pages
$router->get('/checkout', 'CheckoutPageController@index');
$router->get('/checkout/shipping', 'CheckoutPageController@shipping');
$router->post('/checkout/shipping', 'CheckoutPageController@storeShipping');
$router->get('/checkout/payment', 'CheckoutPageController@payment');
$router->post('/checkout/payment', 'CheckoutPageController@storePayment');
$router->get('/checkout/review', 'CheckoutPageController@review');
$router->post('/checkout/review', 'CheckoutPageController@storeOrder');
$router->get('/order-success', 'CheckoutPageController@success');

// Blog
$router->get('/blog', 'BlogController@index');
$router->get('/blog/{slug}', 'BlogController@show');

// Pages
$router->get('/page/{slug}', 'PageController@show');
$router->post('/page/contact-us', 'PageController@contactSubmit');

// Referral link redirect
$router->get('/ref/{code}', 'ReferralController@handle');

// Auth
$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->get('/register', 'AuthController@showRegister');
$router->post('/register', 'AuthController@register');
$router->get('/forgot-password', 'AuthController@showForgotPassword');
$router->post('/forgot-password', 'AuthController@sendResetLink');
$router->get('/reset-password', 'AuthController@showResetPassword');
$router->post('/reset-password', 'AuthController@resetPassword');
$router->get('/logout', 'AuthController@logout');

// Account
$router->get('/account', 'AccountController@dashboard');
$router->get('/account/orders', 'AccountController@orders');
$router->get('/account/orders/{id}/track', 'AccountController@trackOrder');
$router->get('/account/wishlist', 'AccountController@wishlist');
$router->post('/wishlist/toggle/{productId}', 'AccountController@toggleWishlist');
$router->post('/wishlist/remove', 'AccountController@removeWishlist');
$router->get('/orders/{orderNumber}/track', 'AccountController@trackOrderByNumber');
$router->get('/account/profile', 'AccountController@profile');
$router->post('/account/profile', 'AccountController@updateProfile');
$router->get('/account/reviews', 'AccountController@reviews');
$router->post('/reviews/submit', 'AccountController@submitReview');
$router->post('/account/reviews/{id}/delete', 'AccountController@deleteReview');
$router->get('/account/addresses', 'AccountController@addresses');
$router->post('/account/addresses', 'AccountController@storeAddress');
$router->get('/account/change-password', 'AccountController@showChangePassword');
$router->post('/account/change-password', 'AccountController@changePassword');

// ============================================================
// Admin Routes
// ============================================================
$router->group(['prefix' => 'admin', 'middleware' => 'admin'], function($router) {
    // Dashboard
    $router->get('/', 'AdminDashboardController@index');

    // Products
    $router->get('/products', 'AdminProductController@index');
    $router->get('/products/create', 'AdminProductController@create');
    $router->post('/products/store', 'AdminProductController@store');
    $router->get('/products/{id}/edit', 'AdminProductController@edit');
    $router->post('/products/{id}/update', 'AdminProductController@update');
    $router->post('/products/{id}/delete', 'AdminProductController@delete');
    $router->post('/products/{id}/duplicate', 'AdminProductController@duplicate');
    $router->post('/products/{id}/delete-image/{imageId}', 'AdminProductController@deleteImage');
    $router->get('/products/{id}/reviews', 'AdminProductController@reviews');
    $router->post('/products/{id}/reviews/{reviewId}/approve', 'AdminProductController@approveReview');
    $router->post('/products/{id}/reviews/{reviewId}/reject', 'AdminProductController@rejectReview');
    $router->post('/products/{id}/reviews/{reviewId}/delete', 'AdminProductController@deleteReview');

    // Categories
    $router->get('/categories', 'AdminCategoryController@index');
    $router->post('/categories/store', 'AdminCategoryController@store');
    $router->post('/categories/bulk-delete', 'AdminCategoryController@bulkDelete');
    $router->post('/categories/{id}/update', 'AdminCategoryController@update');
    $router->post('/categories/{id}/delete', 'AdminCategoryController@delete');

    // Brands
    $router->get('/brands', 'AdminBrandController@index');
    $router->post('/brands/store', 'AdminBrandController@store');
    $router->post('/brands/bulk-delete', 'AdminBrandController@bulkDelete');
    $router->post('/brands/{id}/delete', 'AdminBrandController@delete');
    $router->post('/brands/{id}/update', 'AdminBrandController@update');

    // Inventory
    $router->get('/inventory', 'AdminInventoryController@index');
    $router->post('/inventory/adjust', 'AdminInventoryController@adjust');

    // Orders
    $router->get('/orders', 'AdminOrderController@index');
    $router->post('/orders/bulk-delete', 'AdminOrderController@bulkDelete');
    $router->get('/orders/{id}', 'AdminOrderController@show');
    $router->post('/orders/{id}/status', 'AdminOrderController@updateStatus');
    $router->post('/orders/{id}/notes', 'AdminOrderController@updateNotes');
    $router->get('/pos-sales', 'AdminOrderController@posSales');

    // Customers
    $router->get('/customers', 'AdminCustomerController@index');
    $router->get('/customers/{id}', 'AdminCustomerController@show');

    // Users
    $router->get('/users', 'AdminUserController@index');
    $router->post('/users/store', 'AdminUserController@store');
    $router->post('/users/{id}/delete', 'AdminUserController@delete');
    $router->post('/users/{id}/update', 'AdminUserController@update');

    // Marketing — Social
    $router->get('/marketing/social', 'AdminMarketingController@social');
    $router->get('/marketing/product-publish', 'AdminMarketingController@productPublish');
    $router->post('/marketing/social/connect', 'AdminMarketingController@socialConnect');
    $router->post('/marketing/social/connect-publer', 'AdminMarketingController@connectPubler');
    $router->post('/marketing/social/publish-product', 'AdminMarketingController@publishProduct');
    $router->post('/marketing/social/accounts', 'AdminMarketingController@socialAccounts');
    $router->post('/marketing/social/subaccounts', 'AdminMarketingController@socialSubaccounts');
    $router->post('/marketing/social/pinterest-boards', 'AdminMarketingController@pinterestBoards');
    $router->post('/marketing/social/publish', 'AdminMarketingController@socialPublish');
    $router->get('/marketing/social/post-status', 'AdminMarketingController@socialPostStatus');
    $router->get('/marketing/social/posts', 'AdminMarketingController@socialPosts');
    $router->get('/marketing/social/analytics', 'AdminMarketingController@socialAnalytics');
    $router->get('/marketing/social/templates', 'AdminMarketingController@socialTemplates');
    $router->post('/marketing/social/create-visual', 'AdminMarketingController@socialCreateVisual');
    $router->get('/marketing/social/visual-status', 'AdminMarketingController@socialVisualStatus');
    $router->post('/marketing/social/upload', 'AdminMarketingController@socialUpload');
    $router->get('/marketing/social/schedules', 'AdminMarketingController@socialSchedules');
    $router->post('/marketing/social/schedule-update', 'AdminMarketingController@socialScheduleUpdate');
    $router->post('/marketing/social/schedule-delete', 'AdminMarketingController@socialScheduleDelete');

    // Marketing — WhatsApp
    $router->get('/marketing/whatsapp', 'AdminMarketingController@whatsapp');
    $router->post('/marketing/whatsapp/settings', 'AdminMarketingController@whatsappSettings');
    $router->post('/marketing/whatsapp/test', 'AdminMarketingController@whatsappTest');
    $router->post('/marketing/whatsapp/send', 'AdminMarketingController@whatsappSend');

    // Marketing — Email
    $router->get('/marketing/email', 'AdminMarketingController@email');

    // Payments
    $router->get('/payments', 'AdminPaymentSettingsController@index');
    $router->get('/payments/settings', 'AdminPaymentSettingsController@settings');
    $router->post('/payments/settings/toggle', 'AdminPaymentSettingsController@toggle');
    $router->post('/payments/settings/mpesa', 'AdminPaymentSettingsController@saveMpesa');
    $router->post('/payments/settings/stripe', 'AdminPaymentSettingsController@saveStripe');
    $router->post('/payments/settings/intasend', 'AdminPaymentSettingsController@saveIntasend');
    $router->post('/payments/settings/pesapal', 'AdminPaymentSettingsController@savePesapal');
    $router->post('/payments/settings/paypal', 'AdminPaymentSettingsController@savePaypal');

    // Reports & Analytics
    $router->get('/reports', 'AdminReportController@reports');
    $router->get('/analytics', 'AdminReportController@analytics');

    // Blog
    $router->get('/blogs', 'AdminBlogController@index');
    $router->get('/blogs/create', 'AdminBlogController@create');
    $router->post('/blogs/store', 'AdminBlogController@store');
    $router->get('/blogs/{id}/edit', 'AdminBlogController@edit');
    $router->post('/blogs/{id}/update', 'AdminBlogController@update');
    $router->post('/blogs/{id}/delete', 'AdminBlogController@delete');

    // Settings
    $router->get('/settings', 'AdminSettingsController@index');
    $router->post('/settings/update', 'AdminSettingsController@update');
    $router->get('/settings/cities', 'AdminSettingsController@cities');
    $router->post('/settings/cities', 'AdminSettingsController@cities');
    $router->post('/settings/make-test', 'AdminSettingsController@makeTestWebhook');
    $router->get('/settings/make-logs', 'AdminSettingsController@makeWebhookLogs');
    $router->post('/settings/pesapal-test', 'AdminSettingsController@pesapalTestConnection');

    // SEO & Sitemap
    $router->get('/seo', 'AdminSeoController@index');
    $router->post('/seo/update', 'AdminSeoController@update');
    $router->get('/sitemap', 'AdminSeoController@sitemap');
    $router->post('/sitemap/save', 'AdminSeoController@saveSitemap');
    $router->get('/sitemap/generate', 'AdminSeoController@generateSitemapPreview');

    // API Integrations
    $router->get('/api-integrations', 'AdminApiIntegrationController@index');

    // Hero Slides
    $router->get('/hero-slides', 'AdminHeroSlideController@index');
    $router->post('/hero-slides', 'AdminHeroSlideController@store');
    $router->post('/hero-slides/edit/{id}', 'AdminHeroSlideController@edit');
    $router->post('/hero-slides/delete', 'AdminHeroSlideController@delete');

    // Promo Banners
    $router->get('/promo-banners', 'AdminPromoBannerController@index');
    $router->post('/promo-banners', 'AdminPromoBannerController@store');
    $router->post('/promo-banners/edit/{id}', 'AdminPromoBannerController@edit');
    $router->post('/promo-banners/store', 'AdminPromoBannerController@storeNew');
    $router->post('/promo-banners/{id}/update', 'AdminPromoBannerController@update');
    $router->post('/promo-banners/{id}/delete', 'AdminPromoBannerController@delete');
    $router->post('/promo-banners/delete', 'AdminPromoBannerController@deleteSelected');

    // Trust Badges
    $router->get('/trust-badges', 'AdminTrustBadgeController@index');
    $router->post('/trust-badges', 'AdminTrustBadgeController@store');
    $router->post('/trust-badges/edit/{id}', 'AdminTrustBadgeController@edit');
    $router->post('/trust-badges/delete', 'AdminTrustBadgeController@delete');

    // Testimonials
    $router->get('/testimonials', 'AdminTestimonialController@index');
    $router->post('/testimonials/store', 'AdminTestimonialController@store');
    $router->post('/testimonials/{id}/update', 'AdminTestimonialController@update');
    $router->post('/testimonials/{id}/delete', 'AdminTestimonialController@delete');

    // Appearance
    $router->get('/appearance', 'AdminAppearanceController@index');
    $router->post('/appearance/update', 'AdminAppearanceController@update');

    // Pages
    $router->get('/pages', 'AdminPageController@index');
    $router->get('/pages/create', 'AdminPageController@create');
    $router->post('/pages/store', 'AdminPageController@store');
    $router->get('/pages/{id}/edit', 'AdminPageController@edit');
    $router->post('/pages/{id}/update', 'AdminPageController@update');
    $router->post('/pages/{id}/delete', 'AdminPageController@delete');

    // Shipping
    $router->get('/shipping', 'AdminShippingController@index');
    $router->post('/shipping/store', 'AdminShippingController@store');
    $router->post('/shipping/{id}/update', 'AdminShippingController@update');
    $router->post('/shipping/{id}/delete', 'AdminShippingController@delete');

    // Shipping Cities
    $router->post('/shipping/cities/store', 'AdminShippingController@storeCity');
    $router->post('/shipping/cities/{id}/update', 'AdminShippingController@updateCity');
    $router->post('/shipping/cities/{id}/delete', 'AdminShippingController@deleteCity');

    // Social Media
    $router->get('/social-media', 'AdminSocialMediaController@index');
    $router->post('/social-media/update', 'AdminSocialMediaController@update');

    // Newsletter
    $router->get('/newsletter/subscribers', 'AdminNewsletterController@subscribers');
    $router->post('/newsletter/subscribers/toggle', 'AdminNewsletterController@toggleSubscriber');
    $router->post('/newsletter/subscribers/delete', 'AdminNewsletterController@deleteSubscriber');
    $router->post('/newsletter/subscribers/bulk-delete', 'AdminNewsletterController@bulkDeleteSubscribers');
    $router->post('/newsletter/export', 'AdminNewsletterController@exportSubscribers');
    $router->get('/newsletter/compose', 'AdminNewsletterController@compose');
    $router->post('/newsletter/send-test', 'AdminNewsletterController@sendTest');
    $router->post('/newsletter/send', 'AdminNewsletterController@send');
    $router->get('/newsletter/settings', 'AdminNewsletterController@settings');
    $router->post('/newsletter/settings', 'AdminNewsletterController@updateSettings');
    $router->post('/newsletter/test-connection', 'AdminNewsletterController@testConnection');

    // Coupons
    $router->get('/coupons', 'AdminCouponController@index');
    $router->post('/coupons/store', 'AdminCouponController@store');
    $router->post('/coupons/{id}/update', 'AdminCouponController@update');
    $router->post('/coupons/{id}/delete', 'AdminCouponController@delete');
    $router->post('/coupons/{id}/toggle', 'AdminCouponController@toggle');

    // Commissions
    $router->get('/commissions', 'AdminCommissionController@index');
    $router->post('/commissions/settings', 'AdminCommissionController@settings');
    $router->post('/commissions/{id}/pay', 'AdminCommissionController@pay');
    $router->post('/commissions/{id}/approve', 'AdminCommissionController@approve');
    $router->post('/commissions/bulk-pay', 'AdminCommissionController@bulkPay');

    // Referrals
    $router->get('/referrals', 'AdminReferralController@index');
    $router->post('/referrals/settings', 'AdminReferralController@settings');
    $router->post('/referrals/{id}/pay', 'AdminReferralController@pay');
    $router->get('/referrals/withdrawals', 'AdminReferralController@withdrawals');
    $router->post('/referrals/withdrawals/{id}/approve', 'AdminReferralController@approveWithdrawal');
    $router->post('/referrals/withdrawals/{id}/reject', 'AdminReferralController@rejectWithdrawal');
});

// Customer referral earnings & withdrawal
$router->get('/account/referral', 'AccountController@referralPage');
$router->post('/account/referral/withdraw', 'AccountController@requestWithdrawal');