<?php
$stats = [
    'revenue' => Database::selectOne("SELECT COALESCE(SUM(total),0) as val FROM orders WHERE payment_status = 'paid'")['val'] ?? 0,
    'orders' => Database::count('orders'),
    'products' => Database::count('products'),
    'customers' => Database::count('users', "role = 'customer'"),
    'lowStock' => Database::count('products', 'stock_quantity <= low_stock_threshold AND is_active = 1'),
    'pendingOrders' => Database::count('orders', "status = 'pending'"),
    'returnedProducts' => Database::count('products', "product_status = 'returned'"),
    'discontinuedProducts' => Database::count('products', "product_status = 'discontinued'"),
    'returningSoonProducts' => Database::count('products', "product_status = 'out_of_stock_returning'"),
];
$recentOrders = Database::select("SELECT o.*, u.name as customer_name FROM orders o LEFT JOIN users u ON o.customer_id = u.id ORDER BY o.created_at DESC LIMIT 5");
$topProducts = Database::select("SELECT p.name, p.price, p.stock_quantity, SUM(oi.quantity) as sold FROM products p JOIN order_items oi ON p.id = oi.product_id GROUP BY p.id ORDER BY sold DESC LIMIT 5");
$lowStockProducts = Database::select("SELECT * FROM products WHERE stock_quantity <= low_stock_threshold AND is_active = 1 ORDER BY stock_quantity ASC LIMIT 5");

// Revenue by day (last 7 days)
$dailyRevenue = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $rev = Database::selectOne("SELECT COALESCE(SUM(total),0) as val FROM orders WHERE DATE(created_at) = ? AND payment_status = 'paid'", [$date])['val'] ?? 0;
    $ordersCount = Database::count('orders', "DATE(created_at) = ?", [$date]);
    $dailyRevenue[] = ['date' => date('D', strtotime($date)), 'full_date' => date('M j', strtotime($date)), 'revenue' => (float)$rev, 'orders' => (int)$ordersCount];
}

// Order status breakdown
$orderStatuses = Database::select("SELECT status, COUNT(*) as cnt, COALESCE(SUM(total),0) as total FROM orders GROUP BY status ORDER BY cnt DESC");

// Product status breakdown
$productStatuses = Database::select("SELECT COALESCE(product_status, 'active') as status, COUNT(*) as cnt FROM products GROUP BY product_status");

// Payment methods breakdown (last 30 days)
$paymentMethods = Database::select("SELECT payment_method, COUNT(*) as cnt, COALESCE(SUM(total),0) as total FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND payment_status = 'paid' GROUP BY payment_method ORDER BY cnt DESC");

// Monthly revenue comparison (last 6 months)
$monthlyRevenue = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-{$i} months"));
    $rev = (float)(Database::selectOne("SELECT COALESCE(SUM(total),0) as t FROM orders WHERE DATE_FORMAT(created_at, '%Y-%m') = ? AND payment_status = 'paid'", [$m])['t'] ?? 0);
    $cnt = (int)(Database::selectOne("SELECT COUNT(*) as c FROM orders WHERE DATE_FORMAT(created_at, '%Y-%m') = ?", [$m])['c'] ?? 0);
    $monthlyRevenue[] = ['month' => date('M', strtotime($m)), 'revenue' => $rev, 'orders' => $cnt];
}
?>

