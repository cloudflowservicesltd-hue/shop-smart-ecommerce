<div class="min-h-[70vh] flex items-center justify-center px-4 py-12">
    <div class="text-center max-w-lg mx-auto animate-fade-in">
        <!-- Success Animation -->
        <div class="relative w-24 h-24 mx-auto mb-8">
            <div class="absolute inset-0 bg-amber-100 rounded-full animate-ping opacity-20"></div>
            <div class="relative w-24 h-24 bg-amber-100 rounded-full flex items-center justify-center">
                <div class="w-16 h-16 bg-amber-500 rounded-full flex items-center justify-center shadow-lg shadow-amber-500/30">
                    <i data-lucide="check" class="w-8 h-8 text-white" stroke-width="3"></i>
                </div>
            </div>
        </div>

        <h1 class="font-heading text-3xl font-bold text-gray-900 mb-3">Order Placed Successfully!</h1>
        <p class="text-gray-500 mb-2">Thank you for your purchase. We'll send a confirmation email shortly.</p>
        
        <?php if (!empty($order ?? [])): ?>
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 my-8 text-left">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-xs text-amber-600 font-medium uppercase tracking-wider">Order Number</p>
                    <p class="text-xl font-bold text-gray-900 font-mono"><?= e($order['order_number']) ?></p>
                </div>
                <span class="inline-flex items-center gap-1.5 bg-amber-100 text-amber-700 text-sm font-semibold px-4 py-1.5 rounded-full">
                    <span class="w-2 h-2 bg-amber-500 rounded-full pulse-dot"></span>
                    <?= ucfirst(e($order['status'] ?? 'pending')) ?>
                </span>
            </div>
            <div class="space-y-2 text-sm border-t border-amber-200 pt-4">
                <div class="flex justify-between">
                    <span class="text-gray-500">Date</span>
                    <span class="font-medium text-gray-900"><?= formatDate($order['created_at']) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Payment Method</span>
                    <span class="font-medium text-gray-900 capitalize"><?= e(str_replace(['_', '-'], ' ', $order['payment_method'] ?? '')) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Shipping To</span>
                    <span class="font-medium text-gray-900"><?= e($order['customer_name'] ?? '') ?></span>
                </div>
            </div>
        </div>

        <!-- Order Items Summary -->
        <?php if (!empty($orderItems ?? [])): ?>
        <div class="bg-gray-50 rounded-2xl p-6 mb-8 text-left">
            <h3 class="font-semibold text-gray-900 text-sm mb-4">Order Items</h3>
            <div class="space-y-3 max-h-48 overflow-y-auto scrollbar-thin">
                <?php foreach ($orderItems as $item): ?>
                <div class="flex items-center gap-3">
                    <img src="<?= $item['image'] ?? "/uploads/no-image-sm.jpg" ?>" 
                         alt="" class="w-12 h-12 rounded-lg object-cover bg-white">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate"><?= e($item['product_name']) ?></p>
                        <p class="text-xs text-gray-400">Qty: <?= $item['quantity'] ?></p>
                    </div>
                    <span class="text-sm font-semibold text-gray-900 shrink-0"><?= formatMoney($item['total'] ?? $item['price'] * $item['quantity']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="border-t border-gray-200 mt-4 pt-3 flex justify-between text-sm">
                <span class="font-medium text-gray-500">Total</span>
                <span class="font-bold text-lg text-gray-900"><?= formatMoney($order['total'] ?? 0) ?></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <?php if (!empty($order['order_number'])): ?>
            <a href="/orders/<?= e($order['order_number']) ?>/track" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-amber-600 text-white font-semibold px-8 py-3.5 rounded-xl hover:bg-amber-700 transition-colors">
                <i data-lucide="map-pin" class="w-5 h-5"></i> Track Order
            </a>
            <?php endif; ?>
            <a href="/account/orders" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 border border-gray-200 text-gray-700 font-semibold px-8 py-3.5 rounded-xl hover:bg-gray-50 transition-colors">
                <i data-lucide="package" class="w-5 h-5"></i> View Orders
            </a>
            <a href="/products" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 text-amber-600 hover:text-amber-700 font-medium px-6 py-3.5 transition-colors">
                <i data-lucide="shopping-bag" class="w-5 h-5"></i> Continue Shopping
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>