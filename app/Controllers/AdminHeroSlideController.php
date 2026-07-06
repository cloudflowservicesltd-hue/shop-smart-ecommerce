<?php

class AdminHeroSlideController extends BaseController
{
    public function index(): void
    {
        $breadcrumbs = [['Frontend Content', ''], ['Hero Slides', '']];
        $slides = Database::select("SELECT * FROM hero_slides ORDER BY sort_order ASC");

        // Handle move up/down
        $action = Request::query('action', '');
        $id = (int)Request::query('id', 0);
        if ($action === 'move_up' && $id) {
            $slide = Database::selectOne("SELECT * FROM hero_slides WHERE id = ?", [$id]);
            if ($slide && $slide['sort_order'] > 1) {
                $prev = Database::selectOne("SELECT * FROM hero_slides WHERE sort_order = ?", [$slide['sort_order'] - 1]);
                if ($prev) Database::update('hero_slides', ['sort_order' => $slide['sort_order']], 'id = ?', [$prev['id']]);
                Database::update('hero_slides', ['sort_order' => $slide['sort_order'] - 1], 'id = ?', [$id]);
            }
            Redirect::to('/admin/hero-slides');
        }
        if ($action === 'move_down' && $id) {
            $slide = Database::selectOne("SELECT * FROM hero_slides WHERE id = ?", [$id]);
            $maxOrder = Database::selectOne("SELECT MAX(sort_order) as m FROM hero_slides")['m'] ?? 1;
            if ($slide && $slide['sort_order'] < $maxOrder) {
                $next = Database::selectOne("SELECT * FROM hero_slides WHERE sort_order = ?", [$slide['sort_order'] + 1]);
                if ($next) Database::update('hero_slides', ['sort_order' => $slide['sort_order']], 'id = ?', [$next['id']]);
                Database::update('hero_slides', ['sort_order' => $slide['sort_order'] + 1], 'id = ?', [$id]);
            }
            Redirect::to('/admin/hero-slides');
        }

        $slides = Database::select("SELECT * FROM hero_slides ORDER BY sort_order ASC");
        $editSlide = null;
        ob_start();
        include ROOT_PATH . '/resources/views/admin/hero-slides.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }

    public function store(): void
    {
        $maxOrder = Database::selectOne("SELECT MAX(sort_order) as m FROM hero_slides")['m'] ?? 0;
        $imageUrl = FileUpload::handle('image', 'hero-slides') ?? Request::post('image_url', '');
        Database::insert('hero_slides', [
            'title' => Request::post('title', ''),
            'subtitle' => Request::post('subtitle', ''),
            'description' => Request::post('description', ''),
            'cta_text' => Request::post('cta_text', 'Shop Now'),
            'cta_link' => Request::post('cta_link', '/products'),
            'image_url' => $imageUrl,
            'bg_gradient' => Request::post('bg_gradient', 'from-amber-700 via-orange-800 to-red-900'),
            'text_position' => Request::post('text_position', 'left'),
            'overlay' => Request::post('overlay', 'dark'),
            'sort_order' => (int)Request::post('sort_order', $maxOrder + 1),
            'is_active' => Request::post('is_active') ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        Session::flash('success', 'Hero slide created successfully');
        Redirect::to('/admin/hero-slides');
    }

    public function edit($id): void
    {
        $imageUrl = FileUpload::handle('image', 'hero-slides');
        $data = [
            'title' => Request::post('title', ''),
            'subtitle' => Request::post('subtitle', ''),
            'description' => Request::post('description', ''),
            'cta_text' => Request::post('cta_text', 'Shop Now'),
            'cta_link' => Request::post('cta_link', '/products'),
            'bg_gradient' => Request::post('bg_gradient', 'from-amber-700 via-orange-800 to-red-900'),
            'text_position' => Request::post('text_position', 'left'),
            'overlay' => Request::post('overlay', 'dark'),
            'sort_order' => (int)Request::post('sort_order', 1),
            'is_active' => Request::post('is_active') ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        // Only update image_url if a new file was uploaded or a URL was provided
        $fallbackUrl = Request::post('image_url', '');
        if ($imageUrl) {
            $data['image_url'] = $imageUrl;
        } elseif ($fallbackUrl) {
            $data['image_url'] = $fallbackUrl;
        }
        Database::update('hero_slides', $data, 'id = ?', [$id]);
        Session::flash('success', 'Hero slide updated successfully');
        Redirect::to('/admin/hero-slides');
    }

    public function delete(): void
    {
        $id = (int)Request::post('id', 0);
        if ($id) {
            Database::delete('hero_slides', 'id = ?', [$id]);
            // Re-sort remaining
            $slides = Database::select("SELECT * FROM hero_slides ORDER BY sort_order ASC");
            foreach ($slides as $i => $s) {
                Database::update('hero_slides', ['sort_order' => $i + 1], 'id = ?', [$s['id']]);
            }
        }
        Session::flash('success', 'Hero slide deleted');
        Redirect::to('/admin/hero-slides');
    }
}