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
        $breadcrumbs = [['Customers', '/admin/customers'], [e($customer['name']), '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/customer-detail.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }
}