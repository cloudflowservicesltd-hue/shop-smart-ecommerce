<?php $editingCoupon = null; ?>
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <span>Marketing</span>
                <i data-lucide="chevron-right" class="w-4 h-4"></i>
                <span class="text-gray-900 font-medium">Coupons</span>
            </div>
            <h1 class="font-heading text-2xl font-bold text-gray-900">Discount Coupons</h1>
        </div>
        <button onclick="toggleCouponForm()" id="toggleFormBtn" class="inline-flex items-center gap-2 bg-amber-600 text-white px-5 py-2.5 rounded-xl text-sm font-medium hover:bg-amber-700 transition-colors shadow-sm">
            <i data-lucide="plus" class="w-4 h-4"></i> Create Coupon
        </button>
    </div>

    <!-- Create/Edit Form (hidden by default) -->
    <div id="couponFormWrapper" class="hidden">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 mb-5 flex items-center gap-2">
                <i data-lucide="ticket" class="w-5 h-5 text-amber-600"></i>
                <span id="formTitle">Create New Coupon</span>
            </h3>
            <form id="couponForm" method="POST" action="/admin/coupons/store">
                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    <!-- Code -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Coupon Code <span class="text-red-500">*</span></label>
                        <input type="text" name="code" id="couponCode" required placeholder="e.g. SUMMER20" maxlength="30"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 uppercase">
                        <p class="text-xs text-gray-400 mt-1">Letters and numbers only, auto-uppercased</p>
                    </div>
                    <!-- Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Discount Type <span class="text-red-500">*</span></label>
                        <select name="type" id="couponType" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 bg-white">
                            <option value="percentage">Percentage (%)</option>
                            <option value="fixed">Fixed Amount (KSh)</option>
                        </select>
                    </div>
                    <!-- Value -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Discount Value <span class="text-red-500">*</span></label>
                        <input type="number" name="value" id="couponValue" required min="0.01" step="0.01" placeholder="e.g. 20"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                        <p id="valueHint" class="text-xs text-gray-400 mt-1">Enter percentage (e.g. 20 for 20%)</p>
                    </div>
                    <!-- Min Order -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Min Order Amount</label>
                        <input type="number" name="min_order_amount" min="0" step="0.01" value="0" placeholder="0"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                        <p class="text-xs text-gray-400 mt-1">Minimum subtotal required to use coupon</p>
                    </div>
                    <!-- Max Discount -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Max Discount Amount</label>
                        <input type="number" name="max_discount_amount" min="0" step="0.01" value="0" placeholder="0"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                        <p class="text-xs text-gray-400 mt-1">Cap for percentage coupons (0 = no limit)</p>
                    </div>
                    <!-- Usage Limit -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Usage Limit</label>
                        <input type="number" name="usage_limit" min="0" value="0" placeholder="0"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                        <p class="text-xs text-gray-400 mt-1">Total times coupon can be used (0 = unlimited)</p>
                    </div>
                    <!-- Valid From -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Valid From</label>
                        <input type="date" name="valid_from"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                    </div>
                    <!-- Valid Until -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Valid Until</label>
                        <input type="date" name="valid_until"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                    </div>
                    <!-- Active -->
                    <div class="flex items-end pb-1">
                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" checked class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500">
                            <span class="text-sm text-gray-700 font-medium">Active</span>
                        </label>
                    </div>
                </div>
                <div class="flex items-center gap-3 mt-6">
                    <button type="submit" class="inline-flex items-center gap-2 bg-amber-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors shadow-sm">
                        <i data-lucide="save" class="w-4 h-4"></i> <span id="submitBtnText">Create Coupon</span>
                    </button>
                    <button type="button" onclick="cancelEdit()" class="inline-flex items-center gap-2 border border-gray-200 text-gray-600 px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                    <i data-lucide="ticket" class="w-5 h-5 text-amber-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900"><?= count($coupons ?? []) ?></p>
                    <p class="text-xs text-gray-500">Total Coupons</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <i data-lucide="check-circle" class="w-5 h-5 text-green-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900"><?= count(array_filter($coupons ?? [], fn($c) => $c['is_active'])) ?></p>
                    <p class="text-xs text-gray-500">Active</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i data-lucide="users" class="w-5 h-5 text-blue-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900"><?= array_sum(array_column($coupons ?? [], 'used_count')) ?></p>
                    <p class="text-xs text-gray-500">Total Uses</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i data-lucide="percent" class="w-5 h-5 text-purple-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900"><?= count(array_filter($coupons ?? [], fn($c) => $c['type'] === 'percentage')) ?></p>
                    <p class="text-xs text-gray-500">Percentage Type</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Coupons Table -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">All Coupons</h3>
        </div>
        <?php if (empty($coupons ?? [])): ?>
        <div class="p-12 text-center">
            <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-2xl flex items-center justify-center">
                <i data-lucide="ticket" class="w-8 h-8 text-gray-300"></i>
            </div>
            <p class="text-gray-500 text-sm">No coupons yet. Create your first discount coupon.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left">
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Code</th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Discount</th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Min Order</th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Usage</th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Validity</th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($coupons as $coupon): 
                        $now = date('Y-m-d H:i:s');
                        $isExpired = ($coupon['valid_until'] && $now > $coupon['valid_until']);
                        $isNotStarted = ($coupon['valid_from'] && $now < $coupon['valid_from']);
                        $isUsedUp = ($coupon['usage_limit'] > 0 && $coupon['used_count'] >= $coupon['usage_limit']);
                    ?>
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center shrink-0">
                                    <i data-lucide="ticket" class="w-4 h-4 text-amber-600"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 font-mono"><?= e($coupon['code']) ?></p>
                                    <p class="text-xs text-gray-400"><?= $coupon['type'] === 'percentage' ? 'Percentage' : 'Fixed Amount' ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($coupon['type'] === 'percentage'): ?>
                            <span class="font-bold text-gray-900"><?= $coupon['value'] ?>%</span>
                            <?php if ($coupon['max_discount_amount'] > 0): ?>
                            <span class="text-xs text-gray-400 block">max <?= formatMoney($coupon['max_discount_amount']) ?></span>
                            <?php endif; ?>
                            <?php else: ?>
                            <span class="font-bold text-gray-900"><?= formatMoney($coupon['value']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-gray-600">
                            <?= $coupon['min_order_amount'] > 0 ? formatMoney($coupon['min_order_amount']) : '<span class="text-gray-400">None</span>' ?>
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-medium text-gray-900"><?= $coupon['used_count'] ?></span>
                            <?php if ($coupon['usage_limit'] > 0): ?>
                            <span class="text-gray-400">/ <?= $coupon['usage_limit'] ?></span>
                            <?php else: ?>
                            <span class="text-gray-400">/ &infin;</span>
                            <?php endif; ?>
                            <?php if ($coupon['usage_limit'] > 0): ?>
                            <div class="w-24 h-1.5 bg-gray-100 rounded-full mt-1.5">
                                <div class="h-full bg-amber-500 rounded-full transition-all" style="width: <?= min(100, ($coupon['used_count'] / $coupon['usage_limit']) * 100) ?>%"></div>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($coupon['valid_from'] || $coupon['valid_until']): ?>
                            <p class="text-xs text-gray-600">
                                <?= $coupon['valid_from'] ? date('M j, Y', strtotime($coupon['valid_from'])) : '—' ?>
                                <i data-lucide="arrow-right" class="w-3 h-3 inline text-gray-300"></i>
                                <?= $coupon['valid_until'] ? date('M j, Y', strtotime($coupon['valid_until'])) : '—' ?>
                            </p>
                            <?php if ($isExpired): ?>
                            <span class="inline-block mt-1 text-xs bg-red-50 text-red-600 px-2 py-0.5 rounded-full font-medium">Expired</span>
                            <?php elseif ($isNotStarted): ?>
                            <span class="inline-block mt-1 text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full font-medium">Scheduled</span>
                            <?php endif; ?>
                            <?php else: ?>
                            <span class="text-xs text-gray-400">No expiry</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($isExpired || $isUsedUp): ?>
                            <span class="inline-flex items-center gap-1 text-xs bg-red-50 text-red-600 px-2.5 py-1 rounded-full font-medium">
                                <i data-lucide="x-circle" class="w-3 h-3"></i> Inactive
                            </span>
                            <?php elseif (!$coupon['is_active']): ?>
                            <span class="inline-flex items-center gap-1 text-xs bg-gray-100 text-gray-500 px-2.5 py-1 rounded-full font-medium cursor-pointer" onclick="toggleCouponStatus(<?= $coupon['id'] ?>)">
                                <i data-lucide="toggle-left" class="w-3 h-3"></i> Disabled
                            </span>
                            <?php else: ?>
                            <span class="inline-flex items-center gap-1 text-xs bg-green-50 text-green-700 px-2.5 py-1 rounded-full font-medium cursor-pointer" onclick="toggleCouponStatus(<?= $coupon['id'] ?>)">
                                <i data-lucide="toggle-right" class="w-3 h-3"></i> Active
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button onclick="editCoupon(<?= htmlspecialchars(json_encode($coupon), ENT_QUOTES, 'UTF-8') ?>)" class="p-2 text-gray-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" title="Edit">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </button>
                                <form action="/admin/coupons/<?= $coupon['id'] ?>/delete" method="POST" onsubmit="return confirm('Delete coupon <?= e($coupon['code']) ?>?')">
                                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                    <button type="submit" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
lucide.createIcons();

function toggleCouponForm() {
    const w = document.getElementById('couponFormWrapper');
    const btn = document.getElementById('toggleFormBtn');
    w.classList.toggle('hidden');
    if (!w.classList.contains('hidden')) {
        btn.innerHTML = '<i data-lucide="x" class="w-4 h-4"></i> Cancel';
        lucide.createIcons();
    } else {
        btn.innerHTML = '<i data-lucide="plus" class="w-4 h-4"></i> Create Coupon';
        lucide.createIcons();
        cancelEdit();
    }
}

function cancelEdit() {
    document.getElementById('couponForm').action = '/admin/coupons/store';
    document.getElementById('couponForm').reset();
    document.getElementById('couponCode').value = '';
    document.getElementById('couponCode').readOnly = false;
    document.getElementById('formTitle').textContent = 'Create New Coupon';
    document.getElementById('submitBtnText').textContent = 'Create Coupon';
    document.getElementById('couponFormWrapper').classList.add('hidden');
    const btn = document.getElementById('toggleFormBtn');
    btn.innerHTML = '<i data-lucide="plus" class="w-4 h-4"></i> Create Coupon';
    lucide.createIcons();
}

function editCoupon(c) {
    const w = document.getElementById('couponFormWrapper');
    w.classList.remove('hidden');
    const btn = document.getElementById('toggleFormBtn');
    btn.innerHTML = '<i data-lucide="x" class="w-4 h-4"></i> Cancel';
    lucide.createIcons();

    document.getElementById('couponForm').action = '/admin/coupons/' + c.id + '/update';
    document.getElementById('couponCode').value = c.code;
    document.getElementById('couponCode').readOnly = true;
    document.getElementById('couponType').value = c.type;
    document.getElementById('couponValue').value = c.value;
    document.querySelector('input[name="min_order_amount"]').value = c.min_order_amount || 0;
    document.querySelector('input[name="max_discount_amount"]').value = c.max_discount_amount || 0;
    document.querySelector('input[name="usage_limit"]').value = c.usage_limit || 0;
    document.querySelector('input[name="valid_from"]').value = c.valid_from ? c.valid_from.substring(0, 10) : '';
    document.querySelector('input[name="valid_until"]').value = c.valid_until ? c.valid_until.substring(0, 10) : '';
    document.querySelector('input[name="is_active"]').checked = c.is_active == 1;
    document.getElementById('formTitle').textContent = 'Edit Coupon: ' + c.code;
    document.getElementById('submitBtnText').textContent = 'Update Coupon';

    updateTypeHint(c.type);
    w.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function updateTypeHint(type) {
    const hint = document.getElementById('valueHint');
    if (type === 'percentage') {
        hint.textContent = 'Enter percentage (e.g. 20 for 20%)';
    } else {
        hint.textContent = 'Enter amount in KSh (e.g. 500)';
    }
}

document.getElementById('couponType').addEventListener('change', function() {
    updateTypeHint(this.value);
});

function toggleCouponStatus(id) {
    const fd = new FormData();
    fd.append('_token', '<?= csrf_token() ?>');
    fetch('/admin/coupons/' + id + '/toggle', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(() => location.reload())
    .catch(() => alert('Failed to toggle coupon status'));
}
</script>