<div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-gray-500">Total Revenue</span>
                <div class="w-10 h-10 bg-amber-50 rounded-lg flex items-center justify-center"><i data-lucide="dollar-sign" class="w-5 h-5 text-amber-600"></i></div>
            </div>
            <p class="text-2xl font-bold text-gray-900"><?= formatMoney($stats['revenue']) ?></p>
            <p class="text-xs text-amber-600 mt-1 flex items-center gap-1"><i data-lucide="trending-up" class="w-3 h-3"></i> Lifetime</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-gray-500">Total Orders</span>
                <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center"><i data-lucide="shopping-bag" class="w-5 h-5 text-blue-600"></i></div>
            </div>
            <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['orders']) ?></p>
            <p class="text-xs text-gray-500 mt-1"><?= $stats['pendingOrders'] ?> pending</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-gray-500">Products</span>
                <div class="w-10 h-10 bg-amber-50 rounded-lg flex items-center justify-center"><i data-lucide="package" class="w-5 h-5 text-amber-600"></i></div>
            </div>
            <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['products']) ?></p>
            <p class="text-xs text-gray-500 mt-1"><span class="text-red-500"><?= $stats['lowStock'] ?> low stock</span> · <span class="text-blue-500"><?= $stats['returningSoonProducts'] ?> returning soon</span> · <span class="text-orange-500"><?= $stats['returnedProducts'] ?> returned</span> · <span class="text-gray-400"><?= $stats['discontinuedProducts'] ?> discontinued</span></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-gray-500">Customers</span>
                <div class="w-10 h-10 bg-purple-50 rounded-lg flex items-center justify-center"><i data-lucide="users" class="w-5 h-5 text-purple-600"></i></div>
            </div>
            <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['customers']) ?></p>
            <p class="text-xs text-gray-500 mt-1">Registered accounts</p>
        </div>
    </div>

    <!-- Main Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Revenue Chart (2/3 width) -->
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-heading font-semibold text-gray-900">Revenue Overview</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Last 7 days performance</p>
                </div>
                <span class="text-xs text-gray-500 bg-gray-50 px-2.5 py-1 rounded-lg">KES</span>
            </div>
            <div class="h-64">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <!-- Order Status Doughnut -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <div class="mb-4">
                <h3 class="font-heading font-semibold text-gray-900">Order Status</h3>
                <p class="text-xs text-gray-500 mt-0.5">Distribution by status</p>
            </div>
            <div class="h-52 flex items-center justify-center">
                <canvas id="orderStatusChart"></canvas>
            </div>
            <div class="mt-3 space-y-1.5 max-h-24 overflow-y-auto scrollbar-thin">
                <?php
                $statusColors = ['pending'=>'#eab308','processing'=>'#3b82f6','shipped'=>'#6366f1','delivered'=>'#10b981','paid'=>'#f59e0b','cancelled'=>'#ef4444','refunded'=>'#8b5cf6'];
                $totalOrders = array_sum(array_column($orderStatuses, 'cnt')) ?: 1;
                foreach ($orderStatuses as $os):
                    $pct = round($os['cnt'] / $totalOrders * 100);
                    $clr = $statusColors[$os['status']] ?? '#9ca3af';
                ?>
                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:<?= $clr ?>"></span>
                        <span class="text-gray-600 capitalize"><?= e($os['status']) ?></span>
                    </div>
                    <span class="font-medium text-gray-900"><?= $os['cnt'] ?> (<?= $pct ?>%)</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Second Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Product Status Breakdown -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <div class="mb-4">
                <h3 class="font-heading font-semibold text-gray-900">Product Status</h3>
                <p class="text-xs text-gray-500 mt-0.5">Inventory status breakdown</p>
            </div>
            <div class="h-48 flex items-center justify-center">
                <canvas id="productStatusChart"></canvas>
            </div>
            <div class="mt-3 space-y-1.5">
                <?php
                $pStatusColors = ['active'=>'#10b981','out_of_stock_returning'=>'#3b82f6','discontinued'=>'#9ca3af','returned'=>'#ef4444','draft'=>'#f59e0b'];
                $pStatusLabels = ['active'=>'Active','out_of_stock_returning'=>'Returning Soon','discontinued'=>'Discontinued','returned'=>'Returned','draft'=>'Draft'];
                $totalProducts = array_sum(array_column($productStatuses, 'cnt')) ?: 1;
                foreach ($productStatuses as $ps):
                    $pct = round($ps['cnt'] / $totalProducts * 100);
                    $clr = $pStatusColors[$ps['status']] ?? '#9ca3af';
                    $label = $pStatusLabels[$ps['status']] ?? ucfirst($ps['status']);
                ?>
                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:<?= $clr ?>"></span>
                        <span class="text-gray-600"><?= e($label) ?></span>
                    </div>
                    <span class="font-medium text-gray-900"><?= $ps['cnt'] ?> (<?= $pct ?>%)</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Payment Methods (last 30 days) -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <div class="mb-4">
                <h3 class="font-heading font-semibold text-gray-900">Payment Methods</h3>
                <p class="text-xs text-gray-500 mt-0.5">Last 30 days</p>
            </div>
            <div class="h-48 flex items-center justify-center">
                <canvas id="paymentMethodChart"></canvas>
            </div>
            <div class="mt-3 space-y-1.5">
                <?php
                $pmColors = ['#10b981','#3b82f6','#8b5cf6','#f59e0b','#ef4444','#ec4899'];
                $totalPayments = array_sum(array_column($paymentMethods, 'cnt')) ?: 1;
                foreach ($paymentMethods as $pi => $pm):
                    $clr = $pmColors[$pi % count($pmColors)];
                    $pct = round($pm['cnt'] / $totalPayments * 100);
                    $pmLabel = ucfirst(str_replace('_', ' ', $pm['payment_method'] ?? 'other'));
                ?>
                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:<?= $clr ?>"></span>
                        <span class="text-gray-600"><?= e($pmLabel) ?></span>
                    </div>
                    <span class="font-medium text-gray-900"><?= $pm['cnt'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Low Stock Alerts -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-heading font-semibold text-gray-900">Low Stock Alerts</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Needs restocking</p>
                </div>
                <span class="text-xs bg-red-50 text-red-600 px-2 py-0.5 rounded-full font-medium"><?= $stats['lowStock'] ?> items</span>
            </div>
            <div class="space-y-3 max-h-72 overflow-y-auto scrollbar-thin">
                <?php if (empty($lowStockProducts)): ?>
                    <div class="flex flex-col items-center justify-center py-8 text-gray-400">
                        <i data-lucide="check-circle" class="w-8 h-8 mb-2"></i>
                        <p class="text-sm">All stock levels healthy</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($lowStockProducts as $p): ?>
                    <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 transition-colors">
                        <div class="w-9 h-9 bg-red-50 rounded-lg flex items-center justify-center shrink-0">
                            <i data-lucide="alert-triangle" class="w-4 h-4 text-red-500"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate"><?= e($p['name']) ?></p>
                            <p class="text-xs text-gray-500">SKU: <?= e($p['sku'] ?? 'N/A') ?></p>
                        </div>
                        <span class="text-sm font-bold text-red-600"><?= $p['stock_quantity'] ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Monthly Revenue Trend -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="font-heading font-semibold text-gray-900">Monthly Revenue Trend</h3>
                <p class="text-xs text-gray-500 mt-0.5">6-month comparison</p>
            </div>
            <a href="/admin/reports" class="text-sm text-amber-600 hover:text-amber-700 font-medium flex items-center gap-1">
                View Reports <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </a>
        </div>
        <div class="h-56">
            <canvas id="monthlyRevenueChart"></canvas>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Orders -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-heading font-semibold text-gray-900">Recent Orders</h3>
                <a href="/admin/orders" class="text-sm text-amber-600 hover:text-amber-700 font-medium">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="text-left text-xs text-gray-500 border-b border-gray-100">
                        <th class="pb-2 font-medium">Order</th><th class="pb-2 font-medium">Customer</th><th class="pb-2 font-medium">Total</th><th class="pb-2 font-medium">Status</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-50">
                    <?php foreach ($recentOrders as $o): ?>
                        <tr class="hover:bg-gray-50/50">
                            <td class="py-2.5 font-mono text-xs"><?= e($o['order_number']) ?></td>
                            <td class="py-2.5"><?= e($o['customer_name'] ?? 'Walk-in') ?></td>
                            <td class="py-2.5 font-medium"><?= formatMoney($o['total']) ?></td>
                            <td class="py-2.5">
                                <?php
                                $statusColors = ['pending'=>'bg-yellow-100 text-yellow-700','paid'=>'bg-amber-100 text-amber-700','processing'=>'bg-blue-100 text-blue-700','shipped'=>'bg-indigo-100 text-indigo-700','delivered'=>'bg-amber-100 text-amber-700','cancelled'=>'bg-red-100 text-red-700'];
                                $color = $statusColors[$o['status']] ?? 'bg-gray-100 text-gray-700';
                                ?>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $color ?>"><?= ucfirst($o['status']) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentOrders)): ?>
                        <tr><td colspan="4" class="py-6 text-center text-gray-500">No orders yet</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Products -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-heading font-semibold text-gray-900">Top Selling Products</h3>
                <a href="/admin/reports" class="text-sm text-amber-600 hover:text-amber-700 font-medium">Reports</a>
            </div>
            <div class="h-56">
                <canvas id="topProductsChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
// Chart.js global defaults
Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
Chart.defaults.font.size = 11;
Chart.defaults.color = '#6b7280';
Chart.defaults.plugins.legend.display = false;

// Helper: create amber gradient (Chart.js 4 compatible)
function makeAmberGradient(context) {
    const chart = context.chart;
    const { ctx, chartArea } = chart;
    if (!chartArea) return 'rgba(245,158,11,0.1)';
    const g = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
    g.addColorStop(0, 'rgba(245,158,11,0.02)');
    g.addColorStop(1, 'rgba(245,158,11,0.25)');
    return g;
}

// 1. Revenue Line Chart (7 days)
try {
new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($dailyRevenue, 'full_date')) ?>,
        datasets: [{
            label: 'Revenue',
            data: <?= json_encode(array_column($dailyRevenue, 'revenue')) ?>,
            borderColor: '#f59e0b',
            backgroundColor: makeAmberGradient,
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointBackgroundColor: '#fff',
            pointBorderColor: '#f59e0b',
            pointBorderWidth: 2,
            pointHoverRadius: 6,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { callback: v => 'KSh ' + (v/1000).toFixed(0) + 'k' } },
            x: { grid: { display: false } }
        },
        plugins: { tooltip: { callbacks: { label: ctx => 'KSh ' + ctx.parsed.y.toLocaleString() } } }
    }
});
} catch(e) { console.warn('Revenue chart error:', e); }

