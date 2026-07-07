<?php if (empty($cartItems ?? [])): ?>
<div class="w-full px-4 sm:px-6 xl:px-0 py-20 text-center">
    <div class="w-24 h-24 mx-auto mb-6 bg-gray-100 rounded-3xl flex items-center justify-center">
        <i data-lucide="shopping-cart" class="w-12 h-12 text-gray-300"></i>
    </div>
    <h2 class="font-heading text-2xl font-bold text-gray-900 mb-2">Your cart is empty</h2>
    <p class="text-gray-500 mb-8">Add some items to your cart before proceeding to checkout.</p>
    <a href="/products" class="inline-flex items-center gap-2 bg-amber-600 text-white font-semibold px-8 py-3.5 rounded-xl hover:bg-amber-700 transition-colors">
        <i data-lucide="shopping-bag" class="w-5 h-5"></i> Continue Shopping
    </a>
</div>
<?php else: ?>
<?php
// Load cities from database (with shipping cost)
$cityRows = Database::select("SELECT * FROM cities WHERE is_active = 1 ORDER BY sort_order, name") ?: [];
if (empty($cityRows)) {
    // Fallback: try shipping_cities table
    $cityRows = Database::select("SELECT id, name, 0 as shipping_cost FROM shipping_cities WHERE is_active = 1 ORDER BY sort_order, name") ?: [];
}
if (empty($cityRows)) {
    // Hardcoded fallback
    $cityRows = [
        ['id' => 0, 'name' => 'Nairobi', 'shipping_cost' => 0],
        ['id' => 1, 'name' => 'Mombasa', 'shipping_cost' => 300],
        ['id' => 2, 'name' => 'Kisumu', 'shipping_cost' => 300],
        ['id' => 3, 'name' => 'Nakuru', 'shipping_cost' => 200],
        ['id' => 4, 'name' => 'Eldoret', 'shipping_cost' => 250],
        ['id' => 5, 'name' => 'Thika', 'shipping_cost' => 150],
        ['id' => 6, 'name' => 'Malindi', 'shipping_cost' => 400],
        ['id' => 7, 'name' => 'Kitale', 'shipping_cost' => 350],
        ['id' => 8, 'name' => 'Nyeri', 'shipping_cost' => 200],
        ['id' => 9, 'name' => 'Nanyuki', 'shipping_cost' => 250],
    ];
}
$cities = array_column($cityRows, 'name');
// Build city shipping cost map for JS
$cityShippingMap = [];
foreach ($cityRows as $cr) {
    $cityShippingMap[$cr['name']] = (float)$cr['shipping_cost'];
}
// Load active payment methods from settings
$activePaymentMethods = [];
$methodConfigs = [
    'mpesa' => ['label' => 'M-Pesa', 'icon' => 'smartphone', 'color' => 'green', 'desc' => 'Pay via M-Pesa on your phone', 'badge' => 'Popular'],
    'intasend' => ['label' => 'IntaSend', 'icon' => 'zap', 'color' => 'purple', 'desc' => 'Mobile money & card payments'],
    'paypal' => ['label' => 'PayPal', 'icon' => 'globe', 'color' => 'indigo', 'desc' => 'Pay via card or PayPal account', 'badge' => 'Cards Accepted'],
    'pesapal' => ['label' => 'PesaPal', 'icon' => 'wallet', 'color' => 'orange', 'desc' => 'Multiple payment options'],
    'stripe' => ['label' => 'Stripe', 'icon' => 'credit-card', 'color' => 'indigo', 'desc' => 'Visa, Mastercard & more'],
];
foreach ($methodConfigs as $key => $config) {
    $setting = Database::selectOne("SELECT value FROM settings WHERE `key` = ?", [$key . '_enabled']);
    if ($setting && $setting['value'] === '1') {
        $activePaymentMethods[$key] = $config;
    }
}
// Fallback: if no methods are enabled, show mpesa
if (empty($activePaymentMethods)) {
    $activePaymentMethods['mpesa'] = $methodConfigs['mpesa'];
}

// PayPal JS SDK config
$ppClientId = Database::selectOne("SELECT value FROM settings WHERE `key` = 'paypal_client_id'")['value'] ?? '';
$ppEnv = Database::selectOne("SELECT value FROM settings WHERE `key` = 'paypal_env'")['value'] ?? 'sandbox';
$ppCurrency = Database::selectOne("SELECT value FROM settings WHERE `key` = 'paypal_currency'")['value'] ?? 'USD';

// Color mapping for Tailwind classes
$colorMap = [
    'green'  => ['bg' => 'bg-green-100', 'text' => 'text-green-600', 'border' => 'border-green-500', 'bgLight' => 'bg-green-50'],
    'purple' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-600', 'border' => 'border-purple-500', 'bgLight' => 'bg-purple-50'],
    'indigo' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-600', 'border' => 'border-indigo-500', 'bgLight' => 'bg-indigo-50'],
    'orange' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-600', 'border' => 'border-orange-500', 'bgLight' => 'bg-orange-50'],
];
?>
<!-- Breadcrumbs -->
<div class="bg-gray-50 border-b border-gray-100">
    <div class="w-full px-4 sm:px-6 xl:px-0 py-3">
        <nav class="flex items-center gap-2 text-sm text-gray-500">
            <a href="/" class="hover:text-amber-600 transition-colors">Home</a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <a href="/cart" class="hover:text-amber-600 transition-colors">Cart</a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <span class="text-gray-900 font-medium">Checkout</span>
        </nav>
    </div>
</div>

