<?php

class AdminAppearanceController extends BaseController
{
    public function index(): void
    {
        $breadcrumbs = [['Frontend Content', ''], ['Appearance', '']];
        $settings = [];
        foreach (Database::select("SELECT * FROM settings WHERE group_name = 'appearance'") as $s) {
            $settings[$s['key']] = $s['value'];
        }
        ob_start();
        include ROOT_PATH . '/resources/views/admin/appearance.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }

    public function update(): void
    {
        $fields = ['hero_autoplay','hero_interval','hero_animation','hero_image_fit','hero_image_position','show_categories','show_featured','show_new_arrivals','show_promo_banners','show_trust_badges','show_newsletter','newsletter_heading','newsletter_subheading'];
        foreach ($fields as $f) {
            $val = Request::post($f, '');
            $existing = Database::selectOne("SELECT id FROM settings WHERE `key` = ?", [$f]);
            if ($existing) {
                Database::update('settings', ['value' => $val, 'updated_at' => date('Y-m-d H:i:s')], '`key` = ?', [$f]);
            } else {
                Database::insert('settings', ['key' => $f, 'value' => $val, 'group_name' => 'appearance', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
            }
        }
        Session::flash('success', 'Appearance settings saved');
        Redirect::to('/admin/appearance');
    }
}