<?php
$breadcrumbs = [['Settings', '/admin/settings'], ['SEO', '']];
$success = Session::get('success'); Session::remove('success');
?>

<div class="space-y-6">
    <!-- Breadcrumbs -->
    <div class="flex items-center gap-2 text-sm text-gray-500">
        <a href="/admin/settings" class="hover:text-amber-600">Settings</a>
        <i data-lucide="chevron-right" class="w-3 h-3"></i>
        <span class="text-gray-900 font-medium">SEO & Meta Tags</span>
    </div>

    <?php if ($success): ?>
    <div class="bg-green-50 border border-green-200 rounded-xl p-4 flex items-center gap-2 text-green-700">
        <i data-lucide="check-circle" class="w-5 h-5"></i> <?= e($success) ?>
    </div>
    <?php endif; ?>

    <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
        <i data-lucide="search" class="w-6 h-6 text-amber-600"></i> SEO & Meta Tags
    </h1>

    <form method="POST" action="/admin/seo/update" class="space-y-6">
        <!-- General Meta -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <i data-lucide="file-text" class="w-5 h-5 text-amber-600"></i> General Meta Tags
            </h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Site Title <span class="text-xs text-gray-400">(appears in browser tab & search results)</span></label>
                    <input type="text" name="seo_title" value="<?= e($settings['seo_title'] ?? '') ?>" placeholder="e.g. ShopSmart - Best Online Store in Kenya"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Meta Description <span class="text-xs text-gray-400">(150-160 characters)</span></label>
                    <textarea name="seo_description" rows="3" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 resize-y" placeholder="A brief description of your store for search engines..."><?= e($settings['seo_description'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Meta Keywords <span class="text-xs text-gray-400">(comma-separated)</span></label>
                    <input type="text" name="seo_keywords" value="<?= e($settings['seo_keywords'] ?? '') ?>" placeholder="e.g. online shop, electronics, Kenya, buy online"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Canonical URL</label>
                    <input type="text" name="canonical_url" value="<?= e($settings['canonical_url'] ?? '') ?>" placeholder="https://yourdomain.com"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
            </div>
        </div>

        <!-- Open Graph -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <i data-lucide="share-2" class="w-5 h-5 text-amber-600"></i> Open Graph (Facebook, LinkedIn, etc.)
            </h3>
            <p class="text-sm text-gray-500 mb-4">Controls how your store appears when shared on social media</p>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">OG Title</label>
                    <input type="text" name="og_title" value="<?= e($settings['og_title'] ?? '') ?>" placeholder="Override the title for social sharing (leave empty to use site title)"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">OG Description</label>
                    <textarea name="og_description" rows="2" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 resize-y" placeholder="Social sharing description"><?= e($settings['og_description'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">OG Image URL</label>
                    <input type="text" name="og_image" value="<?= e($settings['og_image'] ?? '') ?>" placeholder="https://yourdomain.com/og-image.jpg (1200x630 recommended)"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <?php if (!empty($settings['og_image'])): ?>
                    <img src="<?= e($settings['og_image']) ?>" class="mt-2 h-16 rounded-lg object-cover border">
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Twitter Card -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-600" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                Twitter Card
            </h3>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Card Type</label>
                <select name="twitter_card" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <option value="summary_large_image" <?= ($settings['twitter_card'] ?? '') === 'summary_large_image' ? 'selected' : '' ?>>Summary Large Image</option>
                    <option value="summary" <?= ($settings['twitter_card'] ?? '') === 'summary' ? 'selected' : '' ?>>Summary</option>
                    <option value="app" <?= ($settings['twitter_card'] ?? '') === 'app' ? 'selected' : '' ?>>App</option>
                    <option value="player" <?= ($settings['twitter_card'] ?? '') === 'player' ? 'selected' : '' ?>>Player</option>
                </select>
            </div>
        </div>

        <!-- Tracking -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <i data-lucide="bar-chart-3" class="w-5 h-5 text-amber-600"></i> Tracking & Analytics
            </h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Google Analytics ID</label>
                    <input type="text" name="seo_google_analytics" value="<?= e($settings['seo_google_analytics'] ?? '') ?>" placeholder="e.g. G-XXXXXXXXXX"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Google Tag Manager ID</label>
                    <input type="text" name="seo_google_tag" value="<?= e($settings['seo_google_tag'] ?? '') ?>" placeholder="e.g. GTM-XXXXXXX"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Facebook Pixel ID</label>
                    <input type="text" name="seo_facebook_pixel" value="<?= e($settings['seo_facebook_pixel'] ?? '') ?>" placeholder="e.g. 123456789012345"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
            </div>
        </div>

        <!-- Robots.txt -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <i data-lucide="shield" class="w-5 h-5 text-amber-600"></i> Robots.txt
            </h3>
            <textarea name="robots_txt" rows="8" class="w-full px-4 py-3 border border-gray-200 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-amber-500 resize-y" placeholder="User-agent: *&#10;Allow: /&#10;Disallow: /admin/&#10;Disallow: /account/"><?= e($settings['robots_txt'] ?? "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /account/\nDisallow: /api/\n\nSitemap: /sitemap.xml") ?></textarea>
        </div>

        <!-- Save -->
        <div class="flex items-center justify-between">
            <a href="/admin/sitemap" class="inline-flex items-center gap-2 text-amber-600 hover:text-amber-700 font-medium px-6 py-3 transition-colors">
                <i data-lucide="file-code" class="w-4 h-4"></i> Edit Sitemap
            </a>
            <button type="submit" class="inline-flex items-center gap-2 bg-amber-600 text-white font-semibold px-8 py-3 rounded-xl hover:bg-amber-700 transition-colors">
                <i data-lucide="save" class="w-4 h-4"></i> Save SEO Settings
            </button>
        </div>
    </form>
</div>