// 2. Order Status Doughnut
try {
new Chart(document.getElementById('orderStatusChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_map(fn($o) => ucfirst($o['status']), $orderStatuses)) ?>,
        datasets: [{
            data: <?= json_encode(array_column($orderStatuses, 'cnt')) ?>,
            backgroundColor: <?= json_encode(array_map(fn($o) => ($statusColors[$o['status']] ?? '#9ca3af'), $orderStatuses)) ?>,
            borderWidth: 0, hoverOffset: 6
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { tooltip: { callbacks: { label: ctx => ctx.label + ': ' + ctx.parsed } } } }
});
} catch(e) { console.warn('Order status chart error:', e); }

// 3. Product Status Doughnut
try {
new Chart(document.getElementById('productStatusChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_map(fn($p) => ($pStatusLabels[$p['status']] ?? ucfirst($p['status'])), $productStatuses)) ?>,
        datasets: [{
            data: <?= json_encode(array_column($productStatuses, 'cnt')) ?>,
            backgroundColor: <?= json_encode(array_map(fn($p) => ($pStatusColors[$p['status']] ?? '#9ca3af'), $productStatuses)) ?>,
            borderWidth: 0, hoverOffset: 6
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, cutout: '65%' }
});
} catch(e) { console.warn('Product status chart error:', e); }

// 4. Payment Methods Doughnut
try {
new Chart(document.getElementById('paymentMethodChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_map(fn($p) => ucfirst(str_replace('_', ' ', $p['payment_method'] ?? 'other')), $paymentMethods)) ?>,
        datasets: [{
            data: <?= json_encode(array_column($paymentMethods, 'cnt')) ?>,
            backgroundColor: <?= json_encode(array_map(fn($p) => $pmColors[array_search($p['payment_method'], array_column($paymentMethods, 'payment_method')) % count($pmColors)], $paymentMethods)) ?>,
            borderWidth: 0, hoverOffset: 6
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, cutout: '65%' }
});
} catch(e) { console.warn('Payment method chart error:', e); }

// 5. Monthly Revenue Bar Chart
try {
new Chart(document.getElementById('monthlyRevenueChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($monthlyRevenue, 'month')) ?>,
        datasets: [{
            label: 'Revenue',
            data: <?= json_encode(array_column($monthlyRevenue, 'revenue')) ?>,
            backgroundColor: 'rgba(245,158,11,0.7)',
            hoverBackgroundColor: '#f59e0b',
            borderRadius: 6, borderSkipped: false,
            barPercentage: 0.6
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { callback: v => 'KSh ' + (v >= 1000 ? (v/1000).toFixed(0) + 'k' : v) } },
            x: { grid: { display: false } }
        },
        plugins: { tooltip: { callbacks: { label: ctx => 'KSh ' + ctx.parsed.y.toLocaleString() } } }
    }
});
} catch(e) { console.warn('Monthly revenue chart error:', e); }

// 6. Top Products Horizontal Bar
try {
const topProductsData = <?= json_encode(array_slice($topProducts, 0, 5)) ?>;
new Chart(document.getElementById('topProductsChart'), {
    type: 'bar',
    data: {
        labels: topProductsData.map(p => p.name.length > 20 ? p.name.slice(0,20) + '...' : p.name),
        datasets: [{
            label: 'Units Sold',
            data: topProductsData.map(p => p.sold || 0),
            backgroundColor: ['#f59e0b','#fbbf24','#fcd34d','#fde68a','#fef3c7'],
            borderRadius: 4, borderSkipped: false,
            barPercentage: 0.7
        }]
    },
    options: {
        indexAxis: 'y', responsive: true, maintainAspectRatio: false,
        scales: {
            x: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' } },
            y: { grid: { display: false } }
        }
    }
});
} catch(e) { console.warn('Top products chart error:', e); }

// Re-init lucide icons after dynamic content
lucide.createIcons();
</script>