<!-- Steps Indicator -->
<div class="bg-white border-b border-gray-100">
    <div class="max-w-3xl mx-auto px-4 py-5">
        <div class="flex items-center justify-center gap-0">
            <?php
            $steps = ['shipping', 'payment', 'review'];
            $stepLabels = ['Shipping', 'Payment', 'Review'];
            $currentStep = $currentStep ?? 'shipping';
            $stepIndex = array_search($currentStep, $steps);
            foreach ($steps as $i => $step):
                $isActive = $i == $stepIndex;
                $isDone = $i < $stepIndex;
            ?>
            <div class="flex items-center">
                <div class="flex items-center gap-2">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold transition-colors <?= $isDone ? 'bg-amber-600 text-white' : ($isActive ? 'bg-amber-600 text-white' : 'bg-gray-100 text-gray-400') ?>">
                        <?php if ($isDone): ?>
                            <i data-lucide="check" class="w-4 h-4"></i>
                        <?php else: ?>
                            <?= $i + 1 ?>
                        <?php endif; ?>
                    </div>
                    <span class="text-sm font-medium hidden sm:block <?= $isActive ? 'text-amber-600' : 'text-gray-400' ?>"><?= $stepLabels[$i] ?></span>
                </div>
                <?php if ($i < count($steps) - 1): ?>
                <div class="w-12 md:w-20 h-0.5 mx-3 rounded-full <?= $i < $stepIndex ? 'bg-amber-600' : 'bg-gray-200' ?>"></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 py-8">
    <form action="/checkout/<?= $currentStep ?>" method="POST" id="checkoutForm">
        <?= csrf() ?>
        <div class="flex flex-col lg:flex-row gap-8">

            <!-- Main Form -->
            <div class="flex-1">
                <?php if ($currentStep === 'shipping'): ?>
                <!-- Shipping Information -->
                <div class="bg-white border border-gray-200 rounded-2xl p-6">
                    <h2 class="font-heading text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                        <i data-lucide="map-pin" class="w-5 h-5 text-amber-600"></i> Shipping Information
                    </h2>
                    <div class="grid sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Full Name *</label>
                            <input type="text" name="name" required value="<?= e($shipping['name'] ?? Auth::user()['name'] ?? '') ?>"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent" placeholder="John Doe">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Email Address *</label>
                            <input type="email" name="email" required value="<?= e($shipping['email'] ?? Auth::user()['email'] ?? '') ?>"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent" placeholder="john@example.com">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Phone Number *</label>
                            <input type="tel" name="phone" required value="<?= e($shipping['phone'] ?? Auth::user()['phone'] ?? '') ?>"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent" placeholder="+254 700 000 000">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">City *</label>
                            <select name="city" id="citySelect" required class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent bg-white">
                                <option value="">Select city</option>
                                <?php
                                foreach ($cityRows as $cr): ?>
                                <option value="<?= e($cr['name']) ?>" data-shipping-cost="<?= (float)$cr['shipping_cost'] ?>" <?= ($shipping['city'] ?? Auth::user()['city'] ?? '') == $cr['name'] ? 'selected' : '' ?>><?= e($cr['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Delivery Address <span class="text-gray-400 font-normal">(optional)</span></label>
                            <input type="text" name="address" value="<?= e($shipping['address'] ?? Auth::user()['address'] ?? '') ?>"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent" placeholder="Street address, apartment, suite, etc.">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Order Notes (optional)</label>
                            <textarea name="notes" rows="3" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent resize-none" placeholder="Any special delivery instructions..."><?= e($shipping['notes'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <div class="flex justify-end mt-6">
                        <button type="submit" class="inline-flex items-center gap-2 bg-amber-600 text-white font-semibold px-8 py-3 rounded-xl hover:bg-amber-700 transition-colors">
                            Continue to Payment <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <?php elseif ($currentStep === 'payment'): ?>
                <!-- Payment Method -->
                <div class="bg-white border border-gray-200 rounded-2xl p-6">
                    <h2 class="font-heading text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                        <i data-lucide="credit-card" class="w-5 h-5 text-amber-600"></i> Payment Method
                    </h2>
                    <div class="space-y-3">
                        <?php $first = true; foreach ($activePaymentMethods as $key => $config):
                            $colors = $colorMap[$config['color']] ?? $colorMap['green'];
                        ?>
                        <label class="flex items-center gap-4 p-4 border border-gray-200 rounded-xl cursor-pointer hover:border-amber-300 hover:bg-amber-50/50 transition-colors has-[:checked]:<?= $colors['border'] ?> has-[:checked]:<?= $colors['bgLight'] ?>">
                            <input type="radio" name="payment_method" value="<?= $key ?>" <?= $first ? 'checked' : '' ?> class="w-4.5 h-4.5 text-amber-600 focus:ring-amber-500">
                            <div class="w-10 h-10 <?= $colors['bg'] ?> rounded-lg flex items-center justify-center shrink-0">
                                <i data-lucide="<?= $config['icon'] ?>" class="w-5 h-5 <?= $colors['text'] ?>"></i>
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-gray-900 text-sm"><?= e($config['label']) ?></p>
                                <p class="text-xs text-gray-500"><?= e($config['desc']) ?></p>
                            </div>
                            <?php if (!empty($config['badge'])): ?>
                            <span class="text-xs <?= $colors['bg'] ?> <?= $colors['text'] ?> font-medium px-2 py-1 rounded-full"><?= e($config['badge']) ?></span>
                            <?php endif; ?>
                        </label>
                        <?php $first = false; endforeach; ?>
                    </div>

                    <div class="flex justify-between mt-6">
                        <a href="/checkout/shipping" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-700 font-medium px-6 py-3 rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors">
                            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
                        </a>
                        <button type="submit" class="inline-flex items-center gap-2 bg-amber-600 text-white font-semibold px-8 py-3 rounded-xl hover:bg-amber-700 transition-colors">
                            Review Order <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <?php elseif ($currentStep === 'review'): ?>
                <!-- Hidden input so JS can find the selected payment method -->
                <input type="hidden" name="payment_method" value="<?= e($shipping['payment_method'] ?? 'mpesa') ?>">
                <!-- Review & Place Order -->
                <div class="space-y-6">
                    <!-- Shipping Summary -->
                    <div class="bg-white border border-gray-200 rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="font-heading text-lg font-bold text-gray-900 flex items-center gap-2">
                                <i data-lucide="map-pin" class="w-5 h-5 text-amber-600"></i> Shipping Details
                            </h2>
                            <a href="/checkout/shipping" class="text-sm text-amber-600 hover:text-amber-700 font-medium transition-colors">Edit</a>
                        </div>
                        <div class="grid sm:grid-cols-2 gap-3 text-sm">
                            <div><span class="text-gray-400">Name:</span> <span class="font-medium text-gray-900"><?= e($shipping['name'] ?? '') ?></span></div>
                            <div><span class="text-gray-400">Email:</span> <span class="font-medium text-gray-900"><?= e($shipping['email'] ?? '') ?></span></div>
                            <div><span class="text-gray-400">Phone:</span> <span class="font-medium text-gray-900"><?= e($shipping['phone'] ?? '') ?></span></div>
                            <div><span class="text-gray-400">City:</span> <span class="font-medium text-gray-900"><?= e($shipping['city'] ?? '') ?></span></div>
                            <div class="sm:col-span-2"><span class="text-gray-400">Address:</span> <span class="font-medium text-gray-900"><?= e($shipping['address'] ?? '') ?></span></div>
                        </div>
                    </div>

                    <!-- Payment Summary -->
                    <div class="bg-white border border-gray-200 rounded-2xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="font-heading text-lg font-bold text-gray-900 flex items-center gap-2">
                                <i data-lucide="credit-card" class="w-5 h-5 text-amber-600"></i> Payment Method
                            </h2>
                            <a href="/checkout/payment" class="text-sm text-amber-600 hover:text-amber-700 font-medium transition-colors">Edit</a>
                        </div>
                        <p class="text-sm font-medium text-gray-900"><?php
                            $pmLabel = $activePaymentMethods[$shipping['payment_method'] ?? 'mpesa']['label'] ?? ucfirst(str_replace(['_', '-'], ' ', $shipping['payment_method'] ?? 'mpesa'));
                            echo e($pmLabel);
                        ?></p>
                    </div>

                    <!-- Order Items -->
                    <div class="bg-white border border-gray-200 rounded-2xl p-6">
                        <h2 class="font-heading text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <i data-lucide="package" class="w-5 h-5 text-amber-600"></i> Order Items (<?= count($cartItems ?? []) ?>)
                        </h2>
                        <div class="space-y-4 max-h-64 overflow-y-auto scrollbar-thin">
                            <?php foreach ($cartItems as $item): ?>
                            <div class="flex items-center gap-3">
                                <img src="<?= $item['image'] ?? "/uploads/no-image-sm.jpg" ?>"
                                     alt="" class="w-14 h-14 rounded-lg object-cover bg-gray-50">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate"><?= e($item['name']) ?></p>
                                    <p class="text-xs text-gray-400">Qty: <?= $item['quantity'] ?> x <?= formatMoney($item['price']) ?></p>
                                </div>
                                <span class="text-sm font-bold text-gray-900 shrink-0"><?= formatMoney($item['price'] * $item['quantity']) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="flex justify-between">
                        <a href="/checkout/payment" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-700 font-medium px-6 py-3 rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors">
                            <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
                        </a>
                        <button type="button" id="placeOrderBtn" onclick="initiatePayment()" class="inline-flex items-center gap-2 bg-amber-600 text-white font-semibold px-10 py-3.5 rounded-xl hover:bg-amber-700 transition-colors shadow-lg shadow-amber-600/20">
                            <i data-lucide="lock" class="w-4 h-4"></i> Place Order
                        </button>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Order Summary Sidebar -->
            <div class="lg:w-96 shrink-0">
                <div class="bg-white border border-gray-200 rounded-2xl p-6 sticky top-36">
                    <h2 class="font-heading text-lg font-bold text-gray-900 mb-4">Order Summary</h2>
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
                            <span id="shippingCostDisplay" class="font-medium <?= ($shippingCost ?? 0) == 0 ? 'text-amber-600' : '' ?>">
                                <?= ($shippingCost ?? 0) == 0 ? 'Free' : formatMoney($shippingCost) ?>
                            </span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Tax (<?= $taxRate ?? 16 ?>%)</span>
                            <span id="orderTaxDisplay" class="font-medium"><?= formatMoney($tax ?? 0) ?></span>
                        </div>
                        <div class="border-t border-gray-200 pt-3 flex justify-between">
                            <span class="font-semibold text-base text-gray-900">Total</span>
                            <span id="orderTotalDisplay" class="font-bold text-xl text-gray-900"><?= formatMoney($total ?? 0) ?></span>
                        </div>
                    </div>
                    <div class="mt-6 bg-gray-50 rounded-xl p-4">
                        <div class="flex items-center gap-2 text-sm text-gray-500 mb-3">
                            <i data-lucide="shield-check" class="w-4 h-4 text-amber-500"></i>
                            <span class="font-medium text-gray-700">Secure Checkout</span>
                        </div>
                        <div class="flex items-center gap-3 text-xs text-gray-400">
                            <span class="flex items-center gap-1"><i data-lucide="lock" class="w-3 h-3"></i> SSL Encrypted</span>
                            <span class="flex items-center gap-1"><i data-lucide="check-circle" class="w-3 h-3"></i> Safe Payment</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- M-Pesa Payment Modal -->
<div id="mpesaModal" class="hidden fixed inset-0 z-50">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeMpesaModal()"></div>
    <!-- Modal Card -->
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
            <!-- Close button -->
            <button onclick="closeMpesaModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
            <!-- Header -->
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-green-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="smartphone" class="w-8 h-8 text-green-600"></i>
                </div>
                <h3 class="font-heading text-xl font-bold text-gray-900">Pay with M-Pesa</h3>
                <p class="text-gray-500 text-sm mt-1">Complete your payment via M-Pesa STK Push</p>
            </div>
            <!-- Amount -->
            <div class="bg-green-50 border border-green-100 rounded-xl p-4 mb-5 text-center">
                <p class="text-sm text-green-600 font-medium">Amount to Pay</p>
                <p class="text-2xl font-bold text-green-700 mt-1">KSh <?= number_format(round($total ?? 0)) ?></p>
                <?php if (($total ?? 0) != round($total ?? 0)): ?>
                <p class="text-xs text-green-500 mt-1">M-Pesa rounds to the nearest shilling (was <?= formatMoney($total) ?>)</p>
                <?php endif; ?>
            </div>
            <!-- Phone Input -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">M-Pesa Phone Number</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i data-lucide="phone" class="w-4 h-4 text-gray-400"></i>
                    </div>
                    <input type="tel" id="mpesaPhone" placeholder="+254 7XX XXX XXX"
                           pattern="^(\+254|0)[17]\d{8}$"
                           class="w-full pl-11 pr-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                           onkeydown="if(event.key==='Enter')processMpesaPayment()">
                </div>
                <p id="mpesaError" class="hidden text-sm text-red-600 mt-1.5 flex items-center gap-1">
                    <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                    <span></span>
                </p>
            </div>
            <!-- Pay Button -->
            <button id="mpesaPayBtn" onclick="processMpesaPayment()" class="w-full inline-flex items-center justify-center gap-2 bg-green-600 text-white font-semibold px-6 py-3.5 rounded-xl hover:bg-green-700 transition-colors">
                Send STK Push <i data-lucide="send" class="w-4 h-4"></i>
            </button>
            <!-- Status Area -->
            <div id="mpesaStatus" class="mt-4"></div>
        </div>
    </div>
</div>

<!-- PayPal JS SDK Modal -->
<div id="paypalSdkModal" class="hidden fixed inset-0 z-50">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closePaypalSdkModal()"></div>
    <!-- Modal Card -->
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
            <!-- Close button -->
            <button onclick="closePaypalSdkModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
            <!-- Header -->
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-indigo-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none">
                        <path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944 3.72a.77.77 0 0 1 .757-.654h6.328c2.352 0 4.02.546 4.957 1.632.906 1.05 1.18 2.59.84 4.593-.413 2.427-1.473 4.17-3.137 5.175-1.622.977-3.727 1.472-6.26 1.472H6.63l-.96 5.615a.641.641 0 0 1-.594.584z" fill="#003087"/>
                        <path d="M21.735 7.44c-.023.132-.048.267-.077.405-.99 4.79-4.49 6.438-8.93 6.438h-2.26a1.11 1.11 0 0 0-1.094.936l-1.164 7.39a.584.584 0 0 0 .577.677h4.218a.976.976 0 0 0 .963-.822l.041-.214.779-4.94.05-.272a.976.976 0 0 1 .963-.823h.618c4 0 7.13-1.626 8.048-6.33.382-1.96.184-3.595-.828-4.748a3.95 3.95 0 0 0-.396-.417z" fill="#0070e0"/>
                        <path d="M20.722 6.99a7.4 7.4 0 0 0-.998-.238 12.7 12.7 0 0 0-2.003-.147h-5.01a.976.976 0 0 0-.964.823l-1.283 8.138-.037.19a1.11 1.11 0 0 1 1.094-.936h2.26c4.44 0 7.94-1.648 8.93-6.438.038-.19.07-.377.098-.562a5.06 5.06 0 0 0-1.087-1.83z" fill="#003087"/>
                    </svg>
                </div>
                <h3 class="font-heading text-xl font-bold text-gray-900">Pay with PayPal</h3>
                <p class="text-gray-500 text-sm mt-1">Choose how you want to pay</p>
            </div>
            <!-- Amount -->
            <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 mb-5 text-center">
                <p class="text-sm text-indigo-600 font-medium">Amount to Pay</p>
                <p class="text-2xl font-bold text-indigo-700 mt-1"><?= formatMoney($total ?? 0) ?></p>
                <p id="paypalConvertedAmount" class="text-xs text-indigo-400 mt-1"></p>
            </div>
            <!-- PayPal Buttons Container -->
            <div id="paypalButtonContainer" class="min-h-[45px] flex items-center justify-center">
                <div class="flex items-center gap-2 text-gray-400">
                    <i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i>
                    <span class="text-sm">Loading PayPal...</span>
                </div>
            </div>
            <!-- Error/Status area -->
            <div id="paypalSdkStatus" class="mt-4"></div>
            <!-- Cancel -->
            <button onclick="closePaypalSdkModal()" class="w-full mt-3 inline-flex items-center justify-center gap-2 text-gray-600 hover:text-gray-800 font-medium px-6 py-3 rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors">
                Cancel
            </button>
        </div>
    </div>
</div>

<!-- Redirect Payment Modal (IntaSend / PesaPal / Stripe) -->
<div id="redirectModal" class="hidden fixed inset-0 z-50">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeRedirectModal()"></div>
    <!-- Modal Card -->
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
            <!-- Close button -->
            <button onclick="closeRedirectModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
            <!-- Header -->
            <div class="text-center mb-6">
                <div id="redirectModalIcon" class="w-16 h-16 bg-indigo-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="globe" class="w-8 h-8 text-indigo-600"></i>
                </div>
                <h3 class="font-heading text-xl font-bold text-gray-900">Pay with <span id="redirectMethodName">Gateway</span></h3>
                <p class="text-gray-500 text-sm mt-1">You will be redirected to complete your payment securely</p>
            </div>
            <!-- Amount -->
            <div id="redirectAmountBox" class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 mb-5 text-center">
                <p class="text-sm text-indigo-600 font-medium">Amount to Pay</p>
                <p class="text-2xl font-bold text-indigo-700 mt-1"><?= formatMoney($total ?? 0) ?></p>
            </div>
            <!-- Proceed Button -->
            <button id="redirectPayBtn" onclick="processRedirectPayment()" class="w-full inline-flex items-center justify-center gap-2 bg-amber-600 text-white font-semibold px-6 py-3.5 rounded-xl hover:bg-amber-700 transition-colors">
                Proceed to Payment <i data-lucide="external-link" class="w-4 h-4"></i>
            </button>
            <!-- Cancel -->
            <button onclick="closeRedirectModal()" class="w-full mt-3 inline-flex items-center justify-center gap-2 text-gray-600 hover:text-gray-800 font-medium px-6 py-3 rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors">
                Cancel
            </button>
        </div>
    </div>
</div>

<!-- Payment JavaScript -->
<script>
// City shipping cost data
const cityShippingMap = <?= json_encode($cityShippingMap) ?>;
const currencySymbol = '<?= getCurrencySymbol() ?>';
const shippingThreshold = <?= getShippingThreshold() ?>;
const taxRate = <?= $taxRate ?? 16 ?>;
const subtotalVal = <?= (float)($subtotal ?? 0) ?>;
const couponDiscountVal = <?= (float)($couponDiscount ?? 0) ?>;

// PayPal JS SDK config
const ppClientId = '<?= e($ppClientId) ?>';
const ppEnv = '<?= e($ppEnv) ?>';
const ppCurrency = '<?= e($ppCurrency) ?>';
let paypalSdkLoaded = false;

function updateShippingDisplay() {
    const select = document.getElementById('citySelect');
    if (!select) return;
    const opt = select.options[select.selectedIndex];
    const cityShippingCost = opt && opt.dataset.shippingCost ? parseFloat(opt.dataset.shippingCost) : 0;

    // Check free shipping threshold
    let finalShipping = cityShippingCost;
    if (shippingThreshold > 0 && subtotalVal >= shippingThreshold) {
        finalShipping = 0;
    }

    const el = document.getElementById('shippingCostDisplay');
    if (el) {
        if (finalShipping === 0) {
            el.textContent = 'Free';
            el.className = 'font-medium text-amber-600';
        } else {
            el.textContent = currencySymbol + ' ' + finalShipping.toFixed(2);
            el.className = 'font-medium text-gray-900';
        }
    }

    // Also update the total if visible
    updateOrderTotalDisplay(finalShipping);
}

function updateOrderTotalDisplay(shippingCost) {
    const afterDiscount = subtotalVal - couponDiscountVal;
    const tax = afterDiscount * (taxRate / 100);
    const total = afterDiscount + tax + shippingCost;
    const totalEl = document.getElementById('orderTotalDisplay');
    if (totalEl) {
        totalEl.textContent = currencySymbol + ' ' + total.toFixed(2);
    }
    const taxEl = document.getElementById('orderTaxDisplay');
    if (taxEl) {
        taxEl.textContent = currencySymbol + ' ' + tax.toFixed(2);
    }
}

// Attach city change listener
document.addEventListener('DOMContentLoaded', function() {
    const citySelect = document.getElementById('citySelect');
    if (citySelect) {
        citySelect.addEventListener('change', updateShippingDisplay);
        updateShippingDisplay(); // initialize
    }
});
</script>

<script>
// Payment method definitions with icons, colors, names
const paymentMethods = {
    mpesa: { name: 'M-Pesa', color: 'green', icon: 'smartphone' },
    intasend: { name: 'IntaSend', color: 'purple', icon: 'zap' },
    paypal: { name: 'PayPal', color: 'indigo', icon: 'globe' },
    pesapal: { name: 'PesaPal', color: 'orange', icon: 'wallet' },
    stripe: { name: 'Stripe', color: 'indigo', icon: 'credit-card' },
};

const colorStyles = {
    green:  { bg: 'bg-green-100',  text: 'text-green-600',  border: 'border-green-100', bgBox: 'bg-green-50',  textBox: 'text-green-600', borderBox: 'border-green-100', textBold: 'text-green-700' },
    purple: { bg: 'bg-purple-100', text: 'text-purple-600', border: 'border-purple-100', bgBox: 'bg-purple-50', textBox: 'text-purple-600', borderBox: 'border-purple-100', textBold: 'text-purple-700' },
    indigo: { bg: 'bg-indigo-100', text: 'text-indigo-600', border: 'border-indigo-100', bgBox: 'bg-indigo-50', textBox: 'text-indigo-600', borderBox: 'border-indigo-100', textBold: 'text-indigo-700' },
    orange: { bg: 'bg-orange-100', text: 'text-orange-600', border: 'border-orange-100', bgBox: 'bg-orange-50', textBox: 'text-orange-600', borderBox: 'border-orange-100', textBold: 'text-orange-700' },
};

function initiatePayment() {
    // Get selected payment method: hidden input on review step, or checked radio on payment step
    const form = document.getElementById('checkoutForm');
    const methodInput = form.querySelector('input[name="payment_method"][type="hidden"]')
        || form.querySelector('input[name="payment_method"]:checked');
    if (!methodInput || !methodInput.value) { alert('Select a payment method'); return; }
    const method = methodInput.value;

    if (method === 'mpesa') {
        openMpesaModal();
    } else if (method === 'paypal') {
        openPaypalSdkModal();
    } else {
        openRedirectModal(method);
    }
}

function openMpesaModal() {
    document.getElementById('mpesaModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    lucide.createIcons();
    // Focus the phone input after a short delay
    setTimeout(() => document.getElementById('mpesaPhone').focus(), 200);
}

function closeMpesaModal() {
    document.getElementById('mpesaModal').classList.add('hidden');
    document.body.style.overflow = '';
    // Reset state
    document.getElementById('mpesaStatus').innerHTML = '';
    document.getElementById('mpesaPhone').value = '';
    document.getElementById('mpesaPhone').disabled = false;
    document.getElementById('mpesaPayBtn').disabled = false;
    document.getElementById('mpesaPayBtn').innerHTML = 'Send STK Push <i data-lucide="send" class="w-4 h-4"></i>';
    document.getElementById('mpesaError').classList.add('hidden');
    lucide.createIcons();
}

// Safe JSON parse: returns parsed object or throws descriptive error
async function safeJsonResp(resp) {
    const text = await resp.text();
    try { return JSON.parse(text); } catch(e) {
        console.error('Non-JSON response (' + resp.status + '):', text.substring(0, 300));
        if (resp.status === 403 || text.trim().startsWith('<')) {
            throw new Error('Your hosting firewall/WAF is blocking payment requests. Ask your host to allow outbound connections to payment gateways, or disable mod_security for this site.');
        }
        throw new Error('Server returned an unexpected response. Please try again or contact support.');
    }
}

async function processMpesaPayment() {
    const phone = document.getElementById('mpesaPhone').value.trim();
    if (!phone || phone.length < 10) {
        const errorEl = document.getElementById('mpesaError');
        errorEl.querySelector('span').textContent = 'Enter a valid phone number';
        errorEl.classList.remove('hidden');
        return;
    }
    document.getElementById('mpesaError').classList.add('hidden');
    document.getElementById('mpesaPhone').disabled = true;
    document.getElementById('mpesaPayBtn').disabled = true;
    document.getElementById('mpesaPayBtn').innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Sending...';
    document.getElementById('mpesaStatus').innerHTML = '<div class="flex items-center gap-2 text-green-600"><i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Initiating STK Push...</div>';
    lucide.createIcons();

    try {
        const formData = new FormData(document.getElementById('checkoutForm'));
        formData.set('mpesa_phone', phone);

        const resp = await fetch('/payment/initiate', {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: formData
        });
        const data = await safeJsonResp(resp);

        if (data.success) {
            // Payment pending (gateway not configured) — redirect to orders
            if (data.pending) {
                document.getElementById('mpesaStatus').innerHTML = `
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mt-3">
                        <div class="flex items-center gap-2 text-amber-700 font-medium mb-1">
                            <i data-lucide="info" class="w-5 h-5"></i> Payment Pending
                        </div>
                        <p class="text-amber-600 text-sm">${data.message}</p>
                        <div class="mt-3 flex items-center gap-2 text-amber-500 text-sm">
                            <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Redirecting to your orders...
                        </div>
                    </div>`;
                lucide.createIcons();
                setTimeout(() => { window.location.href = '/account/orders'; }, 1500);
                return;
            }
            document.getElementById('mpesaStatus').innerHTML = `
                <div class="bg-green-50 border border-green-200 rounded-xl p-4 mt-3">
                    <div class="flex items-center gap-2 text-green-700 font-medium mb-1">
                        <i data-lucide="check-circle" class="w-5 h-5"></i> STK Push Sent!
                    </div>
                    <p class="text-green-600 text-sm">Check your phone and enter your PIN to complete payment.</p>
                    <div class="mt-3 flex items-center gap-2 text-green-500 text-sm">
                        <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Waiting for confirmation...
                    </div>
                </div>`;
            lucide.createIcons();
            // Poll for payment status
            pollPaymentStatus(data.order_id);
        } else {
            document.getElementById('mpesaStatus').innerHTML = `
                <div class="bg-red-50 border border-red-200 rounded-xl p-4 mt-3">
                    <div class="flex items-center gap-2 text-red-700 font-medium">
                        <i data-lucide="alert-circle" class="w-5 h-5"></i> ${data.message || 'Payment initiation failed'}
                    </div>
                </div>`;
            lucide.createIcons();
            document.getElementById('mpesaPhone').disabled = false;
            document.getElementById('mpesaPayBtn').disabled = false;
            document.getElementById('mpesaPayBtn').innerHTML = 'Send STK Push <i data-lucide="send" class="w-4 h-4"></i>';
        }
    } catch (e) {
        const errMsg = e.message || 'Network error. Please try again.';
        document.getElementById('mpesaStatus').innerHTML = `
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 mt-3">
                <div class="flex items-center gap-2 text-red-700 font-medium">
                    <i data-lucide="alert-circle" class="w-5 h-5"></i> ${errMsg}
                </div>
            </div>`;
        lucide.createIcons();
        document.getElementById('mpesaPhone').disabled = false;
        document.getElementById('mpesaPayBtn').disabled = false;
        document.getElementById('mpesaPayBtn').innerHTML = 'Send STK Push <i data-lucide="send" class="w-4 h-4"></i>';
    }
}

let pollCount = 0;
let pollTimer = null;
function pollPaymentStatus(orderId) {
    pollCount = 0;
    pollTimer = setInterval(async () => {
        pollCount++;
        if (pollCount > 30) { // 60 seconds timeout
            clearInterval(pollTimer);
            document.getElementById('mpesaStatus').innerHTML += `
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mt-3">
                    <div class="flex items-center gap-2 text-amber-700 font-medium">
                        <i data-lucide="clock" class="w-5 h-5"></i> Payment timed out
                    </div>
                    <p class="text-amber-600 text-sm mt-1">Check your M-Pesa messages or try again.</p>
                    <button onclick="processMpesaPayment()" class="mt-2 text-sm font-medium text-amber-700 underline">Retry</button>
                </div>`;
            lucide.createIcons();
            return;
        }
        try {
            const resp = await fetch('/payment/status?order_id=' + orderId);
            const data = await resp.json();
            if (data.paid) {
                clearInterval(pollTimer);
                window.location.href = '/order-success';
            }
        } catch(e) {}
    }, 2000);
}

function openRedirectModal(method) {
    const info = paymentMethods[method] || { name: method, color: 'gray', icon: 'globe' };
    const styles = colorStyles[info.color] || colorStyles.indigo;

    // Update modal content with gateway-specific styling
    document.getElementById('redirectMethodName').textContent = info.name;

    // Update icon
    const iconContainer = document.getElementById('redirectModalIcon');
    iconContainer.className = 'w-16 h-16 ' + styles.bg + ' rounded-2xl flex items-center justify-center mx-auto mb-4';
    iconContainer.innerHTML = '<i data-lucide="' + info.icon + '" class="w-8 h-8 ' + styles.text + '"></i>';

    // Update amount box
    const amountBox = document.getElementById('redirectAmountBox');
    amountBox.className = styles.bgBox + ' border ' + styles.borderBox + ' rounded-xl p-4 mb-5 text-center';
    amountBox.querySelector('p:first-child').className = 'text-sm ' + styles.textBox + ' font-medium';
    amountBox.querySelector('p:last-child').className = 'text-2xl font-bold ' + styles.textBold + ' mt-1';

    document.getElementById('redirectModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    lucide.createIcons();
}

function closeRedirectModal() {
    document.getElementById('redirectModal').classList.add('hidden');
    document.body.style.overflow = '';
    // Reset button state
    const btn = document.getElementById('redirectPayBtn');
    btn.disabled = false;
    btn.innerHTML = 'Proceed to Payment <i data-lucide="external-link" class="w-4 h-4"></i>';
    lucide.createIcons();
}

async function processRedirectPayment() {
    const btn = document.getElementById('redirectPayBtn');
    btn.disabled = true;
    btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Processing...';
    lucide.createIcons();

    const formData = new FormData(document.getElementById('checkoutForm'));
    try {
        const resp = await fetch('/payment/initiate', {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: formData
        });
        const data = await safeJsonResp(resp);
        if (data.success && data.redirect_url) {
            window.location.href = data.redirect_url;
        } else if (data.success && data.pending) {
            // Payment pending (gateway not configured)
            alert(data.message || 'Payment is pending. You can pay later from your orders page.');
            window.location.href = '/account/orders';
        } else if (data.success) {
            window.location.href = '/order-success';
        } else {
            alert(data.message || 'Payment initiation failed');
            btn.disabled = false;
            btn.innerHTML = 'Proceed to Payment <i data-lucide="external-link" class="w-4 h-4"></i>';
            lucide.createIcons();
        }
    } catch(e) {
        alert(e.message || 'Network error. Please try again.');
        btn.disabled = false;
        btn.innerHTML = 'Proceed to Payment <i data-lucide="external-link" class="w-4 h-4"></i>';
        lucide.createIcons();
    }
}

// ============================================================
// PayPal JS SDK Functions
// ============================================================
function closePaypalSdkModal() {
    document.getElementById('paypalSdkModal').classList.add('hidden');
    document.body.style.overflow = '';
    document.getElementById('paypalSdkStatus').innerHTML = '';
    // Reset button container
    document.getElementById('paypalButtonContainer').innerHTML = `
        <div class="flex items-center gap-2 text-gray-400">
            <i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i>
            <span class="text-sm">Loading PayPal...</span>
        </div>`;
    lucide.createIcons();
}

function loadPaypalSdk() {
    return new Promise((resolve, reject) => {
        if (paypalSdkLoaded && window.paypal) {
            resolve();
            return;
        }
        const script = document.createElement('script');
        script.src = 'https://www.paypal.com/sdk/js?client-id=' + encodeURIComponent(ppClientId)
            + '&currency=' + encodeURIComponent(ppCurrency)
            + '&intent=capture'
            + '&components=buttons';
        script.onload = () => {
            paypalSdkLoaded = true;
            resolve();
        };
        script.onerror = () => reject(new Error('Failed to load PayPal SDK'));
        document.head.appendChild(script);
    });
}

async function openPaypalSdkModal() {
    if (!ppClientId) {
        alert('PayPal is not configured. Please contact the store admin.');
        return;
    }

    document.getElementById('paypalSdkModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    document.getElementById('paypalSdkStatus').innerHTML = '';
    document.getElementById('paypalButtonContainer').innerHTML = `
        <div class="flex items-center gap-2 text-gray-400">
            <i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i>
            <span class="text-sm">Loading PayPal...</span>
        </div>`;
    lucide.createIcons();

    try {
        await loadPaypalSdk();
        renderPaypalButtons();
    } catch (e) {
        document.getElementById('paypalButtonContainer').innerHTML = '';
        document.getElementById('paypalSdkStatus').innerHTML = `
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 mt-3">
                <div class="flex items-center gap-2 text-red-700 font-medium">
                    <i data-lucide="alert-circle" class="w-5 h-5"></i> Could not load PayPal. Please try again.
                </div>
            </div>`;
        lucide.createIcons();
    }
}

function renderPaypalButtons() {
    const container = document.getElementById('paypalButtonContainer');
    container.innerHTML = ''; // Clear loading spinner

    paypal.Buttons({
        style: {
            layout: 'vertical',
            color: 'gold',
            shape: 'rect',
            label: 'paypal',
            height: 45,
        },
        // Called when the buyer clicks the PayPal button
        createOrder: async function() {
            try {
                const formData = new FormData(document.getElementById('checkoutForm'));
                // Add a marker so backend knows this is SDK flow (not redirect)
                formData.append('payment_method', 'paypal');

                const resp = await fetch('/payment/paypal/create-order', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: formData
                });
                const data = await safeJsonResp(resp);

                if (data.success) {
                    // Show converted amount if different currency
                    if (ppCurrency !== 'KES') {
                        document.getElementById('paypalConvertedAmount').textContent =
                            'Approximately ' + ppCurrency + ' ' + parseFloat(data.converted_amount || 0).toFixed(2);
                    }
                    // Store DB order ID for capture
                    container.dataset.dbOrderId = data.order_id;
                    return data.paypal_order_id;
                } else {
                    // Show error in modal
                    document.getElementById('paypalSdkStatus').innerHTML = `
                        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mt-3">
                            <div class="flex items-center gap-2 text-red-700 font-medium">
                                <i data-lucide="alert-circle" class="w-5 h-5"></i> ${data.message || 'Failed to create payment'}
                            </div>
                        </div>`;
                    lucide.createIcons();
                    throw new Error(data.message || 'Failed to create order');
                }
            } catch (err) {
                if (err.message !== 'Failed to create order') {
                    document.getElementById('paypalSdkStatus').innerHTML = `
                        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mt-3">
                            <div class="flex items-center gap-2 text-red-700 font-medium">
                                <i data-lucide="alert-circle" class="w-5 h-5"></i> ${err.message || 'Network error. Please try again.'}
                            </div>
                        </div>`;
                    lucide.createIcons();
                }
                throw err; // Re-throw so PayPal SDK shows its own error
            }
        },
        // Called when the buyer approves the payment
        onApprove: async function(data) {
            const dbOrderId = container.dataset.dbOrderId;
            document.getElementById('paypalButtonContainer').innerHTML = `
                <div class="flex items-center justify-center gap-2 text-amber-600 py-2">
                    <i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i>
                    <span class="text-sm font-medium">Capturing payment...</span>
                </div>`;
            lucide.createIcons();

            try {
                const resp = await fetch('/payment/paypal/capture', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        paypal_order_id: data.orderID,
                        order_id: dbOrderId
                    })
                });
                const result = await safeJsonResp(resp);

                if (result.success) {
                    document.getElementById('paypalButtonContainer').innerHTML = `
                        <div class="flex items-center justify-center gap-2 text-green-600 py-2">
                            <i data-lucide="check-circle" class="w-5 h-5"></i>
                            <span class="text-sm font-medium">Payment successful!</span>
                        </div>`;
                    lucide.createIcons();
                    setTimeout(() => {
                        window.location.href = result.redirect || '/order-success';
                    }, 1000);
                } else {
                    document.getElementById('paypalSdkStatus').innerHTML = `
                        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mt-3">
                            <div class="flex items-center gap-2 text-red-700 font-medium">
                                <i data-lucide="alert-circle" class="w-5 h-5"></i> ${result.message || 'Payment capture failed'}
                            </div>
                            <button onclick="renderPaypalButtons()" class="mt-2 text-sm text-amber-700 underline font-medium">Try Again</button>
                        </div>`;
                    lucide.createIcons();
                }
            } catch (e) {
                document.getElementById('paypalSdkStatus').innerHTML = `
                    <div class="bg-red-50 border border-red-200 rounded-xl p-4 mt-3">
                        <div class="flex items-center gap-2 text-red-700 font-medium">
                            <i data-lucide="alert-circle" class="w-5 h-5"></i> ${e.message || 'Network error. Please try again.'}
                        </div>
                        <button onclick="renderPaypalButtons()" class="mt-2 text-sm text-amber-700 underline font-medium">Try Again</button>
                    </div>`;
                lucide.createIcons();
            }
        },
        // Called when the buyer cancels
        onCancel: function() {
            document.getElementById('paypalSdkStatus').innerHTML = `
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mt-3">
                    <div class="flex items-center gap-2 text-amber-700 font-medium">
                        <i data-lucide="info" class="w-5 h-5"></i> Payment cancelled
                    </div>
                    <p class="text-amber-600 text-sm mt-1">You can try again or choose a different payment method.</p>
                    <button onclick="renderPaypalButtons()" class="mt-2 text-sm text-amber-700 underline font-medium">Try Again</button>
                </div>`;
            lucide.createIcons();
        },
        // Called on errors
        onError: function(err) {
            console.error('PayPal button error:', err);
            document.getElementById('paypalSdkStatus').innerHTML = `
                <div class="bg-red-50 border border-red-200 rounded-xl p-4 mt-3">
                    <div class="flex items-center gap-2 text-red-700 font-medium">
                        <i data-lucide="alert-circle" class="w-5 h-5"></i> Something went wrong
                    </div>
                    <p class="text-red-600 text-sm mt-1">Please try again or contact support.</p>
                    <button onclick="renderPaypalButtons()" class="mt-2 text-sm text-amber-700 underline font-medium">Try Again</button>
                </div>`;
            lucide.createIcons();
        }
    }).render('#paypalButtonContainer');
}

// Close modals on Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeMpesaModal();
        closeRedirectModal();
        closePaypalSdkModal();
    }
});
</script>
<?php endif; ?>