<?php

class AdminTrustBadgeController extends BaseController
{
    public function index(): void
    {
        $breadcrumbs = [['Frontend Content', ''], ['Trust Badges', '']];
        $badges = Database::select("SELECT * FROM trust_badges ORDER BY sort_order ASC");
        $editBadge = null;

        $action = Request::query('action', '');
        $id = (int)Request::query('id', 0);

        if ($action === 'move_up' && $id) {
            $badge = Database::selectOne("SELECT * FROM trust_badges WHERE id = ?", [$id]);
            if ($badge && $badge['sort_order'] > 1) {
                $prev = Database::selectOne("SELECT * FROM trust_badges WHERE sort_order = ?", [$badge['sort_order'] - 1]);
                if ($prev) Database::update('trust_badges', ['sort_order' => $badge['sort_order']], 'id = ?', [$prev['id']]);
                Database::update('trust_badges', ['sort_order' => $badge['sort_order'] - 1], 'id = ?', [$id]);
            }
            Redirect::to('/admin/trust-badges');
        }
        if ($action === 'move_down' && $id) {
            $badge = Database::selectOne("SELECT * FROM trust_badges WHERE id = ?", [$id]);
            $maxOrder = Database::selectOne("SELECT MAX(sort_order) as m FROM trust_badges")['m'] ?? 1;
            if ($badge && $badge['sort_order'] < $maxOrder) {
                $next = Database::selectOne("SELECT * FROM trust_badges WHERE sort_order = ?", [$badge['sort_order'] + 1]);
                if ($next) Database::update('trust_badges', ['sort_order' => $badge['sort_order']], 'id = ?', [$next['id']]);
                Database::update('trust_badges', ['sort_order' => $badge['sort_order'] + 1], 'id = ?', [$id]);
            }
            Redirect::to('/admin/trust-badges');
        }
        if ($action === 'edit' && $id) {
            $editBadge = Database::selectOne("SELECT * FROM trust_badges WHERE id = ?", [$id]);
        }

        $badges = Database::select("SELECT * FROM trust_badges ORDER BY sort_order ASC");
        ob_start();
        include ROOT_PATH . '/resources/views/admin/trust-badges.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }

    public function store(): void
    {
        $maxOrder = Database::selectOne("SELECT MAX(sort_order) as m FROM trust_badges")['m'] ?? 0;
        Database::insert('trust_badges', [
            'title' => Request::post('title', ''),
            'subtitle' => Request::post('subtitle', ''),
            'icon_name' => Request::post('icon_name', 'truck'),
            'sort_order' => (int)Request::post('sort_order', $maxOrder + 1),
            'is_active' => Request::post('is_active') ? 1 : 0,
        ]);
        Session::flash('success', 'Trust badge created successfully');
        Redirect::to('/admin/trust-badges');
    }

    public function edit($id): void
    {
        Database::update('trust_badges', [
            'title' => Request::post('title', ''),
            'subtitle' => Request::post('subtitle', ''),
            'icon_name' => Request::post('icon_name', 'truck'),
            'sort_order' => (int)Request::post('sort_order', 1),
            'is_active' => Request::post('is_active') ? 1 : 0,
        ], 'id = ?', [$id]);
        Session::flash('success', 'Trust badge updated successfully');
        Redirect::to('/admin/trust-badges');
    }

    public function delete(): void
    {
        $id = (int)Request::post('id', 0);
        if ($id) Database::delete('trust_badges', 'id = ?', [$id]);
        Session::flash('success', 'Trust badge deleted');
        Redirect::to('/admin/trust-badges');
    }
}