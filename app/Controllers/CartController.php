<?php

/**
 * Cart Controller
 *
 * Handles the shopping cart page and cart CRUD operations (add, update, remove, coupon).
 */
class CartController extends BaseController
{
    /**
     * GET /cart
     * Display the cart page with items, tax calculation, and coupon info.
     */
    public function index(): void
    {
        $cartItems = [];
        $subtotal = 0;
        if (Auth::check()) {
            $cartItems = Database::select("SELECT c.*, p.name, p.price, p.discount_price, p.slug, (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1) as image, b.name as brand_name, v.variant_name, v.price as variant_price, v.stock_quantity as variant_stock FROM cart c JOIN products p ON c.product_id = p.id LEFT JOIN brands b ON p.brand_id = b.id LEFT JOIN product_variants v ON c.variant_id = v.id WHERE c.user_id = ?", [Auth::id()]);
        } else {
            $cartItems = Database::select("SELECT c.*, p.name, p.price, p.discount_price, p.slug, (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1) as image, b.name as brand_name, v.variant_name, v.price as variant_price, v.stock_quantity as variant_stock FROM cart c JOIN products p ON c.product_id = p.id LEFT JOIN brands b ON p.brand_id = b.id LEFT JOIN product_variants v ON c.variant_id = v.id WHERE c.session_id = ?", [session_id()]);
        }
        foreach ($cartItems as &$item) {
            // Use variant price if available, else product price/discount
            if (!empty($item['variant_price'])) {
                $effectivePrice = (float)$item['variant_price'];
            } elseif (!empty($item['discount_price']) && $item['discount_price'] < $item['price']) {
                $item['original_price'] = $item['price'];
                $effectivePrice = (float)$item['discount_price'];
            } else {
                $effectivePrice = (float)$item['price'];
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

        $storeName = getStoreName();
        $pageTitle = 'Shopping Cart - ' . $storeName;
        ob_start();
        include ROOT_PATH . '/resources/views/customer/cart.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/app.php';
    }

    /**
     * POST /cart/add
     * Add a product to the cart (authenticated or guest).
     */
    public function add(): void
    {
        $productId = (int)Request::post('product_id', 0);
        $variantId = Request::post('variant_id') ? (int)Request::post('variant_id') : null;
        $qty = (int)Request::post('quantity', 1);
        if ($productId <= 0) Redirect::back();

        // Verify product is available for purchase
        $product = Database::selectOne("SELECT * FROM products WHERE id = ? AND is_active = 1 AND (product_status IS NULL OR product_status IN ('active','out_of_stock_returning'))", [$productId]);
        if (!$product || ($product['product_status'] ?? 'active') !== 'active') { Session::flash('error', 'This product is not available for purchase.'); Redirect::back(); }

        // If variant, check variant stock and get variant price
        $variant = null;
        if ($variantId) {
            $variant = Database::selectOne("SELECT * FROM product_variants WHERE id = ? AND product_id = ? AND is_active = 1", [$variantId, $productId]);
            if (!$variant || ($variant['stock_quantity'] ?? 0) <= 0) { Session::flash('error', 'This variant is out of stock.'); Redirect::back(); }
            if ($qty > $variant['stock_quantity']) $qty = $variant['stock_quantity'];
        } else {
            if (($product['stock_quantity'] ?? 0) <= 0) { Session::flash('error', 'This product is out of stock.'); Redirect::back(); }
            if ($qty > $product['stock_quantity']) $qty = $product['stock_quantity'];
        }

        // Check for existing cart item (same product + same variant)
        $variantCheck = $variantId ? " AND variant_id = $variantId" : " AND (variant_id IS NULL OR variant_id = 0)";
        $existing = Auth::check()
            ? Database::selectOne("SELECT * FROM cart WHERE user_id = ? AND product_id = ?$variantCheck", [Auth::id(), $productId])
            : Database::selectOne("SELECT * FROM cart WHERE session_id = ? AND product_id = ?$variantCheck", [session_id(), $productId]);

        if ($existing) {
            $newQty = $existing['quantity'] + $qty;
            // Cap at variant or product stock
            if ($variantId && $variant && $newQty > $variant['stock_quantity']) $newQty = $variant['stock_quantity'];
            elseif (!$variantId && $newQty > $product['stock_quantity']) $newQty = $product['stock_quantity'];
            Database::update('cart', ['quantity' => $newQty, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$existing['id']]);
        } else {
            $data = ['product_id' => $productId, 'variant_id' => $variantId, 'quantity' => $qty, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')];
            if (Auth::check()) $data['user_id'] = Auth::id();
            else $data['session_id'] = session_id();
            Database::insert('cart', $data);
        }
        Session::flash('success', 'Product added to cart!');
        Redirect::to('/cart');
    }

    /**
     * POST /cart/update
     * Update cart item quantities (or remove if qty <= 0).
     */
    public function update(): void
    {
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
    }

    /**
     * POST /cart/remove/{id}
     * Remove a single cart item.
     */
    public function remove(int $id): void
    {
        Database::delete('cart', 'id = ?', [$id]);
        Redirect::to('/cart');
    }

    /**
     * POST /cart/coupon
     * Apply or remove a coupon code.
     */
    public function coupon(): void
    {
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
    }
}