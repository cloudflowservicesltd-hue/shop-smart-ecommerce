<?php if (!Auth::check()): ?>
<div class="min-h-[60vh] flex items-center justify-center px-4">
    <div class="text-center">
        <div class="w-20 h-20 mx-auto mb-4 bg-gray-100 rounded-2xl flex items-center justify-center">
            <i data-lucide="user" class="w-10 h-10 text-gray-300"></i>
        </div>
        <h2 class="font-heading text-xl font-bold text-gray-900 mb-2">Please Sign In</h2>
        <p class="text-gray-500 mb-6">Log in to access your referral earnings.</p>
        <a href="/login" class="inline-flex items-center gap-2 bg-amber-600 text-white font-semibold px-8 py-3 rounded-xl hover:bg-amber-700 transition-colors">
            <i data-lucide="log-in" class="w-5 h-5"></i> Sign In
        </a>
    </div>
</div>
<?php else: ?>
<?php $user = Auth::user(); ?>
<!-- Breadcrumbs -->
<div class="bg-gray-50 border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 py-3">
        <nav class="flex items-center gap-2 text-sm text-gray-500">
            <a href="/" class="hover:text-amber-600 transition-colors">Home</a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <a href="/account" class="hover:text-amber-600 transition-colors">My Account</a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <span class="text-gray-900 font-medium">Referral Earnings</span>
        </nav>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Sidebar Navigation -->
        <aside class="lg:w-64 shrink-0">
            <div class="bg-white border border-gray-200 rounded-2xl p-6 sticky top-36">
                <div class="text-center mb-6 pb-6 border-b border-gray-100">
                    <div class="w-16 h-16 mx-auto mb-3 bg-amber-100 rounded-2xl flex items-center justify-center">
                        <span class="text-2xl font-bold text-amber-600"><?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?></span>
                    </div>
                    <h3 class="font-semibold text-gray-900"><?= e($user['name']) ?></h3>
                    <p class="text-sm text-gray-400"><?= e($user['email']) ?></p>
                </div>
                <nav class="space-y-1">
                    <a href="/account" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                        <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Dashboard
                    </a>
                    <a href="/account/orders" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                        <i data-lucide="package" class="w-4 h-4"></i> My Orders
                    </a>
                    <a href="/account/wishlist" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                        <i data-lucide="heart" class="w-4 h-4"></i> Wishlist
                    </a>
                    <a href="/account/reviews" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                        <i data-lucide="star" class="w-4 h-4"></i> My Reviews
                    </a>
                    <a href="/account/referral" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium bg-amber-50 text-amber-700 transition-colors">
                        <i data-lucide="gift" class="w-4 h-4"></i> Referral Earnings
                    </a>
                    <a href="/account/profile" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                        <i data-lucide="user" class="w-4 h-4"></i> Edit Profile
                    </a>
                    <a href="/account/addresses" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                        <i data-lucide="map-pin" class="w-4 h-4"></i> Addresses
                    </a>
                    <hr class="border-gray-100 my-2">
                    <a href="/logout" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-red-600 hover:bg-red-50 transition-colors">
                        <i data-lucide="log-out" class="w-4 h-4"></i> Logout
                    </a>
                </nav>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 min-w-0">
            <h1 class="font-heading text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                <i data-lucide="gift" class="w-6 h-6 text-amber-600"></i> Referral Earnings
            </h1>

            <?php if (!empty($myReferralLink)): ?>
            <!-- Referral Link Section -->
            <div class="bg-white border border-gray-200 rounded-2xl p-6 mb-6">
                <h2 class="font-semibold text-gray-900 flex items-center gap-2 mb-3">
                    <i data-lucide="share-2" class="w-4 h-4 text-amber-600"></i> Your Referral Link
                </h2>
                <p class="text-sm text-gray-500 mb-4">Share this link with friends. When they make a purchase, you earn a commission!</p>
                <div class="flex items-center gap-2">
                    <input type="text" readonly value="<?= e($myReferralLink) ?>" id="myReferralLink"
                        class="flex-1 px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-mono text-gray-700">
                    <button onclick="copyRefLink()" id="copyLinkBtn" class="inline-flex items-center gap-2 bg-amber-600 text-white px-5 py-3 rounded-xl text-sm font-medium hover:bg-amber-700 transition-colors shrink-0">
                        <i data-lucide="copy" class="w-4 h-4"></i> Copy
                    </button>
                </div>
            </div>

            <!-- Earnings Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div class="bg-white border border-gray-200 rounded-2xl p-5 hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-11 h-11 bg-amber-50 rounded-xl flex items-center justify-center">
                            <i data-lucide="users" class="w-5 h-5 text-amber-600"></i>
                        </div>
                        <span class="text-sm text-gray-400">Successful Referrals</span>
                    </div>
                    <p class="text-3xl font-bold text-gray-900"><?= (int)($myReferralStats['total_refs'] ?? 0) ?></p>
                </div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5 hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-11 h-11 bg-green-50 rounded-xl flex items-center justify-center">
                            <i data-lucide="trending-up" class="w-5 h-5 text-green-600"></i>
                        </div>
                        <span class="text-sm text-gray-400">Total Earned</span>
                    </div>
                    <p class="text-3xl font-bold text-green-700"><?= formatMoney($myReferralStats['total_earned'] ?? 0) ?></p>
                </div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5 hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-11 h-11 bg-blue-50 rounded-xl flex items-center justify-center">
                            <i data-lucide="wallet" class="w-5 h-5 text-blue-600"></i>
                        </div>
                        <span class="text-sm text-gray-400">Available Balance</span>
                    </div>
                    <p class="text-3xl font-bold text-blue-700"><?= formatMoney($commissionBalance ?? 0) ?></p>
                </div>
            </div>

            <!-- Withdrawal Request -->
            <?php if (($commissionBalance ?? 0) > 0): ?>
            <div class="bg-white border border-gray-200 rounded-2xl p-6 mb-6">
                <h2 class="font-semibold text-gray-900 flex items-center gap-2 mb-4">
                    <i data-lucide="banknote" class="w-4 h-4 text-amber-600"></i> Request Withdrawal
                </h2>
                <div id="withdrawMsg" class="hidden mb-4 px-4 py-3 rounded-xl text-sm"></div>
                <form id="withdrawForm" class="space-y-4">
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Amount (max: <?= formatMoney($commissionBalance ?? 0) ?>)</label>
                            <input type="number" id="withdrawAmount" name="amount" min="1" max="<?= $commissionBalance ?? 0 ?>" step="0.01" placeholder="0.00" required
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">M-Pesa Phone / Payment Details</label>
                            <input type="text" id="withdrawDetails" name="payment_details" placeholder="e.g. 0712345678" required
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" id="withdrawBtn" class="inline-flex items-center gap-2 bg-amber-600 text-white font-semibold px-8 py-3 rounded-xl hover:bg-amber-700 transition-colors">
                            <i data-lucide="send" class="w-4 h-4"></i> Submit Withdrawal Request
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <!-- Withdrawal History -->
            <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden mb-6">
                <div class="flex items-center justify-between p-6 pb-4">
                    <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                        <i data-lucide="history" class="w-4 h-4 text-amber-600"></i> Withdrawal History
                    </h2>
                </div>
                <?php if (!empty($withdrawals)): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                                <th class="text-left px-6 py-3 font-semibold">Date</th>
                                <th class="text-left px-6 py-3 font-semibold">Amount</th>
                                <th class="text-left px-6 py-3 font-semibold">Payment Details</th>
                                <th class="text-center px-6 py-3 font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($withdrawals as $w): ?>
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 text-gray-500"><?= formatDate($w['created_at']) ?></td>
                                <td class="px-6 py-4 font-medium text-gray-900"><?= formatMoney($w['amount']) ?></td>
                                <td class="px-6 py-4 text-gray-500"><?= e($w['payment_details']) ?></td>
                                <td class="px-6 py-4 text-center">
                                    <?php
                                    $wStatusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-700',
                                        'approved' => 'bg-blue-100 text-blue-700',
                                        'paid' => 'bg-green-100 text-green-700',
                                        'rejected' => 'bg-red-100 text-red-700',
                                    ];
                                    $wColor = $wStatusColors[$w['status']] ?? 'bg-gray-100 text-gray-700';
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold <?= $wColor ?>"><?= ucfirst($w['status']) ?></span>
                                    <?php if ($w['status'] === 'rejected' && !empty($w['admin_notes'])): ?>
                                    <p class="text-xs text-red-500 mt-1" title="<?= e($w['admin_notes']) ?>"><?= e(substr($w['admin_notes'], 0, 40)) ?>...</p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-10 px-6">
                    <i data-lucide="inbox" class="w-10 h-10 text-gray-200 mx-auto mb-3"></i>
                    <p class="text-gray-500">No withdrawal requests yet.</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Referred Users -->
            <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
                <div class="flex items-center justify-between p-6 pb-4">
                    <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                        <i data-lucide="users" class="w-4 h-4 text-amber-600"></i> People You Referred
                    </h2>
                </div>
                <?php if (!empty($referredUsers)): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                                <th class="text-left px-6 py-3 font-semibold">Name</th>
                                <th class="text-left px-6 py-3 font-semibold">Order</th>
                                <th class="text-right px-6 py-3 font-semibold">Commission</th>
                                <th class="text-center px-6 py-3 font-semibold">Status</th>
                                <th class="text-left px-6 py-3 font-semibold">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($referredUsers as $ref): ?>
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <p class="font-medium text-gray-900"><?= e($ref['referred_name'] ?? 'N/A') ?></p>
                                    <p class="text-xs text-gray-400"><?= e($ref['referred_email'] ?? '') ?></p>
                                </td>
                                <td class="px-6 py-4 font-mono text-gray-500"><?= e($ref['order_number'] ?? 'N/A') ?></td>
                                <td class="px-6 py-4 text-right font-medium text-green-700"><?= formatMoney($ref['commission_amount'] ?? 0) ?></td>
                                <td class="px-6 py-4 text-center">
                                    <?php
                                    $rStatusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-700',
                                        'paid' => 'bg-green-100 text-green-700',
                                    ];
                                    $rColor = $rStatusColors[$ref['status']] ?? 'bg-gray-100 text-gray-700';
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold <?= $rColor ?>"><?= ucfirst($ref['status'] ?? 'pending') ?></span>
                                </td>
                                <td class="px-6 py-4 text-gray-500"><?= formatDate($ref['created_at']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-10 px-6">
                    <i data-lucide="user-plus" class="w-10 h-10 text-gray-200 mx-auto mb-3"></i>
                    <p class="text-gray-500 mb-2">No referrals yet.</p>
                    <p class="text-sm text-gray-400">Share your referral link above to start earning!</p>
                </div>
                <?php endif; ?>
            </div>

            <?php else: ?>
            <!-- No referral code assigned -->
            <div class="bg-white border border-gray-200 rounded-2xl p-10 text-center">
                <div class="w-16 h-16 bg-amber-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="gift" class="w-8 h-8 text-amber-400"></i>
                </div>
                <h2 class="font-heading text-xl font-bold text-gray-900 mb-2">Referral Program</h2>
                <p class="text-gray-500 max-w-md mx-auto">Your referral code is being set up. Check back soon to start sharing your link and earning commissions!</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function copyRefLink() {
    const el = document.getElementById('myReferralLink');
    if (!el) return;
    const btn = document.getElementById('copyLinkBtn');
    if (navigator.clipboard) {
        navigator.clipboard.writeText(el.value).then(() => {
            const orig = btn.innerHTML;
            btn.innerHTML = '<i data-lucide="check" class="w-4 h-4"></i> Copied!';
            btn.classList.replace('bg-amber-600', 'bg-green-600');
            btn.classList.replace('hover:bg-amber-700', 'hover:bg-green-700');
            lucide.createIcons();
            setTimeout(() => { btn.innerHTML = orig; btn.classList.replace('bg-green-600', 'bg-amber-600'); btn.classList.replace('hover:bg-green-700', 'hover:bg-amber-700'); lucide.createIcons(); }, 2000);
        });
    } else {
        el.select();
        document.execCommand('copy');
    }
}

const wForm = document.getElementById('withdrawForm');
if (wForm) {
    wForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = document.getElementById('withdrawBtn');
        const msgEl = document.getElementById('withdrawMsg');
        btn.disabled = true;
        btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Processing...';
        lucide.createIcons();

        const fd = new FormData();
        fd.append('_token', '<?= csrf_token() ?>');
        fd.append('amount', document.getElementById('withdrawAmount').value);
        fd.append('payment_details', document.getElementById('withdrawDetails').value);

        fetch('/account/referral/withdraw', {
            method: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            body: fd
        })
        .then(r => r.json())
        .then(d => {
            msgEl.classList.remove('hidden');
            if (d.success) {
                msgEl.className = 'mb-4 px-4 py-3 rounded-xl text-sm bg-green-50 text-green-700 border border-green-200';
                msgEl.textContent = d.message;
                wForm.reset();
                setTimeout(() => window.location.reload(), 2000);
            } else {
                msgEl.className = 'mb-4 px-4 py-3 rounded-xl text-sm bg-red-50 text-red-700 border border-red-200';
                msgEl.textContent = d.error || 'Something went wrong';
            }
            setTimeout(() => msgEl.classList.add('hidden'), 6000);
        })
        .catch(() => {
            msgEl.classList.remove('hidden');
            msgEl.className = 'mb-4 px-4 py-3 rounded-xl text-sm bg-red-50 text-red-700 border border-red-200';
            msgEl.textContent = 'Network error. Please try again.';
        })
        .finally(() => { btn.disabled = false; btn.innerHTML = '<i data-lucide="send" class="w-4 h-4"></i> Submit Withdrawal Request'; lucide.createIcons(); });
    });
}
lucide.createIcons();
</script>