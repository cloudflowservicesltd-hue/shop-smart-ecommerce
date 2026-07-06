<?php

// ============================================================
// POS Terminal (registered first for priority matching)
// ============================================================
$router->get('/pos', function() {
    if (!Auth::check() || !Auth::hasRole(['admin', 'super_admin', 'cashier'])) {
        Session::flash('error', 'Access denied.');
        Redirect::to('/');
    }
    Session::set('pos_cart', Session::get('pos_cart', []));
    include ROOT_PATH . '/resources/views/pos/terminal.php';
});

$router->get('/pos/holds', function() {
    if (!Auth::check() || !Auth::hasRole(['admin', 'super_admin', 'cashier'])) {
        Session::flash('error', 'Access denied.');
        Redirect::to('/');
    }
    include ROOT_PATH . '/resources/views/pos/holds.php';
});

$router->get('/cashier', function() {
    if (!Auth::check() || !Auth::hasRole(['admin', 'super_admin', 'cashier'])) {
        Session::flash('error', 'Access denied.');
        Redirect::to('/');
    }
    $pageTitle = 'Cashier Dashboard';
    ob_start();
    include ROOT_PATH . '/resources/views/cashier/dashboard.php';
    $content = ob_get_clean();
    include ROOT_PATH . '/resources/views/layouts/app.php';
});

// ============================================================
// POS API Routes (AJAX — no layout)
// ============================================================

// Self-heal: ensure required columns/tables exist
try {
    $db = Database::getConnection();
    // Add cashier_id to orders if missing
    try { $db->exec("ALTER TABLE `orders` ADD COLUMN `cashier_id` INT UNSIGNED DEFAULT NULL AFTER `is_pos`"); } catch (\Throwable $e) {}
    // Add referral_code to orders if missing
    try { $db->exec("ALTER TABLE `orders` ADD COLUMN `referral_code` VARCHAR(50) DEFAULT NULL AFTER `cashier_id`"); } catch (\Throwable $e) {}
    // Create pos_holds if missing
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
    // Create commissions if missing
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
    // Create custom_payment_methods if missing
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
    // Create page_views if missing (visit tracking)
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
    // Create blog_posts if missing
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
    // Create referrals table if missing
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
    // Self-heal: ensure all users have referral codes
    $usersWithoutRef = Database::select("SELECT u.id FROM users u LEFT JOIN referrals r ON r.referrer_id = u.id AND r.referred_id IS NULL WHERE r.id IS NULL");
    foreach ($usersWithoutRef as $u) {
        $code = 'REF' . strtoupper(substr(md5($u['id'] . time() . mt_rand()), 0, 8));
        try { Database::insert('referrals', ['referrer_id' => $u['id'], 'referral_code' => $code, 'created_at' => date('Y-m-d H:i:s')]); } catch(\Throwable $e) {}
    }
} catch (\Throwable $e) {
    @file_put_contents(ROOT_PATH . '/storage/logs/pos-api.log', date('Y-m-d H:i:s') . " | SELF-HEAL ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
}

// Track referral visits via ?ref=CODE
$refCode = Request::query('ref', '');
if ($refCode && !isset($_COOKIE['referral_code'])) {
    $ref = Database::selectOne("SELECT * FROM referrals WHERE referral_code = ? AND referred_id IS NULL", [$refCode]);
    if ($ref) {
        setcookie('referral_code', $refCode, time() + 86400 * 30, '/');
    }
}

// Helper: process referral commission when an order is paid
function processReferralCommission($orderId) {
    try {
        $order = Database::selectOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
        if (!$order || empty($order['referral_code']) || empty($order['customer_id'])) return;
        $referralCode = $order['referral_code'];
        $ref = Database::selectOne("SELECT * FROM referrals WHERE referral_code = ? AND referred_id IS NULL", [$referralCode]);
        if (!$ref || $ref['referrer_id'] == $order['customer_id']) return;

        // Check if referral already processed for this order
        $existing = Database::selectOne("SELECT id FROM referrals WHERE order_id = ? AND referred_id = ?", [$orderId, $order['customer_id']]);
        if ($existing) return;

        $referralEnabled = Database::selectOne("SELECT value FROM settings WHERE `key` = 'referral_enabled'")['value'] ?? '1';
        if ($referralEnabled !== '1') return;

        $commissionRate = (float)(Database::selectOne("SELECT value FROM settings WHERE `key` = 'referral_commission_rate'")['value'] ?? 5);
        $commission = (float)$order['total'] * ($commissionRate / 100);

        Database::update('referrals', [
            'referred_id' => $order['customer_id'],
            'status' => 'completed',
            'order_id' => $orderId,
            'order_total' => (float)$order['total'],
            'commission_amount' => $commission,
            'completed_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$ref['id']]);

        // Also add to commissions table if it exists
        try {
            Database::insert('commissions', [
                'user_id' => $ref['referrer_id'],
                'order_id' => $orderId,
                'order_number' => $order['order_number'] ?? '',
                'amount' => $commission,
                'percentage' => $commissionRate,
                'order_total' => (float)$order['total'],
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch(\Throwable $e) {}
    } catch(\Throwable $e) {
        @file_put_contents(ROOT_PATH . '/storage/logs/referral.log', date('Y-m-d H:i:s') . " | ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}



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
// Commission API Routes (Controller)
// ============================================================
$router->get('/api/commissions/balance', 'CommissionController@balance');
$router->get('/api/commissions/history', 'CommissionController@history');
$router->post('/api/commissions/pay/{id}', 'CommissionController@pay');

// ============================================================
// Checkout API Routes (Controller)
// ============================================================
$router->post('/api/checkout/create-order', 'CheckoutController@createOrder');
$router->post('/api/checkout/pay', 'CheckoutController@pay');
$router->post('/api/checkout/pesapal-callback', 'CheckoutController@pesapalCallback');
$router->get('/api/checkout/stripe-success', 'CheckoutController@stripeSuccess');

// ============================================================
// Payment API Routes (Controller)
// ============================================================
$router->post('/api/payments/initiate', 'PaymentController@initiate');
$router->post('/api/payments/callback/mpesa', 'PaymentController@mpesaCallback');
$router->post('/api/payments/callback/intasend', 'PaymentController@intasendCallback');
$router->post('/api/payments/callback/pesapal', 'PaymentController@pesapalCallback');

// ============================================================
// Payment Methods API Routes (Controller)
// ============================================================
$router->get('/api/payment-methods', 'PaymentMethodController@index');
$router->post('/api/payment-methods', 'PaymentMethodController@store');
$router->put('/api/payment-methods/{id}', 'PaymentMethodController@update');
$router->post('/api/payment-methods/{id}', 'PaymentMethodController@update');
$router->delete('/api/payment-methods/{id}', 'PaymentMethodController@delete');

// ============================================================
// M-Pesa Callback (Controller)
// ============================================================
$router->post('/api/mpesa/callback', 'MpesaCallbackController@handle');
$router->post('/api/mpesa/callback.php', 'MpesaCallbackController@handle');

// ============================================================
// Webhook API Routes (Controller)
// ============================================================
$router->post('/api/webhooks/mpesa', 'WebhookController@mpesa');
$router->post('/api/webhooks/stripe', 'WebhookController@stripe');
$router->post('/api/webhooks/intasend', 'WebhookController@intasend');


// ============================================================
// Public / Customer Routes
// ============================================================

// Home
$router->get('/', function() {
    $featuredProducts = Database::select("SELECT p.*, (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1) as image, c.name as category_name, b.name as brand_name, (SELECT AVG(rating) FROM reviews WHERE product_id = p.id) as avg_rating, (SELECT COUNT(*) FROM reviews WHERE product_id = p.id) as review_count FROM products p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN brands b ON p.brand_id = b.id WHERE p.is_featured = 1 AND p.is_active = 1 AND (p.product_status IS NULL OR p.product_status IN ('active','out_of_stock_returning')) ORDER BY p.created_at DESC LIMIT 8");
    $newProducts = Database::select("SELECT p.*, (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1) as image, b.name as brand_name FROM products p LEFT JOIN brands b ON p.brand_id = b.id WHERE p.is_active = 1 AND (p.product_status IS NULL OR p.product_status IN ('active','out_of_stock_returning')) ORDER BY p.created_at DESC LIMIT 12");
    $categories = Database::select("SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id AND is_active = 1 AND (product_status IS NULL OR product_status IN ('active','out_of_stock_returning'))) as product_count FROM categories c WHERE c.parent_id IS NULL AND c.is_active = 1 ORDER BY c.sort_order LIMIT 8");

    $pageTitle = 'ShopSmart - AI-Powered Ecommerce & POS';
    ob_start();
    include ROOT_PATH . '/resources/views/customer/home.php';
    $content = ob_get_clean();
    include ROOT_PATH . '/resources/views/layouts/app.php';
});

// Products listing
$router->get('/products', function() {
    $page = (int)Request::query('page', 1);
    $search = Request::query('q', '');
    $category = Request::query('category', '');
    $brand = Request::query('brand', '');
    $sort = Request::query('sort', 'newest');
    $minPrice = Request::query('min_price', '');
    $maxPrice = Request::query('max_price', '');

    $where = "p.is_active = 1 AND (p.product_status IS NULL OR p.product_status IN ('active','out_of_stock_returning'))";
    $params = [];
    if ($search) { $where .= " AND (p.name LIKE ? OR p.description LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
    if ($category) { $where .= " AND c.slug = ?"; $params[] = $category; }
    if ($brand) { $where .= " AND b.slug = ?"; $params[] = $brand; }
    if ($minPrice !== '') { $where .= " AND p.price >= ?"; $params[] = (float)$minPrice; }
    if ($maxPrice !== '') { $where .= " AND p.price <= ?"; $params[] = (float)$maxPrice; }

    $orderBy = match($sort) { 'price_asc' => 'p.price ASC', 'price_desc' => 'p.price DESC', 'popular' => 'p.created_at DESC', 'rating' => '(SELECT AVG(rating) FROM reviews WHERE product_id = p.id) DESC', default => 'p.created_at DESC' };

    $table = "products p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN brands b ON p.brand_id = b.id";
    $paginated = Database::paginate($table, $page, 12, $where, $params, $orderBy);

    // Re-fetch with explicit columns to avoid slug collision from SELECT *
    $productIds = array_column($paginated['data'], 'id');
    $products = [];
    if (!empty($productIds)) {
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $products = Database::select(
            "SELECT p.*, c.name as category_name, c.slug as category_slug, b.name as brand_name, b.slug as brand_slug,
                    (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1) as image,
                    (SELECT AVG(rating) FROM reviews WHERE product_id = p.id) as avg_rating,
                    (SELECT COUNT(*) FROM reviews WHERE product_id = p.id) as review_count
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN brands b ON p.brand_id = b.id
             WHERE p.id IN ({$placeholders})
             ORDER BY {$orderBy}",
            $productIds
        );
    }

    $pagination = $paginated;
    $totalProducts = $paginated['total'];

    $categories = Database::select("SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id AND is_active = 1 AND (product_status IS NULL OR product_status IN ('active','out_of_stock_returning'))) as product_count FROM categories c WHERE c.parent_id IS NULL AND c.is_active = 1 ORDER BY name");
    $brands = Database::select("SELECT b.*, (SELECT COUNT(*) FROM products WHERE brand_id = b.id AND is_active = 1 AND (product_status IS NULL OR product_status IN ('active','out_of_stock_returning'))) as product_count FROM brands b WHERE b.is_active = 1 ORDER BY name");

    // Set selected category for sidebar highlighting
    $selectedCategory = 0;
    $currentCategory = null;
    if ($category) {
        $currentCategory = Database::selectOne("SELECT * FROM categories WHERE slug = ?", [$category]);
        $selectedCategory = $currentCategory['id'] ?? 0;
    }

    // Build active filters
    $activeFilters = [];
    $removeFilterUrls = [];
    $baseUrl = '/products';
    if ($search) {
        $activeFilters['search'] = 'Search: ' . $search;
        $rm = $_GET; unset($rm['q']); $removeFilterUrls['search'] = $baseUrl . '?' . http_build_query($rm);
    }
    if ($brand) {
        $activeFilters['brand'] = 'Brand: ' . $brand;
        $rm = $_GET; unset($rm['brand']); $removeFilterUrls['brand'] = $baseUrl . '?' . http_build_query($rm);
    }
    if ($minPrice !== '' || $maxPrice !== '') {
        $activeFilters['price'] = 'KSh ' . number_format((float)$minPrice) . ' - KSh ' . number_format((float)$maxPrice);
        $rm = $_GET; unset($rm['min_price'], $rm['max_price']); $removeFilterUrls['price'] = $baseUrl . '?' . http_build_query($rm);
    }

    $pageTitle = 'Products - ShopSmart';
    ob_start();
    include ROOT_PATH . '/resources/views/customer/products.php';
    $content = ob_get_clean();
    include ROOT_PATH . '/resources/views/layouts/app.php';
});

// Product detail
$router->get('/product/{slug}', function($slug) {
    $product = Database::selectOne("SELECT p.*, c.name as category_name, c.slug as category_slug, b.name as brand_name FROM products p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN brands b ON p.brand_id = b.id WHERE p.slug = ? AND p.is_active = 1 AND (p.product_status IS NULL OR p.product_status IN ('active','out_of_stock_returning'))", [$slug]);
    if (!$product) { http_response_code(404); View::render('errors/404'); return; }

    $images = Database::select("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order", [$product['id']]);
    $reviews = Database::select("SELECT r.*, u.name as user_name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? AND r.is_approved = 1 ORDER BY r.created_at DESC", [$product['id']]);
    $related = Database::select("SELECT p.*, (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1) as image FROM products p WHERE p.category_id = ? AND p.id != ? AND p.is_active = 1 AND (p.product_status IS NULL OR p.product_status IN ('active','out_of_stock_returning')) ORDER BY RAND() LIMIT 4", [$product['category_id'], $product['id']]);

    $category = $product['category_id'] ? Database::selectOne("SELECT * FROM categories WHERE id = ?", [$product['category_id']]) : null;
    $brand = $product['brand_id'] ? Database::selectOne("SELECT * FROM brands WHERE id = ?", [$product['brand_id']]) : null;
    $ratingData = Database::selectOne("SELECT AVG(rating) as avg, COUNT(*) as cnt FROM reviews WHERE product_id = ? AND is_approved = 1", [$product['id']]);
    $avgRating = round($ratingData['avg'] ?? 0, 1);
    $reviewCount = (int)($ratingData['cnt'] ?? 0);
    $inWishlist = Auth::check() ? (bool)Database::selectOne("SELECT id FROM wishlists WHERE user_id = ? AND product_id = ?", [Auth::id(), $product['id']]) : false;

    $pageTitle = $product['name'] . ' - ShopSmart';
    ob_start();
    include ROOT_PATH . '/resources/views/customer/product-detail.php';
    $content = ob_get_clean();
    include ROOT_PATH . '/resources/views/layouts/app.php';
});

// Category page
$router->get('/category/{slug}', function($slug) {
    $currentCategory = Database::selectOne("SELECT * FROM categories WHERE slug = ? AND is_active = 1", [$slug]);
    if (!$currentCategory) { http_response_code(404); View::render('errors/404'); return; }

    $_GET['category'] = $slug;
    $page = (int)Request::query('page', 1);
    $brand = Request::query('brand', '');
    $minPrice = Request::query('min_price', '');
    $maxPrice = Request::query('max_price', '');

    $where = "p.is_active = 1 AND (p.product_status IS NULL OR p.product_status IN ('active','out_of_stock_returning')) AND c.slug = ?";
    $params = [$slug];
    if ($brand) { $where .= " AND b.slug = ?"; $params[] = $brand; }
    if ($minPrice !== '') { $where .= " AND p.price >= ?"; $params[] = (float)$minPrice; }
    if ($maxPrice !== '') { $where .= " AND p.price <= ?"; $params[] = (float)$maxPrice; }

    $table = "products p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN brands b ON p.brand_id = b.id";
    $paginated = Database::paginate($table, $page, 12, $where, $params, 'p.created_at DESC');

    // Fix: paginate uses SELECT * which causes slug collision with JOINs.
    // Re-fetch with explicit columns to avoid ambiguity.
    $productIds = array_column($paginated['data'], 'id');
    $products = [];
    if (!empty($productIds)) {
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $products = Database::select(
            "SELECT p.*, c.name as category_name, c.slug as category_slug, b.name as brand_name, b.slug as brand_slug,
                    (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1) as image,
                    (SELECT AVG(rating) FROM reviews WHERE product_id = p.id) as avg_rating,
                    (SELECT COUNT(*) FROM reviews WHERE product_id = p.id) as review_count
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN brands b ON p.brand_id = b.id
             WHERE p.id IN ({$placeholders})
             ORDER BY p.created_at DESC",
            $productIds
        );
    }

    $pagination = $paginated;
    $totalProducts = $paginated['total'];

    // Categories with product counts
    $categories = Database::select("SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id AND is_active = 1 AND (product_status IS NULL OR product_status IN ('active','out_of_stock_returning'))) as product_count FROM categories c WHERE c.parent_id IS NULL AND c.is_active = 1 ORDER BY name");
    // Brands with product counts
    $brands = Database::select("SELECT b.*, (SELECT COUNT(*) FROM products WHERE brand_id = b.id AND is_active = 1 AND (product_status IS NULL OR product_status IN ('active','out_of_stock_returning'))) as product_count FROM brands b WHERE b.is_active = 1 ORDER BY name");

    $selectedCategory = $currentCategory['id'];
    $activeFilters = [];
    $removeFilterUrls = [];
    if ($brand) {
        $activeFilters['brand'] = 'Brand: ' . $brand;
        $rm = $_GET; unset($rm['brand']); $removeFilterUrls['brand'] = '/category/' . $slug . '?' . http_build_query($rm);
    }
    if ($minPrice !== '' || $maxPrice !== '') {
        $label = 'Price: KSh ' . number_format((float)$minPrice) . ' - KSh ' . number_format((float)$maxPrice);
        $activeFilters['price'] = $label;
        $rm = $_GET; unset($rm['min_price'], $rm['max_price']); $removeFilterUrls['price'] = '/category/' . $slug . '?' . http_build_query($rm);
    }

    $pageTitle = $currentCategory['name'] . ' - ShopSmart';
    ob_start();
    include ROOT_PATH . '/resources/views/customer/products.php';
    $content = ob_get_clean();
    include ROOT_PATH . '/resources/views/layouts/app.php';
});

// All categories
$router->get('/categories', function() {
    $categories = Database::select("SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id AND is_active = 1 AND (product_status IS NULL OR product_status IN ('active','out_of_stock_returning'))) as product_count FROM categories c WHERE c.parent_id IS NULL AND c.is_active = 1 ORDER BY sort_order");
    $pageTitle = 'All Categories - ShopSmart';
    ob_start();
    include ROOT_PATH . '/resources/views/customer/categories.php';
    $content = ob_get_clean();
    include ROOT_PATH . '/resources/views/layouts/app.php';
});

// Search
$router->get('/search', function() {
    $_GET['q'] = Request::query('q', '');
    $page = (int)Request::query('page', 1);
    $q = Request::query('q', '');
    $paginated = Database::paginate("products", $page, 12, "is_active = 1 AND (product_status IS NULL OR product_status IN ('active','out_of_stock_returning')) AND name LIKE ?", ["%$q%"], 'created_at DESC');
    foreach ($paginated['data'] as &$p) {
        $img = Database::selectOne("SELECT image_path FROM product_images WHERE product_id = ? AND is_primary = 1", [$p['id']]);
        $p['image'] = $img['image_path'] ?? null;
    }
    $products = $paginated['data'];
    $pagination = $paginated;
    $totalProducts = $paginated['total'];
    $query = $q;
    $pageTitle = "Search: $q - ShopSmart";
    ob_start();
    include ROOT_PATH . '/resources/views/customer/search.php';
    $content = ob_get_clean();
    include ROOT_PATH . '/resources/views/layouts/app.php';
});

// Cart
$router->get('/cart', function() {
    $cartItems = [];
    $subtotal = 0;
    if (Auth::check()) {
        $cartItems = Database::select("SELECT c.*, p.name, p.price, p.discount_price, p.slug, (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1) as image, b.name as brand_name FROM cart c JOIN products p ON c.product_id = p.id LEFT JOIN brands b ON p.brand_id = b.id WHERE c.user_id = ?", [Auth::id()]);
    } else {
        $cartItems = Database::select("SELECT c.*, p.name, p.price, p.discount_price, p.slug, (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1) as image, b.name as brand_name FROM cart c JOIN products p ON c.product_id = p.id LEFT JOIN brands b ON p.brand_id = b.id WHERE c.session_id = ?", [session_id()]);
    }
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
    $freeShippingThreshold = 5000;
    $shippingCost = $subtotal >= $freeShippingThreshold ? 0 : 0; // Currently free shipping always

    // Coupon logic
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
});

// Add to cart
$router->post('/cart/add', function() {
    $productId = (int)Request::post('product_id', 0);
    $qty = (int)Request::post('quantity', 1);
    if ($productId <= 0) Redirect::back();

    // Verify product is available for purchase
    $product = Database::selectOne("SELECT * FROM products WHERE id = ? AND is_active = 1 AND (product_status IS NULL OR product_status IN ('active','out_of_stock_returning'))", [$productId]);
    if (!$product || ($product['stock_quantity'] ?? 0) <= 0 || ($product['product_status'] ?? 'active') !== 'active') { Session::flash('error', 'This product is not available for purchase.'); Redirect::back(); }

    $existing = Auth::check()
        ? Database::selectOne("SELECT * FROM cart WHERE user_id = ? AND product_id = ?", [Auth::id(), $productId])
        : Database::selectOne("SELECT * FROM cart WHERE session_id = ? AND product_id = ?", [session_id(), $productId]);

    if ($existing) {
        $newQty = $existing['quantity'] + $qty;
        Auth::check()
            ? Database::update('cart', ['quantity' => $newQty, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$existing['id']])
            : Database::update('cart', ['quantity' => $newQty, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$existing['id']]);
    } else {
        $data = ['product_id' => $productId, 'quantity' => $qty, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')];
        if (Auth::check()) $data['user_id'] = Auth::id();
        else $data['session_id'] = session_id();
        Database::insert('cart', $data);
    }
    Session::flash('success', 'Product added to cart!');
    Redirect::to('/cart');
});

// Update cart
$router->post('/cart/update', function() {
    $items = Request::post('qty', []);
    foreach ($items as $cartId => $qty) {
        $qty = (int)$qty;
        if ($qty <= 0) {
            Database::delete('cart', 'id = ?', [$cartId]);
        } else {
            Database::update('cart', ['quantity' => $qty, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$cartId]);
        }
    }
    Redirect::to('/cart');
});

// Remove from cart
$router->post('/cart/remove/{id}', function($id) {
    Database::delete('cart', 'id = ?', [$id]);
    Redirect::to('/cart');
});

// Apply coupon
$router->post('/cart/coupon', function() {
    $code = strtoupper(trim(Request::post('coupon', '')));
    $action = Request::post('coupon_action', 'apply');

    if ($action === 'remove') {
        Session::remove('applied_coupon');
        Session::flash('success', 'Coupon removed.');
        Redirect::to('/cart');
    }

    if (empty($code)) {
        Session::flash('coupon_error', 'Please enter a coupon code.');
        Redirect::to('/cart');
    }

    $coupon = Database::selectOne("SELECT * FROM coupons WHERE code = ? AND is_active = 1", [$code]);
    if (!$coupon) {
        Session::flash('coupon_error', 'Invalid coupon code.');
        Redirect::to('/cart');
    }

    $now = date('Y-m-d H:i:s');
    if (($coupon['valid_from'] && $now < $coupon['valid_from']) || ($coupon['valid_until'] && $now > $coupon['valid_until'])) {
        Session::flash('coupon_error', 'This coupon has expired or is not yet valid.');
        Redirect::to('/cart');
    }
    if ($coupon['usage_limit'] > 0 && $coupon['used_count'] >= $coupon['usage_limit']) {
        Session::flash('coupon_error', 'This coupon has reached its usage limit.');
        Redirect::to('/cart');
    }

    // Check min order amount
    $cartItems = [];
    $subtotal = 0;
    if (Auth::check()) {
        $cartItems = Database::select("SELECT c.*, p.price, p.discount_price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?", [Auth::id()]);
    } else {
        $cartItems = Database::select("SELECT c.*, p.price, p.discount_price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.session_id = ?", [session_id()]);
    }
    foreach ($cartItems as $item) {
        $p = !empty($item['discount_price']) && $item['discount_price'] < $item['price'] ? $item['discount_price'] : $item['price'];
        $subtotal += $p * $item['quantity'];
    }
    if ($subtotal < ($coupon['min_order_amount'] ?? 0)) {
        Session::flash('coupon_error', 'Minimum order amount is ' . formatMoney($coupon['min_order_amount']) . ' for this coupon.');
        Redirect::to('/cart');
    }

    Session::set('applied_coupon', $code);
    Session::flash('success', 'Coupon applied successfully!');
    Redirect::to('/cart');
});

// Checkout — shared helper to load cart + compute summary
$loadCheckoutData = function() {
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

    return [$cartItems, $subtotal, $totalItems, $tax, $total, $couponDiscount, $shippingCost];
};

// ============================================================
// Payment API Routes
// ============================================================

// Helper: Create order from cart (used by payment initiation)
$createOrderFromCart = function() {
    if (!Auth::check()) return ['success' => false, 'message' => 'Please login'];
    $shipping = Session::get('checkout_shipping', []);
    if (empty($shipping)) return ['success' => false, 'message' => 'Missing shipping info'];

    global $loadCheckoutData;
    [$cartItems, $subtotal, $totalItems, $taxRate, $computedTotal, $couponDiscount, $shippingCost] = $loadCheckoutData();

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
            $name = $customerName; $orderNumber = $orderNum; $total = $total; $currencySymbol = 'KSh';
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
};

// Helper: Shared curl call with shared-hosting-friendly settings (timeouts, no SSL verify)
$curlRequest = function($url, $options = [], $expectJson = true) {
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
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
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
};

// Helper: Fetch live KES → target currency exchange rate
// Returns multiplier (KES_amount * rate = target_amount) or false on failure
$fetchLiveRate = function($targetCurrency = 'USD') {
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
                    // file_get_contents doesn't easily expose HTTP code, check content
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
};

// Helper: Get PayPal exchange rate — tries live API first, falls back to saved setting
$getPayPalRate = function($targetCurrency = 'USD') use ($fetchLiveRate) {
    // Try live rate from currency API
    $liveRate = $fetchLiveRate($targetCurrency);
    if ($liveRate !== false && $liveRate > 0) {
        return ['rate' => $liveRate, 'source' => 'live'];
    }
    // Fallback to saved rate
    $saved = (float)(Database::selectOne("SELECT value FROM settings WHERE `key` = 'paypal_exchange_rate'")['value'] ?? '0.0077');
    // Default 0.0077 means "1 KES = 0.0077 USD" (i.e., ~130 KES per USD)
    return ['rate' => $saved > 0 ? $saved : 0.0077, 'source' => 'saved'];
};

// API: Fetch live exchange rate (for admin settings)
$router->get('/api/exchange-rate', function() use ($fetchLiveRate) {
    header('Content-Type: application/json');
    $currency = strtoupper(Request::query('currency', 'USD'));

    $rate = $fetchLiveRate($currency);
    if ($rate !== false && $rate > 0) {
        // Convert multiplier to human-readable "1 USD = X KES"
        $kesPerUnit = round(1 / $rate, 2);
        echo json_encode([
            'success' => true,
            'rate' => $rate,                    // multiplier: KES * rate = target
            'kes_per_unit' => $kesPerUnit,      // 1 USD = 130 KES
            'currency' => $currency,
            'formatted' => "1 {$currency} = {$kesPerUnit} KES",
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Could not fetch exchange rate. Using saved rate.']);
    }
});

// API: Initiate Payment
$router->post('/payment/initiate', function() use ($createOrderFromCart, $getPayPalRate) {
    header('Content-Type: application/json');
    // Helper: mark order as failed
    $markFailed = function($orderId, $reason = '') {
        Database::update('orders', [
            'status' => 'failed',
            'payment_status' => 'failed',
            'notes' => 'Payment failed: ' . $reason,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$orderId]);
    };

    try {
    if (!Auth::check()) { echo json_encode(['success' => false, 'message' => 'Please login']); return; }

    $paymentMethod = Request::post('payment_method', Session::get('checkout_shipping')['payment_method'] ?? 'mpesa');

    // Check if payment method is enabled
    $enabled = Database::selectOne("SELECT value FROM settings WHERE `key` = ?", [$paymentMethod . '_enabled']);
    if (!$enabled || $enabled['value'] !== '1') {
        echo json_encode(['success' => false, 'message' => ucfirst($paymentMethod) . ' is not available']);
        return;
    }

    // Create the order
    $result = $createOrderFromCart();
    if (!$result['success']) {
        echo json_encode($result);
        return;
    }

    $orderId = $result['order_id'];
    $orderTotal = $result['total'];
    $orderNum = $result['order_number'];

    // Initiate based on payment method
    switch ($paymentMethod) {
        case 'mpesa':
            $phone = Request::post('mpesa_phone', '');
            // Normalize phone number
            $phone = preg_replace('/\s+/', '', $phone);
            if (strpos($phone, '0') === 0) $phone = '+254' . substr($phone, 1);
            if (strpos($phone, '+') !== 0) $phone = '+' . $phone;

            $mpesaPasskey = Database::selectOne("SELECT value FROM settings WHERE `key` = 'mpesa_passkey'")['value'] ?? '';
            $mpesaEnv = Database::selectOne("SELECT value FROM settings WHERE `key` = 'mpesa_env'")['value'] ?? 'sandbox';
            $shortCode = Database::selectOne("SELECT value FROM settings WHERE `key` = 'mpesa_shortcode'")['value'] ?? '174379';

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
                $consumerKey = Database::selectOne("SELECT value FROM settings WHERE `key` = 'mpesa_consumer_key'")['value'] ?? '';
                $consumerSecret = Database::selectOne("SELECT value FROM settings WHERE `key` = 'mpesa_consumer_secret'")['value'] ?? '';

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

                    $savedCallback = Database::selectOne("SELECT value FROM settings WHERE `key` = 'mpesa_callback'")['value'] ?? '';
                    $callbackUrl = !empty($savedCallback) ? $savedCallback : (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/payment/mpesa/callback';

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
                        echo json_encode(['success' => true, 'order_id' => $orderId, 'message' => 'STK Push initiated']);
                        return;
                    }
                    // STK Push returned error
                    $markFailed($orderId, 'STK Push failed: ' . ($stkData['errorMessage'] ?? $stkData['ResponseDescription'] ?? json_encode($stkData)));
                    echo json_encode(['success' => false, 'message' => 'STK Push failed: ' . ($stkData['errorMessage'] ?? $stkData['ResponseDescription'] ?? 'Unknown error')]);
                    return;
                }
                // OAuth failed
                $markFailed($orderId, 'M-Pesa authentication failed');
                echo json_encode(['success' => false, 'message' => 'M-Pesa authentication failed. Check consumer key and secret.']);
                return;
            }

            // No credentials — keep as pending (user can pay later)
            Database::update('orders', ['notes' => 'Payment pending: M-Pesa not configured', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$orderId]);
            Session::remove('checkout_shipping');
            echo json_encode(['success' => true, 'order_id' => $orderId, 'pending' => true, 'message' => 'Order placed. Payment is pending — M-Pesa is not configured.']);
            return;

        case 'intasend':
            $intasendSecret = Database::selectOne("SELECT value FROM settings WHERE `key` = 'intasend_secret'")['value'] ?? '';
            $intasendPubKey = Database::selectOne("SELECT value FROM settings WHERE `key` = 'intasend_publishable'")['value'] ?? '';
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
                    'amount' => round($orderTotal),
                    'currency' => 'KES',
                    'api_ref' => $orderNum,
                    'redirect_url' => (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/payment/intasend/callback?order_id=' . $orderId,
                ]));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $resp = curl_exec($ch);
                curl_close($ch);
                $data = json_decode($resp, true);
                if (isset($data['url'])) {
                    Database::update('orders', ['payment_reference' => $data['invoice_id'] ?? '', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$orderId]);
                    Session::remove('checkout_shipping');
                    echo json_encode(['success' => true, 'order_id' => $orderId, 'redirect_url' => $data['url']]);
                    return;
                }
                $markFailed($orderId, 'IntaSend: ' . ($data['message'] ?? json_encode($data)));
                echo json_encode(['success' => false, 'message' => 'IntaSend error: ' . ($data['message'] ?? json_encode($data))]);
                return;
            }
            // No credentials — keep as pending (user can pay later)
            Database::update('orders', ['notes' => 'Payment pending: IntaSend not configured', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$orderId]);
            Session::remove('checkout_shipping');
            echo json_encode(['success' => true, 'order_id' => $orderId, 'pending' => true, 'message' => 'Order placed. Payment is pending — IntaSend is not configured.']);
            return;

        case 'paypal':
            $ppClientId = Database::selectOne("SELECT value FROM settings WHERE `key` = 'paypal_client_id'")['value'] ?? '';
            $ppSecret = Database::selectOne("SELECT value FROM settings WHERE `key` = 'paypal_secret'")['value'] ?? '';
            $ppEnv = Database::selectOne("SELECT value FROM settings WHERE `key` = 'paypal_env'")['value'] ?? 'sandbox';
            $ppCurrency = Database::selectOne("SELECT value FROM settings WHERE `key` = 'paypal_currency'")['value'] ?? 'USD';
            $rateInfo = $getPayPalRate($ppCurrency);
            $ppRate = $rateInfo['rate'];
            $rateSource = $rateInfo['source'];

            if (!empty($ppClientId) && !empty($ppSecret)) {
                $ppBase = $ppEnv === 'production'
                    ? 'https://api-m.paypal.com'
                    : 'https://api-m.sandbox.paypal.com';

                // Step 1: Get access token
                $tokenResult = $curlRequest($ppBase . '/v1/oauth2/token', [
                    CURLOPT_USERPWD    => $ppClientId . ':' . $ppSecret,
                    CURLOPT_POST       => true,
                    CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
                ]);

                if (!$tokenResult['success']) {
                    $markFailed($orderId, 'PayPal network error: ' . $tokenResult['error']);
                    echo json_encode(['success' => false, 'message' => 'Cannot reach PayPal servers. Your hosting may block outgoing connections. Error: ' . $tokenResult['error']]);
                    return;
                }

                $tokenData = json_decode($tokenResult['response'], true);
                $accessToken = $tokenData['access_token'] ?? '';

                if (empty($accessToken)) {
                    $authErr = $tokenData['error_description'] ?? ($tokenData['error'] ?? 'Invalid credentials');
                    $markFailed($orderId, 'PayPal auth failed: ' . $authErr);
                    echo json_encode(['success' => false, 'message' => 'PayPal authentication failed: ' . $authErr . '. Check client ID and secret.']);
                    return;
                }

                // Step 2: Create PayPal order with converted currency
                $convertedAmount = round($orderTotal * $ppRate, 2);
                if ($convertedAmount < 1) $convertedAmount = 1.00; // PayPal minimum

                $successUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/payment/paypal/callback?order_id=' . $orderId;
                $cancelUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/checkout/payment';

                $orderResult = $curlRequest($ppBase . '/v2/checkout/orders', [
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
                            'brand_name' => Database::selectOne("SELECT value FROM settings WHERE `key` = 'store_name'")['value'] ?? 'ShopSmart',
                            'return_url' => $successUrl,
                            'cancel_url' => $cancelUrl,
                        ],
                    ]),
                ]);

                if (!$orderResult['success']) {
                    $markFailed($orderId, 'PayPal network error: ' . $orderResult['error']);
                    echo json_encode(['success' => false, 'message' => 'Cannot reach PayPal to create order. Error: ' . $orderResult['error']]);
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
                            echo json_encode(['success' => true, 'order_id' => $orderId, 'redirect_url' => $link['href']]);
                            return;
                        }
                    }
                }
                $errMsg = $ppOrder['message'] ?? ($ppOrder['details'][0]['description'] ?? 'Could not create order');
                $markFailed($orderId, 'PayPal: ' . $errMsg);
                echo json_encode(['success' => false, 'message' => 'PayPal error: ' . $errMsg]);
                return;
            }
            // No credentials — keep as pending (user can pay later)
            Database::update('orders', ['notes' => 'Payment pending: PayPal not configured', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$orderId]);
            Session::remove('checkout_shipping');
            echo json_encode(['success' => true, 'order_id' => $orderId, 'pending' => true, 'message' => 'Order placed. Payment is pending — PayPal is not configured.']);
            return;

        case 'pesapal':
            $ppalConsumerKey = Database::selectOne("SELECT value FROM settings WHERE `key` = 'pesapal_key'")['value'] ?? '';
            $ppalConsumerSecret = Database::selectOne("SELECT value FROM settings WHERE `key` = 'pesapal_secret'")['value'] ?? '';
            $ppalEnv = Database::selectOne("SELECT value FROM settings WHERE `key` = 'pesapal_env'")['value'] ?? 'sandbox';
            $ppalIpnId = Database::selectOne("SELECT value FROM settings WHERE `key` = 'pesapal_ipn_id'")['value'] ?? '';
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
                    $callbackUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/payment/pesapal/callback?order_id=' . $orderId;
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
                        echo json_encode(['success' => true, 'order_id' => $orderId, 'redirect_url' => $ppalOrderData['redirect_url']]);
                        return;
                    }
                    $markFailed($orderId, 'PesaPal: ' . ($ppalOrderData['error']['message'] ?? json_encode($ppalOrderData)));
                    echo json_encode(['success' => false, 'message' => 'PesaPal error: ' . ($ppalOrderData['error']['message'] ?? json_encode($ppalOrderData))]);
                    return;
                }
                $markFailed($orderId, 'PesaPal authentication failed');
                echo json_encode(['success' => false, 'message' => 'PesaPal authentication failed. Check consumer key and secret.']);
                return;
            }
            // No credentials — keep as pending (user can pay later)
            Database::update('orders', ['notes' => 'Payment pending: PesaPal not configured', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$orderId]);
            Session::remove('checkout_shipping');
            echo json_encode(['success' => true, 'order_id' => $orderId, 'pending' => true, 'message' => 'Order placed. Payment is pending — PesaPal is not configured.']);
            return;

        case 'stripe':
            $stripeSecret = Database::selectOne("SELECT value FROM settings WHERE `key` = 'stripe_secret'")['value'] ?? '';
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
                    'success_url' => (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/payment/stripe/success?order_id=' . $orderId,
                    'cancel_url' => (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/checkout/payment',
                ]));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $resp = curl_exec($ch);
                curl_close($ch);
                $session = json_decode($resp, true);
                if (isset($session['url'])) {
                    Database::update('orders', ['payment_reference' => $session['id'] ?? '', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$orderId]);
                    Session::remove('checkout_shipping');
                    echo json_encode(['success' => true, 'order_id' => $orderId, 'redirect_url' => $session['url']]);
                    return;
                }
                $markFailed($orderId, 'Stripe: ' . ($session['error']['message'] ?? json_encode($session)));
                echo json_encode(['success' => false, 'message' => 'Stripe error: ' . ($session['error']['message'] ?? json_encode($session))]);
                return;
            }
            // No credentials — keep as pending (user can pay later)
            Database::update('orders', ['notes' => 'Payment pending: Stripe not configured', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$orderId]);
            Session::remove('checkout_shipping');
            echo json_encode(['success' => true, 'order_id' => $orderId, 'pending' => true, 'message' => 'Order placed. Payment is pending — Stripe is not configured.']);
            return;

        default:
            echo json_encode(['success' => false, 'message' => 'Unsupported payment method']);
            return;
    }
    } catch (\Throwable $e) {
        if (isset($orderId)) $markFailed($orderId, 'Exception: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Payment error: ' . $e->getMessage()]);
    }
});

