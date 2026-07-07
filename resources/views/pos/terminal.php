<?php
try {
    $taxRate = (float)(Database::selectOne("SELECT value FROM settings WHERE `key` = 'tax_rate'")['value'] ?? 16);
} catch (\Throwable $e) {
    $taxRate = 16;
}
try {
    $posCurrencySymbol = Database::selectOne("SELECT value FROM settings WHERE `key` = 'currency_symbol'")['value'] ?? 'KSh';
    if (empty($posCurrencySymbol)) $posCurrencySymbol = 'KSh';
} catch (\Throwable $e) {
    $posCurrencySymbol = 'KSh';
}
$cart = Session::get('pos_cart', []);
$cartTotal = array_sum(array_column($cart, 'subtotal'));
$taxAmount = $cartTotal * ($taxRate / 100);
$grandTotal = $cartTotal + $taxAmount;
$cashier = Auth::user()['name'] ?? 'Cashier';
$storeName = Database::selectOne("SELECT value FROM settings WHERE `key` = 'store_name'")['value'] ?? 'ShopSmart';
$storePhone = Database::selectOne("SELECT value FROM settings WHERE `key` = 'store_phone'")['value'] ?? '+254 700 000 000';
$storeAddr = Database::selectOne("SELECT value FROM settings WHERE `key` = 'store_address'")['value'] ?? 'Nairobi, Kenya';
$storeLogo = Database::selectOne("SELECT value FROM settings WHERE `key` = 'store_logo'")['value'] ?? '';

// Build POS payment methods: Cash is always first, then enabled gateways + custom methods
$posPayMethods = [
    ['slug' => 'cash', 'name' => 'Cash', 'icon' => 'banknote', 'color' => 'amber'],
];

// Add enabled gateway methods for POS
$posGatewayMap = [
    'mpesa_enabled' => ['slug' => 'mpesa', 'name' => 'M-Pesa (Daraja)', 'icon' => 'smartphone', 'color' => 'green'],
    'cloudone_enabled' => ['slug' => 'cloudone', 'name' => 'CloudOne', 'icon' => 'zap', 'color' => 'teal'],
    'card_enabled' => ['slug' => 'card', 'name' => 'Card', 'icon' => 'credit-card', 'color' => 'purple'],
    'pesapal_enabled' => ['slug' => 'pesapal', 'name' => 'PesaPal', 'icon' => 'wallet', 'color' => 'orange'],
];
foreach ($posGatewayMap as $settingKey => $method) {
    // Auto-enable CloudOne on first POS load if setting doesn't exist
    if ($settingKey === 'cloudone_enabled') {
        $enabled = Database::selectOne("SELECT value FROM settings WHERE `key` = ?", [$settingKey]);
        if (!$enabled || empty($enabled['value'])) {
            try {
                Database::insert('settings', ['key' => 'cloudone_enabled', 'value' => '1', 'group_name' => 'payment', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
            } catch (\Throwable $e) {}
            $posPayMethods[] = $method;
            continue;
        }
    }
    $enabled = Database::selectOne("SELECT value FROM settings WHERE `key` = ?", [$settingKey]);
    if ($enabled && $enabled['value'] === '1') {
        $posPayMethods[] = $method;
    }
}

// Add custom payment methods
try {
    $customMethods = Database::select("SELECT * FROM custom_payment_methods WHERE is_active = 1 ORDER BY sort_order ASC");
    foreach ($customMethods as $cm) {
        $posPayMethods[] = [
            'slug' => $cm['slug'],
            'name' => $cm['name'],
            'icon' => $cm['icon'] ?: 'credit-card',
            'color' => $cm['color'] ?: 'gray',
        ];
    }
} catch (\Throwable $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Terminal - <?= e($storeName) ?></title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{fontFamily:{sans:['Inter','system-ui','sans-serif'],heading:['Poppins','sans-serif']}}}}</script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>
        body{font-family:'Inter',system-ui,sans-serif}
        h1,h2,h3,.font-heading{font-family:'Poppins',sans-serif}
        .scrollbar-thin::-webkit-scrollbar{width:5px}
        .scrollbar-thin::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:3px}
        .modal-overlay{animation:fadeIn .15s ease}
        .modal-content{animation:slideUp .2s ease}
        @keyframes fadeIn{from{opacity:0}to{opacity:1}}
        @keyframes slideUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
        @media print{
            body *{visibility:hidden}
            #receiptPrint,#receiptPrint *{visibility:visible}
            #receiptPrint{position:absolute;left:0;top:0;width:80mm;padding:4mm;font-size:11px;color:#000;background:#fff}
            #receiptPrint h2{font-size:14px}
            .no-print{display:none!important}
        }
    </style>
