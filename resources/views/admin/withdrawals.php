<?php
$breadcrumbs = $breadcrumbs ?? [['Withdrawals', '']];
$withdrawals = $withdrawals ?? [];
$stats = $stats ?? ['total' => 0, 'pending' => 0, 'approved' => 0, 'totalAmount' => 0];
$statusBadge = [
    'pending' => 'bg-yellow-100 text-yellow-700',
    'approved' => 'bg-blue-100 text-blue-700',
    'rejected' => 'bg-red-100 text-red-700',
    'paid' => 'bg-green-100 text-green-700',
];
?>
<div class="space-y-6">
    <!-- Breadcrumbs -->
    <?php if (!empty($breadcrumbs)): ?>
    <nav class="flex items-center gap-2 text-sm text-gray-500">
        <a href="/admin" class="hover:text-amber-600 transition-colors">Dashboard</a>
        <?php foreach ($breadcrumbs as $i => $crumb): ?>
        <i data-lucide="chevron-right" class="w-4 h-4"></i>
        <?php if ($i < count($breadcrumbs) - 1): ?>
        <a href="<?= e($crumb[1]) ?>" class="hover:text-amber-600 transition-colors"><?= e($crumb[0]) ?></a>
        <?php else: ?>
        <span class="text-gray-900 font-medium"><?= e($crumb[0]) ?></span>
        <?php endif; ?>
        <?php endforeach; ?>
    </nav>
    <?php endif; ?>

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="font-heading text-xl font-bold text-gray-900">Withdrawal Requests</h1>
            <p class="text-sm text-gray-500 mt-1">Manage referral commission withdrawal requests</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="/admin/referrals" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                <i data-lucide="users-round" class="w-4 h-4"></i> Back to Affiliates
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Requests</p>
            <p class="text-2xl font-bold text-gray-900 mt-1"><?= number_format((int)$stats['total']) ?></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Pending</p>
            <p class="text-2xl font-bold text-yellow-600 mt-1"><?= number_format((int)$stats['pending']) ?></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Approved</p>
            <p class="text-2xl font-bold text-blue-600 mt-1"><?= number_format((int)$stats['approved']) ?></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Pending Amount</p>
            <p class="text-2xl font-bold text-amber-600 mt-1"><?= formatMoney((float)$stats['totalAmount']) ?></p>
        </div>
    </div>

    <!-- Status Filter -->
    <div class="flex items-center gap-2 flex-wrap">
        <span class="text-sm font-medium text-gray-500">Filter:</span>
        <?php foreach (['all', 'pending', 'approved', 'rejected', 'paid'] as $st): ?>
        <a href="/admin/referrals/withdrawals?status=<?= $st ?>" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors <?= (Request::query('status', 'all') === $st) ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">
            <?= ucfirst($st) ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Withdrawals Table -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <?php if (!empty($withdrawals)): ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="text-left px-5 py-3 font-medium text-gray-600">ID</th>
                        <th class="text-left px-5 py-3 font-medium text-gray-600">User</th>
                        <th class="text-left px-5 py-3 font-medium text-gray-600">Amount</th>
                        <th class="text-left px-5 py-3 font-medium text-gray-600">Payment Details</th>
                        <th class="text-left px-5 py-3 font-medium text-gray-600">Status</th>
                        <th class="text-left px-5 py-3 font-medium text-gray-600">Date</th>
                        <th class="text-right px-5 py-3 font-medium text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($withdrawals as $w): ?>
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-5 py-3 font-mono text-xs text-gray-500">#<?= $w['id'] ?></td>
                        <td class="px-5 py-3">
                            <div class="font-medium text-gray-900"><?= e($w['user_name'] ?? 'N/A') ?></div>
                            <div class="text-xs text-gray-400"><?= e($w['user_email'] ?? '') ?></div>
                        </td>
                        <td class="px-5 py-3 font-bold text-gray-900"><?= formatMoney($w['amount']) ?></td>
                        <td class="px-5 py-3 text-xs text-gray-600 max-w-[200px] truncate"><?= e($w['payment_details'] ?? 'N/A') ?></td>
                        <td class="px-5 py-3">
                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold <?= $statusBadge[$w['status']] ?? 'bg-gray-100 text-gray-700' ?>">
                                <?= ucfirst($w['status']) ?>
                            </span>
                            <?php if (!empty($w['admin_notes'])): ?>
                            <div class="text-xs text-red-500 mt-1"><?= e($w['admin_notes']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-3 text-xs text-gray-500"><?= formatDate($w['created_at']) ?></td>
                        <td class="px-5 py-3 text-right">
                            <?php if ($w['status'] === 'pending'): ?>
                            <form action="/admin/referrals/withdrawals/<?= $w['id'] ?>/approve" method="POST" class="inline" onsubmit="return confirm('Approve this withdrawal of <?= formatMoney($w['amount']) ?>?')">
                                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                <button type="submit" class="px-3 py-1.5 bg-green-50 text-green-700 rounded-lg text-xs font-medium hover:bg-green-100 transition-colors">Approve</button>
                            </form>
                            <button onclick="showRejectModal(<?= $w['id'] ?>)" class="ml-1 px-3 py-1.5 bg-red-50 text-red-700 rounded-lg text-xs font-medium hover:bg-red-100 transition-colors">Reject</button>
                            <?php elseif ($w['status'] === 'approved'): ?>
                            <form action="/admin/referrals/withdrawals/<?= $w['id'] ?>/approve" method="POST" class="inline" onsubmit="return confirm('Mark this withdrawal as paid?')">
                                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                <button type="submit" class="px-3 py-1.5 bg-amber-50 text-amber-700 rounded-lg text-xs font-medium hover:bg-amber-100 transition-colors">Mark Paid</button>
                            </form>
                            <?php else: ?>
                            <span class="text-xs text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-16">
            <i data-lucide="inbox" class="w-12 h-12 text-gray-200 mx-auto mb-3"></i>
            <p class="text-gray-500">No withdrawal requests found</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="fixed inset-0 z-[9999] bg-black/50 flex items-center justify-center hidden">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 p-6">
        <h3 class="font-semibold text-gray-900 mb-4">Reject Withdrawal</h3>
        <form id="rejectForm" method="POST" action="">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason (optional)</label>
                <textarea name="admin_notes" rows="3" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500" placeholder="Enter reason for rejection..."></textarea>
            </div>
            <div class="flex items-center justify-end gap-3">
                <button type="button" onclick="document.getElementById('rejectModal').classList.add('hidden')" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900 transition-colors">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">Reject</button>
            </div>
        </form>
    </div>
</div>

<script>
function showRejectModal(id) {
    document.getElementById('rejectForm').action = '/admin/referrals/withdrawals/' + id + '/reject';
    document.getElementById('rejectModal').classList.remove('hidden');
}
document.getElementById('rejectModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.add('hidden');
});
</script>