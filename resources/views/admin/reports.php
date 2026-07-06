<?php
$from = Request::query('from', date('Y-m-01'));
$to = Request::query('to', date('Y-m-t'));
$totalSales = Database::selectOne("SELECT COALESCE(SUM(total),0) as t, COUNT(*) as c FROM orders WHERE DATE(created_at) BETWEEN ? AND ? AND payment_status = 'paid'", [$from, $to]);
$avgOrder = $totalSales['c'] > 0 ? $totalSales['t'] / $totalSales['c'] : 0;
$refundTotal = Database::selectOne("SELECT COALESCE(SUM(total),0) as t FROM orders WHERE DATE(created_at) BETWEEN ? AND ? AND status = 'refunded'", [$from, $to])['t'] ?? 0;
$returnedCount = Database::count('products', "product_status = 'returned'");
$discontinuedCount = Database::count('products', "product_status = 'discontinued'");
$returningSoonCount = Database::count('products', "product_status = 'out_of_stock_returning'");
$paymentBreakdown = Database::select("SELECT payment_method, COUNT(*) as cnt, SUM(total) as total FROM orders WHERE DATE(created_at) BETWEEN ? AND ? AND payment_status = 'paid' GROUP BY payment_method", [$from, $to]);
$topProducts = Database::select("SELECT p.name, SUM(oi.quantity) as sold, SUM(oi.total) as revenue FROM order_items oi JOIN products p ON oi.product_id = p.id JOIN orders o ON oi.order_id = o.id WHERE DATE(o.created_at) BETWEEN ? AND ? GROUP BY p.id ORDER BY revenue DESC LIMIT 10", [$from, $to]);

// Order status distribution
$orderStatusDist = Database::select("SELECT status, COUNT(*) as cnt, COALESCE(SUM(total),0) as total FROM orders WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY status ORDER BY cnt DESC", [$from, $to]);

// Sales by category
$categorySales = Database::select("SELECT c.name, SUM(oi.total) as revenue, SUM(oi.quantity) as sold FROM order_items oi JOIN products p ON oi.product_id = p.id JOIN categories c ON p.category_id = c.id JOIN orders o ON oi.order_id = o.id WHERE DATE(o.created_at) BETWEEN ? AND ? GROUP BY c.id ORDER BY revenue DESC LIMIT 8", [$from, $to]);

// Monthly data for chart
$monthlyData = [];
for ($i = 11; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-{$i} months"));
    $rev = (float)(Database::selectOne("SELECT COALESCE(SUM(total),0) as t FROM orders WHERE DATE_FORMAT(created_at, '%Y-%m') = ? AND payment_status = 'paid'", [$m])['t'] ?? 0);
    $cnt = (int)(Database::selectOne("SELECT COUNT(*) as c FROM orders WHERE DATE_FORMAT(created_at, '%Y-%m') = ?", [$m])['c'] ?? 0);
    $monthlyData[] = ['month' => date('M Y', strtotime($m)), 'revenue' => $rev, 'orders' => $cnt];
}

// Daily orders for the period
$dailyOrders = Database::select("SELECT DATE(created_at) as day, COUNT(*) as cnt, COALESCE(SUM(total),0) as total FROM orders WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY DATE(created_at) ORDER BY day", [$from, $to]);

