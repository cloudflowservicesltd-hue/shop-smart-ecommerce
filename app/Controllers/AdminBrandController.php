<?php

class AdminBrandController extends BaseController
{
    public function index()
    {
        $breadcrumbs = [['Brands', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/brands.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }

    public function store()
    {
        $data = [
            'name' => Request::post('name', ''),
            'slug' => Request::post('slug', ''),
            'description' => Request::post('description', ''),
            'is_active' => Request::post('is_active') ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $logo = FileUpload::handle('logo', 'brands');
        if ($logo) $data['logo'] = $logo;
        Database::insert('brands', $data);
        Session::flash('success', 'Brand created');
        Redirect::to('/admin/brands');
    }

    public function delete($id)
    {
        Database::delete('brands', 'id = ?', [$id]);
        Session::flash('success', 'Brand deleted');
        Redirect::to('/admin/brands');
    }

    public function bulkDelete()
    {
        $ids = Request::post('ids', '');
        if (empty($ids)) {
            Session::flash('error', 'No brands selected');
            Redirect::to('/admin/brands');
            return;
        }
        $idList = array_filter(array_map('intval', explode(',', $ids)));
        if (empty($idList)) {
            Session::flash('error', 'Invalid selection');
            Redirect::to('/admin/brands');
            return;
        }
        $placeholders = implode(',', array_fill(0, count($idList), '?'));
        Database::delete('brands', "id IN ({$placeholders})", $idList);
        Session::flash('success', count($idList) . ' brands deleted');
        Redirect::to('/admin/brands');
    }

    public function update($id)
    {
        $data = [
            'name' => Request::post('name', ''),
            'slug' => Request::post('slug', ''),
            'description' => Request::post('description', ''),
            'is_active' => Request::post('is_active') ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $logo = FileUpload::handle('logo', 'brands');
        if ($logo) $data['logo'] = $logo;
        Database::update('brands', $data, 'id = ?', [$id]);
        Session::flash('success', 'Brand updated');
        Redirect::to('/admin/brands');
    }
}