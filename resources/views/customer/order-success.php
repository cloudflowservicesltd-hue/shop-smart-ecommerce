<?php
// Get settings from layout (already loaded in app.php)
$currencySym = $siteSettings['currency_symbol'] ?? 'KSh';
$storeAddr = $siteSettings['store_address'] ?? '';
$storePhone = $siteSettings['store_phone'] ?? '';
?>

<div class="max-w-2xl mx-auto px-4 py-12">
    <div class="text-center mb-8 animate-fade-in">
        <!-- Success Animation -->
        <div class="relative w-24 h-24 mx-auto mb-6">
            <div class="absolute inset-0 bg-amber-100 rounded-full animate-ping opacity-20"></div>
            <div class="relative w-24 h-24 bg-amber-100 rounded-full flex items-center justify-center">
                <div class="w-16 h-16 bg-amber-500 rounded-full flex items-center justify-center shadow-lg shadow-amber-500/30">
                    <i data-lucide="check" class="w-8 h-8 text-white" stroke-width="3"></i>
                </div>
            </div>
        </div>
        <h1 class="font-heading text-3xl font-bold text-gray-900 mb-2">Order Placed Successfully!</h1>
        <p class="text-gray-500">Thank you for your purchase. We'll send a confirmation email shortly.</p>
    </div>

    <?php if (!empty($order ?? [])): ?>
    <!-- Receipt Card -->
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mb-8" id="orderReceipt">
        <!-- Receipt Header with Logo -->
        <div class="border-b-2 border-dashed border-gray-200 px-6 py-5 text-center">
            <?php if ($siteLogo): ?>
            <img src="<?= e($siteLogo) ?>" alt="<?= e($storeName) ?>" class="h-14 w-auto object-contain mx-auto mb-3">
            <?php endif; ?>
            <h2 class="font-heading text-lg font-bold text-gray-900"><?= e($storeName) ?></h2>
            <?php if ($storeAddr): ?>
            <p class="text-xs text-gray-400 mt-0.5"><?= e($storeAddr) ?></p>
            <?php endif; ?>
            <?php if ($storePhone): ?>
            <p class="text-xs text-gray-400"><?= e($storePhone) ?></p>
            <?php endif; ?>
        </div>

        <!-- Order Info -->
        <div class="px-6 py-4">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <p class="text-[10px] text-gray-400 uppercase tracking-wider font-medium">Receipt #</p>
                    <p class="text-base font-bold text-gray-900 font-mono"><?= e($order['order_number']) ?></p>
                </div>
                <span class="inline-flex items-center gap-1.5 bg-amber-100 text-amber-700 text-xs font-semibold px-3 py-1 rounded-full">
                    <span class="w-1.5 h-1.5 bg-amber-500 rounded-full pulse-dot"></span>
                    <?= ucfirst(e($order['status'] ?? 'pending')) ?>
                </span>
            </div>
            <div class="grid grid-cols-2 gap-x-4 gap-y-1.5 text-xs border-t border-dashed border-gray-100 pt-3">
                <div class="flex justify-between">
                    <span class="text-gray-400">Date</span>
                    <span class="font-medium text-gray-700"><?= formatDate($order['created_at']) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Payment</span>
                    <span class="font-medium text-gray-700 capitalize"><?= e(str_replace(['_', '-'], ' ', $order['payment_method'] ?? '')) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Customer</span>
                    <span class="font-medium text-gray-700"><?= e($order['customer_name'] ?? '') ?></span>
                </div>
                <?php if (!empty($order['customer_address'])): ?>
                <div class="flex justify-between col-span-2">
                    <span class="text-gray-400">Address</span>
                    <span class="font-medium text-gray-700 text-right max-w-[60%] truncate"><?= e($order['customer_address']) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Order Items -->
        <?php if (!empty($orderItems ?? [])): ?>
        <div class="px-6 pb-4">
            <div class="border-t border-dashed border-gray-100 pt-3">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-gray-200 text-[10px] font-semibold text-gray-400 uppercase tracking-wider">
                            <th class="py-2 text-left">Item</th>
                            <th class="py-2 text-center">Qty</th>
                            <th class="py-2 text-right">Price</th>
                            <th class="py-2 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orderItems as $item): ?>
                        <tr class="border-b border-gray-50">
                            <td class="py-2.5">
                                <div class="flex items-center gap-2.5">
                                    <img src="<?= $item['image'] ?? '/uploads/no-image-sm.jpg' ?>" alt="" class="w-9 h-9 rounded-lg object-cover bg-gray-50 shrink-0">
                                    <span class="font-medium text-gray-800 truncate max-w-[150px]"><?= e($item['product_name']) ?></span>
                                </div>
                            </td>
                            <td class="py-2.5 text-center text-gray-500"><?= $item['quantity'] ?></td>
                            <td class="py-2.5 text-right text-gray-500"><?= formatMoney($item['price']) ?></td>
                            <td class="py-2.5 text-right font-semibold text-gray-800"><?= formatMoney($item['total'] ?? $item['price'] * $item['quantity']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Totals -->
            <div class="border-t-2 border-dashed border-gray-100 pt-3 mt-1 space-y-1.5 text-xs">
                <div class="flex justify-between text-gray-400">
                    <span>Subtotal</span>
                    <span><?= formatMoney($order['subtotal'] ?? 0) ?></span>
                </div>
                <?php if (!empty($order['discount']) && $order['discount'] > 0): ?>
                <div class="flex justify-between text-green-600">
                    <span>Discount</span>
                    <span>-<?= formatMoney($order['discount']) ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($order['tax']) && $order['tax'] > 0): ?>
                <div class="flex justify-between text-gray-400">
                    <span>Tax</span>
                    <span><?= formatMoney($order['tax']) ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($order['shipping_cost']) && $order['shipping_cost'] > 0): ?>
                <div class="flex justify-between text-gray-400">
                    <span>Shipping</span>
                    <span><?= formatMoney($order['shipping_cost']) ?></span>
                </div>
                <?php endif; ?>
                <div class="flex justify-between text-sm font-bold text-gray-900 border-t-2 border-dashed border-gray-200 pt-2 mt-2">
                    <span>TOTAL</span>
                    <span class="text-amber-600"><?= formatMoney($order['total'] ?? 0) ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Receipt Footer -->
        <div class="border-t-2 border-dashed border-gray-200 px-6 py-4 text-center">
            <p class="text-gray-400 text-xs">Thank you for shopping with us!</p>
            <p class="text-gray-300 text-[10px] mt-0.5"><?= e($storeName) ?> &mdash; Powered by ShopSmart</p>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
        <button onclick="window.print()" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-gray-100 text-gray-700 font-semibold px-6 py-3 rounded-xl hover:bg-gray-200 transition-colors">
            <i data-lucide="printer" class="w-4 h-4"></i> Print Receipt
        </button>
        <?php if (!empty($order['order_number'])): ?>
        <a href="/orders/<?= e($order['order_number']) ?>/track" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-amber-600 text-white font-semibold px-8 py-3 rounded-xl hover:bg-amber-700 transition-colors">
            <i data-lucide="map-pin" class="w-5 h-5"></i> Track Order
        </a>
        <?php endif; ?>
        <a href="/account/orders" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 border border-gray-200 text-gray-700 font-semibold px-6 py-3 rounded-xl hover:bg-gray-50 transition-colors">
            <i data-lucide="package" class="w-4 h-4"></i> View Orders
        </a>
        <a href="/products" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 text-amber-600 hover:text-amber-700 font-medium px-6 py-3 transition-colors">
            <i data-lucide="shopping-bag" class="w-4 h-4"></i> Continue Shopping
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Print-specific styles: hide everything except receipt when printing -->
<style>
@media print {
    body * { visibility: hidden; }
    #orderReceipt, #orderReceipt * { visibility: visible; }
    #orderReceipt { position: absolute; left: 0; top: 0; width: 100%; max-width: 80mm; margin: 0 auto; border: none !important; box-shadow: none !important; border-radius: 0 !important; }
    @page { margin: 5mm; size: 80mm auto; }
}
</style>