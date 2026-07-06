<?php
$waSettings = [];
$waKeys = ['wa_phone_id', 'wa_access_token', 'wa_business_id', 'wa_verify_token', 'wa_api_version'];
foreach ($waKeys as $k) {
    try { $row = Database::selectOne("SELECT value FROM settings WHERE `key` = ?", [$k]); $waSettings[$k] = $row['value'] ?? ''; } catch (\Throwable $e) { $waSettings[$k] = ''; }
}
$waSettings['wa_api_version'] = $waSettings['wa_api_version'] ?: 'v21.0';
$storeName = Database::selectOne("SELECT value FROM settings WHERE `key` = 'store_name'")['value'] ?? 'ShopSmart';
?>
<div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <a href="/admin/marketing/facebook" class="hover:text-amber-600">Marketing</a>
                <i data-lucide="chevron-right" class="w-4 h-4"></i>
                <span class="text-gray-900 font-medium">WhatsApp</span>
            </div>
            <h1 class="font-heading font-semibold text-xl text-gray-900 mt-1 flex items-center gap-2">
                <i data-lucide="message-circle" class="w-5 h-5 text-green-600"></i> WhatsApp Business
            </h1>
        </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200 flex gap-6">
        <button onclick="switchWATab('settings')" id="waTabSettings" class="wa-tab pb-3 text-sm font-medium border-b-2 border-green-600 text-green-600">⚙️ Settings</button>
        <button onclick="switchWATab('send')" id="waTabSend" class="wa-tab pb-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">💬 Send Messages</button>
        <button onclick="switchWATab('history')" id="waTabHistory" class="wa-tab pb-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">📋 History</button>
    </div>

    <!-- ═══ TAB: Settings ═══ -->
    <div id="waPanelSettings" class="wa-panel">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <form id="waSettingsForm" class="space-y-6">
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                        <h3 class="font-medium mb-4 flex items-center gap-2"><i data-lucide="key" class="w-4 h-4 text-green-600"></i> API Configuration</h3>
                        <p class="text-xs text-gray-500 mb-4">Get these from <a href="https://business.facebook.com/settings/whatsapp-business-api" target="_blank" class="underline text-green-600">Meta Business Settings > WhatsApp</a></p>
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number ID</label>
                                    <input type="text" name="wa_phone_id" value="<?= e($waSettings['wa_phone_id']) ?>" placeholder="123456789012345" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-green-500/20 focus:border-green-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">API Version</label>
                                    <select name="wa_api_version" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500/20 focus:border-green-500 bg-white">
                                        <?php foreach (['v17.0','v18.0','v19.0','v20.0','v21.0'] as $v): ?>
                                        <option value="<?= $v ?>" <?= $waSettings['wa_api_version'] === $v ? 'selected' : '' ?>><?= $v ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Access Token</label>
                                <div class="relative">
                                    <input type="password" name="wa_access_token" value="<?= e($waSettings['wa_access_token']) ?>" placeholder="EAAx..." class="w-full px-3 py-2.5 pr-10 border border-gray-200 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-green-500/20 focus:border-green-500">
                                    <button type="button" onclick="toggleWAKey(this)" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"><i data-lucide="eye-off" class="w-4 h-4"></i></button>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Business Account ID</label>
                                    <input type="text" name="wa_business_id" value="<?= e($waSettings['wa_business_id']) ?>" placeholder="1234567890" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-green-500/20 focus:border-green-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Webhook Verify Token</label>
                                    <input type="text" name="wa_verify_token" value="<?= e($waSettings['wa_verify_token']) ?>" placeholder="my_secret_token" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-green-500/20 focus:border-green-500">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" onclick="saveWASettings()" class="bg-amber-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700 flex items-center gap-2"><i data-lucide="save" class="w-4 h-4"></i> Save Settings</button>
                        <button type="button" onclick="testWAConnection()" id="testWaBtn" class="border border-gray-200 text-gray-700 px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-50 flex items-center gap-2"><i data-lucide="activity" class="w-4 h-4"></i> Test</button>
                        <div id="waTestResult" class="hidden flex-1"></div>
                    </div>
                </form>
            </div>
            <div class="space-y-4">
                <div class="bg-green-50 rounded-xl border border-green-200 p-5">
                    <h3 class="text-sm font-semibold text-green-800 mb-2">📱 Setup Guide</h3>
                    <ol class="text-xs text-green-700 space-y-2 list-decimal pl-4">
                        <li>Create a <a href="https://business.facebook.com" target="_blank" class="underline">Meta Business Account</a></li>
                        <li>Go to Business Settings > WhatsApp</li>
                        <li>Add or connect your WhatsApp number</li>
                        <li>Copy Phone Number ID and Access Token</li>
                        <li>Paste them here and click Save</li>
                    </ol>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                    <h3 class="text-sm font-medium mb-3">Connection Status</h3>
                    <div id="waStatusDot" class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full <?= !empty($waSettings['wa_phone_id']) && !empty($waSettings['wa_access_token']) ? 'bg-green-500' : 'bg-gray-300' ?>"></span>
                        <span class="text-sm text-gray-600"><?= !empty($waSettings['wa_phone_id']) && !empty($waSettings['wa_access_token']) ? 'Configured' : 'Not configured' ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ TAB: Send Messages ═══ -->
    <div id="waPanelSend" class="wa-panel hidden">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="font-medium mb-4 flex items-center gap-2"><i data-lucide="send" class="w-4 h-4 text-green-600"></i> Compose Message</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Recipients</label>
                            <select id="waAudience" onchange="toggleCustomPhones()" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm">
                                <option value="all_customers">All Customers</option>
                                <option value="new_customers">New Customers (Last 30 days)</option>
                                <option value="vip">VIP Customers (5+ orders)</option>
                                <option value="subscribers">Newsletter Subscribers</option>
                                <option value="custom">Custom Phone Numbers</option>
                            </select>
                        </div>
                        <div id="customPhonesWrap" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone Numbers (one per line, include country code)</label>
                            <textarea id="waCustomPhones" rows="3" placeholder="254712345678&#10;254789012345" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-green-500/20 focus:border-green-500"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Template</label>
                            <select id="waTemplate" onchange="applyWATemplate()" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm">
                                <option value="">Custom Message</option>
                                <option value="new_product">New Product Alert</option>
                                <option value="discount">Discount Campaign</option>
                                <option value="order_update">Order Update</option>
                                <option value="abandoned_cart">Abandoned Cart Reminder</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Message <span class="text-xs text-gray-400">(use {{name}}, {{order_number}}, {{product_name}}, {{store_name}})</span></label>
                            <textarea id="waMessage" rows="6" oninput="updateWAPreview()" placeholder="Type your WhatsApp message..." class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500/20 focus:border-green-500"></textarea>
                            <div class="flex justify-between mt-1"><span class="text-xs text-gray-400" id="waCharCount">0 characters</span><span class="text-xs text-gray-400" id="waRecipientCount"></span></div>
                        </div>
                        <button type="button" onclick="confirmWASend()" class="w-full bg-green-600 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-green-700 flex items-center justify-center gap-2"><i data-lucide="send" class="w-4 h-4"></i> Send Messages</button>
                    </div>
                </div>
            </div>
            <!-- Preview -->
            <div class="space-y-4">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="text-sm font-medium mb-3 flex items-center gap-2"><i data-lucide="smartphone" class="w-4 h-4 text-green-600"></i> Preview</h3>
                    <div class="bg-[#e5ddd5] rounded-xl p-3">
                        <div class="bg-white rounded-xl p-3 shadow-sm">
                            <div class="text-[10px] text-green-600 font-medium mb-1"><?= e($storeName) ?></div>
                            <div id="waPreview" class="text-sm text-gray-800 whitespace-pre-wrap min-h-[60px]">Your message will appear here...</div>
                            <div class="text-[10px] text-gray-400 text-right mt-1"><?= date('H:i') ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ TAB: History ═══ -->
    <div id="waPanelHistory" class="wa-panel hidden">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <h3 class="font-medium mb-4 flex items-center gap-2"><i data-lucide="clock" class="w-4 h-4 text-green-600"></i> Message History</h3>
            <div id="waHistoryList" class="space-y-3 max-h-[60vh] overflow-y-auto">
                <?php
                $campaigns = Database::select("SELECT * FROM marketing_campaigns WHERE platform = 'whatsapp' ORDER BY created_at DESC LIMIT 20");
                if (empty($campaigns)): ?>
                    <p class="text-sm text-gray-500 text-center py-8">No messages sent yet</p>
                <?php else: foreach ($campaigns as $c): ?>
                    <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                        <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center shrink-0 mt-0.5"><i data-lucide="message-circle" class="w-4 h-4 text-green-600"></i></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium truncate"><?= e($c['name']) ?></p>
                            <p class="text-xs text-gray-500">Sent to <?= number_format($c['total_sent'] ?? 0) ?> recipients</p>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= ($c['status'] ?? '') === 'sent' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' ?>"><?= ucfirst($c['status'] ?? 'pending') ?></span>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>
