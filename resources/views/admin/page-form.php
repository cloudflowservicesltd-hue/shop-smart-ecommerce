<?php
$page = $page ?? null;
$isEdit = $page !== null;
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="font-heading font-semibold text-xl text-gray-900"><?= $isEdit ? 'Edit Page' : 'Create Page' ?></h1>
        <a href="/admin/pages" class="text-sm text-gray-600 hover:text-gray-900 transition-colors">Cancel</a>
    </div>

    <form method="POST" action="<?= $isEdit ? '/admin/pages/'.$page['id'].'/update' : '/admin/pages/store' ?>" class="space-y-6">
        <?= csrf() ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <!-- Page Content -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="font-medium text-gray-900 mb-4">Page Content</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                            <input type="text" name="title" value="<?= e($page['title'] ?? '') ?>" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Slug *</label>
                            <input type="text" name="slug" value="<?= e($page['slug'] ?? '') ?>" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-amber-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Content</label>
                            <textarea name="content" rows="10" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 resize-y" data-rich-text><?= e($page['content'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- SEO -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="font-medium text-gray-900 mb-4 flex items-center gap-2"><i data-lucide="search" class="w-5 h-5 text-amber-600"></i> SEO Settings</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Meta Title</label>
                            <input type="text" name="meta_title" value="<?= e($page['meta_title'] ?? '') ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Meta Description</label>
                            <textarea name="meta_description" rows="2" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 resize-none"><?= e($page['meta_description'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar -->
            <div class="space-y-6">
                <!-- Publishing -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="font-medium text-gray-900 mb-4">Publishing</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                            <input type="number" name="sort_order" value="<?= e($page['sort_order'] ?? 0) ?>" min="0" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                        </div>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" <?= ($page['is_active'] ?? 1) ? 'checked' : '' ?> class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500">
                            <span class="text-sm text-gray-700">Active</span>
                        </label>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-amber-600 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors">
                        <?= $isEdit ? 'Update Page' : 'Create Page' ?>
                    </button>
                    <a href="/admin/pages" class="px-4 py-2.5 border border-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</div>