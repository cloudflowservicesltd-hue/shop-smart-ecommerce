<!-- Breadcrumbs -->
<div class="bg-gray-50 border-b border-gray-100">
    <div class="w-full px-4 sm:px-6 xl:px-4 py-3">
        <nav class="flex items-center gap-2 text-sm text-gray-500">
            <a href="/" class="hover:text-amber-600 transition-colors">Home</a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <?php if (!empty($currentCategory)): ?>
                <a href="/category/<?= e($currentCategory['slug']) ?>" class="hover:text-amber-600 transition-colors"><?= e($currentCategory['name']) ?></a>
            <?php else: ?>
                <span class="text-gray-900 font-medium">All Products</span>
            <?php endif; ?>
        </nav>
    </div>
</div>

<div class="w-full px-4 sm:px-6 xl:px-4 py-8">
    <div class="flex flex-col lg:flex-row gap-8">

        <!-- Sidebar Filters (Desktop) -->
        <aside class="hidden lg:block w-64 shrink-0">
            <div class="sticky top-36 space-y-6">
                <!-- Active Filters -->
                <?php if (!empty($activeFilters ?? [])): ?>
                <div class="bg-amber-50 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-semibold text-sm text-amber-800">Active Filters</h3>
                        <a href="<?= !empty($currentCategory) ? '/category/' . e($currentCategory['slug']) : '/products' ?>" class="text-xs text-amber-600 hover:text-amber-700 font-medium">Clear All</a>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($activeFilters as $key => $filter): ?>
                        <span class="inline-flex items-center gap-1 bg-white border border-amber-200 text-amber-700 text-xs px-2.5 py-1 rounded-lg">
                            <?= e($filter) ?>
                            <a href="<?= $removeFilterUrls[$key] ?? '#' ?>" class="text-amber-400 hover:text-red-500 transition-colors">
                                <i data-lucide="x" class="w-3 h-3"></i>
                            </a>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Categories -->
                <div class="border border-gray-200 rounded-xl p-5">
                    <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <i data-lucide="folder" class="w-4 h-4 text-amber-600"></i> Categories
                    </h3>
                    <div class="space-y-1 max-h-64 overflow-y-auto scrollbar-thin">
                        <?php foreach ($categories ?? [] as $cat): ?>
                        <a href="/category/<?= e($cat['slug']) ?>" 
                           class="flex items-center justify-between px-3 py-2 rounded-lg text-sm transition-colors <?= ($selectedCategory ?? 0) == $cat['id'] ? 'bg-amber-50 text-amber-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                            <span><?= e($cat['name']) ?></span>
                            <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full"><?= $cat['product_count'] ?? 0 ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Price Range -->
                <div class="border border-gray-200 rounded-xl p-5">
                    <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <i data-lucide="tag" class="w-4 h-4 text-amber-600"></i> Price Range
                    </h3>
                    <div class="space-y-2">
                        <?php 
                        $priceRanges = [
                            ['min' => 0, 'max' => 5000, 'label' => 'Under KSh 5,000'],
                            ['min' => 5000, 'max' => 20000, 'label' => 'KSh 5,000 - 20,000'],
                            ['min' => 20000, 'max' => 50000, 'label' => 'KSh 20,000 - 50,000'],
                            ['min' => 50000, 'max' => 100000, 'label' => 'KSh 50,000 - 100,000'],
                            ['min' => 100000, 'max' => PHP_INT_MAX, 'label' => 'Over KSh 100,000'],
                        ];
                        foreach ($priceRanges as $range): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['min_price' => $range['min'], 'max_price' => $range['max']])) ?>" 
                           class="block px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                            <?= $range['label'] ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-3 pt-3 border-t border-gray-100">
                        <p class="text-xs font-medium text-gray-500 mb-2">Custom Range</p>
                        <div class="flex items-center gap-2">
                            <input type="number" id="customMinPrice" placeholder="Min" min="0" class="w-full px-2.5 py-1.5 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-amber-400" value="<?= e(Request::query('min_price', '')) ?>">
                            <span class="text-gray-400 text-xs">—</span>
                            <input type="number" id="customMaxPrice" placeholder="Max" min="0" class="w-full px-2.5 py-1.5 text-xs border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-amber-400" value="<?= e(Request::query('max_price', '')) ?>">
                        </div>
                        <button onclick="applyCustomPrice()" class="mt-2 w-full text-xs font-medium text-amber-600 bg-amber-50 hover:bg-amber-100 py-1.5 rounded-lg transition-colors">Apply</button>
                    </div>
                </div>

                <!-- Brands -->
                <?php if (!empty($brands ?? [])): ?>
                <div class="border border-gray-200 rounded-xl p-5">
                    <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <i data-lucide="award" class="w-4 h-4 text-amber-600"></i> Brands
                    </h3>
                    <div class="space-y-1 max-h-48 overflow-y-auto scrollbar-thin">
                        <?php foreach ($brands as $brand): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['brand' => $brand['slug']])) ?>" 
                           class="flex items-center justify-between px-3 py-2 rounded-lg text-sm transition-colors <?= (Request::query('brand', '') == $brand['slug']) ? 'bg-amber-50 text-amber-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                            <span><?= e($brand['name']) ?></span>
                            <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full"><?= $brand['product_count'] ?? 0 ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 min-w-0">
            <!-- Top Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <h1 class="font-heading text-2xl font-bold text-gray-900">
                        <?= e($pageTitle ?? 'All Products') ?>
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">
                        <?= number_format($totalProducts ?? 0) ?> products found
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Mobile Filter Toggle -->
                    <button onclick="document.getElementById('mobileFilters').classList.toggle('hidden')" class="lg:hidden inline-flex items-center gap-2 px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        <i data-lucide="sliders-horizontal" class="w-4 h-4"></i> Filters
                    </button>
                    <!-- Sort -->
                    <select onchange="window.location.href=this.value" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent cursor-pointer">
                        <?php 
                        $currentSort = Request::query('sort', 'newest');
                        $sortOptions = [
                            'newest' => 'Newest First',
                            'price_asc' => 'Price: Low to High',
                            'price_desc' => 'Price: High to Low',
                            'popular' => 'Most Popular',
                            'rating' => 'Highest Rated',
                        ];
                        foreach ($sortOptions as $val => $label): ?>
                        <option value="?<?= http_build_query(array_merge($_GET, ['sort' => $val, 'page' => 1])) ?>" <?= $currentSort === $val ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Mobile Filters -->
            <div id="mobileFilters" class="hidden lg:hidden mb-6 bg-gray-50 border border-gray-200 rounded-xl p-4 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">Filters</h3>
                    <button onclick="document.getElementById('mobileFilters').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Categories</p>
                    <div class="flex flex-wrap gap-2">
                        <a href="/products" class="px-3 py-1.5 rounded-lg text-sm <?= empty($selectedCategory) ? 'bg-amber-600 text-white' : 'bg-white border border-gray-200 text-gray-600' ?>">All</a>
                        <?php foreach ($categories ?? [] as $cat): ?>
                        <a href="/category/<?= e($cat['slug']) ?>" class="px-3 py-1.5 rounded-lg text-sm <?= ($selectedCategory ?? 0) == $cat['id'] ? 'bg-amber-600 text-white' : 'bg-white border border-gray-200 text-gray-600' ?>"><?= e($cat['name']) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Active Filter Tags (Mobile) -->
            <?php if (!empty($activeFilters ?? [])): ?>
            <div class="lg:hidden flex flex-wrap gap-2 mb-4">
                <?php foreach ($activeFilters as $key => $filter): ?>
                <span class="inline-flex items-center gap-1 bg-amber-50 border border-amber-200 text-amber-700 text-xs px-2.5 py-1 rounded-lg">
                    <?= e($filter) ?>
                    <a href="<?= $removeFilterUrls[$key] ?? '#' ?>"><i data-lucide="x" class="w-3 h-3"></i></a>
                </span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Product Grid -->
            <?php if (!empty($products ?? [])): ?>
            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-3 md:gap-5">
                <?php foreach ($products as $product): ?>
                <div class="group bg-white rounded-2xl border border-gray-100 overflow-hidden hover:shadow-lg hover:shadow-gray-200/50 transition-all duration-300">
                    <a href="/product/<?= e($product['slug']) ?>" class="block relative aspect-square bg-gray-50 overflow-hidden">
                        <?php if ($product['discount_price'] && $product['discount_price'] < $product['price']): ?>
                        <span class="absolute top-3 left-3 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-lg z-10">
                            -<?= number_format((1 - $product['discount_price'] / $product['price']) * 100, 0) ?>%
                        </span>
                        <?php endif; ?>
                        <?php $listingStatus = $product['product_status'] ?? 'active'; ?>
                        <?php if ($listingStatus === 'out_of_stock_returning'): ?>
                        <div class="absolute inset-0 bg-blue-500/20 flex items-center justify-center z-10">
                            <span class="bg-blue-600 text-white font-bold text-xs px-3 py-1.5 rounded-lg flex items-center gap-1.5 shadow-lg">
                                <i data-lucide="clock" class="w-3.5 h-3.5"></i> Returning Soon
                            </span>
                        </div>
                        <?php elseif (!empty($product['stock_quantity']) && $product['stock_quantity'] <= 0): ?>
                        <div class="absolute inset-0 bg-black/40 flex items-center justify-center z-10">
                            <span class="bg-white text-gray-900 font-bold text-sm px-4 py-2 rounded-lg">Out of Stock</span>
                        </div>
                        <?php endif; ?>
                        <?php if (Auth::check()): ?>
                        <button onclick="event.preventDefault(); event.stopPropagation(); toggleWishlistBtn(this, <?= $product['id'] ?>)"
                            class="absolute top-3 right-3 p-2 bg-white/80 backdrop-blur-sm rounded-lg hover:bg-white transition-all duration-200 z-10 text-gray-500 hover:text-red-500" data-wishlist-product="<?= $product['id'] ?>">
                            <i data-lucide="heart" class="w-4 h-4"></i>
                        </button>
                        <?php endif; ?>
                        <img src="<?= $product['image'] ?? "/uploads/no-image.jpg" ?>" 
                             alt="<?= e($product['name']) ?>" 
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
                    </a>
                    <div class="p-3 md:p-4">
                        <p class="text-xs text-gray-400 mb-1"><?= e($product['brand_name'] ?? '') ?></p>
                        <a href="/product/<?= e($product['slug']) ?>" class="block">
                            <h3 class="font-semibold text-gray-900 text-sm leading-snug group-hover:text-amber-600 transition-colors line-clamp-2"><?= e($product['name']) ?></h3>
                        </a>
                        <div class="flex items-center gap-1 mt-1.5">
                            <?php $avgRating = $product['avg_rating'] ?? 0; ?>
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                <i data-lucide="star" class="w-3 h-3 <?= $s <= round($avgRating) ? 'text-amber-400 fill-amber-400' : 'text-gray-200' ?>"></i>
                            <?php endfor; ?>
                            <span class="text-xs text-gray-400 ml-1">(<?= $product['review_count'] ?? 0 ?>)</span>
                        </div>
                        <div class="flex items-center justify-between mt-3">
                            <div>
                                <span class="font-bold text-gray-900 text-sm md:text-base"><?= formatMoney($product['discount_price'] ?? $product['price']) ?></span>
                                <?php if ($product['discount_price'] && $product['discount_price'] < $product['price']): ?>
                                <span class="text-xs text-gray-400 line-through ml-1"><?= formatMoney($product['price']) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ($listingStatus !== 'out_of_stock_returning' && (empty($product['stock_quantity']) || $product['stock_quantity'] > 0)): ?>
                            <form action="/cart/add" method="POST" class="inline-flex">
                                <?= csrf() ?>
                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="p-2 bg-amber-50 text-amber-600 rounded-lg hover:bg-amber-600 hover:text-white transition-colors" title="Add to Cart">
                                    <i data-lucide="shopping-cart" class="w-4 h-4"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if (!empty($pagination ?? null) && ($pagination['last_page'] ?? 1) > 1): ?>
            <div class="mt-10 flex items-center justify-center">
                <nav class="flex items-center gap-1">
                    <?php if (($pagination['current_page'] ?? 1) > 1): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] - 1])) ?>" class="px-3.5 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-100 transition-colors">
                        <i data-lucide="chevron-left" class="w-4 h-4"></i>
                    </a>
                    <?php endif; ?>
                    <?php for ($p = max(1, $pagination['current_page'] - 2); $p <= min($pagination['last_page'], $pagination['current_page'] + 2); $p++): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>" 
                       class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors <?= $p == $pagination['current_page'] ? 'bg-amber-600 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
                        <?= $p ?>
                    </a>
                    <?php endfor; ?>
                    <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] + 1])) ?>" class="px-3.5 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-100 transition-colors">
                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </a>
                    <?php endif; ?>
                </nav>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <!-- Empty State -->
            <div class="text-center py-20">
                <div class="w-20 h-20 mx-auto mb-4 bg-gray-100 rounded-2xl flex items-center justify-center">
                    <i data-lucide="package-x" class="w-10 h-10 text-gray-300"></i>
                </div>
                <h3 class="font-heading text-lg font-semibold text-gray-900 mb-2">No products found</h3>
                <p class="text-gray-500 mb-6">Try adjusting your filters or search terms</p>
                <a href="/products" class="inline-flex items-center gap-2 bg-amber-600 text-white font-medium px-6 py-2.5 rounded-xl hover:bg-amber-700 transition-colors">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i> Clear Filters
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function applyCustomPrice() {
    const min = document.getElementById('customMinPrice').value;
    const max = document.getElementById('customMaxPrice').value;
    const params = new URLSearchParams(window.location.search);
    if (min) params.set('min_price', min); else params.delete('min_price');
    if (max) params.set('max_price', max); else params.delete('max_price');
    params.delete('page');
    window.location.href = '/products?' + params.toString();
}

