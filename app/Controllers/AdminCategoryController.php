<?php

class AdminCategoryController extends BaseController
{
    public function index()
    {
        $breadcrumbs = [['Categories', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/categories.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }

    public function store()
    {
        $data = [
            'name' => Request::post('name', ''),
            'slug' => Request::post('slug', ''),
            'description' => Request::post('description', ''),
            'parent_id' => Request::post('parent_id') ?: null,
            'is_active' => Request::post('is_active') ? 1 : 0,
            'sort_order' => (int)Request::post('sort_order', 0),
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ];
        $image = FileUpload::handle('image', 'categories');
        if ($image) $data['image'] = $image;
        Database::insert('categories', $data);
        Session::flash('success', 'Category created');
        Redirect::to('/admin/categories');
    }

    public function update($id)
    {
        $data = [
            'name' => Request::post('name', ''),
            'slug' => Request::post('slug', ''),
            'description' => Request::post('description', ''),
            'parent_id' => Request::post('parent_id') ?: null,
            'is_active' => Request::post('is_active') ? 1 : 0,
            'sort_order' => (int)Request::post('sort_order', 0),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $image = FileUpload::handle('image', 'categories');
        if ($image) $data['image'] = $image;
        Database::update('categories', $data, 'id = ?', [$id]);
        Session::flash('success', 'Category updated');
        Redirect::to('/admin/categories');
    }

    public function delete($id)
    {
        Database::delete('categories', 'id = ?', [$id]);
        Session::flash('success', 'Category deleted');
        Redirect::to('/admin/categories');
    }

    public function bulkDelete()
    {
        $ids = Request::post('ids', '');
        if (empty($ids)) {
            Session::flash('error', 'No categories selected');
            Redirect::to('/admin/categories');
            return;
        }
        $idList = array_filter(array_map('intval', explode(',', $ids)));
        if (empty($idList)) {
            Session::flash('error', 'Invalid selection');
            Redirect::to('/admin/categories');
            return;
        }
        $placeholders = implode(',', array_fill(0, count($idList), '?'));
        Database::delete('categories', "id IN ({$placeholders})", $idList);
        Session::flash('success', count($idList) . ' categories deleted');
        Redirect::to('/admin/categories');
    }
}