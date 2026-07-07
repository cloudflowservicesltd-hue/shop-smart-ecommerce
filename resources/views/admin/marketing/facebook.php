<?php
$fbSettings = $fbSettings ?? [
    'fb_app_id'     => '',
    'fb_app_secret' => '',
    'fb_page_token' => '',
    'fb_page_id'    => '',
    'ig_business_id' => '',
    'ig_user_id'    => '',
];
$fbPosts = $fbPosts ?? [];
$igPosts  = $igPosts ?? [];
$scheduledFb = $scheduledFb ?? [];
$isConnected = !empty($fbSettings['fb_page_token']) && !empty($fbSettings['fb_page_id']);
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-gradient-to-br from-[#1877F2] to-[#E1306C]">
                <i data-lucide="share-2" class="w-5 h-5 text-white"></i>
            </div>
            <div>
                <h1 class="font-heading font-semibold text-xl text-gray-900">Facebook & Instagram Marketing</h1>
                <p class="text-sm text-gray-500">Manage your social media presence and publishing</p>
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
        <nav class="flex gap-0 -mb-px" id="fbIgTabs">
            <button type="button" data-tab="settings" class="tab-btn active-tab px-5 py-3 text-sm font-medium border-b-2 border-amber-600 text-amber-600 transition-colors">
                <i data-lucide="settings" class="w-4 h-4 inline-block mr-1.5 -mt-0.5"></i>Settings
            </button>
            <button type="button" data-tab="facebook" class="tab-btn px-5 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors">
                <i data-lucide="facebook" class="w-4 h-4 inline-block mr-1.5 -mt-0.5"></i>Facebook Posts
            </button>
            <button type="button" data-tab="instagram" class="tab-btn px-5 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors">
                <i data-lucide="instagram" class="w-4 h-4 inline-block mr-1.5 -mt-0.5"></i>Instagram Posts
            </button>
        </nav>
    </div>

    <!-- ==================== TAB 1: SETTINGS ==================== -->
    <div id="tab-settings" class="tab-panel">
        <form method="POST" action="/admin/marketing/facebook/settings" id="settingsForm">
            <?= csrf() ?>

            <!-- API Credentials -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-6">
                <div class="flex items-center gap-2 mb-1">
                    <i data-lucide="key-round" class="w-5 h-5 text-amber-600"></i>
                    <h2 class="font-heading font-semibold text-gray-900">Facebook Graph API Credentials</h2>
                </div>
                <p class="text-sm text-gray-500 mb-6">Configure your Facebook App credentials to connect the Graph API.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- App ID -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Facebook App ID</label>
                        <div class="relative">
                            <input
                                type="text"
                                name="fb_app_id"
                                value="<?= e($fbSettings['fb_app_id']) ?>"
                                placeholder="e.g. 123456789012345"
                                class="w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                            >
                            <i data-lucide="app-window" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        </div>
                        <a href="https://developers.facebook.com/apps/" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-xs text-amber-600 hover:text-amber-700 mt-1">
                            <i data-lucide="external-link" class="w-3 h-3"></i> Get from Facebook Developers
                        </a>
                    </div>

                    <!-- App Secret -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Facebook App Secret</label>
                        <div class="relative">
                            <input
                                type="password"
                                name="fb_app_secret"
                                value="<?= e($fbSettings['fb_app_secret']) ?>"
                                placeholder="Enter app secret"
                                class="w-full pl-10 pr-10 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                            >
                            <i data-lucide="lock" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                            <button type="button" onclick="toggleFieldVisibility('fb_app_secret', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Page Access Token -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Page Access Token</label>
                        <div class="relative">
                            <input
                                type="password"
                                name="fb_page_token"
                                id="fb_page_token"
                                value="<?= e($fbSettings['fb_page_token']) ?>"
                                placeholder="EAAxxxxxxxxxxxx..."
                                class="w-full pl-10 pr-10 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 font-mono text-xs"
                            >
                            <i data-lucide="shield-check" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                            <button type="button" onclick="toggleFieldVisibility('fb_page_token', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Requires pages_manage_posts, instagram_basic, instagram_content_publish permissions</p>
                    </div>

                    <!-- Facebook Page ID -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Facebook Page ID</label>
                        <div class="relative">
                            <input
                                type="text"
                                name="fb_page_id"
                                value="<?= e($fbSettings['fb_page_id']) ?>"
                                placeholder="e.g. 987654321098765"
                                class="w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                            >
                            <i data-lucide="file-text" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Instagram Settings -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-6">
                <div class="flex items-center gap-2 mb-1">
                    <div class="w-5 h-5 rounded bg-gradient-to-br from-purple-500 to-pink-500"></div>
                    <h2 class="font-heading font-semibold text-gray-900">Instagram Business Account</h2>
                </div>
                <p class="text-sm text-gray-500 mb-6">Link your Instagram Business or Creator account via the Facebook Page.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- Instagram Business Account ID -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Instagram Business Account ID</label>
                        <div class="relative">
                            <input
                                type="text"
                                name="ig_business_id"
                                id="ig_business_id"
                                value="<?= e($fbSettings['ig_business_id']) ?>"
                                placeholder="e.g. 17841400123456789"
                                class="w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                            >
                            <i data-lucide="at-sign" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Auto-fetched from your connected Facebook Page, or enter manually</p>
                    </div>

                    <!-- Instagram User ID -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Instagram User ID</label>
                        <div class="relative">
                            <input
                                type="text"
                                name="ig_user_id"
                                value="<?= e($fbSettings['ig_user_id']) ?>"
                                placeholder="e.g. 12345678901"
                                class="w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                            >
                            <i data-lucide="user" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
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
                            <p class="text-xs text-gray-500">
                                <?= $isConnected ? 'Your Facebook Page and Instagram account are linked and ready.' : 'Configure credentials above and test your connection.' ?>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            onclick="testConnection()"
                            class="inline-flex items-center gap-2 px-4 py-2.5 border border-gray-200 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors"
                        >
                            <i data-lucide="plug" class="w-4 h-4"></i>
                            <span>Test Connection</span>
                        </button>
                        <button
                            type="submit"
                            onclick="setButtonLoading(this, 'Saving...')"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-amber-600 text-white rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors"
                        >
                            <i data-lucide="save" class="w-4 h-4"></i>
                            <span>Save Settings</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- ==================== TAB 2: FACEBOOK POSTS ==================== -->
    <div id="tab-facebook" class="tab-panel hidden">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Post Composer (2 cols) -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <div class="flex items-center gap-2 mb-5">
                        <i data-lucide="facebook" class="w-5 h-5 text-[#1877F2]"></i>
                        <h2 class="font-heading font-semibold text-gray-900">Create Facebook Post</h2>
                    </div>

                    <form method="POST" action="/admin/marketing/facebook/post" id="fbPostForm" enctype="multipart/form-data">
                        <?= csrf() ?>

                        <!-- Content -->
                        <div class="mb-4">
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-sm font-medium text-gray-700">Post Content</label>
                                <span class="text-xs text-gray-400" id="fbCharCount">0 / 63,206</span>
                            </div>
                            <textarea
                                name="content"
                                id="fbPostContent"
                                rows="5"
                                maxlength="63206"
                                placeholder="What's on your mind? Write your Facebook post here..."
                                oninput="updateCharCount('fbPostContent', 'fbCharCount', 63206)"
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#1877F2] focus:border-[#1877F2] resize-y min-h-[120px]"
                            ></textarea>
                            <div class="flex items-center justify-between mt-1.5">
                                <button type="button" onclick="copyToClipboard('fbPostContent', this)" class="inline-flex items-center gap-1 text-xs text-gray-400 hover:text-gray-600 transition-colors">
                                    <i data-lucide="copy" class="w-3 h-3"></i> Copy content
                                </button>
                                <p class="text-xs text-gray-400">Facebook posts can be up to 63,206 characters</p>
                            </div>
                        </div>

                        <!-- Image Upload -->
                        <div class="mb-4">
                            <label class="text-sm font-medium text-gray-700 mb-1.5 block">Image (optional)</label>
                            <div
                                id="fbDropZone"
                                onclick="document.getElementById('fbImageInput').click()"
                                class="border-2 border-dashed border-gray-200 rounded-lg p-6 text-center cursor-pointer hover:border-[#1877F2] hover:bg-blue-50/30 transition-colors"
                            >
                                <div id="fbImagePreview" class="hidden mb-3">
                                    <img id="fbPreviewImg" src="" alt="Preview" class="max-h-48 mx-auto rounded-lg shadow-sm">
                                    <button type="button" onclick="event.stopPropagation(); clearImagePreview('fb')" class="mt-2 inline-flex items-center gap-1 text-xs text-red-500 hover:text-red-700">
                                        <i data-lucide="x" class="w-3 h-3"></i> Remove image
                                    </button>
                                </div>
                                <div id="fbUploadPlaceholder" class="space-y-2">
                                    <i data-lucide="image-plus" class="w-8 h-8 text-gray-300 mx-auto"></i>
                                    <p class="text-sm text-gray-500">Click to upload or drag and drop</p>
                                    <p class="text-xs text-gray-400">PNG, JPG, GIF up to 10MB</p>
                                </div>
                            </div>
                            <input type="file" name="image" id="fbImageInput" accept="image/*" onchange="previewImage(this, 'fb')" class="hidden">
                        </div>

                        <!-- Link -->
                        <div class="mb-5">
                            <label class="text-sm font-medium text-gray-700 mb-1.5 block">Link (optional)</label>
                            <div class="relative">
                                <i data-lucide="link" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                <input
                                    type="url"
                                    name="link"
                                    placeholder="https://example.com"
                                    class="w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#1877F2] focus:border-[#1877F2]"
                                >
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-4 border-t border-gray-100">
                            <div class="flex items-center gap-2" id="fbScheduleWrap" style="display:none;">
                                <i data-lucide="calendar" class="w-4 h-4 text-gray-400"></i>
                                <input
                                    type="datetime-local"
                                    name="scheduled_at"
                                    class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#1877F2] focus:border-[#1877F2]"
                                >
                            </div>
                            <div class="flex items-center gap-2 sm:ml-auto">
                                <button
                                    type="button"
                                    onclick="toggleScheduleField('fbScheduleWrap')"
                                    class="inline-flex items-center gap-2 px-4 py-2.5 border border-gray-200 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors"
                                >
                                    <i data-lucide="clock" class="w-4 h-4"></i>
                                    <span>Schedule</span>
                                </button>
                                <button
                                    type="submit"
                                    name="action"
                                    value="publish_now"
                                    onclick="setButtonLoading(this, 'Publishing...')"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#1877F2] text-white rounded-lg text-sm font-medium hover:bg-[#166FE5] transition-colors"
                                >
                                    <i data-lucide="send" class="w-4 h-4"></i>
                                    <span>Post Now</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Scheduled Posts -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <i data-lucide="calendar-clock" class="w-5 h-5 text-amber-600"></i>
                            <h3 class="font-heading font-semibold text-gray-900">Scheduled Posts</h3>
                        </div>
                        <span class="text-xs text-gray-400"><?= count($scheduledFb) ?> pending</span>
                    </div>
                    <?php if (!empty($scheduledFb)): ?>
                    <div class="space-y-3 max-h-80 overflow-y-auto">
                        <?php foreach ($scheduledFb as $post): ?>
                        <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="w-9 h-9 rounded-lg bg-[#1877F2]/10 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <i data-lucide="clock" class="w-4 h-4 text-[#1877F2]"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate"><?= e($post['name'] ?? 'Untitled Post') ?></p>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    Scheduled for <?= e($post['scheduled_at'] ?? 'N/A') ?>
                                </p>
                            </div>
                            <form method="POST" action="/admin/marketing/facebook/post/delete" class="flex-shrink-0">
                                <?= csrf() ?>
                                <input type="hidden" name="id" value="<?= e($post['id']) ?>">
                                <button type="submit" class="p-1.5 text-gray-400 hover:text-red-500 transition-colors rounded-lg hover:bg-red-50">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-8">
                        <i data-lucide="calendar-x" class="w-10 h-10 text-gray-200 mx-auto mb-2"></i>
                        <p class="text-sm text-gray-500">No scheduled posts</p>
                        <p class="text-xs text-gray-400 mt-1">Create a post and click "Schedule" to queue it</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Posts (1 col sidebar) -->
            <div class="space-y-6">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <div class="flex items-center gap-2 mb-4">
                        <i data-lucide="history" class="w-5 h-5 text-gray-500"></i>
                        <h3 class="font-heading font-semibold text-gray-900">Recent Posts</h3>
                    </div>
                    <?php if (!empty($fbPosts)): ?>
                    <div class="space-y-3 max-h-[500px] overflow-y-auto">
                        <?php foreach ($fbPosts as $post): ?>
                        <?php
                            $statusColor = match($post['status'] ?? 'published') {
                                'published'  => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
                                'scheduled'  => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
                                'failed'     => 'bg-red-50 text-red-700 ring-1 ring-red-200',
                                default      => 'bg-gray-50 text-gray-600 ring-1 ring-gray-200',
                            };
                            $statusIcon = match($post['status'] ?? 'published') {
                                'published'  => 'check-circle-2',
                                'scheduled'  => 'clock',
                                'failed'     => 'alert-circle',
                                default      => 'circle',
                            };
                        ?>
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wide <?= $statusColor ?>">
                                    <i data-lucide="<?= $statusIcon ?>" class="w-2.5 h-2.5"></i>
                                    <?= e($post['status'] ?? 'published') ?>
                                </span>
                                <span class="text-[10px] text-gray-400"><?= timeAgo($post['created_at'] ?? '') ?></span>
                            </div>
                            <p class="text-sm text-gray-700 line-clamp-2"><?= e($post['name'] ?? '') ?></p>
                            <?php if (!empty($post['sent_count'])): ?>
                            <p class="text-xs text-gray-400 mt-1">Reach: <?= number_format($post['sent_count']) ?></p>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-8">
                        <i data-lucide="message-square-dashed" class="w-10 h-10 text-gray-200 mx-auto mb-2"></i>
                        <p class="text-sm text-gray-500">No posts yet</p>
                        <p class="text-xs text-gray-400 mt-1">Your recent Facebook posts will appear here</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Tips -->
                <div class="bg-amber-50 rounded-xl border border-amber-100 p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <i data-lucide="lightbulb" class="w-4 h-4 text-amber-600"></i>
                        <h4 class="text-sm font-semibold text-amber-800">Tips for Better Reach</h4>
                    </div>
                    <ul class="space-y-2 text-xs text-amber-700">
                        <li class="flex items-start gap-1.5"><i data-lucide="check" class="w-3 h-3 mt-0.5 flex-shrink-0"></i> Posts with images get 2.3x more engagement</li>
                        <li class="flex items-start gap-1.5"><i data-lucide="check" class="w-3 h-3 mt-0.5 flex-shrink-0"></i> Keep posts under 80 characters for higher click rates</li>
                        <li class="flex items-start gap-1.5"><i data-lucide="check" class="w-3 h-3 mt-0.5 flex-shrink-0"></i> Best times: 9-11 AM and 7-9 PM on weekdays</li>
                        <li class="flex items-start gap-1.5"><i data-lucide="check" class="w-3 h-3 mt-0.5 flex-shrink-0"></i> Ask questions to boost comments and reactions</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== TAB 3: INSTAGRAM POSTS ==================== -->
    <div id="tab-instagram" class="tab-panel hidden">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Post Composer (2 cols) -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <div class="flex items-center gap-2 mb-5">
                        <div class="w-5 h-5 rounded bg-gradient-to-br from-purple-500 to-pink-500"></div>
                        <h2 class="font-heading font-semibold text-gray-900">Create Instagram Post</h2>
                    </div>

                    <form method="POST" action="/admin/marketing/instagram/post" id="igPostForm" enctype="multipart/form-data">
                        <?= csrf() ?>

                        <!-- Image Upload (Required) -->
                        <div class="mb-4">
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-sm font-medium text-gray-700">
                                    Image <span class="text-red-500">*</span>
                                </label>
                                <span class="text-xs text-gray-400">Instagram requires an image for posts</span>
                            </div>
                            <div
                                id="igDropZone"
                                onclick="document.getElementById('igImageInput').click()"
                                class="border-2 border-dashed border-gray-200 rounded-lg p-8 text-center cursor-pointer hover:border-pink-400 hover:bg-pink-50/30 transition-colors"
                            >
                                <div id="igImagePreview" class="hidden mb-3">
                                    <img id="igPreviewImg" src="" alt="Preview" class="max-h-64 mx-auto rounded-lg shadow-sm">
                                    <button type="button" onclick="event.stopPropagation(); clearImagePreview('ig')" class="mt-2 inline-flex items-center gap-1 text-xs text-red-500 hover:text-red-700">
                                        <i data-lucide="x" class="w-3 h-3"></i> Remove image
                                    </button>
                                </div>
                                <div id="igUploadPlaceholder" class="space-y-3">
                                    <div class="w-14 h-14 mx-auto rounded-xl bg-gradient-to-br from-purple-100 to-pink-100 flex items-center justify-center">
                                        <i data-lucide="image-plus" class="w-7 h-7 text-pink-500"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-700">Click to upload an image</p>
                                        <p class="text-xs text-gray-400 mt-1">PNG, JPG up to 10MB &bull; Recommended: 1080 &times; 1080px (square) or 1080 &times; 1350px (portrait)</p>
                                    </div>
                                </div>
                            </div>
                            <input type="file" name="image" id="igImageInput" accept="image/*" onchange="previewImage(this, 'ig')" class="hidden" required>
                        </div>

                        <!-- Caption -->
                        <div class="mb-5">
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-sm font-medium text-gray-700">Caption</label>
                                <span class="text-xs text-gray-400" id="igCharCount">0 / 2,200</span>
                            </div>
                            <textarea
                                name="caption"
                                id="igCaption"
                                rows="4"
                                maxlength="2200"
                                placeholder="Write a caption... Use #hashtags to reach more people!"
                                oninput="updateCharCount('igCaption', 'igCharCount', 2200)"
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-pink-500 resize-y min-h-[100px]"
                            ></textarea>
                            <div class="flex items-center justify-between mt-1.5">
                                <button type="button" onclick="copyToClipboard('igCaption', this)" class="inline-flex items-center gap-1 text-xs text-gray-400 hover:text-gray-600 transition-colors">
                                    <i data-lucide="copy" class="w-3 h-3"></i> Copy caption
                                </button>
                                <p class="text-xs text-gray-400">Instagram captions can be up to 2,200 characters</p>
                            </div>
                        </div>

                        <!-- Publish -->
                        <div class="flex items-center justify-end pt-4 border-t border-gray-100">
                            <button
                                type="submit"
                                onclick="setButtonLoading(this, 'Publishing to Instagram...')"
                                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg text-sm font-medium text-white bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 transition-all shadow-sm hover:shadow-md"
                            >
                                <i data-lucide="send" class="w-4 h-4"></i>
                                <span>Publish to Instagram</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Instagram Guidelines -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <div class="flex items-center gap-2 mb-3">
                        <i data-lucide="info" class="w-5 h-5 text-gray-400"></i>
                        <h3 class="font-heading font-semibold text-gray-900">Instagram Publishing Guidelines</h3>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-gray-600">
                        <div class="flex items-start gap-2">
                            <i data-lucide="image" class="w-4 h-4 text-pink-500 mt-0.5 flex-shrink-0"></i>
                            <p><span class="font-medium text-gray-800">Image required</span> &mdash; Instagram posts must include an image. Videos are not supported through the API yet.</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <i data-lucide="aspect-ratio" class="w-4 h-4 text-purple-500 mt-0.5 flex-shrink-0"></i>
                            <p><span class="font-medium text-gray-800">Aspect ratio</span> &mdash; Square (1:1), Portrait (4:5), or Landscape (1.91:1).</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <i data-lucide="shield-alert" class="w-4 h-4 text-amber-500 mt-0.5 flex-shrink-0"></i>
                            <p><span class="font-medium text-gray-800">Content policy</span> &mdash; Ensure your content complies with Instagram's Community Guidelines.</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <i data-lucide="clock" class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0"></i>
                            <p><span class="font-medium text-gray-800">Processing time</span> &mdash; Posts may take a few minutes to appear on your feed.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Instagram Posts (1 col sidebar) -->
            <div class="space-y-6">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <div class="flex items-center gap-2 mb-4">
                        <i data-lucide="history" class="w-5 h-5 text-gray-500"></i>
                        <h3 class="font-heading font-semibold text-gray-900">Recent Instagram Posts</h3>
                    </div>
                    <?php if (!empty($igPosts)): ?>
                    <div class="space-y-3 max-h-[500px] overflow-y-auto">
                        <?php foreach ($igPosts as $post): ?>
                        <?php
                            $statusColor = match($post['status'] ?? 'published') {
                                'published'  => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
                                'scheduled'  => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
                                'failed'     => 'bg-red-50 text-red-700 ring-1 ring-red-200',
                                default      => 'bg-gray-50 text-gray-600 ring-1 ring-gray-200',
                            };
                            $statusIcon = match($post['status'] ?? 'published') {
                                'published'  => 'check-circle-2',
                                'scheduled'  => 'clock',
                                'failed'     => 'alert-circle',
                                default      => 'circle',
                            };
                        ?>
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wide <?= $statusColor ?>">
                                    <i data-lucide="<?= $statusIcon ?>" class="w-2.5 h-2.5"></i>
                                    <?= e($post['status'] ?? 'published') ?>
                                </span>
                                <span class="text-[10px] text-gray-400"><?= timeAgo($post['created_at'] ?? '') ?></span>
                            </div>
                            <p class="text-sm text-gray-700 line-clamp-2"><?= e($post['name'] ?? '') ?></p>
                            <?php if (!empty($post['sent_count'])): ?>
                            <p class="text-xs text-gray-400 mt-1">Reach: <?= number_format($post['sent_count']) ?></p>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-8">
                        <div class="w-14 h-14 mx-auto rounded-xl bg-gradient-to-br from-purple-100 to-pink-100 flex items-center justify-center mb-2">
                            <i data-lucide="camera" class="w-7 h-7 text-pink-400"></i>
                        </div>
                        <p class="text-sm text-gray-500">No Instagram posts yet</p>
                        <p class="text-xs text-gray-400 mt-1">Your recent Instagram posts will appear here</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Hashtag Suggestions -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <div class="flex items-center gap-2 mb-3">
                        <i data-lucide="hash" class="w-5 h-5 text-gray-500"></i>
                        <h3 class="font-heading font-semibold text-gray-900">Popular Hashtags</h3>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <?php
                        $hashtags = ['#trending', '#business', '#branding', '#socialmedia', '#marketing', '#entrepreneur', '#smallbiz', '#instagood', '#photooftheday', '#love'];
                        foreach ($hashtags as $tag):
                        ?>
                        <button
                            type="button"
                            onclick="appendHashtag('<?= $tag ?>', 'igCaption')"
                            class="px-2.5 py-1 bg-gray-100 hover:bg-gradient-to-r hover:from-purple-50 hover:to-pink-50 text-xs text-gray-600 rounded-full transition-colors"
                        ><?= $tag ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ─── Tab Switching ───
document.querySelectorAll('#fbIgTabs .tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        // Deactivate all tabs
        document.querySelectorAll('#fbIgTabs .tab-btn').forEach(b => {
            b.classList.remove('active-tab', 'border-amber-600', 'text-amber-600');
            b.classList.add('border-transparent', 'text-gray-500');
        });
        // Activate clicked tab
        btn.classList.add('active-tab', 'border-amber-600', 'text-amber-600');
        btn.classList.remove('border-transparent', 'text-gray-500');

        // Toggle panels
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.add('hidden'));
        document.getElementById('tab-' + btn.dataset.tab).classList.remove('hidden');

        // Re-init lucide icons for newly visible content
        if (window.lucide) lucide.createIcons();
    });
});

