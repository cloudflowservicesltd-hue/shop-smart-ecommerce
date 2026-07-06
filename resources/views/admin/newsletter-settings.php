<?php
$mailConfig = class_exists('Mailer') ? Mailer::getConfig() : [];
?>
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <a href="/admin/newsletter/subscribers" class="hover:text-amber-600 transition-colors">Newsletter</a>
                <i data-lucide="chevron-right" class="w-4 h-4"></i>
                <span class="text-gray-900 font-medium">Mail Settings</span>
            </div>
            <h1 class="font-heading font-semibold text-xl text-gray-900 mt-1">SMTP Mail Configuration</h1>
            <p class="text-sm text-gray-500 mt-0.5">Configure your SMTP server for sending emails.</p>
        </div>
    </div>

    <!-- Connection Status -->
    <?php if (Mailer::isConfigured()): ?>
    <div class="bg-green-50 border border-green-200 rounded-xl p-4 flex items-center gap-3">
        <i data-lucide="check-circle" class="w-5 h-5 text-green-600 shrink-0"></i>
        <div>
            <p class="text-sm text-green-800 font-medium">Mail is configured. If SMTP fails, PHP mail() is used automatically as fallback.</p>
            <?php if (!empty($mailConfig['smtp_status']) && $mailConfig['smtp_status'] === 'fallback_to_mail'): ?>
            <p class="text-xs text-amber-700 mt-1"><i data-lucide="alert-triangle" class="w-3 h-3 inline mr-1"></i>SMTP was unreachable last time — emails are being sent via PHP mail() fallback. <a href="javascript:testSMTPConnection()" class="underline font-medium">Test again</a></p>
            <?php endif; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="bg-green-50 border border-green-200 rounded-xl p-4 flex items-center gap-3">
        <i data-lucide="check-circle" class="w-5 h-5 text-green-600 shrink-0"></i>
        <p class="text-sm text-green-800 font-medium">PHP mail() is available. Emails will be sent via your server's local mail system. Configure SMTP below for more reliable delivery.</p>
    </div>
    <?php endif; ?>

    <!-- SMTP Form -->
    <form method="POST" action="/admin/newsletter/settings" id="mailSettingsForm" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <?= csrf() ?>

        <div class="lg:col-span-2 space-y-6">
            <!-- SMTP Settings -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <h3 class="font-medium text-gray-900 mb-4 flex items-center gap-2">
                    <i data-lucide="server" class="w-5 h-5 text-amber-600"></i> SMTP Server
                </h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">SMTP Host <span class="text-red-500">*</span></label>
                        <input type="text" name="mail_host" id="mail_host" value="<?= e($mailConfig['host'] ?? '') ?>" placeholder="smtp.gmail.com" required
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                        <p class="text-xs text-gray-400 mt-1">Gmail: smtp.gmail.com | Outlook: smtp.office365.com | SendGrid: smtp.sendgrid.net</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Port <span class="text-red-500">*</span></label>
                            <input type="number" name="mail_port" id="mail_port" value="<?= e($mailConfig['port'] ?? 587) ?>" placeholder="587" required
                                   class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Encryption</label>
                            <select name="mail_encryption" id="mail_encryption" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 bg-white">
                                <option value="tls" <?= ($mailConfig['encryption'] ?? '') === 'tls' ? 'selected' : '' ?>>TLS (Port 587)</option>
                                <option value="ssl" <?= ($mailConfig['encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL (Port 465)</option>
                                <option value="" <?= ($mailConfig['encryption'] ?? '') === '' ? 'selected' : '' ?>>None (Port 25)</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">SMTP Username <span class="text-red-500">*</span></label>
                        <input type="text" name="mail_username" value="<?= e($mailConfig['username'] ?? '') ?>" placeholder="your@email.com" required autocomplete="username"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">SMTP Password</label>
                        <input type="password" name="mail_password" value="" placeholder="<?= !empty($mailConfig['password']) ? 'Leave blank to keep current' : 'Enter SMTP password' ?>" autocomplete="new-password"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                        <p class="text-xs text-gray-400 mt-1"><?= !empty($mailConfig['password']) ? 'Password is set. Leave blank to keep the current one.' : 'For Gmail, use an App Password (not your regular password).' ?></p>
                    </div>
                </div>
            </div>

            <!-- Sender Settings -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <h3 class="font-medium text-gray-900 mb-4 flex items-center gap-2">
                    <i data-lucide="user" class="w-5 h-5 text-amber-600"></i> Sender Information
                </h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">From Name</label>
                        <input type="text" name="mail_from_name" value="<?= e($mailConfig['from_name'] ?? 'ShopSmart') ?>" placeholder="ShopSmart"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">From Email</label>
                        <input type="email" name="mail_from_email" value="<?= e($mailConfig['from_email'] ?? '') ?>" placeholder="noreply@yourdomain.com"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                        <p class="text-xs text-gray-400 mt-1">This email address will appear as the sender. Some providers require it to match the SMTP username.</p>
                    </div>
                </div>
            </div>

            <!-- Test Connection Section -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <h3 class="font-medium text-gray-900 mb-4 flex items-center gap-2">
                    <i data-lucide="activity" class="w-5 h-5 text-amber-600"></i> Test Connection
                </h3>
                <div class="flex flex-col sm:flex-row gap-3">
                    <input type="email" id="test_email" placeholder="Enter your email to receive a test message" 
                           class="flex-1 px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                    <button type="button" id="btnTestConnection" onclick="testSMTPConnection()" 
                            class="px-5 py-2.5 bg-gray-900 text-white rounded-lg text-sm font-medium hover:bg-gray-800 transition-colors flex items-center justify-center gap-2 whitespace-nowrap">
                        <i data-lucide="zap" class="w-4 h-4"></i>
                        Test Connection
                    </button>
                </div>
                <!-- Test Results -->
                <div id="testResults" class="mt-4 hidden">
                    <div id="testResultsContent"></div>
                </div>
            </div>
        </div>

        <!-- Right Sidebar -->
        <div class="space-y-6">
            <!-- Common Providers -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <h3 class="font-medium text-gray-900 mb-3 flex items-center gap-2">
                    <i data-lucide="zap" class="w-5 h-5 text-amber-600"></i> Quick Setup
                </h3>
                <p class="text-xs text-gray-500 mb-3">Click a provider to auto-fill common settings:</p>
                <div class="space-y-2">
                    <button type="button" onclick="fillProvider('gmail')" class="w-full text-left px-3 py-2.5 border border-gray-200 rounded-lg text-sm hover:bg-gray-50 hover:border-amber-300 transition-colors flex items-center gap-2">
                        <span class="w-5 h-5 bg-red-100 text-red-600 rounded flex items-center justify-center text-xs font-bold">G</span>
                        Gmail / Google Workspace
                    </button>
                    <button type="button" onclick="fillProvider('outlook')" class="w-full text-left px-3 py-2.5 border border-gray-200 rounded-lg text-sm hover:bg-gray-50 hover:border-amber-300 transition-colors flex items-center gap-2">
                        <span class="w-5 h-5 bg-blue-100 text-blue-600 rounded flex items-center justify-center text-xs font-bold">O</span>
                        Outlook / Office 365
                    </button>
                    <button type="button" onclick="fillProvider('sendgrid')" class="w-full text-left px-3 py-2.5 border border-gray-200 rounded-lg text-sm hover:bg-gray-50 hover:border-amber-300 transition-colors flex items-center gap-2">
                        <span class="w-5 h-5 bg-teal-100 text-teal-600 rounded flex items-center justify-center text-xs font-bold">S</span>
                        SendGrid
                    </button>
                    <button type="button" onclick="fillProvider('mailgun')" class="w-full text-left px-3 py-2.5 border border-gray-200 rounded-lg text-sm hover:bg-gray-50 hover:border-amber-300 transition-colors flex items-center gap-2">
                        <span class="w-5 h-5 bg-purple-100 text-purple-600 rounded flex items-center justify-center text-xs font-bold">M</span>
                        Mailgun
                    </button>
                    <button type="button" onclick="fillProvider('localhost')" class="w-full text-left px-3 py-2.5 border border-amber-200 bg-amber-50 rounded-lg text-sm hover:bg-amber-100 transition-colors flex items-center gap-2">
                        <span class="w-5 h-5 bg-amber-200 text-amber-700 rounded flex items-center justify-center text-xs font-bold">L</span>
                        <span>
                            <span class="font-medium text-amber-800">Local SMTP (Shared Hosting)</span>
                            <span class="block text-xs text-amber-600">Use if external SMTP is blocked</span>
                        </span>
                    </button>
                </div>
            </div>

            <!-- Troubleshooting -->
            <div class="bg-amber-50 rounded-xl border border-amber-200 p-5">
                <h3 class="font-medium text-amber-900 mb-3 flex items-center gap-2 text-sm">
                    <i data-lucide="help-circle" class="w-4 h-4 text-amber-600"></i>
                    "Could not connect to SMTP host" Fix
                </h3>
                <ul class="text-xs text-amber-800 space-y-2">
                    <li class="flex items-start gap-2">
                        <span class="text-amber-500 font-bold mt-0.5">1.</span>
                        <span><strong>Try port 465 with SSL</strong> — Some hosts block port 587 but allow 465. Change port to 465 and encryption to SSL.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-amber-500 font-bold mt-0.5">2.</span>
                        <span><strong>Try Local SMTP</strong> — Click "Local SMTP" above. Host: <code class="bg-amber-100 px-1 rounded">localhost</code>, Port: <code class="bg-amber-100 px-1 rounded">25</code>, Encryption: <code class="bg-amber-100 px-1 rounded">None</code>, no username/password.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-amber-500 font-bold mt-0.5">3.</span>
                        <span><strong>Ask your hosting provider</strong> which SMTP port they allow. Common: 587, 465, 2525, or only 25.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-amber-500 font-bold mt-0.5">4.</span>
                        <span><strong>Gmail users</strong> — Must use an <a href="https://myaccount.google.com/apppasswords" target="_blank" class="underline text-amber-900 font-medium">App Password</a>, not your regular password. Enable 2FA first.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-amber-500 font-bold mt-0.5">5.</span>
                        <span><strong>Use "Test Connection"</strong> button below the form — it shows exactly which step fails and suggests open ports.</span>
                    </li>
                </ul>
            </div>

            <!-- Actions -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 space-y-3">
                <button type="submit" class="w-full bg-amber-600 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors">
                    Save Settings
                </button>
                <a href="/admin/newsletter/subscribers" class="block text-center border border-gray-200 text-gray-700 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors">
                    Back to Subscribers
                </a>
            </div>
        </div>
    </form>
</div>

<script>
lucide.createIcons();

const providers = {
    gmail:     { host: 'smtp.gmail.com', port: 587, encryption: 'tls', user: '', pass: '' },
    outlook:   { host: 'smtp.office365.com', port: 587, encryption: 'tls', user: '', pass: '' },
    sendgrid:  { host: 'smtp.sendgrid.net', port: 587, encryption: 'tls', user: 'apikey', pass: '' },
    mailgun:   { host: 'smtp.mailgun.org', port: 587, encryption: 'tls', user: '', pass: '' },
    localhost: { host: 'localhost', port: 25, encryption: '', user: '', pass: '' },
};

function fillProvider(name) {
    const p = providers[name];
    if (!p) return;
    document.getElementById('mail_host').value = p.host;
    document.getElementById('mail_port').value = p.port;
    document.getElementById('mail_encryption').value = p.encryption;
    if (p.user) document.querySelector('[name="mail_username"]').value = p.user;
    if (name === 'localhost') {
        document.querySelector('[name="mail_username"]').value = '';
        document.querySelector('[name="mail_password"]').value = '';
    }
}

function testSMTPConnection() {
    const btn = document.getElementById('btnTestConnection');
    const results = document.getElementById('testResults');
    const resultsContent = document.getElementById('testResultsContent');
    const testEmail = document.getElementById('test_email').value.trim();

    // Save settings first via AJAX, then test
    const form = document.getElementById('mailSettingsForm');
    const formData = new FormData(form);

    btn.disabled = true;
    btn.innerHTML = '<svg class="animate-spin w-4 h-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Testing...';
    results.classList.remove('hidden');
    resultsContent.innerHTML = '<div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm text-blue-700">Saving settings and testing connection...</div>';

    // Save settings first
    fetch('/admin/newsletter/settings', {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: formData,
    }).then(() => {
        // Now test connection
        const testFormData = new URLSearchParams();
        if (testEmail) testFormData.append('test_email', testEmail);

        return fetch('/admin/newsletter/test-connection', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: testFormData.toString(),
        });
    }).then(r => r.json()).then(data => {
        renderTestResults(data);
    }).catch(err => {
        resultsContent.innerHTML = `<div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700">Request failed: ${err.message}</div>`;
    }).finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg> Test Connection';
        lucide.createIcons();
    });
}