<script>
lucide.createIcons();

function switchWATab(tab) {
    document.querySelectorAll('.wa-panel').forEach(p => p.classList.add('hidden'));
    document.querySelectorAll('.wa-tab').forEach(b => { b.classList.remove('border-green-600', 'text-green-600'); b.classList.add('border-transparent', 'text-gray-500'); });
    document.getElementById('waPanel' + tab.charAt(0).toUpperCase() + tab.slice(1)).classList.remove('hidden');
    const btn = document.getElementById('waTab' + tab.charAt(0).toUpperCase() + tab.slice(1));
    btn.classList.add('border-green-600', 'text-green-600'); btn.classList.remove('border-transparent', 'text-gray-500');
}

function toggleCustomPhones() {
    document.getElementById('customPhonesWrap').classList.toggle('hidden', document.getElementById('waAudience').value !== 'custom');
}

function toggleWAKey(btn) {
    const input = btn.previousElementSibling;
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btn.innerHTML = show ? '<i data-lucide="eye" class="w-4 h-4"></i>' : '<i data-lucide="eye-off" class="w-4 h-4"></i>';
    lucide.createIcons();
}

const waTemplates = {
    new_product: `Hello {{name}}! 🎉\n\nGreat news — we just added new products to our store!\n\nCheck them out now at <?= e($storeName) ?>\n\n🚚 Free delivery on orders over KSh 5,000!\n\nShop now: {{store_url}}`,
    discount: `🔥 SPECIAL OFFER! 🔥\n\nHello {{name}},\n\nGet amazing discounts on selected items at <?= e($storeName) ?>!\n\nHurry — offer valid while stocks last!\n\nShop now: {{store_url}}`,
    order_update: `Hello {{name}}! 📦\n\nYour order #{{order_number}} has been updated.\n\nTrack your order at: {{store_url}}/account/orders\n\nThank you for shopping with <?= e($storeName) ?>! 🙏`,
    abandoned_cart: `Hey {{name}}! 👋\n\nYou left some items in your cart at <?= e($storeName) ?>.\n\nComplete your order now and don't miss out!\n\n🛒 {{store_url}}/cart`,
};

