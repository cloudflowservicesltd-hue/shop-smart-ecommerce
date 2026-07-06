<div class="space-y-6">
    <h1 class="font-heading font-semibold text-xl text-gray-900">Social Media Links</h1>

    <form method="POST" action="/admin/social-media/update" class="space-y-6">
        <?= csrf() ?>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <h3 class="font-medium text-gray-900 mb-4 flex items-center gap-2">
                <i data-lucide="share-2" class="w-5 h-5 text-amber-600"></i>
                Social Profiles
            </h3>
            <div class="space-y-4">
                <!-- Facebook -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Facebook</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="facebook" class="w-4 h-4 text-gray-400"></i>
                        </div>
                        <input type="text" name="social_facebook" value="<?= e($social['social_facebook'] ?? '') ?>" placeholder="https://facebook.com/yourpage" class="w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                    </div>
                </div>

                <!-- Twitter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Twitter</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="twitter" class="w-4 h-4 text-gray-400"></i>
                        </div>
                        <input type="text" name="social_twitter" value="<?= e($social['social_twitter'] ?? '') ?>" placeholder="https://twitter.com/yourhandle" class="w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                    </div>
                </div>

                <!-- Instagram -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Instagram</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="instagram" class="w-4 h-4 text-gray-400"></i>
                        </div>
                        <input type="text" name="social_instagram" value="<?= e($social['social_instagram'] ?? '') ?>" placeholder="https://instagram.com/yourprofile" class="w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                    </div>
                </div>

                <!-- YouTube -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">YouTube</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="youtube" class="w-4 h-4 text-gray-400"></i>
                        </div>
                        <input type="text" name="social_youtube" value="<?= e($social['social_youtube'] ?? '') ?>" placeholder="https://youtube.com/@yourchannel" class="w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                    </div>
                </div>

                <!-- TikTok -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">TikTok</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="music-2" class="w-4 h-4 text-gray-400"></i>
                        </div>
                        <input type="text" name="social_tiktok" value="<?= e($social['social_tiktok'] ?? '') ?>" placeholder="https://tiktok.com/@yourhandle" class="w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="bg-amber-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors">
            Save Changes
        </button>
    </form>
</div>