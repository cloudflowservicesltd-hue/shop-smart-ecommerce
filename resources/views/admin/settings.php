<?php $settings = []; foreach (Database::select("SELECT * FROM settings ORDER BY group_name, `key`") as $s) { $settings[$s['key']] = $s['value']; } ?>
<div class="space-y-6">
    <h1 class="font-heading font-semibold text-xl text-gray-900">Store Settings</h1>

    <form method="POST" action="/admin/settings/update" enctype="multipart/form-data" class="space-y-6">
        <?= csrf() ?>

        <!-- Logo & Favicon -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <h3 class="font-medium mb-4 flex items-center gap-2"><i data-lucide="image" class="w-5 h-5 text-amber-600"></i> Logo & Favicon</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Logo Upload -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Store Logo</label>
                    <?php if (!empty($settings['site_logo'])): ?>
                        <div class="mb-3 p-3 bg-gray-50 rounded-lg border border-gray-200 flex items-center gap-3">
                            <img src="<?= e($settings['site_logo']) ?>" alt="Logo" class="h-12 w-auto object-contain rounded">
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-500 truncate">Current logo</p>
                                <button type="submit" name="remove_logo" value="1" class="text-xs text-red-600 hover:text-red-800 mt-1">Remove</button>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="mb-3 p-3 bg-gray-50 rounded-lg border border-dashed border-gray-300 text-center">
                            <i data-lucide="image-plus" class="w-8 h-8 mx-auto text-gray-300 mb-1"></i>
                            <p class="text-xs text-gray-400">No logo uploaded</p>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="site_logo" accept="image/png,image/jpeg,image/jpg,image/gif,image/webp,image/svg+xml" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
                    <p class="mt-1 text-xs text-gray-400">Recommended: 200x50px, PNG or SVG</p>
                </div>
                <!-- Favicon Upload -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Favicon</label>
                    <?php if (!empty($settings['site_favicon'])): ?>
                        <div class="mb-3 p-3 bg-gray-50 rounded-lg border border-gray-200 flex items-center gap-3">
                            <img src="<?= e($settings['site_favicon']) ?>" alt="Favicon" class="h-10 w-10 object-contain rounded">
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-500 truncate">Current favicon</p>
                                <button type="submit" name="remove_favicon" value="1" class="text-xs text-red-600 hover:text-red-800 mt-1">Remove</button>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="mb-3 p-3 bg-gray-50 rounded-lg border border-dashed border-gray-300 text-center">
                            <i data-lucide="globe" class="w-8 h-8 mx-auto text-gray-300 mb-1"></i>
                            <p class="text-xs text-gray-400">No favicon uploaded</p>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="site_favicon" accept="image/x-icon,image/png,image/svg+xml,image/jpeg" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
                    <p class="mt-1 text-xs text-gray-400">Recommended: 32x32px, .ico, .png or .svg</p>
                </div>
            </div>
        </div>

        <!-- General -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <h3 class="font-medium mb-4 flex items-center gap-2"><i data-lucide="store" class="w-5 h-5 text-amber-600"></i> General Settings</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Store Name</label><input type="text" name="store_name" value="<?= e($settings['store_name'] ?? '') ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Store Tagline</label><input type="text" name="store_tagline" value="<?= e($settings['store_tagline'] ?? '') ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Contact Email</label><input type="email" name="store_email" value="<?= e($settings['store_email'] ?? '') ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label><input type="text" name="store_phone" value="<?= e($settings['store_phone'] ?? '') ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm"></div>
                <div class="sm:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Address</label><input type="text" name="store_address" value="<?= e($settings['store_address'] ?? '') ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Currency</label><select name="currency" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm"><option value="KES" <?= ($settings['currency'] ?? 'KES') === 'KES' ? 'selected' : '' ?>>KES - Kenya Shilling</option></select></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Currency Symbol</label><input type="text" name="currency_symbol" value="<?= e($settings['currency_symbol'] ?? 'KSh') ?>" placeholder="e.g. KSh, $, £, ₦" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Tax Rate (%)</label><input type="number" name="tax_rate" step="0.1" value="<?= e($settings['tax_rate'] ?? '16') ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Free Shipping Threshold</label><input type="number" name="shipping_threshold" step="1" value="<?= e($settings['shipping_threshold'] ?? '5000') ?>" placeholder="e.g. 5000" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm"></div>
                <div class="sm:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Shipping Banner Text <span class="text-xs text-gray-400">(leave empty for auto-generated)</span></label><input type="text" name="shipping_banner_text" value="<?= e($settings['shipping_banner_text'] ?? '') ?>" placeholder="Leave empty for: Free shipping on orders over [threshold]" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Category Circle Size (px)</label><input type="number" name="category_circle_size" value="<?= e($settings['category_circle_size'] ?? '80') ?>" min="48" max="320" step="8" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm" placeholder="e.g. 80, 96, 128, 160"><p class="text-xs text-gray-400 mt-1">Size in pixels (48–320). Default: 80px</p></div>
            </div>
        </div>

        <!-- Login Page Branding -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <h3 class="font-medium mb-4 flex items-center gap-2"><i data-lucide="log-in" class="w-5 h-5 text-amber-600"></i> Login Page Branding</h3>
            <p class="text-sm text-gray-500 mb-4">Customize the branding shown on the login page.</p>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Login Logo</label>
                    <input type="file" name="login_logo" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
                    <p class="text-xs text-gray-400 mt-1">Recommended: 80x80px. Leave empty to keep current.</p>
                    <?php $existingLogo = Database::selectOne("SELECT value FROM settings WHERE `key` = 'login_logo'"); if ($existingLogo && $existingLogo['value']): ?>
                    <div class="mt-2 flex items-center gap-3">
                        <img src="<?= e($existingLogo['value']) ?>" class="w-10 h-10 rounded-lg object-cover border">
                        <button type="submit" name="remove_login_logo" value="1" class="text-xs text-red-600 hover:text-red-800 font-medium">Remove</button>
                    </div>
                    <?php endif; ?>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">App Name</label>
                    <input type="text" name="login_title" value="<?= e(Database::selectOne("SELECT value FROM settings WHERE `key` = 'login_title'")['value'] ?? 'ShopSmart') ?>" class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subtitle</label>
                    <input type="text" name="login_subtitle" value="<?= e(Database::selectOne("SELECT value FROM settings WHERE `key` = 'login_subtitle'")['value'] ?? 'AI-Powered Ecommerce & POS') ?>" class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="login_description" rows="2" class="w-full px-3.5 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 resize-none"><?= e(Database::selectOne("SELECT value FROM settings WHERE `key` = 'login_description'")['value'] ?? 'Complete business solution with intelligent marketing, real-time inventory, and seamless payment processing.') ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Login Sidebar Color</label>
                    <div class="flex items-center gap-2">
                        <input type="color" name="login_bg_color" value="<?= e($settings['login_bg_color'] ?? '#b45309') ?>" class="w-10 h-10 rounded-lg border border-gray-200 cursor-pointer p-0.5">
                        <input type="text" name="login_bg_color_text" value="<?= e($settings['login_bg_color'] ?? '#b45309') ?>" class="flex-1 px-3 py-2.5 border border-gray-200 rounded-lg text-sm font-mono" oninput="this.form.login_bg_color.value=this.value">
                    </div>
                    <p class="mt-1 text-xs text-gray-400">Background color of the login page sidebar and admin sidebar</p>
                </div>
            </div>
        </div>

        <!-- Website Colors -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <h3 class="font-medium mb-4 flex items-center gap-2"><i data-lucide="palette" class="w-5 h-5 text-amber-600"></i> Website Colors</h3>
            <p class="text-sm text-gray-500 mb-4">Customize the color scheme used across all pages of your store.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Primary Color</label>
                    <div class="flex items-center gap-2">
                        <input type="color" name="primary_color" value="<?= e($settings['primary_color'] ?? '#d97706') ?>" class="w-10 h-10 rounded-lg border border-gray-200 cursor-pointer p-0.5">
                        <input type="text" name="primary_color_text" value="<?= e($settings['primary_color'] ?? '#d97706') ?>" class="flex-1 px-3 py-2.5 border border-gray-200 rounded-lg text-sm font-mono" oninput="this.form.primary_color.value=this.value">
                    </div>
                    <p class="mt-1 text-xs text-gray-400">Buttons, links, accents</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Primary Hover</label>
                    <div class="flex items-center gap-2">
                        <input type="color" name="primary_hover_color" value="<?= e($settings['primary_hover_color'] ?? '#b45309') ?>" class="w-10 h-10 rounded-lg border border-gray-200 cursor-pointer p-0.5">
                        <input type="text" name="primary_hover_color_text" value="<?= e($settings['primary_hover_color'] ?? '#b45309') ?>" class="flex-1 px-3 py-2.5 border border-gray-200 rounded-lg text-sm font-mono" oninput="this.form.primary_hover_color.value=this.value">
                    </div>
                    <p class="mt-1 text-xs text-gray-400">Button hover state</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Header Background</label>
                    <div class="flex items-center gap-2">
                        <input type="color" name="header_bg_color" value="<?= e($settings['header_bg_color'] ?? '#ffffff') ?>" class="w-10 h-10 rounded-lg border border-gray-200 cursor-pointer p-0.5">
                        <input type="text" name="header_bg_color_text" value="<?= e($settings['header_bg_color'] ?? '#ffffff') ?>" class="flex-1 px-3 py-2.5 border border-gray-200 rounded-lg text-sm font-mono" oninput="this.form.header_bg_color.value=this.value">
                    </div>
                    <p class="mt-1 text-xs text-gray-400">Navigation bar</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Footer Background</label>
                    <div class="flex items-center gap-2">
                        <input type="color" name="footer_bg_color" value="<?= e($settings['footer_bg_color'] ?? '#111827') ?>" class="w-10 h-10 rounded-lg border border-gray-200 cursor-pointer p-0.5">
                        <input type="text" name="footer_bg_color_text" value="<?= e($settings['footer_bg_color'] ?? '#111827') ?>" class="flex-1 px-3 py-2.5 border border-gray-200 rounded-lg text-sm font-mono" oninput="this.form.footer_bg_color.value=this.value">
                    </div>
                    <p class="mt-1 text-xs text-gray-400">Footer section</p>
                </div>
            </div>
            <script>
            document.querySelectorAll('input[type="color"]').forEach(el => {
                el.addEventListener('input', () => {
                    const textInput = el.closest('div').querySelector('input[type="text"]');
                    if (textInput) textInput.value = el.value;
                });
            });
            </script>
        </div>

        <!-- Google Reviews -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <h3 class="font-medium mb-1 flex items-center gap-2"><i data-lucide="star" class="w-5 h-5 text-amber-600"></i> Google Reviews</h3>
            <p class="text-sm text-gray-500 mb-4">Display a Google Reviews section on the homepage with a direct link to your reviews. No API key needed.</p>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Google Business ID</label>
                    <input type="text" name="google_place_id" value="<?= e($settings['google_place_id'] ?? '') ?>" placeholder="e.g. ChIJN1t_tDeuEmsRUsoyG83frY4" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm">
                    <p class="mt-1 text-xs text-gray-400">Your Google Place ID. Find it at <a href="https://developers.google.com/maps/documentation/places/web-service/place-id" target="_blank" class="text-amber-600 hover:underline">Google Place ID Finder</a> — or search your business name on Google Maps and copy the Place ID from the URL.</p>
                </div>
                <div class="bg-blue-50 border border-blue-100 rounded-lg p-3">
                    <p class="text-xs text-blue-700"><i data-lucide="info" class="w-3.5 h-3.5 inline-block mr-1 -mt-0.5"></i> The homepage will show an embedded Google Map of your business and a <strong>"View Google Reviews"</strong> button linking to <code class="bg-blue-100 px-1 py-0.5 rounded text-[11px]">g.page/r/YOUR_ID/review</code>. Customers can also click <strong>"Leave a Review"</strong> to write one directly.</p>
                </div>
            </div>
        </div>

        <!-- Blotato Social Publishing -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <h3 class="font-medium mb-4 flex items-center gap-2"><i data-lucide="share-2" class="w-5 h-5 text-amber-600"></i> Social Publishing (Blotato)</h3>
            <p class="text-sm text-gray-500 mb-4">Connect your Blotato account to publish to Twitter, Facebook, Instagram, TikTok, LinkedIn, and more from one place.</p>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Blotato API Key</label>
                <div class="flex gap-3">
                    <input type="password" name="blotato_api_key" value="<?= e($settings['blotato_api_key'] ?? '') ?>" placeholder="Enter your Blotato API key" class="flex-1 px-3 py-2.5 border border-gray-200 rounded-lg text-sm font-mono">
                    <button type="button" onclick="const i=this.previousElementSibling;i.type=i.type==='password'?'text':'password'" class="px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-600 hover:bg-gray-50 transition-colors" title="Toggle visibility">
                        <i data-lucide="eye" class="w-4 h-4"></i>
                    </button>
                </div>
                <p class="mt-1 text-xs text-gray-400">Get your API key from <a href="https://blotato.com" target="_blank" class="text-amber-600 hover:underline">blotato.com</a> → Settings → API Keys</p>
            </div>
        </div>

        <!-- Notifications -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <h3 class="font-medium mb-4 flex items-center gap-2"><i data-lucide="bell" class="w-5 h-5 text-amber-600"></i> Notifications</h3>
            <div class="space-y-3">
                <?php foreach (['New Order' => 'notify_new_order', 'Payment Received' => 'notify_payment', 'Low Stock' => 'notify_low_stock', 'New Customer' => 'notify_new_customer'] as $label => $key): ?>
                <label class="flex items-center justify-between p-3 bg-gray-50 rounded-lg cursor-pointer">
                    <span class="text-sm font-medium"><?= $label ?></span>
                    <input type="checkbox" name="<?= $key ?>" value="1" <?= ($settings[$key] ?? '1') ? 'checked' : '' ?> class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500">
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <button type="submit" class="bg-amber-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700">Save Settings</button>
    </form>
</div>