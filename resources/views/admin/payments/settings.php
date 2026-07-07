<?php
// Helper to get a setting value from DB
$getSetting = function($key, $default = '') {
    $row = Database::selectOne("SELECT value FROM settings WHERE `key` = ?", [$key]);
    return $row ? $row['value'] : $default;
};

// Load all payment settings
$s = [
    // M-Pesa
    'mpesa_enabled'        => $getSetting('mpesa_enabled', '0'),
    'mpesa_env'            => $getSetting('mpesa_env', 'sandbox'),
    'mpesa_consumer_key'   => $getSetting('mpesa_consumer_key'),
    'mpesa_consumer_secret'=> $getSetting('mpesa_consumer_secret'),
    'mpesa_shortcode'      => $getSetting('mpesa_shortcode'),
    'mpesa_passkey'        => $getSetting('mpesa_passkey'),
    'mpesa_callback'       => $getSetting('mpesa_callback'),
    // Stripe
    'stripe_enabled'       => $getSetting('stripe_enabled', '0'),
    'stripe_key'           => $getSetting('stripe_key'),
    'stripe_secret'        => $getSetting('stripe_secret'),
    'stripe_webhook_secret'=> $getSetting('stripe_webhook_secret'),
    // IntaSend
    'intasend_enabled'        => $getSetting('intasend_enabled', '0'),
    'intasend_publishable_key' => $getSetting('intasend_publishable_key'),
    'intasend_secret'         => $getSetting('intasend_secret'),
    'intasend_test_mode'      => $getSetting('intasend_test_mode', '1'),
    // PesaPal
    'pesapal_enabled'         => $getSetting('pesapal_enabled', '0'),
    'pesapal_consumer_key'    => $getSetting('pesapal_consumer_key'),
    'pesapal_consumer_secret' => $getSetting('pesapal_consumer_secret'),
    'pesapal_test_mode'       => $getSetting('pesapal_test_mode', '1'),
    'pesapal_ipn_id'          => $getSetting('pesapal_ipn_id'),
    // PayPal
    'paypal_enabled'       => $getSetting('paypal_enabled', '0'),
    'paypal_env'           => $getSetting('paypal_env', 'sandbox'),
    'paypal_currency'      => $getSetting('paypal_currency', 'USD'),
    'paypal_exchange_rate' => $getSetting('paypal_exchange_rate', '0.0077'),
    'paypal_client_id'     => $getSetting('paypal_client_id'),
    'paypal_secret'        => $getSetting('paypal_secret'),
    'paypal_webhook_id'    => $getSetting('paypal_webhook_id'),
];

