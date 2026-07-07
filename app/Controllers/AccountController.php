<?php

/**
 * Account Controller
 *
 * Handles customer account routes: dashboard, orders, wishlist, profile, reviews, addresses, change password.
 */
class AccountController extends BaseController
{
    /**
     * Customer dashboard.
     * Route: GET /account
     */
    public function dashboard(): void
    {
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
    }

    /**
     * Customer referral earnings & withdrawal page.
     * Route: GET /account/referral
     */
    public function referralPage(): void
    {
        if (!Auth::check()) Redirect::to('/login');
        $userId = Auth::id();

        // Referral link
        $myReferral = Database::selectOne("SELECT * FROM referrals WHERE referrer_id = ? AND referred_id IS NULL LIMIT 1", [$userId]);
        $myReferralLink = '';
        if ($myReferral) {
            $myReferralLink = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/?ref=' . urlencode($myReferral['referral_code']);
        }

        // Stats
        $myReferralStats = Database::selectOne("SELECT COUNT(*) as total_refs, COALESCE(SUM(commission_amount),0) as total_earned, COALESCE(SUM(CASE WHEN status = 'paid' THEN commission_amount ELSE 0 END),0) as total_paid FROM referrals WHERE referrer_id = ? AND referred_id IS NOT NULL", [$userId]);
        $earned = (float)(Database::selectOne("SELECT COALESCE(SUM(amount),0) as total FROM commissions WHERE user_id = ? AND status = 'paid'", [$userId])['total'] ?? 0);
        $pendingW = (float)(Database::selectOne("SELECT COALESCE(SUM(amount),0) as total FROM referral_withdrawals WHERE user_id = ? AND status IN ('pending','approved')", [$userId])['total'] ?? 0);
        $commissionBalance = max(0, $earned - $pendingW);

        // Withdrawal history
        $withdrawals = Database::select("SELECT * FROM referral_withdrawals WHERE user_id = ? ORDER BY created_at DESC", [$userId]);

        // Referral list (people referred)
        $referredUsers = Database::select("SELECT r.*, u.name as referred_name, u.email as referred_email, o.order_number, o.total as order_total FROM referrals r LEFT JOIN users u ON r.referred_id = u.id LEFT JOIN orders o ON o.referral_code = r.referral_code AND o.customer_id = r.referred_id WHERE r.referrer_id = ? AND r.referred_id IS NOT NULL ORDER BY r.created_at DESC", [$userId]);

        ob_start();
        include ROOT_PATH . '/resources/views/customer/account-referral.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/app.php';
    }

    /**
     * Customer orders list (paginated).
     * Route: GET /account/orders
     */
    public function orders(): void
    {
        if (!Auth::check()) Redirect::to('/login');
        $page = (int)Request::query('page', 1);
        $paginated = Database::paginate('orders', $page, 15, 'customer_id = ?', [Auth::id()], 'created_at DESC');
        $orders = $paginated['data'];
        $pagination = $paginated;
        ob_start();
        include ROOT_PATH . '/resources/views/customer/orders.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/app.php';
    }

    /**
     * Track a specific order (authenticated, by order ID).
     * Route: GET /account/orders/{id}/track
     */
    public function trackOrder($id): void
    {
        if (!Auth::check()) Redirect::to('/login');
        $order = Database::selectOne("SELECT * FROM orders WHERE id = ? AND customer_id = ?", [$id, Auth::id()]);
        if (!$order) { http_response_code(404); View::render('errors/404'); return; }
        $items = Database::select("SELECT * FROM order_items WHERE order_id = ?", [$id]);
        ob_start();
        include ROOT_PATH . '/resources/views/customer/order-tracking.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/app.php';
    }

    /**
     * Customer wishlist page.
     * Route: GET /account/wishlist
     */
    public function wishlist(): void
    {
        if (!Auth::check()) Redirect::to('/login');
        $wishlist = Database::select("SELECT w.*, p.name, p.price, p.discount_price, p.slug, p.stock_quantity, (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1) as image FROM wishlists w JOIN products p ON w.product_id = p.id WHERE w.user_id = ?", [Auth::id()]);
        ob_start();
        include ROOT_PATH . '/resources/views/customer/wishlist.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/app.php';
    }