// Toast notification
function showToast(msg, type) {
    const existing = document.querySelector('.wishlist-toast');
    if (existing) existing.remove();
    const t = document.createElement('div');
    t.className = 'wishlist-toast fixed bottom-6 right-6 z-[9999] flex items-center gap-2 px-5 py-3 rounded-xl shadow-xl text-sm font-medium transition-all duration-300 translate-y-2 opacity-0';
    t.style.cssText = type === 'error' ? 'background:#fef2f2;color:#991b1b;border:1px solid #fecaca;' : 'background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;';
    t.innerHTML = (type === 'error' ? '<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' : '<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>') + '<span>' + msg + '</span>';
    document.body.appendChild(t);
    requestAnimationFrame(() => { t.style.transform = 'translateY(0)'; t.style.opacity = '1'; });
    setTimeout(() => { t.style.transform = 'translateY(2px)'; t.style.opacity = '0'; setTimeout(() => t.remove(), 300); }, 2500);
}

function toggleWishlistBtn(btn, productId) {
    if (btn.disabled) return;
    btn.disabled = true;

    const fd = new FormData();
    fd.append('_token', '<?= csrf_token() ?>');

    fetch('/wishlist/toggle/' + productId, {
        method: 'POST',
        headers: {'X-Requested-With': 'XMLHttpRequest'},
        body: fd
    })
    .then(r => {
        if (r.status === 401) { showToast('Please login to use wishlist', 'error'); throw new Error('401'); }
        if (!r.ok) throw new Error('Server error: ' + r.status);
        return r.json();
    })
    .then(d => {
        const added = d.action === 'added';
        const svg = btn.querySelector('svg');
        if (added) {
            btn.classList.remove('text-gray-500');
            btn.classList.add('text-red-500', 'bg-red-50');
            if (svg) { svg.setAttribute('fill', 'currentColor'); svg.style.color = '#ef4444'; }
            showToast('Added to wishlist', 'success');
        } else {
            btn.classList.remove('text-red-500', 'bg-red-50');
            btn.classList.add('text-gray-500');
            if (svg) { svg.setAttribute('fill', 'none'); svg.style.color = ''; }
            showToast('Removed from wishlist', 'success');
        }
    })
    .catch(err => {
        if (err.message !== '401') {
            console.error('Wishlist error:', err);
            showToast('Something went wrong', 'error');
        }
    })
    .finally(() => { btn.disabled = false; });
}
</script>