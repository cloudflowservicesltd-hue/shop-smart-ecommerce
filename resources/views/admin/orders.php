<?php
$tab = Request::query('tab', 'all');
$page = (int)Request::query('page', 1);
$where = "is_pos = 0";
$params = [];
if ($tab !== 'all') { $where .= " AND status = ?"; $params[] = $tab; }
$orders = Database::paginate('orders', $page, 15, $where, $params, 'created_at DESC');
$tabs = ['all'=>'All','pending'=>'Pending','paid'=>'Paid','processing'=>'Processing','shipped'=>'Shipped','delivered'=>'Delivered','cancelled'=>'Cancelled','failed'=>'Failed'];
$tabCounts = [];
foreach (['all','pending','paid','processing','shipped','delivered','cancelled','failed'] as $t) {
    $tabCounts[$t] = $t === 'all' ? Database::count('orders', 'is_pos = 0') : Database::count('orders', "status = ? AND is_pos = 0", [$t]);
}
$statusColors = ['pending'=>'bg-yellow-100 text-yellow-700','paid'=>'bg-green-100 text-green-700','processing'=>'bg-blue-100 text-blue-700','shipped'=>'bg-indigo-100 text-indigo-700','delivered'=>'bg-amber-100 text-amber-700','cancelled'=>'bg-red-100 text-red-700','failed'=>'bg-red-100 text-red-700'];
$paymentStatusColors = ['paid'=>'text-green-600','pending'=>'text-yellow-600','failed'=>'text-red-600'];
?>
<div class="space-y-4">
    <h1 class="font-heading font-semibold text-xl text-gray-900">Online Orders</h1>

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

    <!-- Tabs -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-2 flex flex-wrap gap-1">
        <?php foreach ($tabs as $key => $label): ?>
            <a href="/admin/orders?tab=<?= $key ?>" class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= $tab === $key ? 'bg-amber-600 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
                <?= $label ?> <span class="ml-1 text-xs opacity-70"><?= $tabCounts[$key] ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-4 py-3 w-10">
                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)" class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500 cursor-pointer">
                    </th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Order #</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Customer</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Shipping</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Date</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Items</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Total</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Payment</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Status</th>
                    <th class="text-right px-4 py-3 font-medium text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($orders['data'] as $o):
                    $itemCount = Database::count('order_items', 'order_id = ?', [$o['id']]);
                ?>
                <tr class="hover:bg-gray-50/50" data-id="<?= $o['id'] ?>">
                    <td class="px-4 py-3">
                        <input type="checkbox" class="order-check w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500 cursor-pointer" value="<?= $o['id'] ?>" onchange="updateBulkBar()">
                    </td>
                    <td class="px-4 py-3 font-mono text-xs font-medium"><?= e($o['order_number']) ?></td>
                    <td class="px-4 py-3"><div><p class="font-medium"><?= e($o['customer_name']) ?></p><p class="text-xs text-gray-500"><?= e($o['customer_email'] ?? '') ?></p></div></td>
                    <td class="px-4 py-3 max-w-[220px]">
                        <?php if (!empty($o['customer_address'])): ?>
                        <div class="group/ship relative">
                            <p class="text-xs text-gray-700 truncate" title="<?= e($o['customer_address']) ?>"><?= e($o['customer_address']) ?></p>
                            <?php if (!empty($o['customer_phone'])): ?>
                            <p class="text-xs text-gray-500 mt-0.5 flex items-center gap-1">
                                <i data-lucide="phone" class="w-3 h-3"></i>
                                <?= e($o['customer_phone']) ?>
                            </p>
                            <?php endif; ?>
                            <?php if (strlen($o['customer_address']) > 50): ?>
                            <p class="text-[10px] text-amber-600 mt-0.5 font-medium">Click "View" for full address</p>
                            <?php endif; ?>
                        </div>
                        <?php elseif ($o['is_pos']): ?>
                        <span class="text-xs text-gray-400 italic">POS — No shipping</span>
                        <?php else: ?>
                        <span class="text-xs text-gray-400 italic">No address</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-gray-600 text-xs"><?= formatDate($o['created_at']) ?></td>
                    <td class="px-4 py-3 text-gray-600"><?= $itemCount ?></td>
                    <td class="px-4 py-3 font-medium"><?= formatMoney($o['total']) ?></td>
                    <td class="px-4 py-3">
                        <span class="text-xs font-semibold <?= $paymentStatusColors[$o['payment_status'] ?? 'pending'] ?? 'text-yellow-600' ?>"><?= ucfirst($o['payment_status'] ?? 'pending') ?></span>
                        <?php if (($o['payment_status'] ?? 'pending') === 'pending' && $o['status'] !== 'cancelled' && $o['status'] !== 'failed'): ?>
                        <span class="inline-flex items-center ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-yellow-50 text-yellow-600 border border-yellow-100">
                            <i data-lucide="clock" class="w-2.5 h-2.5 mr-0.5"></i>Awaiting
                        </span>
                        <?php endif; ?>
                        <p class="text-xs text-gray-400 mt-0.5"><?= ucfirst($o['payment_method'] ?? '-') ?></p>
                    </td>
                    <td class="px-4 py-3">
                        <form method="POST" action="/admin/orders/<?= $o['id'] ?>/status" class="inline">
                            <?= csrf() ?>
                            <select name="status" onchange="this.form.submit()" class="text-xs px-2 py-1 rounded-full font-medium border-0 <?= $statusColors[$o['status']] ?? 'bg-gray-100 text-gray-700' ?>">
                                <?php foreach (['pending','paid','processing','shipped','delivered','cancelled','failed'] as $s): ?>
                                    <option value="<?= $s ?>" <?= $o['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="/admin/orders/<?= $o['id'] ?>" class="text-amber-600 hover:text-amber-700 text-xs font-medium">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($orders['data'])): ?>
                    <tr><td colspan="10" class="px-4 py-12 text-center text-gray-500">No orders found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?= View::pagination($orders, "/admin/orders?tab={$tab}") ?>
    </div>
</div>

<!-- Bulk Delete Form -->
<form id="bulkDeleteForm" method="POST" action="/admin/orders/bulk-delete" style="display:none;">
    <?= csrf() ?>
    <input type="hidden" name="ids" id="bulkIds" value="">
</form>

<script>
function toggleSelectAll(master) {
    document.querySelectorAll('.order-check').forEach(function(cb) {
        cb.checked = master.checked;
        cb.closest('tr').classList.toggle('bg-amber-50/50', master.checked);
    });
    updateBulkBar();
}

function updateBulkBar() {
    var checked = document.querySelectorAll('.order-check:checked');
    var bar = document.getElementById('bulkBar');
    var count = document.getElementById('bulkCount');
    var selectAll = document.getElementById('selectAll');
    if (checked.length > 0) {
        bar.classList.remove('hidden');
        count.textContent = checked.length + ' selected';
    } else {
        bar.classList.add('hidden');
    }
    var all = document.querySelectorAll('.order-check');
    selectAll.checked = all.length > 0 && checked.length === all.length;
    selectAll.indeterminate = checked.length > 0 && checked.length < all.length;
}

function clearSelection() {
    document.querySelectorAll('.order-check').forEach(function(cb) {
        cb.checked = false;
        cb.closest('tr').classList.remove('bg-amber-50/50');
    });
    document.getElementById('selectAll').checked = false;
    document.getElementById('selectAll').indeterminate = false;
    updateBulkBar();
}

function bulkDelete() {
    var checked = document.querySelectorAll('.order-check:checked');
    if (checked.length === 0) return;
    var ids = Array.from(checked).map(function(cb) { return cb.value; }).join(',');
    if (!confirm('Delete ' + checked.length + ' selected orders? This cannot be undone. Order items will also be deleted.')) return;
    document.getElementById('bulkIds').value = ids;
    document.getElementById('bulkDeleteForm').submit();
}

document.addEventListener('change', function(e) {
    if (e.target.classList.contains('order-check')) {
        e.target.closest('tr').classList.toggle('bg-amber-50/50', e.target.checked);
        updateBulkBar();
    }
});
</script>