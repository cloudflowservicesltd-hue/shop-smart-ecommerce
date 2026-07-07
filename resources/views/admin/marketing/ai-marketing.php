<?php
$providers = class_exists('AIChat') ? AIChat::getAllProviders() : [];
$activeProvider = '';
if (class_exists('AIChat')) {
    $cfg = AIChat::getProviderConfig();
    if ($cfg) $activeProvider = $cfg['provider'];
}
$products = Database::select("SELECT id, name, price, short_description, description FROM products WHERE is_active = 1 ORDER BY name LIMIT 200");
$storeName = Database::selectOne("SELECT value FROM settings WHERE `key` = 'store_name'")['value'] ?? 'ShopSmart';
?>
<div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <a href="/admin/marketing/facebook" class="hover:text-amber-600">Marketing</a>
                <i data-lucide="chevron-right" class="w-4 h-4"></i>
                <span class="text-gray-900 font-medium">AI Assistant</span>
            </div>
            <h1 class="font-heading font-semibold text-xl text-gray-900 mt-1 flex items-center gap-2">
                <i data-lucide="sparkles" class="w-5 h-5 text-amber-600"></i> AI Marketing Assistant
            </h1>
        </div>
        <div class="flex gap-2">
            <select id="providerSelect" class="px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white">
                <?php foreach ($providers as $id => $p): ?>
                <option value="<?= $id ?>" <?= $activeProvider === $id ? 'selected' : '' ?> ><?= $p['icon'] ?> <?= e($p['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200 flex gap-4 overflow-x-auto">
        <button onclick="switchTab('chat')" id="tabChat" class="tab-btn pb-3 text-sm font-medium border-b-2 border-amber-600 text-amber-600 whitespace-nowrap">💬 AI Chat</button>
        <button onclick="switchTab('schedule')" id="tabSchedule" class="tab-btn pb-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 whitespace-nowrap">📅 Schedule Post</button>
        <button onclick="switchTab('settings')" id="tabSettings" class="tab-btn pb-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 whitespace-nowrap">🔑 API Settings</button>
    </div>

    <!-- ═══════════════ TAB: AI Chat ═══════════════ -->
    <div id="panelChat" class="tab-panel">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Chat Area -->
            <div class="lg:col-span-3 bg-white rounded-xl border border-gray-100 shadow-sm flex flex-col" style="height: 72vh; min-height: 500px;">
                <!-- Chat Header -->
                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center"><i data-lucide="bot" class="w-4 h-4 text-amber-600"></i></div>
                        <span class="text-sm font-medium">AI Marketing Assistant</span>
                        <span class="text-xs text-gray-400" id="chatProviderLabel"><?= $activeProvider ? ($providers[$activeProvider]['name'] ?? '') : 'Not configured' ?></span>
                    </div>
                    <button onclick="clearChat()" class="text-xs text-gray-500 hover:text-red-500 flex items-center gap-1"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Clear</button>
                </div>

                <!-- Messages -->
                <div id="chatMessages" class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50/50">
                    <div class="flex gap-3">
                        <div class="w-7 h-7 bg-amber-100 rounded-full flex items-center justify-center shrink-0 mt-1"><i data-lucide="bot" class="w-3.5 h-3.5 text-amber-600"></i></div>
                        <div class="bg-white border border-gray-100 rounded-xl rounded-tl-none p-3 max-w-[85%] text-sm text-gray-700 shadow-sm">
                            <p>Hello! I'm your AI marketing assistant. I can help you:</p>
                            <ul class="mt-2 space-y-1 text-xs text-gray-500">
                                <li>✨ Generate Facebook ads & social media posts</li>
                                <li>📧 Write email campaigns & subject lines</li>
                                <li>💬 Create WhatsApp marketing messages</li>
                                <li>🔍 Write SEO product descriptions</li>
                                <li>🏷️ Suggest hashtags & marketing strategies</li>
                            </ul>
                            <p class="mt-2 text-xs text-gray-400">Select a product below or just type your request!</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="px-4 py-2 border-t border-gray-100 flex gap-2 overflow-x-auto shrink-0">
                    <button onclick="insertPrompt('Generate a compelling Facebook ad for the selected product')" class="whitespace-nowrap text-xs px-3 py-1.5 rounded-full bg-blue-50 text-blue-700 hover:bg-blue-100 font-medium">📄 Facebook Ad</button>
                    <button onclick="insertPrompt('Write an email campaign with subject line for the selected product')" class="whitespace-nowrap text-xs px-3 py-1.5 rounded-full bg-amber-50 text-amber-700 hover:bg-amber-100 font-medium">📧 Email Campaign</button>
                    <button onclick="insertPrompt('Create a promotional WhatsApp message for the selected product')" class="whitespace-nowrap text-xs px-3 py-1.5 rounded-full bg-green-50 text-green-700 hover:bg-green-100 font-medium">💬 WhatsApp Msg</button>
                    <button onclick="insertPrompt('Write an SEO-optimized product description')" class="whitespace-nowrap text-xs px-3 py-1.5 rounded-full bg-purple-50 text-purple-700 hover:bg-purple-100 font-medium">🔍 SEO Description</button>
                    <button onclick="insertPrompt('Suggest 20 relevant marketing hashtags')" class="whitespace-nowrap text-xs px-3 py-1.5 rounded-full bg-pink-50 text-pink-700 hover:bg-pink-100 font-medium">🏷️ Hashtags</button>
                </div>

                <!-- Input -->
                <div class="px-4 py-3 border-t border-gray-100 shrink-0">
                    <div class="flex gap-2">
                        <select id="productContext" class="px-2 py-2 border border-gray-200 rounded-lg text-xs bg-white max-w-[180px]">
                            <option value="">No product context</option>
                            <?php foreach ($products as $p): ?>
                            <option value='{"name":"<?= e(addslashes($p['name'])) ?>","price":"<?= $p['price'] ?>","desc":"<?= e(addslashes($p['short_description'] ?? $p['description'] ?? '')) ?>"}'><?= e($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="flex-1 relative">
                            <textarea id="chatInput" rows="1" placeholder="Type your marketing request..." class="w-full px-3 py-2 pr-12 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 resize-none" onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendMessage()}"></textarea>
                        </div>
                        <button onclick="sendMessage()" id="sendBtn" class="bg-amber-600 text-white px-4 py-2 rounded-lg hover:bg-amber-700 transition-colors flex items-center gap-1.5 shrink-0">
                            <i data-lucide="send" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Sidebar: Usage Stats -->
            <div class="space-y-4">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                    <h3 class="text-sm font-medium mb-3 flex items-center gap-1"><i data-lucide="bar-chart-3" class="w-4 h-4 text-amber-600"></i> Session</h3>
                    <div class="space-y-2 text-xs text-gray-500">
                        <div class="flex justify-between"><span>Messages</span><span id="msgCount" class="font-medium text-gray-700">1</span></div>
                        <div class="flex justify-between"><span>Provider</span><span id="sessionProvider" class="font-medium text-gray-700"><?= ucfirst($activeProvider ?: 'none') ?></span></div>
                        <div class="flex justify-between"><span>Tokens Used</span><span id="tokensUsed" class="font-medium text-gray-700">0</span></div>
                    </div>
                </div>
                <div class="bg-amber-50 rounded-xl border border-amber-200 p-4">
                    <h3 class="text-xs font-semibold text-amber-800 mb-2">💡 Tips</h3>
                    <ul class="text-xs text-amber-700 space-y-1">
                        <li>• Select a product for context-aware content</li>
                        <li>• Use Shift+Enter for new lines</li>
                        <li>• Click quick actions for common tasks</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════ TAB: Schedule Post ═══════════════ -->
    <div id="panelSchedule" class="tab-panel hidden">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="font-medium mb-4 flex items-center gap-2"><i data-lucide="calendar-clock" class="w-4 h-4 text-amber-600"></i> Schedule AI-Generated Post</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">What should the AI post about?</label>
                            <textarea id="schedulePrompt" rows="3" placeholder="e.g. Flash sale on Samsung phones, 30% off this weekend..." class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 resize-none"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Product (optional)</label>
                            <select id="scheduleProduct" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white">
                                <option value="">No specific product</option>
                                <?php foreach ($products as $p): ?>
                                <option value="<?= e($p['name']) ?>"><?= e($p['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Post To</label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2" id="platformChecks">
                                <label class="flex items-center gap-2 p-2.5 border border-gray-200 rounded-lg cursor-pointer hover:border-amber-300 transition-colors">
                                    <input type="checkbox" value="facebook" class="schedule-platform rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                                    <span class="text-sm">📘 Facebook</span>
                                </label>
                                <label class="flex items-center gap-2 p-2.5 border border-gray-200 rounded-lg cursor-pointer hover:border-amber-300 transition-colors">
                                    <input type="checkbox" value="instagram" class="schedule-platform rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                                    <span class="text-sm">📸 Instagram</span>
                                </label>
                                <label class="flex items-center gap-2 p-2.5 border border-gray-200 rounded-lg cursor-pointer hover:border-amber-300 transition-colors">
                                    <input type="checkbox" value="tiktok" class="schedule-platform rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                                    <span class="text-sm">🎵 TikTok</span>
                                </label>
                                <label class="flex items-center gap-2 p-2.5 border border-gray-200 rounded-lg cursor-pointer hover:border-amber-300 transition-colors">
                                    <input type="checkbox" value="whatsapp" class="schedule-platform rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                                    <span class="text-sm">💬 WhatsApp</span>
                                </label>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Schedule Date & Time</label>
                            <input type="datetime-local" id="scheduleDate" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500">
                        </div>
                        <div id="schedulePreview" class="hidden bg-gray-50 rounded-xl p-4 border border-gray-200">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-semibold text-gray-500 uppercase">AI Preview</span>
                                <button onclick="editAndReschedule()" class="text-xs text-amber-600 hover:underline">Edit & Reschedule</button>
                            </div>
                            <p id="previewContent" class="text-sm text-gray-700 whitespace-pre-wrap"></p>
                        </div>
                        <div class="flex gap-3">
                            <button onclick="scheduleAIPost()" id="scheduleBtn" class="bg-amber-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700 flex items-center gap-2"><i data-lucide="clock" class="w-4 h-4"></i> Generate & Schedule</button>
                            <button onclick="processScheduledNow()" class="border border-gray-200 text-gray-700 px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-50 flex items-center gap-2"><i data-lucide="play" class="w-4 h-4"></i> Process Due Now</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="space-y-4">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                    <h3 class="text-sm font-medium mb-3 flex items-center gap-1"><i data-lucide="calendar-days" class="w-4 h-4 text-amber-600"></i> Scheduled</h3>
                    <div id="scheduledList" class="space-y-2 max-h-80 overflow-y-auto">
                        <p class="text-xs text-gray-400 text-center py-4">Loading...</p>
                    </div>
                </div>
                <div class="bg-amber-50 rounded-xl border border-amber-200 p-4">
                    <h3 class="text-xs font-semibold text-amber-800 mb-2">💡 Scheduling Tips</h3>
                    <ul class="text-xs text-amber-700 space-y-1">
                        <li>• Best times: 9AM, 12PM, 6PM</li>
                        <li>• AI generates unique content per platform</li>
                        <li>• Click "Process Due Now" to publish past-due posts</li>
                        <li>• Set up API keys in each platform's settings</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════ TAB: API Settings ═══════════════ -->
    <div id="panelSettings" class="tab-panel hidden">
        <form id="aiSettingsForm" class="space-y-6">
            <!-- Active Provider -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <h3 class="font-medium mb-4 flex items-center gap-2"><i data-lucide="zap" class="w-4 h-4 text-amber-600"></i> Active Provider</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3" id="activeProviderRadios">
                    <?php foreach ($providers as $id => $p): ?>
                    <label class="relative cursor-pointer">
                        <input type="radio" name="ai_active_provider" value="<?= $id ?>" <?= $activeProvider === $id ? 'checked' : '' ?> class="sr-only peer">
                        <div class="border-2 border-gray-200 peer-checked:border-amber-500 rounded-xl p-3 text-center transition-colors hover:border-gray-300">
                            <div class="text-2xl mb-1"><?= $p['icon'] ?></div>
                            <div class="text-xs font-medium"><?= e($p['name']) ?></div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Provider Cards -->
            <?php foreach ($providers as $id => $p): ?>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-medium flex items-center gap-2"><?= $p['icon'] ?> <?= e($p['name']) ?></h3>
                    <a href="<?= $p['docs'] ?>" target="_blank" class="text-xs text-amber-600 hover:underline flex items-center gap-1"><i data-lucide="external-link" class="w-3 h-3"></i> Get API Key</a>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">API Key</label>
                        <div class="relative">
                            <input type="password" name="ai_<?= $id ?>_api_key" value="<?= e($p['api_key']) ?>" placeholder="sk-..." autocomplete="off" class="w-full px-3 py-2.5 pr-10 border border-gray-200 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                            <button type="button" onclick="toggleKeyVisibility(this)" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"><i data-lucide="eye-off" class="w-4 h-4"></i></button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Model</label>
                        <select name="ai_<?= $id ?>_model" id="modelSelect_<?= $id ?>" onchange="toggleCustomModel('<?= $id ?>')" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 bg-white">
                            <?php foreach ($p['models'] as $m): ?>
                            <option value="<?= e($m) ?>" <?= $p['model'] === $m ? 'selected' : '' ?> ><?= e($m) ?></option>
                            <?php endforeach; ?>
                            <option value="__custom__" <?= !in_array($p['model'], $p['models']) ? 'selected' : '' ?> >✏️ Custom Model...</option>
                        </select>
                        <input type="text" name="ai_<?= $id ?>_model_custom" id="customModel_<?= $id ?>" placeholder="Enter custom model name" value="<?= !in_array($p['model'], $p['models']) ? e($p['model']) : '' ?>" class="w-full mt-2 px-3 py-2 border border-gray-200 rounded-lg text-xs font-mono focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 <?= !in_array($p['model'], $p['models']) ? '' : 'hidden' ?>">
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Actions -->
            <div class="flex gap-3">
                <button type="button" onclick="saveAISettings()" class="bg-amber-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700 flex items-center gap-2"><i data-lucide="save" class="w-4 h-4"></i> Save Settings</button>
                <button type="button" onclick="testAIConnection()" id="testAiBtn" class="border border-gray-200 text-gray-700 px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-50 flex items-center gap-2"><i data-lucide="activity" class="w-4 h-4"></i> Test Connection</button>
                <div id="testAiResult" class="hidden flex-1"></div>
            </div>
        </form>
    </div>
</div>

<script>
lucide.createIcons();
let chatHistory = [];
let totalTokens = 0;
const providerNames = <?= json_encode(array_map(fn($p) => $p['name'], $providers ?? [])) ?>;

function switchTab(tab) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(b => { b.classList.remove('border-amber-600', 'text-amber-600'); b.classList.add('border-transparent', 'text-gray-500'); });
    const panelMap = {chat:'panelChat',schedule:'panelSchedule',settings:'panelSettings'};
    const tabMap = {chat:'tabChat',schedule:'tabSchedule',settings:'tabSettings'};
    document.getElementById(panelMap[tab]).classList.remove('hidden');
    document.getElementById(tabMap[tab]).classList.add('border-amber-600', 'text-amber-600');
    if (tab === 'schedule') loadScheduledPosts();
    lucide.createIcons();
}

function insertPrompt(text) {
    const input = document.getElementById('chatInput');
    const ctx = document.getElementById('productContext');
    let fullText = text;
    if (ctx.value) {
        const p = JSON.parse(ctx.value);
        fullText += ` Product: ${p.name}, Price: KSh ${parseInt(p.price).toLocaleString()}, Description: ${p.desc}`;
    }
    input.value = fullText;
    input.focus();
}

function addMessage(role, content) {
    const container = document.getElementById('chatMessages');
    const isUser = role === 'user';
    const time = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});

    const div = document.createElement('div');
    div.className = isUser ? 'flex gap-3 justify-end' : 'flex gap-3';

    const formattedContent = isUser ? escapeHtml(content) : renderMarkdown(content);

    div.innerHTML = isUser
        ? `<div class="bg-amber-600 text-white rounded-xl rounded-tr-none p-3 max-w-[85%] text-sm shadow-sm"><div>${formattedContent}</div><div class="text-[10px] mt-1 opacity-70">${time}</div></div><div class="w-7 h-7 bg-amber-600 rounded-full flex items-center justify-center shrink-0 mt-1 text-white text-xs font-bold">Y</div>`
        : `<div class="w-7 h-7 bg-amber-100 rounded-full flex items-center justify-center shrink-0 mt-1"><i data-lucide="bot" class="w-3.5 h-3.5 text-amber-600"></i></div><div class="bg-white border border-gray-100 rounded-xl rounded-tl-none p-3 max-w-[85%] text-sm text-gray-700 shadow-sm"><div>${formattedContent}</div><div class="text-[10px] mt-1 text-gray-400">${time}</div></div>`;

    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
    document.getElementById('msgCount').textContent = container.children.length;
    lucide.createIcons();
}

function renderMarkdown(text) {
    let html = escapeHtml(text);
    // Code blocks
    html = html.replace(/```(\w*)\n([\s\S]*?)```/g, '<pre class="bg-gray-800 text-green-400 rounded-lg p-3 my-2 overflow-x-auto text-xs"><code>$2</code></pre>');
    // Inline code
    html = html.replace(/`([^`]+)`/g, '<code class="bg-gray-100 px-1.5 py-0.5 rounded text-xs font-mono">$1</code>');
    // Bold
    html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    // Line breaks
    html = html.replace(/\n/g, '<br>');
    return html;
}

function escapeHtml(t) { const d = document.createElement('div'); d.textContent = t; return d.innerHTML; }

function showLoading() {
    const container = document.getElementById('chatMessages');
    const div = document.createElement('div');
    div.id = 'loadingIndicator';
    div.className = 'flex gap-3';
    div.innerHTML = '<div class="w-7 h-7 bg-amber-100 rounded-full flex items-center justify-center shrink-0 mt-1"><i data-lucide="bot" class="w-3.5 h-3.5 text-amber-600"></i></div><div class="bg-white border border-gray-100 rounded-xl rounded-tl-none p-3 shadow-sm"><div class="flex items-center gap-1.5"><div class="w-2 h-2 bg-amber-400 rounded-full animate-bounce" style="animation-delay:0ms"></div><div class="w-2 h-2 bg-amber-400 rounded-full animate-bounce" style="animation-delay:150ms"></div><div class="w-2 h-2 bg-amber-400 rounded-full animate-bounce" style="animation-delay:300ms"></div></div></div>';
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
    lucide.createIcons();
}

function hideLoading() { document.getElementById('loadingIndicator')?.remove(); }

async function sendMessage() {
    const input = document.getElementById('chatInput');
    const text = input.value.trim();
    if (!text) return;

    const ctx = document.getElementById('productContext').value;
    const provider = document.getElementById('providerSelect').value;
    let userMessage = text;
    if (ctx) {
        const p = JSON.parse(ctx);
        userMessage += `\n\n[Product Context: ${p.name}, Price: KSh ${parseInt(p.price).toLocaleString()}, Description: ${p.desc}]`;
    }

    chatHistory.push({ role: 'user', content: userMessage });
    addMessage('user', text);
    input.value = '';
    input.style.height = 'auto';
    showLoading();

    try {
        const res = await fetch('/admin/marketing/ai/chat', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ messages: chatHistory, provider }),
        });
        const data = await res.json();
        hideLoading();

        if (data.success) {
            chatHistory.push({ role: 'assistant', content: data.content });
            addMessage('assistant', data.content);
            if (data.usage) {
                totalTokens += (data.usage.total_tokens || (data.usage.input_tokens || 0) + (data.usage.output_tokens || 0));
                document.getElementById('tokensUsed').textContent = totalTokens.toLocaleString();
            }
            if (data.provider) {
                document.getElementById('sessionProvider').textContent = data.provider.charAt(0).toUpperCase() + data.provider.slice(1);
                document.getElementById('chatProviderLabel').textContent = providerNames[data.provider] || data.provider;
            }
        } else {
            addMessage('assistant', '❌ ' + (data.error || 'Unknown error occurred'));
        }
    } catch (e) {
        hideLoading();
        addMessage('assistant', '❌ Connection failed: ' + e.message);
    }
}

function clearChat() {
    chatHistory = [];
    totalTokens = 0;
    document.getElementById('chatMessages').innerHTML = '<div class="flex gap-3"><div class="w-7 h-7 bg-amber-100 rounded-full flex items-center justify-center shrink-0 mt-1"><i data-lucide="bot" class="w-3.5 h-3.5 text-amber-600"></i></div><div class="bg-white border border-gray-100 rounded-xl rounded-tl-none p-3 max-w-[85%] text-sm text-gray-700 shadow-sm">Chat cleared. How can I help you with your marketing?</div></div>';
    document.getElementById('msgCount').textContent = '1';
    document.getElementById('tokensUsed').textContent = '0';
    lucide.createIcons();
}

function toggleKeyVisibility(btn) {
    const input = btn.previousElementSibling;
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    btn.innerHTML = isPassword ? '<i data-lucide="eye" class="w-4 h-4"></i>' : '<i data-lucide="eye-off" class="w-4 h-4"></i>';
    lucide.createIcons();
}

async function saveAISettings() {
    const form = document.getElementById('aiSettingsForm');
    const formData = new FormData(form);
    try {
        const res = await fetch('/admin/marketing/ai/settings', { method: 'POST', headers: { 'Accept': 'application/json' }, body: formData });
        const data = await res.json();
        if (data.success) { showToast('Settings saved successfully!', 'success'); }
        else { showToast(data.error || 'Failed to save', 'error'); }
    } catch (e) { showToast('Connection failed', 'error'); }
}

async function testAIConnection() {
    const btn = document.getElementById('testAiBtn');
    const result = document.getElementById('testAiResult');
    btn.disabled = true; btn.innerHTML = '<svg class="animate-spin w-4 h-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Testing...';
    result.classList.remove('hidden');
    result.innerHTML = '<div class="text-sm text-gray-500">Testing AI connection...</div>';
    try {
        const res = await fetch('/admin/marketing/ai/test', { method: 'POST', headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (data.success) {
            result.innerHTML = `<div class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg px-3 py-2 flex items-center gap-2"><svg class="w-4 h-4 text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg> Connected to ${data.provider} — Response: "${data.content?.substring(0, 80)}..."</div>`;
        } else {
            result.innerHTML = `<div class="text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg px-3 py-2">❌ ${data.error}</div>`;
        }
    } catch (e) { result.innerHTML = '<div class="text-sm text-red-700">Connection failed</div>'; }
    btn.disabled = false; btn.innerHTML = '<i data-lucide="activity" class="w-4 h-4"></i> Test Connection'; lucide.createIcons();
}

function showToast(msg, type) {
    const t = document.createElement('div');
    t.className = `fixed top-4 right-4 z-50 px-4 py-2 rounded-lg text-sm font-medium shadow-lg ${type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'}`;
    t.textContent = msg; document.body.appendChild(t);
    setTimeout(() => t.remove(), 3000);
}

function toggleCustomModel(provider) {
    const sel = document.getElementById('modelSelect_' + provider);
    const inp = document.getElementById('customModel_' + provider);
    if (sel.value === '__custom__') { inp.classList.remove('hidden'); inp.focus(); }
    else { inp.classList.add('hidden'); inp.value = ''; }
}

async function loadScheduledPosts() {
    const list = document.getElementById('scheduledList');
    try {
        const res = await fetch('/admin/marketing/ai/scheduled-list', {headers:{'Accept':'application/json'}});
        const data = await res.json();
        if (data.success && data.posts.length > 0) {
            const icons = {facebook:'📘',instagram:'📸',tiktok:'🎵',whatsapp:'💬'};
            list.innerHTML = data.posts.map(p => `
                <div class="p-2 bg-gray-50 rounded-lg border border-gray-100">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-medium">${icons[p.platform] || ''} ${p.platform}</span>
                        <span class="text-[10px] text-gray-400">${p.schedule_date || ''}</span>
                    </div>
                    <p class="text-xs text-gray-600 line-clamp-2">${(p.content || '').substring(0, 80)}...</p>
                </div>`).join('');
        } else {
            list.innerHTML = '<p class="text-xs text-gray-400 text-center py-4">No scheduled posts</p>';
        }
    } catch(e) { list.innerHTML = '<p class="text-xs text-red-400 text-center py-4">Failed to load</p>'; }
}

async function scheduleAIPost() {
    const prompt = document.getElementById('schedulePrompt').value.trim();
    const product = document.getElementById('scheduleProduct').value;
    const platforms = [...document.querySelectorAll('.schedule-platform:checked')].map(c => c.value);
    const scheduleDate = document.getElementById('scheduleDate').value;

    if (!prompt) return showToast('Enter what to post about', 'error');
    if (!platforms.length) return showToast('Select at least one platform', 'error');

    const btn = document.getElementById('scheduleBtn');
    btn.disabled = true; btn.textContent = 'Generating...';

    try {
        const res = await fetch('/admin/marketing/ai/schedule', {
            method: 'POST', headers: {'Accept':'application/json', 'Content-Type':'application/json'},
            body: JSON.stringify({prompt, platforms, schedule_date: scheduleDate, product_name: product})
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById('schedulePreview').classList.remove('hidden');
            document.getElementById('previewContent').textContent = data.content;
            showToast(data.message, 'success');
            loadScheduledPosts();
        } else {
            showToast(data.error || 'Failed', 'error');
        }
    } catch(e) { showToast('Connection failed', 'error'); }
    btn.disabled = false; btn.innerHTML = '<i data-lucide="clock" class="w-4 h-4"></i> Generate & Schedule'; lucide.createIcons();
}

async function processScheduledNow() {
    try {
        const res = await fetch('/admin/marketing/ai/process-scheduled', {method:'POST', headers:{'Accept':'application/json'}});
        const data = await res.json();
        showToast(data.success ? `Processed ${data.processed} post(s)` : 'Failed', data.success ? 'success' : 'error');
        loadScheduledPosts();
    } catch(e) { showToast('Connection failed', 'error'); }
}

function editAndReschedule() {
    document.getElementById('schedulePrompt').value = document.getElementById('previewContent').textContent;
    document.getElementById('schedulePreview').classList.add('hidden');
}
</script>