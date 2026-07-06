<?php

class AdminPageController extends BaseController
{
    public function index(): void
    {
        $pages = Database::select("SELECT * FROM pages ORDER BY sort_order ASC, title ASC");
        $breadcrumbs = [['CMS Pages', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/pages.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }

    public function create(): void
    {
        $breadcrumbs = [['CMS Pages', '/admin/pages'], ['Create Page', '']];
        $page = null;
        ob_start();
        include ROOT_PATH . '/resources/views/admin/page-form.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }

    public function store(): void
    {
        Database::insert('pages', [
            'slug' => preg_replace('/[^a-z0-9-]/', '-', strtolower(Request::post('slug', ''))),
            'title' => Request::post('title', ''),
            'content' => Request::post('content', ''),
            'meta_title' => Request::post('meta_title', ''),
            'meta_description' => Request::post('meta_description', ''),
            'sort_order' => (int)Request::post('sort_order', 0),
            'is_active' => Request::post('is_active') ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        Session::flash('success', 'Page created');
        Redirect::to('/admin/pages');
    }

    public function edit($id): void
    {
        $page = Database::selectOne("SELECT * FROM pages WHERE id = ?", [$id]);
        if (!$page) Redirect::to('/admin/pages');
        $breadcrumbs = [['CMS Pages', '/admin/pages'], [e($page['title']), '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/page-form.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }

    public function update($id): void
    {
        Database::update('pages', [
            'title' => Request::post('title', ''),
            'content' => Request::post('content', ''),
            'meta_title' => Request::post('meta_title', ''),
            'meta_description' => Request::post('meta_description', ''),
            'sort_order' => (int)Request::post('sort_order', 0),
            'is_active' => Request::post('is_active') ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);
        Session::flash('success', 'Page updated');
        Redirect::to('/admin/pages');
    }

    public function delete($id): void
    {
        Database::delete('pages', 'id = ?', [$id]);
        Session::flash('success', 'Page deleted');
        Redirect::to('/admin/pages');
    }
}