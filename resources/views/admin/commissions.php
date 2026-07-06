<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <span>Marketing</span>
                <i data-lucide="chevron-right" class="w-4 h-4"></i>
                <span class="text-gray-900 font-medium">Commissions</span>
            </div>
            <h1 class="font-heading text-2xl font-bold text-gray-900">Commission Management</h1>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="wallet" class="w-6 h-6 text-amber-600"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Total Earned</p>
                    <p class="text-2xl font-bold text-gray-900 mt-0.5"><?= formatMoney($totalEarned) ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="clock" class="w-6 h-6 text-yellow-600"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Pending</p>
                    <p class="text-2xl font-bold text-gray-900 mt-0.5"><?= formatMoney($totalPending) ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="check-circle" class="w-6 h-6 text-green-600"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Paid</p>
                    <p class="text-2xl font-bold text-gray-900 mt-0.5"><?= formatMoney($totalPaid) ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="percent" class="w-6 h-6 text-blue-600"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Commission Rate</p>
                    <p class="text-2xl font-bold text-gray-900 mt-0.5"><?= htmlspecialchars($commissionRate) ?>%</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Commission Settings Card -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-semibold text-gray-900 mb-5 flex items-center gap-2">
            <i data-lucide="settings" class="w-5 h-5 text-amber-600"></i>
            Commission Settings
        </h3>
        <form id="commissionSettingsForm" onsubmit="event.preventDefault(); saveCommissionSettings();">
            <div class="flex flex-col sm:flex-row sm:items-end gap-5">
                <div class="flex items-center gap-3">
                    <label class="text-sm font-medium text-gray-700">Enable Commissions</label>
                    <button type="button" id="commissionToggle" onclick="toggleCommissionSwitch()"
                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-amber-500/20 <?= $commissionEnabled === '1' ? 'bg-amber-600' : 'bg-gray-300' ?>">
                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow-sm <?= $commissionEnabled === '1' ? 'translate-x-6' : 'translate-x-1' ?>"></span>
                    </button>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Commission Percentage</label>
                    <div class="flex items-center gap-2">
                        <input type="number" name="commission_rate" id="commissionRateInput"
                            value="<?= htmlspecialchars($commissionRate) ?>"
                            min="0" max="100" step="0.01"
                            class="w-28 px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                        <span class="text-sm text-gray-500 font-medium">%</span>
                    </div>
                </div>
                <button type="submit" class="inline-flex items-center gap-2 bg-amber-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors shadow-sm">
                    <i data-lucide="save" class="w-4 h-4"></i> Save Settings
                </button>
            </div>
            <input type="hidden" name="commission_enabled" id="commissionEnabledInput" value="<?= htmlspecialchars($commissionEnabled) ?>">
        </form>
    </div>

    <!-- Commission Table -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h3 class="font-semibold text-gray-900">All Commissions</h3>
            <div class="flex items-center gap-3">
                <!-- Filter Tabs -->
                <div class="inline-flex bg-gray-100 rounded-lg p-0.5 text-xs font-medium">
                    <button onclick="filterCommissions('all')" class="commission-filter-btn px-3 py-1.5 rounded-md bg-white text-gray-900 shadow-sm" data-status="all">All</button>
                    <button onclick="filterCommissions('pending')" class="commission-filter-btn px-3 py-1.5 rounded-md text-gray-500 hover:text-gray-700" data-status="pending">Pending</button>
                    <button onclick="filterCommissions('approved')" class="commission-filter-btn px-3 py-1.5 rounded-md text-gray-500 hover:text-gray-700" data-status="approved">Approved</button>
                    <button onclick="filterCommissions('paid')" class="commission-filter-btn px-3 py-1.5 rounded-md text-gray-500 hover:text-gray-700" data-status="paid">Paid</button>
                </div>
                <!-- Bulk Pay -->
                <button onclick="paySelected()" class="inline-flex items-center gap-1.5 bg-green-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-green-700 transition-colors shadow-sm">
                    <i data-lucide="banknote" class="w-3.5 h-3.5"></i> Pay Selected
                </button>
            </div>
        </div>

        <?php if (empty($commissions)): ?>
        <div class="p-12 text-center">
            <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-2xl flex items-center justify-center">
                <i data-lucide="coins" class="w-8 h-8 text-gray-300"></i>
            </div>
            <p class="text-gray-500 text-sm">No commissions recorded yet.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left">
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider w-10">
                            <input type="checkbox" id="selectAllCommissions" onchange="toggleSelectAll(this)" class="w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500">
                        </th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Cashier Name</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Order #</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Order Total</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Rate</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Commission</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100" id="commissionsTableBody">
                    <?php foreach ($commissions as $c): ?>
                    <tr class="hover:bg-gray-50/50 transition-colors commission-row" data-status="<?= htmlspecialchars($c['status']) ?>">
                        <td class="px-4 py-3">
                            <input type="checkbox" class="commission-checkbox w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500" value="<?= (int)$c['id'] ?>">
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center shrink-0">
                                    <i data-lucide="user" class="w-4 h-4 text-amber-600"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900"><?= e($c['cashier_name']) ?></p>
                                    <p class="text-xs text-gray-400"><?= e($c['email'] ?? '') ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <a href="/admin/orders/<?= (int)$c['order_id'] ?>" class="font-mono text-xs text-amber-600 hover:text-amber-700 font-medium"><?= e($c['order_number']) ?></a>
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-900"><?= formatMoney($c['order_total']) ?></td>
                        <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars($c['percentage']) ?>%</td>
                        <td class="px-4 py-3 font-bold text-gray-900"><?= formatMoney($c['amount']) ?></td>
                        <td class="px-4 py-3">
                            <?php if ($c['status'] === 'pending'): ?>
                            <span class="inline-flex items-center gap-1 text-xs bg-amber-100 text-amber-700 px-2.5 py-1 rounded-full font-medium">
                                <i data-lucide="clock" class="w-3 h-3"></i> Pending
                            </span>
                            <?php elseif ($c['status'] === 'approved'): ?>
                            <span class="inline-flex items-center gap-1 text-xs bg-blue-100 text-blue-700 px-2.5 py-1 rounded-full font-medium">
                                <i data-lucide="check" class="w-3 h-3"></i> Approved
                            </span>
                            <?php elseif ($c['status'] === 'paid'): ?>
                            <span class="inline-flex items-center gap-1 text-xs bg-green-100 text-green-700 px-2.5 py-1 rounded-full font-medium">
                                <i data-lucide="banknote" class="w-3 h-3"></i> Paid
                            </span>
                            <?php else: ?>
                            <span class="inline-flex items-center gap-1 text-xs bg-gray-100 text-gray-500 px-2.5 py-1 rounded-full font-medium">
                                <?= e(ucfirst($c['status'])) ?>
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500">
                            <?= date('M j, Y', strtotime($c['created_at'])) ?>
                            <?php if ($c['paid_at']): ?>
                            <span class="block text-green-600">Paid: <?= date('M j, Y', strtotime($c['paid_at'])) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <?php if ($c['status'] === 'pending'): ?>
                                <button onclick="approveCommission(<?= (int)$c['id'] ?>)" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors" title="Approve">
                                    <i data-lucide="check" class="w-3 h-3"></i> Approve
                                </button>
                                <?php endif; ?>
                                <?php if ($c['status'] === 'pending' || $c['status'] === 'approved'): ?>
                                <button onclick="payCommission(<?= (int)$c['id'] ?>)" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-green-700 bg-green-50 hover:bg-green-100 rounded-lg transition-colors" title="Mark as Paid">
                                    <i data-lucide="banknote" class="w-3 h-3"></i> Pay
                                </button>
                                <?php endif; ?>
                                <?php if ($c['status'] === 'paid'): ?>
                                <span class="text-xs text-gray-400 italic">Completed</span>
                                <?php endif; ?>
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
const commissionsData = <?= json_encode($commissions) ?>;