function renderTestResults(data) {
    const resultsContent = document.getElementById('testResultsContent');
    const iconMap = {
        ok:   '<svg class="w-4 h-4 text-green-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
        fail: '<svg class="w-4 h-4 text-red-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>',
        warn: '<svg class="w-4 h-4 text-amber-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
        info: '<svg class="w-4 h-4 text-blue-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>',
    };

    const bgClass = data.success ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200';
    const titleIcon = data.success
        ? '<svg class="w-5 h-5 text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>'
        : '<svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
    const titleText = data.success
        ? (data.method === 'mail' ? 'Email Sent (via PHP mail() fallback)!' : 'Connection Successful!')
        : 'Connection Failed';
    const titleColor = data.success ? 'text-green-800' : 'text-red-800';

    let html = `<div class="border rounded-lg p-4 ${bgClass}">`;
    html += `<div class="flex items-center gap-2 mb-3">${titleIcon}<span class="font-medium ${titleColor}">${titleText}</span>`;
    html += `<span class="text-xs text-gray-500 ml-auto">${data.host ? data.host + ':' + data.port + ' (' + (data.encryption || 'none') + ')' : 'PHP mail()'}</span></div>`;
    if (data.method === 'mail' || data.method === 'mail_fallback') {
        html += `<div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-3 text-xs text-amber-800"><strong>Using PHP mail() fallback.</strong> SMTP is blocked by your hosting, but emails are sent via the server's local mail system. This works fine for most cases.</div>`;
    }

    if (data.error) {
        html += `<div class="bg-white/60 rounded-lg p-3 mb-3 text-xs text-red-700 font-mono leading-relaxed">${escapeHtml(data.error)}</div>`;
    }

    if (data.checks && data.checks.length) {
        html += '<div class="space-y-2">';
        data.checks.forEach(check => {
            const checkBg = check.status === 'ok' ? 'bg-green-100' : check.status === 'fail' ? 'bg-red-100' : check.status === 'warn' ? 'bg-amber-100' : 'bg-blue-100';
            html += `<div class="flex items-start gap-2.5 text-xs">`;
            html += `<div class="${checkBg} rounded-full p-1 mt-0.5">${iconMap[check.status] || iconMap.info}</div>`;
            html += `<div><span class="font-medium text-gray-800">${escapeHtml(check.name)}</span>`;
            html += `<p class="text-gray-600 mt-0.5 leading-relaxed">${escapeHtml(check.message)}</p></div></div>`;
        });
        html += '</div>';
    }

    html += '</div>';
    resultsContent.innerHTML = html;
}

function escapeHtml(text) {
    if (!text) return '';
    const d = document.createElement('div');
    d.textContent = text;
    return d.innerHTML;
}
</script>