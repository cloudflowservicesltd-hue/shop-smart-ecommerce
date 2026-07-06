<?php
$today = date('Y-m-d');
$todaySales = Database::selectOne("SELECT COUNT(*) as cnt, COALESCE(SUM(total),0) as total FROM orders WHERE DATE(created_at) = ? AND is_pos = 1", [$today]);
$posOrders = Database::select("SELECT o.* FROM orders o WHERE o.is_pos = 1 ORDER BY o.created_at DESC LIMIT 50");
$cashiers = Database::select("SELECT * FROM users WHERE role = 'cashier' AND is_active = 1");
?>
<div class="space-y-4">
    <h1 class="font-heading font-semibold text-xl text-gray-900">POS Sales History</h1>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <p class="text-sm text-gray-500">Today's Sales</p>
            <p class="text-2xl font-bold mt-1"><?= number_format($todaySales['cnt'] ?? 0) ?></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <p class="text-sm text-gray-500">Today's Revenue</p>
            <p class="text-2xl font-bold mt-1"><?= formatMoney($todaySales['total'] ?? 0) ?></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <p class="text-sm text-gray-500">Active Cashiers</p>
            <p class="text-2xl font-bold mt-1"><?= count($cashiers) ?></p>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr><th class="text-left px-4 py-3 font-medium text-gray-600">Receipt #</th><th class="text-left px-4 py-3 font-medium text-gray-600">Reference</th><th class="text-left px-4 py-3 font-medium text-gray-600">Customer</th><th class="text-left px-4 py-3 font-medium text-gray-600">Payment</th><th class="text-left px-4 py-3 font-medium text-gray-600">Total</th><th class="text-left px-4 py-3 font-medium text-gray-600">Time</th></tr></thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($posOrders as $o): ?>
                <tr class="hover:bg-gray-50/50">
                    <td class="px-4 py-2.5 font-mono text-xs"><?= e($o['order_number']) ?></td>
                    <td class="px-4 py-2.5"><?= e($o['payment_reference'] ?? '-') ?></td>
                    <td class="px-4 py-2.5"><?= e($o['customer_name'] ?? 'Walk-in') ?></td>
                    <td class="px-4 py-2.5 text-xs"><?= ucfirst($o['payment_method'] ?? '-') ?></td>
                    <td class="px-4 py-2.5 font-medium"><?= formatMoney($o['total']) ?></td>
                    <td class="px-4 py-2.5 text-xs text-gray-500"><?= date('h:i A', strtotime($o['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($posOrders)): ?>
                    <tr><td colspan="6" class="px-4 py-12 text-center text-gray-500">No POS sales yet</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>