    /**
     * Toggle wishlist item (AJAX, returns JSON).
     * Route: POST /wishlist/toggle/{productId}
     */
    public function toggleWishlist($productId): void
    {
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
    }

    /**
     * Remove wishlist item (form POST from wishlist page).
     * Route: POST /wishlist/remove
     */
    public function removeWishlist(): void
    {
        if (!Auth::check()) Redirect::to('/login');
        $productId = (int)Request::post('product_id', 0);
        if ($productId) {
            Database::delete('wishlists', 'user_id = ? AND product_id = ?', [Auth::id(), $productId]);
        }
        Session::flash('success', 'Removed from wishlist');
        Redirect::back();
    }

    /**
     * Public order tracking by order number (no login required).
     * Route: GET /orders/{orderNumber}/track
     */
    public function trackOrderByNumber($orderNumber): void
    {
        $order = Database::selectOne("SELECT * FROM orders WHERE order_number = ?", [$orderNumber]);
        if (!$order) { http_response_code(404); View::render('errors/404'); return; }
        $orderItems = Database::select("SELECT oi.*, (SELECT image_path FROM product_images WHERE product_id = oi.product_id AND is_primary = 1) as image FROM order_items oi WHERE oi.order_id = ?", [$order['id']]);
        $pageTitle = 'Track Order ' . e($order['order_number']);
        ob_start();
        include ROOT_PATH . '/resources/views/customer/order-tracking.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/app.php';
    }

    /**
     * Show profile edit page.
     * Route: GET /account/profile
     */
    public function profile(): void
    {
        if (!Auth::check()) Redirect::to('/login');
        $user = Auth::user();
        $pageTitle = 'Edit Profile - ' . getStoreName();
        ob_start();
        include ROOT_PATH . '/resources/views/customer/account-profile.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/app.php';
    }

    /**
     * Update profile.
     * Route: POST /account/profile
     */
    public function updateProfile(): void
    {
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
    }

    /**
     * Customer reviews list.
     * Route: GET /account/reviews
     */
    public function reviews(): void
    {
        if (!Auth::check()) Redirect::to('/login');
        $reviews = Database::select("SELECT r.*, p.name as product_name, p.slug as product_slug, (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1) as product_image FROM reviews r JOIN products p ON r.product_id = p.id WHERE r.user_id = ? ORDER BY r.created_at DESC", [Auth::id()]);
        $pageTitle = 'My Reviews - ' . getStoreName();
        ob_start();
        include ROOT_PATH . '/resources/views/customer/account-reviews.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/app.php';
    }

    /**
     * Submit a product review.
     * Route: POST /reviews/submit
     */
    public function submitReview(): void
    {
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
    }

    /**
     * Delete own review.
     * Route: POST /account/reviews/{id}/delete
     */
    public function deleteReview($id): void
    {
        if (!Auth::check()) Redirect::to('/login');
        $review = Database::selectOne("SELECT * FROM reviews WHERE id = ? AND user_id = ?", [$id, Auth::id()]);
        if ($review) {
            Database::delete('reviews', 'id = ?', [$id]);
            Session::flash('success', 'Review deleted');
        }
        Redirect::to('/account/reviews');
    }

    /**
     * Customer addresses page.
     * Route: GET /account/addresses
     */
    public function addresses(): void
    {
        if (!Auth::check()) Redirect::to('/login');
        $user = Auth::user();
        $pageTitle = 'My Addresses - ' . getStoreName();
        ob_start();
        include ROOT_PATH . '/resources/views/customer/account-addresses.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/app.php';
    }

    /**
     * Store/update address.
     * Route: POST /account/addresses
     */
    public function storeAddress(): void
    {
        if (!Auth::check()) Redirect::to('/login');
        Database::update('users', [
            'address' => Request::post('address', ''),
            'city' => Request::post('city', ''),
            'phone' => Request::post('phone', ''),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [Auth::id()]);
        Session::flash('success', 'Address updated');
        Redirect::to('/account/addresses');
    }

    /**
     * Show change password page.
     * Route: GET /account/change-password
     */
    public function showChangePassword(): void
    {
        if (!Auth::check()) Redirect::to('/login');
        ob_start();
        include ROOT_PATH . '/resources/views/customer/change-password.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/app.php';
    }

    /**
     * Process password change.
     * Route: POST /account/change-password
     */
    public function changePassword(): void
    {
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
    }
}