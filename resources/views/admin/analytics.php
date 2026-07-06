<?php
// Date range filter
$range = $_GET['range'] ?? '7d';
switch ($range) {
    case 'today':
        $dateCondition = "DATE(created_at) = CURDATE()";
        $dateLabel = 'Today';
        break;
    case '30d':
        $dateCondition = "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $dateLabel = 'Last 30 Days';
        break;
    case 'all':
        $dateCondition = "1=1";
        $dateLabel = 'All Time';
        break;
    case '7d':
    default:
        $dateCondition = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $dateLabel = 'Last 7 Days';
        break;
}

// --- Stats Cards (always today) ---
$todayViews = (int)(Database::selectOne("SELECT COUNT(*) as c FROM page_views WHERE DATE(created_at) = CURDATE()")['c'] ?? 0);
$todayVisitors = (int)(Database::selectOne("SELECT COUNT(DISTINCT session_id) as c FROM page_views WHERE DATE(created_at) = CURDATE()")['c'] ?? 0);
$topPageToday = Database::selectOne("SELECT url, COUNT(*) as c FROM page_views WHERE DATE(created_at) = CURDATE() GROUP BY url ORDER BY c DESC LIMIT 1");
$topPageLabel = $topPageToday ? ($topPageToday['url'] . ' (' . $topPageToday['c'] . ')') : 'N/A';

// Bounce rate: sessions with only 1 pageview / total sessions (today)
$totalSessionsToday = (int)(Database::selectOne("SELECT COUNT(DISTINCT session_id) as c FROM page_views WHERE DATE(created_at) = CURDATE()")['c'] ?? 0);
$bouncedSessions = (int)(Database::selectOne("SELECT COUNT(*) as c FROM (SELECT session_id FROM page_views WHERE DATE(created_at) = CURDATE() GROUP BY session_id HAVING COUNT(*) = 1) as t")['c'] ?? 0);
$bounceRate = $totalSessionsToday > 0 ? round(($bouncedSessions / $totalSessionsToday) * 100, 1) : 0;

// --- Line chart: Page views over last 7 days ---
$dailyViews = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-{$i} days"));
    $dayLabel = date('M j', strtotime("-{$i} days"));
    $count = (int)(Database::selectOne("SELECT COUNT(*) as c FROM page_views WHERE DATE(created_at) = ?", [$day])['c'] ?? 0);
    $dailyViews[] = ['day' => $dayLabel, 'count' => $count];
}

// --- Doughnut: Device breakdown ---
$deviceData = Database::select("SELECT device_type, COUNT(*) as c FROM page_views WHERE $dateCondition GROUP BY device_type ORDER BY c DESC");
$deviceLabels = [];
$deviceCounts = [];
$deviceColors = ['desktop' => '#d97706', 'mobile' => '#059669', 'tablet' => '#2563eb'];
$deviceColorArr = [];
foreach ($deviceData as $d) {
    $deviceLabels[] = ucfirst($d['device_type'] ?? 'Unknown');
    $deviceCounts[] = (int)$d['c'];
    $deviceColorArr[] = $deviceColors[strtolower($d['device_type'] ?? '')] ?? '#6b7280';
}

// --- Bar chart: Top 10 pages (last 30 days) ---
$topPages = Database::select("SELECT url, COUNT(*) as c FROM page_views WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY url ORDER BY c DESC LIMIT 10");
$topPagesLabels = [];
$topPagesCounts = [];
foreach ($topPages as $p) {
    $label = parse_url($p['url'], PHP_URL_PATH) ?: '/';
    $topPagesLabels[] = strlen($label) > 35 ? substr($label, 0, 32) . '...' : $label;
    $topPagesCounts[] = (int)$p['c'];
}

// --- Horizontal bar: Top browsers ---
$browserData = Database::select("SELECT browser, COUNT(*) as c FROM page_views WHERE $dateCondition GROUP BY browser ORDER BY c DESC LIMIT 8");
$browserLabels = [];
$browserCounts = [];
$browserColors = ['#d97706','#059669','#2563eb','#dc2626','#7c3aed','#0891b2','#c026d3','#65a30d'];
foreach ($browserData as $b) {
    $browserLabels[] = ucfirst($b['browser'] ?? 'Unknown');
    $browserCounts[] = (int)$b['c'];
}

