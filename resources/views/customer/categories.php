<?php
// Category circle size from settings
$circleSize = Database::selectOne("SELECT value FROM settings WHERE `key` = 'category_circle_size'")['value'] ?? 'lg';
$sizeMap = [
    'sm'  => ['circle' => 'w-24 h-24', 'wrap' => 'w-28', 'font' => 'text-xs', 'border' => 'border-3', 'gap' => 'gap-4'],
    'md'  => ['circle' => 'w-32 h-32', 'wrap' => 'w-36', 'font' => 'text-sm', 'border' => 'border-4', 'gap' => 'gap-5'],
    'lg'  => ['circle' => 'w-40 h-40', 'wrap' => 'w-44', 'font' => 'text-sm', 'border' => 'border-4', 'gap' => 'gap-6'],
    'xl'  => ['circle' => 'w-48 h-48', 'wrap' => 'w-52', 'font' => 'text-base', 'border' => 'border-4', 'gap' => 'gap-7'],
    '2xl' => ['circle' => 'w-56 h-56', 'wrap' => 'w-60', 'font' => 'text-base', 'border' => 'border-4', 'gap' => 'gap-8'],
];
$sz = $sizeMap[$circleSize] ?? $sizeMap['lg'];
?>
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
    <div class="flex <?= $sz['gap'] ?> overflow-x-auto pb-4 snap-x snap-mandatory scrollbar-thin" style="-webkit-overflow-scrolling: touch;">
        <?php foreach ($categories as $cat): 
            $subcategories = $cat['subcategories'] ?? [];
            $count = $cat['product_count'] ?? 0;
        ?>
        <a href="/category/<?= e($cat['slug']) ?>" class="group flex flex-col items-center snap-start shrink-0 <?= $sz['wrap'] ?>">
            <div class="<?= $sz['circle'] ?> rounded-full overflow-hidden <?= $sz['border'] ?> border-gray-100 group-hover:border-amber-300 transition-all duration-300 shadow-sm group-hover:shadow-lg">
                <img src="<?= $cat['image'] ?? "/uploads/no-image.jpg" ?>" 
                     alt="<?= e($cat['name']) ?>" 
                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
            </div>
            <h3 class="mt-3 <?= $sz['font'] ?> font-medium text-gray-900 group-hover:text-amber-600 transition-colors text-center line-clamp-2 leading-tight"><?= e($cat['name']) ?></h3>
            <span class="text-xs text-gray-400 mt-0.5"><?= number_format($count) ?> items</span>
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
        <div class="flex gap-4 overflow-x-auto pb-4 snap-x snap-mandatory scrollbar-thin" style="-webkit-overflow-scrolling: touch;">
            <?php foreach ($categories as $cat): ?>
                <?php foreach ($cat['subcategories'] ?? [] as $sub): ?>
                <a href="/category/<?= e($sub['slug']) ?>" class="snap-start shrink-0 w-40 group flex items-center gap-3 bg-white border border-gray-100 rounded-xl p-4 hover:border-amber-200 hover:shadow-md transition-all duration-300">
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