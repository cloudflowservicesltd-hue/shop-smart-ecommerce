<?php

class AdminReferralController extends BaseController
{
    public function index(): void
    {
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
    }

    public function settings(): void
    {
        $upsert = function($key, $value) {
            $existing = Database::selectOne("SELECT id FROM settings WHERE `key` = ?", [$key]);
            if ($existing) Database::update('settings', ['value' => $value, 'updated_at' => date('Y-m-d H:i:s')], '`key` = ?', [$key]);
            else Database::insert('settings', ['key' => $key, 'value' => $value, 'group_name' => 'referral', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
        };
        $upsert('referral_commission_rate', Request::post('referral_commission_rate', '5'));
        $upsert('referral_enabled', Request::post('referral_enabled', '1'));
        Session::flash('success', 'Referral settings saved');
        Redirect::to('/admin/referrals');
    }

    public function pay($id): void
    {
        Database::update('referrals', ['status' => 'paid'], 'id = ?', [$id]);
        // Also update commission if exists
        $ref = Database::selectOne("SELECT * FROM referrals WHERE id = ?", [$id]);
        if ($ref) {
            try { Database::update('commissions', ['status' => 'paid', 'paid_at' => date('Y-m-d H:i:s')], 'order_id = ? AND user_id = ?', [$ref['order_id'], $ref['referrer_id']]); } catch(\Throwable $e) {}
        }
        Session::flash('success', 'Commission marked as paid');
        Redirect::to('/admin/referrals');
    }
}