// ─── Toggle Field Visibility (Show/Hide password) ───
function toggleFieldVisibility(fieldId, btn) {
    const field = document.getElementById(fieldId);
    const icon = btn.querySelector('[data-lucide]');
    if (field.type === 'password') {
        field.type = 'text';
        icon.setAttribute('data-lucide', 'eye-off');
    } else {
        field.type = 'password';
        icon.setAttribute('data-lucide', 'eye');
    }
    if (window.lucide) lucide.createIcons();
}

// ─── Character Counter ───
function updateCharCount(textareaId, counterId, max) {
    const len = document.getElementById(textareaId).value.length;
    const counter = document.getElementById(counterId);
    counter.textContent = len.toLocaleString() + ' / ' + max.toLocaleString();
    counter.classList.toggle('text-amber-600', len > max * 0.9);
    counter.classList.toggle('text-red-500', len >= max);
}

// ─── Copy to Clipboard ───
function copyToClipboard(textareaId, btn) {
    const text = document.getElementById(textareaId).value;
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

// ─── Image Preview ───
function previewImage(input, prefix) {
    const file = input.files[0];
    if (!file) return;

    if (!file.type.startsWith('image/')) return;
    if (file.size > 10 * 1024 * 1024) {
        alert('File size must be under 10MB.');
        input.value = '';
        return;
    }

    const reader = new FileReader();
    reader.onload = (e) => {
        document.getElementById(prefix + 'PreviewImg').src = e.target.result;
        document.getElementById(prefix + 'ImagePreview').classList.remove('hidden');
        document.getElementById(prefix + 'UploadPlaceholder').classList.add('hidden');
    };
    reader.readAsDataURL(file);
}

function clearImagePreview(prefix) {
    document.getElementById(prefix + 'PreviewImg').src = '';
    document.getElementById(prefix + 'ImagePreview').classList.add('hidden');
    document.getElementById(prefix + 'UploadPlaceholder').classList.remove('hidden');
    document.getElementById(prefix + 'ImageInput').value = '';
}

// ─── Toggle Schedule Field ───
function toggleScheduleField(wrapId) {
    const wrap = document.getElementById(wrapId);
    wrap.style.display = wrap.style.display === 'none' ? 'flex' : 'none';
}

// ─── Button Loading State ───
function setButtonLoading(btn, loadingText) {
    const originalHtml = btn.innerHTML;
    btn.innerHTML = `<svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" stroke-linecap="round" class="opacity-75"/></svg><span>${loadingText}</span>`;
    btn.disabled = true;
    btn.classList.add('opacity-80', 'cursor-not-allowed');

    // Re-enable after 10s as fallback (form submission should navigate away)
    setTimeout(() => {
        btn.innerHTML = originalHtml;
        btn.disabled = false;
        btn.classList.remove('opacity-80', 'cursor-not-allowed');
        if (window.lucide) lucide.createIcons();
    }, 10000);
}

// ─── Test Connection ───
function testConnection() {
    const btn = event.currentTarget;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = `<svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" stroke-linecap="round" class="opacity-75"/></svg><span>Testing...</span>`;
    btn.disabled = true;

    fetch('/admin/marketing/facebook/test', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        }
    })
    .then(r => r.json())
    .then(data => {
        btn.innerHTML = originalHtml;
        btn.disabled = false;
        if (window.lucide) lucide.createIcons();

        if (data.success) {
            showToast('Connection successful! Facebook and Instagram accounts are linked.', 'success');
        } else {
            showToast(data.message || 'Connection failed. Please check your credentials.', 'error');
        }
    })
    .catch(() => {
        btn.innerHTML = originalHtml;
        btn.disabled = false;
        if (window.lucide) lucide.createIcons();
        showToast('Connection test failed. Please try again.', 'error');
    });
}