function toggleCommissionSwitch() {
    const toggle = document.getElementById('commissionToggle');
    const input = document.getElementById('commissionEnabledInput');
    const dot = toggle.querySelector('span');
    const isEnabled = input.value === '1';
    if (isEnabled) {
        input.value = '0';
        toggle.classList.remove('bg-amber-600');
        toggle.classList.add('bg-gray-300');
        dot.classList.remove('translate-x-6');
        dot.classList.add('translate-x-1');
    } else {
        input.value = '1';
        toggle.classList.remove('bg-gray-300');
        toggle.classList.add('bg-amber-600');
        dot.classList.remove('translate-x-1');
        dot.classList.add('translate-x-6');
    }
}

async function saveCommissionSettings() {
    const form = document.getElementById('commissionSettingsForm');
    const formData = new FormData(form);
    try {
        const res = await fetch('/admin/commissions/settings', {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            showToast('Commission settings saved successfully!', 'success');
        } else {
            showToast(data.error || 'Failed to save settings', 'error');
        }
    } catch (err) {
        showToast('Network error. Please try again.', 'error');
    }
}

async function approveCommission(id) {
    if (!confirm('Approve this commission?')) return;
    try {
        const fd = new FormData();
        fd.append('_token', '<?= csrf_token() ?>');
        const res = await fetch('/admin/commissions/' + id + '/approve', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showToast('Commission approved!', 'success');
            setTimeout(() => location.reload(), 600);
        } else {
            showToast(data.error || 'Failed to approve', 'error');
        }
    } catch (err) {
        showToast('Network error. Please try again.', 'error');
    }
}

async function payCommission(id) {
    const commission = commissionsData.find(c => c.id == id);
    const amount = commission ? 'KSh ' + parseFloat(commission.amount).toLocaleString('en-KE', {minimumFractionDigits: 2}) : 'this commission';
    if (!confirm('Mark commission of ' + amount + ' as paid?')) return;
    try {
        const fd = new FormData();
        fd.append('_token', '<?= csrf_token() ?>');
        const res = await fetch('/admin/commissions/' + id + '/pay', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showToast('Commission marked as paid!', 'success');
            setTimeout(() => location.reload(), 600);
        } else {
            showToast(data.error || 'Failed to update', 'error');
        }
    } catch (err) {
        showToast('Network error. Please try again.', 'error');
    }
}

async function paySelected() {
    const checkboxes = document.querySelectorAll('.commission-checkbox:checked');
    if (checkboxes.length === 0) {
        showToast('Please select at least one commission.', 'error');
        return;
    }
    const ids = Array.from(checkboxes).map(cb => parseInt(cb.value));
    if (!confirm('Mark ' + ids.length + ' commission(s) as paid?')) return;
    try {
        const res = await fetch('/admin/commissions/bulk-pay', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ ids: ids, _token: '<?= csrf_token() ?>' })
        });
        const data = await res.json();
        if (data.success) {
            showToast(ids.length + ' commission(s) paid successfully!', 'success');
            setTimeout(() => location.reload(), 600);
        } else {
            showToast(data.error || 'Failed to pay commissions', 'error');
        }
    } catch (err) {
        showToast('Network error. Please try again.', 'error');
    }
}

