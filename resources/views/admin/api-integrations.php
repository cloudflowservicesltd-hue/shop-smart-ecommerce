<div class="space-y-6">
    <h1 class="font-heading font-semibold text-xl text-gray-900">API Integrations</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php
        $integrations = [
            ['name' => 'Facebook API', 'desc' => 'Connect your Facebook page for auto-posting and campaigns', 'icon' => 'facebook', 'color' => 'bg-blue-100 text-blue-600', 'connected' => false, 'fields' => [['App ID','fb_app_id'],['App Secret','fb_app_secret'],['Page ID','fb_page_id'],['Access Token','fb_token']]],
            ['name' => 'WhatsApp Business API', 'desc' => 'Send notifications and marketing via WhatsApp', 'icon' => 'message-circle', 'color' => 'bg-green-100 text-green-600', 'connected' => false, 'fields' => [['Phone Number ID','wa_phone_id'],['Business Account ID','wa_account_id'],['Access Token','wa_token'],['Webhook Verify Token','wa_verify_token']]],
            ['name' => 'OpenAI API', 'desc' => 'Power AI marketing content generation', 'icon' => 'sparkles', 'color' => 'bg-purple-100 text-purple-600', 'connected' => false, 'fields' => [['API Key','openai_key'],['Model','openai_model']]],
            ['name' => 'SMTP Email', 'desc' => 'Configure email server for campaigns and notifications', 'icon' => 'mail', 'color' => 'bg-amber-100 text-amber-600', 'connected' => false, 'fields' => [['Host','smtp_host'],['Port','smtp_port'],['Username','smtp_user'],['Password','smtp_pass']]],
        ];
        foreach ($integrations as $int):
        ?>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
            <div class="p-6 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 <?= explode(' ', $int['color'])[0] ?> rounded-xl flex items-center justify-center"><i data-lucide="<?= $int['icon'] ?>" class="w-5 h-5 <?= explode(' ', $int['color'])[1] ?>"></i></div>
                    <div class="flex-1">
                        <h3 class="font-medium"><?= $int['name'] ?></h3>
                        <p class="text-xs text-gray-500"><?= $int['desc'] ?></p>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium <?= $int['connected'] ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500' ?>"><?= $int['connected'] ? 'Connected' : 'Not Connected' ?></span>
                </div>
            </div>
            <div class="p-6 space-y-3">
                <?php foreach ($int['fields'] as $f): ?>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1"><?= $f[0] ?></label>
                    <input type="<?= str_contains($f[0], 'Secret') || str_contains($f[0], 'Password') || str_contains($f[0], 'Key') || str_contains($f[0], 'Token') ? 'password' : 'text' ?>" name="<?= $f[1] ?>" placeholder="Enter <?= strtolower($f[0]) ?>" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                </div>
                <?php endforeach; ?>
                <div class="flex gap-2 pt-2">
                    <button class="bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-amber-700">Save & Connect</button>
                    <button class="border border-gray-200 text-gray-600 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50">Test Connection</button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Webhooks -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 md:col-span-2">
            <h3 class="font-medium mb-4 flex items-center gap-2"><i data-lucide="webhook" class="w-5 h-5 text-gray-600"></i> Webhook Endpoints</h3>
            <div class="space-y-3">
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                    <span class="px-2 py-0.5 rounded text-xs font-mono font-medium bg-green-100 text-green-700">POST</span>
                    <code class="text-sm flex-1">/api/webhooks/mpesa</code>
                    <span class="text-xs text-gray-500">M-Pesa Callback</span>
                </div>
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                    <span class="px-2 py-0.5 rounded text-xs font-mono font-medium bg-green-100 text-green-700">POST</span>
                    <code class="text-sm flex-1">/api/webhooks/stripe</code>
                    <span class="text-xs text-gray-500">Stripe Webhook</span>
                </div>
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                    <span class="px-2 py-0.5 rounded text-xs font-mono font-medium bg-green-100 text-green-700">POST</span>
                    <code class="text-sm flex-1">/api/webhooks/intasend</code>
                    <span class="text-xs text-gray-500">IntaSend Webhook</span>
                </div>
            </div>
        </div>
    </div>
</div>