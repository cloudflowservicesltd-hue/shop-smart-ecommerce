<div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <h1 class="font-heading font-semibold text-xl text-gray-900">Blog Posts</h1>
        <a href="/admin/blogs/create" class="inline-flex items-center gap-2 bg-amber-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors">
            <i data-lucide="plus" class="w-4 h-4"></i> New Blog Post
        </a>
    </div>

    <!-- Search -->
    <form method="GET" action="/admin/blogs" class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
        <div class="flex items-center gap-3">
            <div class="relative flex-1">
                <i data-lucide="search" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search posts by title or slug..." class="w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
            </div>
            <?php if ($search): ?>
            <a href="/admin/blogs" class="px-3 py-2.5 text-sm text-gray-600 hover:text-gray-900 transition-colors">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Posts Table -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <?php if (empty($posts['data'])): ?>
        <div class="px-4 py-12 text-center text-gray-400">
            <i data-lucide="pen-line" class="w-10 h-10 mx-auto mb-2 opacity-50"></i>
            <p class="text-sm"><?= $search ? 'No posts match your search.' : 'No blog posts yet. Click "New Blog Post" to create one.' ?></p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Title</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600 hidden md:table-cell">Slug</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Status</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600 hidden sm:table-cell">Author</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600 hidden lg:table-cell">Views</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600 hidden lg:table-cell">Created</th>
                    <th class="text-right px-4 py-3 font-medium text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($posts['data'] as $p): ?>
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <?php if ($p['featured_image']): ?>
                            <img src="<?= e($p['featured_image']) ?>" alt="" class="w-10 h-10 rounded-lg object-cover shrink-0 border border-gray-100">
                            <?php else: ?>
                            <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center shrink-0">
                                <i data-lucide="file-text" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <?php endif; ?>
                            <div class="min-w-0">
                                <span class="font-medium text-gray-900 block truncate max-w-[200px]"><?= e($p['title']) ?></span>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs font-mono hidden md:table-cell"><?= e($p['slug']) ?></td>
                    <td class="px-4 py-3">
                        <?php if ($p['is_published']): ?>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Published</span>
                        <?php else: ?>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Draft</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-gray-600 hidden sm:table-cell"><?= e($p['author_name'] ?? '—') ?></td>
                    <td class="px-4 py-3 text-gray-600 hidden lg:table-cell">
                        <span class="flex items-center gap-1"><i data-lucide="eye" class="w-3.5 h-3.5"></i> <?= (int)$p['views'] ?></span>
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs hidden lg:table-cell"><?= e($p['created_at']) ?></td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <a href="/admin/blogs/<?= $p['id'] ?>/edit" class="p-1.5 text-gray-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" title="Edit">
                                <i data-lucide="pencil" class="w-4 h-4"></i>
                            </a>
                            <?php if ($p['is_published']): ?>
                            <a href="/blog/<?= e($p['slug']) ?>" target="_blank" class="p-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="View">
                                <i data-lucide="external-link" class="w-4 h-4"></i>
                            </a>
                            <?php endif; ?>
                            <form method="POST" action="/admin/blogs/<?= $p['id'] ?>/delete" onsubmit="return confirm('Delete this blog post? This cannot be undone.')">
                                <?= csrf() ?>
                                <button type="submit" class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($posts['last_page']) && $posts['last_page'] > 1): ?>
    <div class="mt-4 flex items-center justify-center">
        <nav class="flex items-center gap-1">
            <?php if ($posts['current_page'] > 1): ?>
            <a href="?page=<?= $posts['current_page'] - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="px-3 py-1.5 rounded-lg text-sm text-gray-600 hover:bg-gray-100 transition-colors">&laquo; Prev</a>
            <?php endif; ?>
            <?php for ($pg = 1; $pg <= $posts['last_page']; $pg++): ?>
            <a href="?page=<?= $pg ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors <?= $pg == $posts['current_page'] ? 'bg-amber-600 text-white' : 'text-gray-600 hover:bg-gray-100' ?>"><?= $pg ?></a>
            <?php endfor; ?>
            <?php if ($posts['current_page'] < $posts['last_page']): ?>
            <a href="?page=<?= $posts['current_page'] + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="px-3 py-1.5 rounded-lg text-sm text-gray-600 hover:bg-gray-100 transition-colors">Next &raquo;</a>
            <?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>
</div>