function applyWATemplate() {
    const v = document.getElementById('waTemplate').value;
    document.getElementById('waMessage').value = waTemplates[v] || '';
    updateWAPreview();
}

function updateWAPreview() {
    const msg = document.getElementById('waMessage').value;
    document.getElementById('waPreview').textContent = msg || 'Your message will appear here...';
    document.getElementById('waCharCount').textContent = msg.length + ' characters';
}

async function saveWASettings() {
    const form = document.getElementById('waSettingsForm');
    const formData = new FormData(form);
    try {
        const res = await fetch('/admin/marketing/whatsapp/settings', { method: 'POST', headers: { 'Accept': 'application/json' }, body: formData });
        const data = await res.json();
        if (data.success) showToast('WhatsApp settings saved!', 'success');
        else showToast(data.error || 'Failed', 'error');
    } catch (e) { showToast('Connection failed', 'error'); }
}

async function testWAConnection() {
    const btn = document.getElementById('testWaBtn');
    const result = document.getElementById('waTestResult');
    btn.disabled = true; btn.textContent = 'Testing...';
    result.classList.remove('hidden'); result.innerHTML = '<div class="text-sm text-gray-500">Testing...</div>';
    try {
        const res = await fetch('/admin/marketing/whatsapp/test', { method: 'POST', headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        result.innerHTML = data.success
            ? `<div class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg px-3 py-2">✅ ${data.message}</div>`
            : `<div class="text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg px-3 py-2">❌ ${data.error}</div>`;
    } catch (e) { result.innerHTML = '<div class="text-sm text-red-700">Connection failed</div>'; }
    btn.disabled = false; btn.innerHTML = '🔄 Test'; 
}

function confirmWASend() {
    const msg = document.getElementById('waMessage').value.trim();
    if (!msg) return showToast('Please enter a message', 'error');
    if (!confirm('Send this message to the selected recipients?')) return;
    sendWAMessages();
}

async function sendWAMessages() {
    const msg = document.getElementById('waMessage').value.trim();
    const audience = document.getElementById('waAudience').value;
    const customPhones = document.getElementById('waCustomPhones')?.value || '';
    try {
        const res = await fetch('/admin/marketing/whatsapp/send', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: msg, audience, custom_phones: customPhones }),
        });
        const data = await res.json();
        if (data.success) showToast('Messages sent: ' + (data.sent || 0), 'success');
        else showToast(data.error || 'Failed to send', 'error');
    } catch (e) { showToast('Connection failed', 'error'); }
}

function showToast(msg, type) {
    const t = document.createElement('div');
    t.className = `fixed top-4 right-4 z-50 px-4 py-2 rounded-lg text-sm font-medium shadow-lg ${type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'}`;
    t.textContent = msg; document.body.appendChild(t); setTimeout(() => t.remove(), 3000);
}
</script>