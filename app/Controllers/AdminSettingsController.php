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
        $fields = ['store_name','store_tagline','store_email','store_phone','store_address','currency','currency_symbol','tax_rate','shipping_threshold','shipping_banner_text','login_title','login_subtitle','login_description','category_circle_size','logo_height'];
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

        // IntaSend integration
        $upsert('intasend_publishable_key', Request::post('intasend_publishable_key', ''), 'intasend');
        $upsert('intasend_secret', Request::post('intasend_secret', ''), 'intasend');
        $upsert('intasend_test_mode', Request::post('intasend_test_mode', '0'), 'intasend');

        // Make.com integration
        $upsert('make_webhook_url', Request::post('make_webhook_url', ''), 'make');
        $upsert('make_api_key', Request::post('make_api_key', ''), 'make');
        $makeEventFields = ['make_event_new_order','make_event_order_paid','make_event_order_shipped','make_event_new_product','make_event_product_updated','make_event_new_customer','make_event_low_stock'];
        foreach ($makeEventFields as $f) {
            $upsert($f, Request::post($f, '0'), 'make');
        }

        // Pesapal settings
        $upsert('pesapal_consumer_key', Request::post('pesapal_consumer_key', ''), 'payments');
        $upsert('pesapal_consumer_secret', Request::post('pesapal_consumer_secret', ''), 'payments');
        $pesapalTestMode = Request::post('pesapal_test_mode') === '1' ? '1' : '0';
        $upsert('pesapal_test_mode', $pesapalTestMode, 'payments');
        // Also sync legacy keys for backward compatibility
        $upsert('pesapal_key', Request::post('pesapal_consumer_key', ''), 'payments');
        $upsert('pesapal_secret', Request::post('pesapal_consumer_secret', ''), 'payments');
        $upsert('pesapal_env', $pesapalTestMode === '1' ? 'sandbox' : 'production', 'payments');

        // Color settings
        $colorFields = ['primary_color','primary_hover_color','header_bg_color','footer_bg_color'];
        foreach ($colorFields as $f) {
            $val = Request::post($f, '');
            if (!$val) $val = Request::post($f . '_text', '');
            $upsert($f, $val, 'general');
        }

        // Address & Map settings
        $upsert('google_maps_enabled', Request::post('google_maps_enabled', '0'), 'address');
        $upsert('google_maps_api_key', Request::post('google_maps_api_key', ''), 'address');
        $addressFieldKeys = ['address_field_receiver_name','address_field_apartment','address_field_street','address_field_house_no','address_field_landmark','address_field_delivery_instructions'];
        foreach ($addressFieldKeys as $afk) {
            $upsert($afk, Request::post($afk, '0'), 'address');
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

    /**
     * Test Make.com webhook connection.
     * Route: POST /admin/settings/make-test
     */
    public function makeTestWebhook(): void
    {
        $url = Request::post('webhook_url', '');
        if (empty($url)) {
            $url = MakeAPI::getWebhookUrl();
        }

        $result = MakeAPI::testConnection($url);

        if ($result['success']) {
            Session::flash('success', '✅ Webhook test successful! HTTP ' . ($result['http_code'] ?? '200') . '. Check your Make.com scenario for the test data.');
        } else {
            Session::flash('error', '❌ Webhook test failed: ' . ($result['error'] ?? 'Unknown error'));
        }

        Redirect::to('/admin/settings');
    }

    /**
     * Test Pesapal connection.
     * Route: POST /admin/settings/pesapal-test
     */
    public function pesapalTestConnection(): void
    {
        header('Content-Type: application/json');

        // Temporarily save the posted settings so PesapalAPI can read them
        $upsert = function($key, $value, $group = 'payments') {
            $existing = Database::selectOne("SELECT id FROM settings WHERE `key` = ?", [$key]);
            if ($existing) {
                Database::update('settings', ['value' => $value, 'updated_at' => date('Y-m-d H:i:s')], '`key` = ?', [$key]);
            } else {
                Database::insert('settings', ['key' => $key, 'value' => $value, 'group_name' => $group, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
            }
        };

        $consumerKey    = Request::post('pesapal_consumer_key', '');
        $consumerSecret = Request::post('pesapal_consumer_secret', '');
        $testMode       = Request::post('pesapal_test_mode') === '1' ? '1' : '0';

        // Save temporarily so PesapalAPI can read them
        $upsert('pesapal_consumer_key', $consumerKey, 'payments');
        $upsert('pesapal_consumer_secret', $consumerSecret, 'payments');
        $upsert('pesapal_test_mode', $testMode, 'payments');
        $upsert('pesapal_key', $consumerKey, 'payments');
        $upsert('pesapal_secret', $consumerSecret, 'payments');
        $upsert('pesapal_env', $testMode === '1' ? 'sandbox' : 'production', 'payments');

        require_once ROOT_PATH . '/app/Core/PesapalAPI.php';
        $result = PesapalAPI::testConnection();

        echo json_encode($result, JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * View Make.com webhook logs.
     * Route: GET /admin/settings/make-logs
     */
    public function makeWebhookLogs(): void
    {
        require_once ROOT_PATH . '/app/Core/MakeAPI.php';

        $logs = MakeAPI::getWebhookLogs(100);
        $breadcrumbs = [['Settings', '/admin/settings'], ['Make.com Webhook Logs', '']];

        ob_start();
        ?>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="font-heading font-semibold text-xl text-gray-900">Make.com Webhook Logs</h1>
                <div class="flex items-center gap-3">
                    <a href="/admin/settings" class="inline-flex items-center gap-1 text-sm text-gray-600 hover:text-gray-900 transition-colors">
                        <i data-lucide="settings" class="w-4 h-4"></i> Settings
                    </a>
                    <button onclick="if(confirm('Clear logs older than last 100?')){fetch('/admin/settings/make-clear-logs',{method:'POST',headers:{'Content-Type':'application/json'}}).then(()=>location.reload())}" class="inline-flex items-center gap-1 text-sm text-red-600 hover:text-red-800 transition-colors">
                        <i data-lucide="trash-2" class="w-4 h-4"></i> Clear Old
                    </button>
                </div>
            </div>

            <?php if (empty($logs)): ?>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-12 text-center">
                <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-2xl flex items-center justify-center">
                    <i data-lucide="inbox" class="w-8 h-8 text-gray-300"></i>
                </div>
                <p class="text-gray-500">No webhook logs yet. Enable events in Settings and they will appear here.</p>
            </div>
            <?php else: ?>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr>
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Event</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Status</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-600">URL</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Time</th>
                                <th class="text-left px-4 py-3 font-medium text-gray-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php foreach ($logs as $log): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-gray-100 rounded-lg text-xs font-medium text-gray-700"><?= e($log['event'] ?: '—') ?></span>
                                </td>
                                <td class="px-4 py-3">
                                    <?php if (($log['http_code'] ?? 0) >= 200 && ($log['http_code'] ?? 0) < 300): ?>
                                    <span class="inline-flex items-center gap-1 text-green-700 text-xs font-medium">
                                        <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i> <?= $log['http_code'] ?> OK
                                    </span>
                                    <?php else: ?>
                                    <span class="inline-flex items-center gap-1 text-red-600 text-xs font-medium">
                                        <i data-lucide="x-circle" class="w-3.5 h-3.5"></i> <?= $log['http_code'] ?? 'ERR' ?>
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 max-w-xs truncate text-xs text-gray-500 font-mono" title="<?= e($log['url'] ?? '') ?>"><?= e($log['url'] ?? '') ?></td>
                                <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap"><?= e($log['created_at'] ?? '') ?></td>
                                <td class="px-4 py-3">
                                    <button onclick="this.nextElementSibling.classList.toggle('hidden')" class="text-xs text-amber-600 hover:text-amber-800 font-medium">View</button>
                                    <div class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4" onclick="if(event.target===this)this.classList.add('hidden')">
                                        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[80vh] overflow-auto p-6">
                                            <div class="flex items-center justify-between mb-4">
                                                <h3 class="font-semibold text-gray-900">Webhook Details</h3>
                                                <button onclick="this.closest('.fixed').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                                                    <i data-lucide="x" class="w-5 h-5"></i>
                                                </button>
                                            </div>
                                            <div class="space-y-4">
                                                <div>
                                                    <h4 class="text-xs font-medium text-gray-500 mb-1">Event</h4>
                                                    <p class="text-sm font-mono"><?= e($log['event']) ?></p>
                                                </div>
                                                <div>
                                                    <h4 class="text-xs font-medium text-gray-500 mb-1">Payload</h4>
                                                    <pre class="text-xs bg-gray-50 p-3 rounded-lg overflow-auto max-h-48 text-gray-700"><?= e($log['payload'] ?? '') ?></pre>
                                                </div>
                                                <div>
                                                    <h4 class="text-xs font-medium text-gray-500 mb-1">Response</h4>
                                                    <pre class="text-xs bg-gray-50 p-3 rounded-lg overflow-auto max-h-48 text-gray-700"><?= e($log['response'] ?? '') ?></pre>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }
}