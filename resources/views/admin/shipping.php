<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="font-heading font-semibold text-xl text-gray-900">Shipping Zones</h1>
        <button onclick="document.getElementById('addForm').classList.toggle('hidden'); document.getElementById('editForm').classList.add('hidden');" class="inline-flex items-center gap-2 bg-amber-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors">
            <i data-lucide="plus" class="w-4 h-4"></i> Add Zone
        </button>
    </div>

    <!-- Add Form -->
    <div id="addForm" class="hidden bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-medium text-gray-900 mb-4">New Shipping Zone</h3>
        <form method="POST" action="/admin/shipping/store" class="space-y-4">
            <?= csrf() ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Zone Name</label>
                    <input type="text" name="name" placeholder="e.g. Nairobi" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Region</label>
                    <input type="text" name="region" placeholder="e.g. Nairobi County" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Base Fee (KSh)</label>
                    <input type="number" name="base_fee" step="0.01" min="0" placeholder="0.00" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Free Above (KSh)</label>
                    <input type="number" name="free_above" step="0.01" min="0" placeholder="0.00" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
            </div>
            <div class="flex items-center justify-between">
                <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" name="is_active" value="1" checked class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500">
                    <span class="text-sm text-gray-700">Active</span>
                </label>
                <div class="flex gap-2">
                    <button type="button" onclick="document.getElementById('addForm').classList.add('hidden')" class="px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 border border-gray-200 hover:bg-gray-50 transition-colors">Cancel</button>
                    <button type="submit" class="bg-amber-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors">Save Zone</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Edit Form (inline, shown when editing a row) -->
    <div id="editForm" class="hidden bg-white rounded-xl border border-amber-200 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-medium text-gray-900">Edit Shipping Zone</h3>
            <button onclick="document.getElementById('editForm').classList.add('hidden')" class="p-1 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form id="editZoneForm" method="POST" class="space-y-4">
            <input type="hidden" name="id" id="editId">
            <?= csrf() ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Zone Name</label>
                    <input type="text" name="name" id="editName" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Region</label>
                    <input type="text" name="region" id="editRegion" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Base Fee (KSh)</label>
                    <input type="number" name="base_fee" id="editBaseFee" step="0.01" min="0" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Free Above (KSh)</label>
                    <input type="number" name="free_above" id="editFreeAbove" step="0.01" min="0" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
            </div>
            <div class="flex items-center justify-between">
                <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" name="is_active" id="editIsActive" value="1" class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500">
                    <span class="text-sm text-gray-700">Active</span>
                </label>
                <div class="flex gap-2">
                    <button type="button" onclick="document.getElementById('editForm').classList.add('hidden')" class="px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 border border-gray-200 hover:bg-gray-50 transition-colors">Cancel</button>
                    <button type="submit" class="bg-amber-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors">Update Zone</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Zones Table -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <?php if (empty($zones)): ?>
        <div class="px-4 py-12 text-center text-gray-400">
            <i data-lucide="truck" class="w-10 h-10 mx-auto mb-2 opacity-50"></i>
            <p class="text-sm">No shipping zones found. Click "Add Zone" to create one.</p>
        </div>
        <?php else: ?>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Name</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600 hidden md:table-cell">Region</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Base Fee</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600 hidden sm:table-cell">Free Above</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Status</th>
                    <th class="text-right px-4 py-3 font-medium text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($zones as $z): ?>
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-amber-50 rounded-lg flex items-center justify-center shrink-0">
                                <i data-lucide="map-pin" class="w-4 h-4 text-amber-600"></i>
                            </div>
                            <span class="font-medium text-gray-900"><?= e($z['name']) ?></span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-gray-600 hidden md:table-cell"><?= e($z['region']) ?></td>
                    <td class="px-4 py-3 text-gray-900 font-medium"><?= formatMoney($z['base_fee']) ?></td>
                    <td class="px-4 py-3 text-gray-600 hidden sm:table-cell"><?= $z['free_above'] > 0 ? formatMoney($z['free_above']) : '<span class="text-gray-400">—</span>' ?></td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $z['is_active'] ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600' ?>"><?= $z['is_active'] ? 'Active' : 'Inactive' ?></span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <button onclick="openEditForm(<?= $z['id'] ?>, '<?= e(addslashes($z['name'])) ?>', '<?= e(addslashes($z['region'])) ?>', <?= (float)$z['base_fee'] ?>, <?= (float)($z['free_above'] ?? 0) ?>, <?= (int)$z['is_active'] ?>)" class="p-1.5 text-gray-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" title="Edit">
                                <i data-lucide="pencil" class="w-4 h-4"></i>
                            </button>
                            <form method="POST" action="/admin/shipping/<?= $z['id'] ?>/delete" onsubmit="return confirm('Delete this shipping zone? This cannot be undone.')">
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
</div>

<script>
function openEditForm(id, name, region, baseFee, freeAbove, isActive) {
    document.getElementById('editId').value = id;
    document.getElementById('editName').value = name;
    document.getElementById('editRegion').value = region;
    document.getElementById('editBaseFee').value = baseFee;
    document.getElementById('editFreeAbove').value = freeAbove;
    document.getElementById('editIsActive').checked = isActive === 1;
    document.getElementById('editZoneForm').action = '/admin/shipping/' + id + '/update';
    document.getElementById('addForm').classList.add('hidden');
    document.getElementById('editForm').classList.remove('hidden');
    document.getElementById('editForm').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    lucide.createIcons();
}

