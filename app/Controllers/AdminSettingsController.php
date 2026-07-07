<?php

class AdminSettingsController extends BaseController
{
    /**
     * Return public-facing site settings as JSON.
     * Called by frontend JS and layouts to get dynamic settings.
     */
    public function publicSettings(): void
    {
        header('Content-Type: application/json');

        try {
            $rows = Database::select("SELECT `key`, `value` FROM settings");
            $settings = [];
            foreach ($rows as $row) {
                $settings[$row['key']] = $row['value'];
            }
        } catch (\Throwable $e) {
            $settings = [];
        }

        $this->posJson([
            'success'       => true,
            'store_name'         => $settings['store_name'] ?? 'ShopSmart',
            'currency_symbol'    => $settings['currency_symbol'] ?? 'KSh',
            'currency'           => $settings['currency'] ?? 'KES',
            'tax_rate'           => (float)($settings['tax_rate'] ?? 16),
            'shipping_threshold' => (float)($settings['shipping_threshold'] ?? 5000),
            'shipping_banner_text' => $settings['shipping_banner_text'] ?? '',
            'store_email'        => $settings['store_email'] ?? '',
            'store_phone'        => $settings['store_phone'] ?? '',
            'store_address'      => $settings['store_address'] ?? '',
            'store_tagline'      => $settings['store_tagline'] ?? '',
            'site_logo'          => $settings['site_logo'] ?? '',
            'site_favicon'       => $settings['site_favicon'] ?? '',
            'primary_color'      => $settings['primary_color'] ?? '#d97706',
            'primary_hover_color' => $settings['primary_hover_color'] ?? '#b45309',
            'header_bg_color'    => $settings['header_bg_color'] ?? '#ffffff',
            'footer_bg_color'    => $settings['footer_bg_color'] ?? '#111827',
            'social_facebook'    => $settings['social_facebook'] ?? '',
            'social_twitter'     => $settings['social_twitter'] ?? '',
            'social_instagram'   => $settings['social_instagram'] ?? '',
            'social_youtube'     => $settings['social_youtube'] ?? '',
            'social_tiktok'      => $settings['social_tiktok'] ?? '',
        ]);
    }

