<?php
// --- Filters & Pagination ---
$page = (int)($_GET['page'] ?? 1);
$search = $_GET['search'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// --- Stat Cards ---
$stats = Database::selectOne("SELECT COUNT(*) as c, SUM(stock_quantity) as total_units, SUM(stock_quantity * price) as value FROM products WHERE is_active = 1");
$totalItems = (int)($stats['c'] ?? 0);
$inventoryValue = (float)($stats['value'] ?? 0);
$totalUnits = (int)($stats['total_units'] ?? 0);
$lowStockCount = Database::count('products', 'is_active = 1 AND stock_quantity > 0 AND stock_quantity <= low_stock_threshold');
$outOfStockCount = Database::count('products', 'is_active = 1 AND stock_quantity = 0');

// --- Categories for filter dropdown ---
$categories = Database::select("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");

// --- Build WHERE clause for product listing ---
$where = "1=1";
$params = [];
if ($search) { $where .= " AND (p.name LIKE ? OR p.sku LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($categoryFilter) { $where .= " AND p.category_id = ?"; $params[] = $categoryFilter; }
if ($statusFilter === 'out') { $where .= " AND p.stock_quantity = 0"; }
elseif ($statusFilter === 'low') { $where .= " AND p.stock_quantity > 0 AND p.stock_quantity <= p.low_stock_threshold"; }
elseif ($statusFilter === 'in') { $where .= " AND p.stock_quantity > p.low_stock_threshold"; }

// --- Count for pagination ---
$totalProducts = (int)Database::selectOne("SELECT COUNT(*) as cnt FROM products p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN brands b ON p.brand_id = b.id WHERE $where", $params)['cnt'];
$lastPage = (int)ceil($totalProducts / 20);
if ($page < 1) $page = 1;
if ($page > $lastPage && $lastPage > 0) $page = $lastPage;
$offset = ($page - 1) * 20;

// --- Fetch products ---
$products = Database::select(
    "SELECT p.*, c.name as category_name, b.name as brand_name, (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1) as image FROM products p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN brands b ON p.brand_id = b.id WHERE $where ORDER BY p.name LIMIT 20 OFFSET $offset",
    $params
);

// --- Stock Adjustment History (last 10) ---
$adjustments = Database::select(
    "SELECT sa.*, p.name as product_name, u.name as adjusted_by_name FROM stock_adjustments sa JOIN products p ON sa.product_id = p.id LEFT JOIN users u ON sa.adjusted_by = u.id ORDER BY sa.created_at DESC LIMIT 10"
);

// --- Build pagination base URL (preserve filters, no page param) ---
$queryParts = array_filter(['search' => $search, 'category' => $categoryFilter, 'status' => $statusFilter], fn($v) => $v !== '');
$baseUrl = '/admin/inventory' . ($queryParts ? '?' . http_build_query($queryParts) : '');
?>

<div class="space-y-6">
    <h1 class="font-heading font-semibold text-xl text-gray-900">Inventory Management</h1>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-500">Total Items</span>
                <div class="w-9 h-9 bg-amber-50 rounded-lg flex items-center justify-center">
                    <i data-lucide="package" class="w-5 h-5 text-amber-600"></i>
                </div>
            </div>
            <p class="text-2xl font-bold mt-2"><?= number_format($totalItems) ?></p>
            <p class="text-xs text-gray-500"><?= number_format($totalUnits) ?> total units</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-500">Inventory Value</span>
                <div class="w-9 h-9 bg-teal-50 rounded-lg flex items-center justify-center">
                    <i data-lucide="dollar-sign" class="w-5 h-5 text-teal-600"></i>
                </div>
            </div>
            <p class="text-2xl font-bold mt-2"><?= formatMoney($inventoryValue) ?></p>
            <p class="text-xs text-gray-500">Stock &times; Price</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-500">Low Stock</span>
                <div class="w-9 h-9 bg-amber-50 rounded-lg flex items-center justify-center">
                    <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-600"></i>
                </div>
            </div>
            <p class="text-2xl font-bold mt-2 text-amber-600"><?= number_format($lowStockCount) ?></p>
            <p class="text-xs text-gray-500">Below threshold</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-500">Out of Stock</span>
                <div class="w-9 h-9 bg-red-50 rounded-lg flex items-center justify-center">
                    <i data-lucide="x-circle" class="w-5 h-5 text-red-600"></i>
                </div>
            </div>
            <p class="text-2xl font-bold mt-2 text-red-600"><?= number_format($outOfStockCount) ?></p>
            <p class="text-xs text-gray-500">Needs restocking</p>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <form method="GET" action="/admin/inventory" class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by name or SKU..." class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent">
            </div>
            <select name="category" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                <option value="">All Categories</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $categoryFilter == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                <option value="">All Status</option>
                <option value="in" <?= $statusFilter === 'in' ? 'selected' : '' ?>>In Stock</option>
                <option value="low" <?= $statusFilter === 'low' ? 'selected' : '' ?>>Low Stock</option>
                <option value="out" <?= $statusFilter === 'out' ? 'selected' : '' ?>>Out of Stock</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors flex items-center justify-center gap-2">
                <i data-lucide="filter" class="w-4 h-4"></i> Filter
            </button>
            <?php if ($search || $categoryFilter || $statusFilter): ?>
            <a href="/admin/inventory" class="px-4 py-2 border border-gray-200 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors flex items-center justify-center gap-2">
                <i data-lucide="x" class="w-4 h-4"></i> Clear
            </a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Product Table -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-medium text-gray-900">All Products</h3>
            <span class="text-xs text-gray-500"><?= number_format($totalProducts) ?> product<?= $totalProducts !== 1 ? 's' : '' ?></span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Product</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">SKU</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600 hidden md:table-cell">Category</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600 hidden lg:table-cell">Brand</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-600">Price</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-600 hidden lg:table-cell">Cost</th>
                        <th class="text-center px-4 py-3 font-medium text-gray-600">Stock</th>
                        <th class="text-center px-4 py-3 font-medium text-gray-600 hidden md:table-cell">Threshold</th>
                        <th class="text-center px-4 py-3 font-medium text-gray-600 hidden sm:table-cell">Status</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-600">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($products as $p):
                        $stockClass = $p['stock_quantity'] === 0 ? 'text-red-600 bg-red-50' : ($p['stock_quantity'] <= $p['low_stock_threshold'] ? 'text-amber-600 bg-amber-50' : 'text-amber-700 bg-amber-50');
                        $stockDot = $p['stock_quantity'] === 0 ? 'bg-red-500' : ($p['stock_quantity'] <= $p['low_stock_threshold'] ? 'bg-amber-500' : 'bg-amber-500');
                    ?>
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <img src="<?= e($p['image'] ?? '/uploads/no-image-sm.jpg') ?>" alt="" class="w-9 h-9 rounded-lg object-cover bg-gray-100 shrink-0">
                                <span class="font-medium text-gray-900 truncate max-w-[180px]"><?= e($p['name']) ?></span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-500 font-mono text-xs"><?= e($p['sku'] ?? '-') ?></td>
                        <td class="px-4 py-3 text-gray-600 hidden md:table-cell"><?= e($p['category_name'] ?? '-') ?></td>
                        <td class="px-4 py-3 text-gray-600 hidden lg:table-cell"><?= e($p['brand_name'] ?? '-') ?></td>
                        <td class="px-4 py-3 text-right font-medium"><?= formatMoney($p['price']) ?></td>
                        <td class="px-4 py-3 text-right text-gray-500 hidden lg:table-cell"><?= formatMoney($p['cost_price']) ?></td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold <?= $stockClass ?>">
                                <span class="w-1.5 h-1.5 rounded-full <?= $stockDot ?>"></span>
                                <?= number_format($p['stock_quantity']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center text-gray-500 hidden md:table-cell"><?= $p['low_stock_threshold'] ?></td>
                        <td class="px-4 py-3 text-center hidden sm:table-cell">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $p['is_active'] ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500' ?>">
                                <?= $p['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" onclick="openAdjustModal(<?= $p['id'] ?>, '<?= e(addslashes($p['name'])) ?>', <?= $p['stock_quantity'] ?>)" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-amber-700 bg-amber-50 hover:bg-amber-100 rounded-lg transition-colors">
                                <i data-lucide="sliders-horizontal" class="w-3.5 h-3.5"></i> Adjust
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="10" class="px-4 py-16 text-center">
                            <div class="flex flex-col items-center gap-2">
                                <i data-lucide="package-open" class="w-10 h-10 text-gray-300"></i>
                                <p class="text-gray-500 font-medium">No products found</p>
                                <p class="text-xs text-gray-400">Try adjusting your search or filters</p>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($lastPage > 1): ?>
        <div class="flex items-center justify-between px-4 py-3 border-t border-gray-100">
            <p class="text-xs text-gray-500">
                Showing <?= $offset + 1 ?>-<?= min($offset + 20, $totalProducts) ?> of <?= number_format($totalProducts) ?>
            </p>
            <nav class="flex items-center gap-1">
                <?php if ($page > 1): ?>
                <a href="<?= $baseUrl ?><?= $queryParts ? '&' : '?' ?>page=<?= $page - 1 ?>" class="px-3 py-1.5 text-sm rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors">
                    <i data-lucide="chevron-left" class="w-4 h-4"></i>
                </a>
                <?php endif; ?>

                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($lastPage, $page + 2);
                if ($startPage > 1) {
                    echo '<a href="' . $baseUrl . ($queryParts ? '&' : '?') . 'page=1" class="px-3 py-1.5 text-sm rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors">1</a>';
                    if ($startPage > 2) echo '<span class="px-1 text-gray-400">...</span>';
                }
                for ($i = $startPage; $i <= $endPage; $i++):
                    $active = $i === $page;
                ?>
                <a href="<?= $baseUrl ?><?= $queryParts ? '&' : '?' ?>page=<?= $i ?>" class="px-3 py-1.5 text-sm rounded-lg transition-colors <?= $active ? 'bg-amber-600 text-white' : 'border border-gray-200 text-gray-600 hover:bg-gray-50' ?>"><?= $i ?></a>
                <?php endfor;
                if ($endPage < $lastPage) {
                    if ($endPage < $lastPage - 1) echo '<span class="px-1 text-gray-400">...</span>';
                    echo '<a href="' . $baseUrl . ($queryParts ? '&' : '?') . 'page=' . $lastPage . '" class="px-3 py-1.5 text-sm rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors">' . $lastPage . '</a>';
                }
                ?>

                <?php if ($page < $lastPage): ?>
                <a href="<?= $baseUrl ?><?= $queryParts ? '&' : '?' ?>page=<?= $page + 1 ?>" class="px-3 py-1.5 text-sm rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors">
                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </a>
                <?php endif; ?>
            </nav>
        </div>
        <?php endif; ?>
    </div>

    <!-- Stock Adjustment History -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-medium text-gray-900">Recent Stock Adjustments</h3>
            <span class="text-xs text-gray-500">Last 10 records</span>
        </div>
        <?php if (empty($adjustments)): ?>
        <div class="px-4 py-10 text-center text-gray-400 text-sm">
            <i data-lucide="clock" class="w-8 h-8 mx-auto mb-2 text-gray-300"></i>
            No stock adjustments yet
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left px-4 py-2.5 font-medium text-gray-600">Product</th>
                        <th class="text-center px-4 py-2.5 font-medium text-gray-600">Qty Change</th>
                        <th class="text-left px-4 py-2.5 font-medium text-gray-600">Reason</th>
                        <th class="text-left px-4 py-2.5 font-medium text-gray-600 hidden sm:table-cell">Adjusted By</th>
                        <th class="text-left px-4 py-2.5 font-medium text-gray-600 hidden md:table-cell">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($adjustments as $a):
                        $qtyClass = $a['quantity'] >= 0 ? 'text-amber-600 bg-amber-50' : 'text-red-600 bg-red-50';
                        $qtyLabel = $a['quantity'] >= 0 ? '+' . $a['quantity'] : (string)$a['quantity'];
                        $reasonLabels = ['restock' => 'Restock', 'return' => 'Return', 'damage' => 'Damage', 'adjustment' => 'Adjustment'];
                        $reasonClass = match($a['reason']) {
                            'restock' => 'bg-amber-100 text-amber-700',
                            'return' => 'bg-blue-100 text-blue-700',
                            'damage' => 'bg-red-100 text-red-700',
                            default => 'bg-gray-100 text-gray-600',
                        };
                    ?>
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-4 py-2.5 font-medium text-gray-900"><?= e($a['product_name']) ?></td>
                        <td class="px-4 py-2.5 text-center">
                            <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-bold <?= $qtyClass ?>"><?= $qtyLabel ?></span>
                        </td>
                        <td class="px-4 py-2.5">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $reasonClass ?>"><?= $reasonLabels[$a['reason']] ?? ucfirst($a['reason']) ?></span>
                        </td>
                        <td class="px-4 py-2.5 text-gray-600 hidden sm:table-cell"><?= e($a['adjusted_by_name'] ?? 'System') ?></td>
                        <td class="px-4 py-2.5 text-gray-500 text-xs hidden md:table-cell"><?= date('M j, Y g:i A', strtotime($a['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Stock Adjust Modal -->
<div id="adjustModal" class="fixed inset-0 z-50 hidden">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" onclick="closeAdjustModal()"></div>

    <!-- Modal -->
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md relative z-10">
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="font-heading font-semibold text-lg text-gray-900">Adjust Stock</h3>
                <button type="button" onclick="closeAdjustModal()" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="px-6 py-5 space-y-4">
                <!-- Product Info -->
                <div class="bg-gray-50 rounded-xl p-4 space-y-2">
                    <p class="text-sm font-medium text-gray-900" id="modalProductName">Product Name</p>
                    <div class="flex items-center gap-4 text-sm">
                        <span class="text-gray-500">Current Stock:</span>
                        <span class="font-bold text-gray-900" id="modalCurrentStock">0</span>
                    </div>
                </div>

                <!-- Form -->
                <form method="POST" action="/admin/inventory/adjust" id="adjustForm">
                    <?= csrf() ?>
                    <input type="hidden" name="product_id" id="modalProductId">

                    <!-- New Stock Quantity -->
                    <div>
                        <label for="modalNewStock" class="block text-sm font-medium text-gray-700 mb-1.5">New Stock Quantity</label>
                        <input type="number" name="quantity" id="modalNewStock" min="0" required
                            class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                            oninput="updateDeltaPreview()">
                        <p class="text-xs text-gray-400 mt-1" id="deltaPreview"></p>
                    </div>

                    <!-- Reason -->
                    <div>
                        <label for="modalReason" class="block text-sm font-medium text-gray-700 mb-1.5">Reason</label>
                        <select name="reason" id="modalReason" required class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                            <option value="restock">Restock</option>
                            <option value="return">Return</option>
                            <option value="damage">Damage</option>
                            <option value="adjustment">Adjustment</option>
                        </select>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-100 bg-gray-50/50 rounded-b-2xl">
                <button type="button" onclick="closeAdjustModal()" class="px-4 py-2.5 text-sm font-medium text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-100 transition-colors">Cancel</button>
                <button type="submit" form="adjustForm" id="adjustSubmitBtn" class="px-5 py-2.5 text-sm font-medium text-white bg-amber-600 rounded-lg hover:bg-amber-700 transition-colors flex items-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i> Update Stock
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function openAdjustModal(productId, productName, currentStock) {
    document.getElementById('modalProductId').value = productId;
    document.getElementById('modalProductName').textContent = productName;
    document.getElementById('modalCurrentStock').textContent = currentStock;
    document.getElementById('modalNewStock').value = currentStock;
    document.getElementById('deltaPreview').textContent = '';
    document.getElementById('adjustModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    // Re-init lucide for new icons in modal
    if (typeof lucide !== 'undefined') lucide.createIcons();
    // Focus the input
    setTimeout(() => document.getElementById('modalNewStock').select(), 100);
}

function closeAdjustModal() {
    document.getElementById('adjustModal').classList.add('hidden');
    document.body.style.overflow = '';
}

function updateDeltaPreview() {
    const current = parseInt(document.getElementById('modalCurrentStock').textContent) || 0;
    const newStock = parseInt(document.getElementById('modalNewStock').value) || 0;
    const delta = newStock - current;
    const preview = document.getElementById('deltaPreview');
    if (delta === 0) {
        preview.textContent = 'No change';
        preview.className = 'text-xs text-gray-400 mt-1';
    } else if (delta > 0) {
        preview.textContent = 'Will add +' + delta + ' units';
        preview.className = 'text-xs text-amber-600 mt-1';
    } else {
        preview.textContent = 'Will remove ' + delta + ' units';
        preview.className = 'text-xs text-red-600 mt-1';
    }
}

// Override form submit to compute delta before sending
document.getElementById('adjustForm').addEventListener('submit', function(e) {
    const current = parseInt(document.getElementById('modalCurrentStock').textContent) || 0;
    const newStock = parseInt(document.getElementById('modalNewStock').value) || 0;
    const delta = newStock - current;
    if (delta === 0) {
        e.preventDefault();
        closeAdjustModal();
        return;
    }
    // Set quantity to delta (the backend adds it to current stock)
    this.querySelector('[name="quantity"]').value = delta;
});

// Close modal on Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeAdjustModal();
});
</script>