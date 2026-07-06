<?php

class AdminInventoryController extends BaseController
{
    public function index()
    {
        $breadcrumbs = [['Inventory', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/inventory.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }

    public function adjust()
    {
        $productId = (int)Request::post('product_id', 0);
        $qty = (int)Request::post('quantity', 0);
        $reason = Request::post('reason', 'adjustment');
        Database::update('products', ['stock_quantity' => Database::selectOne("SELECT stock_quantity FROM products WHERE id = ?", [$productId])['stock_quantity'] + $qty], 'id = ?', [$productId]);
        Database::insert('stock_adjustments', ['product_id' => $productId, 'quantity' => $qty, 'reason' => $reason, 'adjusted_by' => Auth::id(), 'created_at' => date('Y-m-d H:i:s')]);
        Session::flash('success', 'Stock adjusted');
        Redirect::to('/admin/inventory');
    }
}