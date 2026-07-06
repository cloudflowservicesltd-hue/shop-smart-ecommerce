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
        $fields = ['intasend_key', 'intasend_secret', 'intasend_publishable'];
        foreach ($fields as $f) { $this->saveSetting($f, Request::post($f, '')); }
        Session::flash('success', 'IntaSend settings saved');
        Redirect::to('/admin/payments/settings');
    }

    public function savePesapal(): void
    {
        $fields = ['pesapal_env', 'pesapal_key', 'pesapal_secret', 'pesapal_ipn_id'];
        foreach ($fields as $f) { $this->saveSetting($f, Request::post($f, '')); }
        Session::flash('success', 'PesaPal settings saved');
        Redirect::to('/admin/payments/settings');
    }

    public function savePaypal(): void
    {
        $fields = ['paypal_env', 'paypal_currency', 'paypal_exchange_rate', 'paypal_client_id', 'paypal_secret', 'paypal_webhook_id'];
        foreach ($fields as $f) { $this->saveSetting($f, Request::post($f, '')); }
        Session::flash('success', 'PayPal settings saved');
        Redirect::to('/admin/payments/settings');
    }
}