// Products to be returned / discontinued
$returnedProducts = Database::select("SELECT p.name, p.sku, p.price, p.stock_quantity, p.updated_at FROM products p WHERE p.product_status = 'returned' ORDER BY p.updated_at DESC LIMIT 10");
$discontinuedProducts = Database::select("SELECT p.name, p.sku, p.price, p.stock_quantity, p.updated_at FROM products p WHERE p.product_status = 'discontinued' ORDER BY p.updated_at DESC LIMIT 10");
$returningSoonProducts = Database::select("SELECT p.name, p.sku, p.price, p.stock_quantity, p.updated_at FROM products p WHERE p.product_status = 'out_of_stock_returning' ORDER BY p.updated_at DESC LIMIT 10");
?>
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="font-heading font-semibold text-xl text-gray-900">Reports & Analytics</h1>
            <p class="text-sm text-gray-500 mt-0.5">Comprehensive business insights</p>
        </div>
        <form method="GET" class="flex gap-2">
            <input type="date" name="from" value="<?= $from ?>" class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
            <input type="date" name="to" value="<?= $to ?>" class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
            <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors">
                <i data-lucide="filter" class="w-4 h-4 inline -mt-0.5"></i> Apply
            </button>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 bg-amber-50 rounded-lg flex items-center justify-center"><i data-lucide="banknote" class="w-4 h-4 text-amber-600"></i></div>
                <span class="text-xs text-gray-500 font-medium">Total Sales</span>
            </div>
            <p class="text-xl font-bold text-gray-900"><?= formatMoney($totalSales['t'] ?? 0) ?></p>
            <p class="text-xs text-gray-500 mt-1"><?= number_format($totalSales['c'] ?? 0) ?> orders</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center"><i data-lucide="receipt" class="w-4 h-4 text-blue-600"></i></div>
                <span class="text-xs text-gray-500 font-medium">Avg Order</span>
            </div>
            <p class="text-xl font-bold text-gray-900"><?= formatMoney($avgOrder) ?></p>
            <p class="text-xs text-gray-500 mt-1">Per order value</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 bg-red-50 rounded-lg flex items-center justify-center"><i data-lucide="rotate-ccw" class="w-4 h-4 text-red-500"></i></div>
                <span class="text-xs text-gray-500 font-medium">Refunds</span>
            </div>
            <p class="text-xl font-bold text-gray-900"><?= formatMoney($refundTotal) ?></p>
            <p class="text-xs text-gray-500 mt-1"><?= $returnedCount ?> products returned</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center"><i data-lucide="clock" class="w-4 h-4 text-blue-600"></i></div>
                <span class="text-xs text-gray-500 font-medium">Returning Soon</span>
            </div>
            <p class="text-xl font-bold text-gray-900"><?= $returningSoonCount ?></p>
            <p class="text-xs text-gray-500 mt-1">Restocking soon</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center"><i data-lucide="package-x" class="w-4 h-4 text-gray-500"></i></div>
                <span class="text-xs text-gray-500 font-medium">Discontinued</span>
            </div>
            <p class="text-xl font-bold text-gray-900"><?= $discontinuedCount ?></p>
            <p class="text-xs text-gray-500 mt-1">Products discontinued</p>
        </div>
    </div>

    <!-- Revenue & Orders Chart -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="font-heading font-semibold text-gray-900">Monthly Revenue & Orders</h3>
                <p class="text-xs text-gray-500 mt-0.5">12-month trend with order volume</p>
            </div>
            <div class="flex items-center gap-4 text-xs">
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-amber-500"></span> Revenue</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-blue-400"></span> Orders</span>
            </div>
        </div>
        <div class="h-72">
            <canvas id="monthlyRevenueChart"></canvas>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Order Status Distribution -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <div class="mb-4">
                <h3 class="font-heading font-semibold text-gray-900">Order Status</h3>
                <p class="text-xs text-gray-500 mt-0.5">For selected period</p>
            </div>
            <div class="h-48 flex items-center justify-center">
                <canvas id="orderStatusChart"></canvas>
            </div>
            <div class="mt-3 space-y-1.5 max-h-32 overflow-y-auto scrollbar-thin">
                <?php
                $osColors = ['pending'=>'#eab308','processing'=>'#3b82f6','shipped'=>'#6366f1','delivered'=>'#10b981','paid'=>'#f59e0b','cancelled'=>'#ef4444','refunded'=>'#8b5cf6'];
                $totalO = array_sum(array_column($orderStatusDist, 'cnt')) ?: 1;
                foreach ($orderStatusDist as $os):
                    $clr = $osColors[$os['status']] ?? '#9ca3af';
                ?>
                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:<?= $clr ?>"></span>
                        <span class="text-gray-600 capitalize"><?= e($os['status']) ?></span>
                    </div>
                    <span class="font-medium text-gray-900"><?= $os['cnt'] ?> · <?= formatMoney($os['total']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Payment Methods -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <div class="mb-4">
                <h3 class="font-heading font-semibold text-gray-900">Payment Methods</h3>
                <p class="text-xs text-gray-500 mt-0.5">Sales breakdown by method</p>
            </div>
            <div class="h-48 flex items-center justify-center">
                <canvas id="paymentChart"></canvas>
            </div>
            <div class="mt-3 space-y-1.5 max-h-32 overflow-y-auto scrollbar-thin">
                <?php
                $pmClr = ['#10b981','#3b82f6','#8b5cf6','#f59e0b','#ef4444','#ec4899'];
                $totalP = array_sum(array_column($paymentBreakdown, 'cnt')) ?: 1;
                foreach ($paymentBreakdown as $pi => $pm):
                    $pct = round($pm['cnt'] / $totalP * 100);
                    $clr = $pmClr[$pi % count($pmClr)];
                    $pmLabel = ucfirst(str_replace('_', ' ', $pm['payment_method'] ?? 'other'));
                ?>
                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:<?= $clr ?>"></span>
                        <span class="text-gray-600"><?= e($pmLabel) ?></span>
                    </div>
                    <span class="font-medium text-gray-900"><?= formatMoney($pm['total']) ?> (<?= $pct ?>%)</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Sales by Category -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <div class="mb-4">
                <h3 class="font-heading font-semibold text-gray-900">Sales by Category</h3>
                <p class="text-xs text-gray-500 mt-0.5">Top performing categories</p>
            </div>
            <div class="h-56">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Top Products Table -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="font-heading font-semibold text-gray-900">Top Products</h3>
                <p class="text-xs text-gray-500 mt-0.5">Best sellers in selected period</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-left text-xs text-gray-500 border-b border-gray-100">
                    <th class="pb-2 font-medium">#</th><th class="pb-2 font-medium">Product</th><th class="pb-2 font-medium">Units Sold</th><th class="pb-2 font-medium">Revenue</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($topProducts as $i => $p): ?>
                    <tr class="hover:bg-gray-50/50">
                        <td class="py-2.5"><span class="w-6 h-6 bg-amber-50 rounded text-xs font-bold text-amber-600 flex items-center justify-center"><?= $i + 1 ?></span></td>
                        <td class="py-2.5 font-medium text-gray-900"><?= e($p['name']) ?></td>
                        <td class="py-2.5 text-gray-600"><?= number_format($p['sold']) ?></td>
                        <td class="py-2.5 font-medium"><?= formatMoney($p['revenue']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($topProducts)): ?>
                        <tr><td colspan="4" class="py-8 text-center text-gray-500">No sales data for this period</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Returned, Returning Soon & Discontinued Products -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Returned Products -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-red-50 rounded-lg flex items-center justify-center">
                        <i data-lucide="rotate-ccw" class="w-4 h-4 text-red-500"></i>
                    </div>
                    <div>
                        <h3 class="font-heading font-semibold text-gray-900">Returned Products</h3>
                        <p class="text-xs text-gray-500">Marked for return</p>
                    </div>
                </div>
                <span class="text-xs bg-red-50 text-red-600 px-2 py-0.5 rounded-full font-medium"><?= $returnedCount ?></span>
            </div>
            <div class="space-y-2 max-h-64 overflow-y-auto scrollbar-thin">
                <?php if (empty($returnedProducts)): ?>
                    <div class="flex flex-col items-center py-8 text-gray-400">
                        <i data-lucide="check-circle" class="w-6 h-6 mb-1"></i>
                        <p class="text-sm">No returned products</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($returnedProducts as $rp): ?>
                    <div class="flex items-center gap-3 p-2.5 rounded-lg border border-red-100 bg-red-50/50">
                        <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center shrink-0">
                            <i data-lucide="package" class="w-4 h-4 text-red-500"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate"><?= e($rp['name']) ?></p>
                            <p class="text-xs text-gray-500">SKU: <?= e($rp['sku'] ?? 'N/A') ?> · Stock: <?= $rp['stock_quantity'] ?></p>
                        </div>
                        <span class="text-sm font-bold text-red-600"><?= formatMoney($rp['price']) ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Returning Soon -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center">
                        <i data-lucide="clock" class="w-4 h-4 text-blue-600"></i>
                    </div>
                    <div>
                        <h3 class="font-heading font-semibold text-gray-900">Returning Soon</h3>
                        <p class="text-xs text-gray-500">Out of stock, restocking</p>
                    </div>
                </div>
                <span class="text-xs bg-blue-50 text-blue-600 px-2 py-0.5 rounded-full font-medium"><?= $returningSoonCount ?></span>
            </div>
            <div class="space-y-2 max-h-64 overflow-y-auto scrollbar-thin">
                <?php if (empty($returningSoonProducts)): ?>
                    <div class="flex flex-col items-center py-8 text-gray-400">
                        <i data-lucide="check-circle" class="w-6 h-6 mb-1"></i>
                        <p class="text-sm">No returning products</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($returningSoonProducts as $rp): ?>
                    <div class="flex items-center gap-3 p-2.5 rounded-lg border border-blue-100 bg-blue-50/50">
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center shrink-0">
                            <i data-lucide="clock" class="w-4 h-4 text-blue-600"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate"><?= e($rp['name']) ?></p>
                            <p class="text-xs text-gray-500">SKU: <?= e($rp['sku'] ?? 'N/A') ?> · Stock: <?= $rp['stock_quantity'] ?></p>
                        </div>
                        <span class="text-sm font-bold text-blue-600"><?= formatMoney($rp['price']) ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Discontinued Products -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center">
                        <i data-lucide="package-x" class="w-4 h-4 text-gray-500"></i>
                    </div>
                    <div>
                        <h3 class="font-heading font-semibold text-gray-900">Discontinued Products</h3>
                        <p class="text-xs text-gray-500">No longer available</p>
                    </div>
                </div>
                <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full font-medium"><?= $discontinuedCount ?></span>
            </div>
            <div class="space-y-2 max-h-64 overflow-y-auto scrollbar-thin">
                <?php if (empty($discontinuedProducts)): ?>
                    <div class="flex flex-col items-center py-8 text-gray-400">
                        <i data-lucide="check-circle" class="w-6 h-6 mb-1"></i>
                        <p class="text-sm">No discontinued products</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($discontinuedProducts as $dp): ?>
                    <div class="flex items-center gap-3 p-2.5 rounded-lg border border-gray-200 bg-gray-50/50">
                        <div class="w-8 h-8 bg-gray-200 rounded-lg flex items-center justify-center shrink-0">
                            <i data-lucide="package-x" class="w-4 h-4 text-gray-500"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate"><?= e($dp['name']) ?></p>
                            <p class="text-xs text-gray-500">SKU: <?= e($dp['sku'] ?? 'N/A') ?> · Stock: <?= $dp['stock_quantity'] ?></p>
                        </div>
                        <span class="text-sm font-medium text-gray-500"><?= formatMoney($dp['price']) ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Daily Orders Line Chart -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <div class="mb-4">
            <h3 class="font-heading font-semibold text-gray-900">Daily Orders (<?= date('M j', strtotime($from)) ?> - <?= date('M j', strtotime($to)) ?>)</h3>
            <p class="text-xs text-gray-500 mt-0.5">Order count and revenue per day</p>
        </div>
        <div class="h-56">
            <canvas id="dailyOrdersChart"></canvas>
        </div>
    </div>
</div>

<script>
Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
Chart.defaults.font.size = 11;
Chart.defaults.color = '#6b7280';
Chart.defaults.plugins.legend.display = false;

// Helper: create amber gradient (Chart.js 4 compatible)
function makeAmberGrad(context) {
    const chart = context.chart;
    const { ctx, chartArea } = chart;
    if (!chartArea) return 'rgba(245,158,11,0.1)';
    const g = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
    g.addColorStop(0, 'rgba(245,158,11,0.02)');
    g.addColorStop(1, 'rgba(245,158,11,0.2)');
    return g;
}

// 1. Monthly Revenue + Orders
try {
new Chart(document.getElementById('monthlyRevenueChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_map(fn($m) => substr($m['month'], 0, 3), $monthlyData)) ?>,
        datasets: [
            {
                label: 'Revenue',
                data: <?= json_encode(array_column($monthlyData, 'revenue')) ?>,
                backgroundColor: 'rgba(245,158,11,0.7)',
                hoverBackgroundColor: '#f59e0b',
                borderRadius: 5, borderSkipped: false,
                barPercentage: 0.5, yAxisID: 'y'
            },
            {
                label: 'Orders',
                data: <?= json_encode(array_column($monthlyData, 'orders')) ?>,
                type: 'line',
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59,130,246,0.1)',
                pointRadius: 3, pointBackgroundColor: '#fff',
                pointBorderColor: '#3b82f6', pointBorderWidth: 2,
                tension: 0.3, yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: true, position: 'top', labels: { boxWidth: 12, padding: 15, usePointStyle: true, pointStyle: 'circle' } } },
        scales: {
            y: { beginAtZero: true, position: 'left', grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { callback: v => 'KSh ' + (v >= 1000 ? (v/1000).toFixed(0) + 'k' : v) } },
            y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, ticks: { callback: v => v + ' orders' } },
            x: { grid: { display: false } }
        }
    }
});
} catch(e) { console.warn('Monthly revenue chart error:', e); }