// ─── Append Hashtag to Caption ───
function appendHashtag(tag, textareaId) {
    const ta = document.getElementById(textareaId);
    const pos = ta.selectionStart;
    const text = ta.value;
    const before = text.substring(0, pos);
    const after  = text.substring(pos);
    const needsSpace = before.length > 0 && !before.endsWith(' ') && !before.endsWith('\n') ? ' ' : '';
    ta.value = before + needsSpace + tag + ' ' + after;
    ta.focus();
    ta.setSelectionRange(pos + needsSpace.length + tag.length + 1, pos + needsSpace.length + tag.length + 1);
    // Trigger char counter
    ta.dispatchEvent(new Event('input'));
}

// ─── Toast Notification ───
function showToast(message, type) {
    const colors = {
        success: 'bg-emerald-600',
        error:   'bg-red-600',
        info:    'bg-amber-600'
    };
    const icons = {
        success: 'check-circle-2',
        error:   'x-circle',
        info:    'info'
    };
    const toast = document.createElement('div');
    toast.className = `fixed bottom-6 right-6 z-50 flex items-center gap-3 px-5 py-3 rounded-xl text-white text-sm font-medium shadow-lg ${colors[type] || colors.info} transition-all duration-300 translate-y-2 opacity-0`;
    toast.innerHTML = `<i data-lucide="${icons[type] || icons.info}" class="w-4 h-4"></i><span>${message}</span>`;
    document.body.appendChild(toast);

    requestAnimationFrame(() => {
        toast.classList.remove('translate-y-2', 'opacity-0');
        if (window.lucide) lucide.createIcons();
    });

    setTimeout(() => {
        toast.classList.add('translate-y-2', 'opacity-0');
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}
</script>