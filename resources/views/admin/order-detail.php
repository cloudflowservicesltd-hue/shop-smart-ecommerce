<?php
$statusColors = [
    'pending' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
    'paid' => 'bg-green-100 text-green-700 border-green-200',
    'processing' => 'bg-blue-100 text-blue-700 border-blue-200',
    'shipped' => 'bg-indigo-100 text-indigo-700 border-indigo-200',
    'delivered' => 'bg-amber-100 text-amber-700 border-amber-200',
    'completed' => 'bg-amber-100 text-amber-700 border-amber-200',
    'cancelled' => 'bg-red-100 text-red-700 border-red-200',
    'failed' => 'bg-red-100 text-red-700 border-red-200',
];

$statusIcons = [
    'pending' => 'clock',
    'paid' => 'check-circle',
    'processing' => 'loader',
    'shipped' => 'truck',
    'delivered' => 'package-check',
    'completed' => 'package-check',
    'cancelled' => 'x-circle',
    'failed' => 'alert-triangle',
];

$paymentStatusColors = [
    'paid' => 'text-green-600 bg-green-50',
    'pending' => 'text-yellow-600 bg-yellow-50',
    'failed' => 'text-red-600 bg-red-50',
];

$currentStatus = $order['status'] ?? 'pending';
$currentPaymentStatus = $order['payment_status'] ?? 'pending';
?>

