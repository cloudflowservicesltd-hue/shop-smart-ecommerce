<?php if (!Auth::check()): ?>
<div class="min-h-[60vh] flex items-center justify-center px-4">
    <div class="text-center">
        <div class="w-20 h-20 mx-auto mb-4 bg-gray-100 rounded-2xl flex items-center justify-center">
            <i data-lucide="user" class="w-10 h-10 text-gray-300"></i>
        </div>
        <h2 class="font-heading text-xl font-bold text-gray-900 mb-2">Please Sign In</h2>
        <p class="text-gray-500 mb-6">Log in to view your orders.</p>
        <a href="/login" class="inline-flex items-center gap-2 bg-amber-600 text-white font-semibold px-8 py-3 rounded-xl hover:bg-amber-700 transition-colors">
            <i data-lucide="log-in" class="w-5 h-5"></i> Sign In
        </a>
    </div>
</div>
<?php else: ?>
<!-- Breadcrumbs -->
<div class="bg-gray-50 border-b border-gray-100">
    <div class="w-full px-4 sm:px-6 xl:px-4 py-3">
        <nav class="flex items-center gap-2 text-sm text-gray-500">
            <a href="/" class="hover:text-amber-600 transition-colors">Home</a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <a href="/account" class="hover:text-amber-600 transition-colors">Account</a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <span class="text-gray-900 font-medium">My Orders</span>
        </nav>
    </div>
</div>

<div class="w-full px-4 sm:px-6 xl:px-4 py-8">
    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Sidebar -->
        <aside class="lg:w-64 shrink-0">
            <div class="bg-white border border-gray-200 rounded-2xl p-6 sticky top-36">
                <?php $user = Auth::user(); ?>
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
                    <a href="/account/orders" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium bg-amber-50 text-amber-700 transition-colors">
                        <i data-lucide="package" class="w-4 h-4"></i> My Orders
                    </a>
                    <a href="/account/wishlist" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                        <i data-lucide="heart" class="w-4 h-4"></i> Wishlist
                    </a>
                    <a href="/account/reviews" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                        <i data-lucide="star" class="w-4 h-4"></i> My Reviews
                    </a>
                    <a href="/account/profile" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                        <i data-lucide="user" class="w-4 h-4"></i> Edit Profile
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
            <h1 class="font-heading text-2xl font-bold text-gray-900 mb-6">My Orders</h1>

            <?php if (!empty($orders ?? [])): ?>
            <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                                <th class="text-left px-6 py-3.5 font-semibold">Order Number</th>
                                <th class="text-left px-6 py-3.5 font-semibold hidden sm:table-cell">Date</th>
                                <th class="text-right px-6 py-3.5 font-semibold">Total</th>
                                <th class="text-center px-6 py-3.5 font-semibold">Status</th>
                                <th class="text-right px-6 py-3.5 font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($orders as $order): ?>
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <span class="font-mono font-medium text-gray-900"><?= e($order['order_number']) ?></span>
                                    <p class="text-xs text-gray-400 sm:hidden mt-0.5"><?= formatDate($order['created_at']) ?></p>
                                </td>
                                <td class="px-6 py-4 text-gray-500 hidden sm:table-cell"><?= formatDate($order['created_at']) ?></td>
                                <td class="px-6 py-4 text-right font-semibold text-gray-900"><?= formatMoney($order['total']) ?></td>
                                <td class="px-6 py-4">
                                    <?php 
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-700',
                                        'paid' => 'bg-green-100 text-green-700',
                                        'processing' => 'bg-blue-100 text-blue-700',
                                        'shipped' => 'bg-blue-100 text-blue-700',
                                        'delivered' => 'bg-amber-100 text-amber-700',
                                        'cancelled' => 'bg-red-100 text-red-700',
                                        'failed' => 'bg-red-100 text-red-700',
                                    ];
                                    $color = $statusColors[$order['status']] ?? 'bg-gray-100 text-gray-700';
                                    $payStatusColor = ($order['payment_status'] ?? 'pending') === 'paid' ? 'text-green-600' : 'text-yellow-600';
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold <?= $color ?>">
                                        <?= ucfirst($order['status']) ?>
                                    </span>
                                    <?php if (($order['payment_status'] ?? 'pending') === 'pending' && $order['status'] !== 'cancelled' && $order['status'] !== 'failed'): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-50 text-yellow-600 ml-1">
                                        <i data-lucide="clock" class="w-3 h-3 mr-1"></i>Awaiting Payment
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right space-y-1">
                                    <a href="/account/orders/<?= $order['id'] ?>/track" class="inline-flex items-center gap-1 text-amber-600 hover:text-amber-700 font-medium transition-colors text-xs">
                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i> View
                                    </a>
                                    <?php if (($order['payment_status'] ?? 'pending') === 'pending' && $order['status'] !== 'cancelled' && $order['status'] !== 'failed'): ?>
                                    <a href="/order/pay/<?= $order['id'] ?>" class="inline-flex items-center gap-1 bg-green-600 hover:bg-green-700 text-white font-medium px-3 py-1.5 rounded-lg transition-colors text-xs">
                                        <i data-lucide="credit-card" class="w-3.5 h-3.5"></i> Pay Now
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if (!empty($pagination) && ($pagination['last_page'] ?? 1) > 1): ?>
                <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-center gap-1">
                    <?php if (($pagination['current_page'] ?? 1) > 1): ?>
                    <a href="?page=<?= $pagination['current_page'] - 1 ?>" class="px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-100 transition-colors">
                        <i data-lucide="chevron-left" class="w-4 h-4"></i>
                    </a>
                    <?php endif; ?>
                    <?php for ($p = 1; $p <= $pagination['last_page']; $p++): ?>
                    <a href="?page=<?= $p ?>" class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= $p == $pagination['current_page'] ? 'bg-amber-600 text-white' : 'text-gray-600 hover:bg-gray-100' ?>"><?= $p ?></a>
                    <?php endfor; ?>
                    <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
                    <a href="?page=<?= $pagination['current_page'] + 1 ?>" class="px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-100 transition-colors">
                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-20 bg-white border border-gray-200 rounded-2xl">
                <div class="w-20 h-20 mx-auto mb-4 bg-gray-100 rounded-2xl flex items-center justify-center">
                    <i data-lucide="package" class="w-10 h-10 text-gray-300"></i>
                </div>
                <h3 class="font-heading text-lg font-semibold text-gray-900 mb-2">No orders yet</h3>
                <p class="text-gray-500 mb-6">When you place your first order, it will appear here.</p>
                <a href="/products" class="inline-flex items-center gap-2 bg-amber-600 text-white font-medium px-6 py-3 rounded-xl hover:bg-amber-700 transition-colors">
                    <i data-lucide="shopping-bag" class="w-4 h-4"></i> Browse Products
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>