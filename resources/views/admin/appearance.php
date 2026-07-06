<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Appearance Settings</h1>
            <p class="mt-1 text-sm text-gray-500">Manage your store's homepage appearance and layout sections.</p>
        </div>
    </div>

    <form method="POST" action="/admin/appearance/update">
        <?= csrf() ?>

        <!-- Hero Slider Settings -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 bg-amber-50/50">
                <div class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center">
                    <i data-lucide="megaphone" class="w-5 h-5 text-amber-600"></i>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Hero Slider Settings</h2>
                    <p class="text-sm text-gray-500">Configure the homepage hero banner slider behavior.</p>
                </div>
            </div>
            <div class="p-6 space-y-5">
                <!-- Autoplay -->
                <div class="flex items-center justify-between">
                    <div>
                        <label for="hero_autoplay" class="text-sm font-medium text-gray-700">Autoplay</label>
                        <p class="text-sm text-gray-500">Automatically advance slides without user interaction.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="hero_autoplay" name="hero_autoplay" value="1"
                            <?php if (e($settings['hero_autoplay'] ?? '0') === '1'): ?> checked <?php endif; ?>
                            class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-600"></div>
                    </label>
                </div>

                <!-- Slide Interval -->
                <div>
                    <label for="hero_interval" class="block text-sm font-medium text-gray-700 mb-1">Slide Interval</label>
                    <p class="text-sm text-gray-500 mb-2">Time in milliseconds between slide transitions.</p>
                    <div class="flex items-center gap-3 max-w-xs">
                        <input type="number" id="hero_interval" name="hero_interval"
                            value="<?= e($settings['hero_interval'] ?? '5000') ?>"
                            min="1000" max="20000" step="500"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors">
                        <span class="text-sm text-gray-500 whitespace-nowrap">ms</span>
                    </div>
                    <p class="mt-1.5 text-xs text-gray-400">Recommended: 3000–7000 ms. Lower values cycle faster.</p>
                </div>

                <!-- Animation Style -->
                <div>
                    <label for="hero_animation" class="block text-sm font-medium text-gray-700 mb-1">Animation Style</label>
                    <p class="text-sm text-gray-500 mb-2">Transition effect used between slides.</p>
                    <select id="hero_animation" name="hero_animation"
                        class="w-full max-w-xs rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors">
                        <option value="slide" <?= e($settings['hero_animation'] ?? '') === 'slide' ? 'selected' : '' ?>>Slide</option>
                        <option value="fade" <?= e($settings['hero_animation'] ?? '') === 'fade' ? 'selected' : '' ?>>Fade</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Homepage Sections -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 bg-amber-50/50">
                <div class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center">
                    <i data-lucide="layout-grid" class="w-5 h-5 text-amber-600"></i>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Homepage Sections</h2>
                    <p class="text-sm text-gray-500">Toggle visibility of homepage content sections.</p>
                </div>
            </div>
            <div class="divide-y divide-gray-100">
                <!-- Show Categories Section -->
                <div class="flex items-center justify-between px-6 py-4">
                    <div class="flex items-center gap-3">
                        <i data-lucide="grid-3x3" class="w-4 h-4 text-gray-400"></i>
                        <div>
                            <span class="text-sm font-medium text-gray-700">Categories Section</span>
                            <p class="text-xs text-gray-400">Display product category grid on the homepage.</p>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="show_categories" value="1"
                            <?php if (e($settings['show_categories'] ?? '1') === '1'): ?> checked <?php endif; ?>
                            class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-600"></div>
                    </label>
                </div>

                <!-- Show Featured Products -->
                <div class="flex items-center justify-between px-6 py-4">
                    <div class="flex items-center gap-3">
                        <i data-lucide="star" class="w-4 h-4 text-gray-400"></i>
                        <div>
                            <span class="text-sm font-medium text-gray-700">Featured Products</span>
                            <p class="text-xs text-gray-400">Showcase handpicked featured products section.</p>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="show_featured" value="1"
                            <?php if (e($settings['show_featured'] ?? '1') === '1'): ?> checked <?php endif; ?>
                            class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-600"></div>
                    </label>
                </div>

                <!-- Show New Arrivals -->
                <div class="flex items-center justify-between px-6 py-4">
                    <div class="flex items-center gap-3">
                        <i data-lucide="sparkles" class="w-4 h-4 text-gray-400"></i>
                        <div>
                            <span class="text-sm font-medium text-gray-700">New Arrivals</span>
                            <p class="text-xs text-gray-400">Display recently added products section.</p>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="show_new_arrivals" value="1"
                            <?php if (e($settings['show_new_arrivals'] ?? '1') === '1'): ?> checked <?php endif; ?>
                            class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-600"></div>
                    </label>
                </div>

                <!-- Show Promo Banners -->
                <div class="flex items-center justify-between px-6 py-4">
                    <div class="flex items-center gap-3">
                        <i data-lucide="flag" class="w-4 h-4 text-gray-400"></i>
                        <div>
                            <span class="text-sm font-medium text-gray-700">Promo Banners</span>
                            <p class="text-xs text-gray-400">Show promotional banner images on the homepage.</p>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="show_promo_banners" value="1"
                            <?php if (e($settings['show_promo_banners'] ?? '1') === '1'): ?> checked <?php endif; ?>
                            class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-600"></div>
                    </label>
                </div>

                <!-- Show Trust Badges -->
                <div class="flex items-center justify-between px-6 py-4">
                    <div class="flex items-center gap-3">
                        <i data-lucide="shield-check" class="w-4 h-4 text-gray-400"></i>
                        <div>
                            <span class="text-sm font-medium text-gray-700">Trust Badges</span>
                            <p class="text-xs text-gray-400">Display trust badges (e.g. secure payment, fast shipping).</p>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="show_trust_badges" value="1"
                            <?php if (e($settings['show_trust_badges'] ?? '1') === '1'): ?> checked <?php endif; ?>
                            class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-600"></div>
                    </label>
                </div>

                <!-- Show Newsletter -->
                <div class="flex items-center justify-between px-6 py-4">
                    <div class="flex items-center gap-3">
                        <i data-lucide="mail" class="w-4 h-4 text-gray-400"></i>
                        <div>
                            <span class="text-sm font-medium text-gray-700">Newsletter Section</span>
                            <p class="text-xs text-gray-400">Display the email newsletter signup block.</p>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="show_newsletter" value="1"
                            <?php if (e($settings['show_newsletter'] ?? '1') === '1'): ?> checked <?php endif; ?>
                            class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-600"></div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Newsletter Section Content -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 bg-amber-50/50">
                <div class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center">
                    <i data-lucide="mail" class="w-5 h-5 text-amber-600"></i>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Newsletter Section</h2>
                    <p class="text-sm text-gray-500">Customize the heading and subheading for the newsletter signup area.</p>
                </div>
            </div>
            <div class="p-6 space-y-5">
                <!-- Newsletter Heading -->
                <div>
                    <label for="newsletter_heading" class="block text-sm font-medium text-gray-700 mb-1">Heading</label>
                    <p class="text-sm text-gray-500 mb-2">The main headline displayed in the newsletter section.</p>
                    <input type="text" id="newsletter_heading" name="newsletter_heading"
                        value="<?= e($settings['newsletter_heading'] ?? 'Stay in the Loop') ?>"
                        placeholder="e.g. Subscribe to Our Newsletter"
                        class="w-full max-w-lg rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors placeholder:text-gray-400">
                </div>

                <!-- Newsletter Subheading -->
                <div>
                    <label for="newsletter_subheading" class="block text-sm font-medium text-gray-700 mb-1">Subheading</label>
                    <p class="text-sm text-gray-500 mb-2">A brief description shown below the newsletter heading.</p>
                    <textarea id="newsletter_subheading" name="newsletter_subheading" rows="3"
                        placeholder="e.g. Get the latest deals and updates delivered to your inbox."
                        class="w-full max-w-lg rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors placeholder:text-gray-400 resize-y"><?= e($settings['newsletter_subheading'] ?? 'Get the latest deals and updates delivered straight to your inbox.') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-end pb-8">
            <button type="submit"
                class="inline-flex items-center gap-2 bg-amber-600 hover:bg-amber-700 text-white font-medium px-6 py-2.5 rounded-lg text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">
                <i data-lucide="save" class="w-4 h-4"></i>
                Save Appearance Settings
            </button>
        </div>
    </form>
</div>