// Toggle helper
$isEnabled = function($val) { return $val === '1'; };
$toggleColor = function($val) use ($isEnabled) {
    return $isEnabled($val) ? 'bg-amber-500' : 'bg-gray-300';
};
$toggleChecked = function($val) use ($isEnabled) {
    return $isEnabled($val) ? 'checked' : '';
};
$envSelected = function($current, $option) {
    return $current === $option ? 'selected' : '';
};
$val = function($v) { return e($v); };
$maskedVal = function($v) { return $v ? '••••••••••••' : ''; };
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="font-heading font-semibold text-xl text-gray-900">Payment Gateway Settings</h1>
            <p class="text-sm text-gray-500 mt-1">Configure payment gateways and their API credentials</p>
        </div>
    </div>

    <?php if ($flash = Session::get('success')): Session::forget('success'); ?>
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm flex items-center gap-2">
        <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i> <?= e($flash) ?>
    </div>
    <?php endif; ?>
    <?php if ($flash = Session::get('error')): Session::forget('error'); ?>
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm flex items-center gap-2">
        <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i> <?= e($flash) ?>
    </div>
    <?php endif; ?>

    <!-- ==================== M-Pesa ==================== -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between p-6 pb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <i data-lucide="smartphone" class="w-5 h-5 text-green-600"></i>
                </div>
                <div>
                    <h3 class="font-medium text-gray-900">M-Pesa (Safaricom Daraja)</h3>
                    <p class="text-xs text-gray-500">STK Push & C2B payments via Daraja API</p>
                </div>
            </div>
            <form method="POST" action="/admin/payments/settings/toggle" class="flex items-center gap-2">
                <?= csrf() ?>
                <input type="hidden" name="gateway" value="mpesa">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="enabled" value="1" class="sr-only peer" <?= $toggleChecked($s['mpesa_enabled']) ?> onchange="this.form.submit()">
                    <div class="w-11 h-6 bg-gray-300 peer-focus:ring-2 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500"></div>
                </label>
            </form>
        </div>
        <div class="<?= $isEnabled($s['mpesa_enabled']) ? '' : 'opacity-50 pointer-events-none' ?>">
            <form method="POST" action="/admin/payments/settings/mpesa" class="p-6 pt-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?= csrf() ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Environment</label>
                    <select name="mpesa_env" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <option value="sandbox" <?= $envSelected($s['mpesa_env'], 'sandbox') ?>>Sandbox (Testing)</option>
                        <option value="production" <?= $envSelected($s['mpesa_env'], 'production') ?>>Production (Live)</option>
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Use sandbox for testing, production for real payments</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Business Shortcode</label>
                    <input type="text" name="mpesa_shortcode" value="<?= $val($s['mpesa_shortcode']) ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="174379">
                    <p class="text-xs text-gray-400 mt-1">Your Paybill or Till number</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Consumer Key</label>
                    <input type="text" name="mpesa_consumer_key" value="<?= $val($s['mpesa_consumer_key']) ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="From Daraja app credentials">
                    <p class="text-xs text-gray-400 mt-1">OAuth Consumer Key from Safaricom Developer Portal</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Consumer Secret</label>
                    <input type="password" name="mpesa_consumer_secret" value="<?= $val($s['mpesa_consumer_secret']) ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="Enter consumer secret">
                    <p class="text-xs text-gray-400 mt-1">OAuth Consumer Secret from Safaricom Developer Portal</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Online Passkey</label>
                    <input type="password" name="mpesa_passkey" value="<?= $val($s['mpesa_passkey']) ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="Enter passkey">
                    <p class="text-xs text-gray-400 mt-1">Lipa Na M-Pesa Online Passkey</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Callback URL</label>
                    <input type="text" name="mpesa_callback" value="<?= $val($s['mpesa_callback']) ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="https://yourdomain.com/api/payments/callback/mpesa">
                    <p class="text-xs text-gray-400 mt-1">Must be publicly accessible for STK Push callbacks</p>
                </div>
                <div class="sm:col-span-2 flex justify-end pt-2">
                    <button type="submit" class="bg-green-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-green-700 transition-colors flex items-center gap-2">
                        <i data-lucide="save" class="w-4 h-4"></i> Save M-Pesa Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ==================== Stripe ==================== -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between p-6 pb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i data-lucide="credit-card" class="w-5 h-5 text-purple-600"></i>
                </div>
                <div>
                    <h3 class="font-medium text-gray-900">Stripe</h3>
                    <p class="text-xs text-gray-500">Visa, Mastercard & international card payments</p>
                </div>
            </div>
            <form method="POST" action="/admin/payments/settings/toggle" class="flex items-center gap-2">
                <?= csrf() ?>
                <input type="hidden" name="gateway" value="stripe">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="enabled" value="1" class="sr-only peer" <?= $toggleChecked($s['stripe_enabled']) ?> onchange="this.form.submit()">
                    <div class="w-11 h-6 bg-gray-300 peer-focus:ring-2 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-500"></div>
                </label>
            </form>
        </div>
        <div class="<?= $isEnabled($s['stripe_enabled']) ? '' : 'opacity-50 pointer-events-none' ?>">
            <form method="POST" action="/admin/payments/settings/stripe" class="p-6 pt-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?= csrf() ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Publishable Key</label>
                    <input type="text" name="stripe_key" value="<?= $val($s['stripe_key']) ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent" placeholder="pk_live_...">
                    <p class="text-xs text-gray-400 mt-1">Used in frontend for Stripe.js initialization</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Secret Key</label>
                    <input type="password" name="stripe_secret" value="<?= $val($s['stripe_secret']) ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent" placeholder="sk_live_...">
                    <p class="text-xs text-gray-400 mt-1">Server-side key for API requests</p>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Webhook Secret</label>
                    <input type="password" name="stripe_webhook_secret" value="<?= $val($s['stripe_webhook_secret']) ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent" placeholder="whsec_...">
                    <p class="text-xs text-gray-400 mt-1">For verifying webhook signatures (optional)</p>
                </div>
                <div class="sm:col-span-2 flex justify-end pt-2">
                    <button type="submit" class="bg-purple-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-purple-700 transition-colors flex items-center gap-2">
                        <i data-lucide="save" class="w-4 h-4"></i> Save Stripe Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ==================== IntaSend ==================== -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between p-6 pb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i data-lucide="zap" class="w-5 h-5 text-blue-600"></i>
                </div>
                <div>
                    <h3 class="font-medium text-gray-900">IntaSend</h3>
                    <p class="text-xs text-gray-500">M-Pesa, card & bank transfers</p>
                </div>
            </div>
            <form method="POST" action="/admin/payments/settings/toggle" class="flex items-center gap-2">
                <?= csrf() ?>
                <input type="hidden" name="gateway" value="intasend">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="enabled" value="1" class="sr-only peer" <?= $toggleChecked($s['intasend_enabled']) ?> onchange="this.form.submit()">
                    <div class="w-11 h-6 bg-gray-300 peer-focus:ring-2 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-500"></div>
                </label>
            </form>
        </div>
        <div class="<?= $isEnabled($s['intasend_enabled']) ? '' : 'opacity-50 pointer-events-none' ?>">
            <form method="POST" action="/admin/payments/settings/intasend" class="p-6 pt-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?= csrf() ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Publishable Key</label>
                    <input type="text" name="intasend_publishable_key" value="<?= $val($s['intasend_publishable_key']) ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono" placeholder="ISPubKey_xxxxxxxxxxxx">
                    <p class="text-xs text-gray-400 mt-1">From <a href="https://app.intasend.com" target="_blank" class="text-blue-600 hover:underline">app.intasend.com</a> → Settings → API Keys</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Secret Key</label>
                    <div class="flex gap-2">
                        <input type="password" name="intasend_secret" value="<?= $val($s['intasend_secret']) ?>" class="flex-1 px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono" placeholder="Enter secret key">
                        <button type="button" onclick="const i=this.previousElementSibling;i.type=i.type==='password'?'text':'password'" class="px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-500 hover:bg-gray-50"><i data-lucide="eye" class="w-4 h-4"></i></button>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Required for server-side verification</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Test Mode</label>
                    <label class="relative inline-flex items-center cursor-pointer mt-1">
                        <input type="checkbox" name="intasend_test_mode" value="1" class="sr-only peer" <?= $s['intasend_test_mode'] === '1' ? 'checked' : '' ?>>
                        <div class="w-11 h-6 bg-gray-300 peer-focus:ring-2 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-500"></div>
                        <span class="ml-3 text-sm text-gray-600">Sandbox (uncheck for live)</span>
                    </label>
                </div>
                <div class="flex items-end">
                    <div class="bg-blue-50 border border-blue-100 rounded-lg p-3 w-full">
                        <p class="text-xs text-blue-700"><i data-lucide="info" class="w-3.5 h-3.5 inline-block mr-1 -mt-0.5"></i> <strong>Setup:</strong> 1) Create account at <a href="https://intasend.com" target="_blank" class="underline">intasend.com</a>. 2) Get API keys. 3) Set callback URL to <code class="bg-blue-100 px-1 py-0.5 rounded text-[11px]">/payment/intasend/callback</code>.</p>
                    </div>
                </div>
                <div class="sm:col-span-2 flex justify-end pt-2">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors flex items-center gap-2">
                        <i data-lucide="save" class="w-4 h-4"></i> Save IntaSend Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ==================== PesaPal ==================== -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between p-6 pb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                    <i data-lucide="wallet" class="w-5 h-5 text-orange-600"></i>
                </div>
                <div>
                    <h3 class="font-medium text-gray-900">PesaPal</h3>
                    <p class="text-xs text-gray-500">M-Pesa, Airtel Money, Visa, Mastercard & more</p>
                </div>
            </div>
            <form method="POST" action="/admin/payments/settings/toggle" class="flex items-center gap-2">
                <?= csrf() ?>
                <input type="hidden" name="gateway" value="pesapal">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="enabled" value="1" class="sr-only peer" <?= $toggleChecked($s['pesapal_enabled']) ?> onchange="this.form.submit()">
                    <div class="w-11 h-6 bg-gray-300 peer-focus:ring-2 peer-focus:ring-orange-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-500"></div>
                </label>
            </form>
        </div>
        <div class="<?= $isEnabled($s['pesapal_enabled']) ? '' : 'opacity-50 pointer-events-none' ?>">
            <form method="POST" action="/admin/payments/settings/pesapal" class="p-6 pt-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?= csrf() ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Consumer Key</label>
                    <input type="text" name="pesapal_consumer_key" value="<?= $val($s['pesapal_consumer_key']) ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent font-mono" placeholder="Enter Pesapal consumer key">
                    <p class="text-xs text-gray-400 mt-1">From <a href="https://developer.pesapal.com" target="_blank" class="text-orange-600 hover:underline">Pesapal Developer Portal</a></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Consumer Secret</label>
                    <div class="flex gap-2">
                        <input type="password" name="pesapal_consumer_secret" value="<?= $val($s['pesapal_consumer_secret']) ?>" class="flex-1 px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent font-mono" placeholder="Enter consumer secret">
                        <button type="button" onclick="const i=this.previousElementSibling;i.type=i.type==='password'?'text':'password'" class="px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-500 hover:bg-gray-50"><i data-lucide="eye" class="w-4 h-4"></i></button>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Test Mode</label>
                    <label class="relative inline-flex items-center cursor-pointer mt-1">
                        <input type="checkbox" name="pesapal_test_mode" value="1" class="sr-only peer" <?= $s['pesapal_test_mode'] === '1' ? 'checked' : '' ?>>
                        <div class="w-11 h-6 bg-gray-300 peer-focus:ring-2 peer-focus:ring-orange-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-500"></div>
                        <span class="ml-3 text-sm text-gray-600">Sandbox (uncheck for live)</span>
                    </label>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">IPN Notification ID</label>
                    <input type="text" name="pesapal_ipn_id" value="<?= $val($s['pesapal_ipn_id']) ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent font-mono" placeholder="Auto-registered or from Pesapal dashboard">
                    <p class="text-xs text-gray-400 mt-1">Registered automatically on first checkout, or set manually</p>
                </div>
                <div class="sm:col-span-2">
                    <div class="bg-orange-50 border border-orange-100 rounded-lg p-3">
                        <p class="text-xs text-orange-800"><i data-lucide="info" class="w-3.5 h-3.5 inline-block mr-1 -mt-0.5"></i> <strong>Setup:</strong> 1) Create account at <a href="https://www.pesapal.com" target="_blank" class="underline">pesapal.com</a>. 2) Get credentials from Developer Portal. 3) Callback URL: <code class="bg-orange-100 px-1 py-0.5 rounded text-[11px]">/payment/pesapal/callback</code>. 4) IPN URL: <code class="bg-orange-100 px-1 py-0.5 rounded text-[11px]">/payment/pesapal/ipn</code></p>
                    </div>
                </div>
                <div class="sm:col-span-2 flex justify-end pt-2">
                    <button type="submit" class="bg-orange-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-orange-700 transition-colors flex items-center gap-2">
                        <i data-lucide="save" class="w-4 h-4"></i> Save PesaPal Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ==================== PayPal ==================== -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between p-6 pb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                    <i data-lucide="globe" class="w-5 h-5 text-amber-600"></i>
                </div>
                <div>
                    <h3 class="font-medium text-gray-900">PayPal</h3>
                    <p class="text-xs text-gray-500">International payments via PayPal</p>
                </div>
            </div>
            <form method="POST" action="/admin/payments/settings/toggle" class="flex items-center gap-2">
                <?= csrf() ?>
                <input type="hidden" name="gateway" value="paypal">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="enabled" value="1" class="sr-only peer" <?= $toggleChecked($s['paypal_enabled']) ?> onchange="this.form.submit()">
                    <div class="w-11 h-6 bg-gray-300 peer-focus:ring-2 peer-focus:ring-amber-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
                </label>
            </form>
        </div>
        <div class="<?= $isEnabled($s['paypal_enabled']) ? '' : 'opacity-50 pointer-events-none' ?>">
            <form method="POST" action="/admin/payments/settings/paypal" class="p-6 pt-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?= csrf() ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Environment</label>
                    <select name="paypal_env" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                        <option value="sandbox" <?= $envSelected($s['paypal_env'], 'sandbox') ?>>Sandbox (Testing)</option>
                        <option value="production" <?= $envSelected($s['paypal_env'], 'production') ?>>Production (Live)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">PayPal Currency</label>
                    <select name="paypal_currency" id="ppCurrencySelect" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                        <option value="USD" <?= $envSelected($s['paypal_currency'] ?? 'USD', 'USD') ?>>USD - US Dollar</option>
                        <option value="EUR" <?= $envSelected($s['paypal_currency'] ?? 'USD', 'EUR') ?>>EUR - Euro</option>
                        <option value="GBP" <?= $envSelected($s['paypal_currency'] ?? 'USD', 'GBP') ?>>GBP - British Pound</option>
                    </select>
                    <p class="text-xs text-gray-400 mt-1">PayPal does not support KES. Amounts will be converted automatically.</p>
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-medium text-gray-700">Exchange Rate</label>
                        <button type="button" onclick="fetchLiveRate()" id="fetchRateBtn" class="inline-flex items-center gap-1.5 text-xs font-medium text-amber-600 hover:text-amber-700 transition-colors">
                            <i data-lucide="refresh-cw" class="w-3.5 h-3.5" id="fetchRateIcon"></i> Fetch Live Rate
                        </button>
                    </div>
                    <div class="relative">
                        <input type="number" step="0.0001" min="0.0001" name="paypal_exchange_rate" id="ppRateInput" value="<?= $val($s['paypal_exchange_rate'] ?? '0.0077') ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent" placeholder="0.0077">
                    </div>
                    <div id="rateDisplay" class="flex items-center gap-2 mt-1.5">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-green-50 text-green-700 border border-green-100">
                            <i data-lucide="trending-up" class="w-3 h-3 mr-1"></i>
                            <span id="rateText">1 USD ≈ <?= round(1 / ($s['paypal_exchange_rate'] ?? 0.0077), 2) ?> KES</span>
                        </span>
                        <span class="text-[11px] text-gray-400" id="rateSourceText">saved rate</span>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Client ID</label>
                    <input type="text" name="paypal_client_id" value="<?= $val($s['paypal_client_id']) ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent" placeholder="PayPal Client ID">
                    <p class="text-xs text-gray-400 mt-1">From PayPal Developer Portal → My Apps & Credentials</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Secret</label>
                    <input type="password" name="paypal_secret" value="<?= $val($s['paypal_secret']) ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent" placeholder="Enter PayPal secret">
                    <p class="text-xs text-gray-400 mt-1">From PayPal Developer Portal → My Apps & Credentials</p>
                </div>
                <div class="sm:col-span-2 flex justify-end pt-2">
                    <button type="submit" class="bg-amber-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors flex items-center gap-2">
                        <i data-lucide="save" class="w-4 h-4"></i> Save PayPal Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Custom Payment Methods Section -->
