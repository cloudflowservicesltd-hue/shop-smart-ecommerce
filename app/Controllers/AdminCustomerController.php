<?php

class AdminCustomerController extends BaseController
{
    public function index()
    {
        $breadcrumbs = [['Customers', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/customers.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }

    public function show($id)
    {
        $customer = Database::selectOne("SELECT * FROM users WHERE id = ? AND role = 'customer'", [$id]);
        if (!$customer) { http_response_code(404); echo 'Customer not found'; return; }
        $orders = Database::select("SELECT o.*, (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count FROM orders o WHERE o.customer_id = ? ORDER BY o.created_at DESC LIMIT 20", [$id]);
        $stats = Database::selectOne("SELECT COUNT(*) as total, COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total ELSE 0 END), 0) as spent, COALESCE(AVG(CASE WHEN payment_status = 'paid' THEN total END), 0) as avg_order FROM orders WHERE customer_id = ?", [$id]);

        // Referral data
        $myReferral = Database::selectOne("SELECT * FROM referrals WHERE referrer_id = ? AND referred_id IS NULL LIMIT 1", [$id]);
        $referralCode = $myReferral['referral_code'] ?? '';

        // Generate referral code if not exists
        if (empty($referralCode)) {
            $referralCode = strtoupper(substr(md5($customer['email'] . time()), 0, 8));
            Database::insert('referrals', [
                'referrer_id' => $id,
                'referral_code' => $referralCode,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $referralLink = '';
        if ($referralCode) {
            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $referralLink = $protocol . '://' . $host . '/ref/' . urlencode($referralCode);
        }
        $referralStats = Database::selectOne("SELECT COUNT(*) as total_refs, COALESCE(SUM(CASE WHEN status = 'completed' OR status = 'paid' THEN 1 ELSE 0 END),0) as completed_refs, COALESCE(SUM(commission_amount),0) as total_earned, COALESCE(SUM(CASE WHEN status = 'paid' THEN commission_amount ELSE 0 END),0) as paid_out FROM referrals WHERE referrer_id = ? AND referred_id IS NOT NULL", [$id]);
        $referredBy = Database::selectOne("SELECT r.*, u.name as referrer_name, u.email as referrer_email FROM referrals r JOIN users u ON r.referrer_id = u.id WHERE r.referred_id = ? LIMIT 1", [$id]);

        // Commission balance (from commissions table)
        $earned = (float)(Database::selectOne("SELECT COALESCE(SUM(amount),0) as total FROM commissions WHERE user_id = ? AND status = 'paid'", [$id])['total'] ?? 0);
        $pendingWithdrawals = (float)(Database::selectOne("SELECT COALESCE(SUM(amount),0) as total FROM referral_withdrawals WHERE user_id = ? AND status IN ('pending','approved')", [$id])['total'] ?? 0);
        $commissionBalance = $earned - $pendingWithdrawals;

        $breadcrumbs = [['Customers', '/admin/customers'], [e($customer['name']), '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/customer-detail.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }
}