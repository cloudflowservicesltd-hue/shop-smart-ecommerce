<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <span>Marketing</span>
                <i data-lucide="chevron-right" class="w-4 h-4"></i>
                <span class="text-gray-900 font-medium">Affiliates</span>
            </div>
            <h1 class="font-heading text-2xl font-bold text-gray-900">Referral &amp; Affiliate System</h1>
        </div>
    </div>

    <!-- Settings Card -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <h3 class="font-semibold text-gray-900 mb-5 flex items-center gap-2">
            <i data-lucide="settings" class="w-5 h-5 text-amber-600"></i>
            Referral Settings
        </h3>
        <form method="POST" action="/admin/referrals/settings">
            <?= csrf() ?>
            <div class="flex flex-col sm:flex-row sm:items-end gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Commission Rate (%)</label>
                    <div class="flex items-center gap-2">
                        <input type="number" name="referral_commission_rate"
                            value="<?= htmlspecialchars($commissionRate) ?>"
                            min="0" max="100" step="0.01"
                            class="w-28 px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                        <span class="text-sm text-gray-500 font-medium">%</span>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <label class="text-sm font-medium text-gray-700">Enable Referral System</label>
                    <select name="referral_enabled" class="px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500">
                        <option value="1" <?= $referralEnabled === '1' ? 'selected' : '' ?>>Enabled</option>
                        <option value="0" <?= $referralEnabled !== '1' ? 'selected' : '' ?>>Disabled</option>
                    </select>
                </div>
                <button type="submit" class="inline-flex items-center gap-2 bg-amber-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors shadow-sm">
                    <i data-lucide="save" class="w-4 h-4"></i> Save Settings
                </button>
            </div>
        </form>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="users-round" class="w-6 h-6 text-amber-600"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Total Referrals</p>
                    <p class="text-2xl font-bold text-gray-900 mt-0.5"><?= (int)$totalCompleted ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="wallet" class="w-6 h-6 text-green-600"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Total Commission</p>
                    <p class="text-2xl font-bold text-gray-900 mt-0.5"><?= formatMoney($totalCommission) ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="clock" class="w-6 h-6 text-yellow-600"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Pending Payouts</p>
                    <p class="text-2xl font-bold text-gray-900 mt-0.5"><?= (int)$pendingPayouts ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="trophy" class="w-6 h-6 text-blue-600"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Active Referrers</p>
                    <p class="text-2xl font-bold text-gray-900 mt-0.5"><?= (int)$activeReferrers ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Referrals Table -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h3 class="font-semibold text-gray-900">All Referrals</h3>
            <div class="flex items-center gap-3">
                <div class="inline-flex bg-gray-100 rounded-lg p-0.5 text-xs font-medium">
                    <?php foreach (['all' => 'All', 'pending' => 'Pending', 'completed' => 'Completed', 'paid' => 'Paid'] as $val => $label): ?>
                    <a href="/admin/referrals?status=<?= $val ?>" class="px-3 py-1.5 rounded-md <?= ($statusFilter === $val) ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700' ?> transition-colors"><?= $label ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <?php if (empty($referrals)): ?>
        <div class="p-12 text-center">
            <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-2xl flex items-center justify-center">
                <i data-lucide="users-round" class="w-8 h-8 text-gray-300"></i>
            </div>
            <p class="text-gray-500 text-sm">No referrals recorded yet.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left">
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Referrer</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Referred Customer</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Referral Code</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Order</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Order Total</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Commission</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($referrals as $r): ?>
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center shrink-0">
                                    <i data-lucide="user" class="w-4 h-4 text-amber-600"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900"><?= e($r['referrer_name'] ?? 'Unknown') ?></p>
                                    <p class="text-xs text-gray-400"><?= e($r['referrer_email'] ?? '') ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <?php if (!empty($r['referred_name'])): ?>
                            <p class="font-medium text-gray-900"><?= e($r['referred_name']) ?></p>
                            <p class="text-xs text-gray-400"><?= e($r['referred_email'] ?? '') ?></p>
                            <?php else: ?>
                            <span class="text-gray-400 text-xs">No referral yet</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <code class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded font-mono"><?= e($r['referral_code']) ?></code>
                        </td>
                        <td class="px-4 py-3">
                            <?php if (!empty($r['order_number'])): ?>
                            <a href="/admin/orders/<?= (int)$r['order_id'] ?>" class="font-mono text-xs text-amber-600 hover:text-amber-700 font-medium"><?= e($r['order_number']) ?></a>
                            <?php else: ?>
                            <span class="text-gray-400 text-xs">&mdash;</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right font-medium text-gray-900"><?= $r['order_total'] > 0 ? formatMoney($r['order_total']) : '<span class="text-gray-400">&mdash;</span>' ?></td>
                        <td class="px-4 py-3 text-right font-bold text-green-700"><?= $r['commission_amount'] > 0 ? formatMoney($r['commission_amount']) : '<span class="text-gray-400">&mdash;</span>' ?></td>
                        <td class="px-4 py-3">
                            <?php if ($r['status'] === 'pending'): ?>
                            <span class="inline-flex items-center gap-1 text-xs bg-yellow-100 text-yellow-700 px-2.5 py-1 rounded-full font-medium">
                                <i data-lucide="clock" class="w-3 h-3"></i> Pending
                            </span>
                            <?php elseif ($r['status'] === 'completed'): ?>
                            <span class="inline-flex items-center gap-1 text-xs bg-blue-100 text-blue-700 px-2.5 py-1 rounded-full font-medium">
                                <i data-lucide="check" class="w-3 h-3"></i> Completed
                            </span>
                            <?php elseif ($r['status'] === 'paid'): ?>
                            <span class="inline-flex items-center gap-1 text-xs bg-green-100 text-green-700 px-2.5 py-1 rounded-full font-medium">
                                <i data-lucide="banknote" class="w-3 h-3"></i> Paid
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500">
                            <?= date('M j, Y', strtotime($r['created_at'])) ?>
                            <?php if ($r['completed_at']): ?>
                            <span class="block text-green-600">Completed: <?= date('M j, Y', strtotime($r['completed_at'])) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <?php if ($r['status'] === 'completed'): ?>
                            <form method="POST" action="/admin/referrals/<?= (int)$r['id'] ?>/pay" onsubmit="return confirm('Mark this commission as paid?')">
                                <?= csrf() ?>
                                <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-green-700 bg-green-50 hover:bg-green-100 rounded-lg transition-colors" title="Mark as Paid">
                                    <i data-lucide="banknote" class="w-3 h-3"></i> Mark Paid
                                </button>
                            </form>
                            <?php elseif ($r['status'] === 'paid'): ?>
                            <span class="text-xs text-gray-400 italic">Done</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Pagination -->
        <?php if (($pagination['total'] ?? 1) > 1): ?>
        <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
            <p class="text-xs text-gray-500">Showing <?= (($pagination['current'] - 1) * $pagination['per_page'] + 1) ?>-<?= min($pagination['current'] * $pagination['per_page'], $pagination['total_items']) ?> of <?= (int)$pagination['total_items'] ?></p>
            <div class="flex items-center gap-1">
                <?php if ($pagination['current'] > 1): ?>
                <a href="/admin/referrals?status=<?= urlencode($statusFilter) ?>&page=<?= $pagination['current'] - 1 ?>" class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">Prev</a>
                <?php endif; ?>
                <?php for ($p = max(1, $pagination['current'] - 2); $p <= min($pagination['total'], $pagination['current'] + 2); $p++): ?>
                <a href="/admin/referrals?status=<?= urlencode($statusFilter) ?>&page=<?= $p ?>" class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors <?= $p === $pagination['current'] ? 'bg-amber-600 text-white' : 'text-gray-600 bg-gray-100 hover:bg-gray-200' ?>"><?= $p ?></a>
                <?php endfor; ?>
                <?php if ($pagination['current'] < $pagination['total']): ?>
                <a href="/admin/referrals?status=<?= urlencode($statusFilter) ?>&page=<?= $pagination['current'] + 1 ?>" class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">Next</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Top Referrers -->
    <?php if (!empty($topReferrers)): ?>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                <i data-lucide="trophy" class="w-5 h-5 text-amber-600"></i>
                Top Referrers &amp; Referral Links
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left">
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Referrer</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Referral Link</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-center">Referrals</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Total Earned</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($topReferrers as $tr): ?>
                    <?php $refLink = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/?ref=' . urlencode($tr['referral_code']); ?>
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center shrink-0">
                                    <i data-lucide="user" class="w-4 h-4 text-amber-600"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900"><?= e($tr['name']) ?></p>
                                    <p class="text-xs text-gray-400"><?= e($tr['email'] ?? '') ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <code class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded font-mono max-w-xs truncate inline-block" title="<?= e($refLink) ?>" id="refLink<?= (int)$tr['referrer_id'] ?>"><?= e($refLink) ?></code>
                        </td>
                        <td class="px-4 py-3 text-center font-medium text-gray-900"><?= (int)$tr['total_refs'] ?></td>
                        <td class="px-4 py-3 text-right font-bold text-green-700"><?= formatMoney($tr['total_earned']) ?></td>
                        <td class="px-4 py-3 text-right">
                            <button onclick="copyRefLink('refLink<?= (int)$tr['referrer_id'] ?>')" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors" title="Copy Link">
                                <i data-lucide="copy" class="w-3 h-3"></i> Copy
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function copyRefLink(elementId) {
    const el = document.getElementById(elementId);
    if (!el) return;
    const text = el.textContent.trim();
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => showToast('Referral link copied!', 'success'));
    } else {
        const ta = document.createElement('textarea');
        ta.value = text;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        showToast('Referral link copied!', 'success');
    }
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