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
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Order #</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-600">Customer</th>
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
                <tr class="hover:bg-gray-50/50">
                    <td class="px-4 py-3 font-mono text-xs font-medium"><?= e($o['order_number']) ?></td>
                    <td class="px-4 py-3"><div><p class="font-medium"><?= e($o['customer_name']) ?></p><p class="text-xs text-gray-500"><?= e($o['customer_email'] ?? '') ?></p></div></td>
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
                    <tr><td colspan="8" class="px-4 py-12 text-center text-gray-500">No orders found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?= View::pagination($orders, "/admin/orders?tab={$tab}") ?>
    </div>
</div>