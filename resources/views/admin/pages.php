<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="font-heading font-semibold text-xl text-gray-900">CMS Pages</h1>
        <a href="/admin/pages/create" class="inline-flex items-center gap-2 bg-amber-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors">
            <i data-lucide="plus" class="w-4 h-4"></i> Create Page
        </a>
    </div>

    <!-- Pages Table -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <?php if (empty($pages)): ?>
        <div class="px-4 py-12 text-center text-gray-400">
            <i data-lucide="file-text" class="w-10 h-10 mx-auto mb-2 opacity-50"></i>
            <p class="text-sm">No pages found. Click "Create Page" to add one.</p>
        </div>
        <?php else: ?>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Title</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600 hidden md:table-cell">Slug</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Status</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600 hidden sm:table-cell">Sort Order</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600 hidden lg:table-cell">Updated</th>
                    <th class="text-right px-4 py-3 font-medium text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($pages as $p): ?>
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center shrink-0">
                                <i data-lucide="file-text" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <span class="font-medium text-gray-900"><?= e($p['title']) ?></span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs font-mono hidden md:table-cell"><?= e($p['slug']) ?></td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $p['is_active'] ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600' ?>"><?= $p['is_active'] ? 'Active' : 'Inactive' ?></span>
                    </td>
                    <td class="px-4 py-3 text-gray-600 hidden sm:table-cell"><?= e($p['sort_order'] ?? 0) ?></td>
                    <td class="px-4 py-3 text-gray-500 text-xs hidden lg:table-cell"><?= e($p['updated_at'] ?? '') ?></td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <a href="/admin/pages/<?= $p['id'] ?>/edit" class="p-1.5 text-gray-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" title="Edit">
                                <i data-lucide="pencil" class="w-4 h-4"></i>
                            </a>
                            <form method="POST" action="/admin/pages/<?= $p['id'] ?>/delete" onsubmit="return confirm('Delete this page? This cannot be undone.')">
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
        <?php endif; ?>
    </div>

    <?php if (!empty($pagination ?? null) && ($pagination['last_page'] ?? 1) > 1): ?>
    <div class="mt-4 flex items-center justify-center">
        <nav class="flex items-center gap-1">
            <?php if (($pagination['current_page'] ?? 1) > 1): ?>
            <a href="?page=<?= $pagination['current_page'] - 1 ?>" class="px-3 py-1.5 rounded-lg text-sm text-gray-600 hover:bg-gray-100 transition-colors">&laquo; Prev</a>
            <?php endif; ?>
            <?php for ($p = 1; $p <= $pagination['last_page']; $p++): ?>
            <a href="?page=<?= $p ?>" class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors <?= $p == $pagination['current_page'] ? 'bg-amber-600 text-white' : 'text-gray-600 hover:bg-gray-100' ?>"><?= $p ?></a>
            <?php endfor; ?>
            <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
            <a href="?page=<?= $pagination['current_page'] + 1 ?>" class="px-3 py-1.5 rounded-lg text-sm text-gray-600 hover:bg-gray-100 transition-colors">Next &raquo;</a>
            <?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>
</div>