// API: Re-initiate payment for an existing pending order
$router->post('/order/pay/{id}', function($id) use ($getPayPalRate) {
    header('Content-Type: application/json');
    if (!Auth::check()) { echo json_encode(['success' => false, 'message' => 'Please login']); return; }

    $order = Database::selectOne("SELECT * FROM orders WHERE id = ? AND customer_id = ?", [$id, Auth::id()]);
    if (!$order) { echo json_encode(['success' => false, 'message' => 'Order not found']); return; }
    if ($order['payment_status'] !== 'pending') { echo json_encode(['success' => false, 'message' => 'This order is not pending payment']); return; }

    $orderId = $order['id'];
    $orderTotal = $order['total'];
    $orderNum = $order['order_number'];
    $paymentMethod = Request::post('payment_method', $order['payment_method'] ?? 'mpesa');

    // Helper: mark order as failed
    $markFailed = function($orderId, $reason = '') {
        Database::update('orders', [
            'status' => 'failed',
            'payment_status' => 'failed',
            'notes' => 'Payment failed: ' . $reason,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$orderId]);
    };

    try {
    // Update the payment method on the order in case user switches
    Database::update('orders', ['payment_method' => $paymentMethod, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$orderId]);

    switch ($paymentMethod) {
        case 'mpesa':
            $phone = Request::post('mpesa_phone', $order['customer_phone'] ?? '');
            $phone = preg_replace('/\s+/', '', $phone);
            if (strpos($phone, '0') === 0) $phone = '+254' . substr($phone, 1);
            if (strpos($phone, '+') !== 0) $phone = '+' . $phone;

            $mpesaPasskey = Database::selectOne("SELECT value FROM settings WHERE `key` = 'mpesa_passkey'")['value'] ?? '';
            $mpesaEnv = Database::selectOne("SELECT value FROM settings WHERE `key` = 'mpesa_env'")['value'] ?? 'sandbox';
            $shortCode = Database::selectOne("SELECT value FROM settings WHERE `key` = 'mpesa_shortcode'")['value'] ?? '174379';

            Database::update('orders', ['customer_phone' => $phone], 'id = ?', [$orderId]);

            if (!empty($mpesaPasskey) && $mpesaPasskey !== '') {
                $timestamp = date('YmdHis');
                $password = base64_encode($shortCode . $mpesaPasskey . $timestamp);
                $endpoint = $mpesaEnv === 'production'
                    ? 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
                    : 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

                $consumerKey = Database::selectOne("SELECT value FROM settings WHERE `key` = 'mpesa_consumer_key'")['value'] ?? '';
                $consumerSecret = Database::selectOne("SELECT value FROM settings WHERE `key` = 'mpesa_consumer_secret'")['value'] ?? '';

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

                    $savedCallback = Database::selectOne("SELECT value FROM settings WHERE `key` = 'mpesa_callback'")['value'] ?? '';
                    $callbackUrl = !empty($savedCallback) ? $savedCallback : (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/payment/mpesa/callback';

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
                        echo json_encode(['success' => true, 'order_id' => $orderId, 'message' => 'STK Push initiated']);
                        return;
                    }
                    $markFailed($orderId, 'STK Push failed: ' . ($stkData['errorMessage'] ?? $stkData['ResponseDescription'] ?? json_encode($stkData)));
                    echo json_encode(['success' => false, 'message' => 'STK Push failed: ' . ($stkData['errorMessage'] ?? $stkData['ResponseDescription'] ?? 'Unknown error')]);
                    return;
                }
                $markFailed($orderId, 'M-Pesa authentication failed');
                echo json_encode(['success' => false, 'message' => 'M-Pesa authentication failed. Check consumer key and secret.']);
                return;
            }
            // No credentials
            echo json_encode(['success' => false, 'message' => 'M-Pesa is not configured. Please contact admin.']);
            return;

        case 'intasend':
            $intasendSecret = Database::selectOne("SELECT value FROM settings WHERE `key` = 'intasend_secret'")['value'] ?? '';
            $intasendPubKey = Database::selectOne("SELECT value FROM settings WHERE `key` = 'intasend_publishable'")['value'] ?? '';
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
                    'redirect_url' => (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/payment/intasend/callback?order_id=' . $orderId,
                ]));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $resp = curl_exec($ch);
                curl_close($ch);
                $data = json_decode($resp, true);
                if (isset($data['url'])) {
                    Database::update('orders', ['payment_reference' => $data['invoice_id'] ?? '', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$orderId]);
                    echo json_encode(['success' => true, 'order_id' => $orderId, 'redirect_url' => $data['url']]);
                    return;
                }
                $markFailed($orderId, 'IntaSend: ' . ($data['message'] ?? json_encode($data)));
                echo json_encode(['success' => false, 'message' => 'IntaSend error: ' . ($data['message'] ?? json_encode($data))]);
                return;
            }
            echo json_encode(['success' => false, 'message' => 'IntaSend is not configured. Please contact admin.']);
            return;

        case 'paypal':
            $ppClientId = Database::selectOne("SELECT value FROM settings WHERE `key` = 'paypal_client_id'")['value'] ?? '';
            $ppSecret = Database::selectOne("SELECT value FROM settings WHERE `key` = 'paypal_secret'")['value'] ?? '';
            $ppEnv = Database::selectOne("SELECT value FROM settings WHERE `key` = 'paypal_env'")['value'] ?? 'sandbox';
            $ppCurrency = Database::selectOne("SELECT value FROM settings WHERE `key` = 'paypal_currency'")['value'] ?? 'USD';
            $rateInfo = $getPayPalRate($ppCurrency);
            $ppRate = $rateInfo['rate'];
            $rateSource = $rateInfo['source'];

            if (!empty($ppClientId) && !empty($ppSecret)) {
                $ppBase = $ppEnv === 'production' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

                // Step 1: Get access token
                $tokenResult = $curlRequest($ppBase . '/v1/oauth2/token', [
                    CURLOPT_USERPWD    => $ppClientId . ':' . $ppSecret,
                    CURLOPT_POST       => true,
                    CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
                ]);

                if (!$tokenResult['success']) {
                    $markFailed($orderId, 'PayPal network error: ' . $tokenResult['error']);
                    echo json_encode(['success' => false, 'message' => 'Cannot reach PayPal servers. Your hosting may block outgoing connections. Error: ' . $tokenResult['error']]);
                    return;
                }

                $tokenData = json_decode($tokenResult['response'], true);
                $accessToken = $tokenData['access_token'] ?? '';

                if (empty($accessToken)) {
                    $authErr = $tokenData['error_description'] ?? ($tokenData['error'] ?? 'Invalid credentials');
                    $markFailed($orderId, 'PayPal auth failed: ' . $authErr);
                    echo json_encode(['success' => false, 'message' => 'PayPal authentication failed: ' . $authErr]);
                    return;
                }

                // Step 2: Create PayPal order
                $convertedAmount = round($orderTotal * $ppRate, 2);
                if ($convertedAmount < 1) $convertedAmount = 1.00;
                $successUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/payment/paypal/callback?order_id=' . $orderId;
                $cancelUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/account/orders';

                $orderResult = $curlRequest($ppBase . '/v2/checkout/orders', [
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
                            'brand_name' => Database::selectOne("SELECT value FROM settings WHERE `key` = 'store_name'")['value'] ?? 'ShopSmart',
                            'return_url' => $successUrl,
                            'cancel_url' => $cancelUrl,
                        ],
                    ]),
                ]);

                if (!$orderResult['success']) {
                    $markFailed($orderId, 'PayPal network error: ' . $orderResult['error']);
                    echo json_encode(['success' => false, 'message' => 'Cannot reach PayPal to create order. Error: ' . $orderResult['error']]);
                    return;
                }

                $ppOrder = json_decode($orderResult['response'], true);

                if (isset($ppOrder['links'])) {
                    foreach ($ppOrder['links'] as $link) {
                        if (($link['rel'] ?? '') === 'approve') {
                            Database::update('orders', ['payment_reference' => $ppOrder['id'] ?? '', 'notes' => 'PayPal order: KSh ' . number_format($orderTotal, 2) . ' → ' . $ppCurrency . ' ' . $convertedAmount, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$orderId]);
                            echo json_encode(['success' => true, 'order_id' => $orderId, 'redirect_url' => $link['href']]);
                            return;
                        }
                    }
                }
                $errMsg = $ppOrder['message'] ?? ($ppOrder['details'][0]['description'] ?? 'Could not create order');
                $markFailed($orderId, 'PayPal: ' . $errMsg);
                echo json_encode(['success' => false, 'message' => 'PayPal error: ' . $errMsg]);
                return;
            }
            echo json_encode(['success' => false, 'message' => 'PayPal is not configured. Please contact admin.']);
            return;

        case 'pesapal':
            $ppalConsumerKey = Database::selectOne("SELECT value FROM settings WHERE `key` = 'pesapal_key'")['value'] ?? '';
            $ppalConsumerSecret = Database::selectOne("SELECT value FROM settings WHERE `key` = 'pesapal_secret'")['value'] ?? '';
            $ppalEnv = Database::selectOne("SELECT value FROM settings WHERE `key` = 'pesapal_env'")['value'] ?? 'sandbox';
            $ppalIpnId = Database::selectOne("SELECT value FROM settings WHERE `key` = 'pesapal_ipn_id'")['value'] ?? '';
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
                    $callbackUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/payment/pesapal/callback?order_id=' . $orderId;
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
                        echo json_encode(['success' => true, 'order_id' => $orderId, 'redirect_url' => $ppalOrderData['redirect_url']]);
                        return;
                    }
                    $markFailed($orderId, 'PesaPal: ' . ($ppalOrderData['error']['message'] ?? json_encode($ppalOrderData)));
                    echo json_encode(['success' => false, 'message' => 'PesaPal error: ' . ($ppalOrderData['error']['message'] ?? json_encode($ppalOrderData))]);
                    return;
                }
                $markFailed($orderId, 'PesaPal authentication failed');
                echo json_encode(['success' => false, 'message' => 'PesaPal authentication failed']);
                return;
            }
            echo json_encode(['success' => false, 'message' => 'PesaPal is not configured. Please contact admin.']);
            return;

        case 'stripe':
            $stripeSecret = Database::selectOne("SELECT value FROM settings WHERE `key` = 'stripe_secret'")['value'] ?? '';
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
                    'success_url' => (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/payment/stripe/success?order_id=' . $orderId,
                    'cancel_url' => (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/account/orders',
                ]));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $resp = curl_exec($ch);
                curl_close($ch);
                $session = json_decode($resp, true);
                if (isset($session['url'])) {
                    Database::update('orders', ['payment_reference' => $session['id'] ?? '', 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$orderId]);
                    echo json_encode(['success' => true, 'order_id' => $orderId, 'redirect_url' => $session['url']]);
                    return;
                }
                $markFailed($orderId, 'Stripe: ' . ($session['error']['message'] ?? json_encode($session)));
                echo json_encode(['success' => false, 'message' => 'Stripe error: ' . ($session['error']['message'] ?? json_encode($session))]);
                return;
            }
            echo json_encode(['success' => false, 'message' => 'Stripe is not configured. Please contact admin.']);
            return;

        default:
            echo json_encode(['success' => false, 'message' => 'Unsupported payment method']);
            return;
    }
    } catch (\Throwable $e) {
        if (isset($orderId)) $markFailed($orderId, 'Exception: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Payment error: ' . $e->getMessage()]);
    }
});

