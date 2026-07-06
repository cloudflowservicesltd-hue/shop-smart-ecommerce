<?php
$mailConfigured = class_exists('Mailer') && Mailer::isConfigured();
$mailConfig = class_exists('Mailer') ? Mailer::getConfig() : [];
$totalActive = Database::count('newsletter_subscribers', 'is_active = 1');
$preselectedTo = Request::query('to', '');

// Available merge tags
$mergeTags = [
    '{store_name}' => 'ShopSmart',
    '{unsubscribe_url}' => 'Unsubscribe link',
    '{current_date}' => date('F j, Y'),
    '{current_year}' => date('Y'),
];
?>
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <a href="/admin/newsletter/subscribers" class="hover:text-amber-600 transition-colors">Newsletter</a>
                <i data-lucide="chevron-right" class="w-4 h-4"></i>
                <span class="text-gray-900 font-medium">Compose Email</span>
            </div>
            <h1 class="font-heading font-semibold text-xl text-gray-900 mt-1">Compose Email Campaign</h1>
        </div>
    </div>

    <?php if (!$mailConfigured): ?>
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 flex items-start gap-3">
        <i data-lucide="alert-octagon" class="w-5 h-5 text-red-600 mt-0.5 shrink-0"></i>
        <div>
            <p class="text-sm font-medium text-red-800">SMTP not configured</p>
            <p class="text-sm text-red-700 mt-0.5">You must <a href="/admin/newsletter/settings" class="underline font-medium">configure mail settings</a> before sending emails.</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Compose Form -->
    <form method="POST" action="/admin/newsletter/send" id="composeForm" enctype="multipart/form-data" class="space-y-6">

        <!-- Recipients -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <h3 class="font-medium text-gray-900 mb-4 flex items-center gap-2">
                <i data-lucide="users" class="w-5 h-5 text-amber-600"></i> Recipients
            </h3>
            <div class="space-y-4">
                <div class="flex flex-wrap gap-3">
                    <label class="flex items-center gap-2.5 cursor-pointer px-4 py-3 border <?= empty($preselectedTo) ? 'border-amber-400 bg-amber-50' : 'border-gray-200 hover:bg-gray-50' ?> rounded-xl transition-colors has-[:checked]:border-amber-400 has-[:checked]:bg-amber-50">
                        <input type="radio" name="recipient_type" value="all" <?= empty($preselectedTo) ? 'checked' : '' ?> class="text-amber-600 focus:ring-amber-500">
                        <div>
                            <span class="text-sm font-medium text-gray-900">All Active Subscribers</span>
                            <span class="block text-xs text-gray-500"><?= number_format($totalActive) ?> emails</span>
                        </div>
                    </label>
                    <label class="flex items-center gap-2.5 cursor-pointer px-4 py-3 border border-gray-200 hover:bg-gray-50 rounded-xl transition-colors has-[:checked]:border-amber-400 has-[:checked]:bg-amber-50">
                        <input type="radio" name="recipient_type" value="custom" <?= !empty($preselectedTo) ? 'checked' : '' ?> class="text-amber-600 focus:ring-amber-500">
                        <div>
                            <span class="text-sm font-medium text-gray-900">Custom Recipients</span>
                            <span class="block text-xs text-gray-500">Enter emails manually</span>
                        </div>
                    </label>
                </div>
                <div id="customRecipients" class="<?= !empty($preselectedTo) ? '' : 'hidden' ?>">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Addresses</label>
                    <textarea name="custom_emails" rows="3" placeholder="Enter emails separated by commas or one per line" 
                              class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 resize-none"><?= e($preselectedTo) ?></textarea>
                    <p class="text-xs text-gray-400 mt-1">Separate multiple emails with commas or new lines</p>
                </div>
            </div>
        </div>

        <!-- Email Content -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <h3 class="font-medium text-gray-900 mb-4 flex items-center gap-2">
                <i data-lucide="mail" class="w-5 h-5 text-amber-600"></i> Email Content
            </h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">From Name</label>
                    <input type="text" name="from_name" value="<?= e($mailConfig['from_name'] ?? 'ShopSmart') ?>" 
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reply-To Email <span class="text-gray-400 font-normal">(optional)</span></label>
                    <input type="email" name="reply_to" placeholder="reply@yourdomain.com" 
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subject <span class="text-red-500">*</span></label>
                    <input type="text" name="subject" required placeholder="e.g. Special Weekend Deals - Up to 50% Off!" 
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-medium text-gray-700">Message <span class="text-red-500">*</span></label>
                        <div class="flex items-center gap-2">
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" name="is_html" value="1" checked class="w-3.5 h-3.5 text-amber-600 border-gray-300 rounded focus:ring-amber-500" onchange="toggleHtmlMode(this.checked)">
                                <span class="text-xs text-gray-500">HTML mode</span>
                            </label>
                        </div>
                    </div>
                    <textarea name="body" required rows="14" placeholder="Write your email message here..." 
                              class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 font-mono leading-relaxed resize-y"></textarea>
                    <p class="text-xs text-gray-400 mt-1">When in HTML mode, you can use HTML tags. A plain-text version is generated automatically.</p>
                </div>
            </div>
        </div>

        <!-- Attachment -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <h3 class="font-medium text-gray-900 mb-3 flex items-center gap-2">
                <i data-lucide="paperclip" class="w-5 h-5 text-amber-600"></i> Attachments
            </h3>
            <div id="dropZone" class="border-2 border-dashed border-gray-200 rounded-xl p-6 text-center hover:border-amber-300 hover:bg-amber-50/30 transition-colors cursor-pointer" onclick="document.getElementById('attachmentInput').click()">
                <input type="file" id="attachmentInput" name="attachments[]" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.zip" class="hidden" onchange="handleFileSelect(this.files)">
                <i data-lucide="upload-cloud" class="w-10 h-10 text-gray-300 mx-auto mb-3"></i>
                <p class="text-sm text-gray-600 font-medium">Click to upload or drag & drop files here</p>
                <p class="text-xs text-gray-400 mt-1">Images, PDF, DOC, XLS, ZIP — Max 5MB each</p>
            </div>
            <div id="fileList" class="mt-3 space-y-2 hidden"></div>
        </div>

        <!-- Merge Tags Quick Insert -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <h3 class="font-medium text-gray-900 mb-3 flex items-center gap-2">
                <i data-lucide="code" class="w-5 h-5 text-amber-600"></i> Quick Insert — Merge Tags
            </h3>
            <p class="text-xs text-gray-500 mb-3">Click a tag to insert it at the cursor position in the message.</p>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($mergeTags as $tag => $desc): ?>
                <button type="button" onclick="insertTag('<?= $tag ?>')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-50 hover:bg-amber-50 border border-gray-200 hover:border-amber-300 rounded-lg text-xs font-mono text-gray-700 hover:text-amber-700 transition-colors">
                    <span><?= $tag ?></span>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Test Email -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <h3 class="font-medium text-gray-900 mb-3 flex items-center gap-2">
                <i data-lucide="flask-conical" class="w-5 h-5 text-amber-600"></i> Send Test Email
            </h3>
            <div class="flex gap-3">
                <input type="email" id="testEmail" placeholder="Enter your email to send a test" 
                       class="flex-1 px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                <button type="button" onclick="sendTest()" class="inline-flex items-center gap-2 border border-gray-200 text-gray-700 px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors">
                    <i data-lucide="play" class="w-4 h-4"></i> Send Test
                </button>
            </div>
            <p id="testResult" class="text-xs mt-2 hidden"></p>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between">
            <a href="/admin/newsletter/subscribers" class="text-sm text-gray-500 hover:text-gray-700 transition-colors">&larr; Back to Subscribers</a>
            <button type="submit" id="sendBtn" <?= !$mailConfigured ? 'disabled' : '' ?> 
                    class="inline-flex items-center gap-2 bg-amber-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                <i data-lucide="send" class="w-4 h-4"></i> Send Campaign
            </button>
        </div>
        <?= csrf() ?>
    </form>
