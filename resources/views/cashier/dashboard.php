<?php
if (!Auth::check() || !Auth::hasRole(['admin', 'super_admin', 'cashier'])) {
    Session::flash('error', 'Access denied.');
    Redirect::to('/');
}
$user = Auth::user();
$userId = Auth::id();

// Commission summary
$commPending = (float)(Database::selectOne("SELECT COALESCE(SUM(amount), 0) as total FROM commissions WHERE status = 'pending' AND user_id = ?", [$userId])['total'] ?? 0);
$commPaid = (float)(Database::selectOne("SELECT COALESCE(SUM(amount), 0) as total FROM commissions WHERE status = 'paid' AND user_id = ?", [$userId])['total'] ?? 0);
$commTotal = $commPending + $commPaid;

// Recent commissions
$recentComm = Database::select("SELECT c.*, o.order_number, o.total as order_total FROM commissions c LEFT JOIN orders o ON c.order_id = o.id WHERE c.user_id = ? ORDER BY c.created_at DESC LIMIT 10", [$userId]);

// Today's stats
$today = date('Y-m-d');
$todayOrders = Database::selectOne("SELECT COUNT(*) as cnt, COALESCE(SUM(total), 0) as total FROM orders WHERE cashier_id = ? AND DATE(created_at) = ? AND is_pos = 1", [$userId, $today]);
$todayCount = (int)($todayOrders['cnt'] ?? 0);
$todaySales = (float)($todayOrders['total'] ?? 0);