</head>
<body class="bg-gray-100 h-screen flex flex-col">
    <!-- Header -->
    <header class="bg-gray-900 text-white px-4 py-2.5 flex items-center justify-between shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-amber-600 rounded-lg flex items-center justify-center"><i data-lucide="shopping-bag" class="w-4 h-4"></i></div>
            <div>
                <h1 class="text-sm font-heading font-bold">POS Terminal</h1>
                <p class="text-[10px] text-gray-400"><?= e($cashier) ?> | <?= date('l, M d, Y') ?></p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <div id="commissionBadge" class="hidden bg-amber-500/20 border border-amber-500/30 rounded-lg px-3 py-1.5 cursor-pointer hover:bg-amber-500/30 transition-colors" onclick="showCommissionDetail()">
                <div class="flex items-center gap-1.5">
                    <i data-lucide="coins" class="w-3.5 h-3.5 text-amber-500"></i>
                    <div>
                        <p class="text-[9px] text-amber-600/70 leading-none">Commission</p>
                        <p class="text-xs font-bold text-amber-500" id="commBalance"><?= e($posCurrencySymbol) ?> 0</p>
                    </div>
                </div>
            </div>
            <button onclick="openHeldSales()" class="relative p-2 text-gray-400 hover:text-amber-500 rounded-lg hover:bg-gray-800 transition-colors" title="Held Sales">
                <i data-lucide="pause-circle" class="w-5 h-5"></i>
                <span id="heldCount" class="hidden absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center">0</span>
            </button>
            <a href="/cashier" class="p-2 text-gray-400 hover:text-white rounded-lg hover:bg-gray-800 transition-colors" title="Dashboard"><i data-lucide="layout-dashboard" class="w-5 h-5"></i></a>
            <a href="/logout" class="p-2 text-gray-400 hover:text-red-400 rounded-lg hover:bg-gray-800 transition-colors"><i data-lucide="log-out" class="w-5 h-5"></i></a>
        </div>
    </header>

    <div class="flex-1 flex overflow-hidden">
        <!-- Products Side -->
        <div class="flex-1 flex flex-col min-w-0 p-4">
            <!-- Search -->
            <div class="relative mb-4" id="posSearchWrap">
                <i data-lucide="search" class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none z-10"></i>
                <input type="text" id="posSearch" placeholder="Search by name, SKU, or scan barcode..." autocomplete="off"
                    class="w-full pl-10 pr-10 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 bg-white shadow-sm transition-shadow">
                <button type="button" id="posSearchClear" onclick="clearPosSearch()" class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors z-10">
                    <i data-lucide="x-circle" class="w-4 h-4"></i>
                </button>
                <div id="searchResults" class="absolute left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-xl shadow-xl z-30 max-h-72 overflow-y-auto hidden scrollbar-thin"></div>
            </div>

            <!-- Categories -->
            <div class="flex gap-2 mb-4 overflow-x-auto pb-1">
                <button onclick="filterCategory('')" class="pos-cat-btn px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap bg-amber-600 text-white">All</button>
                <?php foreach (Database::select("SELECT * FROM categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY name") as $c): ?>
                    <button onclick="filterCategory(<?= $c['id'] ?>)" class="pos-cat-btn px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap bg-white text-gray-600 border border-gray-200 hover:border-amber-500"><?= e($c['name']) ?></button>
                <?php endforeach; ?>
            </div>

            <!-- Product Grid -->
            <div id="productGrid" class="flex-1 overflow-y-auto grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 content-start scrollbar-thin">
                <?php
                $products = Database::select("SELECT p.*, (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1) as image FROM products p WHERE p.is_active = 1 AND p.stock_quantity > 0 ORDER BY p.name");
                foreach ($products as $p):
                    $productData = htmlspecialchars(json_encode(['id'=>(int)$p['id'],'name'=>$p['name'],'price'=>(float)$p['price'],'image'=>$p['image'] ?? '','sku'=>$p['sku'] ?? '','barcode'=>$p['barcode'] ?? '']), ENT_QUOTES, 'UTF-8');
                ?>
                <button type="button" onclick="event.preventDefault(); addToCart(<?= $productData ?>)"
                    class="bg-white rounded-xl border border-gray-100 shadow-sm p-3 text-left hover:border-amber-300 hover:shadow-md transition-all group">
                    <div class="aspect-square bg-gray-50 rounded-lg mb-2 flex items-center justify-center overflow-hidden">
                        <img src="<?= e($p['image'] ?? '/uploads/no-image-sm.jpg') ?>" alt="" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                    </div>
                    <p class="text-xs font-medium text-gray-900 truncate"><?= e($p['name']) ?></p>
                    <div class="flex items-center justify-between mt-1">
                        <span class="text-sm font-bold text-amber-600"><?= formatMoney($p['price']) ?></span>
                        <span class="text-[10px] text-gray-400">Stock: <?= $p['stock_quantity'] ?></span>
                    </div>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Cart Side -->
        <div class="w-96 bg-white border-l border-gray-200 flex flex-col shrink-0">
            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-heading font-semibold text-sm">Current Sale</h2>
                <button onclick="clearCart()" class="text-xs text-red-500 hover:text-red-700 font-medium">Clear All</button>
            </div>

            <!-- Cart Items -->
            <div id="cartItems" class="flex-1 overflow-y-auto p-4 space-y-2 scrollbar-thin">
                <?php if (empty($cart)): ?>
                    <div class="text-center py-12 text-gray-400">
                        <i data-lucide="shopping-cart" class="w-12 h-12 mx-auto mb-2 opacity-30"></i>
                        <p class="text-sm">No items in cart</p>
                        <p class="text-xs mt-1">Search or tap products to add</p>
                    </div>
                <?php else: ?>
                    <div id="cartList">
                        <?php foreach ($cart as $i => $item): ?>
                        <div class="flex items-center gap-2 p-2 bg-gray-50 rounded-lg" data-idx="<?= $i ?>">
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium truncate"><?= e($item['name']) ?></p>
                                <p class="text-xs text-gray-500"><?= formatMoney($item['price']) ?></p>
                            </div>
                            <div class="flex items-center gap-1">
                                <button onclick="updateQty(<?= $i ?>,-1)" class="w-6 h-6 bg-white border rounded text-xs hover:bg-gray-100">&minus;</button>
                                <span class="w-6 text-center text-xs font-medium" id="qty-<?= $i ?>"><?= $item['qty'] ?></span>
                                <button onclick="updateQty(<?= $i ?>,1)" class="w-6 h-6 bg-white border rounded text-xs hover:bg-gray-100">+</button>
                            </div>
                            <span class="text-xs font-medium w-16 text-right"><?= formatMoney($item['subtotal']) ?></span>
                            <button onclick="removeItem(<?= $i ?>)" class="text-gray-400 hover:text-red-500"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Totals -->
            <div class="border-t border-gray-100 p-4 space-y-2">
                <div class="flex justify-between text-sm"><span class="text-gray-500">Subtotal</span><span id="cartSubtotal"><?= formatMoney($cartTotal) ?></span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-500">Tax (<?= $taxRate ?>%)</span><span id="cartTax"><?= formatMoney($taxAmount) ?></span></div>
                <div class="flex justify-between text-lg font-bold border-t border-gray-100 pt-2"><span>Total</span><span id="cartTotal" class="text-amber-600"><?= formatMoney($grandTotal) ?></span></div>
            </div>

            <!-- Payment Buttons -->
            <div class="p-4 border-t border-gray-100 space-y-2">
                <div class="grid grid-cols-2 gap-2" id="payMethodBtns">
                    <?php foreach ($posPayMethods as $i => $pm):
                        $bgColor = $pm['color'] === 'amber' ? 'bg-amber-600 hover:bg-amber-700' :
                                   ($pm['color'] === 'green' ? 'bg-green-600 hover:bg-green-700' :
                                   ($pm['color'] === 'purple' ? 'bg-purple-600 hover:bg-purple-700' :
                                   ($pm['color'] === 'orange' ? 'bg-orange-600 hover:bg-orange-700' :
                                   'bg-gray-700 hover:bg-gray-800')));
                    ?>
                    <?php if ($i > 0 && $i % 2 === 0): ?>
                </div><div class="grid grid-cols-2 gap-2">
                    <?php endif; ?>
                    <button onclick="openPayment('<?= e($pm['slug']) ?>')" class="<?= $bgColor ?> text-white py-3 rounded-xl text-sm font-medium flex items-center justify-center gap-2">
                        <i data-lucide="<?= e($pm['icon']) ?>" class="w-4 h-4"></i> <?= e($pm['name']) ?>
                    </button>
                    <?php endforeach; ?>
                    <?php if (count($posPayMethods) % 2 !== 0): ?>
                    <button onclick="openHoldSaleModal()" class="bg-amber-500 text-white py-3 rounded-xl text-sm font-medium hover:bg-amber-600 flex items-center justify-center gap-2">
                        <i data-lucide="pause" class="w-4 h-4"></i> Hold
                    </button>
                    <?php endif; ?>
                </div>
                <?php if (count($posPayMethods) % 2 === 0): ?>
                <button onclick="openHoldSaleModal()" class="w-full bg-amber-500 text-white py-3 rounded-xl text-sm font-medium hover:bg-amber-600 flex items-center justify-center gap-2">
                    <i data-lucide="pause" class="w-4 h-4"></i> Hold Sale
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ==================== PAYMENT MODAL ==================== -->
    <div id="paymentModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 modal-overlay">
        <div class="bg-white rounded-2xl max-w-md w-full modal-content shadow-2xl">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div id="payMethodIcon" class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center">
                        <i data-lucide="banknote" class="w-5 h-5 text-amber-600"></i>
                    </div>
                    <div>
                        <h3 class="font-heading font-bold text-lg" id="payMethodTitle">Cash Payment</h3>
                        <p class="text-xs text-gray-500" id="payMethodSub">Enter amount received</p>
                    </div>
                </div>
                <button onclick="closePayment()" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                    <i data-lucide="x" class="w-5 h-5 text-gray-400"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="px-6 py-5 space-y-4">
                <!-- Order Summary -->
                <div class="bg-gray-50 rounded-xl p-4 space-y-2">
                    <div class="flex justify-between text-sm"><span class="text-gray-500">Subtotal</span><span id="paySubtotal"><?= e($posCurrencySymbol) ?> 0.00</span></div>
                    <div class="flex justify-between text-sm"><span class="text-gray-500">Tax (<?= $taxRate ?>%)</span><span id="payTax"><?= e($posCurrencySymbol) ?> 0.00</span></div>
                    <div class="flex justify-between text-base font-bold border-t border-gray-200 pt-2"><span>Total Due</span><span id="payTotal" class="text-amber-600"><?= e($posCurrencySymbol) ?> 0.00</span></div>
                </div>

                <!-- CASH: Amount Received + Change -->
                <div id="cashFields">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Amount Received</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-medium"><?= e($posCurrencySymbol) ?></span>
                        <input type="number" id="amountReceived" step="0.01" min="0" placeholder="0.00" inputmode="decimal"
                            class="w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-xl text-xl font-bold focus:outline-none focus:border-amber-500 text-right transition-colors"
                            oninput="calcChange()">
                    </div>
                    <!-- Quick amount buttons -->
                    <div class="grid grid-cols-4 gap-1.5 mt-2">
                        <button onclick="setQuickAmount('exact')" class="bg-amber-50 text-amber-700 py-2 rounded-lg text-xs font-semibold hover:bg-amber-100 transition-colors">Exact</button>
                        <button onclick="setQuickAmount(50)" class="bg-gray-50 text-gray-600 py-2 rounded-lg text-xs font-semibold hover:bg-gray-100 transition-colors">+50</button>
                        <button onclick="setQuickAmount(100)" class="bg-gray-50 text-gray-600 py-2 rounded-lg text-xs font-semibold hover:bg-gray-100 transition-colors">+100</button>
                        <button onclick="setQuickAmount(500)" class="bg-gray-50 text-gray-600 py-2 rounded-lg text-xs font-semibold hover:bg-gray-100 transition-colors">+500</button>
                        <button onclick="setQuickAmount(1000)" class="bg-gray-50 text-gray-600 py-2 rounded-lg text-xs font-semibold hover:bg-gray-100 transition-colors">1000</button>
                        <button onclick="setQuickAmount(2000)" class="bg-gray-50 text-gray-600 py-2 rounded-lg text-xs font-semibold hover:bg-gray-100 transition-colors">2000</button>
                        <button onclick="setQuickAmount(5000)" class="bg-gray-50 text-gray-600 py-2 rounded-lg text-xs font-semibold hover:bg-gray-100 transition-colors">5000</button>
                        <button onclick="setQuickAmount(10000)" class="bg-gray-50 text-gray-600 py-2 rounded-lg text-xs font-semibold hover:bg-gray-100 transition-colors">10000</button>
                    </div>
                    <!-- Change Display -->
                    <div id="changeDisplay" class="hidden mt-3 rounded-xl p-4 transition-all">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-500">Change</span>
                            <span id="changeAmount" class="text-2xl font-bold text-amber-600"><?= e($posCurrencySymbol) ?> 0.00</span>
                        </div>
                    </div>
                    <p id="shortfallMsg" class="hidden text-sm text-red-600 font-medium mt-2 flex items-center gap-1.5">
                        <i data-lucide="alert-circle" class="w-4 h-4"></i> <span>Amount is less than total due</span>
                    </p>
                </div>

                <!-- MPESA: Phone + STK Push / Manual Code -->
                <div id="mpesaFields" class="hidden space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Customer Phone Number</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-medium">+254</span>
                            <input type="tel" id="mpesaPhone" placeholder="7XX XXX XXX" maxlength="12" inputmode="tel" autocomplete="off"
                                class="w-full pl-14 pr-4 py-3 border-2 border-gray-200 rounded-xl text-lg font-medium focus:outline-none focus:border-green-500 transition-colors">
                        </div>
                    </div>

                    <!-- M-Pesa Amount Display -->
                    <div class="bg-green-50 border border-green-200 rounded-xl p-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-green-700 font-medium">Amount to charge</span>
                            <span id="stkAmountDisplay" class="text-lg font-bold text-green-700"><?= e($posCurrencySymbol) ?> 0</span>
                        </div>
                        <p class="text-xs text-green-600 mt-1">M-Pesa amounts are rounded to the nearest shilling</p>
                    </div>

                    <!-- Two clear option buttons -->
                    <div id="mpesaOptionBtns" class="grid grid-cols-2 gap-2">
                        <button onclick="initiateStkPush()" id="stkPushBtn" class="py-3.5 rounded-xl text-sm font-bold text-white bg-green-600 hover:bg-green-700 transition-colors flex items-center justify-center gap-2">
                            <i data-lucide="smartphone" class="w-4 h-4"></i> <span>Send STK Push</span>
                        </button>
                        <button onclick="showMpesaManualEntry()" id="mpesaManualBtn" class="py-3.5 rounded-xl text-sm font-semibold text-gray-700 bg-white border-2 border-gray-200 hover:border-gray-400 hover:bg-gray-50 transition-colors flex items-center justify-center gap-2">
                            <i data-lucide="keyboard" class="w-4 h-4"></i> <span>Enter Code Manually</span>
                        </button>
                    </div>

                    <!-- STK Status Messages -->
                    <div id="stkStatus" class="hidden"></div>

                    <!-- After STK sent: code entry with auto-fill -->
                    <div id="stkCodeEntry" class="hidden space-y-3">
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-3">
                            <div class="flex items-center gap-2 text-blue-700">
                                <i data-lucide="check-circle" class="w-4 h-4"></i>
                                <span class="text-sm font-medium">STK Push sent &mdash; waiting for customer to confirm on their phone</span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">M-Pesa Transaction Code <span class="text-gray-400 font-normal">(auto-filled or enter manually)</span></label>
                            <input type="text" id="transactionCode" placeholder="e.g. SBK7Y5T4VZ" maxlength="20" autocomplete="off"
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-lg font-mono tracking-wider focus:outline-none focus:border-green-500 uppercase transition-colors">
                            <p class="text-xs text-gray-400 mt-1.5">Enter the code from the customer's M-Pesa confirmation SMS</p>
                        </div>
                    </div>

                    <!-- Manual code entry (chosen by cashier) -->
                    <div id="mpesaManualEntry" class="hidden space-y-3">
                        <div class="bg-gray-50 border border-gray-200 rounded-xl p-3">
                            <div class="flex items-center gap-2 text-gray-600">
                                <i data-lucide="keyboard" class="w-4 h-4"></i>
                                <span class="text-sm font-medium">Enter the transaction code from the customer's M-Pesa message</span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">M-Pesa Transaction Code</label>
                            <input type="text" id="transactionCodeManual" placeholder="e.g. SBK7Y5T4VZ" maxlength="20" autocomplete="off"
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-lg font-mono tracking-wider focus:outline-none focus:border-amber-500 uppercase transition-colors">
                            <p class="text-xs text-gray-400 mt-1.5">Enter the confirmation code from the customer's phone</p>
                        </div>
                    </div>
                </div>

                <!-- DIGITAL: Transaction Code (non-Cash, non-M-Pesa) -->
                <div id="digitalFields" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5" id="txCodeLabel">Transaction Code</label>
                    <input type="text" id="transactionCode" placeholder="e.g. SBK7Y5T4VZ" maxlength="20" autocomplete="off"
                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-lg font-mono tracking-wider focus:outline-none focus:border-amber-500 uppercase transition-colors">
                    <p class="text-xs text-gray-400 mt-1.5">Enter the confirmation code from the customer's phone or terminal</p>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-gray-100 flex gap-3">
                <button onclick="closePayment()" class="flex-1 py-3 rounded-xl border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">Cancel</button>
                <button id="confirmPayBtn" onclick="confirmPayment()" class="flex-1 py-3 rounded-xl text-sm font-bold text-white bg-amber-600 hover:bg-amber-700 transition-colors flex items-center justify-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i> <span>Complete Sale</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ==================== HOLD SALE MODAL ==================== -->
    <div id="holdSaleModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 modal-overlay">
        <div class="bg-white rounded-2xl max-w-md w-full modal-content shadow-2xl">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center">
                        <i data-lucide="pause" class="w-5 h-5 text-amber-600"></i>
                    </div>
                    <h3 class="font-heading font-bold text-lg">Hold Sale</h3>
                </div>
                <button onclick="closeHoldSaleModal()" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                    <i data-lucide="x" class="w-5 h-5 text-gray-400"></i>
                </button>
            </div>
            <!-- Modal Body -->
            <div class="px-6 py-5 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Customer Name <span class="text-gray-400 font-normal">(optional)</span></label>
                    <input type="text" id="holdCustomerName" placeholder="e.g. John" autocomplete="off"
                        class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-xl text-sm focus:outline-none focus:border-amber-500 transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Sale Notes <span class="text-gray-400 font-normal">(optional)</span></label>
                    <textarea id="holdNotes" rows="3" placeholder="Add any notes about this hold..."
                        class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-xl text-sm focus:outline-none focus:border-amber-500 transition-colors resize-none"></textarea>
                </div>
                <!-- Cart Summary -->
                <div class="bg-gray-50 rounded-xl p-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600" id="holdCartSummary">0 items</span>
                        <span class="text-base font-bold text-amber-600" id="holdCartTotal"><?= e($posCurrencySymbol) ?> 0</span>
                    </div>
                </div>
            </div>
            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-gray-100 flex gap-3">
                <button onclick="closeHoldSaleModal()" class="flex-1 py-3 rounded-xl border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">Cancel</button>
                <button id="confirmHoldBtn" onclick="confirmHoldSale()" class="flex-1 py-3 rounded-xl text-sm font-bold text-white bg-amber-600 hover:bg-amber-700 transition-colors flex items-center justify-center gap-2">
                    <i data-lucide="pause" class="w-4 h-4"></i> <span>Hold Sale</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ==================== RECEIPT MODAL ==================== -->
    <div id="receiptModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 modal-overlay">
        <div class="bg-white rounded-2xl max-w-sm w-full modal-content shadow-2xl">
            <div id="receiptContent"></div>
            <!-- Hidden print area -->
            <div id="receiptPrint" class="hidden"></div>
            <div class="px-6 py-4 border-t border-gray-100 flex gap-2 no-print">
                <button onclick="printReceipt()" class="flex-1 bg-amber-600 text-white py-2.5 rounded-xl text-sm font-medium hover:bg-amber-700 flex items-center justify-center gap-2">
                    <i data-lucide="printer" class="w-4 h-4"></i> Print
                </button>
                <button onclick="newSale()" class="flex-1 bg-gray-900 text-white py-2.5 rounded-xl text-sm font-medium hover:bg-gray-800 flex items-center justify-center gap-2">
                    <i data-lucide="plus" class="w-4 h-4"></i> New Sale
                </button>
                <button onclick="closeReceipt()" class="py-2.5 px-4 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50">Close</button>
            </div>
        </div>
    </div>

    <!-- ==================== HELD SALES MODAL ==================== -->
    <div id="heldModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-overlay" style="background:rgba(0,0,0,0.5)">
        <div class="bg-white rounded-2xl max-w-lg w-full modal-content shadow-2xl max-h-[80vh] flex flex-col">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between shrink-0">
                <h3 class="font-heading font-bold text-lg flex items-center gap-2"><i data-lucide="pause-circle" class="w-5 h-5 text-amber-600"></i> Held Sales</h3>
                <button onclick="closeHeldSales()" class="p-2 hover:bg-gray-100 rounded-lg transition-colors" title="Close"><i data-lucide="x" class="w-5 h-5 text-gray-500 hover:text-gray-700"></i></button>
            </div>
            <div id="heldList" class="flex-1 overflow-y-auto p-4 space-y-3 scrollbar-thin"></div>
            <div class="px-6 py-3 border-t border-gray-100 flex items-center justify-between shrink-0">
                <p class="text-xs text-gray-400">Click <strong>Restore</strong> to load a held sale back into the cart</p>
                <button onclick="closeHeldSales()" class="px-4 py-1.5 bg-gray-100 text-gray-600 rounded-lg text-xs font-medium hover:bg-gray-200 transition-colors">Close</button>
            </div>
        </div>
    </div>

    <script>
    // ═══════════════════════════════════════════════════════════════
    // GLOBAL ERROR BOUNDARY — catches ALL unhandled errors
    // ═══════════════════════════════════════════════════════════════
    window.addEventListener('error', function(e) {
        console.error('[POS GLOBAL ERROR]', e.error || e.message, e.filename, e.lineno);
        // Don't show toast for auth errors — the auth dialog handles those
        if (e.error && e.error.isAuthError) { e.preventDefault(); return; }
        posShowError('JavaScript error: ' + (e.message || 'Unknown error'));
        e.preventDefault();
    });
    window.addEventListener('unhandledrejection', function(e) {
        console.error('[POS UNHANDLED PROMISE]', e.reason);
        // Don't show toast for auth errors — the auth dialog handles those
        if (e.reason && e.reason.isAuthError) { e.preventDefault(); return; }
        posShowError('Async error: ' + (e.reason && e.reason.message ? e.reason.message : String(e.reason)));
    });

    // ═══════════════════════════════════════════════════════════════
    // POS ERROR LOG — stores all API interactions for debugging
    // ═══════════════════════════════════════════════════════════════
    var posErrorLog = [];
    function posLogToConsole(label, data) {
        var entry = { t: new Date().toISOString(), label: label, data: data };
        posErrorLog.push(entry);
        if (posErrorLog.length > 50) posErrorLog.shift();
        console.log('[POS] ' + label, data);
    }

    // ═══════════════════════════════════════════════════════════════
    // ERROR TOAST — replaces alert() with a visible, dismissible toast
    // ═══════════════════════════════════════════════════════════════
    function posShowError(msg, detail) {
        var existing = document.getElementById('pos-error-toast');
        if (existing) existing.remove();

        var toast = document.createElement('div');
        toast.id = 'pos-error-toast';
        toast.style.cssText = 'position:fixed;top:16px;right:16px;z-index:9999;max-width:420px;background:#fff;border-left:4px solid #ef4444;border-radius:12px;box-shadow:0 20px 25px -5px rgba(0,0,0,.1),0 8px 10px -6px rgba(0,0,0,.1);padding:16px;font-family:Inter,system-ui,sans-serif;animation:slideUp .2s ease;';

        var html = '<div style="display:flex;align-items:flex-start;gap:12px;">';
        html += '<div style="flex-shrink:0;width:24px;height:24px;border-radius:50%;background:#fef2f2;display:flex;align-items:center;justify-content:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></div>';
        html += '<div style="flex:1;min-width:0;">';
        html += '<p style="margin:0;font-size:13px;font-weight:600;color:#991b1b;line-height:1.4;">' + msg + '</p>';
        if (detail) {
            html += '<p style="margin:6px 0 0;font-size:11px;color:#6b7280;word-break:break-all;max-height:60px;overflow-y:auto;font-family:monospace;">' + detail + '</p>';
        }
        html += '</div>';
        html += '<button onclick="this.closest(\'#pos-error-toast\').remove()" style="flex-shrink:0;background:none;border:none;cursor:pointer;padding:2px;color:#9ca3af;font-size:18px;line-height:1;">&times;</button>';
        html += '</div>';

        // Show debug toggle
        html += '<button onclick="posToggleDebug()" style="margin:10px 0 0;padding:4px 10px;background:#f3f4f6;border:none;border-radius:6px;font-size:10px;color:#6b7280;cursor:pointer;font-family:monospace;">View Debug Log (' + posErrorLog.length + ' entries)</button>';

        toast.innerHTML = html;
        document.body.appendChild(toast);
        setTimeout(function() { if (toast.parentNode) { toast.style.transition = 'opacity .3s'; toast.style.opacity = '0'; setTimeout(function() { toast.remove(); }, 300); } }, 8000);
    }

    function posShowSuccess(msg) {
        var toast = document.createElement('div');
        toast.style.cssText = 'position:fixed;top:16px;right:16px;z-index:9999;background:#fff;border-left:4px solid #22c55e;border-radius:12px;box-shadow:0 20px 25px -5px rgba(0,0,0,.1);padding:14px 18px;font-family:Inter,system-ui,sans-serif;animation:slideUp .2s ease;';
        toast.innerHTML = '<div style="display:flex;align-items:center;gap:10px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><span style="font-size:13px;font-weight:600;color:#166534;">' + msg + '</span></div>';
        document.body.appendChild(toast);
        setTimeout(function() { toast.style.transition = 'opacity .3s'; toast.style.opacity = '0'; setTimeout(function() { toast.remove(); }, 300); }, 3000);
    }

    // Debug log modal
    function posToggleDebug() {
        var existing = document.getElementById('pos-debug-modal');
        if (existing) { existing.remove(); return; }
        var modal = document.createElement('div');
        modal.id = 'pos-debug-modal';
        modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:99999;display:flex;align-items:center;justify-content:center;padding:16px;';
        var logHtml = posErrorLog.map(function(e) {
            return '<div style="padding:6px 10px;border-bottom:1px solid #f3f4f6;font-size:11px;font-family:monospace;"><span style="color:#9ca3af;">' + e.t.split('T')[1].split('.')[0] + '</span> <span style="color:#2563eb;font-weight:600;">[' + e.label + ']</span> <span style="color:#374151;">' + (typeof e.data === 'string' ? e.data : JSON.stringify(e.data).substring(0, 200)) + '</span></div>';
        }).join('') || '<div style="padding:20px;text-align:center;color:#9ca3af;font-size:13px;">No entries yet</div>';
        modal.innerHTML = '<div style="background:#fff;border-radius:16px;max-width:640px;width:100%;max-height:80vh;display:flex;flex-direction:column;box-shadow:0 25px 50px -12px rgba(0,0,0,.25);"><div style="padding:16px 20px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:between;"><h3 style="margin:0;font-size:14px;font-weight:700;color:#111827;">POS Debug Log</h3></div><div style="flex:1;overflow-y:auto;padding:0;">' + logHtml + '</div><div style="padding:12px 20px;border-top:1px solid #e5e7eb;text-align:right;"><button onclick="posToggleDebug()" style="padding:8px 20px;background:#111827;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">Close</button></div></div>';
        modal.addEventListener('click', function(e) { if (e.target === modal) modal.remove(); });
        document.body.appendChild(modal);
    }

    // ═══════════════════════════════════════════════════════════════
    // AUTH ERROR HANDLER — session expired / access denied
    // ═══════════════════════════════════════════════════════════════
    var _posAuthDialogShown = false;
    function posHandleAuthError(status, rawText) {
        if (_posAuthDialogShown) return;
        _posAuthDialogShown = true;

        // Only show the login dialog for 401 (session truly expired)
        // For 403, just log and let the normal error toast handle it
        if (status !== 401) {
            _posAuthDialogShown = false;
            return;
        }

        var title = 'Session Expired';
        var msg = 'Your login session has expired. Please log in again to continue.';
        var hint = 'If this keeps happening, try clearing your browser cookies and logging in fresh.';

        // Remove any existing toast first
        var existing = document.getElementById('pos-error-toast');
        if (existing) existing.remove();

        var overlay = document.createElement('div');
        overlay.id = 'pos-auth-dialog';
        overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:99998;display:flex;align-items:center;justify-content:center;padding:16px;animation:fadeIn .15s ease;';

        overlay.innerHTML =
            '<div style="background:#fff;border-radius:16px;max-width:400px;width:100%;padding:32px;text-align:center;box-shadow:0 25px 50px -12px rgba(0,0,0,.25);animation:slideUp .2s ease;">' +
            '<div style="width:56px;height:56px;border-radius:50%;background:#fef2f2;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">' +
            '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>' +
            '</div>' +
            '<h3 style="margin:0 0 8px;font-size:18px;font-weight:700;color:#111827;font-family:Poppins,sans-serif;">' + title + '</h3>' +
            '<p style="margin:0 0 6px;font-size:13px;color:#6b7280;line-height:1.5;">' + msg + '</p>' +
            '<p style="margin:0 0 24px;font-size:11px;color:#9ca3af;line-height:1.4;">' + hint + '</p>' +
            '<div style="display:flex;gap:12px;justify-content:center;">' +
            '<button onclick="document.getElementById(\'pos-auth-dialog\').remove();_posAuthDialogShown=false;" style="padding:10px 24px;border:1px solid #e5e7eb;border-radius:10px;background:#fff;color:#374151;font-size:13px;font-weight:600;cursor:pointer;">Dismiss</button>' +
            '<a href="/login" style="padding:10px 24px;border:none;border-radius:10px;background:#111827;color:#fff;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">' +
            '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg> Log In</a>' +
            '</div>' +
            '</div>';

        overlay.addEventListener('click', function(e) { if (e.target === overlay) { overlay.remove(); _posAuthDialogShown = false; } });
        document.body.appendChild(overlay);
    }

    // ═══════════════════════════════════════════════════════════════
    // SAFE FETCH WRAPPER — catches ALL network/parse/auth errors
    // ═══════════════════════════════════════════════════════════════
    var _pos403Count = 0;
    function posFetch(url, options) {
        posLogToConsole('FETCH_START', { url: url, method: options.method || 'GET' });
        var startTime = Date.now();

        return fetch(url, options).then(function(response) {
            var elapsed = Date.now() - startTime;
            var contentType = response.headers.get('content-type') || '';
            posLogToConsole('FETCH_RESPONSE', { url: url, status: response.status, statusText: response.statusText, elapsed: elapsed + 'ms', contentType: contentType });

            // ── 401 = session expired → show login dialog ──
            if (response.status === 401) {
                posLogToConsole('FETCH_AUTH_401', { url: url, contentType: contentType });
                if (contentType.indexOf('text/html') >= 0) {
                    posHandleAuthError(401, null);
                    var authErr = new Error('Session expired — please log in again');
                    authErr.isAuthError = true;
                    authErr.status = 401;
                    throw authErr;
                }
            }

            // ── 403 + HTML = server routing issue (NOT user permission) ──
            // This happens when Caddy/webserver doesn't route the API request
            // to PHP properly. Do NOT show auth dialog. Return a safe default.
            // IMPORTANT: Do NOT auto-reload — it disrupts the user's workflow.
            if (response.status === 403 && contentType.indexOf('text/html') >= 0) {
                posLogToConsole('FETCH_403_HTML', { url: url, contentType: contentType });
                _pos403Count++;
                var reqMethod = (options && options.method || 'GET').toUpperCase();
                // For GET read-only endpoints: return safe empty defaults so POS doesn't crash
                if (reqMethod === 'GET') {
                    if (url.indexOf('/holds') >= 0) return { success: true, holds: [] };
                    if (url.indexOf('/products') >= 0) return [];
                    if (url.indexOf('/commissions') >= 0) return { success: true, pending: 0, paid: 0, earned: 0 };
                    if (url.indexOf('/health') >= 0) return { success: false, auth: false, error: 'routing' };
                }
                // For POST/DELETE: indicate failure (don't fake success)
                return { success: false, error: 'Server routing issue. Please refresh the page.' };
            }

            // Read raw text first
            return response.text().then(function(rawText) {
                posLogToConsole('FETCH_RAW', { url: url, status: response.status, length: rawText.length, first500: rawText.substring(0, 500) });

                // Check if response looks like JSON before parsing
                var trimmed = rawText.trim();
                var isJson = trimmed.charAt(0) === '{' || trimmed.charAt(0) === '[';

                if (!isJson) {
                    posLogToConsole('FETCH_NOT_JSON', { url: url, status: response.status, length: rawText.length, rawFirst200: rawText.substring(0, 200) });

                    // 502/503/504 = PHP-FPM down or server error
                    if (response.status >= 500) {
                        throw new Error('Server error (HTTP ' + response.status + '). The server may be restarting. Please try again in a moment.');
                    }

                    // 401 without JSON = session issue
                    if (response.status === 401) {
                        posHandleAuthError(401, rawText);
                        var authErr2 = new Error('Session expired — please log in again');
                        authErr2.isAuthError = true;
                        authErr2.status = 401;
                        throw authErr2;
                    }

                    // 403 without JSON = server routing issue, not a permission problem
                    if (response.status === 403) {
                        posLogToConsole('FETCH_403_NOJSON', { url: url });
                        var reqMethod2 = (options && options.method || 'GET').toUpperCase();
                        if (reqMethod2 === 'GET') {
                            if (url.indexOf('/holds') >= 0) return { success: true, holds: [] };
                            if (url.indexOf('/products') >= 0) return [];
                            if (url.indexOf('/commissions') >= 0) return { success: true, pending: 0, paid: 0, earned: 0 };
                            if (url.indexOf('/health') >= 0) return { success: false, auth: false, error: 'routing' };
                        }
                        return { success: false, error: 'Server returned an error instead of data. Please refresh the page.' };
                    }

                    // Generic non-JSON response
                    var snippet = rawText.substring(0, 120).replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim();
                    throw new Error('Server returned an unexpected response (HTTP ' + response.status + '): "' + snippet + '"');
                }

                // Try to parse as JSON
                var data;
                try {
                    data = JSON.parse(rawText);
                } catch (parseErr) {
                    posLogToConsole('FETCH_PARSE_FAIL', { url: url, rawFirst300: rawText.substring(0, 300) });
                    throw new Error('Server returned malformed JSON (HTTP ' + response.status + '). The response could not be parsed.');
                }

                // Check for HTTP error status with valid JSON
                if (response.status >= 400) {
                    var errMsg = data.error || data.message || 'Server error ' + response.status;
                    if (data.debug_file) errMsg += ' [' + data.debug_file + ':' + data.debug_line + ']';
                    posLogToConsole('FETCH_HTTP_ERROR', { url: url, status: response.status, error: errMsg });
                    var err = new Error(errMsg);
                    err.data = data;
                    err.status = response.status;
                    if (response.status === 401) {
                        err.isAuthError = true;
                        posHandleAuthError(401, rawText);
                    }
                    throw err;
                }

                return data;
            });
        }).catch(function(err) {
            // Network error (no response at all — offline, DNS failure, CORS)
            if (err instanceof TypeError && (err.message.indexOf('fetch') >= 0 || err.message.indexOf('Failed to fetch') >= 0 || err.message.indexOf('NetworkError') >= 0)) {
                posLogToConsole('FETCH_NETWORK_ERROR', { url: url, error: err.message });
                throw new Error('Network error: Could not connect to the server. Check your internet connection and try again.');
            }
            // Timeout / AbortError
            if (err instanceof DOMException && err.name === 'AbortError') {
                posLogToConsole('FETCH_TIMEOUT', { url: url, error: err.message });
                throw new Error('Request timed out. The server took too long to respond. Please try again.');
            }
            // Re-throw auth errors as-is
            if (err.isAuthError) {
                throw err;
            }
            // Re-throw errors that already have data attached (our HTTP error handler)
            if (err.data) {
                throw err;
            }
            // Unknown error type — wrap it
            posLogToConsole('FETCH_UNKNOWN_ERROR', { url: url, error: err.message || String(err) });
            throw new Error(err.message || 'Unknown error occurred');
        });
    }

    // ═══════════════════════════════════════════════════════════════
    // STATE & HELPERS
    // ═══════════════════════════════════════════════════════════════
    let cart = <?= json_encode($cart) ?>;
    const taxRate = <?= $taxRate ?>;
    const storeName = <?= json_encode($storeName) ?>;
        const currencySymbol = <?= json_encode($posCurrencySymbol) ?>;
    const storePhone = <?= json_encode($storePhone) ?>;
    const storeAddr = <?= json_encode($storeAddr) ?>;
    const storeLogo = <?= json_encode($storeLogo) ?>;
    let currentPayMethod = 'cash';
    let lastReceiptData = null;

    function apiHeaders() {
        return { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
    }

    function saveCart() {
        const fd = new FormData();
        fd.append('cart', JSON.stringify(cart));
        posFetch('/api/pos/cart', { method: 'POST', body: fd, headers: apiHeaders() }).catch(function(err) {
            posLogToConsole('SAVE_CART_FAIL', { error: err.message });
        });
    }

    function getTotals() {
        const sub = cart.reduce(function(s, i) { return s + i.price * i.qty; }, 0);
        const tax = sub * (taxRate / 100);
        const total = sub + tax;
        return { sub: sub, tax: tax, total: total };
    }

    function fmt(n) {
        return currencySymbol + ' ' + n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    // ═══════════════════════════════════════════════════════════════
    // CART RENDERING
    // ═══════════════════════════════════════════════════════════════
    function renderCart() {
        try {
        const totals = getTotals();
        document.getElementById('cartSubtotal').textContent = fmt(totals.sub);
        document.getElementById('cartTax').textContent = fmt(totals.tax);
        document.getElementById('cartTotal').textContent = fmt(totals.total);

        const container = document.getElementById('cartItems');
        if (cart.length === 0) {
            container.innerHTML = '<div class="text-center py-12 text-gray-400"><svg class="w-12 h-12 mx-auto mb-2 opacity-30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg><p class="text-sm">No items in cart</p><p class="text-xs mt-1">Search or tap products to add</p></div>';
            return;
        }
        container.innerHTML = '<div id="cartList">' + cart.map(function(item, i) {
            return '<div class="flex items-center gap-2 p-2 bg-gray-50 rounded-lg">' +
                '<div class="flex-1 min-w-0"><p class="text-xs font-medium truncate">' + item.name + '</p><p class="text-xs text-gray-500">' + currencySymbol + ' ' + item.price.toLocaleString() + '</p></div>' +
                '<div class="flex items-center gap-1">' +
                '<button onclick="updateQty(' + i + ',-1)" class="w-6 h-6 bg-white border rounded text-xs hover:bg-gray-100">&minus;</button>' +
                '<span class="w-6 text-center text-xs font-medium">' + item.qty + '</span>' +
                '<button onclick="updateQty(' + i + ',1)" class="w-6 h-6 bg-white border rounded text-xs hover:bg-gray-100">+</button>' +
                '</div>' +
                '<span class="text-xs font-medium w-16 text-right">' + currencySymbol + ' ' + (item.price * item.qty).toLocaleString() + '</span>' +
                '<button onclick="removeItem(' + i + ')" class="text-gray-400 hover:text-red-500"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>' +
                '</div>';
        }).join('') + '</div>';
        } catch(e) {
            posLogToConsole('RENDER_CART_ERROR', { error: e.message });
        }
    }

    function addToCart(product) {
        try {
            const idx = cart.findIndex(function(i) { return i.id === product.id; });
            if (idx >= 0) {
                cart[idx].qty++;
                cart[idx].subtotal = cart[idx].price * cart[idx].qty;
            } else {
                cart.push({ id: product.id, name: product.name, price: product.price, image: product.image || '', qty: 1, subtotal: product.price });
            }
            renderCart();
            saveCart();
        } catch(e) {
            posLogToConsole('ADD_TO_CART_ERROR', { error: e.message });
            posShowError('Failed to add item to cart', e.message);
        }
    }

    function updateQty(i, delta) {
        try {
            cart[i].qty += delta;
            if (cart[i].qty <= 0) cart.splice(i, 1);
            else cart[i].subtotal = cart[i].price * cart[i].qty;
            renderCart();
            saveCart();
        } catch(e) {
            posLogToConsole('UPDATE_QTY_ERROR', { error: e.message });
            posShowError('Failed to update quantity', e.message);
        }
    }

    function removeItem(i) { try { cart.splice(i, 1); renderCart(); saveCart(); } catch(e) { posShowError('Failed to remove item', e.message); } }
    function clearCart() { try { cart = []; renderCart(); saveCart(); } catch(e) { posShowError('Failed to clear cart', e.message); } }

    // ═══════════════════════════════════════════════════════════════
    // PAYMENT MODAL
    // ═══════════════════════════════════════════════════════════════
    const posMethods = <?= json_encode($posPayMethods) ?>;
    let stkCheckoutId = '';
    let stkAmount = 0;
    let stkOrderNum = '';
    let mpesaEntryMode = 'none';

    function openPayment(method) {
        try {
            if (cart.length === 0) return;
            currentPayMethod = method;
            stkCheckoutId = '';
            stkAmount = 0;
            stkOrderNum = '';
            mpesaEntryMode = 'none';

            const totals = getTotals();

            document.getElementById('paySubtotal').textContent = fmt(totals.sub);
            document.getElementById('payTax').textContent = fmt(totals.tax);
            document.getElementById('payTotal').textContent = fmt(totals.total);

            const isCash = method === 'cash';
            const isMpesa = method === 'mpesa' || method === 'cloudone';

            document.getElementById('cashFields').classList.add('hidden');
            document.getElementById('digitalFields').classList.add('hidden');
            document.getElementById('mpesaFields').classList.add('hidden');

            if (isCash) {
                document.getElementById('cashFields').classList.remove('hidden');
            } else if (isMpesa) {
                document.getElementById('mpesaFields').classList.remove('hidden');
                document.getElementById('mpesaOptionBtns').classList.remove('hidden');
                document.getElementById('stkCodeEntry').classList.add('hidden');
                document.getElementById('mpesaManualEntry').classList.add('hidden');
                document.getElementById('stkStatus').classList.add('hidden');
                var stkBtn = document.getElementById('stkPushBtn');
                stkBtn.disabled = false;
                stkBtn.innerHTML = '<i data-lucide="smartphone" class="w-4 h-4"></i> <span>Send STK Push</span>';
                const mpesaAmt = Math.round(totals.total);
                stkAmount = mpesaAmt;
                document.getElementById('stkAmountDisplay').textContent = currencySymbol + ' ' + mpesaAmt.toLocaleString();
            } else {
                document.getElementById('digitalFields').classList.remove('hidden');
            }

            var mInfo = posMethods.find(function(m) { return m.slug === method; }) || { name: method, icon: 'credit-card', color: 'gray' };
            var color = mInfo.color || 'gray';
            var colorMap = { amber: 'amber', green: 'green', purple: 'purple', orange: 'orange', gray: 'gray', blue: 'blue', red: 'red', teal: 'teal', cyan: 'cyan' };

            document.getElementById('payMethodTitle').textContent = mInfo.name + ' Payment';
            document.getElementById('payMethodSub').textContent = isCash ? 'Enter amount received from customer' : isMpesa ? 'Send STK push or enter code manually' : 'Enter ' + mInfo.name + ' transaction code';
            document.getElementById('txCodeLabel').textContent = mInfo.name + ' Transaction Code';

            var iconEl = document.getElementById('payMethodIcon');
            var bgC = colorMap[color] || 'gray';
            iconEl.className = 'w-10 h-10 rounded-xl bg-' + bgC + '-100 flex items-center justify-center';
            iconEl.innerHTML = '<i data-lucide="' + (mInfo.icon || 'credit-card') + '" class="w-5 h-5 text-' + bgC + '-600"></i>';

            document.getElementById('amountReceived').value = '';
            document.getElementById('changeDisplay').classList.add('hidden');
            document.getElementById('shortfallMsg').classList.add('hidden');
            var txCodeEl = document.getElementById('transactionCode');
            if (txCodeEl) txCodeEl.value = '';
            var txCodeManualEl = document.getElementById('transactionCodeManual');
            if (txCodeManualEl) txCodeManualEl.value = '';
            var phoneEl = document.getElementById('mpesaPhone');
            if (phoneEl) phoneEl.value = '';

            document.getElementById('paymentModal').classList.remove('hidden');
            if (isCash) {
                setTimeout(function() { document.getElementById('amountReceived').focus(); }, 100);
            } else if (isMpesa) {
                setTimeout(function() { var p = document.getElementById('mpesaPhone'); if (p) p.focus(); }, 100);
            } else {
                setTimeout(function() { document.getElementById('transactionCode').focus(); }, 100);
            }
            lucide.createIcons();
        } catch(e) {
            posLogToConsole('OPEN_PAYMENT_ERROR', { error: e.message });
            posShowError('Failed to open payment dialog', e.message);
        }
    }

    function closePayment() {
        try { document.getElementById('paymentModal').classList.add('hidden'); } catch(e) {}
    }

    // ═══════════════════════════════════════════════════════════════
    // M-PESA
    // ═══════════════════════════════════════════════════════════════
    function showMpesaManualEntry() {
        try {
        mpesaEntryMode = 'manual';
        document.getElementById('mpesaOptionBtns').classList.add('hidden');
        document.getElementById('stkCodeEntry').classList.add('hidden');
        document.getElementById('mpesaManualEntry').classList.remove('hidden');
        setTimeout(function() {
            var el = document.getElementById('transactionCodeManual');
            if (el) el.focus();
        }, 150);
        lucide.createIcons();
        } catch(e) {
            posLogToConsole('SHOW_MANUAL_ENTRY_ERROR', { error: e.message });
            posShowError('Failed to show manual entry', e.message);
        }
    }

    function initiateStkPush() {
        try {
        var phoneInput = document.getElementById('mpesaPhone');
        var phone = phoneInput.value.replace(/[^0-9]/g, '');
        var totals = getTotals();
        var amount = Math.round(totals.total);
        var orderNum = 'POS-' + Date.now().toString(36).toUpperCase();

        if (!phone || phone.length < 9) {
            phoneInput.classList.add('border-red-500');
            phoneInput.focus();
            return;
        }
        phoneInput.classList.remove('border-red-500');

        // Normalize phone
        if (phone.startsWith('0')) phone = phone.substring(1);
        if (phone.startsWith('254') && phone.length > 9) phone = phone.substring(3);
        if (!phone.startsWith('254')) phone = '254' + phone;

        var btn = document.getElementById('stkPushBtn');
        btn.disabled = true;
        btn.innerHTML = '<svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" class="opacity-75" stroke-linecap="round"/></svg> Sending STK Push...';

        var statusEl = document.getElementById('stkStatus');
        statusEl.classList.remove('hidden');
        statusEl.innerHTML = '<div class="flex items-center gap-2 text-sm text-gray-500"><svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" class="opacity-75" stroke-linecap="round"/></svg> Initiating STK push for ' + currencySymbol + ' ' + amount.toLocaleString() + '...</div>';

        var stkEndpoint = currentPayMethod === 'cloudone'
            ? '/api/pos/cloudone-stk-push'
            : '/api/pos/stk-push';

        posFetch(stkEndpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ phone: phone, amount: amount, orderRef: orderNum })
        }).then(function(data) {
            if (data.success) {
                mpesaEntryMode = 'stk';
                stkCheckoutId = data.CheckoutRequestID || '';
                stkAmount = data.amount || amount;
                stkOrderNum = orderNum;
                document.getElementById('mpesaOptionBtns').classList.add('hidden');
                document.getElementById('stkCodeEntry').classList.remove('hidden');
                statusEl.innerHTML = '<div class="flex items-center gap-2 text-green-600 text-sm"><i data-lucide="check-circle" class="w-4 h-4"></i> STK push sent for <strong>' + currencySymbol + ' ' + stkAmount.toLocaleString() + '</strong> to ' + phone + '</div>';
                lucide.createIcons();
                setTimeout(function() { var tc = document.getElementById('transactionCode'); if (tc) tc.focus(); }, 200);
            } else {
                statusEl.innerHTML = '<div class="bg-red-50 border border-red-200 rounded-xl p-3"><div class="flex items-center gap-2 text-red-700 text-sm"><i data-lucide="alert-circle" class="w-4 h-4"></i> ' + (data.error || 'STK push failed') + '</div></div>';
                lucide.createIcons();
                if (data.error && (data.error.indexOf('not configured') >= 0 || data.error.indexOf('M-Pesa error') >= 0)) {
                    setTimeout(function() { showMpesaManualEntry(); }, 1000);
                }
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="smartphone" class="w-4 h-4"></i> <span>Retry STK Push</span>';
                lucide.createIcons();
            }
        }).catch(function(e) {
            posLogToConsole('STK_PUSH_FRONTEND_ERROR', { error: e.message });
            // Don't show auth errors in the status box — auth dialog is already shown
            if (e.isAuthError) {
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="smartphone" class="w-4 h-4"></i> <span>Send STK Push</span>';
                lucide.createIcons();
                return;
            }
            statusEl.innerHTML = '<div class="bg-red-50 border border-red-200 rounded-xl p-3"><div class="flex items-center gap-2 text-red-700 text-sm"><i data-lucide="alert-circle" class="w-4 h-4"></i> ' + e.message + '</div></div>';
            setTimeout(function() { showMpesaManualEntry(); }, 500);
            btn.disabled = false;
            btn.innerHTML = '<i data-lucide="smartphone" class="w-4 h-4"></i> <span>Retry STK Push</span>';
            lucide.createIcons();
        });
        } catch(e) {
            posLogToConsole('STK_PUSH_UNEXPECTED', { error: e.message, stack: e.stack });
            posShowError('Failed to initiate STK push', e.message);
            var stkBtn = document.getElementById('stkPushBtn');
            if (stkBtn) {
                stkBtn.disabled = false;
                stkBtn.innerHTML = '<i data-lucide="smartphone" class="w-4 h-4"></i> <span>Send STK Push</span>';
                lucide.createIcons();
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // CASH HELPERS
    // ═══════════════════════════════════════════════════════════════
    function setQuickAmount(val) {
        try {
        var totals = getTotals();
        var input = document.getElementById('amountReceived');
        if (val === 'exact') { input.value = totals.total.toFixed(2); }
        else { var current = parseFloat(input.value) || 0; input.value = (current + val).toFixed(2); }
        calcChange();
        } catch(e) { posLogToConsole('SET_QUICK_AMOUNT_ERROR', { error: e.message }); }
    }

    function calcChange() {
        try {
        var totals = getTotals();
        var received = parseFloat(document.getElementById('amountReceived').value) || 0;
        var change = received - totals.total;
        var changeEl = document.getElementById('changeDisplay');
        var shortEl = document.getElementById('shortfallMsg');
        var changeAmt = document.getElementById('changeAmount');

        if (received > 0 && change >= 0) {
            changeEl.classList.remove('hidden');
            changeAmt.textContent = fmt(change);
            changeAmt.className = 'text-2xl font-bold text-amber-600';
            shortEl.classList.add('hidden');
        } else if (received > 0 && change < 0) {
            changeEl.classList.remove('hidden');
            changeAmt.textContent = 'Short: ' + fmt(Math.abs(change));
            changeAmt.className = 'text-2xl font-bold text-red-600';
            shortEl.classList.remove('hidden');
        } else {
            changeEl.classList.add('hidden');
            shortEl.classList.add('hidden');
        }
        } catch(e) { posLogToConsole('CALC_CHANGE_ERROR', { error: e.message }); }
    }

    // ═══════════════════════════════════════════════════════════════
    // CONFIRM PAYMENT — comprehensive error handling
    // ═══════════════════════════════════════════════════════════════
    function confirmPayment() {
        try {
            var totals = getTotals();
            var orderNum = stkOrderNum || ('POS-' + Date.now().toString(36).toUpperCase());
            var amountReceived = 0, changeAmount = 0, transactionCode = '';
            var checkoutTotal = totals.total;

            if (currentPayMethod === 'cash') {
                amountReceived = parseFloat(document.getElementById('amountReceived').value) || 0;
                if (amountReceived < totals.total) {
                    document.getElementById('amountReceived').classList.add('border-red-500');
                    document.getElementById('amountReceived').focus();
                    return;
                }
                changeAmount = amountReceived - totals.total;
                document.getElementById('amountReceived').classList.remove('border-red-500');
            } else if (currentPayMethod === 'mpesa' || currentPayMethod === 'cloudone') {
                checkoutTotal = Math.round(totals.total);
                var stkCodeEl = document.getElementById('transactionCode');
                var manualCodeEl = document.getElementById('transactionCodeManual');
                if (stkCodeEl && !document.getElementById('stkCodeEntry').classList.contains('hidden')) {
                    transactionCode = stkCodeEl.value.trim();
                } else if (manualCodeEl && !document.getElementById('mpesaManualEntry').classList.contains('hidden')) {
                    transactionCode = manualCodeEl.value.trim();
                }
                if (stkCodeEl) stkCodeEl.classList.remove('border-red-500');
                if (manualCodeEl) manualCodeEl.classList.remove('border-red-500');
                amountReceived = checkoutTotal;
            } else {
                transactionCode = document.getElementById('transactionCode').value.trim();
                if (!transactionCode) {
                    document.getElementById('transactionCode').classList.add('border-red-500');
                    document.getElementById('transactionCode').focus();
                    return;
                }
                document.getElementById('transactionCode').classList.remove('border-red-500');
                amountReceived = totals.total;
            }

            // Validate cart
            if (cart.length === 0) {
                posShowError('Cart is empty', 'Add items to the cart before completing the sale.');
                return;
            }

            var btn = document.getElementById('confirmPayBtn');
            btn.disabled = true;
            btn.innerHTML = '<svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" class="opacity-75" stroke-linecap="round"/></svg> Processing...';

            var fd = new FormData();
            fd.append('cart', JSON.stringify(cart));
            fd.append('method', currentPayMethod);
            fd.append('subtotal', totals.sub);
            fd.append('tax', totals.tax);
            fd.append('total', checkoutTotal);
            fd.append('orderNum', orderNum);
            fd.append('amount_received', amountReceived);
            fd.append('change_amount', changeAmount);
            fd.append('transaction_code', transactionCode);
            if (stkCheckoutId) fd.append('stk_checkout_id', stkCheckoutId);

            posLogToConsole('CHECKOUT_SENDING', { method: currentPayMethod, total: checkoutTotal, items: cart.length });

            posFetch('/api/pos/checkout', {
                method: 'POST',
                headers: apiHeaders(),
                body: fd
            }).then(function(data) {
                posLogToConsole('CHECKOUT_SUCCESS', { order_id: data.order_id, order_number: data.order_number });

                if (data.success) {
                    closePayment();
                    showReceipt({
                        orderNum: orderNum,
                        method: currentPayMethod,
                        sub: totals.sub,
                        tax: totals.tax,
                        total: checkoutTotal,
                        amountReceived: amountReceived,
                        changeAmount: changeAmount,
                        transactionCode: transactionCode,
                        cart: cart.slice(),
                        cashier: <?= json_encode($cashier) ?>,
                        timestamp: new Date().toLocaleString('en-KE', { dateStyle: 'medium', timeStyle: 'short' })
                    });
                    cart = [];
                    renderCart();
                    saveCart();
                    loadCommission();
                    loadHeldCount();
                } else {
                    posShowError('Checkout failed', data.error || 'Unknown error');
                }
            }).catch(function(err) {
                posLogToConsole('CHECKOUT_CATCH', { error: err.message, data: err.data || null });
                // Auth errors are handled by the auth dialog — don't double-show
                if (err.isAuthError) return;
                posShowError('Checkout error', err.message);
            }).finally(function() {
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="check" class="w-4 h-4"></i> <span>Complete Sale</span>';
                lucide.createIcons();
            });
        } catch(e) {
            posLogToConsole('CONFIRM_PAYMENT_UNEXPECTED', { error: e.message, stack: e.stack });
            posShowError('Unexpected error in payment', e.message);
            var btn = document.getElementById('confirmPayBtn');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="check" class="w-4 h-4"></i> <span>Complete Sale</span>';
                lucide.createIcons();
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // RECEIPT
    // ═══════════════════════════════════════════════════════════════
    function showReceipt(d) {
        try {
        lastReceiptData = d;
        var isCash = d.method === 'cash';
        var methodLabel = (posMethods.find(function(m) { return m.slug === d.method; }) || {}).name || d.method.toUpperCase();

        var itemsHtml = d.cart ? d.cart.map(function(i) {
            return '<tr class="border-b border-dashed border-gray-200">' +
                '<td class="py-1 text-left text-xs">' + i.name + '</td>' +
                '<td class="py-1 text-center text-xs">' + i.qty + '</td>' +
                '<td class="py-1 text-right text-xs">' + fmt(i.price) + '</td>' +
                '<td class="py-1 text-right text-xs font-medium">' + fmt(i.price * i.qty) + '</td>' +
                '</tr>';
        }).join('') : '';

        var cashInfoHtml = isCash ? (
            '<div class="mt-2 pt-2 border-t border-dashed border-gray-200">' +
            '<div class="flex justify-between text-xs"><span>Amount Received</span><span class="font-medium">' + fmt(d.amountReceived) + '</span></div>' +
            '<div class="flex justify-between text-sm font-bold mt-1"><span>Change</span><span class="text-amber-600">' + fmt(d.changeAmount) + '</span></div>' +
            '</div>'
        ) : (
            '<div class="mt-2 pt-2 border-t border-dashed border-gray-200">' +
            '<div class="flex justify-between text-xs"><span>Transaction Code</span><span class="font-mono font-bold text-amber-600">' + (d.transactionCode || 'N/A') + '</span></div>' +
            '</div>'
        );

        var logoHtml = storeLogo
            ? '<img src="' + storeLogo + '" alt="' + storeName + '" class="w-16 h-16 object-contain mx-auto mb-3 rounded-lg">'
            : '<div class="w-14 h-14 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-3"><svg class="w-7 h-7 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg></div>';

        var receiptHtml =
            '<div class="px-6 pt-5 pb-2">' +
            logoHtml +
            '<h3 class="font-heading font-bold text-lg text-center">Sale Complete!</h3>' +
            '<p class="text-xs text-gray-400 text-center mb-4">' + d.orderNum + '</p>' +
            '</div>' +
            '<div class="px-6 pb-4">' +
            '<div class="border-2 border-dashed border-gray-200 rounded-xl p-4 text-xs">' +
            '<div class="text-center mb-3 pb-2 border-b border-dashed border-gray-200">' +
            '<p class="font-bold text-sm">' + storeName + '</p>' +
            '<p class="text-gray-400">' + storeAddr + '</p>' +
            '<p class="text-gray-400">Tel: ' + storePhone + '</p>' +
            '</div>' +
            '<div class="flex justify-between text-gray-400 mb-2"><span>Receipt #' + d.orderNum + '</span><span>' + d.timestamp + '</span></div>' +
            '<div class="flex justify-between text-gray-400 mb-3 pb-2 border-b border-dashed border-gray-200"><span>Cashier: ' + d.cashier + '</span><span class="font-medium text-gray-700">' + methodLabel + '</span></div>' +
            '<table class="w-full mb-2">' +
            '<thead><tr class="border-b border-gray-300">' +
            '<th class="py-1 text-left text-[10px] font-semibold text-gray-500">ITEM</th>' +
            '<th class="py-1 text-center text-[10px] font-semibold text-gray-500">QTY</th>' +
            '<th class="py-1 text-right text-[10px] font-semibold text-gray-500">PRICE</th>' +
            '<th class="py-1 text-right text-[10px] font-semibold text-gray-500">TOTAL</th>' +
            '</tr></thead>' +
            '<tbody>' + itemsHtml + '</tbody>' +
            '</table>' +
            '<div class="space-y-1 border-t border-dashed border-gray-200 pt-2">' +
            '<div class="flex justify-between text-xs"><span>Subtotal</span><span>' + fmt(d.sub) + '</span></div>' +
            '<div class="flex justify-between text-xs"><span>Tax (' + taxRate + '%)</span><span>' + fmt(d.tax) + '</span></div>' +
            '<div class="flex justify-between text-sm font-bold border-t border-dashed border-gray-200 pt-1 mt-1"><span>TOTAL</span><span class="text-amber-600">' + fmt(d.total) + '</span></div>' +
            cashInfoHtml +
            '</div>' +
            '<div class="text-center mt-3 pt-2 border-t border-dashed border-gray-200">' +
            '<p class="text-gray-400">Thank you for shopping!</p>' +
            '<p class="text-gray-300 text-[10px] mt-0.5">' + storeName + ' - Powered by ShopSmart POS</p>' +
            '</div>' +
            '</div></div>';

        document.getElementById('receiptContent').innerHTML = receiptHtml;
        document.getElementById('receiptPrint').innerHTML = receiptHtml;
        document.getElementById('receiptModal').classList.remove('hidden');
        lucide.createIcons();
        } catch(e) {
            posLogToConsole('SHOW_RECEIPT_ERROR', { error: e.message, stack: e.stack });
            posShowError('Failed to display receipt', e.message);
        }
    }

    function printReceipt() {
        try {
        if (!lastReceiptData) return;
        var isCash = lastReceiptData.method === 'cash';
        var methodLabel = { cash: 'CASH', mpesa: 'M-PESA', card: 'CARD', pesapal: 'PESAPAL' }[lastReceiptData.method] || lastReceiptData.method.toUpperCase();
        var items = lastReceiptData.cart || [];
        var printLogoHtml = storeLogo
            ? '<div style="text-align:center;margin-bottom:6px;"><img src="' + storeLogo + '" style="max-width:64px;max-height:64px;object-fit:contain;" /></div>'
            : '';
        var printHtml = '<div style="font-family:monospace;font-size:12px;max-width:80mm;margin:0 auto;padding:2mm;color:#000;">' +
            '<div style="text-align:center;margin-bottom:8px;">' +
            printLogoHtml +
            '<h2 style="font-size:16px;font-weight:bold;margin:0;">' + storeName + '</h2>' +
            '<p style="margin:2px 0;color:#555;">' + storeAddr + '</p>' +
            '<p style="margin:2px 0;color:#555;">Tel: ' + storePhone + '</p>' +
            '</div>' +
            '<div style="border-top:1px dashed #000;border-bottom:1px dashed #000;padding:4px 0;margin-bottom:8px;">' +
            '<div style="display:flex;justify-content:space-between;"><span>Receipt #' + lastReceiptData.orderNum + '</span><span>' + lastReceiptData.timestamp + '</span></div>' +
            '<div style="display:flex;justify-content:space-between;"><span>Cashier: ' + lastReceiptData.cashier + '</span><span><b>' + methodLabel + '</b></span></div>' +
            '</div>' +
            '<table style="width:100%;border-collapse:collapse;margin-bottom:8px;">' +
            '<tr style="border-bottom:1px solid #000;"><th style="text-align:left;padding:2px 0;">Item</th><th style="text-align:center;">Qty</th><th style="text-align:right;">Price</th><th style="text-align:right;">Total</th></tr>' +
            items.map(function(i) { return '<tr><td style="padding:2px 0;">' + i.name + '</td><td style="text-align:center;">' + i.qty + '</td><td style="text-align:right;">' + fmt(i.price) + '</td><td style="text-align:right;">' + fmt(i.price * i.qty) + '</td></tr>'; }).join('') +
            '</table>' +
            '<div style="border-top:1px dashed #000;padding-top:4px;">' +
            '<div style="display:flex;justify-content:space-between;"><span>Subtotal:</span><span>' + fmt(lastReceiptData.sub) + '</span></div>' +
            '<div style="display:flex;justify-content:space-between;"><span>Tax (' + taxRate + '%):</span><span>' + fmt(lastReceiptData.tax) + '</span></div>' +
            '<div style="display:flex;justify-content:space-between;font-size:14px;font-weight:bold;border-top:1px solid #000;padding-top:4px;margin-top:4px;"><span>TOTAL:</span><span>' + fmt(lastReceiptData.total) + '</span></div>' +
            (isCash ? '<div style="margin-top:4px;"><div style="display:flex;justify-content:space-between;"><span>Received:</span><span>' + fmt(lastReceiptData.amountReceived) + '</span></div><div style="display:flex;justify-content:space-between;font-weight:bold;"><span>Change:</span><span>' + fmt(lastReceiptData.changeAmount) + '</span></div></div>' : '<div style="margin-top:4px;display:flex;justify-content:space-between;"><span>Txn Code:</span><span style="font-weight:bold;">' + (lastReceiptData.transactionCode || 'N/A') + '</span></div>') +
            '</div>' +
            '<div style="text-align:center;margin-top:12px;padding-top:8px;border-top:1px dashed #000;">' +
            '<p style="color:#555;">Thank you for shopping!</p>' +
            '<p style="color:#999;font-size:10px;">' + storeName + ' - Powered by ShopSmart POS</p>' +
            '</div></div>';

        var win = window.open('', '_blank', 'width=350,height=600');
        win.document.write('<!DOCTYPE html><html><head><title>Receipt - ' + lastReceiptData.orderNum + '</title></head><body onload="window.print();window.close();">' + printHtml + '</body></html>');
        win.document.close();
        } catch(e) {
            posLogToConsole('PRINT_RECEIPT_ERROR', { error: e.message });
            posShowError('Failed to print receipt', e.message);
        }
    }

    function closeReceipt() {
        try { document.getElementById('receiptModal').classList.add('hidden'); } catch(e) {}
    }

    function newSale() {
        try {
        closeReceipt();
        document.getElementById('posSearch').focus();
        } catch(e) { posLogToConsole('NEW_SALE_ERROR', { error: e.message }); }
    }

    // ═══════════════════════════════════════════════════════════════
    // HOLD SALE MODAL
    // ═══════════════════════════════════════════════════════════════
    function openHoldSaleModal() {
        try {
            if (cart.length === 0) return;
            var totals = getTotals();
            document.getElementById('holdCustomerName').value = '';
            document.getElementById('holdNotes').value = '';
            document.getElementById('holdCartSummary').textContent = cart.length + ' item' + (cart.length !== 1 ? 's' : '');
            document.getElementById('holdCartTotal').textContent = fmt(totals.total);
            document.getElementById('holdSaleModal').classList.remove('hidden');
            lucide.createIcons();
            setTimeout(function() { document.getElementById('holdCustomerName').focus(); }, 100);
        } catch(e) {
            posShowError('Failed to open hold dialog', e.message);
        }
    }

    function closeHoldSaleModal() {
        try { document.getElementById('holdSaleModal').classList.add('hidden'); } catch(e) {}
    }

    function confirmHoldSale() {
        try {
            if (cart.length === 0) return;
            var notes = document.getElementById('holdNotes').value.trim();
            var customerName = document.getElementById('holdCustomerName').value.trim();

            var btn = document.getElementById('confirmHoldBtn');
            btn.disabled = true;
            btn.innerHTML = '<svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" class="opacity-75" stroke-linecap="round"/></svg> Holding...';

            var fd = new FormData();
            fd.append('cart', JSON.stringify(cart));
            fd.append('notes', notes);
            fd.append('customer_name', customerName);

            posFetch('/api/pos/holds', {
                method: 'POST',
                headers: apiHeaders(),
                body: fd
            }).then(function(data) {
                if (data.success) {
                    cart = [];
                    renderCart();
                    saveCart();
                    loadHeldCount();
                    closeHoldSaleModal();
                    posShowSuccess('Sale held successfully!');
                } else {
                    posShowError('Failed to hold sale', data.error || 'Unknown error');
                }
            }).catch(function(e) {
                posLogToConsole('HOLD_SALE_ERROR', { error: e.message });
                if (e.isAuthError) return;
                posShowError('Network error while holding sale', e.message);
            }).finally(function() {
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="pause" class="w-4 h-4"></i> <span>Hold Sale</span>';
                lucide.createIcons();
            });
        } catch(e) {
            posShowError('Unexpected error holding sale', e.message);
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // HELD SALES
    // ═══════════════════════════════════════════════════════════════
    function loadHeldCount() {
        posFetch('/api/pos/holds', { headers: apiHeaders() }).then(function(data) {
            if (data.success && data.holds && data.holds.length > 0) {
                document.getElementById('heldCount').textContent = data.holds.length;
                document.getElementById('heldCount').classList.remove('hidden');
            } else {
                document.getElementById('heldCount').classList.add('hidden');
            }
        }).catch(function(err) {
            posLogToConsole('LOAD_HELD_COUNT_ERROR', { error: err.message });
        });
    }

    function openHeldSales() {
        try {
            var modal = document.getElementById('heldModal');
            modal.classList.remove('hidden');
            var list = document.getElementById('heldList');
            list.innerHTML = '<div class="text-center py-8 text-gray-400"><svg class="animate-spin w-6 h-6 mx-auto mb-2" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" class="opacity-75" stroke-linecap="round"/></svg>Loading...</div>';
            posFetch('/api/pos/holds', { headers: apiHeaders() }).then(function(data) {
                if (data.success && data.holds && data.holds.length > 0) {
                    list.innerHTML = data.holds.map(function(h) {
                        return '<div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl border border-gray-100 hover:border-amber-300 transition-colors">' +
                            '<div class="flex-1 min-w-0">' +
                            '<div class="flex items-center gap-2 mb-1">' +
                            '<span class="text-xs font-medium text-gray-900">' + (h.customer_name ? h.customer_name + ' &middot; ' : '') + (h.cashier_name || 'Unknown') + '</span>' +
                            '<span class="text-[10px] text-gray-400">' + h.created_at + '</span>' +
                            '</div>' +
                            '<p class="text-xs text-gray-500">' + h.items_count + ' item(s) &middot; <span class="font-semibold text-amber-600">' + currencySymbol + ' ' + Number(h.total).toLocaleString() + '</span></p>' +
                            (h.notes ? '<p class="text-[10px] text-gray-400 mt-0.5">' + h.notes + '</p>' : '') +
                            '</div>' +
                            '<div class="flex gap-1.5 shrink-0">' +
                            '<button onclick="event.stopPropagation(); restoreHold(' + h.id + ')" class="px-3 py-2 bg-amber-600 text-white rounded-lg text-xs font-semibold hover:bg-amber-700 flex items-center gap-1"><i data-lucide="rotate-ccw" class="w-3 h-3"></i> Restore</button>' +
                            '<button onclick="event.stopPropagation(); deleteHold(' + h.id + ')" class="px-2 py-2 bg-red-50 text-red-600 rounded-lg text-xs hover:bg-red-100 border border-red-200">Delete</button>' +
                            '</div></div>';
                    }).join('');
                    lucide.createIcons();
                } else {
                    list.innerHTML = '<div class="text-center py-12 text-gray-400"><i data-lucide="pause-circle" class="w-10 h-10 mx-auto mb-2 opacity-30"></i><p class="text-sm">No held sales</p><p class="text-xs mt-1">Hold a sale to see it here</p></div>';
                    lucide.createIcons();
                }
            }).catch(function(err) {
                posLogToConsole('OPEN_HELD_SALES_ERROR', { error: err.message });
                if (err.isAuthError) {
                    list.innerHTML = '<div class="text-center py-8"><p class="text-gray-400 text-sm">Please log in to view held sales</p></div>';
                    return;
                }
                list.innerHTML = '<div class="text-center py-8"><p class="text-red-400 text-sm">Failed to load held sales</p><p class="text-xs text-gray-400 mt-1">' + err.message + '</p></div>';
            });
        } catch(e) {
            posShowError('Failed to open held sales', e.message);
        }
    }

    function closeHeldSales() { try { document.getElementById('heldModal').classList.add('hidden'); } catch(e) {} }

    function restoreHold(id) {
        posFetch('/api/pos/holds/restore/' + id, { method: 'POST', headers: apiHeaders() }).then(function(data) {
            if (data.success) {
                cart = data.cart || [];
                renderCart();
                saveCart();
                closeHeldSales();
                loadHeldCount();
                posShowSuccess('Sale restored to cart!');
            } else {
                posShowError('Failed to restore sale', data.error || 'Unknown error');
            }
        }).catch(function(e) {
            posLogToConsole('RESTORE_HOLD_ERROR', { error: e.message });
            if (e.isAuthError) return;
            posShowError('Network error restoring sale', e.message);
        });
    }

    function deleteHold(id) {
        if (!confirm('Delete this held sale?')) return;
        posFetch('/api/pos/holds/' + id, { method: 'DELETE', headers: apiHeaders() }).then(function(data) {
            if (data.success) { loadHeldCount(); openHeldSales(); }
        }).catch(function(e) {
            posLogToConsole('DELETE_HOLD_ERROR', { error: e.message });
        });
    }

    // ═══════════════════════════════════════════════════════════════
    // COMMISSION
    // ═══════════════════════════════════════════════════════════════
    function loadCommission() {
        posFetch('/api/commissions/balance', { headers: apiHeaders() }).then(function(data) {
            if (data.success && (data.earned > 0 || data.pending > 0)) {
                document.getElementById('commBalance').textContent = currencySymbol + ' ' + Number(data.pending).toLocaleString();
                document.getElementById('commissionBadge').classList.remove('hidden');
            }
        }).catch(function(){});
    }

    function showCommissionDetail() {
        posFetch('/api/commissions/balance', { headers: apiHeaders() }).then(function(data) {
            if (data.success) {
                alert('Commission Summary:\n\nPending: ' + currencySymbol + ' ' + Number(data.pending).toLocaleString() + '\nPaid: ' + currencySymbol + ' ' + Number(data.paid).toLocaleString() + '\nTotal Earned: ' + currencySymbol + ' ' + Number(data.earned).toLocaleString() + '\n\nView details in Admin > Commissions');
            }
        }).catch(function(){});
    }

    // ═══════════════════════════════════════════════════════════════
    // INIT & EVENT LISTENERS
    // ═══════════════════════════════════════════════════════════════
    posLogToConsole('POS_TERMINAL_INIT', { cart_items: cart.length, pay_methods: posMethods.length });

    // Health check: verify API routing is working on page load
    posFetch('/api/pos/health', { headers: apiHeaders() }).then(function(h) {
        if (h && h.success) {
            posLogToConsole('POS_HEALTH_OK', { auth: h.auth, user_id: h.user_id, role: h.user_role, session: h.session_id });
            if (!h.auth) {
                posLogToConsole('POS_HEALTH_NO_AUTH', {});
                posHandleAuthError(401, null);
                return;
            }
            // API is working — load data
            loadHeldCount();
            loadCommission();
        } else {
            posLogToConsole('POS_HEALTH_FAIL', h);
            loadHeldCount();
            loadCommission();
        }
    }).catch(function(e) {
        posLogToConsole('POS_HEALTH_ERROR', { error: e.message });
        // Even if health check fails, try loading data
        loadHeldCount();
        loadCommission();
    });

    // ═══════════════════════════════════════════════════════════════
    // SEARCH — live dropdown with barcode/SKU support
    // ═══════════════════════════════════════════════════════════════
    var _allProducts = []; // master list for search
    var _searchTimer = null;
    var _searchOpen = false;

    // Build master product list from the grid on page load
    (function buildProductIndex() {
        try {
            var grid = document.getElementById('productGrid');
            grid.querySelectorAll('button').forEach(function(btn) {
                try {
                    var onclickStr = btn.getAttribute('onclick') || '';
                    // Extract the JSON object from addToCart(...)
                    var match = onclickStr.match(/addToCart\((\{.*\})\)/);
                    if (match) {
                        _allProducts.push(JSON.parse(match[1]));
                    }
                } catch(e) {}
            });
            posLogToConsole('PRODUCT_INDEX', { count: _allProducts.length });
        } catch(e) {
            posLogToConsole('PRODUCT_INDEX_ERROR', { error: e.message });
        }
    })();

    function clearPosSearch() {
        try {
            var input = document.getElementById('posSearch');
            var clearBtn = document.getElementById('posSearchClear');
            var dropdown = document.getElementById('searchResults');
            input.value = '';
            clearBtn.classList.add('hidden');
            dropdown.classList.add('hidden');
            _searchOpen = false;
            // Show all products in grid again
            var grid = document.getElementById('productGrid');
            grid.querySelectorAll('button').forEach(function(b) { b.style.display = ''; });
            input.focus();
        } catch(e) {}
    }

    function addToCartFromSearch(product) {
        try {
            addToCart(product);
            // Don't clear search — user may want to add more from same search
            // Just flash the input briefly
            var input = document.getElementById('posSearch');
            input.style.borderColor = '#22c55e';
            setTimeout(function() { input.style.borderColor = ''; }, 400);
        } catch(e) {
            posLogToConsole('SEARCH_ADD_ERROR', { error: e.message });
        }
    }

    function renderSearchResults(results) {
        var dropdown = document.getElementById('searchResults');
        var clearBtn = document.getElementById('posSearchClear');
        var q = document.getElementById('posSearch').value.trim();

        if (!q || results.length === 0) {
            dropdown.classList.add('hidden');
            _searchOpen = false;
            // Show/hide clear button
            clearBtn.classList.toggle('hidden', !q);
            // If query but no results, show "no results" message
            if (q && results.length === 0) {
                dropdown.innerHTML = '<div class="p-4 text-center text-gray-400 text-sm"><svg class="w-8 h-8 mx-auto mb-1 opacity-40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>No products found for "' + q.replace(/</g,'&lt;') + '"</div>';
                dropdown.classList.remove('hidden');
                _searchOpen = true;
            }
            // Filter grid buttons by name
            filterGridByQuery(q);
            return;
        }

        clearBtn.classList.remove('hidden');
        _searchOpen = true;

        var html = results.slice(0, 10).map(function(p) {
            var pd = JSON.stringify({ id: p.id, name: p.name, price: p.price, image: p.image || '', sku: p.sku || '', barcode: p.barcode || '' })
                .replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            var img = p.image || '/uploads/no-image-sm.jpg';
            var skuHtml = p.sku ? '<span class="text-[10px] text-gray-400 bg-gray-100 px-1.5 py-0.5 rounded">' + p.sku + '</span>' : '';
            var barcodeHtml = p.barcode ? '<span class="text-[10px] text-gray-400 bg-gray-100 px-1.5 py-0.5 rounded font-mono">' + p.barcode + '</span>' : '';
            return '<button type="button" onclick="event.preventDefault(); addToCartFromSearch(' + pd + ')" class="w-full flex items-center gap-3 px-3 py-2.5 hover:bg-amber-50 transition-colors text-left border-b border-gray-50 last:border-0">' +
                '<img src="' + img + '" alt="" class="w-10 h-10 rounded-lg object-cover bg-gray-50 shrink-0">' +
                '<div class="flex-1 min-w-0">' +
                '<p class="text-sm font-medium text-gray-900 truncate">' + p.name + '</p>' +
                '<div class="flex items-center gap-1.5 mt-0.5">' + skuHtml + barcodeHtml + '</div>' +
                '</div>' +
                '<div class="text-right shrink-0">' +
                '<p class="text-sm font-bold text-amber-600">' + currencySymbol + ' ' + Number(p.price).toLocaleString() + '</p>' +
                '<p class="text-[10px] text-gray-400">Stock: ' + (p.stock_quantity || '—') + '</p>' +
                '</div>' +
                '<i data-lucide="plus-circle" class="w-5 h-5 text-gray-300 group-hover:text-amber-500 shrink-0"></i>' +
                '</button>';
        }).join('');

        if (results.length > 10) {
            html += '<div class="p-2 text-center text-xs text-gray-400 border-t border-gray-100">+' + (results.length - 10) + ' more results — type to narrow down</div>';
        }

        dropdown.innerHTML = html;
        dropdown.classList.remove('hidden');
        try { lucide.createIcons(); } catch(e) {}

        // Also filter the grid to highlight matching products
        filterGridByQuery(q);
    }

    function filterGridByQuery(q) {
        var grid = document.getElementById('productGrid');
        var btns = grid.querySelectorAll('button');
        if (!q) { btns.forEach(function(b) { b.style.display = ''; }); return; }
        var ql = q.toLowerCase();
        btns.forEach(function(b) {
            var text = (b.textContent || '').toLowerCase();
            b.style.display = text.indexOf(ql) >= 0 ? '' : 'none';
        });
    }

    function performSearch(query) {
        if (!query) { renderSearchResults([]); return; }
        var ql = query.toLowerCase();

        // Search in master product list
        var results = _allProducts.filter(function(p) {
            return (p.name && p.name.toLowerCase().indexOf(ql) >= 0) ||
                   (p.sku && p.sku.toLowerCase().indexOf(ql) >= 0) ||
                   (p.barcode && p.barcode.indexOf(ql) >= 0);
        });

        // If no local results, try API search
        if (results.length === 0 && ql.length >= 2) {
            posFetch('/api/pos/products?search=' + encodeURIComponent(query), { headers: apiHeaders() }).then(function(apiProducts) {
                if (Array.isArray(apiProducts) && apiProducts.length > 0) {
                    // Merge into master list and show
                    apiProducts.forEach(function(p) {
                        if (!_allProducts.find(function(ep) { return ep.id === p.id; })) {
                            _allProducts.push({ id: Number(p.id), name: p.name, price: Number(p.price), image: p.image || '', sku: p.sku || '', barcode: p.barcode || '', stock_quantity: p.stock_quantity });
                        }
                    });
                    performSearch(query); // re-search with updated list
                } else {
                    renderSearchResults([]);
                }
            }).catch(function() {
                renderSearchResults([]);
            });
            return;
        }

        renderSearchResults(results);
    }

    // Search input handler — debounced
    document.getElementById('posSearch').addEventListener('input', function() {
        try {
            var val = this.value.trim();
            document.getElementById('posSearchClear').classList.toggle('hidden', !val);

            clearTimeout(_searchTimer);
            var query = val;

            // Instant match for barcodes (no debounce — scanner input is fast)
            if (/^\d{4,}$/.test(query)) {
                // Looks like a barcode — instant search
                var barcodeMatch = _allProducts.find(function(p) { return p.barcode === query; });
                if (barcodeMatch) {
                    addToCart(barcodeMatch);
                    this.value = '';
                    document.getElementById('posSearchClear').classList.add('hidden');
                    document.getElementById('searchResults').classList.add('hidden');
                    return;
                }
            }

            _searchTimer = setTimeout(function() { performSearch(query); }, 150);
        } catch(e) {
            posLogToConsole('SEARCH_ERROR', { error: e.message });
        }
    });

    // Close search dropdown when clicking outside
    document.addEventListener('click', function(e) {
        try {
            var wrap = document.getElementById('posSearchWrap');
            if (wrap && !wrap.contains(e.target)) {
                document.getElementById('searchResults').classList.add('hidden');
                _searchOpen = false;
            }
        } catch(e) {}
    });

    // Keyboard nav for search results
    document.getElementById('posSearch').addEventListener('keydown', function(e) {
        try {
            var dropdown = document.getElementById('searchResults');
            if (!_searchOpen || dropdown.classList.contains('hidden')) return;
            var items = dropdown.querySelectorAll('button');
            if (!items.length) return;

            var current = dropdown.querySelector('button.bg-amber-50');
            var idx = -1;
            if (current) {
                items.forEach(function(item, i) { if (item === current) idx = i; });
                current.classList.remove('bg-amber-50');
            }

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                idx = (idx + 1) % items.length;
                items[idx].classList.add('bg-amber-50');
                items[idx].scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                idx = idx <= 0 ? items.length - 1 : idx - 1;
                items[idx].classList.add('bg-amber-50');
                items[idx].scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (idx >= 0 && items[idx]) {
                    items[idx].click();
                } else if (items.length > 0) {
                    items[0].click();
                }
            } else if (e.key === 'Escape') {
                dropdown.classList.add('hidden');
                _searchOpen = false;
            }
        } catch(e) {}
    });

    function filterCategory(catId) {
        try {
            document.querySelectorAll('.pos-cat-btn').forEach(function(b) {
                b.className = 'pos-cat-btn px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap bg-white text-gray-600 border border-gray-200 hover:border-amber-500';
            });
            if (event && event.target) {
                event.target.className = 'pos-cat-btn px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap bg-amber-600 text-white';
            }
            posFetch('/api/pos/products?category=' + catId, { headers: apiHeaders() }).then(function(products) {
                if (!Array.isArray(products)) return;
                // Rebuild product index
                _allProducts = products.map(function(p) {
                    return { id: Number(p.id), name: p.name, price: Number(p.price), image: p.image || '', sku: p.sku || '', barcode: p.barcode || '', stock_quantity: p.stock_quantity };
                });
                var grid = document.getElementById('productGrid');
                grid.innerHTML = products.map(function(p) {
                    var productData = JSON.stringify({ id: Number(p.id), name: p.name, price: Number(p.price), image: p.image || '' });
                    var safeData = productData.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                    return '<button type="button" onclick="event.preventDefault(); addToCart(' + safeData + ')" class="bg-white rounded-xl border border-gray-100 shadow-sm p-3 text-left hover:border-amber-300 hover:shadow-md transition-all group">' +
                        '<div class="aspect-square bg-gray-50 rounded-lg mb-2 flex items-center justify-center overflow-hidden">' +
                        '<img src="' + (p.image || '/uploads/no-image-sm.jpg') + '" alt="" class="w-full h-full object-cover group-hover:scale-105 transition-transform">' +
                        '</div>' +
                        '<p class="text-xs font-medium text-gray-900 truncate">' + p.name + '</p>' +
                        '<div class="flex items-center justify-between mt-1">' +
                        '<span class="text-sm font-bold text-amber-600">' + currencySymbol + ' ' + Number(p.price).toLocaleString() + '</span>' +
                        '<span class="text-[10px] text-gray-400">Stock: ' + p.stock_quantity + '</span>' +
                        '</div></button>';
                }).join('');
            }).catch(function(e) {
                posLogToConsole('FILTER_CATEGORY_ERROR', { catId: catId, error: e.message });
                if (e.isAuthError) return;
                posShowError('Failed to load products for category', e.message);
            });
        } catch(e) {
            posLogToConsole('FILTER_CATEGORY_UNEXPECTED', { error: e.message });
        }
    }

    // Enter key handlers — wrapped to prevent crashes if elements don't exist
    try { document.getElementById('amountReceived').addEventListener('keydown', function(e) { if (e.key === 'Enter') confirmPayment(); }); } catch(e) {}
    try { document.getElementById('transactionCode').addEventListener('keydown', function(e) { if (e.key === 'Enter') confirmPayment(); }); } catch(e) {}
    try { document.getElementById('transactionCodeManual').addEventListener('keydown', function(e) { if (e.key === 'Enter') confirmPayment(); }); } catch(e) {}

    try { lucide.createIcons(); } catch(e) { console.warn('[POS] lucide.createIcons() failed:', e.message); }
    console.log('%c[POS Terminal] Comprehensive error catching enabled. Auth detection, JSON validation, and all API calls are logged.', 'color: #d97706; font-weight: bold; font-size: 12px;');
    </script>
</body>
</html>