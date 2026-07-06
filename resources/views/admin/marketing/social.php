<?php
$blotatoConnected = $blotatoConnected ?? false;
$accounts = $accounts ?? [];
$recentPosts = $recentPosts ?? [];

if (!function_exists('getSocialPlatformIcon')) {
    function getSocialPlatformIcon(string $platform): string {
        $icons = [
            'twitter'  => '<svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
            'facebook' => '<svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
            'instagram' => '<svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678a6.162 6.162 0 100 12.324 6.162 6.162 0 100-12.324zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405a1.441 1.441 0 11-2.882 0 1.441 1.441 0 012.882 0z"/></svg>',
            'tiktok'   => '<svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1v-3.5a6.37 6.37 0 00-.79-.05A6.34 6.34 0 003.15 15.2a6.34 6.34 0 0010.86 4.46V13a8.28 8.28 0 005.58 2.15v-3.44a4.85 4.85 0 01-3.77-1.26V6.69h3.77z"/></svg>',
            'linkedin' => '<svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
            'threads'  => '<svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12.186 24h-.007c-3.581-.024-6.334-1.205-8.184-3.509C2.35 18.44 1.5 15.586 1.472 12.01v-.017c.03-3.579.879-6.43 2.525-8.482C5.845 1.205 8.6.024 12.18 0h.014c2.746.02 5.043.725 6.826 2.098 1.677 1.29 2.858 3.13 3.509 5.467l-2.04.569c-1.104-3.96-3.898-5.984-8.304-6.015-2.91.022-5.11.936-6.54 2.717C4.307 6.504 3.616 8.914 3.589 12c.027 3.086.718 5.496 2.057 7.164 1.43 1.783 3.631 2.698 6.54 2.717 2.623-.02 4.358-.631 5.798-2.045 1.647-1.613 1.618-3.593 1.09-4.798-.31-.71-.873-1.3-1.634-1.75-.192 1.352-.622 2.446-1.284 3.272-.886 1.102-2.14 1.704-3.73 1.79-1.202.065-2.361-.218-3.259-.795-1.063-.681-1.685-1.714-1.752-2.91-.065-1.17.434-2.242 1.405-3.021.928-.744 2.197-1.168 3.676-1.224 1.18-.042 2.31.115 3.347.463-.104-.682-.34-1.223-.72-1.612-.563-.577-1.426-.875-2.565-.886h-.032l-.041-2c1.68.017 3.038.5 4.039 1.435.745.697 1.24 1.624 1.47 2.754.48-.44.862-.95 1.118-1.52.673-1.516.542-3.217.144-4.263-.4-1.046-1.126-1.854-2.08-2.33l.905-1.782c1.332.676 2.358 1.827 2.963 3.337.645 1.614.748 3.639-.182 5.737-.537 1.212-1.344 2.245-2.39 3.056.002.025.003.05.003.075 0 2.09-.85 3.887-2.528 5.344-1.81 1.575-3.946 2.387-6.502 2.408z"/></svg>',
            'bluesky'  => '<svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12 10.816c1.355-1.704 2.94-3.043 4.757-4.017 2.256-1.215 4.243-1.508 4.243-1.508V24h-9V10.816zM12 10.816C10.645 9.112 9.06 7.773 7.243 6.799 4.987 5.584 3 5.291 3 5.291V24h9V10.816zM12 10.816V0S8.5 4 3 5.291M12 0s3.5 4 9 5.291"/></svg>',
            'pinterest'=> '<svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.668.967-2.914 2.171-2.914 1.023 0 1.518.769 1.518 1.69 0 1.029-.655 2.568-.994 3.995-.283 1.194.599 2.169 1.777 2.169 2.133 0 3.772-2.249 3.772-5.495 0-2.873-2.064-4.882-5.012-4.882-3.414 0-5.418 2.561-5.418 5.207 0 1.031.397 2.138.893 2.738a.36.36 0 01.083.345l-.333 1.36c-.053.22-.174.267-.402.161-1.499-.698-2.436-2.889-2.436-4.649 0-3.785 2.75-7.262 7.929-7.262 4.163 0 7.398 2.967 7.398 6.931 0 4.136-2.607 7.464-6.227 7.464-1.216 0-2.359-.631-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24 12.017 24 18.635 24 24 18.633 24 12.013 24 5.393 18.635.028 12.017.028z"/></svg>',
            'youtube'  => '<svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>',
        ];
        return $icons[$platform] ?? $icons['twitter'] ?? '';
    }
}
?>

