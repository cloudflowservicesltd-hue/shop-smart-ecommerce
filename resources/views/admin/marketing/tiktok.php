<?php
$tiktokSettings = $tiktokSettings ?? [
    'tiktok_access_token'  => '',
    'tiktok_business_id'   => '',
    'tiktok_advertiser_id' => '',
    'tiktok_app_id'        => '',
    'tiktok_app_secret'    => '',
];
$tiktokPosts = $tiktokPosts ?? [];
$isConnected = !empty($tiktokSettings['tiktok_access_token']) && !empty($tiktokSettings['tiktok_business_id']);
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-gradient-to-br from-[#010101] to-[#25F4EE]">
                <i data-lucide="music-2" class="w-5 h-5 text-white"></i>
            </div>
            <div>
                <h1 class="font-heading font-semibold text-xl text-gray-900">TikTok Marketing</h1>
                <p class="text-sm text-gray-500">Manage your TikTok presence and publishing</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium <?= $isConnected ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-red-50 text-red-700 ring-1 ring-red-200' ?>">
                <span class="w-1.5 h-1.5 rounded-full <?= $isConnected ? 'bg-emerald-500' : 'bg-red-500' ?>"></span>
                <?= $isConnected ? 'Connected' : 'Disconnected' ?>
            </span>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="border-b border-gray-200">
        <nav class="flex gap-0 -mb-px" id="tiktokTabs">
            <button type="button" data-tab="post" class="tiktok-tab px-5 py-3 text-sm font-medium border-b-2 border-amber-600 text-amber-600 transition-colors">
                <i data-lucide="send" class="w-4 h-4 inline-block mr-1.5 -mt-0.5"></i>TikTok Post
            </button>
            <button type="button" data-tab="settings" class="tiktok-tab px-5 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors">
                <i data-lucide="settings" class="w-4 h-4 inline-block mr-1.5 -mt-0.5"></i>Settings
            </button>
            <button type="button" data-tab="history" class="tiktok-tab px-5 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors">
                <i data-lucide="history" class="w-4 h-4 inline-block mr-1.5 -mt-0.5"></i>Post History
            </button>
            <button type="button" data-tab="scheduled" class="tiktok-tab px-5 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors">
                <i data-lucide="calendar-clock" class="w-4 h-4 inline-block mr-1.5 -mt-0.5"></i>Scheduled Posts
            </button>
        </nav>
    </div>

    <!-- ==================== TAB 1: TIKTOK POST ==================== -->
    <div id="tab-post" class="tiktok-panel">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Post Composer -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <div class="flex items-center gap-2 mb-5">
                        <i data-lucide="music-2" class="w-5 h-5 text-[#fe2c55]"></i>
                        <h2 class="font-heading font-semibold text-gray-900">Create TikTok Post</h2>
                    </div>

                    <form id="tiktokPostForm" class="space-y-5">
                        <!-- Post Content -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-sm font-medium text-gray-700">Post Content</label>
                                <span class="text-xs text-gray-400" id="ttCharCount">0 / 2,200</span>
                            </div>
                            <textarea
                                id="ttPostContent"
                                rows="5"
                                maxlength="2200"
                                placeholder="Write your TikTok caption here... Add #hashtags for better reach!"
                                oninput="updateTTCharCount()"
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#fe2c55] focus:border-[#fe2c55] resize-y min-h-[120px]"
                            ></textarea>
                            <div class="flex items-center justify-between mt-1.5">
                                <button type="button" onclick="copyTTContent(this)" class="inline-flex items-center gap-1 text-xs text-gray-400 hover:text-gray-600 transition-colors">
                                    <i data-lucide="copy" class="w-3 h-3"></i> Copy content
                                </button>
                                <p class="text-xs text-gray-400">TikTok captions can be up to 2,200 characters</p>
                            </div>
                        </div>

                        <!-- Image URL -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 mb-1.5 block">Image URL (optional)</label>
                            <div class="relative">
                                <i data-lucide="image" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                <input
                                    type="url"
                                    id="ttImageUrl"
                                    placeholder="https://example.com/image.jpg"
                                    class="w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#fe2c55] focus:border-[#fe2c55]"
                                >
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Provide a direct URL to an image to include with your post</p>
                        </div>

                        <!-- Publish -->
                        <div class="flex items-center justify-end pt-4 border-t border-gray-100">
                            <button
                                type="button"
                                onclick="publishTikTokPost()"
                                id="ttPublishBtn"
                                class="inline-flex items-center gap-2 px-6 py-2.5 bg-[#fe2c55] text-white rounded-lg text-sm font-medium hover:bg-[#e0284d] transition-colors"
                            >
                                <i data-lucide="send" class="w-4 h-4"></i>
                                <span>Publish to TikTok</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Quick Tips -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <div class="flex items-center gap-2 mb-3">
                        <i data-lucide="lightbulb" class="w-4 h-4 text-amber-600"></i>
                        <h3 class="text-sm font-semibold text-gray-900">Tips for TikTok</h3>
                    </div>
                    <ul class="space-y-2 text-xs text-gray-600">
                        <li class="flex items-start gap-1.5"><i data-lucide="check" class="w-3 h-3 mt-0.5 flex-shrink-0 text-amber-600"></i> Keep captions short and punchy</li>
                        <li class="flex items-start gap-1.5"><i data-lucide="check" class="w-3 h-3 mt-0.5 flex-shrink-0 text-amber-600"></i> Use trending hashtags for discoverability</li>
                        <li class="flex items-start gap-1.5"><i data-lucide="check" class="w-3 h-3 mt-0.5 flex-shrink-0 text-amber-600"></i> Best posting times: 7-9 AM and 7-11 PM</li>
                        <li class="flex items-start gap-1.5"><i data-lucide="check" class="w-3 h-3 mt-0.5 flex-shrink-0 text-amber-600"></i> Include a call-to-action in every post</li>
                    </ul>
                </div>

                <!-- Trending Hashtags -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <div class="flex items-center gap-2 mb-3">
                        <i data-lucide="hash" class="w-4 h-4 text-gray-500"></i>
                        <h3 class="text-sm font-semibold text-gray-900">Popular Hashtags</h3>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <?php
                        $tiktokHashtags = ['#fyp', '#foryou', '#trending', '#viral', '#smallbusiness', '#ecommerce', '#shoplocal', '#newproduct', '#sale', '#tiktokmademebuyit'];
                        foreach ($tiktokHashtags as $tag):
                        ?>
                        <button
                            type="button"
                            onclick="appendTTHashtag('<?= $tag ?>')"
                            class="px-2.5 py-1 bg-gray-100 hover:bg-pink-50 text-xs text-gray-600 hover:text-pink-600 rounded-full transition-colors"
                        ><?= $tag ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== TAB 2: SETTINGS ==================== -->
    <div id="tab-settings" class="tiktok-panel hidden">
        <form id="tiktokSettingsForm" class="space-y-6">
            <!-- API Credentials -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <div class="flex items-center gap-2 mb-1">
                    <i data-lucide="key-round" class="w-5 h-5 text-amber-600"></i>
                    <h2 class="font-heading font-semibold text-gray-900">TikTok API Credentials</h2>
                </div>
                <p class="text-sm text-gray-500 mb-6">Configure your TikTok for Business API credentials to connect and publish content.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- Access Token -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Access Token</label>
                        <div class="relative">
                            <input
                                type="password"
                                name="tiktok_access_token"
                                id="tiktok_access_token"
                                value="<?= e($tiktokSettings['tiktok_access_token']) ?>"
                                placeholder="Enter TikTok access token"
                                class="w-full pl-10 pr-10 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 font-mono text-xs"
                            >
                            <i data-lucide="shield-check" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                            <button type="button" onclick="toggleKeyVisibility(this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Business ID -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Business ID</label>
                        <div class="relative">
                            <input
                                type="text"
                                name="tiktok_business_id"
                                value="<?= e($tiktokSettings['tiktok_business_id']) ?>"
                                placeholder="e.g. 123456789012345"
                                class="w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                            >
                            <i data-lucide="building-2" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        </div>
                    </div>

                    <!-- Advertiser ID -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Advertiser ID</label>
                        <div class="relative">
                            <input
                                type="text"
                                name="tiktok_advertiser_id"
                                value="<?= e($tiktokSettings['tiktok_advertiser_id']) ?>"
                                placeholder="e.g. 987654321098765"
                                class="w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                            >
                            <i data-lucide="megaphone" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        </div>
                    </div>

                    <!-- App ID -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">App ID</label>
                        <div class="relative">
                            <input
                                type="text"
                                name="tiktok_app_id"
                                value="<?= e($tiktokSettings['tiktok_app_id']) ?>"
                                placeholder="e.g. 123456789012345"
                                class="w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                            >
                            <i data-lucide="app-window" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        </div>
                        <a href="https://developers.tiktok.com/apps/" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-xs text-amber-600 hover:text-amber-700 mt-1">
                            <i data-lucide="external-link" class="w-3 h-3"></i> Get from TikTok Developers
                        </a>
                    </div>

                    <!-- App Secret -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">App Secret</label>
                        <div class="relative">
                            <input
                                type="password"
                                name="tiktok_app_secret"
                                id="tiktok_app_secret"
                                value="<?= e($tiktokSettings['tiktok_app_secret']) ?>"
                                placeholder="Enter TikTok app secret"
                                class="w-full pl-10 pr-10 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 font-mono text-xs"
                            >
                            <i data-lucide="lock" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                            <button type="button" onclick="toggleKeyVisibility(this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Connection Status & Actions -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center <?= $isConnected ? 'bg-emerald-100' : 'bg-red-100' ?>">
                            <i data-lucide="<?= $isConnected ? 'check-circle-2' : 'x-circle' ?>" class="w-5 h-5 <?= $isConnected ? 'text-emerald-600' : 'text-red-500' ?>"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Connection Status</p>
                            <p class="text-xs text-gray-500" id="ttConnectionText">
                                <?= $isConnected ? 'Your TikTok Business account is connected and ready.' : 'Configure credentials above and test your connection.' ?>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            onclick="testTikTokConnection()"
                            id="ttTestBtn"
                            class="inline-flex items-center gap-2 px-4 py-2.5 border border-gray-200 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors"
                        >
                            <i data-lucide="plug" class="w-4 h-4"></i>
                            <span>Test Connection</span>
                        </button>
                        <button
                            type="button"
                            onclick="saveTikTokSettings()"
                            id="ttSaveBtn"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-amber-600 text-white rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors"
                        >
                            <i data-lucide="save" class="w-4 h-4"></i>
                            <span>Save Settings</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Setup Guide Sidebar -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2"></div>
            <div class="space-y-4">
                <div class="bg-amber-50 rounded-xl border border-amber-100 p-5">
                    <h3 class="text-sm font-semibold text-amber-800 mb-3 flex items-center gap-2">
                        <i data-lucide="book-open" class="w-4 h-4"></i> Setup Guide
                    </h3>
                    <ol class="text-xs text-amber-700 space-y-2 list-decimal pl-4">
                        <li>Create a <a href="https://developers.tiktok.com" target="_blank" class="underline">TikTok Developer Account</a></li>
                        <li>Register an app in the TikTok Developer Portal</li>
                        <li>Request Content Posting API permissions</li>
                        <li>Link your TikTok Business Account</li>
                        <li>Copy your credentials and paste them above</li>
                        <li>Click "Test Connection" to verify</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== TAB 3: POST HISTORY ==================== -->
    <div id="tab-history" class="tiktok-panel hidden">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-2">
                    <i data-lucide="history" class="w-5 h-5 text-gray-500"></i>
                    <h2 class="font-heading font-semibold text-gray-900">TikTok Post History</h2>
                </div>
                <span class="text-xs text-gray-400" id="ttHistoryCount"><?= count($tiktokPosts) ?> posts</span>
            </div>

            <?php if (!empty($tiktokPosts)): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Content</th>
                            <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody id="ttHistoryBody">
                        <?php foreach ($tiktokPosts as $post):
                            $statusClasses = match($post['status'] ?? '') {
                                'sent'     => 'bg-green-100 text-green-700',
                                'failed'   => 'bg-red-100 text-red-700',
                                'scheduled'=> 'bg-amber-100 text-amber-700',
                                default    => 'bg-gray-100 text-gray-600',
                            };
                        ?>
                        <tr class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors">
                            <td class="py-3 px-4 font-medium text-gray-900 max-w-[200px] truncate"><?= e($post['name'] ?? 'Untitled') ?></td>
                            <td class="py-3 px-4 text-gray-600 max-w-[300px] truncate"><?= e($post['content'] ?? '') ?></td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusClasses ?>">
                                    <?= e(ucfirst($post['status'] ?? 'unknown')) ?>
                                </span>
                            </td>
                            <td class="py-3 px-4 text-gray-500 whitespace-nowrap text-xs"><?= e($post['created_at'] ?? '') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-12">
                <i data-lucide="music-2" class="w-10 h-10 text-gray-200 mx-auto mb-3"></i>
                <p class="text-sm text-gray-500">No TikTok posts yet</p>
                <p class="text-xs text-gray-400 mt-1">Your TikTok post history will appear here</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ==================== TAB 4: SCHEDULED POSTS ==================== -->
    <div id="tab-scheduled" class="tiktok-panel hidden">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-2">
                    <i data-lucide="calendar-clock" class="w-5 h-5 text-amber-600"></i>
                    <h2 class="font-heading font-semibold text-gray-900">All Scheduled Posts</h2>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-gray-400" id="ttScheduledCount"></span>
                    <button
                        type="button"
                        onclick="processScheduledPosts()"
                        id="ttProcessBtn"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 text-white rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors"
                    >
                        <i data-lucide="play" class="w-4 h-4"></i>
                        <span>Process Now</span>
                    </button>
                </div>
            </div>

            <div id="scheduledPostsTable">
                <!-- Populated via JavaScript -->
                <div id="scheduledPostsLoading" class="text-center py-12">
                    <svg class="animate-spin w-6 h-6 text-amber-600 mx-auto mb-3" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"></circle>
                        <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" stroke-linecap="round" class="opacity-75"></path>
                    </svg>
                    <p class="text-sm text-gray-500">Loading scheduled posts...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const tiktokPosts = <?php echo json_encode($tiktokPosts) ?>;

// ─── Tab Switching ───
document.querySelectorAll('#tiktokTabs .tiktok-tab').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('#tiktokTabs .tiktok-tab').forEach(b => {
            b.classList.remove('border-amber-600', 'text-amber-600');
            b.classList.add('border-transparent', 'text-gray-500');
        });
        btn.classList.add('border-amber-600', 'text-amber-600');
        btn.classList.remove('border-transparent', 'text-gray-500');

        document.querySelectorAll('.tiktok-panel').forEach(p => p.classList.add('hidden'));
        document.getElementById('tab-' + btn.dataset.tab).classList.remove('hidden');

        if (window.lucide) lucide.createIcons();

        // Load scheduled posts when switching to that tab
        if (btn.dataset.tab === 'scheduled') {
            loadScheduledPosts();
        }
    });
});