// 2. Order Status Doughnut
try {
const osColors = <?= json_encode(array_map(fn($o) => $osColors[$o['status']] ?? '#9ca3af', $orderStatusDist)) ?>;
new Chart(document.getElementById('orderStatusChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_map(fn($o) => ucfirst($o['status']), $orderStatusDist)) ?>,
        datasets: [{ data: <?= json_encode(array_column($orderStatusDist, 'cnt')) ?>, backgroundColor: osColors, borderWidth: 0, hoverOffset: 6 }]
    },
    options: { responsive: true, maintainAspectRatio: false, cutout: '60%' }
});
} catch(e) { console.warn('Order status chart error:', e); }

// 3. Payment Methods
try {
const pmClr = ['#10b981','#3b82f6','#8b5cf6','#f59e0b','#ef4444','#ec4899'];
new Chart(document.getElementById('paymentChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_map(fn($p) => ucfirst(str_replace('_', ' ', $p['payment_method'] ?? 'other')), $paymentBreakdown)) ?>,
        datasets: [{ data: <?= json_encode(array_column($paymentBreakdown, 'total')) ?>, backgroundColor: pmClr.slice(0, <?= count($paymentBreakdown) ?>), borderWidth: 0, hoverOffset: 6 }]
    },
    options: { responsive: true, maintainAspectRatio: false, cutout: '60%', plugins: { tooltip: { callbacks: { label: ctx => 'KSh ' + ctx.parsed.toLocaleString() } } } }
});
} catch(e) { console.warn('Payment chart error:', e); }

