<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="/admin/settings" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-amber-600 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Settings
            </a>
            <span class="text-gray-300">/</span>
            <h1 class="font-heading font-semibold text-xl text-gray-900">Cities Management</h1>
        </div>
    </div>

    <?php if ($error = Session::getFlash('error')): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if ($success = Session::getFlash('success')): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm"><?= e($success) ?></div>
    <?php endif; ?>

    <!-- Add New City -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-medium mb-4 flex items-center gap-2">
            <i data-lucide="map-pin-plus" class="w-5 h-5 text-amber-600"></i> Add New City
        </h3>
        <form method="POST" action="/admin/settings/cities" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
            <?= csrf() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">City Name</label>
                <input type="text" name="name" required placeholder="e.g. Nairobi" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Shipping Cost</label>
                <input type="number" name="shipping_cost" step="0.01" min="0" value="0" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                <input type="number" name="sort_order" min="0" value="0" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
            </div>
            <div class="flex items-center gap-3">
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" checked class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500">
                    Active
                </label>
                <button type="submit" class="bg-amber-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors">Add City</button>
            </div>
        </form>
    </div>

    <!-- Cities Table -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-medium flex items-center gap-2">
                <i data-lucide="building-2" class="w-5 h-5 text-amber-600"></i> All Cities
                <span class="text-xs text-gray-400 font-normal">(<?= count($cities ?? []) ?> total)</span>
            </h3>
        </div>
        <?php if (empty($cities)): ?>
        <div class="px-6 py-12 text-center text-gray-500 text-sm">
            <i data-lucide="map-pin" class="w-10 h-10 mx-auto text-gray-200 mb-3"></i>
            <p>No cities added yet. Add your first city above.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="text-left px-6 py-3 font-medium text-gray-600">City Name</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-600">Shipping Cost</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-600">Status</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-600">Sort</th>
                        <th class="text-right px-6 py-3 font-medium text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50" id="citiesTableBody">
                    <?php foreach ($cities as $city): ?>
                    <tr class="hover:bg-gray-50/50" data-id="<?= $city['id'] ?>">
                        <td class="px-6 py-3 font-medium text-gray-900"><?= e($city['name']) ?></td>
                        <td class="px-6 py-3 text-gray-600"><?= formatMoney($city['shipping_cost']) ?></td>
                        <td class="px-6 py-3">
                            <?php if ($city['is_active']): ?>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Active
                            </span>
                            <?php else: ?>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-500">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Inactive
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-3 text-gray-500"><?= (int)$city['sort_order'] ?></td>
                        <td class="px-6 py-3 text-right space-x-2">
                            <button onclick="editCity(<?= $city['id'] ?>, '<?= e(addslashes($city['name'])) ?>', <?= $city['shipping_cost'] ?>, <?= $city['is_active'] ?>, <?= $city['sort_order'] ?>)" class="text-amber-600 hover:text-amber-700 text-xs font-medium">Edit</button>
                            <form method="POST" action="/admin/settings/cities" class="inline" onsubmit="return confirm('Delete this city?')">
                                <?= csrf() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $city['id'] ?>">
                                <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-medium">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Edit City Modal -->
<div id="editCityModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40" onclick="if(event.target===this)this.classList.add('hidden'),this.classList.remove('flex')">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 p-6">
        <h3 class="font-heading font-semibold text-lg text-gray-900 mb-4 flex items-center gap-2">
            <i data-lucide="pencil" class="w-5 h-5 text-amber-600"></i> Edit City
        </h3>
        <form method="POST" action="/admin/settings/cities" id="editCityForm">
            <?= csrf() ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="editCityId">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">City Name</label>
                    <input type="text" name="name" id="editCityName" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Shipping Cost</label>
                    <input type="number" name="shipping_cost" id="editCityShippingCost" step="0.01" min="0" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" id="editCitySortOrder" min="0" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="is_active" id="editCityActive" value="1" class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500">
                    Active
                </label>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="document.getElementById('editCityModal').classList.add('hidden');document.getElementById('editCityModal').classList.remove('flex')" class="px-4 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition-colors">Cancel</button>
                <button type="submit" class="bg-amber-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors">Update City</button>
            </div>
        </form>
    </div>
</div>

<script>
function editCity(id, name, shippingCost, isActive, sortOrder) {
    document.getElementById('editCityId').value = id;
    document.getElementById('editCityName').value = name;
    document.getElementById('editCityShippingCost').value = shippingCost;
    document.getElementById('editCitySortOrder').value = sortOrder;
    document.getElementById('editCityActive').checked = isActive == 1;
    const modal = document.getElementById('editCityModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
</script>
<script>lucide.createIcons();</script>