// ─── Toggle Key Visibility ───
function toggleKeyVisibility(btn) {
    const input = btn.previousElementSibling;
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    btn.innerHTML = isPassword ? '<i data-lucide="eye" class="w-4 h-4"></i>' : '<i data-lucide="eye-off" class="w-4 h-4"></i>';
    lucide.createIcons();
}

// ─── Character Counter ───
function updateTTCharCount() {
    const len = document.getElementById('ttPostContent').value.length;
    const counter = document.getElementById('ttCharCount');
    counter.textContent = len.toLocaleString() + ' / 2,200';
    counter.classList.toggle('text-amber-600', len > 1980);
    counter.classList.toggle('text-red-500', len >= 2200);
}

// ─── Copy Content ───
function copyTTContent(btn) {
    const text = document.getElementById('ttPostContent').value;
    if (!text.trim()) return;
    navigator.clipboard.writeText(text).then(() => {
        const icon = btn.querySelector('[data-lucide]');
        const original = icon.getAttribute('data-lucide');
        icon.setAttribute('data-lucide', 'check');
        if (window.lucide) lucide.createIcons();
        setTimeout(() => {
            icon.setAttribute('data-lucide', original);
            if (window.lucide) lucide.createIcons();
        }, 1500);
    });
}

// ─── Append Hashtag ───
function appendTTHashtag(tag) {
    const ta = document.getElementById('ttPostContent');
    const pos = ta.selectionStart;
    const text = ta.value;
    const before = text.substring(0, pos);
    const after = text.substring(pos);
    const needsSpace = before.length > 0 && !before.endsWith(' ') && !before.endsWith('\n') ? ' ' : '';
    ta.value = before + needsSpace + tag + ' ' + after;
    ta.focus();
    ta.setSelectionRange(pos + needsSpace.length + tag.length + 1, pos + needsSpace.length + tag.length + 1);
    ta.dispatchEvent(new Event('input'));
}

