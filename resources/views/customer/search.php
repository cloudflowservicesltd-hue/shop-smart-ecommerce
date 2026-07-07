<!-- Breadcrumbs -->
<div class="bg-gray-50 border-b border-gray-100">
    <div class="w-full px-4 sm:px-6 xl:px-0 py-3">
        <nav class="flex items-center gap-2 text-sm text-gray-500">
            <a href="/" class="hover:text-amber-600 transition-colors">Home</a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <span class="text-gray-900 font-medium">Search Results</span>
        </nav>
    </div>
</div>

<div class="w-full px-4 sm:px-6 xl:px-0 py-8">
    <!-- Search Header -->
    <div class="mb-8">
        <h1 class="font-heading text-2xl font-bold text-gray-900">
            <?php if (!empty($query ?? '')): ?>
            Results for "<span class="text-amber-600"><?= e($query) ?></span>"
            <?php else: ?>
            Search Products
            <?php endif; ?>
        </h1>
        <p class="text-sm text-gray-500 mt-1"><?= number_format($totalProducts ?? 0) ?> products found</p>
    </div>

    <!-- Search Form -->
    <form action="/search" method="GET" class="mb-8 max-w-2xl">
        <div class="relative">
            <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
            <input type="text" name="q" value="<?= e($query ?? '') ?>" placeholder="Search for products, brands, categories..." autofocus
                   class="w-full pl-12 pr-32 py-3.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent">
            <button type="submit" class="absolute right-1.5 top-1/2 -translate-y-1/2 bg-amber-600 text-white font-medium px-6 py-2 rounded-lg hover:bg-amber-700 transition-colors text-sm">
                Search
            </button>
        </div>
    </form>

    <?php if (!empty($searchCategories ?? [])): ?>
    <!-- Categories Section -->
    <div class="mb-8">
        <h2 class="font-heading text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
            <i data-lucide="grid-3x3" class="w-5 h-5 text-amber-600"></i> Categories
        </h2>
        <div class="flex gap-4 overflow-x-auto pb-3 snap-x snap-mandatory scrollbar-thin">
            <?php foreach ($searchCategories as $cat): ?>
            <a href="/category/<?= e($cat['slug']) ?>" class="snap-start shrink-0 w-44 bg-white rounded-xl border border-gray-100 overflow-hidden hover:shadow-lg hover:shadow-gray-200/50 transition-all duration-300 group">
                <div class="aspect-square bg-gray-50 overflow-hidden">
                    <?php if (!empty($cat['image'])): ?>
                    <img src="<?= e($cat['image']) ?>" alt="<?= e($cat['name']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy">
                    <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center">
                        <i data-lucide="folder" class="w-12 h-12 text-gray-200"></i>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="p-3">
                    <h3 class="font-semibold text-sm text-gray-900 truncate group-hover:text-amber-600 transition-colors"><?= e($cat['name']) ?></h3>
                    <p class="text-xs text-gray-400 mt-0.5"><?= number_format((int)($cat['product_count'] ?? 0)) ?> products</p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($searchBrands ?? [])): ?>
    <!-- Brands Section -->
    <div class="mb-8">
        <h2 class="font-heading text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
            <i data-lucide="award" class="w-5 h-5 text-amber-600"></i> Brands
        </h2>
        <div class="flex gap-4 overflow-x-auto pb-3 snap-x snap-mandatory scrollbar-thin">
            <?php foreach ($searchBrands as $b): ?>
            <a href="/products?brand=<?= e($b['slug']) ?>" class="snap-start shrink-0 w-36 bg-white rounded-xl border border-gray-100 overflow-hidden hover:shadow-lg hover:shadow-gray-200/50 transition-all duration-300 group flex flex-col items-center justify-center p-5">
                <?php if (!empty($b['logo'])): ?>
                <img src="<?= e($b['logo']) ?>" alt="<?= e($b['name']) ?>" class="w-16 h-16 object-contain group-hover:scale-110 transition-transform duration-300 mb-3" loading="lazy">
                <?php else: ?>
                <div class="w-16 h-16 bg-gray-50 rounded-xl flex items-center justify-center mb-3">
                    <i data-lucide="tag" class="w-8 h-8 text-gray-200"></i>
                </div>
                <?php endif; ?>
                <span class="text-sm font-medium text-gray-900 group-hover:text-amber-600 transition-colors text-center truncate w-full"><?= e($b['name']) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($products ?? [])): ?>
    <!-- Sort -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-2 text-sm text-gray-500">
            <span>Sort by:</span>
            <select onchange="window.location.href=this.value" class="px-3 py-1.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-amber-500">
                <?php 
                $currentSort = Request::query('sort', 'relevance');
                $sortOptions = [
                    'relevance' => 'Relevance',
                    'newest' => 'Newest',
                    'price_asc' => 'Price: Low to High',
                    'price_desc' => 'Price: High to Low',
                ];
                foreach ($sortOptions as $val => $label): ?>
                <option value="?<?= http_build_query(array_merge($_GET, ['sort' => $val, 'page' => 1])) ?>" <?= $currentSort === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Results Grid -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
        <?php foreach ($products as $product): ?>
        <div class="group bg-white rounded-2xl border border-gray-100 overflow-hidden hover:shadow-lg hover:shadow-gray-200/50 transition-all duration-300">
            <a href="/product/<?= e($product['slug']) ?>" class="block relative aspect-square bg-gray-50 overflow-hidden">
                <?php if ($product['discount_price'] && $product['discount_price'] < $product['price']): ?>
                <span class="absolute top-3 left-3 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-lg z-10">
                    -<?= number_format((1 - $product['discount_price'] / $product['price']) * 100, 0) ?>%
                </span>
                <?php endif; ?>
                <?php if ($product['stock_quantity'] <= 0): ?>
                <div class="absolute inset-0 bg-black/40 flex items-center justify-center z-10">
                    <span class="bg-white text-gray-900 font-bold text-xs px-3 py-1.5 rounded-lg">Out of Stock</span>
                </div>
                <?php endif; ?>
                <img src="<?= $product['image'] ?? "/uploads/no-image.jpg" ?>" 
                     alt="<?= e($product['name']) ?>" 
                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
            </a>
            <div class="p-3 md:p-4">
                <?php if (!empty($product['brand_name'])): ?>
                <p class="text-xs text-gray-400 mb-1"><?= e($product['brand_name']) ?></p>
                <?php endif; ?>
                <a href="/product/<?= e($product['slug']) ?>" class="block">
                    <h3 class="font-semibold text-gray-900 text-sm leading-snug group-hover:text-amber-600 transition-colors line-clamp-2">
                        <?php 
                        // Highlight search query in product name
                        $name = e($product['name']);
                        if (!empty($query)) {
                            $name = preg_replace('/(' . preg_quote($query, '/') . ')/i', '<mark class="bg-yellow-200 text-gray-900 rounded px-0.5">$1</mark>', $name);
                        }
                        echo $name;
                        ?>
                    </h3>
                </a>
                <div class="flex items-center gap-1 mt-1.5">
                    <?php $avgRating = $product['avg_rating'] ?? 0; ?>
                    <?php for ($s = 1; $s <= 5; $s++): ?>
                        <i data-lucide="star" class="w-3 h-3 <?= $s <= round($avgRating) ? 'text-amber-400 fill-amber-400' : 'text-gray-200' ?>"></i>
                    <?php endfor; ?>
                </div>
                <div class="flex items-center justify-between mt-3">
                    <div>
                        <span class="font-bold text-gray-900 text-sm md:text-base"><?= formatMoney($product['discount_price'] ?? $product['price']) ?></span>
                        <?php if ($product['discount_price'] && $product['discount_price'] < $product['price']): ?>
                        <span class="text-xs text-gray-400 line-through ml-1"><?= formatMoney($product['price']) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($product['stock_quantity'] > 0): ?>
                    <form action="/cart/add" method="POST" class="inline-flex">
                        <?= csrf() ?>
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <input type="hidden" name="quantity" value="1">
                        <button type="submit" class="p-2 bg-amber-50 text-amber-600 rounded-lg hover:bg-amber-600 hover:text-white transition-colors">
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
    <?php if (!empty($pagination) && ($pagination['last_page'] ?? 1) > 1): ?>
    <div class="mt-10 flex items-center justify-center">
        <nav class="flex items-center gap-1">
            <?php if (($pagination['current_page'] ?? 1) > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] - 1])) ?>" class="px-3.5 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-100 transition-colors">
                <i data-lucide="chevron-left" class="w-4 h-4"></i>
            </a>
            <?php endif; ?>
            <?php for ($p = max(1, $pagination['current_page'] - 2); $p <= min($pagination['last_page'], $pagination['current_page'] + 2); $p++): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>" 
               class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors <?= $p == $pagination['current_page'] ? 'bg-amber-600 text-white' : 'text-gray-600 hover:bg-gray-100' ?>"><?= $p ?></a>
            <?php endfor; ?>
            <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] + 1])) ?>" class="px-3.5 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-100 transition-colors">
                <i data-lucide="chevron-right" class="w-4 h-4"></i>
            </a>
            <?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>

    <?php elseif (empty($searchCategories ?? []) && empty($searchBrands ?? [])): ?>
    <!-- No Results -->
    <div class="text-center py-20 bg-white border border-gray-200 rounded-2xl">
        <div class="w-20 h-20 mx-auto mb-4 bg-gray-100 rounded-2xl flex items-center justify-center">
            <i data-lucide="search-x" class="w-10 h-10 text-gray-300"></i>
        </div>
        <h2 class="font-heading text-xl font-bold text-gray-900 mb-2">No results found</h2>
        <?php if (!empty($query)): ?>
        <p class="text-gray-500 mb-6">We couldn't find anything matching "<span class="font-medium text-gray-700"><?= e($query) ?></span>"</p>
        <?php endif; ?>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="/products" class="inline-flex items-center gap-2 bg-amber-600 text-white font-medium px-6 py-3 rounded-xl hover:bg-amber-700 transition-colors">
                <i data-lucide="shopping-bag" class="w-4 h-4"></i> Browse All Products
            </a>
            <a href="/categories" class="inline-flex items-center gap-2 border border-gray-200 text-gray-700 font-medium px-6 py-3 rounded-xl hover:bg-gray-50 transition-colors">
                <i data-lucide="grid-3x3" class="w-4 h-4"></i> View Categories
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>