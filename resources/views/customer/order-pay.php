<?php
$order = $order ?? [];
$orderItems = $orderItems ?? [];

// Load active payment methods from settings
$activePaymentMethods = [];
$methodConfigs = [
    'mpesa' => ['label' => 'M-Pesa', 'icon' => 'smartphone', 'color' => 'green', 'desc' => 'Pay via M-Pesa on your phone'],
    'intasend' => ['label' => 'IntaSend', 'icon' => 'zap', 'color' => 'purple', 'desc' => 'Mobile money & card payments'],
    'paypal' => ['label' => 'PayPal', 'icon' => 'globe', 'color' => 'indigo', 'desc' => 'Pay securely with PayPal'],
    'pesapal' => ['label' => 'PesaPal', 'icon' => 'wallet', 'color' => 'orange', 'desc' => 'Multiple payment options'],
    'stripe' => ['label' => 'Stripe', 'icon' => 'credit-card', 'color' => 'indigo', 'desc' => 'Visa, Mastercard & more'],
];
foreach ($methodConfigs as $key => $config) {
    $setting = Database::selectOne("SELECT value FROM settings WHERE `key` = ?", [$key . '_enabled']);
    if ($setting && $setting['value'] === '1') {
        $activePaymentMethods[$key] = $config;
    }
}
// Fallback: show all if none enabled (so user can at least see options)
if (empty($activePaymentMethods)) {
    $activePaymentMethods = $methodConfigs;
}

$colorMap = [
    'green'  => ['bg' => 'bg-green-100', 'text' => 'text-green-600', 'border' => 'border-green-500', 'bgLight' => 'bg-green-50'],
    'purple' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-600', 'border' => 'border-purple-500', 'bgLight' => 'bg-purple-50'],
    'indigo' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-600', 'border' => 'border-indigo-500', 'bgLight' => 'bg-indigo-50'],
    'orange' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-600', 'border' => 'border-orange-500', 'bgLight' => 'bg-orange-50'],
];
?>

<!-- Breadcrumbs -->
<div class="bg-gray-50 border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 py-3">
        <nav class="flex items-center gap-2 text-sm text-gray-500">
            <a href="/" class="hover:text-amber-600 transition-colors">Home</a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <a href="/account/orders" class="hover:text-amber-600 transition-colors">Orders</a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <span class="text-gray-900 font-medium">Pay for Order</span>
        </nav>
    </div>
</div>

<div class="max-w-4xl mx-auto px-4 py-8">
    <!-- Order Summary Card -->
    <div class="bg-white border border-gray-200 rounded-2xl p-6 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wider font-medium mb-1">Pay for Order</p>
                <h1 class="font-heading text-2xl font-bold text-gray-900 font-mono"><?= e($order['order_number']) ?></h1>
                <p class="text-sm text-gray-500 mt-1">Placed on <?= formatDate($order['created_at']) ?></p>
            </div>
            <div class="text-left sm:text-right">
                <p class="text-sm text-gray-500">Amount Due</p>
                <p class="text-2xl font-bold text-gray-900"><?= formatMoney($order['total']) ?></p>
            </div>
        </div>

        <!-- Order Items -->
        <div class="divide-y divide-gray-100">
            <?php foreach ($orderItems as $item): ?>
            <div class="flex items-center gap-3 py-3 first:pt-0 last:pb-0">
                <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center shrink-0">
                    <i data-lucide="package" class="w-5 h-5 text-gray-400"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate"><?= e($item['product_name']) ?></p>
                    <p class="text-xs text-gray-400">Qty: <?= $item['quantity'] ?> x <?= formatMoney($item['price']) ?></p>
                </div>
                <span class="text-sm font-bold text-gray-900 shrink-0"><?= formatMoney($item['total'] ?? $item['price'] * $item['quantity']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($order['notes'])): ?>
        <div class="mt-4 p-3 bg-amber-50 border border-amber-100 rounded-xl">
            <p class="text-xs text-amber-700"><i data-lucide="info" class="w-3 h-3 inline mr-1"></i> <?= e($order['notes']) ?></p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Payment Method Selection -->
    <div class="bg-white border border-gray-200 rounded-2xl p-6">
        <h2 class="font-heading text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
            <i data-lucide="credit-card" class="w-5 h-5 text-amber-600"></i> Select Payment Method
        </h2>

        <form id="payOrderForm">
            <input type="hidden" name="payment_method" id="payMethodInput" value="<?= e($order['payment_method'] ?? 'mpesa') ?>">
            <div class="space-y-3 mb-6">
                <?php $first = true; foreach ($activePaymentMethods as $key => $config):
                    $colors = $colorMap[$config['color']] ?? $colorMap['green'];
                    $isSelected = ($key === ($order['payment_method'] ?? 'mpesa'));
                ?>
                <label class="flex items-center gap-4 p-4 border border-gray-200 rounded-xl cursor-pointer hover:border-amber-300 hover:bg-amber-50/50 transition-colors has-[:checked]:<?= $colors['border'] ?> has-[:checked]:<?= $colors['bgLight'] ?>" data-method="<?= $key ?>">
                    <input type="radio" name="pm" value="<?= $key ?>" <?= $isSelected ? 'checked' : '' ?> class="w-4.5 h-4.5 text-amber-600 focus:ring-amber-500" onchange="document.getElementById('payMethodInput').value=this.value">
                    <div class="w-10 h-10 <?= $colors['bg'] ?> rounded-lg flex items-center justify-center shrink-0">
                        <i data-lucide="<?= $config['icon'] ?>" class="w-5 h-5 <?= $colors['text'] ?>"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-gray-900 text-sm"><?= e($config['label']) ?></p>
                        <p class="text-xs text-gray-500"><?= e($config['desc']) ?></p>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>

            <!-- M-Pesa Phone (shown only for mpesa) -->
            <div id="mpesaPhoneBox" class="mb-6 <?= ($order['payment_method'] ?? 'mpesa') !== 'mpesa' ? 'hidden' : '' ?>">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">M-Pesa Phone Number</label>
                <input type="tel" name="mpesa_phone" id="mpesaPhoneInput" value="<?= e($order['customer_phone'] ?? '') ?>"
                       placeholder="+254 7XX XXX XXX"
                       pattern="^(\+254|0)[17]\d{8}$"
                       class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
            </div>

            <!-- Pay Button -->
            <div class="flex items-center justify-between">
                <a href="/account/orders" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-700 font-medium px-6 py-3 rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Orders
                </a>
                <button type="button" id="payNowBtn" onclick="processOrderPayment()" class="inline-flex items-center gap-2 bg-green-600 text-white font-semibold px-10 py-3.5 rounded-xl hover:bg-green-700 transition-colors shadow-lg shadow-green-600/20">
                    <i data-lucide="lock" class="w-4 h-4"></i> Pay <?= formatMoney($order['total']) ?>
                </button>
            </div>
        </form>

        <!-- Status Area -->
        <div id="payStatus" class="mt-4"></div>
    </div>
