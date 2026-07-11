<?php
$brands = Database::select("SELECT b.*, (SELECT COUNT(*) FROM products WHERE brand_id = b.id) as product_count FROM brands b ORDER BY b.name");
?>
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="font-heading font-semibold text-xl text-gray-900">Brands</h1>
        <button onclick="document.getElementById('addForm').classList.toggle('hidden')" class="inline-flex items-center gap-2 bg-amber-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors">
            <i data-lucide="plus" class="w-4 h-4"></i> Add Brand
        </button>
    </div>

    <!-- Add Form -->
    <div id="addForm" class="hidden bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-medium text-gray-900 mb-4">New Brand</h3>
        <form method="POST" action="/admin/brands/store" enctype="multipart/form-data" class="space-y-4">
            <?= csrf() ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <input type="text" name="name" placeholder="Brand Name" required class="px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                <input type="text" name="slug" placeholder="brand-slug" required class="px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                <div class="flex items-center gap-2 px-3 py-2.5 border border-gray-200 rounded-lg">
                    <input type="checkbox" name="is_active" value="1" checked id="addIsActive" class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500">
                    <label for="addIsActive" class="text-sm text-gray-700">Active</label>
                </div>
                <button type="submit" class="bg-amber-600 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors">Save</button>
            </div>
            <textarea name="description" rows="2" placeholder="Description (optional)" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 resize-none"></textarea>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1.5">Logo</label>
                <input type="file" name="logo" accept="image/*" class="w-full max-w-sm text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
            </div>
        </form>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" onclick="closeEditModal()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl border border-gray-100 w-full max-w-lg p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="font-heading font-semibold text-lg text-gray-900">Edit Brand</h3>
                <button onclick="closeEditModal()" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form method="POST" id="editForm" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="id" id="editId">
                <?= csrf() ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input type="text" name="name" id="editName" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
                    <input type="text" name="slug" id="editSlug" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" id="editDescription" rows="3" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 resize-none"></textarea>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" id="editIsActive" class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500">
                    <label for="editIsActive" class="text-sm text-gray-700">Active</label>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Logo</label>
                    <div class="flex items-center gap-4">
                        <div id="editLogoPreview" class="hidden w-12 h-12 rounded-xl bg-gray-100 overflow-hidden shrink-0">
                            <img id="editLogoImg" src="" alt="" class="w-full h-full object-cover">
                        </div>
                        <div class="flex-1">
                            <input type="file" name="logo" accept="image/*" class="w-full max-w-sm text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
                            <p class="text-xs text-gray-400 mt-1">Leave empty to keep current logo</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">Cancel</button>
                    <button type="submit" class="px-4 py-2.5 text-sm font-medium text-white bg-amber-600 hover:bg-amber-700 rounded-lg transition-colors">Update Brand</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" onclick="closeDeleteModal()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl border border-gray-100 w-full max-w-sm p-6 space-y-4 text-center">
            <div class="mx-auto w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                <i data-lucide="alert-triangle" class="w-6 h-6 text-red-600"></i>
            </div>
            <h3 class="font-heading font-semibold text-lg text-gray-900">Delete Brand</h3>
            <p class="text-sm text-gray-500">Are you sure you want to delete <strong id="deleteBrandName" class="text-gray-900"></strong>? This action cannot be undone.</p>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="id" id="deleteId">
                <?= csrf() ?>
                <div class="flex items-center justify-center gap-3 pt-2">
                    <button type="button" onclick="closeDeleteModal()" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">Cancel</button>
                    <button type="submit" class="px-4 py-2.5 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">Delete</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Actions Bar -->
    <div id="bulkBar" class="hidden bg-red-50 border border-red-200 rounded-xl px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span id="bulkCount" class="text-sm font-medium text-red-700">0 selected</span>
            <button type="button" onclick="bulkDelete()" class="inline-flex items-center gap-1.5 bg-red-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-red-700 transition-colors">
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Delete Selected
            </button>
            <button type="button" onclick="clearSelection()" class="inline-flex items-center gap-1.5 text-red-600 px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-red-100 transition-colors">
                Clear
            </button>
        </div>
    </div>

    <!-- Brands Table -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-4 py-3 w-10">
                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)" class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500 cursor-pointer">
                    </th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Name</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Slug</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Products</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Status</th>
                    <th class="text-right px-4 py-3 font-medium text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if (empty($brands)): ?>
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center text-gray-400">
                        <i data-lucide="package-open" class="w-10 h-10 mx-auto mb-2 opacity-50"></i>
                        <p class="text-sm">No brands found. Click "Add Brand" to create one.</p>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($brands as $b): ?>
                <tr class="hover:bg-gray-50/50 transition-colors" data-id="<?= $b['id'] ?>">
                    <td class="px-4 py-3">
                        <input type="checkbox" class="brand-check w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500 cursor-pointer" value="<?= $b['id'] ?>" onchange="updateBulkBar()">
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <?php if ($b['logo']): ?>
                            <img src="/<?= e($b['logo']) ?>" alt="<?= e($b['name']) ?>" class="w-8 h-8 rounded-lg object-cover bg-gray-100">
                            <?php else: ?>
                            <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center shrink-0">
                                <i data-lucide="tag" class="w-4 h-4 text-gray-400"></i>
                            </div>
                            <?php endif; ?>
                            <div>
                                <div class="font-medium text-gray-900"><?= e($b['name']) ?></div>
                                <?php if ($b['description']): ?>
                                <div class="text-xs text-gray-400 truncate max-w-[200px]"><?= e($b['description']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs font-mono"><?= e($b['slug']) ?></td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center gap-1 text-gray-600">
                            <i data-lucide="package" class="w-3.5 h-3.5"></i>
                            <?= $b['product_count'] ?>
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $b['is_active'] ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600' ?>"><?= $b['is_active'] ? 'Active' : 'Inactive' ?></span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <button onclick="openEditModal(<?= $b['id'] ?>, '<?= e(addslashes($b['name'])) ?>', '<?= e(addslashes($b['slug'])) ?>', '<?= e(addslashes($b['description'] ?? '')) ?>', <?= $b['is_active'] ?>, '<?= e($b['logo'] ?? '') ?>')" class="p-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                                <i data-lucide="pencil" class="w-4 h-4"></i>
                            </button>
                            <button onclick="openDeleteModal(<?= $b['id'] ?>, '<?= e(addslashes($b['name'])) ?>')" class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Bulk Delete Form -->
<form id="bulkDeleteForm" method="POST" action="/admin/brands/bulk-delete" style="display:none;">
    <?= csrf() ?>
    <input type="hidden" name="ids" id="bulkIds" value="">
</form>

<script>
function toggleSelectAll(master) {
    document.querySelectorAll('.brand-check').forEach(function(cb) {
        cb.checked = master.checked;
        cb.closest('tr').classList.toggle('bg-amber-50/50', master.checked);
    });
    updateBulkBar();
}

function updateBulkBar() {
    var checked = document.querySelectorAll('.brand-check:checked');
    var bar = document.getElementById('bulkBar');
    var count = document.getElementById('bulkCount');
    var selectAll = document.getElementById('selectAll');
    if (checked.length > 0) {
        bar.classList.remove('hidden');
        count.textContent = checked.length + ' selected';
    } else {
        bar.classList.add('hidden');
    }
    var all = document.querySelectorAll('.brand-check');
    selectAll.checked = all.length > 0 && checked.length === all.length;
    selectAll.indeterminate = checked.length > 0 && checked.length < all.length;
}

function clearSelection() {
    document.querySelectorAll('.brand-check').forEach(function(cb) {
        cb.checked = false;
        cb.closest('tr').classList.remove('bg-amber-50/50');
    });
    document.getElementById('selectAll').checked = false;
    document.getElementById('selectAll').indeterminate = false;
    updateBulkBar();
}

function bulkDelete() {
    var checked = document.querySelectorAll('.brand-check:checked');
    if (checked.length === 0) return;
    var ids = Array.from(checked).map(function(cb) { return cb.value; }).join(',');
    if (!confirm('Delete ' + checked.length + ' selected brands? This cannot be undone.')) return;
    document.getElementById('bulkIds').value = ids;
    document.getElementById('bulkDeleteForm').submit();
}

document.addEventListener('change', function(e) {
    if (e.target.classList.contains('brand-check')) {
        e.target.closest('tr').classList.toggle('bg-amber-50/50', e.target.checked);
        updateBulkBar();
    }
});

function openEditModal(id, name, slug, description, isActive, logo) {
    document.getElementById('editId').value = id;
    document.getElementById('editName').value = name;
    document.getElementById('editSlug').value = slug;
    document.getElementById('editDescription').value = description;
    document.getElementById('editIsActive').checked = isActive === 1;
    document.getElementById('editForm').action = '/admin/brands/' + id + '/update';
    var preview = document.getElementById('editLogoPreview');
    var previewImg = document.getElementById('editLogoImg');
    if (logo) {
        previewImg.src = '/' + logo;
        preview.classList.remove('hidden');
    } else {
        preview.classList.add('hidden');
    }
    document.getElementById('editModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    lucide.createIcons();
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.body.style.overflow = '';
}

function openDeleteModal(id, name) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteBrandName').textContent = name;
    document.getElementById('deleteForm').action = '/admin/brands/' + id + '/delete';
    document.getElementById('deleteModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    lucide.createIcons();
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeEditModal();
        closeDeleteModal();
    }
});
</script>