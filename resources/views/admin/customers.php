<?php
$page = (int)Request::query('page', 1);
$search = Request::query('search', '');
$where = "role = 'customer'";
$params = [];
if ($search) { $where .= " AND (name LIKE ? OR email LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
$customers = Database::paginate('users', $page, 15, $where, $params, 'created_at DESC');
?>
<div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <h1 class="font-heading font-semibold text-xl text-gray-900">Customers</h1>
        <form method="GET" class="relative w-full sm:w-72">
            <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search customers..." class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
        </form>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr><th class="text-left px-4 py-3 font-medium text-gray-600">Customer</th><th class="text-left px-4 py-3 font-medium text-gray-600">Phone</th><th class="text-left px-4 py-3 font-medium text-gray-600">Orders</th><th class="text-left px-4 py-3 font-medium text-gray-600">Total Spent</th><th class="text-left px-4 py-3 font-medium text-gray-600">Joined</th><th class="text-right px-4 py-3 font-medium text-gray-600">Actions</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($customers['data'] as $c):
                    $orderCount = Database::count('orders', 'customer_id = ?', [$c['id']]);
                    $totalSpent = Database::selectOne("SELECT COALESCE(SUM(total),0) as t FROM orders WHERE customer_id = ? AND payment_status = 'paid'", [$c['id']])['t'] ?? 0;
                ?>
                <tr class="hover:bg-gray-50/50">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-amber-100 rounded-full flex items-center justify-center text-amber-700 text-sm font-bold"><?= strtoupper(substr($c['name'], 0, 1)) ?></div>
                            <div><p class="font-medium text-gray-900"><?= e($c['name']) ?></p><p class="text-xs text-gray-500"><?= e($c['email']) ?></p></div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-gray-600"><?= e($c['phone'] ?? '-') ?></td>
                    <td class="px-4 py-3"><?= $orderCount ?></td>
                    <td class="px-4 py-3 font-medium"><?= formatMoney($totalSpent) ?></td>
                    <td class="px-4 py-3 text-gray-500 text-xs"><?= formatDate($c['created_at']) ?></td>
                    <td class="px-4 py-3 text-right">
                        <a href="/admin/customers/<?= $c['id'] ?>" class="text-amber-600 hover:text-amber-700 text-xs font-medium">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?= View::pagination($customers, '/admin/customers?' . http_build_query(['search' => $search])) ?>
    </div>
</div>