</div>

<!-- M-Pesa Modal -->
<div id="mpesaPayModal" class="hidden fixed inset-0 z-50">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeMpesaPayModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
            <button onclick="closeMpesaPayModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-green-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="smartphone" class="w-8 h-8 text-green-600"></i>
                </div>
                <h3 class="font-heading text-xl font-bold text-gray-900">Pay with M-Pesa</h3>
                <p class="text-gray-500 text-sm mt-1">Complete payment for <?= e($order['order_number']) ?></p>
            </div>
            <div class="bg-green-50 border border-green-100 rounded-xl p-4 mb-5 text-center">
                <p class="text-sm text-green-600 font-medium">Amount to Pay</p>
                <p class="text-2xl font-bold text-green-700 mt-1">KSh <?= number_format(round($order['total'])) ?></p>
                <?php if ($order['total'] != round($order['total'])): ?>
                <p class="text-xs text-green-500 mt-1">M-Pesa rounds to the nearest shilling (was <?= formatMoney($order['total']) ?>)</p>
                <?php endif; ?>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">M-Pesa Phone Number</label>
                <input type="tel" id="mpesaPhoneModal" value="<?= e($order['customer_phone'] ?? '') ?>"
                       placeholder="+254 7XX XXX XXX"
                       class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                       onkeydown="if(event.key==='Enter')processMpesaPay()">
            </div>
            <button id="mpesaPayModalBtn" onclick="processMpesaPay()" class="w-full inline-flex items-center justify-center gap-2 bg-green-600 text-white font-semibold px-6 py-3.5 rounded-xl hover:bg-green-700 transition-colors">
                Send STK Push <i data-lucide="send" class="w-4 h-4"></i>
            </button>
            <div id="mpesaPayStatus" class="mt-4"></div>
        </div>
    </div>
</div>

<script>
const orderId = <?= (int)$order['id'] ?>;

// Toggle M-Pesa phone input
document.querySelectorAll('input[name="pm"]').forEach(radio => {
    radio.addEventListener('change', () => {
        document.getElementById('mpesaPhoneBox').classList.toggle('hidden', radio.value !== 'mpesa');
    });
});