function filterCommissions(status) {
    const rows = document.querySelectorAll('.commission-row');
    const buttons = document.querySelectorAll('.commission-filter-btn');
    buttons.forEach(btn => {
        if (btn.dataset.status === status) {
            btn.classList.add('bg-white', 'text-gray-900', 'shadow-sm');
            btn.classList.remove('text-gray-500');
        } else {
            btn.classList.remove('bg-white', 'text-gray-900', 'shadow-sm');
            btn.classList.add('text-gray-500');
        }
    });
    rows.forEach(row => {
        if (status === 'all' || row.dataset.status === status) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function toggleSelectAll(master) {
    const checkboxes = document.querySelectorAll('.commission-checkbox');
    checkboxes.forEach(cb => {
        const row = cb.closest('tr');
        if (row && row.style.display !== 'none') {
            cb.checked = master.checked;
        }
    });
}

function toggleKeyVisibility(btn) {
    const input = btn.previousElementSibling;
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    btn.innerHTML = isPassword ? '<i data-lucide="eye" class="w-4 h-4"></i>' : '<i data-lucide="eye-off" class="w-4 h-4"></i>';
    lucide.createIcons();
}

function showToast(msg, type) {
    const t = document.createElement('div');
    t.className = `fixed top-4 right-4 z-50 px-4 py-2 rounded-lg text-sm font-medium shadow-lg ${type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'}`;
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3000);
}

lucide.createIcons();
</script>