<div class="mt-8 bg-white border border-gray-200 rounded-2xl overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <div>
            <h3 class="font-heading font-bold text-gray-900 flex items-center gap-2"><i data-lucide="plus-circle" class="w-5 h-5 text-amber-600"></i> Custom Payment Methods (POS)</h3>
            <p class="text-xs text-gray-500 mt-0.5">Add custom payment methods that appear in the POS terminal. Cash is always shown.</p>
        </div>
    </div>
    <div class="p-6">
        <!-- Add new method form -->
        <form id="addPayMethodForm" class="flex flex-wrap items-end gap-3 mb-6 pb-6 border-b border-gray-100">
            <div class="flex-1 min-w-[150px]">
                <label class="block text-xs font-medium text-gray-600 mb-1">Method Name</label>
                <input type="text" id="newMethodName" placeholder="e.g. Bank Transfer" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
            </div>
            <div class="w-32">
                <label class="block text-xs font-medium text-gray-600 mb-1">Icon</label>
                <select id="newMethodIcon" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <option value="credit-card">Card</option>
                    <option value="banknote">Cash</option>
                    <option value="smartphone">Phone</option>
                    <option value="wallet">Wallet</option>
                    <option value="landmark">Bank</option>
                    <option value="qr-code">QR Code</option>
                    <option value="receipt">Receipt</option>
                </select>
            </div>
            <div class="w-32">
                <label class="block text-xs font-medium text-gray-600 mb-1">Color</label>
                <select id="newMethodColor" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <option value="gray">Gray</option>
                    <option value="amber">Amber</option>
                    <option value="green">Green</option>
                    <option value="blue">Blue</option>
                    <option value="purple">Purple</option>
                    <option value="orange">Orange</option>
                    <option value="red">Red</option>
                    <option value="teal">Teal</option>
                </select>
            </div>
            <button type="button" onclick="addPayMethod()" class="px-5 py-2 bg-amber-600 text-white rounded-lg text-sm font-medium hover:bg-amber-700 flex items-center gap-2">
                <i data-lucide="plus" class="w-4 h-4"></i> Add Method
            </button>
        </form>
        <!-- Existing methods list -->
        <div id="customMethodsList">
            <p class="text-sm text-gray-400 text-center py-4">Loading...</p>
        </div>
    </div>
