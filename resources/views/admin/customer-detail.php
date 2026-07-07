<?php
$customer = $customer ?? null;
$orders = $orders ?? [];
$stats = $stats ?? ['total' => 0, 'spent' => 0, 'avg' => 0];
$statusColors = ['pending'=>'bg-yellow-100 text-yellow-700','paid'=>'bg-amber-100 text-amber-700','processing'=>'bg-blue-100 text-blue-700','shipped'=>'bg-indigo-100 text-indigo-700','delivered'=>'bg-amber-100 text-amber-700','completed'=>'bg-amber-100 text-amber-700','cancelled'=>'bg-red-100 text-red-700'];
$paymentColors = ['paid'=>'text-amber-600','pending'=>'text-amber-600','failed'=>'text-red-600'];
?>
<div class="space-y-6">
    <!-- Back Button -->
    <a href="/admin/customers" class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-amber-600 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Customers
    </a>

    <!-- Customer Info Card -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="p-6">
            <div class="flex flex-col sm:flex-row sm:items-start gap-5">
                <!-- Avatar -->
                <div class="w-16 h-16 bg-amber-100 rounded-2xl flex items-center justify-center text-amber-700 text-2xl font-bold shrink-0">
                    <?= strtoupper(substr($customer['name'], 0, 1)) ?>
                </div>
                <!-- Info -->
                <div class="flex-1 min-w-0">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
                        <div>
                            <h1 class="font-heading font-semibold text-xl text-gray-900"><?= e($customer['name']) ?></h1>
                            <p class="text-sm text-gray-500"><?= e($customer['email']) ?></p>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium <?= $customer['is_active'] ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500' ?>">
                            <span class="w-1.5 h-1.5 rounded-full <?= $customer['is_active'] ? 'bg-amber-500' : 'bg-gray-400' ?>"></span>
                            <?= $customer['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="text-gray-400 block text-xs uppercase tracking-wide mb-0.5">Phone</span>
                            <span class="text-gray-900"><?= e($customer['phone'] ?? 'Not provided') ?></span>
                        </div>
                        <div>
                            <span class="text-gray-400 block text-xs uppercase tracking-wide mb-0.5">Address</span>
                            <span class="text-gray-900"><?= e($customer['address'] ?? 'Not provided') ?></span>
                            <?php if (!empty($customer['city'])): ?>
                                <span class="text-gray-500">, <?= e($customer['city']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($customer['country'])): ?>
                                <span class="text-gray-500">, <?= e($customer['country']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <span class="text-gray-400 block text-xs uppercase tracking-wide mb-0.5">Joined</span>
                            <span class="text-gray-900"><?= formatDate($customer['created_at']) ?></span>
                        </div>
                        <div>
                            <span class="text-gray-400 block text-xs uppercase tracking-wide mb-0.5">Last Login</span>
                            <span class="text-gray-900"><?= $customer['last_login'] ? timeAgo($customer['last_login']) : 'Never' ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="shopping-bag" class="w-5 h-5 text-amber-600"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Total Orders</p>
                    <p class="text-xl font-bold text-gray-900"><?= number_format((int)$stats['total']) ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="banknote" class="w-5 h-5 text-amber-600"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Total Spent</p>
                    <p class="text-xl font-bold text-gray-900"><?= formatMoney((float)$stats['spent']) ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="trending-up" class="w-5 h-5 text-blue-600"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Avg. Order Value</p>
                    <p class="text-xl font-bold text-gray-900"><?= formatMoney((float)$stats['avg']) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-heading font-semibold text-base text-gray-900">Recent Orders</h2>
        </div>
        <?php if (empty($orders)): ?>
        <div class="px-6 py-12 text-center text-gray-500 text-sm">No orders yet</div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="text-left px-6 py-3 font-medium text-gray-600">Order #</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-600">Date</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-600">Items</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-600">Total</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-600">Status</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-600">Payment</th>
                        <th class="text-right px-6 py-3 font-medium text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($orders as $o): ?>
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-6 py-3 font-mono text-xs font-medium text-gray-900"><?= e($o['order_number']) ?></td>
                        <td class="px-6 py-3 text-gray-600 text-xs"><?= formatDate($o['created_at']) ?></td>
                        <td class="px-6 py-3 text-gray-600"><?= (int)($o['item_count'] ?? 0) ?></td>
                        <td class="px-6 py-3 font-medium text-gray-900"><?= formatMoney($o['total']) ?></td>
                        <td class="px-6 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $statusColors[$o['status']] ?? 'bg-gray-100 text-gray-700' ?>">
                                <?= ucfirst($o['status'] ?? 'pending') ?>
                            </span>
                        </td>
                        <td class="px-6 py-3">
                            <span class="text-xs font-medium <?= $paymentColors[$o['payment_status'] ?? 'pending'] ?? 'text-amber-600' ?>">
                                <?= ucfirst($o['payment_status'] ?? 'pending') ?>
                            </span>
                        </td>
                        <td class="px-6 py-3 text-right">
                            <a href="/admin/orders/<?= $o['id'] ?>" class="text-amber-600 hover:text-amber-700 text-xs font-medium">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Activity Summary -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <h2 class="font-heading font-semibold text-base text-gray-900 mb-4">Activity Summary</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                <div class="w-9 h-9 bg-amber-100 rounded-lg flex items-center justify-center">
                    <i data-lucide="user-plus" class="w-4 h-4 text-amber-600"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Registered</p>
                    <p class="text-sm font-medium text-gray-900"><?= formatDate($customer['created_at']) ?> at <?= date('h:i A', strtotime($customer['created_at'])) ?></p>
                </div>
            </div>
            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                <div class="w-9 h-9 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i data-lucide="log-in" class="w-4 h-4 text-blue-600"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Last Login</p>
                    <p class="text-sm font-medium text-gray-900"><?= $customer['last_login'] ? formatDate($customer['last_login']) . ' at ' . date('h:i A', strtotime($customer['last_login'])) : 'Never logged in' ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Referral & Commission Info -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-heading font-semibold text-base text-gray-900 flex items-center gap-2">
                <i data-lucide="users-round" class="w-5 h-5 text-amber-600"></i> Referral & Commission
            </h2>
        </div>
        <div class="p-6">
            <?php if (!empty($referredBy)): ?>
            <div class="mb-4 p-4 bg-blue-50 border border-blue-100 rounded-lg">
                <p class="text-xs text-blue-600 font-medium uppercase tracking-wide mb-1">Referred By</p>
                <p class="text-sm font-medium text-gray-900"><?= e($referredBy['referrer_name'] ?? 'N/A') ?></p>
                <p class="text-xs text-gray-500"><?= e($referredBy['referrer_email'] ?? '') ?> · Code: <?= e($referredBy['referral_code'] ?? '') ?></p>
            </div>
            <?php endif; ?>

            <?php if (!empty($referralCode)): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                <div class="p-4 bg-gray-50 rounded-lg">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Referral Code</p>
                    <p class="text-sm font-mono font-bold text-gray-900 mt-1"><?= e($referralCode) ?></p>
                </div>
                <div class="p-4 bg-gray-50 rounded-lg">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Total Referrals</p>
                    <p class="text-xl font-bold text-gray-900 mt-1"><?= (int)($referralStats['total_refs'] ?? 0) ?></p>
                </div>
                <div class="p-4 bg-gray-50 rounded-lg">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Commission Earned</p>
                    <p class="text-xl font-bold text-amber-600 mt-1"><?= formatMoney((float)($referralStats['total_earned'] ?? 0)) ?></p>
                </div>
                <div class="p-4 bg-gray-50 rounded-lg">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Commission Paid</p>
                    <p class="text-xl font-bold text-green-600 mt-1"><?= formatMoney((float)($referralStats['paid_out'] ?? 0)) ?></p>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Referral Link</label>
                <div class="flex items-center gap-2">
                    <input type="text" value="<?= e($referralLink) ?>" readonly class="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm font-mono bg-gray-50">
                    <button onclick="navigator.clipboard.writeText('<?= e(addslashes($referralLink)) ?>');this.textContent='Copied!';setTimeout(()=>this.textContent='Copy',2000)" class="px-4 py-2 bg-amber-50 text-amber-700 rounded-lg text-sm font-medium hover:bg-amber-100 transition-colors shrink-0">Copy</button>
                </div>
            </div>
            <?php else: ?>
            <p class="text-sm text-gray-500">No referral code generated for this customer.</p>
            <?php endif; ?>
        </div>
    </div>
</div>