// --- Recent visitors table (paginated, 50 per page) ---
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;
$totalRecords = (int)(Database::selectOne("SELECT COUNT(*) as c FROM page_views WHERE $dateCondition")['c'] ?? 0);
$totalPages = max(1, ceil($totalRecords / $perPage));
$recentVisits = Database::select("SELECT url, referrer, device_type, browser, os, created_at FROM page_views WHERE $dateCondition ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
?>
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="font-heading font-semibold text-xl text-gray-900">Visitor Analytics</h1>
            <p class="text-sm text-gray-500 mt-0.5">Track website traffic and visitor behavior</p>
        </div>
        <!-- Date Range Filter -->
        <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
            <a href="?range=today" class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors <?= $range === 'today' ? 'bg-white text-amber-700 shadow-sm' : 'text-gray-600 hover:text-gray-900' ?>">Today</a>
            <a href="?range=7d" class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors <?= $range === '7d' ? 'bg-white text-amber-700 shadow-sm' : 'text-gray-600 hover:text-gray-900' ?>">Last 7 Days</a>
            <a href="?range=30d" class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors <?= $range === '30d' ? 'bg-white text-amber-700 shadow-sm' : 'text-gray-600 hover:text-gray-900' ?>">Last 30 Days</a>
            <a href="?range=all" class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors <?= $range === 'all' ? 'bg-white text-amber-700 shadow-sm' : 'text-gray-600 hover:text-gray-900' ?>">All Time</a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Page Views -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Page Views</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1"><?= number_format($todayViews) ?></p>
                    <p class="text-xs text-gray-400 mt-1">Today</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-amber-50 flex items-center justify-center">
                    <i data-lucide="eye" class="w-5 h-5 text-amber-600"></i>
                </div>
            </div>
        </div>
        <!-- Unique Visitors -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Unique Visitors</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1"><?= number_format($todayVisitors) ?></p>
                    <p class="text-xs text-gray-400 mt-1">Today</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-emerald-50 flex items-center justify-center">
                    <i data-lucide="users" class="w-5 h-5 text-emerald-600"></i>
                </div>
            </div>
        </div>
        <!-- Most Visited Page -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div class="min-w-0 flex-1 mr-3">
                    <p class="text-sm text-gray-500">Top Page Today</p>
                    <p class="text-sm font-semibold text-gray-900 mt-1 truncate" title="<?= e($topPageToday['url'] ?? '') ?>"><?= e($topPageLabel) ?></p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-blue-50 flex items-center justify-center shrink-0">
                    <i data-lucide="trending-up" class="w-5 h-5 text-blue-600"></i>
                </div>
            </div>
        </div>
        <!-- Bounce Rate -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Bounce Rate</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1"><?= $bounceRate ?>%</p>
                    <p class="text-xs text-gray-400 mt-1">Today</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-red-50 flex items-center justify-center">
                    <i data-lucide="mouse-pointer-click" class="w-5 h-5 text-red-500"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 1: Line + Doughnut -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Page Views Trend (7 days) -->
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="font-heading font-semibold text-gray-900 mb-4">Page Views Trend <span class="text-sm font-normal text-gray-400">(Last 7 Days)</span></h3>
            <div style="height: 280px;">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
        <!-- Device Breakdown -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="font-heading font-semibold text-gray-900 mb-4">Device Breakdown <span class="text-sm font-normal text-gray-400">(<?= $dateLabel ?>)</span></h3>
            <div style="height: 280px;" class="flex items-center justify-center">
                <canvas id="deviceChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Charts Row 2: Top Pages + Top Browsers -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Pages -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="font-heading font-semibold text-gray-900 mb-4">Top 10 Pages <span class="text-sm font-normal text-gray-400">(Last 30 Days)</span></h3>
            <div style="height: 300px;">
                <canvas id="topPagesChart"></canvas>
            </div>
        </div>
        <!-- Top Browsers -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="font-heading font-semibold text-gray-900 mb-4">Top Browsers <span class="text-sm font-normal text-gray-400">(<?= $dateLabel ?>)</span></h3>
            <div style="height: 300px;">
                <canvas id="browserChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Visitors Table -->
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-heading font-semibold text-gray-900">Recent Visits</h3>
            <span class="text-sm text-gray-400"><?= number_format($totalRecords) ?> total records</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left">
                        <th class="px-5 py-3 font-medium text-gray-500">URL</th>
                        <th class="px-5 py-3 font-medium text-gray-500">Referrer</th>
                        <th class="px-5 py-3 font-medium text-gray-500">Device</th>
                        <th class="px-5 py-3 font-medium text-gray-500">Browser</th>
                        <th class="px-5 py-3 font-medium text-gray-500">OS</th>
                        <th class="px-5 py-3 font-medium text-gray-500">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($recentVisits)): ?>
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-gray-400">
                                <i data-lucide="inbox" class="w-10 h-10 mx-auto mb-2 text-gray-300"></i>
                                <p>No visit data yet. Start browsing the storefront to see data here.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentVisits as $v): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-5 py-3 max-w-[200px] truncate" title="<?= e($v['url']) ?>">
                                    <span class="font-mono text-xs text-gray-700"><?= e($v['url']) ?></span>
                                </td>
                                <td class="px-5 py-3 max-w-[180px] truncate" title="<?= e($v['referrer']) ?>">
                                    <?php if (!empty($v['referrer'])): ?>
                                        <span class="text-xs text-gray-500"><?= e($v['referrer']) ?></span>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-300">Direct</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-3">
                                    <?php
                                        $devIcon = 'monitor';
                                        $devColor = 'text-blue-600 bg-blue-50';
                                        if (($v['device_type'] ?? '') === 'mobile') { $devIcon = 'smartphone'; $devColor = 'text-emerald-600 bg-emerald-50'; }
                                        elseif (($v['device_type'] ?? '') === 'tablet') { $devIcon = 'tablet'; $devColor = 'text-purple-600 bg-purple-50'; }
                                    ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium capitalize <?= $devColor ?>">
                                        <i data-lucide="<?= $devIcon ?>" class="w-3 h-3"></i>
                                        <?= e(ucfirst($v['device_type'] ?? 'desktop')) ?>
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-gray-600"><?= e(ucfirst($v['browser'] ?? 'Unknown')) ?></td>
                                <td class="px-5 py-3 text-gray-600"><?= e($v['os'] ?? 'Unknown') ?></td>
                                <td class="px-5 py-3 text-gray-500 whitespace-nowrap"><?= date('M j, H:i', strtotime($v['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages > 1): ?>
        <div class="px-5 py-3 border-t border-gray-100 flex items-center justify-between">
            <p class="text-sm text-gray-500">Page <?= $page ?> of <?= $totalPages ?></p>
            <div class="flex gap-1">
                <?php if ($page > 1): ?>
                    <a href="?range=<?= urlencode($range) ?>&page=<?= $page - 1 ?>" class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 text-gray-700">&larr; Prev</a>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?range=<?= urlencode($range) ?>&page=<?= $page + 1 ?>" class="px-3 py-1.5 text-sm bg-amber-600 text-white rounded-lg hover:bg-amber-700">Next &rarr;</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const amber = '#d97706';
    const amberLight = 'rgba(217,119,6,0.1)';

    // Trend Chart - Line
    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($dailyViews, 'day')) ?>,
            datasets: [{
                label: 'Page Views',
                data: <?= json_encode(array_column($dailyViews, 'count')) ?>,
                borderColor: amber,
                backgroundColor: amberLight,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: amber,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(0,0,0,0.05)' } },
                x: { grid: { display: false } }
            }
        }
    });

    // Device Chart - Doughnut
    new Chart(document.getElementById('deviceChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($deviceLabels) ?>,
            datasets: [{
                data: <?= json_encode($deviceCounts) ?>,
                backgroundColor: <?= json_encode($deviceColorArr) ?>,
                borderWidth: 0,
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true, pointStyle: 'circle' } }
            }
        }
    });

    // Top Pages Chart - Bar
    new Chart(document.getElementById('topPagesChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($topPagesLabels) ?>,
            datasets: [{
                label: 'Views',
                data: <?= json_encode($topPagesCounts) ?>,
                backgroundColor: amber,
                borderRadius: 4,
                barThickness: 24
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(0,0,0,0.05)' } },
                y: { grid: { display: false } }
            }
        }
    });

    // Browser Chart - Horizontal Bar
    const browserColors = <?= json_encode($browserColors) ?>;
    new Chart(document.getElementById('browserChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($browserLabels) ?>,
            datasets: [{
                label: 'Views',
                data: <?= json_encode($browserCounts) ?>,
                backgroundColor: browserColors.slice(0, <?= count($browserLabels) ?>),
                borderRadius: 4,
                barThickness: 20
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(0,0,0,0.05)' } },
                y: { grid: { display: false } }
            }
        }
    });

    // Re-init lucide for dynamically added icons
    if (window.lucide) lucide.createIcons();
});
</script>