// ─── Publish TikTok Post ───
async function publishTikTokPost() {
    const content = document.getElementById('ttPostContent').value.trim();
    const imageUrl = document.getElementById('ttImageUrl').value.trim();
    if (!content) return showToast('Please enter post content', 'error');

    const btn = document.getElementById('ttPublishBtn');
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" stroke-linecap="round" class="opacity-75"/></svg><span>Publishing...</span>';
    btn.disabled = true;
    btn.classList.add('opacity-80', 'cursor-not-allowed');

    try {
        const res = await fetch('/admin/marketing/tiktok/post', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ content: content, image_url: imageUrl })
        });
        const data = await res.json();
        if (data.success) {
            showToast('Post published to TikTok successfully!', 'success');
            document.getElementById('ttPostContent').value = '';
            document.getElementById('ttImageUrl').value = '';
            updateTTCharCount();
        } else {
            showToast(data.error || 'Failed to publish post', 'error');
        }
    } catch (e) {
        showToast('Connection failed. Please try again.', 'error');
    }

    btn.innerHTML = originalHtml;
    btn.disabled = false;
    btn.classList.remove('opacity-80', 'cursor-not-allowed');
    if (window.lucide) lucide.createIcons();
}

// ─── Save Settings ───
async function saveTikTokSettings() {
    const form = document.getElementById('tiktokSettingsForm');
    const formData = new FormData(form);
    const btn = document.getElementById('ttSaveBtn');
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" stroke-linecap="round" class="opacity-75"/></svg><span>Saving...</span>';
    btn.disabled = true;

    try {
        const res = await fetch('/admin/marketing/tiktok/settings', {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            showToast('TikTok settings saved!', 'success');
        } else {
            showToast(data.error || 'Failed to save settings', 'error');
        }
    } catch (e) {
        showToast('Connection failed. Please try again.', 'error');
    }

    btn.innerHTML = originalHtml;
    btn.disabled = false;
    if (window.lucide) lucide.createIcons();
}

// ─── Test Connection ───
async function testTikTokConnection() {
    const btn = document.getElementById('ttTestBtn');
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" stroke-linecap="round" class="opacity-75"/></svg><span>Testing...</span>';
    btn.disabled = true;

    try {
        const res = await fetch('/admin/marketing/tiktok/test', {
            method: 'POST',
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();
        if (data.success) {
            showToast('TikTok connection successful!', 'success');
        } else {
            showToast(data.error || 'Connection failed. Check your credentials.', 'error');
        }
    } catch (e) {
        showToast('Connection test failed. Please try again.', 'error');
    }

    btn.innerHTML = originalHtml;
    btn.disabled = false;
    if (window.lucide) lucide.createIcons();
}

// ─── Load Scheduled Posts ───
async function loadScheduledPosts() {
    const container = document.getElementById('scheduledPostsTable');
    try {
        const res = await fetch('/admin/marketing/scheduled?platforms=tiktok,facebook,instagram,whatsapp', {
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();
        const posts = data.posts || data.data || [];
        renderScheduledPosts(posts);
    } catch (e) {
        container.innerHTML = '<div class="text-center py-8"><p class="text-sm text-red-500">Failed to load scheduled posts</p></div>';
    }
}

function renderScheduledPosts(posts) {
    const container = document.getElementById('scheduledPostsTable');
    const countEl = document.getElementById('ttScheduledCount');
    countEl.textContent = posts.length + ' pending';

    if (!posts.length) {
        container.innerHTML = '<div class="text-center py-12"><i data-lucide="calendar-x" class="w-10 h-10 text-gray-200 mx-auto mb-3"></i><p class="text-sm text-gray-500">No scheduled posts</p><p class="text-xs text-gray-400 mt-1">Scheduled posts from all platforms will appear here</p></div>';
        if (window.lucide) lucide.createIcons();
        return;
    }

    const platformIcons = {
        tiktok: { icon: 'music-2', color: 'text-[#fe2c55]', bg: 'bg-pink-50', label: 'TikTok' },
        facebook: { icon: 'facebook', color: 'text-[#1877F2]', bg: 'bg-blue-50', label: 'Facebook' },
        instagram: { icon: 'instagram', color: 'text-pink-500', bg: 'bg-purple-50', label: 'Instagram' },
        whatsapp: { icon: 'message-circle', color: 'text-green-600', bg: 'bg-green-50', label: 'WhatsApp' },
    };

    let rows = '';
    posts.forEach(post => {
        const p = platformIcons[post.platform] || { icon: 'circle', color: 'text-gray-500', bg: 'bg-gray-50', label: post.platform };
        const content = (post.content || post.name || '').substring(0, 80);
        rows += `
        <tr class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors">
            <td class="py-3 px-4">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg ${p.bg} flex items-center justify-center flex-shrink-0">
                        <i data-lucide="${p.icon}" class="w-3.5 h-3.5 ${p.color}"></i>
                    </div>
                    <span class="text-sm font-medium text-gray-900">${p.label}</span>
                </div>
            </td>
            <td class="py-3 px-4 text-gray-600 max-w-[300px] truncate text-sm">${escapeHtml(content)}</td>
            <td class="py-3 px-4 text-gray-500 whitespace-nowrap text-xs">${escapeHtml(post.scheduled_at || '')}</td>
            <td class="py-3 px-4">
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                    <i data-lucide="clock" class="w-3 h-3"></i>
                    Scheduled
                </span>
            </td>
        </tr>`;
    });

    container.innerHTML = `
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100">
                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Platform</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Content</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Schedule Date</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>
    </div>`;

    if (window.lucide) lucide.createIcons();
}

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// ─── Process Scheduled Posts ───
async function processScheduledPosts() {
    const btn = document.getElementById('ttProcessBtn');
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" stroke-linecap="round" class="opacity-75"/></svg><span>Processing...</span>';
    btn.disabled = true;

    try {
        const res = await fetch('/admin/marketing/ai/process-scheduled', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        });
        const data = await res.json();
        if (data.success) {
            showToast('Scheduled posts processed successfully!', 'success');
            loadScheduledPosts();
        } else {
            showToast(data.error || 'Failed to process scheduled posts', 'error');
        }
    } catch (e) {
        showToast('Processing failed. Please try again.', 'error');
    }

    btn.innerHTML = originalHtml;
    btn.disabled = false;
    if (window.lucide) lucide.createIcons();
}

// ─── Toast Notification ───
function showToast(msg, type) {
    const t = document.createElement('div');
    t.className = `fixed top-4 right-4 z-50 px-4 py-2 rounded-lg text-sm font-medium shadow-lg ${type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'}`;
    t.textContent = msg; document.body.appendChild(t);
    setTimeout(() => t.remove(), 3000);
}

// Initialize
if (window.lucide) lucide.createIcons();
</script>