function processOrderPayment() {
    const method = document.getElementById('payMethodInput').value;
    if (method === 'mpesa') {
        // Open M-Pesa modal
        document.getElementById('mpesaPayModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        lucide.createIcons();
        setTimeout(() => document.getElementById('mpesaPhoneModal').focus(), 200);
    } else {
        // Redirect payment
        initiatePay(method);
    }
}

function closeMpesaPayModal() {
    document.getElementById('mpesaPayModal').classList.add('hidden');
    document.body.style.overflow = '';
    document.getElementById('mpesaPayStatus').innerHTML = '';
    const btn = document.getElementById('mpesaPayModalBtn');
    btn.disabled = false;
    btn.innerHTML = 'Send STK Push <i data-lucide="send" class="w-4 h-4"></i>';
    lucide.createIcons();
}

async function processMpesaPay() {
    const phone = document.getElementById('mpesaPhoneModal').value.trim();
    if (!phone || phone.length < 10) { alert('Enter a valid phone number'); return; }

    const btn = document.getElementById('mpesaPayModalBtn');
    btn.disabled = true;
    btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Sending...';
    document.getElementById('mpesaPayStatus').innerHTML = '<div class="flex items-center gap-2 text-green-600"><i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Initiating STK Push...</div>';
    lucide.createIcons();

    const formData = new FormData();
    formData.append('payment_method', 'mpesa');
    formData.append('mpesa_phone', phone);
    formData.append('amount', '<?= (float)$order['total'] ?>');

    try {
        const resp = await fetch('/order/pay/' + orderId, {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: formData
        });
        const text = await resp.text();
        let data;
        try { data = JSON.parse(text); } catch(e) {
            console.error('Non-JSON response:', text.substring(0, 200));
            throw new Error('Server error: firewall may be blocking the request. Contact admin.');
        }

        if (data.success) {
            document.getElementById('mpesaPayStatus').innerHTML = `
                <div class="bg-green-50 border border-green-200 rounded-xl p-4 mt-3">
                    <div class="flex items-center gap-2 text-green-700 font-medium mb-1">
                        <i data-lucide="check-circle" class="w-5 h-5"></i> STK Push Sent!
                    </div>
                    <p class="text-green-600 text-sm">Check your phone and enter your PIN.</p>
                    <div class="mt-3 flex items-center gap-2 text-green-500 text-sm">
                        <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Waiting for confirmation...
                    </div>
                </div>`;
            lucide.createIcons();
            pollOrderPayment(orderId);
        } else {
            document.getElementById('mpesaPayStatus').innerHTML = `
                <div class="bg-red-50 border border-red-200 rounded-xl p-4 mt-3">
                    <div class="flex items-center gap-2 text-red-700 font-medium">
                        <i data-lucide="alert-circle" class="w-5 h-5"></i> ${data.message || 'Payment failed'}
                    </div>
                </div>`;
            lucide.createIcons();
            btn.disabled = false;
            btn.innerHTML = 'Send STK Push <i data-lucide="send" class="w-4 h-4"></i>';
            lucide.createIcons();
        }
    } catch(e) {
        document.getElementById('mpesaPayStatus').innerHTML = `
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 mt-3">
                <div class="flex items-center gap-2 text-red-700 font-medium">
                    <i data-lucide="alert-circle" class="w-5 h-5"></i> ${e.message || 'Network error'}
                </div>
            </div>`;
        lucide.createIcons();
        btn.disabled = false;
        btn.innerHTML = 'Send STK Push <i data-lucide="send" class="w-4 h-4"></i>';
        lucide.createIcons();
    }
}

async function initiatePay(method) {
    const btn = document.getElementById('payNowBtn');
    btn.disabled = true;
    btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Processing...';
    lucide.createIcons();

    const formData = new FormData();
    formData.append('payment_method', method);
    formData.append('amount', '<?= (float)$order['total'] ?>');

    try {
        const resp = await fetch('/order/pay/' + orderId, {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: formData
        });
        const text = await resp.text();
        let data;
        try { data = JSON.parse(text); } catch(e) {
            // Server returned HTML instead of JSON (WAF/firewall block)
            console.error('Non-JSON response:', text.substring(0, 200));
            throw new Error('Server error: your hosting firewall may be blocking payment requests. Please contact the site admin.');
        }

        if (data.success && data.redirect_url) {
            window.location.href = data.redirect_url;
        } else if (data.success) {
            window.location.href = '/account/orders';
        } else {
            alert(data.message || 'Payment failed');
            btn.disabled = false;
            btn.innerHTML = '<i data-lucide="lock" class="w-4 h-4"></i> Pay <?= formatMoney($order['total']) ?>';
            lucide.createIcons();
        }
    } catch(e) {
        alert(e.message || 'Network error');
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="lock" class="w-4 h-4"></i> Pay <?= formatMoney($order['total']) ?>';
        lucide.createIcons();
    }
}

let pollCount = 0;
function pollOrderPayment(oid) {
    pollCount = 0;
    const timer = setInterval(async () => {
        pollCount++;
        if (pollCount > 30) {
            clearInterval(timer);
            document.getElementById('mpesaPayStatus').innerHTML += `
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mt-3">
                    <div class="flex items-center gap-2 text-amber-700 font-medium">
                        <i data-lucide="clock" class="w-5 h-5"></i> Payment timed out
                    </div>
                    <p class="text-amber-600 text-sm mt-1">Check your M-Pesa messages or try again.</p>
                    <button onclick="closeMpesaPayModal()" class="mt-2 text-sm font-medium text-amber-700 underline">Close</button>
                </div>`;
            lucide.createIcons();
            return;
        }
        try {
            const resp = await fetch('/payment/status?order_id=' + oid);
            const data = await resp.json();
            if (data.paid) {
                clearInterval(timer);
                window.location.href = '/account/orders/' + oid + '/track';
            }
        } catch(e) {}
    }, 2000);
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeMpesaPayModal();
});
</script>