</div>

<script>
lucide.createIcons();

// Toggle recipient type
document.querySelectorAll('input[name="recipient_type"]').forEach(r => {
    r.addEventListener('change', function() {
        document.getElementById('customRecipients').classList.toggle('hidden', this.value !== 'custom');
    });
});

// Toggle HTML mode label
function toggleHtmlMode(isHtml) {
    const ta = document.querySelector('textarea[name="body"]');
    if (isHtml) {
        ta.placeholder = 'Write your HTML email here...\n\nExample:\n<h1>Hello!</h1>\n<p>Check out our latest deals.</p>';
    } else {
        ta.placeholder = 'Write your plain-text email here...';
    }
}

// Insert merge tag at cursor
function insertTag(tag) {
    const ta = document.querySelector('textarea[name="body"]');
    const start = ta.selectionStart;
    const end = ta.selectionEnd;
    const val = ta.value;
    ta.value = val.substring(0, start) + tag + val.substring(end);
    ta.selectionStart = ta.selectionEnd = start + tag.length;
    ta.focus();
}

// Send test email
function sendTest() {
    const email = document.getElementById('testEmail').value.trim();
    const result = document.getElementById('testResult');
    if (!email || !email.includes('@')) {
        result.textContent = 'Please enter a valid email address.';
        result.className = 'text-xs mt-2 text-red-600';
        result.classList.remove('hidden');
        return;
    }

    const fd = new FormData();
    fd.append('_token', '<?= csrf_token() ?>');
    fd.append('to', email);
    fd.append('subject', document.querySelector('input[name="subject"]').value || 'Test Email');
    fd.append('body', document.querySelector('textarea[name="body"]').value || '<p>This is a test email.</p>');
    fd.append('is_html', document.querySelector('input[name="is_html"]').checked ? '1' : '0');
    fd.append('from_name', document.querySelector('input[name="from_name"]').value || '');
    // Attach files
    const fileInput = document.getElementById('attachmentInput');
    if (fileInput.files) {
        for (let i = 0; i < fileInput.files.length; i++) {
            fd.append('attachments[]', fileInput.files[i]);
        }
    }

    result.textContent = 'Sending...';
    result.className = 'text-xs mt-2 text-amber-600';
    result.classList.remove('hidden');

    fetch('/admin/newsletter/send-test', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            result.textContent = '✓ Test email sent successfully!';
            result.className = 'text-xs mt-2 text-green-600';
        } else {
            result.textContent = '✗ Failed: ' + (d.error || 'Unknown error');
            result.className = 'text-xs mt-2 text-red-600';
        }
    })
    .catch(() => {
        result.textContent = '✗ Request failed. Check your connection.';
        result.className = 'text-xs mt-2 text-red-600';
    });
}

