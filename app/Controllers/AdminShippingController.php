<?php

class AdminShippingController extends BaseController
{
    public function index(): void
    {
        $zones = Database::select("SELECT * FROM shipping_zones ORDER BY sort_order ASC");
        $cities = Database::select("SELECT * FROM shipping_cities ORDER BY sort_order ASC, name ASC");
        $breadcrumbs = [['Shipping Zones', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/shipping.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }

    public function store(): void
    {
        Database::insert('shipping_zones', [
            'name' => Request::post('name', ''),
            'region' => Request::post('region', ''),
            'base_fee' => (float)Request::post('base_fee', 0),
            'free_above' => (float)Request::post('free_above', 0),
            'is_active' => Request::post('is_active') ? 1 : 0,
            'sort_order' => (int)Request::post('sort_order', 0),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        Session::flash('success', 'Shipping zone created');
        Redirect::to('/admin/shipping');
    }

    public function update($id): void
    {
        Database::update('shipping_zones', [
            'name' => Request::post('name', ''),
            'region' => Request::post('region', ''),
            'base_fee' => (float)Request::post('base_fee', 0),
            'free_above' => (float)Request::post('free_above', 0),
            'is_active' => Request::post('is_active') ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);
        Session::flash('success', 'Shipping zone updated');
        Redirect::to('/admin/shipping');
    }

    public function delete($id): void
    {
        Database::delete('shipping_zones', 'id = ?', [$id]);
        Session::flash('success', 'Shipping zone deleted');
        Redirect::to('/admin/shipping');
    }

    // --- Cities Management ---

    public function storeCity(): void
    {
        $name = trim(Request::post('name', ''));
        if (empty($name)) {
            Session::flash('error', 'City name is required');
            Redirect::to('/admin/shipping#cities');
            return;
        }
        $exists = Database::selectOne("SELECT id FROM shipping_cities WHERE name = ?", [$name]);
        if ($exists) {
            Session::flash('error', 'City already exists');
            Redirect::to('/admin/shipping#cities');
            return;
        }
        Database::insert('shipping_cities', [
            'name' => $name,
            'sort_order' => (int)Request::post('sort_order', 0),
            'is_active' => Request::post('is_active') ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        Session::flash('success', 'City added');
        Redirect::to('/admin/shipping#cities');
    }

    public function updateCity($id): void
    {
        Database::update('shipping_cities', [
            'name' => trim(Request::post('name', '')),
            'sort_order' => (int)Request::post('sort_order', 0),
            'is_active' => Request::post('is_active') ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);
        Session::flash('success', 'City updated');
        Redirect::to('/admin/shipping#cities');
    }

    public function deleteCity($id): void
    {
        Database::delete('shipping_cities', 'id = ?', [$id]);
        Session::flash('success', 'City deleted');
        Redirect::to('/admin/shipping#cities');
    }
}