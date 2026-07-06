<?php

class AdminCouponController extends BaseController
{
    public function index(): void
    {
        $breadcrumbs = [['Coupons', '']];
        $coupons = Database::select("SELECT c.* FROM coupons c ORDER BY c.created_at DESC");
        $pageTitle = 'Coupons - ' . getStoreName() . ' Admin';
        ob_start();
        include ROOT_PATH . '/resources/views/admin/coupons.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }

    public function store(): void
    {
        $code = strtoupper(trim(Request::post('code', '')));
        $type = Request::post('type', 'percentage');
        $value = (float)Request::post('value', 0);
        $minOrderAmount = (float)Request::post('min_order_amount', 0);
        $maxDiscountAmount = (float)Request::post('max_discount_amount', 0);
        $usageLimit = (int)Request::post('usage_limit', 0);
        $validFrom = Request::post('valid_from', '');
        $validUntil = Request::post('valid_until', '');
        $isActive = (int)Request::post('is_active', 1);

        if (empty($code)) { Session::flash('error', 'Coupon code is required.'); Redirect::back(); }
        if ($value <= 0) { Session::flash('error', 'Discount value must be greater than 0.'); Redirect::back(); }

        $existing = Database::selectOne("SELECT id FROM coupons WHERE code = ?", [$code]);
        if ($existing) { Session::flash('error', 'Coupon code already exists.'); Redirect::back(); }

        Database::insert('coupons', [
            'code' => $code,
            'type' => $type,
            'value' => $value,
            'min_order_amount' => $minOrderAmount,
            'max_discount_amount' => $maxDiscountAmount,
            'usage_limit' => $usageLimit,
            'used_count' => 0,
            'is_active' => $isActive,
            'valid_from' => $validFrom ? $validFrom . ' 00:00:00' : null,
            'valid_until' => $validUntil ? $validUntil . ' 23:59:59' : null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        Session::flash('success', "Coupon \"$code\" created successfully.");
        Redirect::to('/admin/coupons');
    }

    public function update($id): void
    {
        $coupon = Database::selectOne("SELECT * FROM coupons WHERE id = ?", [$id]);
        if (!$coupon) { Session::flash('error', 'Coupon not found.'); Redirect::to('/admin/coupons'); }

        $code = strtoupper(trim(Request::post('code', '')));
        $type = Request::post('type', 'percentage');
        $value = (float)Request::post('value', 0);
        $minOrderAmount = (float)Request::post('min_order_amount', 0);
        $maxDiscountAmount = (float)Request::post('max_discount_amount', 0);
        $usageLimit = (int)Request::post('usage_limit', 0);
        $validFrom = Request::post('valid_from', '');
        $validUntil = Request::post('valid_until', '');
        $isActive = (int)Request::post('is_active', 1);

        if (empty($code)) { Session::flash('error', 'Coupon code is required.'); Redirect::back(); }
        if ($value <= 0) { Session::flash('error', 'Discount value must be greater than 0.'); Redirect::back(); }

        $dupCheck = Database::selectOne("SELECT id FROM coupons WHERE code = ? AND id != ?", [$code, $id]);
        if ($dupCheck) { Session::flash('error', 'Coupon code already exists.'); Redirect::back(); }

        Database::update('coupons', [
            'code' => $code,
            'type' => $type,
            'value' => $value,
            'min_order_amount' => $minOrderAmount,
            'max_discount_amount' => $maxDiscountAmount,
            'usage_limit' => $usageLimit,
            'is_active' => $isActive,
            'valid_from' => $validFrom ? $validFrom . ' 00:00:00' : null,
            'valid_until' => $validUntil ? $validUntil . ' 23:59:59' : null,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::flash('success', "Coupon \"$code\" updated successfully.");
        Redirect::to('/admin/coupons');
    }

    public function delete($id): void
    {
        $coupon = Database::selectOne("SELECT * FROM coupons WHERE id = ?", [$id]);
        if ($coupon) {
            Database::delete('coupons', 'id = ?', [$id]);
            Session::flash('success', "Coupon \"{$coupon['code']}\" deleted.");
        }
        Redirect::to('/admin/coupons');
    }

    public function toggle($id): void
    {
        $coupon = Database::selectOne("SELECT * FROM coupons WHERE id = ?", [$id]);
        if ($coupon) {
            Database::update('coupons', ['is_active' => $coupon['is_active'] ? 0 : 1, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }
}