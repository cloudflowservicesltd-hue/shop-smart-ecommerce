<?php if (!Auth::check()): ?>
<div class="min-h-[60vh] flex items-center justify-center px-4">
    <div class="text-center">
        <div class="w-20 h-20 mx-auto mb-4 bg-gray-100 rounded-2xl flex items-center justify-center">
            <i data-lucide="user" class="w-10 h-10 text-gray-300"></i>
        </div>
        <h2 class="font-heading text-xl font-bold text-gray-900 mb-2">Please Sign In</h2>
        <p class="text-gray-500 mb-6">Log in to access your account dashboard.</p>
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
            <span class="text-gray-900 font-medium">My Account</span>
        </nav>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Sidebar Navigation -->
        <aside class="lg:w-64 shrink-0">
            <div class="bg-white border border-gray-200 rounded-2xl p-6 sticky top-36">
                <!-- Profile Card -->
                <div class="text-center mb-6 pb-6 border-b border-gray-100">
                    <div class="w-16 h-16 mx-auto mb-3 bg-amber-100 rounded-2xl flex items-center justify-center">
                        <span class="text-2xl font-bold text-amber-600"><?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?></span>
                    </div>
                    <h3 class="font-semibold text-gray-900"><?= e($user['name']) ?></h3>
                    <p class="text-sm text-gray-400"><?= e($user['email']) ?></p>
                </div>

                <nav class="space-y-1">
                    <a href="/account" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium bg-amber-50 text-amber-700 transition-colors">
                        <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Dashboard
                    </a>
                    <a href="/account/orders" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                        <i data-lucide="package" class="w-4 h-4"></i> My Orders
                        <?php if (($orderCount ?? 0) > 0): ?>
                        <span class="ml-auto bg-gray-100 text-gray-500 text-xs font-medium px-2 py-0.5 rounded-full"><?= $orderCount ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="/account/wishlist" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                        <i data-lucide="heart" class="w-4 h-4"></i> Wishlist
                        <?php if (($wishlistCount ?? 0) > 0): ?>
                        <span class="ml-auto bg-gray-100 text-gray-500 text-xs font-medium px-2 py-0.5 rounded-full"><?= $wishlistCount ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="/account/reviews" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                        <i data-lucide="star" class="w-4 h-4"></i> My Reviews
                        <?php if (($reviewCount ?? 0) > 0): ?>
                        <span class="ml-auto bg-gray-100 text-gray-500 text-xs font-medium px-2 py-0.5 rounded-full"><?= $reviewCount ?></span>
                        <?php endif; ?>
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
            <h1 class="font-heading text-2xl font-bold text-gray-900 mb-6">Dashboard</h1>

            <!-- Quick Stats -->
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-8">
                <div class="bg-white border border-gray-200 rounded-2xl p-5 hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 bg-amber-50 rounded-xl flex items-center justify-center">
                            <i data-lucide="package" class="w-5 h-5 text-amber-600"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900"><?= $orderCount ?? 0 ?></p>
                            <p class="text-xs text-gray-400">Total Orders</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5 hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 bg-red-50 rounded-xl flex items-center justify-center">
                            <i data-lucide="heart" class="w-5 h-5 text-red-500"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900"><?= $wishlistCount ?? 0 ?></p>
                            <p class="text-xs text-gray-400">Wishlist Items</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5 hover:shadow-md transition-shadow col-span-2 md:col-span-1">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 bg-amber-50 rounded-xl flex items-center justify-center">
                            <i data-lucide="star" class="w-5 h-5 text-amber-500"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900"><?= $reviewCount ?? 0 ?></p>
                            <p class="text-xs text-gray-400">Reviews</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Info -->
            <div class="bg-white border border-gray-200 rounded-2xl p-6 mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                        <i data-lucide="user" class="w-4 h-4 text-amber-600"></i> Profile Information
                    </h2>
                    <a href="/account/profile" class="text-sm text-amber-600 hover:text-amber-700 font-medium transition-colors">Edit</a>
                </div>
                <div class="grid sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-400 block text-xs mb-0.5">Full Name</span>
                        <span class="font-medium text-gray-900"><?= e($user['name']) ?></span>
                    </div>
                    <div>
                        <span class="text-gray-400 block text-xs mb-0.5">Email</span>
                        <span class="font-medium text-gray-900"><?= e($user['email']) ?></span>
                    </div>
                    <div>
                        <span class="text-gray-400 block text-xs mb-0.5">Phone</span>
                        <span class="font-medium text-gray-900"><?= e($user['phone'] ?? 'Not set') ?></span>
                    </div>
                    <div>
                        <span class="text-gray-400 block text-xs mb-0.5">Member Since</span>
                        <span class="font-medium text-gray-900"><?= formatDate($user['created_at']) ?></span>
                    </div>
                </div>
            </div>

            <?php if (!empty($myReferralLink)): ?>
            <!-- Referral Program -->
            <div class="bg-white border border-gray-200 rounded-2xl p-6 mb-8">
                <h2 class="font-semibold text-gray-900 flex items-center gap-2 mb-4">
                    <i data-lucide="gift" class="w-4 h-4 text-amber-600"></i> Refer &amp; Earn
                </h2>
                <p class="text-sm text-gray-500 mb-4">Share your unique referral link with friends. When they make a purchase, you earn a commission!</p>
                <div class="flex items-center gap-2 mb-4">
                    <input type="text" readonly value="<?= e($myReferralLink) ?>" id="myReferralLink"
                        class="flex-1 px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm font-mono text-gray-700">
                    <button onclick="copyMyRefLink()" class="inline-flex items-center gap-2 bg-amber-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors shrink-0">
                        <i data-lucide="copy" class="w-4 h-4"></i> Copy Link
                    </button>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div class="bg-amber-50 rounded-xl p-4 text-center">
                        <p class="text-xl font-bold text-amber-700"><?= (int)($myReferralStats['total_refs'] ?? 0) ?></p>
                        <p class="text-xs text-amber-600 font-medium">Successful Referrals</p>
                    </div>
                    <div class="bg-green-50 rounded-xl p-4 text-center">
                        <p class="text-xl font-bold text-green-700"><?= formatMoney($myReferralStats['total_earned'] ?? 0) ?></p>
                        <p class="text-xs text-green-600 font-medium">Total Earned</p>
                    </div>
                    <div class="bg-blue-50 rounded-xl p-4 text-center">
                        <p class="text-xl font-bold text-blue-700"><?= formatMoney($myReferralStats['total_paid'] ?? 0) ?></p>
                        <p class="text-xs text-blue-600 font-medium">Paid Out</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recent Orders -->
            <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
                <div class="flex items-center justify-between p-6 pb-4">
                    <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                        <i data-lucide="clock" class="w-4 h-4 text-amber-600"></i> Recent Orders
                    </h2>
                    <?php if (($orderCount ?? 0) > 3): ?>
                    <a href="/account/orders" class="text-sm text-amber-600 hover:text-amber-700 font-medium transition-colors">View All</a>
                    <?php endif; ?>
                </div>
                <?php if (!empty($recentOrders ?? [])): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                                <th class="text-left px-6 py-3 font-semibold">Order</th>
                                <th class="text-left px-6 py-3 font-semibold">Date</th>
                                <th class="text-right px-6 py-3 font-semibold">Total</th>
                                <th class="text-center px-6 py-3 font-semibold">Status</th>
                                <th class="text-right px-6 py-3 font-semibold"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach (array_slice($recentOrders, 0, 5) as $order): ?>
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 font-mono font-medium text-gray-900"><?= e($order['order_number']) ?></td>
                                <td class="px-6 py-4 text-gray-500"><?= formatDate($order['created_at']) ?></td>
                                <td class="px-6 py-4 text-right font-medium text-gray-900"><?= formatMoney($order['total']) ?></td>
                                <td class="px-6 py-4 text-center">
                                    <?php 
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-700',
                                        'paid' => 'bg-green-100 text-green-700',
                                        'processing' => 'bg-blue-100 text-blue-700',
                                        'shipped' => 'bg-blue-100 text-blue-700',
                                        'delivered' => 'bg-amber-100 text-amber-700',
                                        'cancelled' => 'bg-red-100 text-red-700',
                                    ];
                                    $color = $statusColors[$order['status']] ?? 'bg-gray-100 text-gray-700';
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold <?= $color ?>"><?= ucfirst($order['status']) ?></span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="/account/orders/<?= $order['id'] ?>/track" class="text-amber-600 hover:text-amber-700 font-medium transition-colors text-xs">Track</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-12 px-6">
                    <i data-lucide="package" class="w-10 h-10 text-gray-200 mx-auto mb-3"></i>
                    <p class="text-gray-500 mb-4">You haven't placed any orders yet.</p>
                    <a href="/products" class="inline-flex items-center gap-2 bg-amber-600 text-white text-sm font-medium px-6 py-2.5 rounded-xl hover:bg-amber-700 transition-colors">
                        <i data-lucide="shopping-bag" class="w-4 h-4"></i> Start Shopping
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (Auth::check() && !empty($myReferralLink)): ?>
<script>
function copyMyRefLink() {
    const el = document.getElementById('myReferralLink');
    if (!el) return;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(el.value).then(() => {
            const btn = el.nextElementSibling;
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
lucide.createIcons();
</script>
<?php endif; ?>