<?php

class AdminPromoBannerController extends BaseController
{
    public function index(): void
    {
        $breadcrumbs = [['Frontend Content', ''], ['Promo Banners', '']];
        $banners = Database::select("SELECT * FROM promo_banners ORDER BY position ASC");
        $editBanner = null;

        $action = Request::query('action', '');
        $id = (int)Request::query('id', 0);
        if ($action === 'edit' && $id) {
            $editBanner = Database::selectOne("SELECT * FROM promo_banners WHERE id = ?", [$id]);
            $banners = Database::select("SELECT * FROM promo_banners ORDER BY position ASC");
        }

        ob_start();
        include ROOT_PATH . '/resources/views/admin/promo-banners.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }

    public function store(): void
    {
        $maxPos = Database::selectOne("SELECT MAX(position) as m FROM promo_banners")['m'] ?? 0;
        $imageUrl = FileUpload::handle('image', 'banners') ?? Request::post('image_url', '');
        Database::insert('promo_banners', [
            'title' => Request::post('title', ''),
            'subtitle' => Request::post('subtitle', ''),
            'cta_text' => Request::post('cta_text', 'Shop Now'),
            'cta_link' => Request::post('cta_link', '/products'),
            'bg_gradient' => Request::post('bg_gradient', 'from-amber-500 to-orange-600'),
            'icon' => Request::post('icon', 'zap'),
            'image_url' => $imageUrl,
            'position' => (int)Request::post('position', $maxPos + 1),
            'is_active' => Request::post('is_active') ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        Session::flash('success', 'Promo banner created successfully');
        Redirect::to('/admin/promo-banners');
    }

    public function edit($id): void
    {
        $imageUrl = FileUpload::handle('image', 'banners');
        $data = [
            'title' => Request::post('title', ''),
            'subtitle' => Request::post('subtitle', ''),
            'cta_text' => Request::post('cta_text', 'Shop Now'),
            'cta_link' => Request::post('cta_link', '/products'),
            'bg_gradient' => Request::post('bg_gradient', 'from-amber-500 to-orange-600'),
            'icon' => Request::post('icon', 'zap'),
            'position' => (int)Request::post('position', 1),
            'is_active' => Request::post('is_active') ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($imageUrl) {
            // Delete old image if new one uploaded
            $old = Database::selectOne("SELECT image_url FROM promo_banners WHERE id = ?", [$id]);
            if ($old && $old['image_url']) FileUpload::delete($old['image_url']);
            $data['image_url'] = $imageUrl;
        }
        if (Request::post('remove_image') == '1') {
            $old = Database::selectOne("SELECT image_url FROM promo_banners WHERE id = ?", [$id]);
            if ($old && $old['image_url']) FileUpload::delete($old['image_url']);
            $data['image_url'] = null;
        }
        Database::update('promo_banners', $data, 'id = ?', [$id]);
        Session::flash('success', 'Promo banner updated successfully');
        Redirect::to('/admin/promo-banners');
    }

    public function storeNew(): void
    {
        $this->store();
    }

    public function update($id): void
    {
        $this->edit($id);
    }

    public function delete($id): void
    {
        $id = (int)$id;
        if ($id) {
            $old = Database::selectOne("SELECT image_url FROM promo_banners WHERE id = ?", [$id]);
            if ($old && $old['image_url']) FileUpload::delete($old['image_url']);
            Database::delete('promo_banners', 'id = ?', [$id]);
        }
        Session::flash('success', 'Promo banner deleted');
        Redirect::to('/admin/promo-banners');
    }

    public function deleteSelected(): void
    {
        $id = (int)Request::post('id', 0);
        $this->delete($id);
    }
}