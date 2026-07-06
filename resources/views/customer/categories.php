<!-- Breadcrumbs -->
<div class="bg-gray-50 border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 py-3">
        <nav class="flex items-center gap-2 text-sm text-gray-500">
            <a href="/" class="hover:text-amber-600 transition-colors">Home</a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <span class="text-gray-900 font-medium">All Categories</span>
        </nav>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="font-heading text-2xl md:text-3xl font-bold text-gray-900">All Categories</h1>
        <p class="text-gray-500 mt-1">Browse our wide range of product categories</p>
    </div>

    <?php if (!empty($categories ?? [])): ?>
    <!-- Top-Level Categories -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
        <?php foreach ($categories as $cat): 
            $subcategories = $cat['subcategories'] ?? [];
            $count = $cat['product_count'] ?? 0;
        ?>
        <a href="/category/<?= e($cat['slug']) ?>" class="group bg-white rounded-2xl border border-gray-100 overflow-hidden hover:shadow-lg hover:border-amber-200 transition-all duration-300">
            <div class="aspect-video bg-gradient-to-br from-amber-50 to-teal-50 overflow-hidden relative">
                <img src="<?= $cat['image'] ?? "/uploads/no-image.jpg" ?>" 
                     alt="<?= e($cat['name']) ?>" 
                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
            </div>
            <div class="p-5">
                <h3 class="font-heading font-semibold text-gray-900 group-hover:text-amber-600 transition-colors"><?= e($cat['name']) ?></h3>
                <?php if (!empty($cat['description'])): ?>
                <p class="text-xs text-gray-400 mt-1 line-clamp-1"><?= e($cat['description']) ?></p>
                <?php endif; ?>
                <div class="flex items-center justify-between mt-3">
                    <span class="text-xs text-gray-400"><?= number_format($count) ?> products</span>
                    <i data-lucide="arrow-right" class="w-4 h-4 text-gray-300 group-hover:text-amber-500 group-hover:translate-x-1 transition-all"></i>
                </div>
                <?php if (!empty($subcategories)): ?>
                <div class="flex flex-wrap gap-1.5 mt-3 pt-3 border-t border-gray-100">
                    <?php foreach (array_slice($subcategories, 0, 3) as $sub): ?>
                    <span class="text-[11px] bg-gray-50 text-gray-500 px-2 py-0.5 rounded-full"><?= e($sub['name']) ?></span>
                    <?php endforeach; ?>
                    <?php if (count($subcategories) > 3): ?>
                    <span class="text-[11px] text-amber-600 font-medium">+<?= count($subcategories) - 3 ?> more</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Sub-Categories (if parent categories have them) -->
    <?php 
    $hasSubs = false;
    foreach ($categories ?? [] as $cat) {
        if (!empty($cat['subcategories'] ?? [])) { $hasSubs = true; break; }
    }
    if ($hasSubs): ?>
    <div class="mt-16">
        <h2 class="font-heading text-xl font-bold text-gray-900 mb-6">Sub-Categories</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
            <?php foreach ($categories as $cat): ?>
                <?php foreach ($cat['subcategories'] ?? [] as $sub): ?>
                <a href="/category/<?= e($sub['slug']) ?>" class="group flex items-center gap-3 bg-white border border-gray-100 rounded-xl p-4 hover:border-amber-200 hover:shadow-md transition-all duration-300">
                    <div class="w-10 h-10 bg-amber-50 rounded-lg flex items-center justify-center shrink-0 group-hover:bg-amber-100 transition-colors">
                        <i data-lucide="tag" class="w-4 h-4 text-amber-600"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900 group-hover:text-amber-600 transition-colors truncate"><?= e($sub['name']) ?></p>
                        <p class="text-xs text-gray-400"><?= number_format($sub['product_count'] ?? 0) ?> items</p>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <!-- Empty State -->
    <div class="text-center py-20 bg-white border border-gray-200 rounded-2xl">
        <div class="w-20 h-20 mx-auto mb-4 bg-gray-100 rounded-2xl flex items-center justify-center">
            <i data-lucide="grid-3x3" class="w-10 h-10 text-gray-300"></i>
        </div>
        <h2 class="font-heading text-xl font-bold text-gray-900 mb-2">No categories yet</h2>
        <p class="text-gray-500 mb-6">Categories will appear here once they're added.</p>
        <a href="/" class="inline-flex items-center gap-2 bg-amber-600 text-white font-medium px-6 py-3 rounded-xl hover:bg-amber-700 transition-colors">
            <i data-lucide="home" class="w-4 h-4"></i> Back to Home
        </a>
    </div>
    <?php endif; ?>
</div>