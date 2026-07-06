<?php

/**
 * Checkout Page Controller
 *
 * Handles the multi-step checkout flow (shipping → payment → review → order)
 * for customer-facing page routes. Contains shared helpers previously defined
 * as closures in routes/web.php.
 */
class CheckoutPageController extends BaseController
{
    /**
     * Shared helper: Load cart items and compute checkout summary.
     *
     * Returns [cartItems, subtotal, totalItems, tax, total, couponDiscount, shippingCost].
     * Redirects to /cart if the cart is empty (unless it's an API request).
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

        return [$cartItems, $subtotal, $totalItems, $tax, $total, $couponDiscount, $shippingCost];
    }

    /**
     * Shared helper: Create an order from the current cart.
     *
     * Reads shipping info from session, validates, creates the order + items,
     * deducts stock, clears the cart, increments coupon usage, and sends
     * a confirmation email.
     *
     * @return array{success:bool, message?:string, order_id?:int, order_number?:string, total?:float, payment_method?:string}
     */
    protected function createOrderFromCart(): array
    {
        if (!Auth::check()) return ['success' => false, 'message' => 'Please login'];
        $shipping = Session::get('checkout_shipping', []);
        if (empty($shipping)) return ['success' => false, 'message' => 'Missing shipping info'];

        [$cartItems, $subtotal, $totalItems, $taxRate, $computedTotal, $couponDiscount, $shippingCost] = $this->loadCheckoutData();

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
            $customerEmail = $shipping['email'] ?? Auth::user()['email'] ?? '';
            $customerName = $shipping['name'] ?? Auth::user()['name'] ?? 'Customer';
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
     * Shared helper: Render the checkout view for a given step.
     * Requires auth. Loads checkout data and renders customer/checkout.php.
     */
    protected function renderCheckout(string $step): void
    {
        if (!Auth::check()) { Session::flash('error', 'Please login to checkout'); Redirect::to('/login'); }
        [$cartItems, $subtotal, $totalItems, $tax, $total, $couponDiscount, $shippingCost] = $this->loadCheckoutData();
        $shipping = Session::get('checkout_shipping', []);
        $currentStep = $step;
        ob_start();
        include ROOT_PATH . '/resources/views/customer/checkout.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/app.php';
    }

    // ------------------------------------------------------------------
    // Route methods
    // ------------------------------------------------------------------

    /**
     * GET /checkout
     * Fallback — redirects to shipping step.
     */
    public function index(): void
    {
        $this->renderCheckout('shipping');
    }

    /**
     * GET /checkout/shipping
     * Show the shipping information form.
     */
    public function shipping(): void
    {
        $this->renderCheckout('shipping');
    }

    /**
     * POST /checkout/shipping
     * Validate and store shipping info in session, advance to payment.
     */
    public function storeShipping(): void
    {
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
    }

    /**
     * GET /checkout/payment
     * Show the payment method selection. Requires shipping data in session.
     */
    public function payment(): void
    {
        // Must have shipping data
        $shipping = Session::get('checkout_shipping', []);
        if (empty($shipping['name']) || empty($shipping['address'])) {
            Redirect::to('/checkout/shipping');
        }
        $this->renderCheckout('payment');
    }

    /**
     * POST /checkout/payment
     * Store the selected payment method and advance to review.
     */
    public function storePayment(): void
    {
        if (!Auth::check()) Redirect::to('/login');
        $shipping = Session::get('checkout_shipping', []);
        $shipping['payment_method'] = Request::post('payment_method', 'mpesa');
        Session::set('checkout_shipping', $shipping);
        Redirect::to('/checkout/review');
    }

    /**
     * GET /checkout/review
     * Show the order review. Requires shipping + payment method in session.
     */
    public function review(): void
    {
        $shipping = Session::get('checkout_shipping', []);
        if (empty($shipping['name']) || empty($shipping['address'])) {
            Redirect::to('/checkout/shipping');
        }
        if (empty($shipping['payment_method'])) {
            Redirect::to('/checkout/payment');
        }
        $this->renderCheckout('review');
    }

    /**
     * POST /checkout/review
     * Place the order. Creates the order, clears the cart, and redirects to success.
     */
    public function storeOrder(): void
    {
        if (!Auth::check()) Redirect::to('/login');
        $shipping = Session::get('checkout_shipping', []);
        if (empty($shipping)) Redirect::to('/checkout/shipping');

        [$cartItems, $subtotal, $totalItems, $taxRate, $computedTotal, $couponDiscount, $shippingCost] = $this->loadCheckoutData();

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
                $csRow = Database::selectOne("SELECT value FROM settings WHERE `key` = 'currency_symbol'");
                $name = $shipping['name'] ?? Auth::user()['name'] ?? 'Customer'; $orderNumber = $orderNum; $total = $total; $currencySymbol = ($csRow && !empty($csRow['value'])) ? $csRow['value'] : 'KSh';
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
    }

    /**
     * GET /order-success
     * Show the order confirmation / success page.
     */
    public function success(): void
    {
        $orderId = Session::get('last_order_id');
        $orderNum = Session::get('last_order_num');
        if (!$orderId) Redirect::to('/');
        $order = Database::selectOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
        $items = Database::select("SELECT * FROM order_items WHERE order_id = ?", [$orderId]);
        ob_start();
        include ROOT_PATH . '/resources/views/customer/order-success.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/app.php';
    }
}