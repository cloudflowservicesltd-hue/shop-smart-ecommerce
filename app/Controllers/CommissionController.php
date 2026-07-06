<?php



class CommissionController extends BaseController
{
    public function balance(): void
    {
        try {
            header('Content-Type: application/json');
            if (!$this->requireAuth()) return;
            $userId = Auth::id();
            $pending = (float)(Database::selectOne("SELECT COALESCE(SUM(amount),0) as t FROM commissions WHERE status='pending' AND user_id=?", [$userId])['t'] ?? 0);
            $paid = (float)(Database::selectOne("SELECT COALESCE(SUM(amount),0) as t FROM commissions WHERE status='paid' AND user_id=?", [$userId])['t'] ?? 0);
            $this->posJson(['success'=>true,'pending'=>$pending,'paid'=>$paid,'earned'=>$pending+$paid]);
        } catch (\Throwable $e) {
            header('Content-Type: application/json');
            $this->posJson(['success'=>true,'pending'=>0,'paid'=>0,'earned'=>0]);
        }
    }

    public function history(): void
    {
        try {
            header('Content-Type: application/json');
            if (!$this->requireAuth()) return;
            $userId = Auth::id();
            $isAdmin = Auth::isAdmin();
            $where = $isAdmin ? '1=1' : 'c.user_id = ?';
            $params = $isAdmin ? [] : [$userId];
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = 20;
            $offset = ($page - 1) * $limit;
            $total = (int)(Database::selectOne("SELECT COUNT(*) as cnt FROM commissions c WHERE $where", $params)['cnt'] ?? 0);
            $records = Database::select("SELECT c.*, u.name as cashier_name FROM commissions c LEFT JOIN users u ON c.user_id = u.id WHERE $where ORDER BY c.created_at DESC LIMIT $limit OFFSET $offset", $params);
            $this->posJson(['success' => true, 'commissions' => $records, 'total' => $total, 'page' => $page, 'pages' => max(1, ceil($total / $limit))]);
        } catch (\Throwable $e) {
            http_response_code(500);
            $this->posJson(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function pay(int $id): void
    {
        try {
            header('Content-Type: application/json');
            if (!Auth::check() || !Auth::isAdmin()) {
                http_response_code(403);
                $this->posJson(['error' => 'Access denied']);
                return;
            }
            Database::update('commissions', ['status' => 'paid', 'paid_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
            $this->posJson(['success' => true, 'message' => 'Commission marked as paid']);
        } catch (\Throwable $e) {
            http_response_code(500);
            $this->posJson(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}