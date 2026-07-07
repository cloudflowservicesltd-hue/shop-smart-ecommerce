<?php

class AdminPaymentSettingsController extends BaseController
{
    public function index(): void
    {
        $breadcrumbs = [['Payments', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/payments/payments.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }

    public function settings(): void
    {
        $breadcrumbs = [['Payments', '/admin/payments'], ['Settings', '']];
        ob_start();
        include ROOT_PATH . '/resources/views/admin/payments/settings.php';
        $content = ob_get_clean();
        include ROOT_PATH . '/resources/views/layouts/admin.php';
    }

    protected function saveSetting(string $key, string $value, string $group = 'payments'): void
    {
        $existing = Database::selectOne("SELECT id FROM settings WHERE `key` = ?", [$key]);
        $now = date('Y-m-d H:i:s');
        if ($existing) {
            Database::update('settings', ['value' => $value, 'updated_at' => $now], '`key` = ?', [$key]);
        } else {
            Database::insert('settings', ['key' => $key, 'value' => $value, 'group_name' => $group, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function toggle(): void
    {
        $gateway = Request::post('gateway', '');
        $enabled = Request::post('enabled', '0');
        $allowed = ['mpesa', 'stripe', 'intasend', 'pesapal', 'paypal'];
        if (!in_array($gateway, $allowed)) { Session::flash('error', 'Invalid gateway'); Redirect::back(); }
        $this->saveSetting($gateway . '_enabled', $enabled === '1' ? '1' : '0');
        Session::flash('success', ucfirst($gateway) . ' ' . ($enabled === '1' ? 'enabled' : 'disabled'));
        Redirect::to('/admin/payments/settings');
    }

    public function saveMpesa(): void
    {
        $fields = ['mpesa_env', 'mpesa_consumer_key', 'mpesa_consumer_secret', 'mpesa_shortcode', 'mpesa_passkey', 'mpesa_callback'];
        foreach ($fields as $f) { $this->saveSetting($f, Request::post($f, '')); }
        Session::flash('success', 'M-Pesa settings saved');
        Redirect::to('/admin/payments/settings');
    }

    public function saveStripe(): void
    {
        $fields = ['stripe_key', 'stripe_secret', 'stripe_webhook_secret'];
        foreach ($fields as $f) { $this->saveSetting($f, Request::post($f, '')); }
        Session::flash('success', 'Stripe settings saved');
        Redirect::to('/admin/payments/settings');
    }

    public function saveIntasend(): void
    {
        $publishableKey = Request::post('intasend_publishable_key', '');
        $secret = Request::post('intasend_secret', '');
        $testMode = Request::post('intasend_test_mode', '0') === '1' ? '1' : '0';

        // Save new keys
        $this->saveSetting('intasend_publishable_key', $publishableKey);
        $this->saveSetting('intasend_secret', $secret);
        $this->saveSetting('intasend_test_mode', $testMode);

        // Sync to old keys for backward compatibility
        $this->saveSetting('intasend_publishable', $publishableKey);

        // Sync to .env
        $this->syncToEnv('INTASEND_PUBLISHABLE_KEY', $publishableKey);
        $this->syncToEnv('INTASEND_TEST_ENVIRONMENT', $testMode === '1' ? 'true' : 'false');

        Session::flash('success', 'IntaSend settings saved');
        Redirect::to('/admin/payments/settings');
    }

    public function savePesapal(): void
    {
        $consumerKey = Request::post('pesapal_consumer_key', '');
        $consumerSecret = Request::post('pesapal_consumer_secret', '');
        $testMode = Request::post('pesapal_test_mode', '0') === '1' ? '1' : '0';
        $ipnId = Request::post('pesapal_ipn_id', '');

        // Save new keys
        $this->saveSetting('pesapal_consumer_key', $consumerKey);
        $this->saveSetting('pesapal_consumer_secret', $consumerSecret);
        $this->saveSetting('pesapal_test_mode', $testMode);
        $this->saveSetting('pesapal_ipn_id', $ipnId);

        // Sync to old keys for backward compatibility
        $this->saveSetting('pesapal_key', $consumerKey);
        $this->saveSetting('pesapal_secret', $consumerSecret);
        $this->saveSetting('pesapal_env', $testMode === '1' ? 'sandbox' : 'production');

        // Sync to .env
        $this->syncToEnv('PESAPAL_CONSUMER_KEY', $consumerKey);
        $this->syncToEnv('PESAPAL_CONSUMER_SECRET', $consumerSecret);

        Session::flash('success', 'PesaPal settings saved');
        Redirect::to('/admin/payments/settings');
    }

    private function syncToEnv(string $key, string $value): void
    {
        $envPath = ROOT_PATH . '/.env';
        if (!file_exists($envPath)) return;
        $content = file_get_contents($envPath);
        if (preg_match('/^' . preg_quote($key, '/') . '=.*$/m', $content)) {
            $content = preg_replace('/^' . preg_quote($key, '/') . '=.*$/m', $key . '=' . $value, $content);
        } else {
            $content .= "\n" . $key . '=' . $value;
        }
        file_put_contents($envPath, $content);
    }

    public function savePaypal(): void
    {
        $fields = ['paypal_env', 'paypal_currency', 'paypal_exchange_rate', 'paypal_client_id', 'paypal_secret', 'paypal_webhook_id'];
        foreach ($fields as $f) { $this->saveSetting($f, Request::post($f, '')); }
        Session::flash('success', 'PayPal settings saved');
        Redirect::to('/admin/payments/settings');
    }
}