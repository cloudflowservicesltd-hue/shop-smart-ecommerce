<?php $order = $order ?? []; $orderItems = $orderItems ?? []; ?>
<!-- Breadcrumbs -->
<div class="bg-gray-50 border-b border-gray-100">
    <div class="w-full px-4 sm:px-6 xl:px-0 py-3">
        <nav class="flex items-center gap-2 text-sm text-gray-500">
            <a href="/" class="hover:text-amber-600 transition-colors">Home</a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <a href="/account/orders" class="hover:text-amber-600 transition-colors">Orders</a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <span class="text-gray-900 font-medium">Track Order</span>
        </nav>
    </div>
</div>

<?php if (empty($order)): ?>
<div class="w-full px-4 sm:px-6 xl:px-0 py-20 text-center">
    <div class="w-20 h-20 mx-auto mb-4 bg-gray-100 rounded-2xl flex items-center justify-center">
        <i data-lucide="search-x" class="w-10 h-10 text-gray-300"></i>
    </div>
    <h2 class="font-heading text-xl font-bold text-gray-900 mb-2">Order not found</h2>
    <p class="text-gray-500 mb-6">We couldn't find an order with that number.</p>
    <a href="/account/orders" class="inline-flex items-center gap-2 bg-amber-600 text-white font-medium px-6 py-3 rounded-xl hover:bg-amber-700 transition-colors">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Orders
    </a>
</div>
<?php else: ?>

<!-- Order Header -->
<div class="bg-white border-b border-gray-100">
    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wider font-medium mb-1">Order Number</p>
                <h1 class="font-heading text-2xl font-bold text-gray-900 font-mono"><?= e($order['order_number']) ?></h1>
                <p class="text-sm text-gray-500 mt-1">Placed on <?= formatDate($order['created_at']) ?></p>
            </div>
            <div class="text-left sm:text-right">
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
                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold <?= $color ?>">
                    <?php if ($order['status'] !== 'cancelled'): ?>
                    <span class="w-2 h-2 rounded-full mr-2 <?= $order['status'] === 'delivered' ? 'bg-amber-500' : 'pulse-dot bg-current opacity-60' ?>"></span>
                    <?php endif; ?>
                    <?= ucfirst($order['status']) ?>
                </span>
            </div>
        </div>
    </div>
</div>