<style>
    /* Custom scrollbar */
    .social-scroll::-webkit-scrollbar { width: 6px; }
    .social-scroll::-webkit-scrollbar-track { background: transparent; }
    .social-scroll::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 3px; }
    .social-scroll::-webkit-scrollbar-thumb:hover { background: #9ca3af; }

    /* Toast animation */
    @keyframes socialToastIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    @keyframes socialToastOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(100%); opacity: 0; } }
    .toast-in { animation: socialToastIn 0.3s ease-out forwards; }
    .toast-out { animation: socialToastOut 0.3s ease-in forwards; }

    /* Skeleton pulse */
    @keyframes socialPulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
    .social-skeleton { animation: socialPulse 1.5s ease-in-out infinite; background: #f3f4f6; border-radius: 6px; }

    /* Tab transitions */
    .social-panel { display: none; }
    .social-panel.active { display: block; animation: socialFadeIn 0.2s ease-out; }
    @keyframes socialFadeIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }

    /* Upload zone */
    .upload-zone { border: 2px dashed #d1d5db; transition: all 0.2s; }
    .upload-zone:hover, .upload-zone.dragover { border-color: #d97706; background: #fffbeb; }

    /* Platform colors */
    .platform-twitter { --pc: #1d9bf0; }
    .platform-facebook { --pc: #1877f2; }
    .platform-instagram { --pc: #e4405f; }
    .platform-tiktok { --pc: #010101; }
    .platform-linkedin { --pc: #0a66c2; }
    .platform-threads { --pc: #000000; }
    .platform-bluesky { --pc: #0085ff; }
    .platform-pinterest { --pc: #e60023; }
    .platform-youtube { --pc: #ff0000; }
</style>

<div class="space-y-6">
    <!-- Toast Container -->
    <div id="socialToastContainer" class="fixed top-4 right-4 z-50 flex flex-col gap-2 pointer-events-none"></div>

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-gradient-to-br from-amber-500 to-orange-600">
                <i data-lucide="share-2" class="w-5 h-5 text-white"></i>
            </div>
            <div>
                <h1 class="font-heading font-semibold text-xl text-gray-900">Social Publishing</h1>
                <p class="text-sm text-gray-500">Manage and publish across all platforms via Blotato</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span id="connectionBadge" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium <?= $blotatoConnected ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-red-50 text-red-700 ring-1 ring-red-200' ?>">
                <span class="w-1.5 h-1.5 rounded-full <?= $blotatoConnected ? 'bg-emerald-500' : 'bg-red-500' ?>"></span>
                <?= $blotatoConnected ? 'Blotato Connected' : 'Not Connected' ?>
            </span>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="border-b border-gray-200">
        <nav class="flex gap-0 -mb-px overflow-x-auto" id="socialTabs">
            <button type="button" data-tab="accounts" class="social-tab px-5 py-3 text-sm font-medium border-b-2 border-amber-600 text-amber-600 transition-colors whitespace-nowrap">
                <i data-lucide="users" class="w-4 h-4 inline-block mr-1.5 -mt-0.5"></i>Accounts
            </button>
            <button type="button" data-tab="compose" class="social-tab px-5 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors whitespace-nowrap">
                <i data-lucide="pen-line" class="w-4 h-4 inline-block mr-1.5 -mt-0.5"></i>Compose & Publish
            </button>
            <button type="button" data-tab="posts" class="social-tab px-5 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors whitespace-nowrap">
                <i data-lucide="history" class="w-4 h-4 inline-block mr-1.5 -mt-0.5"></i>Posts & History
            </button>
            <button type="button" data-tab="analytics" class="social-tab px-5 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors whitespace-nowrap">
                <i data-lucide="bar-chart-3" class="w-4 h-4 inline-block mr-1.5 -mt-0.5"></i>Analytics
            </button>
            <button type="button" data-tab="templates" class="social-tab px-5 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition-colors whitespace-nowrap">
                <i data-lucide="layout-template" class="w-4 h-4 inline-block mr-1.5 -mt-0.5"></i>Visual Templates
            </button>
        </nav>
    </div>

    <!-- ==================== TAB 1: ACCOUNTS ==================== -->
    <div id="tab-accounts" class="social-panel active">
        <!-- Connection Status Card -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center <?= $blotatoConnected ? 'bg-emerald-50' : 'bg-red-50' ?>">
                        <i data-lucide="<?= $blotatoConnected ? 'check-circle' : 'unplug' ?>" class="w-5 h-5 <?= $blotatoConnected ? 'text-emerald-600' : 'text-red-500' ?>"></i>
                    </div>
                    <div>
                        <h3 class="font-heading font-semibold text-gray-900">API Connection</h3>
                        <p class="text-sm text-gray-500"><?= $blotatoConnected ? 'Your Blotato API key is configured and active.' : 'Enter your Blotato API key to connect.' ?></p>
                    </div>
                </div>
                <?php if (!$blotatoConnected): ?>
                <form id="connectForm" method="POST" action="/admin/marketing/social/connect" class="flex items-center gap-2">
                    <input type="text" name="api_key" placeholder="Blotato API Key" required
                        class="w-64 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700 transition-colors">
                        <i data-lucide="plug" class="w-4 h-4"></i> Connect
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Accounts Grid Header -->
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-heading font-semibold text-gray-900">Connected Accounts</h2>
            <button type="button" onclick="fetchAccounts()" id="fetchAccountsBtn" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i> Fetch Accounts
            </button>
        </div>

        <!-- Accounts Grid -->
        <div id="accountsGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php if (empty($accounts)): ?>
            <!-- Empty State -->
            <div class="col-span-full bg-white rounded-xl border border-gray-100 shadow-sm p-12 text-center">
                <div class="w-16 h-16 rounded-2xl bg-gray-50 flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="link-2-off" class="w-8 h-8 text-gray-300"></i>
                </div>
                <h3 class="font-heading font-semibold text-gray-900 mb-1">No Accounts Connected</h3>
                <p class="text-sm text-gray-500 max-w-md mx-auto">Connect your social accounts on <a href="https://blotato.com" target="_blank" class="text-amber-600 hover:text-amber-700 font-medium">blotato.com</a> and click "Fetch Accounts" to see them here.</p>
            </div>
            <?php else: ?>
                <?php foreach ($accounts as $account): ?>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 hover:shadow-md transition-shadow">
                    <div class="flex items-start gap-3">
                        <img src="<?= htmlspecialchars($account['avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($account['name'] ?? 'U') . '&background=d97706&color=fff') ?>" alt="" class="w-11 h-11 rounded-full object-cover ring-2 ring-gray-100">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-0.5">
                                <h4 class="text-sm font-semibold text-gray-900 truncate"><?= htmlspecialchars($account['name'] ?? 'Unknown') ?></h4>
                                <span class="platform-<?= htmlspecialchars($account['platform'] ?? 'twitter') ?> inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wide" style="background: color-mix(in srgb, var(--pc) 10%, white); color: var(--pc);">
                                    <?= getSocialPlatformIcon($account['platform'] ?? 'twitter') ?>
                                    <?= htmlspecialchars($account['platform'] ?? 'twitter') ?>
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 truncate">@<?= htmlspecialchars($account['username'] ?? '') ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 mt-3 pt-3 border-t border-gray-50 text-xs text-gray-400">
                        <?php if (!empty($account['followers'])): ?>
                        <span class="flex items-center gap-1"><i data-lucide="users" class="w-3 h-3"></i> <?= number_format($account['followers']) ?> followers</span>
                        <?php endif; ?>
                        <?php if (!empty($account['subaccounts'])): ?>
                        <span class="flex items-center gap-1"><i data-lucide="folder" class="w-3 h-3"></i> <?= count($account['subaccounts']) ?> pages</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- ==================== TAB 2: COMPOSE & PUBLISH ==================== -->
    <div id="tab-compose" class="social-panel">
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
            <!-- Composer -->
            <div class="lg:col-span-3 space-y-5">
                <!-- Account Selector -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <div class="flex items-center gap-2 mb-4">
                        <i data-lucide="at-sign" class="w-5 h-5 text-amber-600"></i>
                        <h2 class="font-heading font-semibold text-gray-900">Select Account</h2>
                    </div>
                    <select id="composeAccount" onchange="onAccountSelect()" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 bg-white">
                        <option value="">-- Choose an account --</option>
                        <?php foreach ($accounts as $i => $acc): ?>
                        <option value="<?= $i ?>" data-platform="<?= htmlspecialchars($acc['platform'] ?? '') ?>" data-subaccounts="<?= htmlspecialchars(json_encode($acc['subaccounts'] ?? [])) ?>" data-boards="<?= htmlspecialchars(json_encode($acc['boards'] ?? [])) ?>">
                            <?= htmlspecialchars(($acc['name'] ?? 'Account') . ' (@' . ($acc['username'] ?? '') . ') — ' . ucfirst($acc['platform'] ?? 'unknown')) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Platform-specific fields -->
                    <div id="platformSpecificFields" class="mt-4 hidden">
                        <!-- Facebook: Page Selector -->
                        <div id="fields-facebook" class="hidden">
                            <label class="text-sm font-medium text-gray-700 block mb-1.5">Page</label>
                            <select id="fbPageId" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 bg-white">
                                <option value="">Select a page</option>
                            </select>
                        </div>
                        <!-- TikTok: Extra Options -->
                        <div id="fields-tiktok" class="hidden space-y-3">
                            <div>
                                <label class="text-sm font-medium text-gray-700 block mb-1.5">Privacy Level</label>
                                <select id="ttPrivacy" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 bg-white">
                                    <option value="PUBLIC_TO_EVERYONE">Public</option>
                                    <option value="FRIENDS_ONLY">Friends Only</option>
                                    <option value="PRIVATE">Private</option>
                                </select>
                            </div>
                            <div class="grid grid-cols-3 gap-3">
                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                    <input type="checkbox" id="ttComments" checked class="rounded border-gray-300 text-amber-600 focus:ring-amber-500"> Comments
                                </label>
                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                    <input type="checkbox" id="ttDuet" checked class="rounded border-gray-300 text-amber-600 focus:ring-amber-500"> Duet
                                </label>
                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                    <input type="checkbox" id="ttStitch" checked class="rounded border-gray-300 text-amber-600 focus:ring-amber-500"> Stitch
                                </label>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                    <input type="checkbox" id="ttBranded" class="rounded border-gray-300 text-amber-600 focus:ring-amber-500"> Branded Content
                                </label>
                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                    <input type="checkbox" id="ttBrandedVideo" class="rounded border-gray-300 text-amber-600 focus:ring-amber-500"> Branded Video
                                </label>
                            </div>
                        </div>
                        <!-- Pinterest: Board Selector -->
                        <div id="fields-pinterest" class="hidden">
                            <label class="text-sm font-medium text-gray-700 block mb-1.5">Board</label>
                            <select id="pinBoard" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 bg-white">
                                <option value="">Select a board</option>
                            </select>
                        </div>
                        <!-- YouTube: Extra Fields -->
                        <div id="fields-youtube" class="hidden space-y-3">
                            <div>
                                <label class="text-sm font-medium text-gray-700 block mb-1.5">Video Title</label>
                                <input type="text" id="ytTitle" placeholder="Enter video title" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700 block mb-1.5">Privacy Status</label>
                                <select id="ytPrivacy" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 bg-white">
                                    <option value="public">Public</option>
                                    <option value="unlisted">Unlisted</option>
                                    <option value="private">Private</option>
                                </select>
                            </div>
                            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" id="ytNotify" checked class="rounded border-gray-300 text-amber-600 focus:ring-amber-500"> Notify Subscribers
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <i data-lucide="message-square" class="w-5 h-5 text-amber-600"></i>
                            <h2 class="font-heading font-semibold text-gray-900">Content</h2>
                        </div>
                        <span class="text-xs text-gray-400" id="composeCharCount">0 / 2,200</span>
                    </div>
                    <textarea id="composeContent" rows="6" maxlength="2200" placeholder="Write your post content here..." oninput="updateComposeCharCount()" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 resize-y min-h-[140px]"></textarea>

                    <!-- Media URLs -->
                    <div class="mt-4">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-medium text-gray-700">Media URLs</label>
                            <button type="button" onclick="addMediaUrlField()" class="inline-flex items-center gap-1 text-xs text-amber-600 hover:text-amber-700 font-medium transition-colors">
                                <i data-lucide="plus" class="w-3 h-3"></i> Add Media URL
                            </button>
                        </div>
                        <div id="mediaUrlsContainer" class="space-y-2">
                            <div class="flex items-center gap-2">
                                <input type="url" placeholder="https://example.com/image.jpg" class="media-url-input flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                                <button type="button" onclick="removeMediaUrlField(this)" class="p-2 text-gray-400 hover:text-red-500 transition-colors">
                                    <i data-lucide="x" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- File Upload -->
                    <div class="mt-4">
                        <label class="text-sm font-medium text-gray-700 block mb-2">Upload Media</label>
                        <div id="uploadZone" class="upload-zone rounded-lg p-8 text-center cursor-pointer" onclick="document.getElementById('fileInput').click()" ondragover="event.preventDefault(); this.classList.add('dragover')" ondragleave="this.classList.remove('dragover')" ondrop="handleFileDrop(event)">
                            <input type="file" id="fileInput" class="hidden" accept="image/*,video/*" onchange="handleFileUpload(this)" multiple>
                            <i data-lucide="upload-cloud" class="w-10 h-10 text-gray-300 mx-auto mb-2"></i>
                            <p class="text-sm text-gray-500">Drop files here or <span class="text-amber-600 font-medium">browse</span></p>
                            <p class="text-xs text-gray-400 mt-1">Images and videos up to 50MB</p>
                        </div>
                        <div id="uploadProgress" class="hidden mt-3">
                            <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                                <span id="uploadFileName">Uploading...</span>
                                <span id="uploadPercent">0%</span>
                            </div>
                            <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                <div id="uploadBar" class="h-full bg-amber-500 rounded-full transition-all duration-300" style="width:0%"></div>
                            </div>
                        </div>
                        <div id="uploadedFiles" class="mt-3 flex flex-wrap gap-2"></div>
                    </div>
                </div>

                <!-- Schedule -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <div class="flex items-center gap-2 mb-4">
                        <i data-lucide="clock" class="w-5 h-5 text-amber-600"></i>
                        <h2 class="font-heading font-semibold text-gray-900">Schedule</h2>
                    </div>
                    <div class="space-y-3">
                        <label class="flex items-center gap-3 cursor-pointer p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50">
                            <input type="radio" name="scheduleType" value="now" checked onchange="toggleScheduleFields()" class="text-amber-600 focus:ring-amber-500">
                            <div>
                                <span class="text-sm font-medium text-gray-900">Publish Now</span>
                                <p class="text-xs text-gray-500">Post immediately after clicking publish</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50">
                            <input type="radio" name="scheduleType" value="scheduled" onchange="toggleScheduleFields()" class="text-amber-600 focus:ring-amber-500">
                            <div class="flex-1">
                                <span class="text-sm font-medium text-gray-900">Schedule</span>
                                <p class="text-xs text-gray-500">Choose a specific date and time</p>
                            </div>
                            <input type="datetime-local" id="scheduleDatetime" disabled class="px-3 py-1.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 disabled:opacity-40 disabled:cursor-not-allowed">
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50">
                            <input type="radio" name="scheduleType" value="nextslot" onchange="toggleScheduleFields()" class="text-amber-600 focus:ring-amber-500">
                            <div>
                                <span class="text-sm font-medium text-gray-900">Next Free Slot</span>
                                <p class="text-xs text-gray-500">Let Blotato pick the optimal time</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Publish Button -->
                <div class="flex items-center gap-3">
                    <button type="button" onclick="publishPost()" id="publishBtn" class="inline-flex items-center gap-2 px-6 py-2.5 bg-amber-600 text-white text-sm font-semibold rounded-lg hover:bg-amber-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <i data-lucide="send" class="w-4 h-4"></i> Publish
                    </button>
                    <div id="publishSpinner" class="hidden">
                        <svg class="animate-spin h-5 w-5 text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    </div>
                </div>
            </div>

            <!-- Preview Panel -->
            <div class="lg:col-span-2">
                <div class="sticky top-6">
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                        <div class="flex items-center gap-2 mb-4">
                            <i data-lucide="eye" class="w-5 h-5 text-amber-600"></i>
                            <h2 class="font-heading font-semibold text-gray-900">Preview</h2>
                        </div>
                        <div id="previewCard" class="rounded-xl border border-gray-200 overflow-hidden">
                            <!-- Preview Header -->
                            <div class="flex items-center gap-3 p-4">
                                <div id="previewAvatar" class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center">
                                    <i data-lucide="user" class="w-5 h-5 text-gray-400"></i>
                                </div>
                                <div>
                                    <p id="previewName" class="text-sm font-semibold text-gray-900">Account Name</p>
                                    <p id="previewUsername" class="text-xs text-gray-500">@username</p>
                                </div>
                                <span id="previewPlatformBadge" class="ml-auto hidden px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase" style="color: var(--pc); background: color-mix(in srgb, var(--pc) 10%, white);"></span>
                            </div>
                            <!-- Preview Content -->
                            <div class="px-4 pb-3">
                                <p id="previewContent" class="text-sm text-gray-800 whitespace-pre-wrap">Your post preview will appear here...</p>
                            </div>
                            <!-- Preview Media -->
                            <div id="previewMedia" class="hidden">
                                <div id="previewMediaGrid" class="grid grid-cols-2 gap-0.5"></div>
                            </div>
                            <!-- Preview Footer -->
                            <div class="flex items-center gap-6 px-4 py-3 border-t border-gray-100 text-gray-400">
                                <span class="flex items-center gap-1 text-xs"><i data-lucide="heart" class="w-4 h-4"></i> Like</span>
                                <span class="flex items-center gap-1 text-xs"><i data-lucide="message-circle" class="w-4 h-4"></i> Comment</span>
                                <span class="flex items-center gap-1 text-xs"><i data-lucide="repeat-2" class="w-4 h-4"></i> Share</span>
                            </div>
                        </div>
                    </div>

                    <!-- Post Status Card -->
                    <div id="postStatusCard" class="hidden mt-4 bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                        <div class="flex items-center gap-2 mb-3">
                            <i data-lucide="activity" class="w-5 h-5 text-amber-600"></i>
                            <h2 class="font-heading font-semibold text-gray-900">Publishing Status</h2>
                        </div>
                        <div id="postStatusContent" class="space-y-2">
                            <div class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                <span class="text-sm text-gray-600">Publishing...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== TAB 3: POSTS & HISTORY ==================== -->
    <div id="tab-posts" class="social-panel">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
            <!-- Toolbar -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-4 border-b border-gray-100">
                <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-0.5" id="postFilters">
                    <button type="button" data-filter="all" class="post-filter-btn px-3 py-1.5 text-xs font-medium rounded-md bg-white text-gray-900 shadow-sm transition-all">All</button>
                    <button type="button" data-filter="published" class="post-filter-btn px-3 py-1.5 text-xs font-medium rounded-md text-gray-500 hover:text-gray-700 transition-all">Published</button>
                    <button type="button" data-filter="failed" class="post-filter-btn px-3 py-1.5 text-xs font-medium rounded-md text-gray-500 hover:text-gray-700 transition-all">Failed</button>
                    <button type="button" data-filter="scheduled" class="post-filter-btn px-3 py-1.5 text-xs font-medium rounded-md text-gray-500 hover:text-gray-700 transition-all">Scheduled</button>
                </div>
                <button type="button" onclick="loadPosts()" id="refreshPostsBtn" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i> Refresh
                </button>
            </div>

            <!-- Posts Table -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider px-4 py-3">Platform</th>
                            <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider px-4 py-3">Account</th>
                            <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider px-4 py-3">Content</th>
                            <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider px-4 py-3">Status</th>
                            <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider px-4 py-3">Date</th>
                            <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider px-4 py-3">Link</th>
                            <th class="text-right text-xs font-medium text-gray-500 uppercase tracking-wider px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="postsTableBody" class="divide-y divide-gray-50">
                        <?php if (empty($recentPosts)): ?>
                        <tr>
                            <td colspan="7" class="px-4 py-16 text-center">
                                <div class="w-12 h-12 rounded-xl bg-gray-50 flex items-center justify-center mx-auto mb-3">
                                    <i data-lucide="inbox" class="w-6 h-6 text-gray-300"></i>
                                </div>
                                <p class="text-sm text-gray-500">No posts yet. Create your first post!</p>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($recentPosts as $post): ?>
                            <tr class="hover:bg-gray-50/50 transition-colors" data-status="<?= htmlspecialchars($post['status'] ?? 'published') ?>">
                                <td class="px-4 py-3">
                                    <span class="platform-<?= htmlspecialchars($post['platform'] ?? 'twitter') ?> inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase" style="background: color-mix(in srgb, var(--pc) 10%, white); color: var(--pc);">
                                        <?= getSocialPlatformIcon($post['platform'] ?? 'twitter') ?>
                                        <?= htmlspecialchars(ucfirst($post['platform'] ?? 'twitter')) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900 font-medium"><?= htmlspecialchars($post['account_name'] ?? 'Unknown') ?></td>
                                <td class="px-4 py-3 text-sm text-gray-600 max-w-xs truncate"><?= htmlspecialchars($post['content'] ?? '') ?></td>
                                <td class="px-4 py-3">
                                    <?php $st = $post['status'] ?? 'published'; ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium
                                        <?= $st === 'published' ? 'bg-emerald-50 text-emerald-700' : ($st === 'failed' ? 'bg-red-50 text-red-700' : 'bg-blue-50 text-blue-700') ?>">
                                        <span class="w-1.5 h-1.5 rounded-full <?= $st === 'published' ? 'bg-emerald-500' : ($st === 'failed' ? 'bg-red-500' : 'bg-blue-500') ?>"></span>
                                        <?= ucfirst($st) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500"><?= htmlspecialchars($post['date'] ?? date('M j, Y')) ?></td>
                                <td class="px-4 py-3">
                                    <?php if (!empty($post['url'])): ?>
                                    <a href="<?= htmlspecialchars($post['url']) ?>" target="_blank" class="inline-flex items-center gap-1 text-xs text-amber-600 hover:text-amber-700 font-medium">
                                        <i data-lucide="external-link" class="w-3 h-3"></i> View
                                    </a>
                                    <?php else: ?>
                                    <span class="text-xs text-gray-400">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <?php if ($st === 'scheduled'): ?>
                                    <button type="button" onclick="editScheduledPost('<?= htmlspecialchars($post['id'] ?? '') ?>')" class="text-gray-400 hover:text-amber-600 transition-colors mr-1"><i data-lucide="pencil" class="w-4 h-4"></i></button>
                                    <button type="button" onclick="deleteScheduledPost('<?= htmlspecialchars($post['id'] ?? '') ?>')" class="text-gray-400 hover:text-red-500 transition-colors"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ==================== TAB 4: ANALYTICS ==================== -->
    <div id="tab-analytics" class="social-panel">
        <!-- Filters -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-4">
            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                <div class="flex items-center gap-2">
                    <i data-lucide="filter" class="w-4 h-4 text-gray-400"></i>
                    <select id="analyticsPlatform" onchange="loadAnalytics()" class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 bg-white">
                        <option value="">All Platforms</option>
                        <option value="twitter">Twitter / X</option>
                        <option value="facebook">Facebook</option>
                        <option value="instagram">Instagram</option>
                        <option value="tiktok">TikTok</option>
                        <option value="linkedin">LinkedIn</option>
                        <option value="threads">Threads</option>
                        <option value="bluesky">Bluesky</option>
                        <option value="pinterest">Pinterest</option>
                        <option value="youtube">YouTube</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500">Sort by:</span>
                    <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-0.5" id="analyticsSortBtns">
                        <button type="button" data-sort="views" class="analytics-sort-btn px-3 py-1.5 text-xs font-medium rounded-md bg-white text-gray-900 shadow-sm transition-all">Views</button>
                        <button type="button" data-sort="likes" class="analytics-sort-btn px-3 py-1.5 text-xs font-medium rounded-md text-gray-500 hover:text-gray-700 transition-all">Likes</button>
                        <button type="button" data-sort="comments" class="analytics-sort-btn px-3 py-1.5 text-xs font-medium rounded-md text-gray-500 hover:text-gray-700 transition-all">Comments</button>
                        <button type="button" data-sort="reach" class="analytics-sort-btn px-3 py-1.5 text-xs font-medium rounded-md text-gray-500 hover:text-gray-700 transition-all">Reach</button>
                    </div>
                </div>
                <button type="button" onclick="loadAnalytics()" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors sm:ml-auto">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i> Refresh
                </button>
            </div>
        </div>

        <!-- Analytics Grid -->
        <div id="analyticsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Empty / Loading state -->
            <div id="analyticsEmpty" class="col-span-full bg-white rounded-xl border border-gray-100 shadow-sm p-12 text-center">
                <div class="w-16 h-16 rounded-2xl bg-gray-50 flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="bar-chart-3" class="w-8 h-8 text-gray-300"></i>
                </div>
                <h3 class="font-heading font-semibold text-gray-900 mb-1">No Analytics Yet</h3>
                <p class="text-sm text-gray-500 max-w-md mx-auto">Publish some posts and come back to see how they perform across platforms.</p>
            </div>
        </div>
    </div>

    <!-- ==================== TAB 5: VISUAL TEMPLATES ==================== -->
    <div id="tab-templates" class="social-panel">
        <!-- Templates Grid -->
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-heading font-semibold text-gray-900">Visual Templates</h2>
            <button type="button" onclick="loadTemplates()" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i> Refresh
            </button>
        </div>
        <div id="templatesGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <!-- Will be populated by JS -->
            <div id="templatesEmpty" class="col-span-full bg-white rounded-xl border border-gray-100 shadow-sm p-12 text-center">
                <div class="w-16 h-16 rounded-2xl bg-gray-50 flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="layout-template" class="w-8 h-8 text-gray-300"></i>
                </div>
                <h3 class="font-heading font-semibold text-gray-900 mb-1">No Templates Available</h3>
                <p class="text-sm text-gray-500 max-w-md mx-auto">Visual templates will appear here once configured in Blotato.</p>
            </div>
        </div>

        <!-- Generate Visual Modal (inline) -->
        <div id="generateVisualModal" class="hidden fixed inset-0 z-40 flex items-center justify-center bg-black/40 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 w-full max-w-lg mx-4 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-heading font-semibold text-gray-900">Generate Visual</h3>
                    <button type="button" onclick="closeGenerateModal()" class="p-1 text-gray-400 hover:text-gray-600 transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
                </div>
                <div id="generateVisualForm">
                    <p class="text-sm text-gray-500 mb-3">Template: <span id="generateTemplateName" class="font-medium text-gray-900"></span></p>
                    <div class="mb-4">
                        <label class="text-sm font-medium text-gray-700 block mb-1.5">Prompt</label>
                        <textarea id="generatePrompt" rows="4" placeholder="Describe what you want the visual to look like..." class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 resize-y"></textarea>
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="button" onclick="generateVisual()" id="generateVisualBtn" class="inline-flex items-center gap-2 px-5 py-2.5 bg-amber-600 text-white text-sm font-semibold rounded-lg hover:bg-amber-700 transition-colors disabled:opacity-50">
                            <i data-lucide="sparkles" class="w-4 h-4"></i> Generate
                        </button>
                        <button type="button" onclick="closeGenerateModal()" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">Cancel</button>
                    </div>
                </div>
                <div id="generateVisualStatus" class="hidden mt-4 p-4 rounded-lg bg-gray-50">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="animate-spin h-4 w-4 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <span class="text-sm text-gray-600">Generating visual...</span>
                    </div>
                    <div class="w-full h-1.5 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full bg-amber-500 rounded-full animate-pulse" style="width: 60%"></div>
                    </div>
                </div>
                <div id="generateVisualResult" class="hidden mt-4">
                    <p class="text-sm font-medium text-emerald-700 mb-2 flex items-center gap-1.5"><i data-lucide="check-circle" class="w-4 h-4"></i> Visual generated successfully!</p>
                    <img id="generatedImageUrl" src="" alt="Generated visual" class="w-full rounded-lg border border-gray-200 mb-3">
                    <button type="button" onclick="closeGenerateModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">Done</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// =============================================
// SOCIAL PUBLISHING — Vanilla JS
// =============================================

(function() {
    'use strict';

    // ---------- STATE ----------
    let currentTab = 'accounts';
    let currentPostFilter = 'all';
    let currentAnalyticsSort = 'views';
    let currentGenerateTemplateId = null;
    let uploadedMediaUrls = [];
    let accountsData = <?= json_encode($accounts) ?>;
    let isPublishing = false;

    // ---------- HELPERS ----------
    function $(sel) { return document.querySelector(sel); }
    function $$(sel) { return document.querySelectorAll(sel); }

    function showToast(message, type = 'success') {
        const container = $('#socialToastContainer');
        const colors = {
            success: 'bg-emerald-600',
            error: 'bg-red-600',
            info: 'bg-amber-600'
        };
        const icons = {
            success: 'check-circle',
            error: 'alert-circle',
            info: 'info'
        };
        const toast = document.createElement('div');
        toast.className = `pointer-events-auto flex items-center gap-2 px-4 py-3 rounded-lg shadow-lg text-white text-sm font-medium toast-in ${colors[type] || colors.info}`;
        toast.innerHTML = `<i data-lucide="${icons[type] || icons.info}" class="w-4 h-4 shrink-0"></i><span>${message}</span>`;
        container.appendChild(toast);
        if (typeof lucide !== 'undefined') lucide.createIcons();
        setTimeout(() => {
            toast.classList.remove('toast-in');
            toast.classList.add('toast-out');
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    }

    function showLoading(btn, loading = true) {
        if (!btn) return;
        if (loading) {
            btn.disabled = true;
            btn.dataset.originalHtml = btn.innerHTML;
            btn.innerHTML = '<svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Loading...';
        } else {
            btn.disabled = false;
            btn.innerHTML = btn.dataset.originalHtml || btn.innerHTML;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    }

    function apiCall(url, options = {}) {
        const defaults = { headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' } };
        if (options.body && !(options.body instanceof FormData)) {
            defaults.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(options.body);
        } else if (options.body instanceof FormData) {
            delete defaults.headers['Content-Type'];
        }
        return fetch(url, { ...defaults, ...options, headers: { ...defaults.headers, ...options.headers } })
            .then(r => {
                if (!r.ok) throw new Error(`HTTP ${r.status}: ${r.statusText}`);
                return r.json();
            });
    }

    function pollStatus(url, maxAttempts = 30, interval = 2000) {
        return new Promise((resolve, reject) => {
            let attempts = 0;
            const timer = setInterval(async () => {
                attempts++;
                try {
                    const data = await apiCall(url);
                    if (data.status === 'published' || data.status === 'completed' || data.status === 'success') {
                        clearInterval(timer);
                        resolve(data);
                    } else if (data.status === 'failed' || data.status === 'error') {
                        clearInterval(timer);
                        reject(new Error(data.message || 'Operation failed'));
                    } else if (attempts >= maxAttempts) {
                        clearInterval(timer);
                        reject(new Error('Polling timed out'));
                    }
                } catch (e) {
                    if (attempts >= maxAttempts) {
                        clearInterval(timer);
                        reject(e);
                    }
                }
            }, interval);
        });
    }

    function formatNumber(n) {
        if (n == null) return '0';
        if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M';
        if (n >= 1000) return (n / 1000).toFixed(1) + 'K';
        return n.toString();
    }

    // ---------- PLATFORM ICON SVGs ----------
    const platformIcons = {
        twitter: '<svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
        facebook: '<svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
        instagram: '<svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678a6.162 6.162 0 100 12.324 6.162 6.162 0 100-12.324zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405a1.441 1.441 0 11-2.882 0 1.441 1.441 0 012.882 0z"/></svg>',
        tiktok: '<svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1v-3.5a6.37 6.37 0 00-.79-.05A6.34 6.34 0 003.15 15.2a6.34 6.34 0 0010.86 4.46V13a8.28 8.28 0 005.58 2.15v-3.44a4.85 4.85 0 01-3.77-1.26V6.69h3.77z"/></svg>',
        linkedin: '<svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
        threads: '<svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12.186 24h-.007c-3.581-.024-6.334-1.205-8.184-3.509C2.35 18.44 1.5 15.586 1.472 12.01v-.017c.03-3.579.879-6.43 2.525-8.482C5.845 1.205 8.6.024 12.18 0h.014c2.746.02 5.043.725 6.826 2.098 1.677 1.29 2.858 3.13 3.509 5.467l-2.04.569c-1.104-3.96-3.898-5.984-8.304-6.015-2.91.022-5.11.936-6.54 2.717C4.307 6.504 3.616 8.914 3.589 12c.027 3.086.718 5.496 2.057 7.164 1.43 1.783 3.631 2.698 6.54 2.717 2.623-.02 4.358-.631 5.798-2.045 1.647-1.613 1.618-3.593 1.09-4.798-.31-.71-.873-1.3-1.634-1.75-.192 1.352-.622 2.446-1.284 3.272-.886 1.102-2.14 1.704-3.73 1.79-1.202.065-2.361-.218-3.259-.795-1.063-.681-1.685-1.714-1.752-2.91-.065-1.17.434-2.242 1.405-3.021.928-.744 2.197-1.168 3.676-1.224 1.18-.042 2.31.115 3.347.463-.104-.682-.34-1.223-.72-1.612-.563-.577-1.426-.875-2.565-.886h-.032l-.041-2c1.68.017 3.038.5 4.039 1.435.745.697 1.24 1.624 1.47 2.754.48-.44.862-.95 1.118-1.52.673-1.516.542-3.217.144-4.263-.4-1.046-1.126-1.854-2.08-2.33l.905-1.782c1.332.676 2.358 1.827 2.963 3.337.645 1.614.748 3.639-.182 5.737-.537 1.212-1.344 2.245-2.39 3.056.002.025.003.05.003.075 0 2.09-.85 3.887-2.528 5.344-1.81 1.575-3.946 2.387-6.502 2.408z"/></svg>',
        bluesky: '<svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12 10.816c1.355-1.704 2.94-3.043 4.757-4.017 2.256-1.215 4.243-1.508 4.243-1.508V24h-9V10.816zM12 10.816C10.645 9.112 9.06 7.773 7.243 6.799 4.987 5.584 3 5.291 3 5.291V24h9V10.816zM12 10.816V0S8.5 4 3 5.291M12 0s3.5 4 9 5.291"/></svg>',
        pinterest: '<svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.668.967-2.914 2.171-2.914 1.023 0 1.518.769 1.518 1.69 0 1.029-.655 2.568-.994 3.995-.283 1.194.599 2.169 1.777 2.169 2.133 0 3.772-2.249 3.772-5.495 0-2.873-2.064-4.882-5.012-4.882-3.414 0-5.418 2.561-5.418 5.207 0 1.031.397 2.138.893 2.738a.36.36 0 01.083.345l-.333 1.36c-.053.22-.174.267-.402.161-1.499-.698-2.436-2.889-2.436-4.649 0-3.785 2.75-7.262 7.929-7.262 4.163 0 7.398 2.967 7.398 6.931 0 4.136-2.607 7.464-6.227 7.464-1.216 0-2.359-.631-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24 12.017 24 18.635 24 24 18.633 24 12.013 24 5.393 18.635.028 12.017.028z"/></svg>',
        youtube: '<svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>'
    };

    function getPlatformIcon(platform) {
        return platformIcons[platform] || platformIcons.twitter;
    }

    // ---------- TAB SWITCHING ----------
    $$('.social-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            const target = tab.dataset.tab;
            $$('.social-tab').forEach(t => {
                t.classList.remove('border-amber-600', 'text-amber-600');
                t.classList.add('border-transparent', 'text-gray-500');
            });
            tab.classList.remove('border-transparent', 'text-gray-500');
            tab.classList.add('border-amber-600', 'text-amber-600');

            $$('.social-panel').forEach(p => p.classList.remove('active'));
            const panel = $(`#tab-${target}`);
            if (panel) panel.classList.add('active');
            currentTab = target;

            // Lazy load
            if (target === 'posts') loadPosts();
            if (target === 'analytics') loadAnalytics();
            if (target === 'templates') loadTemplates();

            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    });

    // ---------- TAB 1: ACCOUNTS ----------
    window.fetchAccounts = async function() {
        const btn = $('#fetchAccountsBtn');
        showLoading(btn);
        try {
            const data = await apiCall('/admin/marketing/social/accounts', { method: 'POST' });
            accountsData = data.accounts || [];
            renderAccounts(accountsData);
            showToast(`${accountsData.length} account(s) fetched successfully`);
        } catch (e) {
            showToast('Failed to fetch accounts: ' + e.message, 'error');
        } finally {
            showLoading(btn, false);
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    };

    function renderAccounts(accounts) {
        const grid = $('#accountsGrid');
        if (!accounts || accounts.length === 0) {
            grid.innerHTML = `
                <div class="col-span-full bg-white rounded-xl border border-gray-100 shadow-sm p-12 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-gray-50 flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="link-2-off" class="w-8 h-8 text-gray-300"></i>
                    </div>
                    <h3 class="font-heading font-semibold text-gray-900 mb-1">No Accounts Connected</h3>
                    <p class="text-sm text-gray-500 max-w-md mx-auto">Connect your social accounts on <a href="https://blotato.com" target="_blank" class="text-amber-600 hover:text-amber-700 font-medium">blotato.com</a> and click "Fetch Accounts" to see them here.</p>
                </div>`;
            if (typeof lucide !== 'undefined') lucide.createIcons();
            return;
        }
        grid.innerHTML = accounts.map(acc => {
            const platform = acc.platform || 'twitter';
            const avatar = acc.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(acc.name || 'U')}&background=d97706&color=fff`;
            const extra = [];
            if (acc.followers) extra.push(`<span class="flex items-center gap-1"><i data-lucide="users" class="w-3 h-3"></i> ${formatNumber(acc.followers)} followers</span>`);
            if (acc.subaccounts && acc.subaccounts.length) extra.push(`<span class="flex items-center gap-1"><i data-lucide="folder" class="w-3 h-3"></i> ${acc.subaccounts.length} pages</span>`);
            return `
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 hover:shadow-md transition-shadow">
                    <div class="flex items-start gap-3">
                        <img src="${avatar}" alt="" class="w-11 h-11 rounded-full object-cover ring-2 ring-gray-100">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-0.5">
                                <h4 class="text-sm font-semibold text-gray-900 truncate">${escapeHtml(acc.name || 'Unknown')}</h4>
                                <span class="platform-${platform} inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wide" style="background: color-mix(in srgb, var(--pc) 10%, white); color: var(--pc);">
                                    ${getPlatformIcon(platform)}
                                    ${escapeHtml(platform)}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 truncate">@${escapeHtml(acc.username || '')}</p>
                        </div>
                    </div>
                    ${extra.length ? `<div class="flex items-center gap-3 mt-3 pt-3 border-t border-gray-50 text-xs text-gray-400">${extra.join('')}</div>` : ''}
                </div>`;
        }).join('');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // ---------- TAB 2: COMPOSE & PUBLISH ----------
    window.onAccountSelect = function() {
        const sel = $('#composeAccount');
        const opt = sel.options[sel.selectedIndex];
        const platform = opt.dataset.platform || '';
        const fieldsContainer = $('#platformSpecificFields');

        // Hide all platform fields
        ['facebook', 'tiktok', 'pinterest', 'youtube'].forEach(p => {
            const el = $(`#fields-${p}`);
            if (el) el.classList.add('hidden');
        });

        if (!platform) {
            fieldsContainer.classList.add('hidden');
            updatePreview();
            return;
        }

        fieldsContainer.classList.remove('hidden');

        if (platform === 'facebook') {
            const el = $('#fields-facebook');
            el.classList.remove('hidden');
            const subaccounts = JSON.parse(opt.dataset.subaccounts || '[]');
            const select = $('#fbPageId');
            select.innerHTML = '<option value="">Select a page</option>' + subaccounts.map(s => `<option value="${escapeHtml(s.id || '')}">${escapeHtml(s.name || s.id || '')}</option>`).join('');
        } else if (platform === 'tiktok') {
            $('#fields-tiktok').classList.remove('hidden');
        } else if (platform === 'pinterest') {
            const el = $('#fields-pinterest');
            el.classList.remove('hidden');
            const boards = JSON.parse(opt.dataset.boards || '[]');
            const select = $('#pinBoard');
            select.innerHTML = '<option value="">Select a board</option>' + boards.map(b => `<option value="${escapeHtml(b.id || '')}">${escapeHtml(b.name || b.id || '')}</option>`).join('');
        } else if (platform === 'youtube') {
            $('#fields-youtube').classList.remove('hidden');
        }

        // Update char limit
        const isTwitter = platform === 'twitter';
        const textarea = $('#composeContent');
        textarea.maxLength = isTwitter ? 280 : 2200;
        updateComposeCharCount();
        updatePreview();
    };

    window.updateComposeCharCount = function() {
        const textarea = $('#composeContent');
        const count = textarea.value.length;
        const max = parseInt(textarea.maxLength) || 2200;
        const el = $('#composeCharCount');
        el.textContent = `${count.toLocaleString()} / ${max.toLocaleString()}`;
        el.className = count > max * 0.9 ? 'text-xs text-red-500 font-medium' : 'text-xs text-gray-400';
        updatePreview();
    };

    function updatePreview() {
        const sel = $('#composeAccount');
        const opt = sel.options[sel.selectedIndex];
        const platform = opt ? opt.dataset.platform : '';
        const text = $('#composeContent').value || 'Your post preview will appear here...';

        const previewName = $('#previewName');
        const previewUsername = $('#previewUsername');
        const previewAvatar = $('#previewAvatar');
        const previewContent = $('#previewContent');
        const previewBadge = $('#previewPlatformBadge');
        const previewMedia = $('#previewMedia');

        if (opt && opt.value) {
            const parts = opt.textContent.split(' — ');
            previewName.textContent = parts[0] || 'Account Name';
            previewUsername.textContent = parts[0] ? ('@' + (parts[0].match(/@(\S+)/) || ['', 'username'])[1]) : '@username';
            const acc = accountsData[parseInt(opt.value)];
            if (acc && acc.avatar) {
                previewAvatar.innerHTML = `<img src="${escapeHtml(acc.avatar)}" class="w-10 h-10 rounded-full object-cover">`;
            } else {
                previewAvatar.innerHTML = `<i data-lucide="user" class="w-5 h-5 text-gray-400"></i>`;
            }
        } else {
            previewName.textContent = 'Account Name';
            previewUsername.textContent = '@username';
            previewAvatar.innerHTML = `<i data-lucide="user" class="w-5 h-5 text-gray-400"></i>`;
        }

        if (platform) {
            previewBadge.classList.remove('hidden');
            previewBadge.className = `ml-auto platform-${platform} px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase`;
            previewBadge.style.cssText = `color: var(--pc); background: color-mix(in srgb, var(--pc) 10%, white);`;
            previewBadge.innerHTML = getPlatformIcon(platform) + ' ' + escapeHtml(platform);
        } else {
            previewBadge.classList.add('hidden');
        }

        previewContent.textContent = text;

        // Show uploaded media in preview
        const allMedia = [...uploadedMediaUrls];
        $$('.media-url-input').forEach(input => {
            if (input.value.trim()) allMedia.push(input.value.trim());
        });

        if (allMedia.length > 0) {
            previewMedia.classList.remove('hidden');
            const cols = allMedia.length === 1 ? 'grid-cols-1' : 'grid-cols-2';
            $('#previewMediaGrid').className = `grid ${cols} gap-0.5`;
            $('#previewMediaGrid').innerHTML = allMedia.slice(0, 4).map(url =>
                `<img src="${escapeHtml(url)}" alt="" class="w-full h-40 object-cover" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22400%22 height=%22400%22><rect fill=%22%23f3f4f6%22 width=%22400%22 height=%22400%22/><text x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 fill=%22%239ca3af%22 font-size=%2216%22>Media</text></svg>'">`
            ).join('');
        } else {
            previewMedia.classList.add('hidden');
        }

        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    window.toggleScheduleFields = function() {
        const type = document.querySelector('input[name="scheduleType"]:checked').value;
        const dtInput = $('#scheduleDatetime');
        dtInput.disabled = type !== 'scheduled';
    };

    window.addMediaUrlField = function() {
        const container = $('#mediaUrlsContainer');
        const div = document.createElement('div');
        div.className = 'flex items-center gap-2';
        div.innerHTML = `
            <input type="url" placeholder="https://example.com/image.jpg" class="media-url-input flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
            <button type="button" onclick="removeMediaUrlField(this)" class="p-2 text-gray-400 hover:text-red-500 transition-colors"><i data-lucide="x" class="w-4 h-4"></i></button>`;
        container.appendChild(div);
        if (typeof lucide !== 'undefined') lucide.createIcons();
        div.querySelector('.media-url-input').addEventListener('input', updatePreview);
    };

    window.removeMediaUrlField = function(btn) {
        btn.closest('.flex').remove();
        updatePreview();
    };

    // Listen to existing media URL inputs
    $$('.media-url-input').forEach(input => input.addEventListener('input', updatePreview));

    window.handleFileDrop = function(e) {
        e.preventDefault();
        e.currentTarget.classList.remove('dragover');
        const files = e.dataTransfer.files;
        if (files.length) uploadFiles(files);
    };

    window.handleFileUpload = function(input) {
        if (input.files.length) uploadFiles(input.files);
    };

    async function uploadFiles(files) {
        const progress = $('#uploadProgress');
        const bar = $('#uploadBar');
        const fileName = $('#uploadFileName');
        const percent = $('#uploadPercent');

        progress.classList.remove('hidden');

        for (const file of files) {
            fileName.textContent = `Uploading ${file.name}...`;
            bar.style.width = '0%';
            percent.textContent = '0%';

            const formData = new FormData();
            formData.append('file', file);

            try {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', '/admin/marketing/social/upload');

                xhr.upload.onprogress = (e) => {
                    if (e.lengthComputable) {
                        const pct = Math.round((e.loaded / e.total) * 100);
                        bar.style.width = pct + '%';
                        percent.textContent = pct + '%';
                    }
                };

                await new Promise((resolve, reject) => {
                    xhr.onload = () => {
                        if (xhr.status >= 200 && xhr.status < 300) {
                            resolve(JSON.parse(xhr.responseText));
                        } else {
                            reject(new Error(`Upload failed: ${xhr.status}`));
                        }
                    };
                    xhr.onerror = () => reject(new Error('Upload failed'));
                    xhr.send(formData);
                });

                const result = JSON.parse(xhr.responseText);
                if (result.url) {
                    uploadedMediaUrls.push(result.url);
                    renderUploadedFiles();
                    updatePreview();
                    showToast(`${file.name} uploaded successfully`);
                }
            } catch (e) {
                showToast(`Failed to upload ${file.name}`, 'error');
            }
        }

        progress.classList.add('hidden');
        // Reset file input
        $('#fileInput').value = '';
    }

    function renderUploadedFiles() {
        const container = $('#uploadedFiles');
        if (uploadedMediaUrls.length === 0) {
            container.innerHTML = '';
            return;
        }
        container.innerHTML = uploadedMediaUrls.map((url, i) => `
            <div class="relative group inline-block">
                <img src="${escapeHtml(url)}" class="w-16 h-16 rounded-lg object-cover border border-gray-200">
                <button type="button" onclick="removeUploadedFile(${i})" class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                    <i data-lucide="x" class="w-3 h-3"></i>
                </button>
            </div>
        `).join('');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    window.removeUploadedFile = function(index) {
        uploadedMediaUrls.splice(index, 1);
        renderUploadedFiles();
        updatePreview();
    };

    window.publishPost = async function() {
        if (isPublishing) return;

        const sel = $('#composeAccount');
        if (!sel.value) {
            showToast('Please select an account', 'error');
            return;
        }

        const content = $('#composeContent').value.trim();
        if (!content) {
            showToast('Please enter post content', 'error');
            return;
        }

        const accountIdx = parseInt(sel.value);
        const account = accountsData[accountIdx];
        const platform = account?.platform || '';

        const mediaUrls = [...uploadedMediaUrls];
        $$('.media-url-input').forEach(input => {
            if (input.value.trim()) mediaUrls.push(input.value.trim());
        });

        const scheduleType = document.querySelector('input[name="scheduleType"]:checked').value;
        const payload = {
            account_id: account?.id || accountIdx.toString(),
            platform: platform,
            content: content,
            media_urls: mediaUrls,
            schedule_type: scheduleType
        };

        // Platform-specific fields
        if (platform === 'facebook') {
            payload.page_id = $('#fbPageId').value;
        } else if (platform === 'tiktok') {
            payload.privacy = $('#ttPrivacy').value;
            payload.allow_comments = $('#ttComments').checked;
            payload.allow_duet = $('#ttDuet').checked;
            payload.allow_stitch = $('#ttStitch').checked;
            payload.branded_content = $('#ttBranded').checked;
            payload.branded_video = $('#ttBrandedVideo').checked;
        } else if (platform === 'pinterest') {
            payload.board_id = $('#pinBoard').value;
        } else if (platform === 'youtube') {
            payload.title = $('#ytTitle').value;
            payload.privacy_status = $('#ytPrivacy').value;
            payload.notify_subscribers = $('#ytNotify').checked;
        }

        if (scheduleType === 'scheduled') {
            payload.scheduled_at = $('#scheduleDatetime').value;
        }

        isPublishing = true;
        const btn = $('#publishBtn');
        const spinner = $('#publishSpinner');
        const statusCard = $('#postStatusCard');
        const statusContent = $('#postStatusContent');

        btn.disabled = true;
        btn.classList.add('opacity-50');
        spinner.classList.remove('hidden');
        statusCard.classList.remove('hidden');
        statusContent.innerHTML = `
            <div class="flex items-center gap-2">
                <svg class="animate-spin h-4 w-4 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                <span class="text-sm text-gray-600">Sending to Blotato...</span>
            </div>`;

        try {
            const result = await apiCall('/admin/marketing/social/publish', {
                method: 'POST',
                body: payload
            });

            if (result.post_id) {
                statusContent.innerHTML = `
                    <div class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <span class="text-sm text-gray-600">Waiting for publish status...</span>
                    </div>`;

                const status = await pollStatus(`/admin/marketing/social/post-status?id=${result.post_id}`);

                statusContent.innerHTML = `
                    <div class="flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500"></i>
                        <div>
                            <p class="text-sm font-medium text-emerald-700">Published successfully!</p>
                            ${status.url ? `<a href="${escapeHtml(status.url)}" target="_blank" class="text-xs text-amber-600 hover:text-amber-700">View post &rarr;</a>` : ''}
                        </div>
                    </div>`;
                showToast('Post published successfully!');
                if (typeof lucide !== 'undefined') lucide.createIcons();
            } else {
                throw new Error('No post ID returned');
            }
        } catch (e) {
            statusContent.innerHTML = `
                <div class="flex items-center gap-2">
                    <i data-lucide="alert-circle" class="w-5 h-5 text-red-500"></i>
                    <div>
                        <p class="text-sm font-medium text-red-700">Publishing failed</p>
                        <p class="text-xs text-gray-500">${escapeHtml(e.message)}</p>
                    </div>
                </div>`;
            showToast('Failed to publish: ' + e.message, 'error');
            if (typeof lucide !== 'undefined') lucide.createIcons();
        } finally {
            isPublishing = false;
            btn.disabled = false;
            btn.classList.remove('opacity-50');
            spinner.classList.add('hidden');
        }
    };

    // ---------- TAB 3: POSTS & HISTORY ----------
    $$('.post-filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            $$('.post-filter-btn').forEach(b => {
                b.classList.remove('bg-white', 'text-gray-900', 'shadow-sm');
                b.classList.add('text-gray-500');
            });
            btn.classList.add('bg-white', 'text-gray-900', 'shadow-sm');
            btn.classList.remove('text-gray-500');
            currentPostFilter = btn.dataset.filter;
            filterPosts();
        });
    });

    function filterPosts() {
        $$('#postsTableBody tr[data-status]').forEach(row => {
            if (currentPostFilter === 'all' || row.dataset.status === currentPostFilter) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    window.loadPosts = async function() {
        const btn = $('#refreshPostsBtn');
        showLoading(btn);
        try {
            const data = await apiCall('/admin/marketing/social/posts');
            const posts = data.posts || [];
            renderPosts(posts);
            showToast(`${posts.length} post(s) loaded`);
        } catch (e) {
            showToast('Failed to load posts: ' + e.message, 'error');
        } finally {
            showLoading(btn, false);
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    };

    function renderPosts(posts) {
        const tbody = $('#postsTableBody');
        if (!posts || posts.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="px-4 py-16 text-center">
                        <div class="w-12 h-12 rounded-xl bg-gray-50 flex items-center justify-center mx-auto mb-3">
                            <i data-lucide="inbox" class="w-6 h-6 text-gray-300"></i>
                        </div>
                        <p class="text-sm text-gray-500">No posts yet. Create your first post!</p>
                    </td>
                </tr>`;
            if (typeof lucide !== 'undefined') lucide.createIcons();
            return;
        }

        tbody.innerHTML = posts.map(post => {
            const st = post.status || 'published';
            const statusClass = st === 'published' ? 'bg-emerald-50 text-emerald-700' : (st === 'failed' ? 'bg-red-50 text-red-700' : 'bg-blue-50 text-blue-700');
            const dotClass = st === 'published' ? 'bg-emerald-500' : (st === 'failed' ? 'bg-red-500' : 'bg-blue-500');
            const actions = st === 'scheduled' ? `
                <button type="button" onclick="editScheduledPost('${escapeHtml(post.id || '')}')" class="text-gray-400 hover:text-amber-600 transition-colors mr-1"><i data-lucide="pencil" class="w-4 h-4"></i></button>
                <button type="button" onclick="deleteScheduledPost('${escapeHtml(post.id || '')}')" class="text-gray-400 hover:text-red-500 transition-colors"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
            ` : '';
            const urlLink = post.url ? `<a href="${escapeHtml(post.url)}" target="_blank" class="inline-flex items-center gap-1 text-xs text-amber-600 hover:text-amber-700 font-medium"><i data-lucide="external-link" class="w-3 h-3"></i> View</a>` : `<span class="text-xs text-gray-400">&mdash;</span>`;

            return `
                <tr class="hover:bg-gray-50/50 transition-colors" data-status="${escapeHtml(st)}">
                    <td class="px-4 py-3">
                        <span class="platform-${escapeHtml(post.platform || 'twitter')} inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase" style="background: color-mix(in srgb, var(--pc) 10%, white); color: var(--pc);">
                            ${getPlatformIcon(post.platform || 'twitter')}
                            ${escapeHtml(ucfirst(post.platform || 'twitter'))}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-900 font-medium">${escapeHtml(post.account_name || 'Unknown')}</td>
                    <td class="px-4 py-3 text-sm text-gray-600 max-w-xs truncate">${escapeHtml(post.content || '')}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium ${statusClass}">
                            <span class="w-1.5 h-1.5 rounded-full ${dotClass}"></span>
                            ${ucfirst(st)}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500">${escapeHtml(post.date || '')}</td>
                    <td class="px-4 py-3">${urlLink}</td>
                    <td class="px-4 py-3 text-right">${actions}</td>
                </tr>`;
        }).join('');

        filterPosts();
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function ucfirst(str) { return str.charAt(0).toUpperCase() + str.slice(1); }

    window.editScheduledPost = async function(postId) {
        const newTime = prompt('Enter new scheduled datetime (YYYY-MM-DD HH:MM):');
        if (!newTime) return;
        try {
            await apiCall('/admin/marketing/social/posts/schedule', {
                method: 'PUT',
                body: { post_id: postId, scheduled_at: newTime }
            });
            showToast('Schedule updated');
            loadPosts();
        } catch (e) {
            showToast('Failed to update schedule: ' + e.message, 'error');
        }
    };

    window.deleteScheduledPost = async function(postId) {
        if (!confirm('Delete this scheduled post?')) return;
        try {
            await apiCall('/admin/marketing/social/posts/' + postId, { method: 'DELETE' });
            showToast('Post deleted');
            loadPosts();
        } catch (e) {
            showToast('Failed to delete post: ' + e.message, 'error');
        }
    };

    // ---------- TAB 4: ANALYTICS ----------
    $$('.analytics-sort-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            $$('.analytics-sort-btn').forEach(b => {
                b.classList.remove('bg-white', 'text-gray-900', 'shadow-sm');
                b.classList.add('text-gray-500');
            });
            btn.classList.add('bg-white', 'text-gray-900', 'shadow-sm');
            btn.classList.remove('text-gray-500');
            currentAnalyticsSort = btn.dataset.sort;
            loadAnalytics();
        });
    });

    window.loadAnalytics = async function() {
        const platform = $('#analyticsPlatform').value;
        const url = `/admin/marketing/social/analytics?platform=${platform}&sortBy=${currentAnalyticsSort}`;

        try {
            const data = await apiCall(url);
            renderAnalytics(data.posts || data.analytics || []);
        } catch (e) {
            showToast('Failed to load analytics: ' + e.message, 'error');
        }
    };

    function renderAnalytics(posts) {
        const grid = $('#analyticsGrid');
        const empty = $('#analyticsEmpty');

        if (!posts || posts.length === 0) {
            grid.innerHTML = '';
            grid.appendChild(empty);
            empty.style.display = '';
            return;
        }

        if (empty) empty.style.display = 'none';

        const cardsHtml = posts.map(post => {
            const platform = post.platform || 'twitter';
            const metrics = [
                { icon: 'eye', label: 'Views', value: post.views || post.impressions || 0 },
                { icon: 'heart', label: 'Likes', value: post.likes || 0 },
                { icon: 'message-circle', label: 'Comments', value: post.comments || 0 },
                { icon: 'share-2', label: 'Reach', value: post.reach || 0 }
            ];

            return `
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="platform-${platform} inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase" style="background: color-mix(in srgb, var(--pc) 10%, white); color: var(--pc);">
                            ${getPlatformIcon(platform)}
                            ${escapeHtml(ucfirst(platform))}
                        </span>
                        <span class="text-xs text-gray-400 ml-auto">${escapeHtml(post.date || '')}</span>
                    </div>
                    <p class="text-sm text-gray-800 line-clamp-2 mb-4">${escapeHtml(post.content || 'No content')}</p>
                    <div class="grid grid-cols-2 gap-3">
                        ${metrics.map(m => `
                            <div class="flex items-center gap-2">
                                <i data-lucide="${m.icon}" class="w-3.5 h-3.5 text-gray-400"></i>
                                <div>
                                    <p class="text-xs font-semibold text-gray-900">${formatNumber(m.value)}</p>
                                    <p class="text-[10px] text-gray-400">${m.label}</p>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>`;
        }).join('');

        grid.innerHTML = cardsHtml;
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    // ---------- TAB 5: VISUAL TEMPLATES ----------
    window.loadTemplates = async function() {
        try {
            const data = await apiCall('/admin/marketing/social/templates');
            renderTemplates(data.templates || []);
        } catch (e) {
            showToast('Failed to load templates: ' + e.message, 'error');
        }
    };

    function renderTemplates(templates) {
        const grid = $('#templatesGrid');
        const empty = $('#templatesEmpty');

        if (!templates || templates.length === 0) {
            grid.innerHTML = '';
            grid.appendChild(empty);
            empty.style.display = '';
            return;
        }

        if (empty) empty.style.display = 'none';

        grid.innerHTML = templates.map(t => `
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-md transition-shadow group">
                ${t.thumbnail ? `<div class="h-40 bg-gray-100 overflow-hidden"><img src="${escapeHtml(t.thumbnail)}" alt="" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"></div>` : `<div class="h-40 bg-gradient-to-br from-amber-50 to-orange-50 flex items-center justify-center"><i data-lucide="layout-template" class="w-12 h-12 text-amber-300"></i></div>`}
                <div class="p-4">
                    <h4 class="text-sm font-semibold text-gray-900 mb-1">${escapeHtml(t.name || 'Untitled Template')}</h4>
                    <p class="text-xs text-gray-500 line-clamp-2 mb-3">${escapeHtml(t.description || 'No description')}</p>
                    <button type="button" onclick="openGenerateModal('${escapeHtml(t.id || '')}', '${escapeHtml(t.name || '')}')" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-amber-700 bg-amber-50 rounded-lg hover:bg-amber-100 transition-colors">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5"></i> Generate Visual
                    </button>
                </div>
            </div>
        `).join('');

        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    window.openGenerateModal = function(templateId, templateName) {
        currentGenerateTemplateId = templateId;
        $('#generateTemplateName').textContent = templateName;
        $('#generatePrompt').value = '';
        $('#generateVisualForm').classList.remove('hidden');
        $('#generateVisualStatus').classList.add('hidden');
        $('#generateVisualResult').classList.add('hidden');
        $('#generateVisualBtn').disabled = false;
        $('#generateVisualModal').classList.remove('hidden');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    };

    window.closeGenerateModal = function() {
        $('#generateVisualModal').classList.add('hidden');
        currentGenerateTemplateId = null;
    };

    window.generateVisual = async function() {
        const prompt = $('#generatePrompt').value.trim();
        if (!prompt) {
            showToast('Please enter a prompt', 'error');
            return;
        }

        const btn = $('#generateVisualBtn');
        btn.disabled = true;
        $('#generateVisualStatus').classList.remove('hidden');
        $('#generateVisualResult').classList.add('hidden');

        try {
            const result = await apiCall('/admin/marketing/social/create-visual', {
                method: 'POST',
                body: {
                    template_id: currentGenerateTemplateId,
                    prompt: prompt
                }
            });

            if (result.visual_id) {
                const status = await pollStatus(`/admin/marketing/social/visual-status?id=${result.visual_id}`, 60, 3000);
                $('#generateVisualStatus').classList.add('hidden');
                $('#generateVisualResult').classList.remove('hidden');
                if (status.url) {
                    $('#generatedImageUrl').src = status.url;
                    $('#generatedImageUrl').classList.remove('hidden');
                } else {
                    $('#generatedImageUrl').classList.add('hidden');
                }
                showToast('Visual generated successfully!');
            } else {
                throw new Error('No visual ID returned');
            }
        } catch (e) {
            $('#generateVisualStatus').classList.add('hidden');
            showToast('Failed to generate visual: ' + e.message, 'error');
        } finally {
            btn.disabled = false;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    };

    // Close modal on backdrop click
    $('#generateVisualModal').addEventListener('click', function(e) {
        if (e.target === this) closeGenerateModal();
    });

    // ---------- INIT ----------
    updatePreview();

})();
</script>