function openEditCityForm(id, name, sortOrder, isActive) {
    document.getElementById('editCityId').value = id;
    document.getElementById('editCityName').value = name;
    document.getElementById('editCitySortOrder').value = sortOrder;
    document.getElementById('editCityIsActive').checked = isActive === 1;
    document.getElementById('editCityForm').action = '/admin/shipping/cities/' + id + '/update';
    document.getElementById('addCityForm').classList.add('hidden');
    document.getElementById('editCityForm').classList.remove('hidden');
    document.getElementById('editCityForm').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    lucide.createIcons();
}
</script>

<!-- Delivery Cities Section -->
<div class="mt-10 pt-8 border-t border-gray-200" id="cities">
    <div class="flex items-center justify-between mb-4">
        <h2 class="font-heading font-semibold text-xl text-gray-900">Delivery Cities</h2>
        <button onclick="document.getElementById('addCityForm').classList.toggle('hidden'); document.getElementById('editCityForm').classList.add('hidden');" class="inline-flex items-center gap-2 bg-amber-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors">
            <i data-lucide="plus" class="w-4 h-4"></i> Add City
        </button>
    </div>

    <!-- Add City Form -->
    <div id="addCityForm" class="hidden bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-4">
        <h3 class="font-medium text-gray-900 mb-4">New Delivery City</h3>
        <form method="POST" action="/admin/shipping/cities/store" class="space-y-4">
            <?= csrf() ?>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">City Name</label>
                    <input type="text" name="name" placeholder="e.g. Nairobi" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" min="0" value="0" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div class="flex items-end gap-4">
                    <label class="inline-flex items-center gap-2 cursor-pointer select-none pb-2.5">
                        <input type="checkbox" name="is_active" value="1" checked class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500">
                        <span class="text-sm text-gray-700">Active</span>
                    </label>
                </div>
            </div>
            <div class="flex gap-2">
                <button type="button" onclick="document.getElementById('addCityForm').classList.add('hidden')" class="px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 border border-gray-200 hover:bg-gray-50 transition-colors">Cancel</button>
                <button type="submit" class="bg-amber-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors">Save City</button>
            </div>
        </form>
    </div>

    <!-- Edit City Form -->
    <div id="editCityForm" class="hidden bg-white rounded-xl border border-amber-200 shadow-sm p-6 mb-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-medium text-gray-900">Edit City</h3>
            <button onclick="document.getElementById('editCityForm').classList.add('hidden')" class="p-1 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form id="editCityForm" method="POST" class="space-y-4">
            <input type="hidden" name="id" id="editCityId">
            <?= csrf() ?>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">City Name</label>
                    <input type="text" name="name" id="editCityName" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" id="editCitySortOrder" min="0" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div class="flex items-end gap-4">
                    <label class="inline-flex items-center gap-2 cursor-pointer select-none pb-2.5">
                        <input type="checkbox" name="is_active" id="editCityIsActive" value="1" class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500">
                        <span class="text-sm text-gray-700">Active</span>
                    </label>
                </div>
            </div>
            <div class="flex gap-2">
                <button type="button" onclick="document.getElementById('editCityForm').classList.add('hidden')" class="px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 border border-gray-200 hover:bg-gray-50 transition-colors">Cancel</button>
                <button type="submit" class="bg-amber-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors">Update City</button>
            </div>
        </form>
    </div>

    <!-- Cities Table -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <?php if (empty($cities ?? [])): ?>
        <div class="px-4 py-12 text-center text-gray-400">
            <i data-lucide="map-pin" class="w-10 h-10 mx-auto mb-2 opacity-50"></i>
            <p class="text-sm">No delivery cities found. Click "Add City" to create one.</p>
        </div>
        <?php else: ?>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">City Name</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600 hidden sm:table-cell">Sort Order</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Status</th>
                    <th class="text-right px-4 py-3 font-medium text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($cities as $c): ?>
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-amber-50 rounded-lg flex items-center justify-center shrink-0">
                                <i data-lucide="building" class="w-4 h-4 text-amber-600"></i>
                            </div>
                            <span class="font-medium text-gray-900"><?= e($c['name']) ?></span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-gray-600 hidden sm:table-cell"><?= (int)$c['sort_order'] ?></td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= ($c['is_active'] ?? 1) ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600' ?>"><?= ($c['is_active'] ?? 1) ? 'Active' : 'Inactive' ?></span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <button onclick="openEditCityForm(<?= $c['id'] ?>, '<?= e(addslashes($c['name'])) ?>', <?= (int)$c['sort_order'] ?>, <?= (int)($c['is_active'] ?? 1) ?>)" class="p-1.5 text-gray-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" title="Edit">
                                <i data-lucide="pencil" class="w-4 h-4"></i>
                            </button>
                            <form method="POST" action="/admin/shipping/cities/<?= $c['id'] ?>/delete" onsubmit="return confirm('Delete this city? This cannot be undone.')">
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
</div>