<div class="max-w-4xl mx-auto px-4 py-8 space-y-8">
    <!-- Tracking Timeline -->
    <?php if ($order['status'] !== 'cancelled'): ?>
    <div class="bg-white border border-gray-200 rounded-2xl p-6 md:p-8">
        <h2 class="font-heading text-lg font-bold text-gray-900 mb-8 flex items-center gap-2">
            <i data-lucide="map-pin" class="w-5 h-5 text-amber-600"></i> Order Tracking
        </h2>
        <div class="relative">
            <?php 
            $allSteps = ['pending', 'paid', 'processing', 'shipped', 'delivered'];
            $stepLabels = ['Order Placed', 'Payment Confirmed', 'Processing', 'Shipped', 'Delivered'];
            $stepIcons = ['shopping-bag', 'credit-card', 'box', 'truck', 'check-circle'];
            $currentIdx = array_search($order['status'], $allSteps);
            if ($currentIdx === false) $currentIdx = -1;
            ?>
            <div class="absolute left-5 top-0 bottom-0 w-0.5 bg-gray-100 hidden md:block"></div>
            <div class="absolute left-[18px] top-0 bottom-0 w-0.5 bg-amber-500 hidden md:block" style="height: <?= max(0, $currentIdx) * 100 . '%' ?>; transition: height 0.5s;"></div>
            <div class="space-y-0">
                <?php foreach ($allSteps as $i => $step): 
                    $isDone = $i <= $currentIdx;
                    $isCurrent = $i == $currentIdx;
                ?>
                <div class="flex items-start gap-4 md:gap-6 py-4 relative">
                    <div class="relative z-10 shrink-0">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center transition-colors <?= $isDone ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-400' ?> <?= $isCurrent ? 'ring-4 ring-amber-100' : '' ?>">
                            <i data-lucide="<?= $stepIcons[$i] ?>" class="w-4.5 h-4.5"></i>
                        </div>
                    </div>
                    <div class="pt-1.5">
                        <p class="font-semibold text-sm <?= $isDone ? 'text-gray-900' : 'text-gray-400' ?>"><?= $stepLabels[$i] ?></p>
                        <?php if ($isCurrent && !empty($order['updated_at'])): ?>
                        <p class="text-xs text-gray-400 mt-0.5">Last updated <?= timeAgo($order['updated_at']) ?></p>
                        <?php elseif ($isDone && !$isCurrent): ?>
                        <p class="text-xs text-amber-600 mt-0.5">Completed</p>
                        <?php else: ?>
                        <p class="text-xs text-gray-300 mt-0.5">Pending</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="bg-red-50 border border-red-200 rounded-2xl p-6 flex items-center gap-4">
        <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center shrink-0">
            <i data-lucide="x-circle" class="w-6 h-6 text-red-500"></i>
        </div>
        <div>
            <h3 class="font-semibold text-red-800">Order Cancelled</h3>
            <p class="text-sm text-red-600 mt-0.5">This order has been cancelled. If you have questions, please contact support.</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Order Details -->
    <div class="grid md:grid-cols-2 gap-6">
        <!-- Shipping Info -->
        <div class="bg-white border border-gray-200 rounded-2xl p-6">
            <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2 text-sm">
                <i data-lucide="truck" class="w-4 h-4 text-amber-600"></i> Shipping Details
            </h3>
            <div class="space-y-2.5 text-sm">
                <div><span class="text-gray-400 block text-xs">Name</span><span class="font-medium text-gray-900"><?= e($order['customer_name']) ?></span></div>
                <div><span class="text-gray-400 block text-xs">Email</span><span class="font-medium text-gray-900"><?= e($order['customer_email']) ?></span></div>
                <div><span class="text-gray-400 block text-xs">Phone</span><span class="font-medium text-gray-900"><?= e($order['customer_phone']) ?></span></div>
                <?php if (!empty($order['customer_address'])): ?>
                <div><span class="text-gray-400 block text-xs">Address</span><span class="font-medium text-gray-900"><?= e($order['customer_address']) ?></span></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Payment Info -->
        <div class="bg-white border border-gray-200 rounded-2xl p-6">
            <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2 text-sm">
                <i data-lucide="credit-card" class="w-4 h-4 text-amber-600"></i> Payment Details
            </h3>
            <div class="space-y-2.5 text-sm">
                <div><span class="text-gray-400 block text-xs">Method</span><span class="font-medium text-gray-900 capitalize"><?= e(str_replace(['_', '-'], ' ', $order['payment_method'] ?? '')) ?></span></div>
                <div><span class="text-gray-400 block text-xs">Payment Status</span>
                    <span class="font-medium <?= ($order['payment_status'] ?? '') === 'paid' ? 'text-amber-600' : 'text-yellow-600' ?>"><?= ucfirst($order['payment_status'] ?? 'pending') ?></span>
                </div>
                <?php if (!empty($order['payment_reference'])): ?>
                <div><span class="text-gray-400 block text-xs">Reference</span><span class="font-medium text-gray-900 font-mono"><?= e($order['payment_reference']) ?></span></div>
                <?php endif; ?>
            </div>
            <?php if (($order['payment_status'] ?? 'pending') === 'pending' && $order['status'] !== 'cancelled' && $order['status'] !== 'failed'): ?>
            <a href="/order/pay/<?= $order['id'] ?>" class="mt-4 w-full inline-flex items-center justify-center gap-2 bg-green-600 text-white font-semibold px-6 py-2.5 rounded-xl hover:bg-green-700 transition-colors text-sm">
                <i data-lucide="credit-card" class="w-4 h-4"></i> Pay Now
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Order Items -->
    <div class="bg-white border border-gray-200 rounded-2xl p-6">
        <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2 text-sm">
            <i data-lucide="package" class="w-4 h-4 text-amber-600"></i> Order Items
        </h3>
        <div class="divide-y divide-gray-100">
            <?php foreach ($orderItems as $item): ?>
            <div class="flex items-center gap-4 py-4 first:pt-0 last:pb-0">
                <img src="<?= $item['image'] ?? "/uploads/no-image-sm.jpg" ?>" 
                     alt="" class="w-14 h-14 rounded-lg object-cover bg-gray-50 shrink-0">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate"><?= e($item['product_name']) ?></p>
                    <p class="text-xs text-gray-400">Qty: <?= $item['quantity'] ?> x <?= formatMoney($item['price']) ?></p>
                </div>
                <span class="text-sm font-bold text-gray-900 shrink-0"><?= formatMoney($item['total'] ?? $item['price'] * $item['quantity']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="border-t border-gray-200 mt-4 pt-4 space-y-2 text-sm">
            <div class="flex justify-between text-gray-500">
                <span>Subtotal</span>
                <span><?= formatMoney($order['subtotal'] ?? 0) ?></span>
            </div>
            <div class="flex justify-between text-gray-500">
                <span>Shipping</span>
                <span><?= ($order['shipping_cost'] ?? 0) == 0 ? 'Free' : formatMoney($order['shipping_cost']) ?></span>
            </div>
            <div class="flex justify-between text-gray-500">
                <span>Tax</span>
                <span><?= formatMoney($order['tax'] ?? 0) ?></span>
            </div>
            <div class="flex justify-between font-bold text-gray-900 text-base pt-2 border-t border-gray-100">
                <span>Total</span>
                <span><?= formatMoney($order['total']) ?></span>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>