    public function index(): void
    {
        $breadcrumbs = [['Settings', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/settings.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }

    public function update(): void
    {
        // Helper: upsert a setting
        $upsert = function($key, $value, $group = 'general') {
            $existing = Database::selectOne("SELECT id FROM settings WHERE `key` = ?", [$key]);
            if ($existing) {
                Database::update('settings', ['value' => $value, 'updated_at' => date('Y-m-d H:i:s')], '`key` = ?', [$key]);
            } else {
                Database::insert('settings', ['key' => $key, 'value' => $value, 'group_name' => $group, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
            }
        };

        // Handle logo upload
        if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['site_logo'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp','svg'];
            if (in_array($ext, $allowed) && $file['size'] <= 5*1024*1024) {
                $uploadDir = ROOT_PATH . '/public/uploads/settings/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $filename = 'logo_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                    // Remove old logo
                    $oldLogo = Database::selectOne("SELECT value FROM settings WHERE `key` = 'site_logo'");
                    if ($oldLogo && $oldLogo['value']) {
                        $oldPath = ROOT_PATH . '/public' . $oldLogo['value'];
                        if (file_exists($oldPath)) unlink($oldPath);
                    }
                    $upsert('site_logo', '/uploads/settings/' . $filename, 'general');
                }
            }
        }

        // Remove logo
        if (Request::post('remove_logo') == '1') {
            $oldLogo = Database::selectOne("SELECT value FROM settings WHERE `key` = 'site_logo'");
            if ($oldLogo && $oldLogo['value']) {
                $oldPath = ROOT_PATH . '/public' . $oldLogo['value'];
                if (file_exists($oldPath)) unlink($oldPath);
            }
            $upsert('site_logo', '', 'general');
        }

        // Handle favicon upload
        if (isset($_FILES['site_favicon']) && $_FILES['site_favicon']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['site_favicon'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['ico','png','jpg','jpeg','svg','gif','webp'];
            if (in_array($ext, $allowed) && $file['size'] <= 2*1024*1024) {
                $uploadDir = ROOT_PATH . '/public/uploads/settings/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $filename = 'favicon_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                    // Remove old favicon
                    $oldFav = Database::selectOne("SELECT value FROM settings WHERE `key` = 'site_favicon'");
                    if ($oldFav && $oldFav['value']) {
                        $oldPath = ROOT_PATH . '/public' . $oldFav['value'];
                        if (file_exists($oldPath)) unlink($oldPath);
                    }
                    $upsert('site_favicon', '/uploads/settings/' . $filename, 'general');
                }
            }
        }

        // Remove favicon
        if (Request::post('remove_favicon') == '1') {
            $oldFav = Database::selectOne("SELECT value FROM settings WHERE `key` = 'site_favicon'");
            if ($oldFav && $oldFav['value']) {
                $oldPath = ROOT_PATH . '/public' . $oldFav['value'];
                if (file_exists($oldPath)) unlink($oldPath);
            }
            $upsert('site_favicon', '', 'general');
        }

        // General text fields
        $fields = ['store_name','store_tagline','store_email','store_phone','store_address','currency','currency_symbol','tax_rate','shipping_threshold','shipping_banner_text','login_title','login_subtitle','login_description','category_circle_size'];
        foreach ($fields as $f) {
            $upsert($f, Request::post($f, ''), 'general');
        }

        // Login sidebar color
        $loginBg = Request::post('login_bg_color', '');
        if (!$loginBg) $loginBg = Request::post('login_bg_color_text', '');
        $upsert('login_bg_color', $loginBg ?: '#b45309', 'general');

        // Login logo upload
        if (isset($_FILES['login_logo']) && $_FILES['login_logo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['login_logo'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp','svg'];
            if (in_array($ext, $allowed) && $file['size'] <= 2*1024*1024) {
                $uploadDir = ROOT_PATH . '/public/uploads/settings/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $filename = 'login_logo_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                    $oldLogo = Database::selectOne("SELECT value FROM settings WHERE `key` = 'login_logo'");
                    if ($oldLogo && $oldLogo['value']) {
                        $oldPath = ROOT_PATH . '/public' . $oldLogo['value'];
                        if (file_exists($oldPath)) unlink($oldPath);
                    }
                    $upsert('login_logo', '/uploads/settings/' . $filename, 'general');
                }
            }
        }

        // Remove login logo
        if (Request::post('remove_login_logo') == '1') {
            $oldLogo = Database::selectOne("SELECT value FROM settings WHERE `key` = 'login_logo'");
            if ($oldLogo && $oldLogo['value']) {
                $oldPath = ROOT_PATH . '/public' . $oldLogo['value'];
                if (file_exists($oldPath)) unlink($oldPath);
            }
            $upsert('login_logo', '', 'general');
        }

        // Notification checkboxes
        $notifFields = ['notify_new_order','notify_payment','notify_low_stock','notify_new_customer'];
        foreach ($notifFields as $f) {
            $upsert($f, Request::post($f, '0'), 'general');
        }

        // Google Reviews settings (only business ID, no API key needed)
        $googleFields = ['google_place_id'];
        foreach ($googleFields as $f) {
            $upsert($f, Request::post($f, ''), 'general');
        }

        // Blotato API key
        $upsert('blotato_api_key', Request::post('blotato_api_key', ''), 'blotato');

        // Color settings
        $colorFields = ['primary_color','primary_hover_color','header_bg_color','footer_bg_color'];
        foreach ($colorFields as $f) {
            $val = Request::post($f, '');
            if (!$val) $val = Request::post($f . '_text', '');
            $upsert($f, $val, 'general');
        }

        Session::flash('success', 'Settings saved successfully');
        Redirect::to('/admin/settings');
    }

    /**
     * Manage cities (GET: list, POST: add/edit/delete).
     * Route: GET/POST /admin/settings/cities
     */
    public function cities(): void
    {
        $action = Request::post('action', '');

        // Handle POST actions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($action === 'delete') {
                $id = (int)Request::post('id', 0);
                if ($id > 0) {
                    Database::delete('cities', 'id = ?', [$id]);
                    Session::flash('success', 'City deleted');
                }
                Redirect::to('/admin/settings/cities');
                return;
            }

            if ($action === 'edit') {
                $id = (int)Request::post('id', 0);
                if ($id > 0) {
                    Database::update('cities', [
                        'name'         => trim(Request::post('name', '')),
                        'shipping_cost' => (float)Request::post('shipping_cost', 0),
                        'is_active'    => Request::post('is_active') ? 1 : 0,
                        'sort_order'   => (int)Request::post('sort_order', 0),
                    ], 'id = ?', [$id]);
                    Session::flash('success', 'City updated');
                }
                Redirect::to('/admin/settings/cities');
                return;
            }

            // Default: add new city
            $name = trim(Request::post('name', ''));
            if (empty($name)) {
                Session::flash('error', 'City name is required');
                Redirect::to('/admin/settings/cities');
                return;
            }
            $exists = Database::selectOne("SELECT id FROM cities WHERE name = ?", [$name]);
            if ($exists) {
                Session::flash('error', 'City already exists');
                Redirect::to('/admin/settings/cities');
                return;
            }
            Database::insert('cities', [
                'name'          => $name,
                'shipping_cost' => (float)Request::post('shipping_cost', 0),
                'is_active'     => Request::post('is_active') ? 1 : 0,
                'sort_order'    => (int)Request::post('sort_order', 0),
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
            Session::flash('success', 'City added');
            Redirect::to('/admin/settings/cities');
            return;
        }

        // GET: list cities
        $cities = Database::select("SELECT * FROM cities ORDER BY sort_order ASC, name ASC");
        $breadcrumbs = [['Settings', '/admin/settings'], ['Cities', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/cities.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }
}