<?php
$post = $post ?? null;
$isEdit = $post !== null;
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="font-heading font-semibold text-xl text-gray-900"><?= $isEdit ? 'Edit Blog Post' : 'Create Blog Post' ?></h1>
        <a href="/admin/blogs" class="text-sm text-gray-600 hover:text-gray-900 transition-colors">Cancel</a>
    </div>

    <form method="POST" action="<?= $isEdit ? '/admin/blogs/'.$post['id'].'/update' : '/admin/blogs/store' ?>" enctype="multipart/form-data" class="space-y-6">
        <?= csrf() ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <!-- Post Content -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="font-medium text-gray-900 mb-4">Post Content</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                            <input type="text" name="title" id="blogTitle" value="<?= e($post['title'] ?? '') ?>" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500" oninput="autoSlug()">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Slug *</label>
                            <input type="text" name="slug" id="blogSlug" value="<?= e($post['slug'] ?? '') ?>" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-amber-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Excerpt</label>
                            <textarea name="excerpt" rows="3" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 resize-y" placeholder="A short summary of the post..."><?= e($post['excerpt'] ?? '') ?></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Content *</label>
                            <textarea name="content" rows="16" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 resize-y" data-rich-text><?= e($post['content'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- SEO -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="font-medium text-gray-900 mb-4 flex items-center gap-2"><i data-lucide="search" class="w-5 h-5 text-amber-600"></i> SEO Settings</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Meta Title</label>
                            <input type="text" name="meta_title" value="<?= e($post['meta_title'] ?? '') ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500" placeholder="Leave blank to use post title">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Meta Description</label>
                            <textarea name="meta_description" rows="2" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 resize-none" placeholder="Brief description for search engines..."><?= e($post['meta_description'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar -->
            <div class="space-y-6">
                <!-- Featured Image -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="font-medium text-gray-900 mb-4 flex items-center gap-2"><i data-lucide="image" class="w-5 h-5 text-amber-600"></i> Featured Image</h3>
                    <div class="space-y-3">
                        <?php if (!empty($post['featured_image'])): ?>
                        <div class="relative">
                            <img src="<?= e($post['featured_image']) ?>" alt="Featured" class="w-full h-40 object-cover rounded-lg border border-gray-200">
                            <input type="hidden" name="current_featured_image" value="<?= e($post['featured_image']) ?>">
                        </div>
                        <?php endif; ?>
                        <input type="file" name="featured_image" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
                        <p class="text-xs text-gray-400">Recommended: 1200x630px. Leave empty to keep current image.</p>
                    </div>
                </div>

                <!-- Publishing -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                    <h3 class="font-medium text-gray-900 mb-4 flex items-center gap-2"><i data-lucide="send" class="w-5 h-5 text-amber-600"></i> Publishing</h3>
                    <div class="space-y-4">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="is_published" value="1" <?= ($post['is_published'] ?? 0) ? 'checked' : '' ?> class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500">
                            <span class="text-sm text-gray-700">Published</span>
                        </label>
                        <?php if ($isEdit): ?>
                        <div class="text-xs text-gray-500 space-y-1">
                            <p>Created: <?= e($post['created_at']) ?></p>
                            <p>Updated: <?= e($post['updated_at']) ?></p>
                            <p>Views: <?= (int)($post['views'] ?? 0) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-amber-600 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors">
                        <?= $isEdit ? 'Update Post' : 'Create Post' ?>
                    </button>
                    <a href="/admin/blogs" class="px-4 py-2.5 border border-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function autoSlug() {
    const title = document.getElementById('blogTitle').value;
    const slug = title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
    document.getElementById('blogSlug').value = slug;
}
</script>