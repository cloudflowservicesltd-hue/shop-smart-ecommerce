<!-- Breadcrumbs -->
<div class="bg-gray-50 border-b border-gray-100">
    <div class="w-full px-4 sm:px-6 lg:px-8 py-3">
        <nav class="flex items-center gap-2 text-sm text-gray-500">
            <a href="/" class="hover:text-amber-600 transition-colors">Home</a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <span class="text-gray-900 font-medium">Shopping Cart</span>
        </nav>
    </div>
</div>

<div class="w-full px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="font-heading text-2xl md:text-3xl font-bold text-gray-900 mb-8">Shopping Cart</h1>

    <?php if (!empty($cartItems ?? [])): ?>
    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Cart Items -->
        <div class="flex-1">
            <form action="/cart/update" method="POST" id="cartForm">
                <?= csrf() ?>
                <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
                    <!-- Header -->
                    <div class="hidden md:grid grid-cols-12 gap-4 px-6 py-4 bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <div class="col-span-6">Product</div>
                        <div class="col-span-2 text-center">Price</div>
                        <div class="col-span-2 text-center">Quantity</div>
                        <div class="col-span-2 text-right">Subtotal</div>
                    </div>

                    <!-- Items -->
                    <?php foreach ($cartItems as $item): ?>
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 px-4 md:px-6 py-5 border-t border-gray-100 items-center animate-fade-in">
                        <!-- Product -->
                        <div class="col-span-6 flex items-center gap-4">
                            <a href="/product/<?= e($item['slug']) ?>" class="w-20 h-20 shrink-0 bg-gray-50 rounded-xl overflow-hidden border border-gray-100">
                                <img src="<?= $item['image'] ?? "/uploads/no-image-sm.jpg" ?>" 
                                     alt="<?= e($item['name']) ?>" class="w-full h-full object-cover">
                            </a>
                            <div class="min-w-0">
                                <a href="/product/<?= e($item['slug']) ?>" class="font-semibold text-gray-900 text-sm hover:text-amber-600 transition-colors line-clamp-2"><?= e($item['name']) ?></a>
                                <?php if (!empty($item['variant_name'])): ?>
                                <span class="inline-block text-xs font-medium text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md mt-1"><?= e($item['variant_name']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($item['brand_name'])): ?>
                                <p class="text-xs text-gray-400 mt-0.5"><?= e($item['brand_name']) ?></p>
                                <?php endif; ?>
                                <form action="/cart/remove/<?= $item['id'] ?>" method="POST" class="inline" onsubmit="return confirm('Remove this item?')">
                                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                    <button type="submit" class="inline-flex items-center gap-1 text-xs text-red-500 hover:text-red-600 mt-1.5 transition-colors">
                                        <i data-lucide="trash-2" class="w-3 h-3"></i> Remove
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Price -->
                        <div class="col-span-2 text-center">
                            <span class="md:hidden text-xs text-gray-400 mr-1">Price:</span>
                            <span class="text-sm font-medium text-gray-700"><?= formatMoney($item['price']) ?></span>
                            <?php if (!empty($item['original_price']) && $item['original_price'] > $item['price']): ?>
                            <span class="md:hidden text-xs text-gray-400 line-through ml-1"><?= formatMoney($item['original_price']) ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Quantity -->
                        <div class="col-span-2 flex justify-center">
                            <div class="inline-flex items-center border border-gray-200 rounded-lg overflow-hidden">
                                <button type="button" onclick="updateQty(<?= $item['id'] ?>, <?= $item['quantity'] - 1 ?>)" class="px-3 py-1.5 text-gray-500 hover:bg-gray-50 transition-colors" <?= $item['quantity'] <= 1 ? 'disabled' : '' ?>>
                                    <i data-lucide="minus" class="w-3.5 h-3.5"></i>
                                </button>
                                <input type="number" name="qty[<?= $item['id'] ?>]" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock_quantity'] ?? 99 ?>" 
                                       class="w-12 text-center border-x border-gray-200 py-1.5 text-sm font-medium focus:outline-none">
                                <button type="button" onclick="updateQty(<?= $item['id'] ?>, <?= $item['quantity'] + 1 ?>)" class="px-3 py-1.5 text-gray-500 hover:bg-gray-50 transition-colors" <?= ($item['quantity'] ?? 0) >= ($item['stock_quantity'] ?? 99) ? 'disabled' : '' ?>>
                                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Subtotal -->
                        <div class="col-span-2 text-right">
                            <span class="md:hidden text-xs text-gray-400 mr-1">Subtotal:</span>
                            <span class="font-bold text-gray-900"><?= formatMoney($item['price'] * $item['quantity']) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Cart Actions -->
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-4">
                    <a href="/products" class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-amber-600 transition-colors">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i> Continue Shopping
                    </a>
                    <button type="submit" class="inline-flex items-center gap-2 text-sm font-medium text-amber-600 hover:text-amber-700 bg-amber-50 hover:bg-amber-100 px-5 py-2.5 rounded-xl transition-colors">
                        <i data-lucide="refresh-cw" class="w-4 h-4"></i> Update Cart
                    </button>
                </div>
            </form>
        </div>

        <!-- Cart Summary -->
        <div class="lg:w-96 shrink-0">
            <div class="bg-white border border-gray-200 rounded-2xl p-6 sticky top-36">
                <h2 class="font-heading text-lg font-bold text-gray-900 mb-6">Order Summary</h2>

                <!-- Coupon -->
                <?php $flashCouponError = Session::flash('coupon_error'); ?>
                <?php if (!empty($appliedCoupon)): ?>
                <form action="/cart/coupon" method="POST" class="mb-6">
                    <?= csrf() ?>
                    <input type="hidden" name="coupon_action" value="remove">
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i data-lucide="ticket" class="w-4 h-4 text-amber-600"></i>
                            <div>
                                <p class="text-sm font-medium text-amber-800"><?= e($appliedCoupon) ?></p>
                                <p class="text-xs text-amber-600">You save <?= formatMoney($couponDiscount) ?></p>
                            </div>
                        </div>
                        <button type="submit" class="text-xs text-red-500 hover:text-red-700 font-medium transition-colors flex items-center gap-1">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i> Remove
                        </button>
                    </div>
                </form>
                <?php else: ?>
                <form action="/cart/coupon" method="POST" class="mb-6">
                    <?= csrf() ?>
                    <label class="text-sm font-medium text-gray-700 mb-2 block">Coupon Code</label>
                    <div class="flex gap-2">
                        <input type="text" name="coupon" placeholder="Enter code" value="" 
                               class="flex-1 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent uppercase">
                        <button type="submit" class="px-5 py-2.5 bg-gray-900 text-white text-sm font-medium rounded-xl hover:bg-gray-800 transition-colors">
                            Apply
                        </button>
                    </div>
                    <?php if (!empty($couponError)): ?>
                    <p class="text-xs text-red-500 mt-1.5"><?= e($couponError) ?></p>
                    <?php elseif (!empty($flashCouponError)): ?>
                    <p class="text-xs text-red-500 mt-1.5"><?= e($flashCouponError) ?></p>
                    <?php endif; ?>
                </form>
                <?php endif; ?>

                <div class="space-y-3 text-sm border-t border-gray-100 pt-4">
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal (<?= $totalItems ?? 0 ?> items)</span>
                        <span class="font-medium"><?= formatMoney($subtotal ?? 0) ?></span>
                    </div>
                    <?php if (!empty($couponDiscount)): ?>
                    <div class="flex justify-between text-amber-600">
                        <span>Discount</span>
                        <span class="font-medium">-<?= formatMoney($couponDiscount) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="flex justify-between text-gray-600">
                        <span>Shipping</span>
                        <span class="font-medium <?= ($shippingCost ?? 0) == 0 ? 'text-amber-600' : '' ?>">
                            <?= ($shippingCost ?? 0) == 0 ? 'Free' : formatMoney($shippingCost) ?>
                        </span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Tax (16%)</span>
                        <span class="font-medium"><?= formatMoney($tax ?? 0) ?></span>
                    </div>
                    <div class="border-t border-gray-200 pt-3 flex justify-between text-gray-900">
                        <span class="font-semibold text-base">Total</span>
                        <span class="font-bold text-xl"><?= formatMoney($total ?? 0) ?></span>
                    </div>
                </div>

                <?php if (($shippingCost ?? 0) == 0 && ($subtotal ?? 0) > 0): ?>
                <div class="mt-4 bg-amber-50 rounded-xl p-3 flex items-center gap-2 text-sm text-amber-700">
                    <i data-lucide="truck" class="w-4 h-4 shrink-0"></i>
                    <span>You qualify for free shipping!</span>
                </div>
                <?php elseif (($subtotal ?? 0) > 0): ?>
                <div class="mt-4 bg-gray-50 rounded-xl p-3 flex items-center gap-2 text-sm text-gray-500">
                    <i data-lucide="info" class="w-4 h-4 shrink-0"></i>
                    <span>Add <?= formatMoney(5000 - ($subtotal ?? 0)) ?> more for free shipping</span>
                </div>
                <?php endif; ?>

                <a href="/checkout" class="mt-6 w-full inline-flex items-center justify-center gap-2 bg-amber-600 text-white font-semibold py-3.5 rounded-xl hover:bg-amber-700 transition-colors shadow-lg shadow-amber-600/20">
                    <i data-lucide="lock" class="w-4 h-4"></i> Proceed to Checkout
                </a>

                <div class="mt-4 flex items-center justify-center gap-4 text-gray-300">
                    <span class="flex items-center gap-1 text-xs"><i data-lucide="shield-check" class="w-3.5 h-3.5"></i> Secure</span>
                    <span class="flex items-center gap-1 text-xs"><i data-lucide="credit-card" class="w-3.5 h-3.5"></i> M-Pesa / Card</span>
                </div>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- Empty Cart -->
    <div class="text-center py-20 max-w-md mx-auto">
        <div class="w-24 h-24 mx-auto mb-6 bg-gray-100 rounded-3xl flex items-center justify-center">
            <i data-lucide="shopping-cart" class="w-12 h-12 text-gray-300"></i>
        </div>
        <h2 class="font-heading text-2xl font-bold text-gray-900 mb-2">Your cart is empty</h2>
        <p class="text-gray-500 mb-8">Looks like you haven't added anything to your cart yet.</p>
        <a href="/products" class="inline-flex items-center gap-2 bg-amber-600 text-white font-semibold px-8 py-3.5 rounded-xl hover:bg-amber-700 transition-colors shadow-lg shadow-amber-600/20">
            <i data-lucide="shopping-bag" class="w-5 h-5"></i> Start Shopping
        </a>
    </div>
    <?php endif; ?>
</div>

<?php if (!empty($cartItems ?? [])): ?>
<script>
function updateQty(cartItemId, newQty) {
    if (newQty < 1) return;
    const input = document.querySelector('input[name="qty[' + cartItemId + ']"]');
    if (input) { input.value = newQty; document.getElementById('cartForm').submit(); }
}
</script>
<?php endif; ?>