<div class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="/admin/orders" class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-amber-600 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Back to Orders
            </a>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            <?php if (!empty($order['is_pos'])): ?>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-purple-50 text-purple-700 border border-purple-200">
                    <i data-lucide="monitor" class="w-3.5 h-3.5"></i> POS Order
                </span>
            <?php else: ?>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">
                    <i data-lucide="globe" class="w-3.5 h-3.5"></i> Online Order
                </span>
            <?php endif; ?>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border <?= $statusColors[$currentStatus] ?? 'bg-gray-100 text-gray-700 border-gray-200' ?>">
                <i data-lucide="<?= $statusIcons[$currentStatus] ?? 'circle' ?>" class="w-3.5 h-3.5"></i>
                <?= ucfirst($currentStatus) ?>
            </span>
        </div>
    </div>

    <!-- Order Title -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
            <div>
                <h1 class="font-heading font-bold text-2xl text-gray-900 font-mono"><?= e($order['order_number']) ?></h1>
                <p class="text-sm text-gray-500 mt-1">
                    Placed on <?= formatDate($order['created_at']) ?>
                    <?php if ($order['updated_at'] !== $order['created_at']): ?>
                        &middot; Last updated <?= formatDate($order['updated_at']) ?>
                    <?php endif; ?>
                </p>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-500">Order Total</p>
                <p class="text-3xl font-bold text-gray-900"><?= formatMoney($order['total']) ?></p>
            </div>
        </div>
    </div>

    <!-- Status Update Section -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <h2 class="font-heading font-semibold text-base text-gray-900 mb-4 flex items-center gap-2">
            <i data-lucide="refresh-cw" class="w-4 h-4 text-amber-600"></i>
            Update Order Status
        </h2>
        <form method="POST" action="/admin/orders/<?= $order['id'] ?>/status" class="flex flex-col sm:flex-row sm:items-end gap-4">
            <?= csrf() ?>
            <div class="flex-1">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1.5">Order Status</label>
                <select name="status" id="status" class="w-full sm:w-64 px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent bg-white">
                    <?php foreach (['pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'failed'] as $s): ?>
                        <option value="<?= $s ?>" <?= $currentStatus === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center justify-center gap-2 whitespace-nowrap">
                <i data-lucide="check" class="w-4 h-4"></i>
                Update Status
            </button>
        </form>
    </div>

    <!-- Order Items Table -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-heading font-semibold text-base text-gray-900 flex items-center gap-2">
                <i data-lucide="shopping-bag" class="w-4 h-4 text-amber-600"></i>
                Order Items
                <span class="text-sm font-normal text-gray-500">(<?= count($items) ?>)</span>
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="text-left px-6 py-3 font-medium text-gray-600">#</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-600">Product</th>
                        <th class="text-center px-6 py-3 font-medium text-gray-600">Qty</th>
                        <th class="text-right px-6 py-3 font-medium text-gray-600">Unit Price</th>
                        <th class="text-right px-6 py-3 font-medium text-gray-600">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($items as $i => $item): ?>
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-6 py-4 text-gray-400 text-xs"><?= $i + 1 ?></td>
                        <td class="px-6 py-4">
                            <p class="font-medium text-gray-900"><?= e($item['product_name']) ?></p>
                            <?php if (!empty($item['sku'])): ?>
                                <p class="text-xs text-gray-400 mt-0.5">SKU: <?= e($item['sku']) ?></p>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-center text-gray-700"><?= (int)$item['quantity'] ?></td>
                        <td class="px-6 py-4 text-right text-gray-700"><?= formatMoney($item['price']) ?></td>
                        <td class="px-6 py-4 text-right font-medium text-gray-900"><?= formatMoney($item['total']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                            <i data-lucide="package-x" class="w-10 h-10 mx-auto mb-2 text-gray-300"></i>
                            <p>No items found for this order</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($items)): ?>
                <tfoot>
                    <tr class="border-t border-gray-200">
                        <td colspan="4" class="px-6 py-3 text-sm text-gray-500 text-right">Subtotal</td>
                        <td class="px-6 py-3 text-sm font-medium text-gray-700 text-right"><?= formatMoney($order['subtotal']) ?></td>
                    </tr>
                    <?php if ($order['tax'] > 0): ?>
                    <tr class="border-t border-gray-100">
                        <td colspan="4" class="px-6 py-3 text-sm text-gray-500 text-right">Tax</td>
                        <td class="px-6 py-3 text-sm font-medium text-gray-700 text-right"><?= formatMoney($order['tax']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($order['discount'] > 0): ?>
                    <tr class="border-t border-gray-100">
                        <td colspan="4" class="px-6 py-3 text-sm text-gray-500 text-right">Discount</td>
                        <td class="px-6 py-3 text-sm font-medium text-red-600 text-right">-<?= formatMoney($order['discount']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($order['shipping_cost'] > 0): ?>
                    <tr class="border-t border-gray-100">
                        <td colspan="4" class="px-6 py-3 text-sm text-gray-500 text-right">Shipping</td>
                        <td class="px-6 py-3 text-sm font-medium text-gray-700 text-right"><?= formatMoney($order['shipping_cost']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="border-t-2 border-gray-300 bg-gray-50/50">
                        <td colspan="4" class="px-6 py-4 text-base font-semibold text-gray-900 text-right">Grand Total</td>
                        <td class="px-6 py-4 text-base font-bold text-amber-700 text-right"><?= formatMoney($order['total']) ?></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <!-- Info Cards Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Customer Info Card -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <h2 class="font-heading font-semibold text-base text-gray-900 mb-4 flex items-center gap-2">
                <i data-lucide="user" class="w-4 h-4 text-amber-600"></i>
                Customer Information
            </h2>
            <div class="space-y-3">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center shrink-0">
                        <span class="text-amber-700 font-bold text-sm"><?= strtoupper(substr($order['customer_name'] ?? '?', 0, 1)) ?></span>
                    </div>
                    <div class="min-w-0">
                        <p class="font-medium text-gray-900"><?= e($order['customer_name'] ?? 'Walk-in Customer') ?></p>
                        <?php if (!empty($order['customer_id'])): ?>
                            <a href="/admin/customers" class="text-xs text-amber-600 hover:text-amber-700 flex items-center gap-1 mt-0.5">
                                <i data-lucide="external-link" class="w-3 h-3"></i>
                                View Customer Profile
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-3 space-y-2.5">
                    <?php if (!empty($order['customer_email'])): ?>
                    <div class="flex items-center gap-2.5 text-sm">
                        <i data-lucide="mail" class="w-4 h-4 text-gray-400 shrink-0"></i>
                        <a href="mailto:<?= e($order['customer_email']) ?>" class="text-gray-700 hover:text-amber-600 truncate"><?= e($order['customer_email']) ?></a>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($order['customer_phone'])): ?>
                    <div class="flex items-center gap-2.5 text-sm">
                        <i data-lucide="phone" class="w-4 h-4 text-gray-400 shrink-0"></i>
                        <a href="tel:<?= e($order['customer_phone']) ?>" class="text-gray-700 hover:text-amber-600"><?= e($order['customer_phone']) ?></a>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($order['customer_address'])): ?>
                    <div class="flex items-start gap-2.5 text-sm">
                        <i data-lucide="map-pin" class="w-4 h-4 text-gray-400 shrink-0 mt-0.5"></i>
                        <p class="text-gray-700 whitespace-pre-line"><?= e($order['customer_address']) ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($order['shipping_latitude']) && !empty($order['shipping_longitude'])): ?>
                    <div class="flex items-center gap-2.5 text-sm">
                        <i data-lucide="navigation" class="w-4 h-4 text-gray-400 shrink-0"></i>
                        <a href="https://www.google.com/maps?q=<?= e($order['shipping_latitude']) ?>,<?= e($order['shipping_longitude']) ?>" target="_blank" class="text-amber-600 hover:text-amber-700 font-medium flex items-center gap-1.5">
                            <span class="font-mono text-xs text-gray-500"><?= e($order['shipping_latitude']) ?>, <?= e($order['shipping_longitude']) ?></span>
                            <i data-lucide="external-link" class="w-3 h-3"></i> View on Map
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($customer): ?>
                <div class="border-t border-gray-100 pt-3">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Registered Account</p>
                    <div class="flex items-center gap-2.5 text-sm">
                        <i data-lucide="shield-check" class="w-4 h-4 text-amber-500 shrink-0"></i>
                        <span class="text-gray-700"><?= e($customer['name']) ?></span>
                    </div>
                    <div class="flex items-center gap-2.5 text-sm mt-1.5">
                        <i data-lucide="mail" class="w-4 h-4 text-gray-400 shrink-0"></i>
                        <span class="text-gray-500 text-xs"><?= e($customer['email']) ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Payment Info Card -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <h2 class="font-heading font-semibold text-base text-gray-900 mb-4 flex items-center gap-2">
                <i data-lucide="credit-card" class="w-4 h-4 text-amber-600"></i>
                Payment Information
            </h2>
            <div class="space-y-3">
                <!-- Payment Method -->
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">Payment Method</span>
                    <span class="text-sm font-medium text-gray-900 flex items-center gap-1.5">
                        <?php
                        $methodIcons = [
                            'mpesa' => 'smartphone',
                            'card' => 'credit-card',
                            'cash' => 'banknote',
                            'pesapal' => 'credit-card',
                            'intasend' => 'zap',
                        ];
                        $methodKey = strtolower($order['payment_method'] ?? '');
                        $mIcon = $methodIcons[$methodKey] ?? 'credit-card';
                        ?>
                        <i data-lucide="<?= $mIcon ?>" class="w-4 h-4 text-gray-400"></i>
                        <?= ucfirst($order['payment_method'] ?? 'N/A') ?>
                    </span>
                </div>

                <!-- Payment Status -->
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">Payment Status</span>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold <?= $paymentStatusColors[$currentPaymentStatus] ?? 'text-gray-600 bg-gray-50' ?>">
                            <?php if ($currentPaymentStatus === 'paid'): ?>
                                <i data-lucide="check-circle" class="w-3.5 h-3.5 mr-1"></i>
                            <?php elseif ($currentPaymentStatus === 'pending'): ?>
                                <i data-lucide="clock" class="w-3.5 h-3.5 mr-1"></i>
                            <?php else: ?>
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5 mr-1"></i>
                            <?php endif; ?>
                            <?= ucfirst($currentPaymentStatus) ?>
                        </span>
                        <?php if ($currentPaymentStatus === 'pending' && $currentStatus !== 'cancelled' && $currentStatus !== 'failed'): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-yellow-100 text-yellow-700 border border-yellow-200">
                            <i data-lucide="clock" class="w-3 h-3 mr-0.5"></i>Awaiting Payment
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Payment Pending Reason (from order notes) -->
                <?php if ($currentPaymentStatus === 'pending' && !empty($order['notes']) && strpos($order['notes'], 'Payment pending') === 0): ?>
                <div class="bg-yellow-50 border border-yellow-100 rounded-lg p-3 mt-1">
                    <p class="text-xs text-yellow-700 flex items-start gap-1.5">
                        <i data-lucide="info" class="w-3.5 h-3.5 shrink-0 mt-0.5"></i>
                        <?= e($order['notes']) ?>
                    </p>
                </div>
                <?php endif; ?>

                <!-- Payment Reference -->
                <?php if (!empty($order['payment_reference'])): ?>
                <div class="border-t border-gray-100 pt-3">
                    <p class="text-xs text-gray-500 mb-1">Transaction Reference</p>
                    <p class="font-mono text-sm font-medium text-gray-900 bg-gray-50 px-3 py-2 rounded-lg select-all"><?= e($order['payment_reference']) ?></p>
                </div>
                <?php endif; ?>

                <!-- POS Cash Details -->
                <?php if (!empty($order['is_pos']) && $order['payment_method'] === 'cash'): ?>
                <div class="border-t border-gray-100 pt-3 space-y-2.5">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Cash Payment Details</p>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Amount Received</span>
                        <span class="font-medium text-gray-900"><?= formatMoney($order['amount_received'] ?? 0) ?></span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Change Due</span>
                        <span class="font-semibold text-amber-600"><?= formatMoney($order['change_amount'] ?? 0) ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Transaction Records -->
                <?php if (!empty($transactions)): ?>
                <div class="border-t border-gray-100 pt-3">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2.5">Transaction Records</p>
                    <div class="space-y-2 max-h-48 overflow-y-auto">
                        <?php foreach ($transactions as $tx): ?>
                        <div class="bg-gray-50 rounded-lg p-3 space-y-1.5">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-medium text-gray-700"><?= e($tx['gateway'] ?? $tx['payment_method'] ?? 'Transaction') ?></span>
                                <span class="text-xs font-medium <?= ($tx['status'] ?? '') === 'completed' || ($tx['status'] ?? '') === 'success' || ($tx['status'] ?? '') === 'paid' ? 'text-green-600' : (($tx['status'] ?? '') === 'failed' ? 'text-red-600' : 'text-yellow-600') ?>">
                                    <?= ucfirst($tx['status'] ?? 'pending') ?>
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <span class="font-mono"><?= e($tx['reference'] ?? '-') ?></span>
                                <span><?= formatMoney($tx['amount'] ?? 0) ?></span>
                            </div>
                            <?php if (!empty($tx['created_at'])): ?>
                            <p class="text-xs text-gray-400"><?= date('M j, Y g:i A', strtotime($tx['created_at'])) ?></p>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Notes & Timeline Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Order Notes -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <h2 class="font-heading font-semibold text-base text-gray-900 mb-4 flex items-center gap-2">
                <i data-lucide="file-text" class="w-4 h-4 text-amber-600"></i>
                Order Notes
            </h2>

            <!-- Current Notes Display -->
            <?php if (!empty($order['notes'])): ?>
            <div class="mb-4 bg-amber-50 border border-amber-200 rounded-lg p-4">
                <div class="flex items-start gap-2">
                    <i data-lucide="sticky-note" class="w-4 h-4 text-amber-600 shrink-0 mt-0.5"></i>
                    <p class="text-sm text-gray-700 whitespace-pre-wrap"><?= e($order['notes']) ?></p>
                </div>
            </div>
            <?php else: ?>
            <div class="mb-4 bg-gray-50 border border-gray-100 rounded-lg p-4 text-center">
                <i data-lucide="file-text" class="w-8 h-8 mx-auto text-gray-300 mb-2"></i>
                <p class="text-sm text-gray-400">No notes on this order</p>
            </div>
            <?php endif; ?>

            <!-- Notes Form -->
            <form method="POST" action="/admin/orders/<?= $order['id'] ?>/notes">
                <?= csrf() ?>
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-1.5">
                    <?= !empty($order['notes']) ? 'Update Notes' : 'Add Notes' ?>
                </label>
                <textarea
                    name="notes"
                    id="notes"
                    rows="3"
                    placeholder="Add internal notes about this order..."
                    class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent resize-none"
                ><?= e($order['notes'] ?? '') ?></textarea>
                <button type="submit" class="mt-2 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-1.5">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    Save Notes
                </button>
            </form>
        </div>

        <!-- Timeline / Activity -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <h2 class="font-heading font-semibold text-base text-gray-900 mb-4 flex items-center gap-2">
                <i data-lucide="activity" class="w-4 h-4 text-amber-600"></i>
                Order Timeline
            </h2>
            <div class="relative">
                <!-- Timeline line -->
                <div class="absolute left-[15px] top-2 bottom-2 w-0.5 bg-gray-200"></div>

                <div class="space-y-5">
                    <!-- Order Placed -->
                    <div class="relative flex gap-4 pl-2">
                        <div class="w-[30px] h-[30px] bg-amber-100 rounded-full flex items-center justify-center shrink-0 z-10 border-2 border-white">
                            <i data-lucide="shopping-cart" class="w-3.5 h-3.5 text-amber-600"></i>
                        </div>
                        <div class="pt-0.5">
                            <p class="text-sm font-medium text-gray-900">Order Placed</p>
                            <p class="text-xs text-gray-500"><?= date('F j, Y \a\t g:i A', strtotime($order['created_at'])) ?></p>
                        </div>
                    </div>

                    <!-- Payment -->
                    <?php if ($currentPaymentStatus === 'paid' || $currentStatus === 'paid' || $currentStatus === 'completed' || $currentStatus === 'processing' || $currentStatus === 'shipped' || $currentStatus === 'delivered'): ?>
                    <div class="relative flex gap-4 pl-2">
                        <div class="w-[30px] h-[30px] bg-green-100 rounded-full flex items-center justify-center shrink-0 z-10 border-2 border-white">
                            <i data-lucide="credit-card" class="w-3.5 h-3.5 text-green-600"></i>
                        </div>
                        <div class="pt-0.5">
                            <p class="text-sm font-medium text-gray-900">Payment Confirmed</p>
                            <p class="text-xs text-gray-500">via <?= ucfirst($order['payment_method'] ?? 'N/A') ?><?php if (!empty($order['payment_reference'])): ?> &middot; Ref: <?= e($order['payment_reference']) ?><?php endif; ?></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Awaiting Payment -->
                    <?php if ($currentPaymentStatus === 'pending' && $currentStatus !== 'cancelled' && $currentStatus !== 'failed'): ?>
                    <div class="relative flex gap-4 pl-2">
                        <div class="w-[30px] h-[30px] bg-yellow-100 rounded-full flex items-center justify-center shrink-0 z-10 border-2 border-white">
                            <i data-lucide="clock" class="w-3.5 h-3.5 text-yellow-600"></i>
                        </div>
                        <div class="pt-0.5">
                            <p class="text-sm font-medium text-yellow-700">Awaiting Payment</p>
                            <p class="text-xs text-gray-500">Customer can pay from their orders page<?php if (!empty($order['notes']) && strpos($order['notes'], 'Payment pending') === 0): ?> &middot; <?= e($order['notes']) ?><?php endif; ?></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Payment Failed -->
                    <?php if ($currentPaymentStatus === 'failed' && $currentStatus === 'failed'): ?>
                    <div class="relative flex gap-4 pl-2">
                        <div class="w-[30px] h-[30px] bg-red-100 rounded-full flex items-center justify-center shrink-0 z-10 border-2 border-white">
                            <i data-lucide="alert-triangle" class="w-3.5 h-3.5 text-red-600"></i>
                        </div>
                        <div class="pt-0.5">
                            <p class="text-sm font-medium text-red-700">Payment Failed</p>
                            <p class="text-xs text-gray-500"><?= e($order['notes'] ?? 'Payment could not be processed') ?></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Processing -->
                    <?php if (in_array($currentStatus, ['processing', 'shipped', 'delivered', 'completed'])): ?>
                    <div class="relative flex gap-4 pl-2">
                        <div class="w-[30px] h-[30px] bg-blue-100 rounded-full flex items-center justify-center shrink-0 z-10 border-2 border-white">
                            <i data-lucide="loader" class="w-3.5 h-3.5 text-blue-600"></i>
                        </div>
                        <div class="pt-0.5">
                            <p class="text-sm font-medium text-gray-900">Order Processing</p>
                            <p class="text-xs text-gray-500">Being prepared for fulfillment</p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Shipped -->
                    <?php if (in_array($currentStatus, ['shipped', 'delivered'])): ?>
                    <div class="relative flex gap-4 pl-2">
                        <div class="w-[30px] h-[30px] bg-indigo-100 rounded-full flex items-center justify-center shrink-0 z-10 border-2 border-white">
                            <i data-lucide="truck" class="w-3.5 h-3.5 text-indigo-600"></i>
                        </div>
                        <div class="pt-0.5">
                            <p class="text-sm font-medium text-gray-900">Order Shipped</p>
                            <p class="text-xs text-gray-500">Package is in transit</p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Delivered / Completed -->
                    <?php if (in_array($currentStatus, ['delivered', 'completed'])): ?>
                    <div class="relative flex gap-4 pl-2">
                        <div class="w-[30px] h-[30px] bg-amber-100 rounded-full flex items-center justify-center shrink-0 z-10 border-2 border-white">
                            <i data-lucide="package-check" class="w-3.5 h-3.5 text-amber-600"></i>
                        </div>
                        <div class="pt-0.5">
                            <p class="text-sm font-medium text-gray-900"><?= $currentStatus === 'delivered' ? 'Order Delivered' : 'Order Completed' ?></p>
                            <p class="text-xs text-gray-500">Successfully <?= $currentStatus === 'delivered' ? 'delivered to customer' : 'completed' ?></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Cancelled -->
                    <?php if ($currentStatus === 'cancelled'): ?>
                    <div class="relative flex gap-4 pl-2">
                        <div class="w-[30px] h-[30px] bg-red-100 rounded-full flex items-center justify-center shrink-0 z-10 border-2 border-white">
                            <i data-lucide="x-circle" class="w-3.5 h-3.5 text-red-600"></i>
                        </div>
                        <div class="pt-0.5">
                            <p class="text-sm font-medium text-red-700">Order Cancelled</p>
                            <p class="text-xs text-gray-500">Last updated <?= date('F j, Y \a\t g:i A', strtotime($order['updated_at'])) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Last Updated (if different from created) -->
                    <?php if ($order['updated_at'] && $order['updated_at'] !== $order['created_at'] && !in_array($currentStatus, ['delivered', 'completed', 'cancelled'])): ?>
                    <div class="relative flex gap-4 pl-2">
                        <div class="w-[30px] h-[30px] bg-gray-100 rounded-full flex items-center justify-center shrink-0 z-10 border-2 border-white">
                            <i data-lucide="clock" class="w-3.5 h-3.5 text-gray-500"></i>
                        </div>
                        <div class="pt-0.5">
                            <p class="text-sm font-medium text-gray-900">Last Updated</p>
                            <p class="text-xs text-gray-500"><?= date('F j, Y \a\t g:i A', strtotime($order['updated_at'])) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>