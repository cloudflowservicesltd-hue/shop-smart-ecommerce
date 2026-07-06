<?php

class AdminCommissionController extends BaseController
{
    public function index(): void
    {
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
    }

    public function settings(): void
    {
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
    }

    public function pay($id): void
    {
        header('Content-Type: application/json');
        $comm = Database::selectOne("SELECT * FROM commissions WHERE id = ?", [(int)$id]);
        if (!$comm) { echo json_encode(['success' => false, 'error' => 'Commission not found']); return; }
        Database::update('commissions', ['status' => 'paid', 'paid_at' => date('Y-m-d H:i:s')], 'id = ?', [(int)$id]);
        echo json_encode(['success' => true]);
    }

    public function approve($id): void
    {
        header('Content-Type: application/json');
        Database::update('commissions', ['status' => 'approved'], 'id = ?', [(int)$id]);
        echo json_encode(['success' => true]);
    }

    public function bulkPay(): void
    {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        $ids = $input['ids'] ?? [];
        if (empty($ids)) { echo json_encode(['success' => false, 'error' => 'No IDs provided']); return; }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        Database::query("UPDATE commissions SET status = 'paid', paid_at = ? WHERE id IN ($placeholders)", array_merge([date('Y-m-d H:i:s')], $ids));
        echo json_encode(['success' => true, 'paid' => count($ids)]);
    }
}