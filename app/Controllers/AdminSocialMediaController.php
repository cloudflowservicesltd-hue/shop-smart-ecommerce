<?php

class AdminSocialMediaController extends BaseController
{
    public function index(): void
    {
        $social = [];
        foreach (Database::select("SELECT * FROM settings WHERE group_name = 'social'") as $s) {
            $social[$s['key']] = $s['value'];
        }
        $breadcrumbs = [['Social Media', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/social-media.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }

    public function update(): void
    {
        $fields = ['social_facebook', 'social_twitter', 'social_instagram', 'social_youtube', 'social_tiktok'];
        foreach ($fields as $f) {
            $val = Request::post($f, '');
            Database::update('settings', ['value' => $val, 'updated_at' => date('Y-m-d H:i:s')], '`key` = ?', [$f]);
        }
        Session::flash('success', 'Social media links updated');
        Redirect::to('/admin/social-media');
    }
}