// GET: Order pay page (shows payment form for a pending order)
$router->get('/order/pay/{id}', function($id) {
    if (!Auth::check()) Redirect::to('/login');
    $order = Database::selectOne("SELECT * FROM orders WHERE id = ? AND customer_id = ?", [$id, Auth::id()]);
    if (!$order) { http_response_code(404); View::render('errors/404'); return; }
    if ($order['payment_status'] !== 'pending') { Session::flash('error', 'This order is not pending payment'); Redirect::to('/account/orders'); }
    $orderItems = Database::select("SELECT * FROM order_items WHERE order_id = ?", [$id]);
    ob_start();
    include ROOT_PATH . '/resources/views/customer/order-pay.php';
    $content = ob_get_clean();
    include ROOT_PATH . '/resources/views/layouts/app.php';
});

// API: Check payment status (for polling)
$router->get('/payment/status', function() {
    header('Content-Type: application/json');
    $orderId = (int)Request::query('order_id', 0);
    if (!$orderId) { echo json_encode(['paid' => false]); return; }
    $order = Database::selectOne("SELECT payment_status FROM orders WHERE id = ?", [$orderId]);
    echo json_encode(['paid' => ($order['payment_status'] ?? 'pending') === 'paid']);
});

// API: M-Pesa Callback (webhook from Safaricom)
$router->post('/payment/mpesa/callback', function() {
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
            processReferralCommission($order['id']);
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
});

// API: IntaSend Callback
$router->get('/payment/intasend/callback', function() {
    $orderId = (int)Request::query('order_id', 0);
    $invoiceId = Request::query('invoice_id', '');
    if ($orderId) {
        Database::update('orders', [
            'payment_status' => 'paid',
            'status' => 'processing',
            'payment_reference' => $invoiceId,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$orderId]);
        processReferralCommission($orderId);
    }
    Session::set('last_order_id', $orderId);
    Redirect::to('/order-success');
});

// API: PayPal Callback
$router->get('/payment/paypal/callback', function() {
    $orderId = (int)Request::query('order_id', 0);
    if ($orderId) {
        Database::update('orders', [
            'payment_status' => 'paid',
            'status' => 'processing',
            'payment_reference' => Request::query('token', 'paypal'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$orderId]);
        processReferralCommission($orderId);
    }
    Session::set('last_order_id', $orderId);
    Redirect::to('/order-success');
});

// API: Stripe Success Callback
$router->get('/payment/stripe/success', function() {
    $orderId = (int)Request::query('order_id', 0);
    if ($orderId) {
        Database::update('orders', [
            'payment_status' => 'paid',
            'status' => 'processing',
            'payment_reference' => Request::query('session_id', 'stripe'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$orderId]);
        processReferralCommission($orderId);
    }
    Session::set('last_order_id', $orderId);
    Redirect::to('/order-success');
});

// API: PesaPal Redirect
$router->get('/payment/pesapal/redirect', function() {
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
        processReferralCommission($orderId);
    }
    Session::set('last_order_id', $orderId);
    Redirect::to('/order-success');
});

// Render checkout view helper
$renderCheckout = function($step) use ($loadCheckoutData) {
    if (!Auth::check()) { Session::flash('error', 'Please login to checkout'); Redirect::to('/login'); }
    [$cartItems, $subtotal, $totalItems, $tax, $total, $couponDiscount, $shippingCost] = $loadCheckoutData();
    $shipping = Session::get('checkout_shipping', []);
    $currentStep = $step;
    ob_start();
    include ROOT_PATH . '/resources/views/customer/checkout.php';
    $content = ob_get_clean();
    include ROOT_PATH . '/resources/views/layouts/app.php';
};

// Checkout - Step 1: Shipping (GET and fallback from /checkout)
$router->get('/checkout', function() use ($renderCheckout) { $renderCheckout('shipping'); });
$router->get('/checkout/shipping', function() use ($renderCheckout) { $renderCheckout('shipping'); });
$router->post('/checkout/shipping', function() {
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
});

// Checkout - Step 2: Payment
$router->get('/checkout/payment', function() use ($renderCheckout) {
    // Must have shipping data
    $shipping = Session::get('checkout_shipping', []);
    if (empty($shipping['name']) || empty($shipping['address'])) {
        Redirect::to('/checkout/shipping');
    }
    $renderCheckout('payment');
});
$router->post('/checkout/payment', function() {
    if (!Auth::check()) Redirect::to('/login');
    $shipping = Session::get('checkout_shipping', []);
    $shipping['payment_method'] = Request::post('payment_method', 'mpesa');
    Session::set('checkout_shipping', $shipping);
    Redirect::to('/checkout/review');
});

// Checkout - Step 3: Review & Place Order
$router->get('/checkout/review', function() use ($renderCheckout) {
    $shipping = Session::get('checkout_shipping', []);
    if (empty($shipping['name']) || empty($shipping['address'])) {
        Redirect::to('/checkout/shipping');
    }
    if (empty($shipping['payment_method'])) {
        Redirect::to('/checkout/payment');
    }
    $renderCheckout('review');
});
$router->post('/checkout/review', function() use ($loadCheckoutData) {
    if (!Auth::check()) Redirect::to('/login');
    $shipping = Session::get('checkout_shipping', []);
    if (empty($shipping)) Redirect::to('/checkout/shipping');

    [$cartItems, $subtotal, $totalItems, $taxRate, $computedTotal, $couponDiscount, $shippingCost] = $loadCheckoutData();

    // Recompute with discount for order
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
        'referral_code' => $_COOKIE['referral_code'] ?? null,
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

    // Send order confirmation email (non-blocking)
    try {
        $custEmail = $shipping['email'] ?? Auth::user()['email'] ?? '';
        if (class_exists('Mailer') && Mailer::isConfigured() && !empty($custEmail)) {
            $storeName = Database::selectOne("SELECT value FROM settings WHERE `key` = 'store_name'")['value'] ?? 'ShopSmart';
            $name = $shipping['name'] ?? Auth::user()['name'] ?? 'Customer'; $orderNumber = $orderNum; $total = $total; $currencySymbol = 'KSh';
            $orderUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/account/orders";
            $items = Database::select("SELECT product_name, quantity, price, total FROM order_items WHERE order_id = ?", [$orderId]);
            ob_start();
            include ROOT_PATH . '/resources/views/emails/order-confirmation.php';
            $emailBody = ob_get_clean();
            Mailer::send($custEmail, "Order Confirmation - {$orderNum}", $emailBody);
        }
    } catch (\Throwable $e) {
        error_log('[EMAIL] Order confirmation failed: ' . $e->getMessage());
    }

    Redirect::to('/order-success');
});

// Order success
$router->get('/order-success', function() {
    $orderId = Session::get('last_order_id');
    $orderNum = Session::get('last_order_num');
    if (!$orderId) Redirect::to('/');
    $order = Database::selectOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
    $items = Database::select("SELECT * FROM order_items WHERE order_id = ?", [$orderId]);
    ob_start();
    include ROOT_PATH . '/resources/views/customer/order-success.php';
    $content = ob_get_clean();
    include ROOT_PATH . '/resources/views/layouts/app.php';
});

// Blog Routes
$router->get('/blog', function() {
    $page = (int)Request::query('page', 1);
    $posts = Database::paginate('blog_posts', $page, 9, 'is_published = 1', [], 'created_at DESC');
    $pageTitle = 'Blog';
    ob_start();
    include ROOT_PATH . '/resources/views/customer/blog.php';
    $content = ob_get_clean();
    include ROOT_PATH . '/resources/views/layouts/app.php';
});

$router->get('/blog/{slug}', function($slug) {
    $post = Database::selectOne("SELECT bp.*, u.name as author_name FROM blog_posts bp LEFT JOIN users u ON bp.author_id = u.id WHERE bp.slug = ? AND bp.is_published = 1", [$slug]);
    if (!$post) Redirect::to('/blog');

    // Increment views
    Database::query("UPDATE blog_posts SET views = views + 1 WHERE id = ?", [$post['id']]);

    $pageTitle = $post['meta_title'] ?: $post['title'];
    $metaDescription = $post['meta_description'] ?? '';
    ob_start();
    include ROOT_PATH . '/resources/views/customer/blog-detail.php';
    $content = ob_get_clean();
    include ROOT_PATH . '/resources/views/layouts/app.php';
});

// Auth routes
$router->get('/login', function() {
    if (Auth::check()) Redirect::to('/');
    include ROOT_PATH . '/resources/views/auth/login.php';
});

$router->post('/login', function() {
    $email = Request::post('email', '');
    $password = Request::post('password', '');

    // Remember session_id before login (for cart merge)
    $sessionId = session_id();

    if (Auth::attempt($email, $password)) {
        // Merge guest cart items into user cart
        if (Auth::check()) {
            $userId = Auth::id();
            $guestItems = Database::select("SELECT * FROM cart WHERE session_id = ? AND (user_id IS NULL OR user_id = 0)", [$sessionId]);
            foreach ($guestItems as $item) {
                $existing = Database::selectOne("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?", [$userId, $item['product_id']]);
                if ($existing) {
                    Database::update('cart', ['quantity' => $existing['quantity'] + $item['quantity'], 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$existing['id']]);
                    Database::delete('cart', 'id = ?', [$item['id']]);
                } else {
                    Database::update('cart', ['user_id' => $userId, 'session_id' => null, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$item['id']]);
                }
            }
        }

        $user = Auth::user();
        if (in_array($user['role'], ['super_admin', 'admin'])) Redirect::to('/admin');
        elseif ($user['role'] === 'cashier') Redirect::to('/pos');
        else Redirect::to('/');
    }
    Session::flash('error', 'Invalid email or password');
    Redirect::to('/login');
});

$router->get('/register', function() {
    if (Auth::check()) Redirect::to('/');
    include ROOT_PATH . '/resources/views/auth/register.php';
});

$router->get('/forgot-password', function() {
    if (Auth::check()) Redirect::to('/');
    ob_start();
    include ROOT_PATH . '/resources/views/auth/forgot-password.php';
    $content = ob_get_clean();
    echo $content;
});

$router->post('/forgot-password', function() {
    $email = trim(Request::post('email', ''));
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        Session::flash('error', 'Please enter a valid email address');
        Redirect::to('/forgot-password');
    }

    $user = Database::selectOne("SELECT id, name, email FROM users WHERE email = ?", [$email]);
    if ($user) {
        // Generate token
        $token = bin2hex(random_bytes(32));
        Database::delete('password_resets', 'email = ?', [$email]);
        Database::insert('password_resets', ['email' => $email, 'token' => $token, 'created_at' => date('Y-m-d H:i:s')]);

        // Send reset email
        try {
            if (class_exists('Mailer') && Mailer::isConfigured()) {
                $storeName = Database::selectOne("SELECT value FROM settings WHERE `key` = 'store_name'")['value'] ?? 'ShopSmart';
                $resetUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/reset-password?token=' . $token;
                ob_start();
                $name = $user['name'];
                include ROOT_PATH . '/resources/views/emails/reset-password.php';
                $emailBody = ob_get_clean();
                Mailer::send($email, "Reset your {$storeName} password", $emailBody);
            }
        } catch (\Throwable $e) {
            error_log('[EMAIL] Reset email failed: ' . $e->getMessage());
        }
    }

    // Always show success (prevent email enumeration)
    Session::flash('success', 'If an account exists with that email, a reset link has been sent.');
    Redirect::to('/forgot-password');
});