// Held sales
$heldSales = Database::select("SELECT * FROM pos_holds WHERE user_id = ? ORDER BY created_at DESC", [$userId]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier Dashboard - <?= e($user['name']) ?></title>
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
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <!-- Header -->
    <header class="bg-gray-900 text-white px-4 py-3 flex items-center justify-between shrink-0">
        <div class="flex items-center gap-3">
            <a href="/pos" class="flex items-center gap-2 hover:opacity-80 transition-opacity">
                <div class="w-8 h-8 bg-amber-600 rounded-lg flex items-center justify-center"><i data-lucide="shopping-bag" class="w-4 h-4"></i></div>
                <h1 class="text-sm font-heading font-bold">Cashier Dashboard</h1>
            </a>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-xs text-gray-400"><?= e($user['name']) ?></span>
            <a href="/pos" class="px-3 py-1.5 bg-amber-600 text-white rounded-lg text-xs font-medium hover:bg-amber-700 flex items-center gap-1.5 transition-colors">
                <i data-lucide="monitor" class="w-3.5 h-3.5"></i> POS Terminal
            </a>
            <a href="/logout" class="p-2 text-gray-400 hover:text-red-400 rounded-lg hover:bg-gray-800 transition-colors"><i data-lucide="log-out" class="w-5 h-5"></i></a>
        </div>
    </header>

    <main class="flex-1 p-6 max-w-6xl mx-auto w-full">
        <!-- Stats Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white border border-gray-200 rounded-2xl p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center"><i data-lucide="receipt" class="w-5 h-5 text-amber-600"></i></div>
                    <span class="text-xs text-gray-500">Today's Sales</span>
                </div>
                <p class="text-2xl font-bold text-gray-900"><?= $todayCount ?></p>
                <p class="text-xs text-gray-400 mt-1"><?= formatMoney($todaySales) ?></p>
            </div>
            <div class="bg-white border border-gray-200 rounded-2xl p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center"><i data-lucide="coins" class="w-5 h-5 text-green-600"></i></div>
                    <span class="text-xs text-gray-500">Commission Pending</span>
                </div>
                <p class="text-2xl font-bold text-green-600"><?= formatMoney($commPending) ?></p>
                <p class="text-xs text-gray-400 mt-1">Total earned: <?= formatMoney($commTotal) ?></p>
            </div>
            <div class="bg-white border border-gray-200 rounded-2xl p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center"><i data-lucide="wallet" class="w-5 h-5 text-blue-600"></i></div>
                    <span class="text-xs text-gray-500">Commission Paid</span>
                </div>
                <p class="text-2xl font-bold text-blue-600"><?= formatMoney($commPaid) ?></p>
            </div>
            <div class="bg-white border border-gray-200 rounded-2xl p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-orange-100 rounded-xl flex items-center justify-center"><i data-lucide="pause-circle" class="w-5 h-5 text-orange-600"></i></div>
                    <span class="text-xs text-gray-500">Held Sales</span>
                </div>
                <p class="text-2xl font-bold text-orange-600"><?= count($heldSales) ?></p>
                <?php if (count($heldSales) > 0): ?>
                <p class="text-xs text-amber-600 mt-1">Total: <?= formatMoney(array_sum(array_column($heldSales, 'total'))) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid lg:grid-cols-2 gap-6">
            <!-- Held Sales -->
            <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="font-heading font-bold text-gray-900 flex items-center gap-2">
                        <i data-lucide="pause-circle" class="w-5 h-5 text-orange-500"></i> Held Sales
                    </h2>
                    <a href="/pos" class="text-xs text-amber-600 hover:text-amber-700 font-medium">Go to POS →</a>
                </div>
                <div class="p-4 max-h-80 overflow-y-auto scrollbar-thin space-y-2">
                    <?php if (empty($heldSales)): ?>
                        <p class="text-sm text-gray-400 text-center py-8">No held sales</p>
                    <?php else: foreach ($heldSales as $h): ?>
                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl border border-gray-100">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs text-gray-500"><?= e($h['created_at']) ?></p>
                            <p class="text-sm font-medium text-gray-900"><?= (int)$h['items_count'] ?> item(s) · <span class="text-amber-600 font-bold"><?= formatMoney($h['total']) ?></span></p>
                            <?php if (!empty($h['notes'])): ?><p class="text-xs text-gray-400"><?= e($h['notes']) ?></p><?php endif; ?>
                        </div>
                        <a href="/pos" class="px-3 py-1.5 bg-amber-600 text-white rounded-lg text-xs font-medium hover:bg-amber-700 flex items-center gap-1 shrink-0">
                            <i data-lucide="rotate-ccw" class="w-3 h-3"></i> Restore
                        </a>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <!-- Commission History -->
            <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="font-heading font-bold text-gray-900 flex items-center gap-2">
                        <i data-lucide="coins" class="w-5 h-5 text-green-500"></i> Commission History
                    </h2>
                </div>
                <div class="p-4 max-h-80 overflow-y-auto scrollbar-thin space-y-2">
                    <?php if (empty($recentComm)): ?>
                        <p class="text-sm text-gray-400 text-center py-8">No commissions yet</p>
                    <?php else: foreach ($recentComm as $c): ?>
                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl border border-gray-100">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center <?= $c['status'] === 'paid' ? 'bg-green-100' : 'bg-amber-100' ?>">
                            <i data-lucide="<?= $c['status'] === 'paid' ? 'check-circle' : 'clock' ?>" class="w-4 h-4 <?= $c['status'] === 'paid' ? 'text-green-600' : 'text-amber-600' ?>"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900"><?= e($c['order_number']) ?></p>
                            <p class="text-xs text-gray-400"><?= e($c['created_at']) ?> · Order: <?= formatMoney($c['order_total']) ?> · <?= (float)$c['percentage'] ?>%</p>
                        </div>
                        <span class="text-sm font-bold <?= $c['status'] === 'paid' ? 'text-green-600' : 'text-amber-600' ?> shrink-0"><?= formatMoney($c['amount']) ?></span>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </main>

    <footer class="mt-auto bg-white border-t border-gray-100 px-6 py-3 text-center text-xs text-gray-400">
        &copy; <?= date('Y') ?> ShopSmart POS · Cashier Dashboard
    </footer>
    <script>lucide.createIcons();</script>
</body>
</html>