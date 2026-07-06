<?php
$transactions = Database::select("SELECT * FROM transactions ORDER BY created_at DESC LIMIT 20");
$stats = [
    'mpesa' => Database::selectOne("SELECT COALESCE(SUM(amount),0) as t FROM transactions WHERE payment_method = 'mpesa'")['t'] ?? 0,
    'card' => Database::selectOne("SELECT COALESCE(SUM(amount),0) as t FROM transactions WHERE payment_method = 'card'")['t'] ?? 0,
    'total' => Database::selectOne("SELECT COALESCE(SUM(amount),0) as t FROM transactions")['t'] ?? 0,
];
$gateways = [
    ['name'=>'M-Pesa','icon'=>'smartphone','color'=>'bg-green-100 text-green-700','status'=>Database::selectOne("SELECT value FROM settings WHERE `key` = 'mpesa_enabled'")['value'] ?? '0'],
    ['name'=>'Stripe','icon'=>'credit-card','color'=>'bg-purple-100 text-purple-700','status'=>Database::selectOne("SELECT value FROM settings WHERE `key` = 'stripe_enabled'")['value'] ?? '0'],
    ['name'=>'IntaSend','icon'=>'zap','color'=>'bg-blue-100 text-blue-700','status'=>Database::selectOne("SELECT value FROM settings WHERE `key` = 'intasend_enabled'")['value'] ?? '0'],
    ['name'=>'PesaPal','icon'=>'wallet','color'=>'bg-orange-100 text-orange-700','status'=>Database::selectOne("SELECT value FROM settings WHERE `key` = 'pesapal_enabled'")['value'] ?? '0'],
    ['name'=>'PayPal','icon'=>'globe','color'=>'bg-amber-100 text-amber-700','status'=>Database::selectOne("SELECT value FROM settings WHERE `key` = 'paypal_enabled'")['value'] ?? '0'],
];
?>
<div class="space-y-6">
    <h1 class="font-heading font-semibold text-xl text-gray-900">Payments</h1>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <p class="text-sm text-gray-500">Total Revenue</p>
            <p class="text-2xl font-bold mt-1"><?= formatMoney($stats['total']) ?></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <p class="text-sm text-gray-500">M-Pesa Collections</p>
            <p class="text-2xl font-bold mt-1 text-green-600"><?= formatMoney($stats['mpesa']) ?></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <p class="text-sm text-gray-500">Card Payments</p>
            <p class="text-2xl font-bold mt-1 text-purple-600"><?= formatMoney($stats['card']) ?></p>
        </div>
    </div>

    <!-- Gateway Status -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <?php foreach ($gateways as $g): ?>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 <?= explode(' ', $g['color'])[0] ?> rounded-lg flex items-center justify-center"><i data-lucide="<?= $g['icon'] ?>" class="w-5 h-5 <?= explode(' ', $g['color'])[1] ?>"></i></div>
                <div><h3 class="font-medium"><?= $g['name'] ?></h3><span class="text-xs <?= $g['status'] === '1' ? 'text-amber-600' : 'text-gray-500' ?>">● <?= $g['status'] === '1' ? 'Active' : 'Inactive' ?></span></div>
            </div>
            <a href="/admin/payments/settings" class="text-xs text-amber-600 hover:text-amber-700 font-medium">Configure →</a>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Recent Transactions -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <h3 class="font-medium px-4 pt-4">Recent Transactions</h3>
        <table class="w-full text-sm mt-2">
            <thead class="bg-gray-50"><tr><th class="text-left px-4 py-2.5 text-xs font-medium text-gray-600">Reference</th><th class="text-left px-4 py-2.5 text-xs font-medium text-gray-600">Method</th><th class="text-left px-4 py-2.5 text-xs font-medium text-gray-600">Amount</th><th class="text-left px-4 py-2.5 text-xs font-medium text-gray-600">Status</th><th class="text-left px-4 py-2.5 text-xs font-medium text-gray-600">Date</th></tr></thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach ($transactions as $t): ?>
                <tr class="hover:bg-gray-50/50">
                    <td class="px-4 py-2.5 font-mono text-xs"><?= e($t['reference'] ?? '-') ?></td>
                    <td class="px-4 py-2.5"><span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100"><?= ucfirst($t['payment_method']) ?></span></td>
                    <td class="px-4 py-2.5 font-medium"><?= formatMoney($t['amount']) ?></td>
                    <td class="px-4 py-2.5"><span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $t['status'] === 'completed' ? 'bg-amber-100 text-amber-700' : 'bg-yellow-100 text-yellow-700' ?>"><?= ucfirst($t['status']) ?></span></td>
                    <td class="px-4 py-2.5 text-xs text-gray-500"><?= formatDate($t['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($transactions)): ?>
                    <tr><td colspan="5" class="px-4 py-12 text-center text-gray-500">No transactions yet</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>