// File drag & drop and selection
const dropZone = document.getElementById('dropZone');
const fileList = document.getElementById('fileList');

['dragenter','dragover'].forEach(ev => {
    dropZone.addEventListener(ev, e => { e.preventDefault(); dropZone.classList.add('border-amber-400','bg-amber-50/50'); });
});
['dragleave','drop'].forEach(ev => {
    dropZone.addEventListener(ev, e => { e.preventDefault(); dropZone.classList.remove('border-amber-400','bg-amber-50/50'); });
});
dropZone.addEventListener('drop', e => {
    const input = document.getElementById('attachmentInput');
    input.files = e.dataTransfer.files;
    handleFileSelect(e.dataTransfer.files);
});

function handleFileSelect(files) {
    if (!files || files.length === 0) { fileList.classList.add('hidden'); return; }
    fileList.classList.remove('hidden');
    fileList.innerHTML = '';
    const maxSize = 5 * 1024 * 1024;
    Array.from(files).forEach((f, i) => {
        const sizeKB = (f.size / 1024).toFixed(1);
        const sizeStr = f.size > 1024*1024 ? (f.size / (1024*1024)).toFixed(1) + ' MB' : sizeKB + ' KB';
        const overSize = f.size > maxSize;
        const div = document.createElement('div');
        div.className = 'flex items-center gap-2 text-xs px-3 py-2 rounded-lg ' + (overSize ? 'bg-red-50 text-red-600' : 'bg-gray-50 text-gray-600');
        div.innerHTML = '<i data-lucide="paperclip" class="w-3.5 h-3.5 shrink-0"></i>' +
            '<span class="truncate flex-1">' + f.name + '</span>' +
            '<span class="text-gray-400 shrink-0">' + sizeStr + '</span>' +
            (overSize ? '<span class="text-red-500 font-medium shrink-0">Too large!</span>' : '');
        fileList.appendChild(div);
    });
    lucide.createIcons();
}
</script>