// Reset Password
$router->get('/reset-password', function() {
    if (Auth::check()) Redirect::to('/');
    ob_start();
    include ROOT_PATH . '/resources/views/auth/reset-password.php';
    $content = ob_get_clean();
    echo $content;
});

$router->post('/reset-password', function() {
    $token = Request::post('token', Request::query('token', ''));
    $password = Request::post('password', '');
    $confirm = Request::post('password_confirmation', '');

    if ($password !== $confirm) { Session::flash('error', 'Passwords do not match'); Redirect::to('/reset-password?token=' . urlencode($token)); }
    if (strlen($password) < 6) { Session::flash('error', 'Password must be at least 6 characters'); Redirect::to('/reset-password?token=' . urlencode($token)); }

    $reset = Database::selectOne("SELECT * FROM password_resets WHERE token = ? AND created_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)", [$token]);
    if (!$reset) { Session::flash('error', 'Invalid or expired reset link. Please request a new one.'); Redirect::to('/forgot-password'); }

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    Database::update('users', ['password' => $hashed, 'updated_at' => date('Y-m-d H:i:s')], 'email = ?', [$reset['email']]);
    Database::delete('password_resets', 'email = ?', [$reset['email']]);

    // Send password changed notification
    try {
        if (class_exists('Mailer') && Mailer::isConfigured()) {
            $user = Database::selectOne("SELECT name, email FROM users WHERE email = ?", [$reset['email']]);
            if ($user) {
                $storeName = Database::selectOne("SELECT value FROM settings WHERE `key` = 'store_name'")['value'] ?? 'ShopSmart';
                ob_start();
                $name = $user['name'];
                include ROOT_PATH . '/resources/views/emails/password-changed.php';
                $emailBody = ob_get_clean();
                Mailer::send($user['email'], "Your {$storeName} password has been changed", $emailBody);
            }
        }
    } catch (\Throwable $e) {
        error_log('[EMAIL] Password changed notification failed: ' . $e->getMessage());
    }

    Session::flash('success', 'Password reset successfully. Please login with your new password.');
    Redirect::to('/login');
});

$router->get('/register', function() {
    if (Auth::check()) Redirect::to('/');
    include ROOT_PATH . '/resources/views/auth/register.php';
});

$router->post('/register', function() {
    $name = Request::post('name', '');
    $email = Request::post('email', '');
    $password = Request::post('password', '');
    $confirm = Request::post('password_confirmation', '');
    $referralCodeInput = strtoupper(trim(Request::post('referral_code', '')));
    if ($password !== $confirm) { Session::flash('error', 'Passwords do not match'); Redirect::to('/register'); }
    $existing = Database::selectOne("SELECT id FROM users WHERE email = ?", [$email]);
    if ($existing) { Session::flash('error', 'Email already registered'); Redirect::to('/register'); }

    // Validate referral code if provided
    $validRefCode = '';
    if (!empty($referralCodeInput)) {
        $refCheck = Database::selectOne("SELECT * FROM referrals WHERE referral_code = ?", [$referralCodeInput]);
        if (!$refCheck) { Session::flash('error', 'Invalid referral code. Please check and try again.'); Redirect::to('/register'); }
        $validRefCode = $referralCodeInput;
    } elseif (isset($_COOKIE['referral_code'])) {
        $validRefCode = $_COOKIE['referral_code'];
    }

    // Remember session_id before register (for cart merge)
    $sessionId = session_id();

    Auth::register(['name' => $name, 'email' => $email, 'password' => $password, 'phone' => Request::post('phone', '')]);

    // Generate referral code for new user
    if (Auth::check()) {
        $userId = Auth::id();
        $myReferralCode = 'REF' . strtoupper(substr(md5($userId . time()), 0, 8));
        try { Database::insert('referrals', ['referrer_id' => $userId, 'referral_code' => $myReferralCode, 'created_at' => date('Y-m-d H:i:s')]); } catch(\Throwable $e) {}

        // If user registered with a referral code, store it as a cookie for order tracking
        if (!empty($validRefCode)) {
            setcookie('referral_code', $validRefCode, time() + 86400 * 90, '/');
        }
    }

    // Merge guest cart items into newly registered user cart
    if (Auth::check()) {
        $userId = Auth::id();
        $guestItems = Database::select("SELECT * FROM cart WHERE session_id = ? AND (user_id IS NULL OR user_id = 0)", [$sessionId]);
        foreach ($guestItems as $item) {
            $existingCart = Database::selectOne("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?", [$userId, $item['product_id']]);
            if ($existingCart) {
                Database::update('cart', ['quantity' => $existingCart['quantity'] + $item['quantity'], 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$existingCart['id']]);
                Database::delete('cart', 'id = ?', [$item['id']]);
            } else {
                Database::update('cart', ['user_id' => $userId, 'session_id' => null, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$item['id']]);
            }
        }
    }

    // Send welcome email (non-blocking)
    try {
        if (class_exists('Mailer') && Mailer::isConfigured()) {
            $storeName = Database::selectOne("SELECT value FROM settings WHERE `key` = 'store_name'")['value'] ?? 'ShopSmart';
            $name = $name; $loginUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/login';
            ob_start();
            include ROOT_PATH . '/resources/views/emails/welcome.php';
            $emailBody = ob_get_clean();
            Mailer::send($email, "Welcome to {$storeName}!", $emailBody);
        }
    } catch (\Throwable $e) {
        error_log('[EMAIL] Welcome email failed: ' . $e->getMessage());
    }

    Redirect::to('/');
});

$router->get('/logout', function() { Auth::logout(); Redirect::to('/'); });

// Customer Account
$router->get('/account', function() {
    if (!Auth::check()) Redirect::to('/login');
    $userId = Auth::id();
    $orders = Database::select("SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC LIMIT 5", [$userId]);
    // Referral data for user dashboard
    $myReferral = Database::selectOne("SELECT * FROM referrals WHERE referrer_id = ? AND referred_id IS NULL LIMIT 1", [$userId]);
    $myReferralLink = '';
    if ($myReferral) {
        $myReferralLink = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/?ref=' . urlencode($myReferral['referral_code']);
    }
    $myReferralStats = Database::selectOne("SELECT COUNT(*) as total_refs, COALESCE(SUM(commission_amount),0) as total_earned, COALESCE(SUM(CASE WHEN status = 'paid' THEN commission_amount ELSE 0 END),0) as total_paid FROM referrals WHERE referrer_id = ? AND referred_id IS NOT NULL", [$userId]);
    ob_start();
    include ROOT_PATH . '/resources/views/customer/account.php';
    $content = ob_get_clean();
    include ROOT_PATH . '/resources/views/layouts/app.php';
});

$router->get('/account/orders', function() {
    if (!Auth::check()) Redirect::to('/login');
    $page = (int)Request::query('page', 1);
    $paginated = Database::paginate('orders', $page, 15, 'customer_id = ?', [Auth::id()], 'created_at DESC');
    $orders = $paginated['data'];
    $pagination = $paginated;
    ob_start();
    include ROOT_PATH . '/resources/views/customer/orders.php';
    $content = ob_get_clean();
    include ROOT_PATH . '/resources/views/layouts/app.php';
});

$router->get('/account/orders/{id}/track', function($id) {
    if (!Auth::check()) Redirect::to('/login');
    $order = Database::selectOne("SELECT * FROM orders WHERE id = ? AND customer_id = ?", [$id, Auth::id()]);
    if (!$order) { http_response_code(404); View::render('errors/404'); return; }
    $items = Database::select("SELECT * FROM order_items WHERE order_id = ?", [$id]);
    ob_start();
    include ROOT_PATH . '/resources/views/customer/order-tracking.php';
    $content = ob_get_clean();
    include ROOT_PATH . '/resources/views/layouts/app.php';
});

$router->get('/account/wishlist', function() {
    if (!Auth::check()) Redirect::to('/login');
    $wishlist = Database::select("SELECT w.*, p.name, p.price, p.discount_price, p.slug, p.stock_quantity, (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1) as image FROM wishlists w JOIN products p ON w.product_id = p.id WHERE w.user_id = ?", [Auth::id()]);
    ob_start();
    include ROOT_PATH . '/resources/views/customer/wishlist.php';
    $content = ob_get_clean();
    include ROOT_PATH . '/resources/views/layouts/app.php';
});

// Wishlist actions
$router->post('/wishlist/toggle/{productId}', function($productId) {
    header('Content-Type: application/json');
    if (!Auth::check()) { http_response_code(401); echo json_encode(['error' => 'Login required']); return; }
    try {
        $existing = Database::selectOne("SELECT id FROM wishlists WHERE user_id = ? AND product_id = ?", [Auth::id(), $productId]);
        if ($existing) {
            Database::delete('wishlists', 'id = ?', [$existing['id']]);
            echo json_encode(['action' => 'removed']);
        } else {
            Database::insert('wishlists', ['user_id' => Auth::id(), 'product_id' => $productId, 'created_at' => date('Y-m-d H:i:s')]);
            echo json_encode(['action' => 'added']);
        }
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update wishlist']);
    }
});

// Wishlist remove (form POST from wishlist page)
$router->post('/wishlist/remove', function() {
    if (!Auth::check()) Redirect::to('/login');
    $productId = (int)Request::post('product_id', 0);
    if ($productId) {
        Database::delete('wishlists', 'user_id = ? AND product_id = ?', [Auth::id(), $productId]);
    }
    Session::flash('success', 'Removed from wishlist');
    Redirect::back();
});

// Public order tracking by order number (no login required)
$router->get('/orders/{orderNumber}/track', function($orderNumber) {
    $order = Database::selectOne("SELECT * FROM orders WHERE order_number = ?", [$orderNumber]);
    if (!$order) { http_response_code(404); View::render('errors/404'); return; }
    $orderItems = Database::select("SELECT oi.*, (SELECT image_path FROM product_images WHERE product_id = oi.product_id AND is_primary = 1) as image FROM order_items oi WHERE oi.order_id = ?", [$order['id']]);
    $pageTitle = 'Track Order ' . e($order['order_number']);
    ob_start();
    include ROOT_PATH . '/resources/views/customer/order-tracking.php';
    $content = ob_get_clean();
    include ROOT_PATH . '/resources/views/layouts/app.php';
});

// Account profile
$router->get('/account/profile', function() {
    if (!Auth::check()) Redirect::to('/login');
    $user = Auth::user();
    $pageTitle = 'Edit Profile - ShopSmart';
    ob_start();
    include ROOT_PATH . '/resources/views/customer/account-profile.php';
    $content = ob_get_clean();
    include ROOT_PATH . '/resources/views/layouts/app.php';
});

// Account profile update
$router->post('/account/profile', function() {
    if (!Auth::check()) Redirect::to('/login');
    $data = [
        'name' => Request::post('name', ''),
        'phone' => Request::post('phone', ''),
        'city' => Request::post('city', ''),
        'address' => Request::post('address', ''),
        'updated_at' => date('Y-m-d H:i:s'),
    ];
    Database::update('users', $data, 'id = ?', [Auth::id()]);
    Session::set('user_name', $data['name']);
    Session::flash('success', 'Profile updated successfully');
    Redirect::to('/account/profile');
});

// Account reviews
$router->get('/account/reviews', function() {
    if (!Auth::check()) Redirect::to('/login');
    $reviews = Database::select("SELECT r.*, p.name as product_name, p.slug as product_slug, (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1) as product_image FROM reviews r JOIN products p ON r.product_id = p.id WHERE r.user_id = ? ORDER BY r.created_at DESC", [Auth::id()]);
    $pageTitle = 'My Reviews - ShopSmart';
    ob_start();
    include ROOT_PATH . '/resources/views/customer/account-reviews.php';
    $content = ob_get_clean();
    include ROOT_PATH . '/resources/views/layouts/app.php';
});

// Submit review
$router->post('/reviews/submit', function() {
    if (!Auth::check()) { Session::flash('error', 'Please login to submit a review'); Redirect::back(); }
    $productId = (int)Request::post('product_id', 0);
    $rating = min(5, max(1, (int)Request::post('rating', 5)));
    $title = trim(Request::post('title', ''));
    $review = trim(Request::post('review', ''));
    if (!$productId || !$review) { Session::flash('error', 'Please fill in the review'); Redirect::back(); }
    // Check if already reviewed
    $existing = Database::selectOne("SELECT id FROM reviews WHERE user_id = ? AND product_id = ?", [Auth::id(), $productId]);
    if ($existing) { Session::flash('error', 'You have already reviewed this product'); Redirect::back(); }
    Database::insert('reviews', [
        'product_id' => $productId,
        'user_id' => Auth::id(),
        'rating' => $rating,
        'title' => $title,
        'review' => $review,
        'is_approved' => 1,
        'created_at' => date('Y-m-d H:i:s'),
    ]);
    Session::flash('success', 'Review submitted! Thank you for your feedback');
    Redirect::back();
});

// Delete own review
$router->post('/account/reviews/{id}/delete', function($id) {
    if (!Auth::check()) Redirect::to('/login');
    $review = Database::selectOne("SELECT * FROM reviews WHERE id = ? AND user_id = ?", [$id, Auth::id()]);
    if ($review) {
        Database::delete('reviews', 'id = ?', [$id]);
        Session::flash('success', 'Review deleted');
    }
    Redirect::to('/account/reviews');
});

// Account addresses
$router->get('/account/addresses', function() {
    if (!Auth::check()) Redirect::to('/login');
    $user = Auth::user();
    $pageTitle = 'My Addresses - ShopSmart';
    ob_start();
    include ROOT_PATH . '/resources/views/customer/account-addresses.php';
    $content = ob_get_clean();
    include ROOT_PATH . '/resources/views/layouts/app.php';
});

$router->post('/account/addresses', function() {
    if (!Auth::check()) Redirect::to('/login');
    Database::update('users', [
        'address' => Request::post('address', ''),
        'city' => Request::post('city', ''),
        'phone' => Request::post('phone', ''),
        'updated_at' => date('Y-m-d H:i:s'),
    ], 'id = ?', [Auth::id()]);
    Session::flash('success', 'Address updated');
    Redirect::to('/account/addresses');
});

// Change Password
$router->get('/account/change-password', function() {
    if (!Auth::check()) Redirect::to('/login');
    ob_start();
    include ROOT_PATH . '/resources/views/customer/change-password.php';
    $content = ob_get_clean();
    include ROOT_PATH . '/resources/views/layouts/app.php';
});

