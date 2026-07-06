<?php
$categories = Database::select("SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count, p.name as parent_name FROM categories c LEFT JOIN categories p ON c.parent_id = p.id ORDER BY c.sort_order, c.name");
$allCategories = Database::select("SELECT * FROM categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY name");
?>
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="font-heading font-semibold text-xl text-gray-900">Categories</h1>
        <button onclick="document.getElementById('addForm').classList.toggle('hidden'); document.getElementById('editForm').classList.add('hidden');" class="inline-flex items-center gap-2 bg-amber-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700">
            <i data-lucide="plus" class="w-4 h-4"></i> Add Category
        </button>
    </div>

    <!-- Add Form -->
    <div id="addForm" class="hidden bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-medium text-gray-900 mb-4">New Category</h3>
        <form method="POST" action="/admin/categories/store" enctype="multipart/form-data" class="space-y-4">
            <?= csrf() ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <input type="text" name="name" placeholder="Category Name" required class="px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                <input type="text" name="slug" placeholder="slug-name" required class="px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                <select name="parent_id" class="px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <option value="">Parent (None)</option>
                    <?php foreach ($allCategories as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="sort_order" placeholder="Sort Order" value="0" min="0" class="px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <textarea name="description" rows="2" placeholder="Description (optional)" class="sm:col-span-2 px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 resize-none"></textarea>
                <div class="flex items-end">
                    <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" name="is_active" value="1" checked class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500">
                        <span class="text-sm text-gray-700">Active</span>
                    </label>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1.5">Image</label>
                <input type="file" name="image" accept="image/*" class="w-full max-w-sm text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('addForm').classList.add('hidden')" class="px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 border border-gray-200 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="bg-amber-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700">Save Category</button>
            </div>
        </form>
    </div>

    <!-- Edit Form -->
    <div id="editForm" class="hidden bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-medium text-gray-900">Edit Category</h3>
            <button onclick="document.getElementById('editForm').classList.add('hidden')" class="p-1 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form id="editCategoryForm" method="POST" enctype="multipart/form-data" class="space-y-4">
            <?= csrf() ?>
            <input type="hidden" name="_method" value="PUT">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Name</label>
                    <input type="text" name="name" id="editName" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Slug</label>
                    <input type="text" name="slug" id="editSlug" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Parent</label>
                    <select name="parent_id" id="editParentId" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                        <option value="">Parent (None)</option>
                        <?php foreach ($allCategories as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" id="editSortOrder" value="0" min="0" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Description</label>
                    <textarea name="description" id="editDescription" rows="2" placeholder="Description (optional)" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 resize-none"></textarea>
                </div>
                <div class="flex items-end">
                    <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" name="is_active" id="editIsActive" value="1" class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500">
                        <span class="text-sm text-gray-700">Active</span>
                    </label>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1.5">Image</label>
                <div class="flex items-center gap-4">
                    <div id="editImagePreview" class="hidden w-16 h-16 rounded-xl bg-gray-100 overflow-hidden shrink-0">
                        <img id="editImageImg" src="" alt="" class="w-full h-full object-cover">
                    </div>
                    <div class="flex-1">
                        <input type="file" name="image" accept="image/*" class="w-full max-w-sm text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
                        <p class="text-xs text-gray-400 mt-1">Leave empty to keep current image</p>
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('editForm').classList.add('hidden')" class="px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 border border-gray-200 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="bg-amber-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700">Update Category</button>
            </div>
        </form>
    </div>

    <!-- Categories List -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Name</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Slug</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600 hidden md:table-cell">Parent</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Products</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Status</th>
                    <th class="text-right px-4 py-3 font-medium text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($categories as $c): ?>
                <tr class="hover:bg-gray-50/50">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <?php if ($c['image']): ?>
                            <img src="/<?= e($c['image']) ?>" alt="<?= e($c['name']) ?>" class="w-8 h-8 rounded-lg object-cover bg-gray-100 shrink-0">
                            <?php else: ?>
                            <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center shrink-0">
                                <i data-lucide="folder" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <?php endif; ?>
                            <span class="font-medium text-gray-900"><?= e($c['name']) ?></span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs font-mono"><?= e($c['slug']) ?></td>
                    <td class="px-4 py-3 text-gray-600 hidden md:table-cell"><?= e($c['parent_name'] ?? '-') ?></td>
                    <td class="px-4 py-3"><?= $c['product_count'] ?></td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $c['is_active'] ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600' ?>"><?= $c['is_active'] ? 'Active' : 'Inactive' ?></span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <button onclick="openEditForm(<?= $c['id'] ?>, '<?= e(addslashes($c['name'])) ?>', '<?= e(addslashes($c['slug'])) ?>', '<?= e(addslashes($c['description'] ?? '')) ?>', <?= (int)($c['parent_id'] ?? 0) ?>, <?= (int)$c['is_active'] ?>, <?= (int)($c['sort_order'] ?? 0) ?>, '<?= e($c['image'] ?? '') ?>')" class="p-1.5 text-gray-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg" title="Edit">
                                <i data-lucide="pencil" class="w-4 h-4"></i>
                            </button>
                            <form method="POST" action="/admin/categories/<?= $c['id'] ?>/delete" onsubmit="return confirm('Delete this category? This cannot be undone.')">
                                <?= csrf() ?><button type="submit" class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg" title="Delete">
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
</div>

<script>
function openEditForm(id, name, slug, description, parentId, isActive, sortOrder, image) {
    var form = document.getElementById('editCategoryForm');
    document.getElementById('editName').value = name;
    document.getElementById('editSlug').value = slug;
    document.getElementById('editDescription').value = description;
    document.getElementById('editParentId').value = parentId || '';
    document.getElementById('editIsActive').checked = isActive === 1;
    document.getElementById('editSortOrder').value = sortOrder;
    form.action = '/admin/categories/' + id + '/update';
    var preview = document.getElementById('editImagePreview');
    var previewImg = document.getElementById('editImageImg');
    if (image) {
        previewImg.src = '/' + image;
        preview.classList.remove('hidden');
    } else {
        preview.classList.add('hidden');
    }
    document.getElementById('addForm').classList.add('hidden');
    document.getElementById('editForm').classList.remove('hidden');
    document.getElementById('editForm').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
</script>