</div>

<script>
function updateRateDisplay() {
    const rate = parseFloat(document.getElementById('ppRateInput').value);
    const currency = document.getElementById('ppCurrencySelect').value;
    if (rate > 0) {
        const kesPerUnit = (1 / rate).toFixed(2);
        document.getElementById('rateText').textContent = '1 ' + currency + ' ≈ ' + kesPerUnit + ' KES';
    }
}

async function fetchLiveRate() {
    const btn = document.getElementById('fetchRateBtn');
    const icon = document.getElementById('fetchRateIcon');
    const currency = document.getElementById('ppCurrencySelect').value;
    const sourceText = document.getElementById('rateSourceText');

    btn.disabled = true;
    icon.classList.add('animate-spin');
    sourceText.textContent = 'fetching...';

    try {
        const resp = await fetch('/api/exchange-rate?currency=' + currency, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await resp.json();
        if (data.success) {
            document.getElementById('ppRateInput').value = data.rate.toFixed(6);
            document.getElementById('rateText').textContent = data.formatted;
            sourceText.textContent = 'live rate';
            sourceText.className = 'text-[11px] text-green-600 font-medium';
        } else {
            sourceText.textContent = data.message || 'fetch failed';
            sourceText.className = 'text-[11px] text-red-500';
        }
    } catch(e) {
        sourceText.textContent = 'network error';
        sourceText.className = 'text-[11px] text-red-500';
    }
    btn.disabled = false;
    icon.classList.remove('animate-spin');
    lucide.createIcons();
}

document.getElementById('ppRateInput').addEventListener('input', updateRateDisplay);
document.getElementById('ppCurrencySelect').addEventListener('change', function() {
    updateRateDisplay();
    document.getElementById('rateSourceText').textContent = 'saved rate';
    document.getElementById('rateSourceText').className = 'text-[11px] text-gray-400';
});

// ─── Custom Payment Methods ──────────────────────
async function loadCustomMethods() {
    const el = document.getElementById('customMethodsList');
    try {
        const r = await fetch('/api/payment-methods', {headers:{'Accept':'application/json'}});
        const data = await r.json();
        if (data.success && data.methods.length > 0) {
            el.innerHTML = '<div class="space-y-2">' + data.methods.map(m => `
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl border ${m.is_active ? 'border-gray-200' : 'border-gray-100 opacity-50'}">
                    <div class="flex-1 min-w-0">
                        <span class="text-sm font-medium text-gray-900">${m.name}</span>
                        <span class="text-xs text-gray-400 ml-2">${m.icon} / ${m.color}</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" ${m.is_active ? 'checked' : ''} onchange="toggleCustomMethod(${m.id}, this.checked)" class="sr-only peer">
                        <div class="w-9 h-5 bg-gray-200 peer-focus:ring-2 peer-focus:ring-amber-300 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-amber-500"></div>
                    </label>
                    <button onclick="deleteCustomMethod(${m.id})" class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                </div>
            `).join('') + '</div>';
            lucide.createIcons();
        } else {
            el.innerHTML = '<p class="text-sm text-gray-400 text-center py-4">No custom payment methods yet. Add one above.</p>';
        }
    } catch(e) {
        el.innerHTML = '<p class="text-sm text-red-400 text-center py-4">Failed to load</p>';
    }
}

async function addPayMethod() {
    const name = document.getElementById('newMethodName').value.trim();
    if (!name) return alert('Enter a method name');
    const fd = new FormData();
    fd.append('name', name);
    fd.append('icon', document.getElementById('newMethodIcon').value);
    fd.append('color', document.getElementById('newMethodColor').value);
    try {
        const r = await fetch('/api/payment-methods', {method:'POST', body:fd});
        const data = await r.json();
        if (data.success) {
            document.getElementById('newMethodName').value = '';
            loadCustomMethods();
        } else { alert(data.error || 'Failed to add'); }
    } catch(e) { alert('Network error'); }
}

async function toggleCustomMethod(id, active) {
    const fd = new FormData();
    fd.append('is_active', active ? '1' : '0');
    try { await fetch('/api/payment-methods/' + id, {method:'POST', body:fd}); loadCustomMethods(); } catch(e) {}
}

async function deleteCustomMethod(id) {
    if (!confirm('Delete this payment method?')) return;
    try {
        await fetch('/api/payment-methods/' + id, {method:'DELETE'});
        loadCustomMethods();
    } catch(e) {}
}

loadCustomMethods();
</script>