$router->post('/account/change-password', function() {
    if (!Auth::check()) Redirect::to('/login');
    $current = Request::post('current_password', '');
    $new = Request::post('new_password', '');
    $confirm = Request::post('password_confirmation', '');

    $user = Auth::user();
    if (!password_verify($current, $user['password'])) {
        Session::flash('error', 'Current password is incorrect');
        Redirect::to('/account/change-password');
    }
    if ($new !== $confirm) {
        Session::flash('error', 'New passwords do not match');
        Redirect::to('/account/change-password');
    }
    if (strlen($new) < 6) {
        Session::flash('error', 'Password must be at least 6 characters');
        Redirect::to('/account/change-password');
    }

    Database::update('users', ['password' => password_hash($new, PASSWORD_DEFAULT), 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [Auth::id()]);

    // Send password changed notification
    try {
        if (class_exists('Mailer') && Mailer::isConfigured()) {
            $storeName = Database::selectOne("SELECT value FROM settings WHERE `key` = 'store_name'")['value'] ?? 'ShopSmart';
            $name = $user['name'];
            ob_start();
            include ROOT_PATH . '/resources/views/emails/password-changed.php';
            $emailBody = ob_get_clean();
            Mailer::send($user['email'], "Your {$storeName} password has been changed", $emailBody);
        }
    } catch (\Throwable $e) {
        error_log('[EMAIL] Password changed notification failed: ' . $e->getMessage());
    }

    Session::flash('success', 'Password changed successfully');
    Redirect::to('/account/change-password');
});

// ============================================================
// Static Pages
// ============================================================
$router->get('/page/{slug}', function($slug) {
    $page = Database::selectOne("SELECT * FROM pages WHERE slug = ? AND is_active = 1", [$slug]);
    if (!$page) { http_response_code(404); View::render('errors/404'); return; }
    $pageTitle = $page['title'] . ' - ShopSmart';
    ob_start();
    include ROOT_PATH . '/resources/views/customer/page.php';
    $content = ob_get_clean();
    include ROOT_PATH . '/resources/views/layouts/app.php';
});

// Contact page (form handler)
$router->post('/page/contact-us', function() {
    $name = trim(Request::post('name', ''));
    $email = trim(Request::post('email', ''));
    $subject = trim(Request::post('subject', ''));
    $message = trim(Request::post('message', ''));
    if (!$name || !$email || !$message) { Session::flash('error', 'Please fill in all required fields'); Redirect::back(); }
    // Store contact message (could also send email)
    Database::insert('notifications', [
        'type' => 'contact_form',
        'title' => "Contact: $subject",
        'message' => "From: $name ($email)\n\n$message",
        'is_read' => 0,
        'created_at' => date('Y-m-d H:i:s'),
    ]);
    Session::flash('success', 'Thank you for contacting us! We\'ll get back to you soon.');
    Redirect::to('/page/contact-us');
});

// ============================================================
// Admin Routes
// ============================================================

$router->group(['prefix' => 'admin', 'middleware' => 'admin'], function($router) {

    $router->get('/', function() {
        $pageTitle = 'Dashboard - Admin';
        $breadcrumbs = [];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/dashboard.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    // Products
    $router->get('/products', function() {
        $breadcrumbs = [['Products', '/admin/products']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/products.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    $router->get('/products/create', function() {
        $breadcrumbs = [['Products', '/admin/products'], ['Add Product', '']];
        $product = null;
        ob_start();
        include ROOT_PATH . '/resources/views/admin/product-form.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    $router->post('/products/store', function() {
        $name = Request::post('name', '');
        $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name)) . '-' . time();
        $data = [
            'name' => $name, 'slug' => $slug,
            'short_description' => Request::post('short_description', ''),
            'description' => Request::post('description', ''),
            'category_id' => Request::post('category_id') ?: null,
            'brand_id' => Request::post('brand_id') ?: null,
            'price' => (float)Request::post('price', 0),
            'cost_price' => (float)Request::post('cost_price', 0),
            'discount_price' => Request::post('discount_price') ? (float)Request::post('discount_price') : null,
            'stock_quantity' => (int)Request::post('stock_quantity', 0),
            'low_stock_threshold' => (int)Request::post('low_stock_threshold', 10),
            'sku' => Request::post('sku', ''),
            'barcode' => Request::post('barcode', ''),
            'weight' => Request::post('weight') ? (float)Request::post('weight') : null,
            'supplier' => Request::post('supplier', ''),
            'product_status' => Request::post('product_status', 'active'),
            'is_active' => Request::post('is_active') ? 1 : 0,
            'is_featured' => Request::post('is_featured') ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ];
        $productId = Database::insert('products', $data);

        // Handle multiple image uploads
        if (isset($_FILES['images']) && $_FILES['images']['error'][0] !== UPLOAD_ERR_NO_FILE) {
            $fileCount = is_array($_FILES['images']['name']) ? count($_FILES['images']['name']) : 0;
            for ($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['images']['name'][$i],
                        'type' => $_FILES['images']['type'][$i],
                        'tmp_name' => $_FILES['images']['tmp_name'][$i],
                        'error' => $_FILES['images']['error'][$i],
                        'size' => $_FILES['images']['size'][$i],
                    ];
                    $config = require ROOT_PATH . '/config/app.php';
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, $config['upload']['allowed_types']) && $file['size'] <= $config['upload']['max_size']) {
                        $uploadDir = ROOT_PATH . '/public/uploads/products/';
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                        $filename = uniqid() . '_' . time() . '.' . $ext;
                        if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                            $isPrimary = ($i === 0) ? 1 : 0;
                            Database::insert('product_images', [
                                'product_id' => $productId,
                                'image_path' => '/uploads/products/' . $filename,
                                'is_primary' => $isPrimary,
                                'sort_order' => $i,
                                'created_at' => date('Y-m-d H:i:s')
                            ]);
                        }
                    }
                }
            }
        }

        Session::flash('success', 'Product created successfully');
        Redirect::to('/admin/products');
    });

    $router->get('/products/{id}/edit', function($id) {
        $product = Database::selectOne("SELECT * FROM products WHERE id = ?", [$id]);
        if (!$product) { http_response_code(404); echo 'Not found'; return; }
        $breadcrumbs = [['Products', '/admin/products'], ['Edit Product', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/product-form.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    $router->post('/products/{id}/update', function($id) {
        $name = Request::post('name', '');
        $data = [
            'name' => $name,
            'short_description' => Request::post('short_description', ''),
            'description' => Request::post('description', ''),
            'category_id' => Request::post('category_id') ?: null,
            'brand_id' => Request::post('brand_id') ?: null,
            'price' => (float)Request::post('price', 0),
            'cost_price' => (float)Request::post('cost_price', 0),
            'discount_price' => Request::post('discount_price') ? (float)Request::post('discount_price') : null,
            'stock_quantity' => (int)Request::post('stock_quantity', 0),
            'low_stock_threshold' => (int)Request::post('low_stock_threshold', 10),
            'sku' => Request::post('sku', ''),
            'barcode' => Request::post('barcode', ''),
            'weight' => Request::post('weight') ? (float)Request::post('weight') : null,
            'supplier' => Request::post('supplier', ''),
            'product_status' => Request::post('product_status', 'active'),
            'is_active' => Request::post('is_active') ? 1 : 0,
            'is_featured' => Request::post('is_featured') ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        Database::update('products', $data, 'id = ?', [$id]);

        $image = FileUpload::handle('image');
        if ($image) {
            Database::update('product_images', ['is_primary' => 0], 'product_id = ? AND is_primary = 1', [$id]);
            Database::insert('product_images', ['product_id' => $id, 'image_path' => $image, 'is_primary' => 1, 'created_at' => date('Y-m-d H:i:s')]);
        }

        // Handle multiple image uploads
        if (isset($_FILES['images']) && $_FILES['images']['error'][0] !== UPLOAD_ERR_NO_FILE) {
            $existingCount = Database::selectOne("SELECT COUNT(*) as cnt FROM product_images WHERE product_id = ?", [$id])['cnt'];
            $fileCount = is_array($_FILES['images']['name']) ? count($_FILES['images']['name']) : 0;
            for ($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['images']['name'][$i],
                        'type' => $_FILES['images']['type'][$i],
                        'tmp_name' => $_FILES['images']['tmp_name'][$i],
                        'error' => $_FILES['images']['error'][$i],
                        'size' => $_FILES['images']['size'][$i],
                    ];
                    $config = require ROOT_PATH . '/config/app.php';
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, $config['upload']['allowed_types']) && $file['size'] <= $config['upload']['max_size']) {
                        $uploadDir = ROOT_PATH . '/public/uploads/products/';
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                        $filename = uniqid() . '_' . time() . '.' . $ext;
                        if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                            // First image is primary only if no existing primary images
                            $isPrimary = ($i === 0 && $existingCount == 0) ? 1 : 0;
                            Database::insert('product_images', [
                                'product_id' => $id,
                                'image_path' => '/uploads/products/' . $filename,
                                'is_primary' => $isPrimary,
                                'sort_order' => $existingCount + $i,
                                'created_at' => date('Y-m-d H:i:s')
                            ]);
                        }
                    }
                }
            }
        }

        Session::flash('success', 'Product updated successfully');
        Redirect::to('/admin/products');
    });

    $router->post('/products/{id}/delete', function($id) {
        Database::delete('products', 'id = ?', [$id]);
        Session::flash('success', 'Product deleted');
        Redirect::to('/admin/products');
    });

    $router->post('/products/{id}/delete-image/{imageId}', function($id, $imageId) {
        $img = Database::selectOne("SELECT * FROM product_images WHERE id = ? AND product_id = ?", [$imageId, $id]);
        if ($img) {
            $fullPath = ROOT_PATH . '/public' . $img['image_path'];
            if (file_exists($fullPath)) unlink($fullPath);
            Database::delete('product_images', 'id = ?', [$imageId]);
            // If deleted image was primary, set another as primary
            $another = Database::selectOne("SELECT id FROM product_images WHERE product_id = ? AND id != ? ORDER BY sort_order LIMIT 1", [$id, $imageId]);
            if ($another && $img['is_primary']) {
                Database::update('product_images', ['is_primary' => 1], 'id = ?', [$another['id']]);
            }
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        return;
    });

    // Admin product reviews
    $router->get('/products/{id}/reviews', function($id) {
        $product = Database::selectOne("SELECT * FROM products WHERE id = ?", [$id]);
        if (!$product) { Redirect::to('/admin/products'); return; }
        $reviews = Database::select("SELECT r.*, u.name as user_name, u.email as user_email FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? ORDER BY r.created_at DESC", [$id]);
        $breadcrumbs = [['Products', '/admin/products'], [$product['name'], '/admin/products/' . $id . '/reviews'], ['Reviews', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/product-reviews.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    $router->post('/products/{id}/reviews/{reviewId}/approve', function($id, $reviewId) {
        Database::update('reviews', ['is_approved' => 1], 'id = ?', [$reviewId]);
        Session::flash('success', 'Review approved');
        Redirect::to('/admin/products/' . $id . '/reviews');
    });

    $router->post('/products/{id}/reviews/{reviewId}/reject', function($id, $reviewId) {
        Database::update('reviews', ['is_approved' => 0], 'id = ?', [$reviewId]);
        Session::flash('success', 'Review rejected');
        Redirect::to('/admin/products/' . $id . '/reviews');
    });

    $router->post('/products/{id}/reviews/{reviewId}/delete', function($id, $reviewId) {
        Database::delete('reviews', 'id = ?', [$reviewId]);
        Session::flash('success', 'Review deleted');
        Redirect::to('/admin/products/' . $id . '/reviews');
    });

    // Categories
    $router->get('/categories', function() {
        $breadcrumbs = [['Categories', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/categories.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    $router->post('/categories/store', function() {
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
    });

    $router->post('/categories/{id}/update', function($id) {
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
        Database::update('categories', $data, 'id = ?', [$id]);
        Session::flash('success', 'Category updated');
        Redirect::to('/admin/categories');
    });

    $router->post('/categories/{id}/delete', function($id) {
        Database::delete('categories', 'id = ?', [$id]);
        Session::flash('success', 'Category deleted');
        Redirect::to('/admin/categories');
    });

    // Brands
    $router->get('/brands', function() {
        $breadcrumbs = [['Brands', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/brands.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    $router->post('/brands/store', function() {
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
    });

    $router->post('/brands/{id}/delete', function($id) {
        Database::delete('brands', 'id = ?', [$id]);
        Session::flash('success', 'Brand deleted');
        Redirect::to('/admin/brands');
    });

    $router->post('/brands/{id}/update', function($id) {
        $data = [
            'name' => Request::post('name', ''),
            'slug' => Request::post('slug', ''),
            'description' => Request::post('description', ''),
            'is_active' => Request::post('is_active') ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $logo = FileUpload::handle('logo', 'brands');
        if ($logo) $data['logo'] = $logo;
        Database::update('brands', $data, 'id = ?', [$id]);
        Session::flash('success', 'Brand updated');
        Redirect::to('/admin/brands');
    });

    // Inventory
    $router->get('/inventory', function() {
        $breadcrumbs = [['Inventory', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/inventory.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    $router->post('/inventory/adjust', function() {
        $productId = (int)Request::post('product_id', 0);
        $qty = (int)Request::post('quantity', 0);
        $reason = Request::post('reason', 'adjustment');
        Database::update('products', ['stock_quantity' => Database::selectOne("SELECT stock_quantity FROM products WHERE id = ?", [$productId])['stock_quantity'] + $qty], 'id = ?', [$productId]);
        Database::insert('stock_adjustments', ['product_id' => $productId, 'quantity' => $qty, 'reason' => $reason, 'adjusted_by' => Auth::id(), 'created_at' => date('Y-m-d H:i:s')]);
        Session::flash('success', 'Stock adjusted');
        Redirect::to('/admin/inventory');
    });

    // Orders
    $router->get('/orders', function() {
        $breadcrumbs = [['Orders', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/orders.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    $router->get('/orders/{id}', function($id) {
        $order = Database::selectOne("SELECT * FROM orders WHERE id = ?", [$id]);
        if (!$order) { http_response_code(404); echo 'Order not found'; return; }
        $items = Database::select("SELECT * FROM order_items WHERE order_id = ?", [$id]);
        $customer = $order['customer_id'] ? Database::selectOne("SELECT * FROM users WHERE id = ?", [$order['customer_id']]) : null;
        $transactions = Database::select("SELECT * FROM transactions WHERE order_id = ? ORDER BY created_at DESC", [$id]);
        $breadcrumbs = [['Orders', '/admin/orders'], ['Order ' . $order['order_number'], '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/order-detail.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    $router->post('/orders/{id}/status', function($id) {
        $newStatus = Request::post('status', '');
        $update = ['status' => $newStatus, 'updated_at' => date('Y-m-d H:i:s')];
        if (in_array($newStatus, ['paid', 'completed', 'delivered'])) {
            $update['payment_status'] = 'paid';
        }
        Database::update('orders', $update, 'id = ?', [$id]);
        // Process referral commission when admin marks order as paid
        if (in_array($newStatus, ['paid', 'completed', 'delivered'])) {
            processReferralCommission((int)$id);
        }
        Session::flash('success', 'Order status updated to ' . ucfirst($newStatus));
        Redirect::to('/admin/orders/' . $id);
    });

    $router->post('/orders/{id}/notes', function($id) {
        Database::update('orders', [
            'notes' => Request::post('notes', ''),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);
        Session::flash('success', 'Order notes updated');
        Redirect::to('/admin/orders/' . $id);
    });

    // POS Sales
    $router->get('/pos-sales', function() {
        $breadcrumbs = [['POS Sales', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/pos-sales.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    // Customers
    $router->get('/customers', function() {
        $breadcrumbs = [['Customers', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/customers.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    $router->get('/customers/{id}', function($id) {
        $customer = Database::selectOne("SELECT * FROM users WHERE id = ? AND role = 'customer'", [$id]);
        if (!$customer) { http_response_code(404); echo 'Customer not found'; return; }
        $orders = Database::select("SELECT o.*, (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count FROM orders o WHERE o.customer_id = ? ORDER BY o.created_at DESC LIMIT 20", [$id]);
        $stats = Database::selectOne("SELECT COUNT(*) as total, COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total ELSE 0 END), 0) as spent, COALESCE(AVG(CASE WHEN payment_status = 'paid' THEN total END), 0) as avg_order FROM orders WHERE customer_id = ?", [$id]);
        $breadcrumbs = [['Customers', '/admin/customers'], [e($customer['name']), '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/customer-detail.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    // Users
    $router->get('/users', function() {
        $breadcrumbs = [['Users & Roles', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/users.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    $router->post('/users/store', function() {
        Database::insert('users', [
            'name' => Request::post('name', ''), 'email' => Request::post('email', ''),
            'password' => password_hash(Request::post('password', ''), PASSWORD_DEFAULT),
            'role' => Request::post('role', 'customer'),
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        Session::flash('success', 'User created');
        Redirect::to('/admin/users');
    });

    $router->post('/users/{id}/delete', function($id) {
        Database::delete('users', 'id = ?', [$id]);
        Session::flash('success', 'User deleted');
        Redirect::to('/admin/users');
    });

    $router->post('/users/{id}/update', function($id) {
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
        Database::update('users', $data, 'id = ?', [$id]);
        Session::flash('success', 'User updated');
        Redirect::to('/admin/users');
    });

    // ─── Social Publishing (Blotato) ──────────────────────────
    $router->get('/marketing/social', function() {
        $breadcrumbs = [['Marketing', ''], ['Social Publishing', '']];
        $blotatoConnected = false;
        $accounts = [];
        $recentPosts = [];
        try {
            if (class_exists('BlotatoAPI') && BlotatoAPI::getApiKey()) {
                $blotatoConnected = true;
                $accounts = BlotatoAPI::getAccounts();
                $recentPosts = BlotatoAPI::listPosts(10);
            }
        } catch (\Throwable $e) {
            $blotatoConnected = false;
        }
        ob_start();
        include ROOT_PATH . '/resources/views/admin/marketing/social.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    // Blotato: Connect (test & save API key)
    $router->post('/marketing/social/connect', function() {
        header('Content-Type: application/json');
        $key = trim(Request::post('api_key', ''));
        if (empty($key)) { echo json_encode(['success' => false, 'error' => 'API key is required']); return; }
        BlotatoAPI::setApiKey($key);
        try {
            $user = BlotatoAPI::testConnection();
            echo json_encode(['success' => true, 'message' => 'Connected as ' . ($user['email'] ?? $user['name'] ?? 'user'), 'user' => $user]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    });

    // Blotato: Fetch accounts
    $router->post('/marketing/social/accounts', function() {
        header('Content-Type: application/json');
        try {
            $platform = Request::post('platform', null);
            $accounts = BlotatoAPI::getAccounts($platform ?: null);
            echo json_encode(['success' => true, 'accounts' => $accounts]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    });

    // Blotato: Get subaccounts (FB pages, LinkedIn pages, YT playlists)
    $router->post('/marketing/social/subaccounts', function() {
        header('Content-Type: application/json');
        try {
            $accountId = Request::post('account_id', '');
            $result = BlotatoAPI::getSubaccounts($accountId);
            echo json_encode(['success' => true, 'subaccounts' => $result]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    });

    // Blotato: Get Pinterest boards
    $router->post('/marketing/social/pinterest-boards', function() {
        header('Content-Type: application/json');
        try {
            $accountId = Request::post('account_id', '');
            $result = BlotatoAPI::getPinterestBoards($accountId);
            echo json_encode(['success' => true, 'boards' => $result]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    });

    // Blotato: Publish post
    $router->post('/marketing/social/publish', function() {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            $accountId = $input['account_id'] ?? '';
            $platform = $input['platform'] ?? '';
            $text = $input['text'] ?? '';
            $mediaUrls = $input['media_urls'] ?? [];
            $scheduledTime = $input['scheduled_time'] ?? null;
            $useNextFreeSlot = $input['use_next_free_slot'] ?? false;

            if (empty($accountId) || empty($platform) || empty($text)) {
                echo json_encode(['success' => false, 'error' => 'Account, platform, and content are required']);
                return;
            }

            $postPayload = [
                'accountId' => $accountId,
                'content' => [
                    'text' => $text,
                    'mediaUrls' => $mediaUrls,
                    'platform' => $platform,
                ],
                'target' => ['targetType' => $platform],
            ];

            // Platform-specific target fields
            if ($platform === 'facebook' && !empty($input['page_id'])) {
                $postPayload['target']['pageId'] = $input['page_id'];
            }
            if ($platform === 'pinterest' && !empty($input['board_id'])) {
                $postPayload['target']['boardId'] = $input['board_id'];
            }
            if ($platform === 'youtube') {
                $postPayload['target']['title'] = $input['title'] ?? '';
                $postPayload['target']['privacyStatus'] = $input['privacy_status'] ?? 'public';
                $postPayload['target']['shouldNotifySubscribers'] = (bool)($input['notify_subscribers'] ?? false);
            }
            if ($platform === 'tiktok') {
                $postPayload['target']['privacyLevel'] = $input['privacy_level'] ?? 'PUBLIC_TO_EVERYONE';
                $postPayload['target']['disabledComments'] = (bool)($input['disabled_comments'] ?? false);
                $postPayload['target']['disabledDuet'] = (bool)($input['disabled_duet'] ?? false);
                $postPayload['target']['disabledStitch'] = (bool)($input['disabled_stitch'] ?? false);
                $postPayload['target']['isBrandedContent'] = (bool)($input['is_branded_content'] ?? false);
                $postPayload['target']['isYourBrand'] = (bool)($input['is_your_brand'] ?? false);
                $postPayload['target']['isAiGenerated'] = false;
            }

            $rootPayload = ['post' => $postPayload];
            if (!empty($scheduledTime)) {
                $rootPayload['scheduledTime'] = $scheduledTime;
            } elseif ($useNextFreeSlot) {
                $rootPayload['useNextFreeSlot'] = true;
            }

            $result = BlotatoAPI::createPost($rootPayload);
            echo json_encode(['success' => true, 'post_submission_id' => $result['postSubmissionId'] ?? '', 'message' => 'Post submitted successfully']);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    });

    // Blotato: Get post status (polling)
    $router->get('/marketing/social/post-status', function() {
        header('Content-Type: application/json');
        try {
            $id = Request::query('id', '');
            if (empty($id)) { echo json_encode(['success' => false, 'error' => 'Post ID required']); return; }
            $result = BlotatoAPI::getPostStatus($id);
            echo json_encode(['success' => true, 'data' => $result]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    });

    // Blotato: List posts
    $router->get('/marketing/social/posts', function() {
        header('Content-Type: application/json');
        try {
            $result = BlotatoAPI::listPosts(20);
            echo json_encode(['success' => true, 'posts' => $result]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    });

    // Blotato: Analytics
    $router->get('/marketing/social/analytics', function() {
        header('Content-Type: application/json');
        try {
            $sortBy = Request::query('sort_by', 'views_count');
            $platform = Request::query('platform', null);
            $result = BlotatoAPI::listTopPosts($sortBy, 10, $platform);
            echo json_encode(['success' => true, 'data' => $result]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    });

    // Blotato: List visual templates
    $router->get('/marketing/social/templates', function() {
        header('Content-Type: application/json');
        try {
            $result = BlotatoAPI::listTemplates();
            echo json_encode(['success' => true, 'templates' => $result]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    });

    // Blotato: Create visual from template
    $router->post('/marketing/social/create-visual', function() {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $templateId = $input['template_id'] ?? '';
            $prompt = $input['prompt'] ?? '';
            if (empty($templateId) || empty($prompt)) {
                echo json_encode(['success' => false, 'error' => 'Template ID and prompt are required']);
                return;
            }
            $result = BlotatoAPI::createVisual($templateId, $prompt);
            echo json_encode(['success' => true, 'id' => $result['item']['id'] ?? '', 'status' => $result['item']['status'] ?? 'queueing']);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    });

    // Blotato: Get visual status
    $router->get('/marketing/social/visual-status', function() {
        header('Content-Type: application/json');
        try {
            $id = Request::query('id', '');
            if (empty($id)) { echo json_encode(['success' => false, 'error' => 'Visual ID required']); return; }
            $result = BlotatoAPI::getVisualStatus($id);
            echo json_encode(['success' => true, 'data' => $result]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    });

    // Blotato: Upload media
    $router->post('/marketing/social/upload', function() {
        header('Content-Type: application/json');
        try {
            $url = trim(Request::post('url', ''));
            if (empty($url)) { echo json_encode(['success' => false, 'error' => 'Media URL is required']); return; }
            $result = BlotatoAPI::uploadMediaFromUrl($url);
            echo json_encode(['success' => true, 'data' => $result]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    });

    // Blotato: List scheduled posts
    $router->get('/marketing/social/schedules', function() {
        header('Content-Type: application/json');
        try {
            $result = BlotatoAPI::listSchedules(20);
            echo json_encode(['success' => true, 'schedules' => $result]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    });

    // Blotato: Update schedule
    $router->post('/marketing/social/schedule-update', function() {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? '';
            $scheduledTime = $input['scheduled_time'] ?? null;
            if (empty($id)) { echo json_encode(['success' => false, 'error' => 'Schedule ID required']); return; }
            $patch = [];
            if ($scheduledTime) $patch['scheduledTime'] = $scheduledTime;
            BlotatoAPI::updateSchedule($id, $patch);
            echo json_encode(['success' => true]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    });

    // Blotato: Delete schedule
    $router->post('/marketing/social/schedule-delete', function() {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? '';
            if (empty($id)) { echo json_encode(['success' => false, 'error' => 'Schedule ID required']); return; }
            BlotatoAPI::deleteSchedule($id);
            echo json_encode(['success' => true]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    });

    $router->get('/marketing/whatsapp', function() {
        $breadcrumbs = [['Marketing', ''], ['WhatsApp', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/marketing/whatsapp.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    $router->get('/marketing/email', function() {
        $breadcrumbs = [['Marketing', ''], ['Email Campaigns', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/marketing/email.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    // ─── WhatsApp API Routes ──────────────────────────
    $router->post('/marketing/whatsapp/settings', function() {
        header('Content-Type: application/json');
        $fields = ['wa_phone_id','wa_access_token','wa_business_id','wa_verify_token','wa_api_version'];
        foreach ($fields as $f) {
            $val = Request::post($f, '');
            if ($f === 'wa_access_token' && empty($val)) {
                $existing = Database::selectOne("SELECT id FROM settings WHERE `key` = ?", [$f]);
                if ($existing) continue;
            }
            $existing = Database::selectOne("SELECT id FROM settings WHERE `key` = ?", [$f]);
            if ($existing) {
                Database::update('settings', ['value' => $val, 'updated_at' => date('Y-m-d H:i:s')], '`key` = ?', [$f]);
            } else {
                Database::insert('settings', ['key' => $f, 'value' => $val, 'group_name' => 'whatsapp', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
            }
        }
        echo json_encode(['success' => true]);
    });

    $router->post('/marketing/whatsapp/test', function() {
        header('Content-Type: application/json');
        $phoneId = '';
        try { $r = Database::selectOne("SELECT value FROM settings WHERE `key` = 'wa_phone_id'"); $phoneId = $r['value'] ?? ''; } catch (\Throwable $e) {}
        $token = '';
        try { $r = Database::selectOne("SELECT value FROM settings WHERE `key` = 'wa_access_token'"); $token = $r['value'] ?? ''; } catch (\Throwable $e) {}
        $version = '';
        try { $r = Database::selectOne("SELECT value FROM settings WHERE `key` = 'wa_api_version'"); $version = $r['value'] ?? 'v21.0'; } catch (\Throwable $e) {}

        if (empty($phoneId) || empty($token)) {
            echo json_encode(['success' => false, 'error' => 'Phone Number ID and Access Token are required']);
            return;
        }

        $ch = curl_init("https://graph.facebook.com/{$version}/{$phoneId}");
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_TIMEOUT => 15, CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token]]);
        $resp = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($resp === false) {
            echo json_encode(['success' => false, 'error' => 'Network error: ' . $err]);
        } else {
            $data = json_decode($resp, true);
            if (isset($data['display_phone_number'])) {
                echo json_encode(['success' => true, 'message' => 'Connected to ' . $data['display_phone_number']]);
            } else {
                $errMsg = $data['error']['message'] ?? json_encode($data);
                echo json_encode(['success' => false, 'error' => $errMsg]);
            }
        }
    });

    $router->post('/marketing/whatsapp/send', function() {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        $message = trim($input['message'] ?? '');
        $audience = $input['audience'] ?? 'all_customers';

        if (empty($message)) { echo json_encode(['success' => false, 'error' => 'Message is required']); return; }

        $phoneId = '';
        try { $r = Database::selectOne("SELECT value FROM settings WHERE `key` = 'wa_phone_id'"); $phoneId = $r['value'] ?? ''; } catch (\Throwable $e) {}
        $token = '';
        try { $r = Database::selectOne("SELECT value FROM settings WHERE `key` = 'wa_access_token'"); $token = $r['value'] ?? ''; } catch (\Throwable $e) {}
        $version = '';
        try { $r = Database::selectOne("SELECT value FROM settings WHERE `key` = 'wa_api_version'"); $version = $r['value'] ?? 'v21.0'; } catch (\Throwable $e) {}

        if (empty($phoneId) || empty($token)) {
            echo json_encode(['success' => false, 'error' => 'WhatsApp not configured. Go to Settings first.']);
            return;
        }

        // Get recipient phone numbers
        $phones = [];
        if ($audience === 'custom') {
            $raw = $input['custom_phones'] ?? '';
            foreach (explode("\n", $raw) as $line) {
                $phone = preg_replace('/[^0-9]/', '', trim($line));
                if (strlen($phone) >= 10) $phones[] = $phone;
            }
        } else {
            $where = '1=1';
            if ($audience === 'new_customers') $where = 'created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
            if ($audience === 'vip') $where = '(SELECT COUNT(*) FROM orders WHERE orders.user_id = users.id AND payment_status = \'paid\') >= 5';
            if ($audience === 'subscribers') {
                $subs = Database::select("SELECT phone FROM newsletter_subscribers WHERE status = 'active' AND phone IS NOT NULL AND phone != ''");
                foreach ($subs as $s) {
                    $phone = preg_replace('/[^0-9]/', '', $s['phone']);
                    if (strlen($phone) >= 10) $phones[] = $phone;
                }
            } else {
                $users = Database::select("SELECT phone FROM users WHERE $where AND phone IS NOT NULL AND phone != '' LIMIT 500");
                foreach ($users as $u) {
                    $phone = preg_replace('/[^0-9]/', '', $u['phone']);
                    if (strlen($phone) >= 10) $phones[] = $phone;
                }
            }
        }

        if (empty($phones)) {
            echo json_encode(['success' => false, 'error' => 'No recipients found']);
            return;
        }

        // Send via WhatsApp Business API
        $sent = 0;
        $failed = 0;
        $phoneIdClean = preg_replace('/[^0-9]/', '', $phoneId);

        foreach (array_slice($phones, 0, 100) as $phone) {
            // Ensure phone has country code format
            if (!str_starts_with($phone, '+')) $phone = '+' . $phone;

            $payload = json_encode([
                'messaging_product' => 'whatsapp',
                'to' => $phone,
                'type' => 'text',
                'text' => ['body' => $message],
            ]);

            $ch = curl_init("https://graph.facebook.com/{$version}/{$phoneIdClean}/messages");
            curl_setopt_array($ch, [
                CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 15, CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
                CURLOPT_POSTFIELDS => $payload,
            ]);
            $resp = curl_exec($ch);
            curl_close($ch);
            $data = json_decode($resp, true);

            if (isset($data['messages'][0]['id'])) $sent++;
            else $failed++;
        }

        Database::insert('marketing_campaigns', [
            'name' => 'WA Blast ' . date('M j, g:i A'), 'platform' => 'whatsapp', 'type' => 'blast',
            'status' => $sent > 0 ? 'sent' : 'failed', 'content' => $message,
            'total_sent' => $sent, 'total_failed' => $failed,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);

        echo json_encode(['success' => $sent > 0, 'sent' => $sent, 'failed' => $failed, 'total' => count($phones)]);
    });

    // Payments
    $router->get('/payments', function() {
        $breadcrumbs = [['Payments', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/payments/payments.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    $router->get('/payments/settings', function() {
        $breadcrumbs = [['Payments', '/admin/payments'], ['Settings', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/payments/settings.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    // Helper: upsert a setting
    $saveSetting = function($key, $value, $group = 'payments') {
        $existing = Database::selectOne("SELECT id FROM settings WHERE `key` = ?", [$key]);
        $now = date('Y-m-d H:i:s');
        if ($existing) {
            Database::update('settings', ['value' => $value, 'updated_at' => $now], '`key` = ?', [$key]);
        } else {
            Database::insert('settings', ['key' => $key, 'value' => $value, 'group_name' => $group, 'created_at' => $now, 'updated_at' => $now]);
        }
    };

    // Toggle gateway enabled/disabled
    $router->post('/payments/settings/toggle', function() use ($saveSetting) {
        $gateway = Request::post('gateway', '');
        $enabled = Request::post('enabled', '0');
        $allowed = ['mpesa', 'stripe', 'intasend', 'pesapal', 'paypal'];
        if (!in_array($gateway, $allowed)) { Session::flash('error', 'Invalid gateway'); Redirect::back(); }
        $saveSetting($gateway . '_enabled', $enabled === '1' ? '1' : '0');
        Session::flash('success', ucfirst($gateway) . ' ' . ($enabled === '1' ? 'enabled' : 'disabled'));
        Redirect::to('/admin/payments/settings');
    });

    // Save M-Pesa settings
    $router->post('/payments/settings/mpesa', function() use ($saveSetting) {
        $fields = ['mpesa_env', 'mpesa_consumer_key', 'mpesa_consumer_secret', 'mpesa_shortcode', 'mpesa_passkey', 'mpesa_callback'];
        foreach ($fields as $f) { $saveSetting($f, Request::post($f, '')); }
        Session::flash('success', 'M-Pesa settings saved');
        Redirect::to('/admin/payments/settings');
    });

    // Save Stripe settings
    $router->post('/payments/settings/stripe', function() use ($saveSetting) {
        $fields = ['stripe_key', 'stripe_secret', 'stripe_webhook_secret'];
        foreach ($fields as $f) { $saveSetting($f, Request::post($f, '')); }
        Session::flash('success', 'Stripe settings saved');
        Redirect::to('/admin/payments/settings');
    });

    // Save IntaSend settings
    $router->post('/payments/settings/intasend', function() use ($saveSetting) {
        $fields = ['intasend_key', 'intasend_secret', 'intasend_publishable'];
        foreach ($fields as $f) { $saveSetting($f, Request::post($f, '')); }
        Session::flash('success', 'IntaSend settings saved');
        Redirect::to('/admin/payments/settings');
    });

    // Save PesaPal settings
    $router->post('/payments/settings/pesapal', function() use ($saveSetting) {
        $fields = ['pesapal_env', 'pesapal_key', 'pesapal_secret', 'pesapal_ipn_id'];
        foreach ($fields as $f) { $saveSetting($f, Request::post($f, '')); }
        Session::flash('success', 'PesaPal settings saved');
        Redirect::to('/admin/payments/settings');
    });

    // Save PayPal settings
    $router->post('/payments/settings/paypal', function() use ($saveSetting) {
        $fields = ['paypal_env', 'paypal_currency', 'paypal_exchange_rate', 'paypal_client_id', 'paypal_secret', 'paypal_webhook_id'];
        foreach ($fields as $f) { $saveSetting($f, Request::post($f, '')); }
        Session::flash('success', 'PayPal settings saved');
        Redirect::to('/admin/payments/settings');
    });

    // Reports
    $router->get('/reports', function() {
        $breadcrumbs = [['Reports', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/reports.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    // Analytics
    $router->get('/analytics', function() {
        $breadcrumbs = [['Analytics', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/analytics.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    // Blog Posts
    $router->get('/blogs', function() {
        $page = (int)Request::query('page', 1);
        $search = Request::query('search', '');

        $where = '1=1';
        $params = [];
        if ($search) { $where .= ' AND (bp.title LIKE ? OR bp.slug LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }

        $posts = Database::paginate("blog_posts bp LEFT JOIN users u ON bp.author_id = u.id", $page, 15, $where, $params, 'bp.created_at DESC', 'bp.*, u.name as author_name');

        $breadcrumbs = [['Blog Posts', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/blogs.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    $router->get('/blogs/create', function() {
        $post = null;
        $breadcrumbs = [['Blog Posts', '/admin/blogs'], ['Create Post', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/blog-form.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    $router->post('/blogs/store', function() {
        $title = Request::post('title', '');
        $slug = Request::post('slug', '') ?: strtolower(preg_replace('/[^a-z0-9]+/', '-', $title));

        // Ensure unique slug
        $existing = Database::selectOne("SELECT id FROM blog_posts WHERE slug = ?", [$slug]);
        if ($existing) { $slug .= '-' . time(); }

        $featuredImage = FileUpload::handle('featured_image', 'blog');

        Database::insert('blog_posts', [
            'title' => $title,
            'slug' => $slug,
            'excerpt' => Request::post('excerpt', ''),
            'content' => Request::post('content', ''),
            'featured_image' => $featuredImage,
            'author_id' => Auth::id(),
            'is_published' => Request::post('is_published') ? 1 : 0,
            'meta_title' => Request::post('meta_title', ''),
            'meta_description' => Request::post('meta_description', ''),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        Session::flash('success', 'Blog post created');
        Redirect::to('/admin/blogs');
    });

    $router->get('/blogs/{id}/edit', function($id) {
        $post = Database::selectOne("SELECT * FROM blog_posts WHERE id = ?", [(int)$id]);
        if (!$post) Redirect::to('/admin/blogs');
        $breadcrumbs = [['Blog Posts', '/admin/blogs'], [e($post['title']), '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/blog-form.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    $router->post('/blogs/{id}/update', function($id) {
        $post = Database::selectOne("SELECT * FROM blog_posts WHERE id = ?", [(int)$id]);
        if (!$post) Redirect::to('/admin/blogs');

        $title = Request::post('title', '');
        $slug = Request::post('slug', '') ?: strtolower(preg_replace('/[^a-z0-9]+/', '-', $title));

        // Ensure unique slug (exclude current post)
        $existing = Database::selectOne("SELECT id FROM blog_posts WHERE slug = ? AND id != ?", [$slug, (int)$id]);
        if ($existing) { $slug .= '-' . time(); }

        $featuredImage = FileUpload::handle('featured_image', 'blog');
        $data = [
            'title' => $title,
            'slug' => $slug,
            'excerpt' => Request::post('excerpt', ''),
            'content' => Request::post('content', ''),
            'is_published' => Request::post('is_published') ? 1 : 0,
            'meta_title' => Request::post('meta_title', ''),
            'meta_description' => Request::post('meta_description', ''),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($featuredImage) {
            if ($post['featured_image']) FileUpload::delete($post['featured_image']);
            $data['featured_image'] = $featuredImage;
        }

        Database::update('blog_posts', $data, 'id = ?', [(int)$id]);
        Session::flash('success', 'Blog post updated');
        Redirect::to('/admin/blogs');
    });

    $router->post('/blogs/{id}/delete', function($id) {
        $post = Database::selectOne("SELECT * FROM blog_posts WHERE id = ?", [(int)$id]);
        if ($post) {
            if ($post['featured_image']) FileUpload::delete($post['featured_image']);
            Database::delete('blog_posts', 'id = ?', [(int)$id]);
        }
        Session::flash('success', 'Blog post deleted');
        Redirect::to('/admin/blogs');
    });

    // Settings
    $router->get('/settings', function() {
        $breadcrumbs = [['Settings', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/settings.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    $router->post('/settings/update', function() {
        // Helper: upsert a setting
        $upsert = function($key, $value, $group = 'general') {
            $existing = Database::selectOne("SELECT id FROM settings WHERE `key` = ?", [$key]);
            if ($existing) {
                Database::update('settings', ['value' => $value, 'updated_at' => date('Y-m-d H:i:s')], '`key` = ?', [$key]);
            } else {
                Database::insert('settings', ['key' => $key, 'value' => $value, 'group_name' => $group, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
            }
        };

        // Handle logo upload
        if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['site_logo'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp','svg'];
            if (in_array($ext, $allowed) && $file['size'] <= 5*1024*1024) {
                $uploadDir = ROOT_PATH . '/public/uploads/settings/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $filename = 'logo_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                    // Remove old logo
                    $oldLogo = Database::selectOne("SELECT value FROM settings WHERE `key` = 'site_logo'");
                    if ($oldLogo && $oldLogo['value']) {
                        $oldPath = ROOT_PATH . '/public' . $oldLogo['value'];
                        if (file_exists($oldPath)) unlink($oldPath);
                    }
                    $upsert('site_logo', '/uploads/settings/' . $filename, 'general');
                }
            }
        }

        // Remove logo
        if (Request::post('remove_logo') == '1') {
            $oldLogo = Database::selectOne("SELECT value FROM settings WHERE `key` = 'site_logo'");
            if ($oldLogo && $oldLogo['value']) {
                $oldPath = ROOT_PATH . '/public' . $oldLogo['value'];
                if (file_exists($oldPath)) unlink($oldPath);
            }
            $upsert('site_logo', '', 'general');
        }

        // Handle favicon upload
        if (isset($_FILES['site_favicon']) && $_FILES['site_favicon']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['site_favicon'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['ico','png','jpg','jpeg','svg','gif','webp'];
            if (in_array($ext, $allowed) && $file['size'] <= 2*1024*1024) {
                $uploadDir = ROOT_PATH . '/public/uploads/settings/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $filename = 'favicon_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                    // Remove old favicon
                    $oldFav = Database::selectOne("SELECT value FROM settings WHERE `key` = 'site_favicon'");
                    if ($oldFav && $oldFav['value']) {
                        $oldPath = ROOT_PATH . '/public' . $oldFav['value'];
                        if (file_exists($oldPath)) unlink($oldPath);
                    }
                    $upsert('site_favicon', '/uploads/settings/' . $filename, 'general');
                }
            }
        }

        // Remove favicon
        if (Request::post('remove_favicon') == '1') {
            $oldFav = Database::selectOne("SELECT value FROM settings WHERE `key` = 'site_favicon'");
            if ($oldFav && $oldFav['value']) {
                $oldPath = ROOT_PATH . '/public' . $oldFav['value'];
                if (file_exists($oldPath)) unlink($oldPath);
            }
            $upsert('site_favicon', '', 'general');
        }

        // General text fields
        $fields = ['store_name','store_tagline','store_email','store_phone','store_address','currency','tax_rate','shipping_banner_text'];
        foreach ($fields as $f) {
            $upsert($f, Request::post($f, ''), 'general');
        }

        // Notification checkboxes
        $notifFields = ['notify_new_order','notify_payment','notify_low_stock','notify_new_customer'];
        foreach ($notifFields as $f) {
            $upsert($f, Request::post($f, '0'), 'general');
        }

        // Google Reviews settings (only business ID, no API key needed)
        $googleFields = ['google_place_id'];
        foreach ($googleFields as $f) {
            $upsert($f, Request::post($f, ''), 'general');
        }

        // Blotato API key
        $upsert('blotato_api_key', Request::post('blotato_api_key', ''), 'blotato');

        // Color settings
        $colorFields = ['primary_color','primary_hover_color','header_bg_color','footer_bg_color'];
        foreach ($colorFields as $f) {
            $val = Request::post($f, '');
            if (!$val) $val = Request::post($f . '_text', '');
            $upsert($f, $val, 'general');
        }

        Session::flash('success', 'Settings saved successfully');
        Redirect::to('/admin/settings');
    });

    // API Integrations
    $router->get('/api-integrations', function() {
        $breadcrumbs = [['API Integrations', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/api-integrations.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    // ============================================================
    // Frontend Content Management
    // ============================================================

    // Hero Slides - List
    $router->get('/hero-slides', function() {
        $breadcrumbs = [['Frontend Content', ''], ['Hero Slides', '']];
        $slides = Database::select("SELECT * FROM hero_slides ORDER BY sort_order ASC");

        // Handle move up/down
        $action = Request::query('action', '');
        $id = (int)Request::query('id', 0);
        if ($action === 'move_up' && $id) {
            $slide = Database::selectOne("SELECT * FROM hero_slides WHERE id = ?", [$id]);
            if ($slide && $slide['sort_order'] > 1) {
                $prev = Database::selectOne("SELECT * FROM hero_slides WHERE sort_order = ?", [$slide['sort_order'] - 1]);
                if ($prev) Database::update('hero_slides', ['sort_order' => $slide['sort_order']], 'id = ?', [$prev['id']]);
                Database::update('hero_slides', ['sort_order' => $slide['sort_order'] - 1], 'id = ?', [$id]);
            }
            Redirect::to('/admin/hero-slides');
        }
        if ($action === 'move_down' && $id) {
            $slide = Database::selectOne("SELECT * FROM hero_slides WHERE id = ?", [$id]);
            $maxOrder = Database::selectOne("SELECT MAX(sort_order) as m FROM hero_slides")['m'] ?? 1;
            if ($slide && $slide['sort_order'] < $maxOrder) {
                $next = Database::selectOne("SELECT * FROM hero_slides WHERE sort_order = ?", [$slide['sort_order'] + 1]);
                if ($next) Database::update('hero_slides', ['sort_order' => $slide['sort_order']], 'id = ?', [$next['id']]);
                Database::update('hero_slides', ['sort_order' => $slide['sort_order'] + 1], 'id = ?', [$id]);
            }
            Redirect::to('/admin/hero-slides');
        }

        $slides = Database::select("SELECT * FROM hero_slides ORDER BY sort_order ASC");
        $editSlide = null;
        ob_start();
        include ROOT_PATH . '/resources/views/admin/hero-slides.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    // Hero Slides - Create
    $router->post('/hero-slides', function() {
        $maxOrder = Database::selectOne("SELECT MAX(sort_order) as m FROM hero_slides")['m'] ?? 0;
        $imageUrl = FileUpload::handle('image', 'hero-slides') ?? Request::post('image_url', '');
        Database::insert('hero_slides', [
            'title' => Request::post('title', ''),
            'subtitle' => Request::post('subtitle', ''),
            'description' => Request::post('description', ''),
            'cta_text' => Request::post('cta_text', 'Shop Now'),
            'cta_link' => Request::post('cta_link', '/products'),
            'image_url' => $imageUrl,
            'bg_gradient' => Request::post('bg_gradient', 'from-amber-700 via-orange-800 to-red-900'),
            'text_position' => Request::post('text_position', 'left'),
            'overlay' => Request::post('overlay', 'dark'),
            'sort_order' => (int)Request::post('sort_order', $maxOrder + 1),
            'is_active' => Request::post('is_active') ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        Session::flash('success', 'Hero slide created successfully');
        Redirect::to('/admin/hero-slides');
    });

    // Hero Slides - Edit
    $router->post('/hero-slides/edit/{id}', function($id) {
        $imageUrl = FileUpload::handle('image', 'hero-slides');
        $data = [
            'title' => Request::post('title', ''),
            'subtitle' => Request::post('subtitle', ''),
            'description' => Request::post('description', ''),
            'cta_text' => Request::post('cta_text', 'Shop Now'),
            'cta_link' => Request::post('cta_link', '/products'),
            'bg_gradient' => Request::post('bg_gradient', 'from-amber-700 via-orange-800 to-red-900'),
            'text_position' => Request::post('text_position', 'left'),
            'overlay' => Request::post('overlay', 'dark'),
            'sort_order' => (int)Request::post('sort_order', 1),
            'is_active' => Request::post('is_active') ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        // Only update image_url if a new file was uploaded or a URL was provided
        $fallbackUrl = Request::post('image_url', '');
        if ($imageUrl) {
            $data['image_url'] = $imageUrl;
        } elseif ($fallbackUrl) {
            $data['image_url'] = $fallbackUrl;
        }
        Database::update('hero_slides', $data, 'id = ?', [$id]);
        Session::flash('success', 'Hero slide updated successfully');
        Redirect::to('/admin/hero-slides');
    });

    // Hero Slides - Delete
    $router->post('/hero-slides/delete', function() {
        $id = (int)Request::post('id', 0);
        if ($id) {
            Database::delete('hero_slides', 'id = ?', [$id]);
            // Re-sort remaining
            $slides = Database::select("SELECT * FROM hero_slides ORDER BY sort_order ASC");
            foreach ($slides as $i => $s) {
                Database::update('hero_slides', ['sort_order' => $i + 1], 'id = ?', [$s['id']]);
            }
        }
        Session::flash('success', 'Hero slide deleted');
        Redirect::to('/admin/hero-slides');
    });

    // Promo Banners - List
    $router->get('/promo-banners', function() {
        $breadcrumbs = [['Frontend Content', ''], ['Promo Banners', '']];
        $banners = Database::select("SELECT * FROM promo_banners ORDER BY position ASC");
        $editBanner = null;

        $action = Request::query('action', '');
        $id = (int)Request::query('id', 0);
        if ($action === 'edit' && $id) {
            $editBanner = Database::selectOne("SELECT * FROM promo_banners WHERE id = ?", [$id]);
            $banners = Database::select("SELECT * FROM promo_banners ORDER BY position ASC");
        }

        ob_start();
        include ROOT_PATH . '/resources/views/admin/promo-banners.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    // Promo Banners - Create
    $router->post('/promo-banners', function() {
        $maxPos = Database::selectOne("SELECT MAX(position) as m FROM promo_banners")['m'] ?? 0;
        Database::insert('promo_banners', [
            'title' => Request::post('title', ''),
            'subtitle' => Request::post('subtitle', ''),
            'cta_text' => Request::post('cta_text', 'Shop Now'),
            'cta_link' => Request::post('cta_link', '/products'),
            'bg_gradient' => Request::post('bg_gradient', 'from-amber-500 to-orange-600'),
            'icon' => Request::post('icon', 'zap'),
            'position' => (int)Request::post('position', $maxPos + 1),
            'is_active' => Request::post('is_active') ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        Session::flash('success', 'Promo banner created successfully');
        Redirect::to('/admin/promo-banners');
    });

    // Promo Banners - Edit
    $router->post('/promo-banners/edit/{id}', function($id) {
        Database::update('promo_banners', [
            'title' => Request::post('title', ''),
            'subtitle' => Request::post('subtitle', ''),
            'cta_text' => Request::post('cta_text', 'Shop Now'),
            'cta_link' => Request::post('cta_link', '/products'),
            'bg_gradient' => Request::post('bg_gradient', 'from-amber-500 to-orange-600'),
            'icon' => Request::post('icon', 'zap'),
            'position' => (int)Request::post('position', 1),
            'is_active' => Request::post('is_active') ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);
        Session::flash('success', 'Promo banner updated successfully');
        Redirect::to('/admin/promo-banners');
    });

    // Promo Banners - Store (matches JS action /admin/promo-banners/store)
    $router->post('/promo-banners/store', function() {
        $maxPos = Database::selectOne("SELECT MAX(position) as m FROM promo_banners")['m'] ?? 0;
        Database::insert('promo_banners', [
            'title' => Request::post('title', ''),
            'subtitle' => Request::post('subtitle', ''),
            'cta_text' => Request::post('cta_text', 'Shop Now'),
            'cta_link' => Request::post('cta_link', '/products'),
            'bg_gradient' => Request::post('bg_gradient', 'from-amber-500 to-orange-600'),
            'icon' => Request::post('icon', 'zap'),
            'position' => (int)Request::post('position', $maxPos + 1),
            'is_active' => Request::post('is_active') ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        Session::flash('success', 'Promo banner created successfully');
        Redirect::to('/admin/promo-banners');
    });

    // Promo Banners - Update (matches JS action /admin/promo-banners/{id}/update)
    $router->post('/promo-banners/{id}/update', function($id) {
        Database::update('promo_banners', [
            'title' => Request::post('title', ''),
            'subtitle' => Request::post('subtitle', ''),
            'cta_text' => Request::post('cta_text', 'Shop Now'),
            'cta_link' => Request::post('cta_link', '/products'),
            'bg_gradient' => Request::post('bg_gradient', 'from-amber-500 to-orange-600'),
            'icon' => Request::post('icon', 'zap'),
            'position' => (int)Request::post('position', 1),
            'is_active' => Request::post('is_active') ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);
        Session::flash('success', 'Promo banner updated successfully');
        Redirect::to('/admin/promo-banners');
    });

    // Promo Banners - Delete by ID in URL (matches JS action /admin/promo-banners/{id}/delete)
    $router->post('/promo-banners/{id}/delete', function($id) {
        $id = (int)$id;
        if ($id) Database::delete('promo_banners', 'id = ?', [$id]);
        Session::flash('success', 'Promo banner deleted');
        Redirect::to('/admin/promo-banners');
    });

    // Promo Banners - Delete (original POST body id)
    $router->post('/promo-banners/delete', function() {
        $id = (int)Request::post('id', 0);
        if ($id) Database::delete('promo_banners', 'id = ?', [$id]);
        Session::flash('success', 'Promo banner deleted');
        Redirect::to('/admin/promo-banners');
    });

    // Trust Badges - List
    $router->get('/trust-badges', function() {
        $breadcrumbs = [['Frontend Content', ''], ['Trust Badges', '']];
        $badges = Database::select("SELECT * FROM trust_badges ORDER BY sort_order ASC");
        $editBadge = null;

        $action = Request::query('action', '');
        $id = (int)Request::query('id', 0);

        if ($action === 'move_up' && $id) {
            $badge = Database::selectOne("SELECT * FROM trust_badges WHERE id = ?", [$id]);
            if ($badge && $badge['sort_order'] > 1) {
                $prev = Database::selectOne("SELECT * FROM trust_badges WHERE sort_order = ?", [$badge['sort_order'] - 1]);
                if ($prev) Database::update('trust_badges', ['sort_order' => $badge['sort_order']], 'id = ?', [$prev['id']]);
                Database::update('trust_badges', ['sort_order' => $badge['sort_order'] - 1], 'id = ?', [$id]);
            }
            Redirect::to('/admin/trust-badges');
        }
        if ($action === 'move_down' && $id) {
            $badge = Database::selectOne("SELECT * FROM trust_badges WHERE id = ?", [$id]);
            $maxOrder = Database::selectOne("SELECT MAX(sort_order) as m FROM trust_badges")['m'] ?? 1;
            if ($badge && $badge['sort_order'] < $maxOrder) {
                $next = Database::selectOne("SELECT * FROM trust_badges WHERE sort_order = ?", [$badge['sort_order'] + 1]);
                if ($next) Database::update('trust_badges', ['sort_order' => $badge['sort_order']], 'id = ?', [$next['id']]);
                Database::update('trust_badges', ['sort_order' => $badge['sort_order'] + 1], 'id = ?', [$id]);
            }
            Redirect::to('/admin/trust-badges');
        }
        if ($action === 'edit' && $id) {
            $editBadge = Database::selectOne("SELECT * FROM trust_badges WHERE id = ?", [$id]);
        }

        $badges = Database::select("SELECT * FROM trust_badges ORDER BY sort_order ASC");
        ob_start();
        include ROOT_PATH . '/resources/views/admin/trust-badges.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    // Trust Badges - Create
    $router->post('/trust-badges', function() {
        $maxOrder = Database::selectOne("SELECT MAX(sort_order) as m FROM trust_badges")['m'] ?? 0;
        Database::insert('trust_badges', [
            'title' => Request::post('title', ''),
            'subtitle' => Request::post('subtitle', ''),
            'icon_name' => Request::post('icon_name', 'truck'),
            'sort_order' => (int)Request::post('sort_order', $maxOrder + 1),
            'is_active' => Request::post('is_active') ? 1 : 0,
        ]);
        Session::flash('success', 'Trust badge created successfully');
        Redirect::to('/admin/trust-badges');
    });

    // Trust Badges - Edit
    $router->post('/trust-badges/edit/{id}', function($id) {
        Database::update('trust_badges', [
            'title' => Request::post('title', ''),
            'subtitle' => Request::post('subtitle', ''),
            'icon_name' => Request::post('icon_name', 'truck'),
            'sort_order' => (int)Request::post('sort_order', 1),
            'is_active' => Request::post('is_active') ? 1 : 0,
        ], 'id = ?', [$id]);
        Session::flash('success', 'Trust badge updated successfully');
        Redirect::to('/admin/trust-badges');
    });

    // Trust Badges - Delete
    $router->post('/trust-badges/delete', function() {
        $id = (int)Request::post('id', 0);
        if ($id) Database::delete('trust_badges', 'id = ?', [$id]);
        Session::flash('success', 'Trust badge deleted');
        Redirect::to('/admin/trust-badges');
    });

    // ============================================================
    // Testimonials
    // ============================================================
    $router->get('/testimonials', function() {
        $testimonials = Database::select("SELECT * FROM testimonials ORDER BY sort_order ASC, created_at DESC");
        $breadcrumbs = [['Frontend Content', ''], ['Testimonials', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/testimonials.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    $router->post('/testimonials/store', function() {
        Database::insert('testimonials', [
            'author_name' => Request::post('author_name', ''),
            'author_title' => Request::post('author_title', ''),
            'author_photo' => Request::post('author_photo', ''),
            'rating' => (int)Request::post('rating', 5),
            'review_text' => Request::post('review_text', ''),
            'source' => Request::post('source', 'google'),
            'is_featured' => Request::post('is_featured') ? 1 : 0,
            'sort_order' => (int)Request::post('sort_order', 0),
            'is_active' => Request::post('is_active') ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        Session::flash('success', 'Testimonial added successfully');
        Redirect::to('/admin/testimonials');
    });

    $router->post('/testimonials/{id}/update', function($id) {
        Database::update('testimonials', [
            'author_name' => Request::post('author_name', ''),
            'author_title' => Request::post('author_title', ''),
            'author_photo' => Request::post('author_photo', ''),
            'rating' => (int)Request::post('rating', 5),
            'review_text' => Request::post('review_text', ''),
            'source' => Request::post('source', 'google'),
            'is_featured' => Request::post('is_featured') ? 1 : 0,
            'sort_order' => (int)Request::post('sort_order', 0),
            'is_active' => Request::post('is_active') ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);
        Session::flash('success', 'Testimonial updated successfully');
        Redirect::to('/admin/testimonials');
    });

    $router->post('/testimonials/{id}/delete', function($id) {
        Database::delete('testimonials', 'id = ?', [$id]);
        Session::flash('success', 'Testimonial deleted');
        Redirect::to('/admin/testimonials');
    });

    // Appearance Settings
    $router->get('/appearance', function() {
        $breadcrumbs = [['Frontend Content', ''], ['Appearance', '']];
        $settings = [];
        foreach (Database::select("SELECT * FROM settings WHERE group_name = 'appearance'") as $s) {
            $settings[$s['key']] = $s['value'];
        }
        ob_start();
        include ROOT_PATH . '/resources/views/admin/appearance.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    $router->post('/appearance/update', function() {
        $fields = ['hero_autoplay','hero_interval','hero_animation','show_categories','show_featured','show_new_arrivals','show_promo_banners','show_trust_badges','show_newsletter','newsletter_heading','newsletter_subheading'];
        foreach ($fields as $f) {
            $val = Request::post($f, '');
            $existing = Database::selectOne("SELECT id FROM settings WHERE `key` = ?", [$f]);
            if ($existing) {
                Database::update('settings', ['value' => $val, 'updated_at' => date('Y-m-d H:i:s')], '`key` = ?', [$f]);
            } else {
                Database::insert('settings', ['key' => $f, 'value' => $val, 'group_name' => 'appearance', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
            }
        }
        Session::flash('success', 'Appearance settings saved');
        Redirect::to('/admin/appearance');
    });

    // ============================================================
    // CMS Pages Management
    // ============================================================
    $router->get('/pages', function() {
        $pages = Database::select("SELECT * FROM pages ORDER BY sort_order ASC, title ASC");
        $breadcrumbs = [['CMS Pages', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/pages.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    $router->get('/pages/create', function() {
        $breadcrumbs = [['CMS Pages', '/admin/pages'], ['Create Page', '']];
        $page = null;
        ob_start();
        include ROOT_PATH . '/resources/views/admin/page-form.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    $router->post('/pages/store', function() {
        Database::insert('pages', [
            'slug' => preg_replace('/[^a-z0-9-]/', '-', strtolower(Request::post('slug', ''))),
            'title' => Request::post('title', ''),
            'content' => Request::post('content', ''),
            'meta_title' => Request::post('meta_title', ''),
            'meta_description' => Request::post('meta_description', ''),
            'sort_order' => (int)Request::post('sort_order', 0),
            'is_active' => Request::post('is_active') ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        Session::flash('success', 'Page created');
        Redirect::to('/admin/pages');
    });

    $router->get('/pages/{id}/edit', function($id) {
        $page = Database::selectOne("SELECT * FROM pages WHERE id = ?", [$id]);
        if (!$page) Redirect::to('/admin/pages');
        $breadcrumbs = [['CMS Pages', '/admin/pages'], [e($page['title']), '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/page-form.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    $router->post('/pages/{id}/update', function($id) {
        Database::update('pages', [
            'title' => Request::post('title', ''),
            'content' => Request::post('content', ''),
            'meta_title' => Request::post('meta_title', ''),
            'meta_description' => Request::post('meta_description', ''),
            'sort_order' => (int)Request::post('sort_order', 0),
            'is_active' => Request::post('is_active') ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);
        Session::flash('success', 'Page updated');
        Redirect::to('/admin/pages');
    });

    $router->post('/pages/{id}/delete', function($id) {
        Database::delete('pages', 'id = ?', [$id]);
        Session::flash('success', 'Page deleted');
        Redirect::to('/admin/pages');
    });

    // ============================================================
    // Shipping Zones
    // ============================================================
    $router->get('/shipping', function() {
        $zones = Database::select("SELECT * FROM shipping_zones ORDER BY sort_order ASC");
        $breadcrumbs = [['Shipping Zones', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/shipping.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    $router->post('/shipping/store', function() {
        Database::insert('shipping_zones', [
            'name' => Request::post('name', ''),
            'region' => Request::post('region', ''),
            'base_fee' => (float)Request::post('base_fee', 0),
            'free_above' => (float)Request::post('free_above', 0),
            'is_active' => Request::post('is_active') ? 1 : 0,
            'sort_order' => (int)Request::post('sort_order', 0),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        Session::flash('success', 'Shipping zone created');
        Redirect::to('/admin/shipping');
    });

    $router->post('/shipping/{id}/update', function($id) {
        Database::update('shipping_zones', [
            'name' => Request::post('name', ''),
            'region' => Request::post('region', ''),
            'base_fee' => (float)Request::post('base_fee', 0),
            'free_above' => (float)Request::post('free_above', 0),
            'is_active' => Request::post('is_active') ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);
        Session::flash('success', 'Shipping zone updated');
        Redirect::to('/admin/shipping');
    });

    $router->post('/shipping/{id}/delete', function($id) {
        Database::delete('shipping_zones', 'id = ?', [$id]);
        Session::flash('success', 'Shipping zone deleted');
        Redirect::to('/admin/shipping');
    });

    // ============================================================
    // Social Media Settings
    // ============================================================
    $router->get('/social-media', function() {
        $social = [];
        foreach (Database::select("SELECT * FROM settings WHERE group_name = 'social'") as $s) {
            $social[$s['key']] = $s['value'];
        }
        $breadcrumbs = [['Social Media', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/social-media.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    $router->post('/social-media/update', function() {
        $fields = ['social_facebook', 'social_twitter', 'social_instagram', 'social_youtube', 'social_tiktok'];
        foreach ($fields as $f) {
            $val = Request::post($f, '');
            Database::update('settings', ['value' => $val, 'updated_at' => date('Y-m-d H:i:s')], '`key` = ?', [$f]);
        }
        Session::flash('success', 'Social media links updated');
        Redirect::to('/admin/social-media');
    });

    // ============================================================
    // Newsletter Management
    // ============================================================

    // Newsletter Subscribers - List
    $router->get('/newsletter/subscribers', function() {
        $breadcrumbs = [['Marketing', ''], ['Newsletter Subscribers', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/newsletter-subscribers.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    // Newsletter Subscribers - Toggle active/inactive
    $router->post('/newsletter/subscribers/toggle', function() {
        $id = (int)Request::post('id', 0);
        if ($id) {
            $sub = Database::selectOne("SELECT * FROM newsletter_subscribers WHERE id = ?", [$id]);
            if ($sub) {
                Database::update('newsletter_subscribers', ['is_active' => $sub['is_active'] ? 0 : 1], 'id = ?', [$id]);
                echo json_encode(['success' => true, 'active' => !$sub['is_active']]);
                return;
            }
        }
        echo json_encode(['success' => false]);
    });

    // Newsletter Subscribers - Delete single
    $router->post('/newsletter/subscribers/delete', function() {
        $id = (int)Request::post('id', 0);
        if ($id) Database::delete('newsletter_subscribers', 'id = ?', [$id]);
        Session::flash('success', 'Subscriber deleted');
        Redirect::back();
    });

    // Newsletter Subscribers - Bulk delete
    $router->post('/newsletter/subscribers/bulk-delete', function() {
        $ids = array_filter(array_map('intval', explode(',', Request::post('ids', ''))));
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            Database::delete('newsletter_subscribers', "id IN ($placeholders)", $ids);
        }
        Session::flash('success', count($ids) . ' subscriber(s) deleted');
        Redirect::to('/admin/newsletter/subscribers');
    });

    // Newsletter Subscribers - Export CSV
    $router->post('/newsletter/export', function() {
        $subscribers = Database::select("SELECT email, is_active, created_at FROM newsletter_subscribers ORDER BY created_at DESC");
        $filename = 'newsletter-subscribers-' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Email', 'Status', 'Subscribed At']);
        foreach ($subscribers as $s) {
            fputcsv($output, [$s['email'], $s['is_active'] ? 'Active' : 'Inactive', $s['created_at']]);
        }
        fclose($output);
        exit;
    });

    // Newsletter - Compose Email Page
    $router->get('/newsletter/compose', function() {
        $breadcrumbs = [['Marketing', ''], ['Compose Email', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/newsletter-compose.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    // Newsletter - Send Test Email (AJAX)
    $router->post('/newsletter/send-test', function() {
        header('Content-Type: application/json');
        $to = Request::post('to', '');
        $subject = Request::post('subject', 'Test Email from ShopSmart');
        $body = Request::post('body', '<p>This is a test email from ShopSmart.</p>');
        $isHtml = (bool)Request::post('is_html', true);
        $fromName = Request::post('from_name', '');

        if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'error' => 'Invalid email address']);
            return;
        }

        $mail = Mailer::getInstance();
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->isHTML($isHtml);
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body));
        if ($fromName) {
            $mail->setFrom($mail->From, $fromName);
        }

        // Handle attachments
        if (!empty($_FILES['attachments'])) {
            $maxSize = 5 * 1024 * 1024;
            foreach ($_FILES['attachments']['name'] as $i => $name) {
                if ($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK && $_FILES['attachments']['size'][$i] <= $maxSize) {
                    $mail->addAttachment($_FILES['attachments']['tmp_name'][$i], $name);
                }
            }
        }

        try {
            $ok = $mail->send();
            echo json_encode($ok ? ['success' => true] : ['success' => false, 'error' => 'SMTP error — check your mail settings']);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $mail->ErrorInfo]);
        }
    });

    // Newsletter - Send Campaign
    $router->post('/newsletter/send', function() {
        $recipientType = Request::post('recipient_type', 'all');
        $subject = trim(Request::post('subject', ''));
        $body = trim(Request::post('body', ''));
        $isHtml = (bool)Request::post('is_html', true);
        $replyTo = Request::post('reply_to', '');
        $fromName = trim(Request::post('from_name', ''));

        if (empty($subject) || empty($body)) {
            Session::flash('error', 'Subject and message body are required.');
            Redirect::back();
        }

        // Collect recipient emails
        $emails = [];
        if ($recipientType === 'custom') {
            $raw = Request::post('custom_emails', '');
            // Split by comma, semicolon, or newline
            $parts = preg_split('/[\s,;]+/', $raw);
            foreach ($parts as $e) {
                $e = trim($e);
                if (filter_var($e, FILTER_VALIDATE_EMAIL)) $emails[] = $e;
            }
        } else {
            $subs = Database::select("SELECT email FROM newsletter_subscribers WHERE is_active = 1");
            $emails = array_column($subs, 'email');
        }

        if (empty($emails)) {
            Session::flash('error', 'No valid recipients found.');
            Redirect::back();
        }

        // Process merge tags
        $config = require ROOT_PATH . '/config/app.php';
        $storeUrl = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $body = str_replace(
            ['{store_name}', '{current_date}', '{current_year}', '{unsubscribe_url}'],
            [e($config['name']), date('F j, Y'), date('Y'), $storeUrl . '/newsletter/unsubscribe'],
            $body
        );
        $subject = str_replace(
            ['{store_name}', '{current_date}', '{current_year}'],
            [$config['name'], date('F j, Y'), date('Y')],
            $subject
        );

        // Collect attachment file paths
        $attachmentFiles = [];
        $maxSize = 5 * 1024 * 1024;
        if (!empty($_FILES['attachments']['name'])) {
            foreach ($_FILES['attachments']['name'] as $i => $name) {
                if ($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK && $_FILES['attachments']['size'][$i] <= $maxSize) {
                    $attachmentFiles[] = [
                        'path' => $_FILES['attachments']['tmp_name'][$i],
                        'name' => $name,
                    ];
                }
            }
        }

        // Send using Mailer directly to support attachments
        $result = ['sent' => 0, 'failed' => 0, 'errors' => [], 'total' => count($emails)];
        $batches = array_chunk($emails, 50);
        foreach ($batches as $batchIndex => $batch) {
            try {
                $mail = Mailer::getInstance();
                $mail->Subject = $subject;
                $mail->Body = $body;
                $mail->isHTML($isHtml);
                $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body));
                if ($replyTo) $mail->addReplyTo($replyTo);

                $batchFailed = 0;
                foreach ($batch as $email) {
                    $email = trim($email);
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $mail->addBCC($email);
                    } else {
                        $batchFailed++;
                        $result['errors'][] = "Invalid email: $email";
                    }
                }

                // Add attachments to each batch
                foreach ($attachmentFiles as $af) {
                    $mail->addAttachment($af['path'], $af['name']);
                }

                if ($mail->send()) {
                    $result['sent'] += count($batch) - $batchFailed;
                } else {
                    $result['failed'] += count($batch) - $batchFailed;
                    $result['errors'][] = "Batch " . ($batchIndex + 1) . " failed: " . $mail->ErrorInfo;
                }
                $result['failed'] += $batchFailed;
            } catch (\Exception $e) {
                $result['failed'] += count($batch);
                $result['errors'][] = "Batch " . ($batchIndex + 1) . " exception: " . $mail->ErrorInfo;
            }
            if ($batchIndex < count($batches) - 1) {
                usleep(1000000);
            }
        }

        if ($result['sent'] > 0 && empty($result['errors'])) {
            Session::flash('success', "Email sent successfully to {$result['sent']} recipient(s).");
        } elseif ($result['sent'] > 0) {
            Session::flash('success', "Sent to {$result['sent']} recipient(s). " . count($result['errors']) . " had issues.");
            if (!empty($result['errors'])) Session::flash('error', implode('<br>', array_slice($result['errors'], 0, 5)));
        } else {
            Session::flash('error', 'Failed to send emails. ' . implode('<br>', array_slice($result['errors'], 0, 3)));
        }

        Redirect::to('/admin/newsletter/subscribers');
    });

    // Newsletter - Mail Settings Page
    $router->get('/newsletter/settings', function() {
        $breadcrumbs = [['Marketing', ''], ['Mail Settings', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/newsletter-settings.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    // Newsletter - Save Mail Settings
    $router->post('/newsletter/settings', function() {
        $data = [
            'mail_host'       => Request::post('mail_host', ''),
            'mail_port'       => Request::post('mail_port', 587),
            'mail_username'   => Request::post('mail_username', ''),
            'mail_password'   => Request::post('mail_password', ''),
            'mail_encryption' => Request::post('mail_encryption', 'tls'),
            'mail_from_name'  => Request::post('mail_from_name', 'ShopSmart'),
            'mail_from_email' => Request::post('mail_from_email', ''),
        ];

        // If password field is empty, keep existing
        if (empty($data['mail_password'])) {
            $existing = Database::selectOne("SELECT value FROM settings WHERE `key` = 'mail_password'");
            if ($existing) unset($data['mail_password']);
        }

        Mailer::saveConfig($data);
        // Reset Mailer singleton so new settings take effect (already done inside saveConfig now)
        Session::flash('success', 'Mail settings saved successfully');
        Redirect::to('/admin/newsletter/settings');
    });

    // Newsletter - Test SMTP Connection (AJAX)
    $router->post('/newsletter/test-connection', function() {
        header('Content-Type: application/json');
        $toEmail = Request::post('test_email', '');
        $result = Mailer::testConnection($toEmail ?: null);
        echo json_encode($result);
    });

    // ============================================================
    // Coupons
    // ============================================================

    // Coupons List
    $router->get('/coupons', function() {
        $breadcrumbs = [['Coupons', '']];
        $coupons = Database::select("SELECT c.* FROM coupons c ORDER BY c.created_at DESC");
        $pageTitle = 'Coupons - ShopSmart Admin';
        ob_start();
        include ROOT_PATH . '/resources/views/admin/coupons.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    // Create Coupon
    $router->post('/coupons/store', function() {
        $code = strtoupper(trim(Request::post('code', '')));
        $type = Request::post('type', 'percentage');
        $value = (float)Request::post('value', 0);
        $minOrderAmount = (float)Request::post('min_order_amount', 0);
        $maxDiscountAmount = (float)Request::post('max_discount_amount', 0);
        $usageLimit = (int)Request::post('usage_limit', 0);
        $validFrom = Request::post('valid_from', '');
        $validUntil = Request::post('valid_until', '');
        $isActive = (int)Request::post('is_active', 1);

        if (empty($code)) { Session::flash('error', 'Coupon code is required.'); Redirect::back(); }
        if ($value <= 0) { Session::flash('error', 'Discount value must be greater than 0.'); Redirect::back(); }

        $existing = Database::selectOne("SELECT id FROM coupons WHERE code = ?", [$code]);
        if ($existing) { Session::flash('error', 'Coupon code already exists.'); Redirect::back(); }

        Database::insert('coupons', [
            'code' => $code,
            'type' => $type,
            'value' => $value,
            'min_order_amount' => $minOrderAmount,
            'max_discount_amount' => $maxDiscountAmount,
            'usage_limit' => $usageLimit,
            'used_count' => 0,
            'is_active' => $isActive,
            'valid_from' => $validFrom ? $validFrom . ' 00:00:00' : null,
            'valid_until' => $validUntil ? $validUntil . ' 23:59:59' : null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        Session::flash('success', "Coupon \"$code\" created successfully.");
        Redirect::to('/admin/coupons');
    });

    // Update Coupon
    $router->post('/coupons/{id}/update', function($id) {
        $coupon = Database::selectOne("SELECT * FROM coupons WHERE id = ?", [$id]);
        if (!$coupon) { Session::flash('error', 'Coupon not found.'); Redirect::to('/admin/coupons'); }

        $code = strtoupper(trim(Request::post('code', '')));
        $type = Request::post('type', 'percentage');
        $value = (float)Request::post('value', 0);
        $minOrderAmount = (float)Request::post('min_order_amount', 0);
        $maxDiscountAmount = (float)Request::post('max_discount_amount', 0);
        $usageLimit = (int)Request::post('usage_limit', 0);
        $validFrom = Request::post('valid_from', '');
        $validUntil = Request::post('valid_until', '');
        $isActive = (int)Request::post('is_active', 1);

        if (empty($code)) { Session::flash('error', 'Coupon code is required.'); Redirect::back(); }
        if ($value <= 0) { Session::flash('error', 'Discount value must be greater than 0.'); Redirect::back(); }

        $dupCheck = Database::selectOne("SELECT id FROM coupons WHERE code = ? AND id != ?", [$code, $id]);
        if ($dupCheck) { Session::flash('error', 'Coupon code already exists.'); Redirect::back(); }

        Database::update('coupons', [
            'code' => $code,
            'type' => $type,
            'value' => $value,
            'min_order_amount' => $minOrderAmount,
            'max_discount_amount' => $maxDiscountAmount,
            'usage_limit' => $usageLimit,
            'is_active' => $isActive,
            'valid_from' => $validFrom ? $validFrom . ' 00:00:00' : null,
            'valid_until' => $validUntil ? $validUntil . ' 23:59:59' : null,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::flash('success', "Coupon \"$code\" updated successfully.");
        Redirect::to('/admin/coupons');
    });

    // Delete Coupon
    $router->post('/coupons/{id}/delete', function($id) {
        $coupon = Database::selectOne("SELECT * FROM coupons WHERE id = ?", [$id]);
        if ($coupon) {
            Database::delete('coupons', 'id = ?', [$id]);
            Session::flash('success', "Coupon \"{$coupon['code']}\" deleted.");
        }
        Redirect::to('/admin/coupons');
    });

    // Toggle Coupon Active
    $router->post('/coupons/{id}/toggle', function($id) {
        $coupon = Database::selectOne("SELECT * FROM coupons WHERE id = ?", [$id]);
        if ($coupon) {
            Database::update('coupons', ['is_active' => $coupon['is_active'] ? 0 : 1, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    });

    // ─── DATABASE MIGRATION: Commissions + Scheduled Posts ─────────────
    // Auto-create commissions table and add scheduled post columns
    (function() {
        try {
            Database::query("CREATE TABLE IF NOT EXISTS `commissions` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT UNSIGNED NOT NULL,
                `order_id` INT UNSIGNED NOT NULL,
                `order_number` VARCHAR(255) NOT NULL,
                `amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `percentage` DECIMAL(5,2) NOT NULL DEFAULT 0,
                `order_total` DECIMAL(12,2) NOT NULL DEFAULT 0,
                `status` ENUM('pending','approved','paid') DEFAULT 'pending',
                `created_at` VARCHAR(255),
                `paid_at` VARCHAR(255),
                INDEX `idx_user_id` (`user_id`),
                INDEX `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (\Throwable $e) {}
        // Add ai_prompt, image_url, target_platforms columns to marketing_campaigns if missing
        try {
            $cols = Database::query("SHOW COLUMNS FROM marketing_campaigns LIKE 'ai_prompt'")->fetchAll();
            if (empty($cols)) {
                Database::query("ALTER TABLE marketing_campaigns ADD COLUMN `ai_prompt` TEXT AFTER `content`");
                Database::query("ALTER TABLE marketing_campaigns ADD COLUMN `image_url` VARCHAR(500) AFTER `ai_prompt`");
                Database::query("ALTER TABLE marketing_campaigns ADD COLUMN `target_platforms` VARCHAR(500) AFTER `image_url`");
            }
        } catch (\Throwable $e) {}
    })();

    // ─── Commission System ─────────────────────────────────────────────
    $router->get('/commissions', function() {
        $breadcrumbs = [['Commissions', '']];
        $commissions = Database::select("SELECT c.*, u.name as cashier_name, u.email FROM commissions c LEFT JOIN users u ON c.user_id = u.id ORDER BY c.created_at DESC LIMIT 100");
        $totalEarned = Database::selectOne("SELECT COALESCE(SUM(amount),0) as total FROM commissions WHERE status != 'rejected'")['total'] ?? 0;
        $totalPending = Database::selectOne("SELECT COALESCE(SUM(amount),0) as total FROM commissions WHERE status = 'pending'")['total'] ?? 0;
        $totalPaid = Database::selectOne("SELECT COALESCE(SUM(amount),0) as total FROM commissions WHERE status = 'paid'")['total'] ?? 0;
        $commissionRate = Database::selectOne("SELECT value FROM settings WHERE `key` = 'commission_percentage'")['value'] ?? '0';
        $commissionEnabled = Database::selectOne("SELECT value FROM settings WHERE `key` = 'commission_enabled'")['value'] ?? '0';

        ob_start();
        include ROOT_PATH . '/resources/views/admin/commissions.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    $router->post('/commissions/settings', function() {
        header('Content-Type: application/json');
        $enabled = Request::post('commission_enabled', '0');
        $percentage = Request::post('commission_percentage', '0');

        foreach (['commission_enabled' => $enabled, 'commission_percentage' => $percentage] as $k => $v) {
            $existing = Database::selectOne("SELECT id FROM settings WHERE `key` = ?", [$k]);
            if ($existing) {
                Database::update('settings', ['value' => $v, 'updated_at' => date('Y-m-d H:i:s')], '`key` = ?', [$k]);
            } else {
                Database::insert('settings', ['key' => $k, 'value' => $v, 'group_name' => 'commission', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
            }
        }
        echo json_encode(['success' => true]);
    });

    $router->post('/commissions/{id}/pay', function($id) {
        header('Content-Type: application/json');
        $comm = Database::selectOne("SELECT * FROM commissions WHERE id = ?", [(int)$id]);
        if (!$comm) { echo json_encode(['success' => false, 'error' => 'Commission not found']); return; }
        Database::update('commissions', ['status' => 'paid', 'paid_at' => date('Y-m-d H:i:s')], 'id = ?', [(int)$id]);
        echo json_encode(['success' => true]);
    });

    $router->post('/commissions/{id}/approve', function($id) {
        header('Content-Type: application/json');
        Database::update('commissions', ['status' => 'approved'], 'id = ?', [(int)$id]);
        echo json_encode(['success' => true]);
    });

    $router->post('/commissions/bulk-pay', function() {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        $ids = $input['ids'] ?? [];
        if (empty($ids)) { echo json_encode(['success' => false, 'error' => 'No IDs provided']); return; }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        Database::query("UPDATE commissions SET status = 'paid', paid_at = ? WHERE id IN ($placeholders)", array_merge([date('Y-m-d H:i:s')], $ids));
        echo json_encode(['success' => true, 'paid' => count($ids)]);
    });
    $router->get('/referrals', function() {
        $breadcrumbs = [['Affiliates', '']];
        $statusFilter = Request::query('status', 'all');
        $page = (int)Request::query('page', 1);
        $perPage = 20;

        // Stats
        $totalCompleted = Database::selectOne("SELECT COUNT(*) as c FROM referrals WHERE status = 'completed'")['c'] ?? 0;
        $totalCommission = Database::selectOne("SELECT COALESCE(SUM(commission_amount),0) as t FROM referrals WHERE status IN ('completed','paid')")['t'] ?? 0;
        $pendingPayouts = Database::selectOne("SELECT COUNT(*) as c FROM referrals WHERE status = 'completed'")['c'] ?? 0;
        $activeReferrers = Database::selectOne("SELECT COUNT(DISTINCT referrer_id) as c FROM referrals WHERE status IN ('completed','paid')")['c'] ?? 0;

        // Settings
        $commissionRate = Database::selectOne("SELECT value FROM settings WHERE `key` = 'referral_commission_rate'")['value'] ?? '5';
        $referralEnabled = Database::selectOne("SELECT value FROM settings WHERE `key` = 'referral_enabled'")['value'] ?? '1';

        // Referrals query with filters
        $where = "1=1";
        $params = [];
        if ($statusFilter !== 'all') {
            $where .= " AND r.status = ?";
            $params[] = $statusFilter;
        }
        $totalReferrals = Database::selectOne("SELECT COUNT(*) as c FROM referrals r WHERE {$where}", $params)['c'] ?? 0;
        $totalPages = max(1, ceil($totalReferrals / $perPage));
        $offset = ($page - 1) * $perPage;
        $referrals = Database::select("SELECT r.*, u1.name as referrer_name, u1.email as referrer_email, u2.name as referred_name, u2.email as referred_email, o.order_number FROM referrals r LEFT JOIN users u1 ON r.referrer_id = u1.id LEFT JOIN users u2 ON r.referred_id = u2.id LEFT JOIN orders o ON r.order_id = o.id WHERE {$where} ORDER BY r.created_at DESC LIMIT {$perPage} OFFSET {$offset}", $params);

        // Top referrers
        $topReferrers = Database::select("SELECT r.referrer_id, u.name, u.email, r.referral_code, COUNT(*) as total_refs, COALESCE(SUM(r.commission_amount),0) as total_earned FROM referrals r LEFT JOIN users u ON r.referrer_id = u.id WHERE r.status IN ('completed','paid') GROUP BY r.referrer_id, u.name, u.email, r.referral_code ORDER BY total_earned DESC LIMIT 10");

        $pagination = ['current' => $page, 'total' => $totalPages, 'per_page' => $perPage, 'total_items' => $totalReferrals];

        ob_start();
        include ROOT_PATH . '/resources/views/admin/referrals.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    });

    $router->post('/referrals/settings', function() {
        $upsert = function($key, $value) {
            $existing = Database::selectOne("SELECT id FROM settings WHERE `key` = ?", [$key]);
            if ($existing) Database::update('settings', ['value' => $value, 'updated_at' => date('Y-m-d H:i:s')], '`key` = ?', [$key]);
            else Database::insert('settings', ['key' => $key, 'value' => $value, 'group_name' => 'referral', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
        };
        $upsert('referral_commission_rate', Request::post('referral_commission_rate', '5'));
        $upsert('referral_enabled', Request::post('referral_enabled', '1'));
        Session::flash('success', 'Referral settings saved');
        Redirect::to('/admin/referrals');
    });

    $router->post('/referrals/{id}/pay', function($id) {
        Database::update('referrals', ['status' => 'paid'], 'id = ?', [$id]);
        // Also update commission if exists
        $ref = Database::selectOne("SELECT * FROM referrals WHERE id = ?", [$id]);
        if ($ref) {
            try { Database::update('commissions', ['status' => 'paid', 'paid_at' => date('Y-m-d H:i:s')], 'order_id = ? AND user_id = ?', [$ref['order_id'], $ref['referrer_id']]); } catch(\Throwable $e) {}
        }
        Session::flash('success', 'Commission marked as paid');
        Redirect::to('/admin/referrals');
    });
});

// ============================================================
// Website Visit Tracking (runs on every non-admin/api/static request)
// ============================================================
$trackVisit = function() {
    try {
        $uri = Request::uri();
        // Skip admin, api, static files
        if (str_starts_with($uri, '/admin') || str_starts_with($uri, '/api/') || str_starts_with($uri, '/pos') || str_contains($uri, '.')) return;

        $sessionId = session_id();
        $userId = Auth::check() ? Auth::id() : null;
        $url = $_SERVER['REQUEST_URI'] ?? '/';
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        // Simple device detection
        $deviceType = 'desktop';
        if (preg_match('/Mobile|Android.*Mobile|iPhone|iPod/i', $userAgent)) $deviceType = 'mobile';
        elseif (preg_match('/iPad|Android(?!.*Mobile)|Tablet/i', $userAgent)) $deviceType = 'tablet';

        // Simple browser detection
        $browser = 'Unknown';
        if (preg_match('/Edg\//i', $userAgent)) $browser = 'Edge';
        elseif (preg_match('/Chrome/i', $userAgent)) $browser = 'Chrome';
        elseif (preg_match('/Firefox/i', $userAgent)) $browser = 'Firefox';
        elseif (preg_match('/Safari/i', $userAgent)) $browser = 'Safari';
        elseif (preg_match('/Opera|OPR/i', $userAgent)) $browser = 'Opera';

        // Simple OS detection
        $os = 'Unknown';
        if (preg_match('/Windows/i', $userAgent)) $os = 'Windows';
        elseif (preg_match('/Mac OS/i', $userAgent)) $os = 'macOS';
        elseif (preg_match('/Linux/i', $userAgent)) $os = 'Linux';
        elseif (preg_match('/Android/i', $userAgent)) $os = 'Android';
        elseif (preg_match('/iPhone|iPad|iPod/i', $userAgent)) $os = 'iOS';

        // Rate limit: 1 per session per URL per 60s
        $recent = Database::selectOne("SELECT id FROM page_views WHERE session_id = ? AND url = ? AND created_at > DATE_SUB(NOW(), INTERVAL 60 SECOND)", [$sessionId, $url]);
        if (!$recent) {
            Database::insert('page_views', [
                'session_id' => $sessionId, 'user_id' => $userId, 'url' => $url,
                'referrer' => $referrer, 'user_agent' => $userAgent, 'ip_address' => $ip,
                'device_type' => $deviceType, 'browser' => $browser, 'os' => $os,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
    } catch (\Throwable $e) {
        // Silently fail — never break the page
    }
};
$trackVisit();