// 4. Category Bar Chart
try {
const catColors = ['#f59e0b','#10b981','#3b82f6','#8b5cf6','#ef4444','#ec4899','#06b6d4','#84cc16'];
new Chart(document.getElementById('categoryChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_map(fn($c) => strlen($c['name']) > 12 ? substr($c['name'],0,12).'...' : $c['name'], $categorySales)) ?>,
        datasets: [{
            data: <?= json_encode(array_column($categorySales, 'revenue')) ?>,
            backgroundColor: catColors,
            borderRadius: 4, borderSkipped: false, barPercentage: 0.7
        }]
    },
    options: {
        indexAxis: 'y', responsive: true, maintainAspectRatio: false,
        scales: { x: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { callback: v => 'KSh ' + (v >= 1000 ? (v/1000).toFixed(0) + 'k' : v) } }, y: { grid: { display: false } } }
    }
});
} catch(e) { console.warn('Category chart error:', e); }

// 5. Daily Orders
try {
const dailyLabels = <?= json_encode(array_map(fn($d) => date('M j', strtotime($d['day'])), $dailyOrders)) ?>;
const dailyTotals = <?= json_encode(array_column($dailyOrders, 'total')) ?>;
const dailyCounts = <?= json_encode(array_column($dailyOrders, 'cnt')) ?>;
new Chart(document.getElementById('dailyOrdersChart'), {
    type: 'line',
    data: {
        labels: dailyLabels,
        datasets: [{
            label: 'Revenue',
            data: dailyTotals,
            borderColor: '#f59e0b', backgroundColor: makeAmberGrad, fill: true,
            tension: 0.4, pointRadius: 3, pointBackgroundColor: '#fff',
            pointBorderColor: '#f59e0b', pointBorderWidth: 2, yAxisID: 'y'
        },{
            label: 'Orders',
            data: dailyCounts,
            type: 'bar',
            backgroundColor: 'rgba(59,130,246,0.5)',
            borderRadius: 3, borderSkipped: false,
            barPercentage: 0.4, yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: true, position: 'top', labels: { boxWidth: 12, padding: 15, usePointStyle: true, pointStyle: 'circle' } } },
        scales: {
            y: { beginAtZero: true, position: 'left', grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { callback: v => 'KSh ' + (v >= 1000 ? (v/1000).toFixed(0) + 'k' : v) } },
            y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, ticks: { stepSize: 1 } },
            x: { grid: { display: false } }
        }
    }
});
} catch(e) { console.warn('Daily orders chart error:', e); }

lucide.createIcons();
</script>