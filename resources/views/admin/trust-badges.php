<div class="max-w-5xl mx-auto space-y-6">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Trust Badges</h1>
            <p class="mt-1 text-sm text-gray-500">Manage the trust badges displayed on your storefront.</p>
        </div>
        <button onclick="document.getElementById('badge-form-section').classList.remove('hidden');document.getElementById('badge-form').reset();document.getElementById('form-title').textContent='Add Trust Badge';document.getElementById('badge-id-input').value='';document.getElementById('badge-form-section').scrollIntoView({behavior:'smooth'})"
                class="inline-flex items-center gap-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium px-4 py-2.5 rounded-lg transition-colors shadow-sm">
            <i data-lucide="plus" class="w-4 h-4"></i>
            Add Badge
        </button>
    </div>

    <!-- Success / Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="flex items-center gap-2 bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-lg">
            <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>
            <?= e($_SESSION['success']) ?>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="flex items-center gap-2 bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg">
            <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
            <?= e($_SESSION['error']) ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Badge List -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-800">All Badges</h2>
            <span class="text-xs text-gray-400"><?= count($badges ?? []) ?> total</span>
        </div>

        <?php if (empty($badges)): ?>
            <div class="flex flex-col items-center justify-center py-16 px-4 text-center">
                <div class="w-14 h-14 rounded-full bg-amber-50 flex items-center justify-center mb-4">
                    <i data-lucide="award" class="w-7 h-7 text-amber-400"></i>
                </div>
                <p class="text-sm font-medium text-gray-700">No trust badges yet</p>
                <p class="text-xs text-gray-400 mt-1">Click "Add Badge" to create your first trust badge.</p>
            </div>
        <?php else: ?>
            <div class="divide-y divide-gray-100" id="badge-list">
                <?php $total = count($badges); ?>
                <?php foreach ($badges as $index => $badge): ?>
                    <div class="flex items-center gap-4 px-5 py-4 hover:bg-amber-50/40 transition-colors group" id="badge-row-<?= e($badge['id']) ?>">
                        <!-- Sort Controls -->
                        <div class="flex flex-col items-center gap-0.5 shrink-0">
                            <?php if ($index > 0): ?>
                                <a href="?action=move_up&id=<?= e($badge['id']) ?>&<?= csrf() ?>=1"
                                   class="p-1 rounded text-gray-400 hover:text-amber-600 hover:bg-amber-50 transition-colors"
                                   title="Move Up">
                                    <i data-lucide="chevron-up" class="w-4 h-4"></i>
                                </a>
                            <?php else: ?>
                                <span class="p-1"><i data-lucide="chevron-up" class="w-4 h-4 text-gray-200"></i></span>
                            <?php endif; ?>
                            <span class="text-[10px] font-semibold text-gray-400 tabular-nums"><?= e($badge['sort_order'] ?? $index + 1) ?></span>
                            <?php if ($index < $total - 1): ?>
                                <a href="?action=move_down&id=<?= e($badge['id']) ?>&<?= csrf() ?>=1"
                                   class="p-1 rounded text-gray-400 hover:text-amber-600 hover:bg-amber-50 transition-colors"
                                   title="Move Down">
                                    <i data-lucide="chevron-down" class="w-4 h-4"></i>
                                </a>
                            <?php else: ?>
                                <span class="p-1"><i data-lucide="chevron-down" class="w-4 h-4 text-gray-200"></i></span>
                            <?php endif; ?>
                        </div>

                        <!-- Drag Handle -->
                        <div class="cursor-grab active:cursor-grabbing text-gray-300 hover:text-amber-500 transition-colors shrink-0">
                            <i data-lucide="grip-vertical" class="w-5 h-5"></i>
                        </div>

                        <!-- Icon Preview -->
                        <div class="w-10 h-10 rounded-lg bg-amber-50 border border-amber-100 flex items-center justify-center shrink-0">
                            <i data-lucide="<?= e($badge['icon_name'] ?? 'award') ?>" class="w-5 h-5 text-amber-600"></i>
                        </div>

                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-medium text-gray-800 truncate"><?= e($badge['title']) ?></p>
                                <?php if (!empty($badge['is_active'])): ?>
                                    <span class="inline-flex items-center gap-1 text-[10px] font-semibold uppercase tracking-wide text-green-700 bg-green-50 border border-green-200 rounded-full px-2 py-0.5">
                                        <i data-lucide="check" class="w-2.5 h-2.5"></i> Active
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 text-[10px] font-semibold uppercase tracking-wide text-gray-500 bg-gray-50 border border-gray-200 rounded-full px-2 py-0.5">
                                        <i data-lucide="pause" class="w-2.5 h-2.5"></i> Inactive
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($badge['subtitle'])): ?>
                                <p class="text-xs text-gray-400 mt-0.5 truncate"><?= e($badge['subtitle']) ?></p>
                            <?php endif; ?>
                        </div>

                        <!-- Icon Label -->
                        <span class="hidden sm:inline-flex text-[11px] font-mono text-gray-400 bg-gray-50 border border-gray-100 rounded px-2 py-0.5 shrink-0">
                            <?= e($badge['icon_name'] ?? '—') ?>
                        </span>

                        <!-- Actions -->
                        <div class="flex items-center gap-1 shrink-0 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
                            <a href="?action=edit&id=<?= e($badge['id']) ?>"
                               class="p-2 rounded-lg text-gray-400 hover:text-amber-600 hover:bg-amber-50 transition-colors"
                               title="Edit">
                                <i data-lucide="pencil" class="w-4 h-4"></i>
                            </a>
                            <button type="button"
                                    onclick="confirmDelete(<?= e($badge['id']) ?>, '<?= e(addslashes($badge['title'])) ?>')"
                                    class="p-2 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                                    title="Delete">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Add / Edit Form -->
    <div id="badge-form-section" class="<?= isset($editBadge) ? '' : 'hidden' ?> bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 bg-amber-50/50">
            <h2 id="form-title" class="text-base font-semibold text-gray-800">
                <?= isset($editBadge) ? 'Edit Trust Badge' : 'Add Trust Badge' ?>
            </h2>
            <p class="text-xs text-gray-500 mt-0.5">
                <?= isset($editBadge) ? 'Update the badge details below.' : 'Fill in the details to create a new trust badge.' ?>
            </p>
        </div>

        <form id="badge-form" method="POST" action="<?= isset($editBadge) ? '?action=update&id=' . e($editBadge['id']) : '?action=store' ?>" class="p-5 space-y-5">
            <?= csrf() ?>
            <input type="hidden" name="id" id="badge-id-input" value="<?= isset($editBadge) ? e($editBadge['id']) : '' ?>">

            <!-- Title -->
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1.5">Title <span class="text-red-500">*</span></label>
                <input type="text" id="title" name="title" required maxlength="100"
                       value="<?= isset($editBadge) ? e($editBadge['title']) : '' ?>"
                       placeholder="e.g. Free Shipping"
                       class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none transition-shadow">
            </div>

            <!-- Subtitle -->
            <div>
                <label for="subtitle" class="block text-sm font-medium text-gray-700 mb-1.5">Subtitle</label>
                <input type="text" id="subtitle" name="subtitle" maxlength="200"
                       value="<?= isset($editBadge) ? e($editBadge['subtitle']) : '' ?>"
                       placeholder="e.g. On orders over $50"
                       class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none transition-shadow">
            </div>

            <!-- Icon Name -->
            <div>
                <label for="icon_name" class="block text-sm font-medium text-gray-700 mb-1.5">Icon</label>
                <div class="flex items-center gap-3">
                    <select id="icon_name" name="icon_name"
                            onchange="updateIconPreview(this.value)"
                            class="flex-1 rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none transition-shadow">
                        <?php
                        $icons = [
                            'truck' => 'Truck (Shipping)',
                            'shield-check' => 'Shield Check (Security)',
                            'headphones' => 'Headphones (Support)',
                            'refresh-cw' => 'Refresh (Returns)',
                            'clock' => 'Clock (Delivery Time)',
                            'check-circle' => 'Check Circle (Guarantee)',
                            'award' => 'Award (Quality)',
                            'credit-card' => 'Credit Card (Payment)',
                            'map-pin' => 'Map Pin (Location)',
                            'phone' => 'Phone (Contact)',
                        ];
                        $selectedIcon = isset($editBadge) ? $editBadge['icon_name'] : 'truck';
                        foreach ($icons as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= $selectedIcon === $value ? 'selected' : '' ?>>
                                <?= e($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="icon-preview-box" class="w-11 h-11 rounded-lg bg-amber-50 border border-amber-200 flex items-center justify-center shrink-0">
                        <i data-lucide="<?= e($selectedIcon) ?>" class="w-5 h-5 text-amber-600" id="icon-preview-icon"></i>
                    </div>
                </div>
            </div>

            <!-- Sort Order & Active Status -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <!-- Sort Order -->
                <div>
                    <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-1.5">Sort Order</label>
                    <input type="number" id="sort_order" name="sort_order" min="0" max="999"
                           value="<?= isset($editBadge) ? e($editBadge['sort_order']) : '0' ?>"
                           class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 focus:outline-none transition-shadow">
                    <p class="text-[11px] text-gray-400 mt-1">Lower numbers appear first.</p>
                </div>

                <!-- Active -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
                    <div class="mt-2">
                        <label class="inline-flex items-center gap-3 cursor-pointer select-none group">
                            <div class="relative">
                                <input type="checkbox" name="is_active" value="1"
                                       <?= (isset($editBadge) && $editBadge['is_active']) || !isset($editBadge) ? 'checked' : '' ?>
                                       class="peer sr-only"
                                       id="is_active">
                                <div class="w-10 h-6 bg-gray-200 rounded-full peer-checked:bg-amber-500 transition-colors"></div>
                                <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow-sm peer-checked:translate-x-4 transition-transform"></div>
                            </div>
                            <span class="text-sm text-gray-600 group-hover:text-gray-800 transition-colors">Active</span>
                        </label>
                    </div>
                    <p class="text-[11px] text-gray-400 mt-1">Only active badges are shown to customers.</p>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                <a href="?" class="text-sm font-medium text-gray-600 hover:text-gray-800 px-4 py-2.5 rounded-lg hover:bg-gray-100 transition-colors">
                    Cancel
                </a>
                <button type="submit"
                        class="inline-flex items-center gap-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium px-5 py-2.5 rounded-lg transition-colors shadow-sm">
                    <i data-lucide="<?= isset($editBadge) ? 'save' : 'plus' ?>" class="w-4 h-4"></i>
                    <?= isset($editBadge) ? 'Update Badge' : 'Create Badge' ?>
                </button>
            </div>
        </form>
    </div>

</div>

<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeDeleteModal()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6 space-y-4">
        <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center mx-auto">
            <i data-lucide="alert-triangle" class="w-6 h-6 text-red-500"></i>
        </div>
        <div class="text-center">
            <h3 class="text-lg font-semibold text-gray-900">Delete Badge</h3>
            <p class="text-sm text-gray-500 mt-1">
                Are you sure you want to delete <strong id="delete-badge-name" class="text-gray-700"></strong>? This action cannot be undone.
            </p>
        </div>
        <form id="delete-form" method="POST" action="" class="flex items-center justify-center gap-3">
            <?= csrf() ?>
            <input type="hidden" name="id" id="delete-badge-id">
            <input type="hidden" name="_method" value="DELETE">
            <button type="button" onclick="closeDeleteModal()"
                    class="px-4 py-2.5 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                Cancel
            </button>
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium px-5 py-2.5 rounded-lg transition-colors shadow-sm">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
                Delete
            </button>
        </form>
    </div>
</div>

<script>
    // Initialize Lucide icons after DOM is ready
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });

    // Icon preview updater
    function updateIconPreview(iconName) {
        const container = document.getElementById('icon-preview-box');
        container.innerHTML = '<i data-lucide="' + iconName + '" class="w-5 h-5 text-amber-600"></i>';
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    // Delete modal
    function confirmDelete(id, name) {
        document.getElementById('delete-badge-id').value = id;
        document.getElementById('delete-badge-name').textContent = name;
        document.getElementById('delete-form').action = '?action=delete&id=' + id;
        document.getElementById('delete-modal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    function closeDeleteModal() {
        document.getElementById('delete-modal').classList.add('hidden');
        document.body.style.overflow = '';
    }

    // Close modal on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeDeleteModal();
        }
    });
</script>