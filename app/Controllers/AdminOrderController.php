<?php

class AdminOrderController extends BaseController
{
    public function index()
    {
        $breadcrumbs = [['Orders', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/orders.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }

    public function show($id)
    {
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
    }

    public function updateStatus($id)
    {
        $newStatus = Request::post('status', '');
        $update = ['status' => $newStatus, 'updated_at' => date('Y-m-d H:i:s')];
        if (in_array($newStatus, ['paid', 'completed', 'delivered'])) {
            $update['payment_status'] = 'paid';
        }
        Database::update('orders', $update, 'id = ?', [$id]);
        // Process referral commission when admin marks order as paid
        if (in_array($newStatus, ['paid', 'completed', 'delivered'])) {
            $this->processReferralCommission((int)$id);
        }
        Session::flash('success', 'Order status updated to ' . ucfirst($newStatus));
        Redirect::to('/admin/orders/' . $id);
    }

    public function updateNotes($id)
    {
        Database::update('orders', [
            'notes' => Request::post('notes', ''),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);
        Session::flash('success', 'Order notes updated');
        Redirect::to('/admin/orders/' . $id);
    }

    public function bulkDelete()
    {
        $ids = Request::post('ids', '');
        if (empty($ids)) {
            Session::flash('error', 'No orders selected');
            Redirect::to('/admin/orders');
            return;
        }
        $idList = array_filter(array_map('intval', explode(',', $ids)));
        if (empty($idList)) {
            Session::flash('error', 'Invalid selection');
            Redirect::to('/admin/orders');
            return;
        }
        $placeholders = implode(',', array_fill(0, count($idList), '?'));
        // Delete order items first, then orders
        Database::delete('order_items', "order_id IN ({$placeholders})", $idList);
        Database::delete('orders', "id IN ({$placeholders})", $idList);
        Session::flash('success', count($idList) . ' orders deleted');
        Redirect::to('/admin/orders');
    }

    public function posSales()
    {
        $breadcrumbs = [['POS Sales', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/pos-sales.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }
}