<?php
$page = (int)Request::query('page', 1);
$search = Request::query('search', '');
$catFilter = Request::query('category', '');
$brandFilter = Request::query('brand', '');
$statusFilter = Request::query('status', '');
$productStatusFilter = Request::query('product_status', '');

$where = '1=1';
$params = [];
if ($search) { $where .= ' AND (p.name LIKE ? OR p.sku LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($catFilter) { $where .= ' AND p.category_id = ?'; $params[] = $catFilter; }
if ($brandFilter) { $where .= ' AND p.brand_id = ?'; $params[] = $brandFilter; }
if ($statusFilter !== '') { $where .= ' AND p.is_active = ?'; $params[] = $statusFilter; }
if ($productStatusFilter) { $where .= ' AND p.product_status = ?'; $params[] = $productStatusFilter; }

$products = Database::paginate("products p", $page, 15, $where, $params, 'p.created_at DESC');
$categories = Database::select("SELECT * FROM categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY name");
$brands = Database::select("SELECT * FROM brands WHERE is_active = 1 ORDER BY name");

// Status counts
$statusCounts = [
    'active' => Database::count('products', "COALESCE(product_status, 'active') = 'active'"),
    'out_of_stock_returning' => Database::count('products', "product_status = 'out_of_stock_returning'"),
    'discontinued' => Database::count('products', "product_status = 'discontinued'"),
    'returned' => Database::count('products', "product_status = 'returned'"),
    'draft' => Database::count('products', "product_status = 'draft'"),
];
?>

<div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <h1 class="font-heading font-semibold text-xl text-gray-900">Products</h1>
        <a href="/admin/products/create" class="inline-flex items-center gap-2 bg-amber-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-amber-700 transition-colors">
            <i data-lucide="plus" class="w-4 h-4"></i> Add Product
        </a>
    </div>

    <!-- Status Summary Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <button onclick="setProductStatusFilter('')" class="bg-white rounded-xl border-2 <?= empty($productStatusFilter) ? 'border-amber-500 bg-amber-50' : 'border-gray-100' ?> p-3 text-left hover:border-amber-300 transition-colors">
            <p class="text-lg font-bold text-gray-900"><?= $statusCounts['active'] ?></p>
            <p class="text-xs text-gray-500">Active</p>
        </button>
        <button onclick="setProductStatusFilter('out_of_stock_returning')" class="bg-white rounded-xl border-2 <?= $productStatusFilter === 'out_of_stock_returning' ? 'border-blue-500 bg-blue-50' : 'border-gray-100' ?> p-3 text-left hover:border-blue-300 transition-colors">
            <p class="text-lg font-bold text-gray-900"><?= $statusCounts['out_of_stock_returning'] ?></p>
            <p class="text-xs text-gray-500">Returning Soon</p>
        </button>
        <button onclick="setProductStatusFilter('discontinued')" class="bg-white rounded-xl border-2 <?= $productStatusFilter === 'discontinued' ? 'border-gray-500 bg-gray-100' : 'border-gray-100' ?> p-3 text-left hover:border-gray-300 transition-colors">
            <p class="text-lg font-bold text-gray-900"><?= $statusCounts['discontinued'] ?></p>
            <p class="text-xs text-gray-500">Discontinued</p>
        </button>
        <button onclick="setProductStatusFilter('returned')" class="bg-white rounded-xl border-2 <?= $productStatusFilter === 'returned' ? 'border-red-500 bg-red-50' : 'border-gray-100' ?> p-3 text-left hover:border-red-300 transition-colors">
            <p class="text-lg font-bold text-gray-900"><?= $statusCounts['returned'] ?></p>
            <p class="text-xs text-gray-500">Returned</p>
        </button>
        <button onclick="setProductStatusFilter('draft')" class="bg-white rounded-xl border-2 <?= $productStatusFilter === 'draft' ? 'border-amber-500 bg-amber-50' : 'border-gray-100' ?> p-3 text-left hover:border-amber-300 transition-colors">
            <p class="text-lg font-bold text-gray-900"><?= $statusCounts['draft'] ?></p>
            <p class="text-xs text-gray-500">Draft</p>
        </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
        <form method="GET" id="filterForm" class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search products..." class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
            </div>
            <select name="category" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                <option value="">All Categories</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $catFilter == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="brand" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                <option value="">All Brands</option>
                <?php foreach ($brands as $b): ?>
                    <option value="<?= $b['id'] ?>" <?= $brandFilter == $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                <option value="">All Active</option>
                <option value="1" <?= $statusFilter === '1' ? 'selected' : '' ?>>Active</option>
                <option value="0" <?= $statusFilter === '0' ? 'selected' : '' ?>>Inactive</option>
            </select>
            <input type="hidden" name="product_status" id="productStatusInput" value="<?= e($productStatusFilter) ?>">
            <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded-lg text-sm font-medium hover:bg-amber-700">
                <i data-lucide="filter" class="w-4 h-4 inline -mt-0.5"></i> Filter
            </button>
            <a href="/admin/products" class="px-4 py-2 border border-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors text-center">Clear</a>
        </form>
    </div>

    <!-- Products Table -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Product</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Category</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Price</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Stock</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Status</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Product Status</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($products['data'] as $p):
                        $cat = Database::selectOne("SELECT name FROM categories WHERE id = ?", [$p['category_id']]);
                        $img = Database::selectOne("SELECT image_path FROM product_images WHERE product_id = ? AND is_primary = 1", [$p['id']]);
                        $pStatus = $p['product_status'] ?? 'active';
                    ?>
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <img src="<?= e($img['image_path'] ?? '/uploads/no-image-sm.jpg') ?>" alt="" class="w-10 h-10 rounded-lg object-cover bg-gray-100">
                                <div>
                                    <p class="font-medium text-gray-900 truncate max-w-[200px]"><?= e($p['name']) ?></p>
                                    <p class="text-xs text-gray-500"><?= e($p['sku'] ?? 'No SKU') ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-600"><?= e($cat['name'] ?? '-') ?></td>
                        <td class="px-4 py-3 font-medium"><?= formatMoney($p['price']) ?></td>
                        <td class="px-4 py-3">
                            <span class="<?= $p['stock_quantity'] <= $p['low_stock_threshold'] ? 'text-red-600 font-medium' : 'text-gray-600' ?>"><?= $p['stock_quantity'] ?></span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $p['is_active'] ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600' ?>">
                                <?= $p['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <?php
                            $psStyles = [
                                'active' => 'bg-emerald-50 text-emerald-700',
                                'out_of_stock_returning' => 'bg-blue-50 text-blue-700',
                                'discontinued' => 'bg-gray-100 text-gray-600',
                                'returned' => 'bg-red-50 text-red-700',
                                'draft' => 'bg-amber-50 text-amber-700',
                            ];
                            $psIcons = [
                                'active' => 'check-circle',
                                'out_of_stock_returning' => 'clock',
                                'discontinued' => 'package-x',
                                'returned' => 'rotate-ccw',
                                'draft' => 'edit-3',
                            ];
                            $psLabels = [
                                'active' => 'Active',
                                'out_of_stock_returning' => 'Returning Soon',
                                'discontinued' => 'Discontinued',
                                'returned' => 'Returned',
                                'draft' => 'Draft',
                            ];
                            $psStyle = $psStyles[$pStatus] ?? 'bg-gray-100 text-gray-600';
                            $psIcon = $psIcons[$pStatus] ?? 'help-circle';
                            $psLabel = $psLabels[$pStatus] ?? ucfirst($pStatus);
                            ?>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium <?= $psStyle ?>">
                                <i data-lucide="<?= $psIcon ?>" class="w-3 h-3"></i>
                                <?= $psLabel ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <?php $reviewCount = Database::count('reviews', 'product_id = ?', [$p['id']]); ?>
                                <a href="/admin/products/<?= $p['id'] ?>/reviews" class="p-1.5 text-gray-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" title="Reviews (<?= $reviewCount ?>)">
                                    <i data-lucide="message-square" class="w-4 h-4"></i>
                                </a>
                                <a href="/admin/products/<?= $p['id'] ?>/edit" class="p-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"><i data-lucide="pencil" class="w-4 h-4"></i></a>
                                <form method="POST" action="/admin/products/<?= $p['id'] ?>/duplicate" onsubmit="return confirm('Duplicate this product?')">
                                    <?= csrf() ?>
                                    <button type="submit" class="p-1.5 text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors" title="Duplicate"><i data-lucide="copy" class="w-4 h-4"></i></button>
                                </form>
                                <form method="POST" action="/admin/products/<?= $p['id'] ?>/delete" onsubmit="return confirm('Delete this product?')">
                                    <?= csrf() ?>
                                    <button class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($products['data'])): ?>
                        <tr><td colspan="7" class="px-4 py-12 text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <i data-lucide="package" class="w-10 h-10 text-gray-300 mb-2"></i>
                                <p>No products found</p>
                            </div>
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?= View::pagination($products, '/admin/products?' . http_build_query(array_filter(['search'=>$search,'category'=>$catFilter,'brand'=>$brandFilter,'status'=>$statusFilter,'product_status'=>$productStatusFilter]))) ?>
    </div>
</div>

<script>
function setProductStatusFilter(status) {
    document.getElementById('productStatusInput').value = status;
    document.getElementById('filterForm').submit();
}
lucide.createIcons();
</script>