<?php if (!Auth::check()): ?>
<div class="min-h-[60vh] flex items-center justify-center px-4">
    <div class="text-center">
        <div class="w-20 h-20 mx-auto mb-4 bg-red-50 rounded-2xl flex items-center justify-center">
            <i data-lucide="heart" class="w-10 h-10 text-red-300"></i>
        </div>
        <h2 class="font-heading text-xl font-bold text-gray-900 mb-2">Please Sign In</h2>
        <p class="text-gray-500 mb-6">Log in to view and manage your wishlist.</p>
        <a href="/login" class="inline-flex items-center gap-2 bg-amber-600 text-white font-semibold px-8 py-3 rounded-xl hover:bg-amber-700 transition-colors">
            <i data-lucide="log-in" class="w-5 h-5"></i> Sign In
        </a>
    </div>
</div>
<?php else: ?>
<!-- Breadcrumbs -->
<div class="bg-gray-50 border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 py-3">
        <nav class="flex items-center gap-2 text-sm text-gray-500">
            <a href="/" class="hover:text-amber-600 transition-colors">Home</a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <a href="/account" class="hover:text-amber-600 transition-colors">Account</a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <span class="text-gray-900 font-medium">Wishlist</span>
        </nav>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="font-heading text-2xl font-bold text-gray-900">My Wishlist</h1>
            <p class="text-sm text-gray-500 mt-1"><?= count($wishlist ?? []) ?> items saved</p>
        </div>
    </div>

    <?php if (!empty($wishlist ?? [])): ?>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
        <?php foreach ($wishlist as $item): ?>
        <div class="group bg-white rounded-2xl border border-gray-100 overflow-hidden hover:shadow-lg transition-all duration-300 animate-fade-in">
            <a href="/product/<?= e($item['slug']) ?>" class="block relative aspect-square bg-gray-50 overflow-hidden">
                <?php if (!empty($item['discount_price']) && $item['discount_price'] < $item['price']): ?>
                <span class="absolute top-3 left-3 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-lg z-10">
                    -<?= number_format((1 - $item['discount_price'] / $item['price']) * 100, 0) ?>%
                </span>
                <?php endif; ?>
                <?php if (empty($item['stock_quantity']) || $item['stock_quantity'] <= 0): ?>
                <div class="absolute inset-0 bg-black/40 flex items-center justify-center z-10">
                    <span class="bg-white text-gray-900 font-bold text-xs px-3 py-1.5 rounded-lg">Out of Stock</span>
                </div>
                <?php endif; ?>
                <img src="<?= $item['image'] ?? "/uploads/no-image.jpg" ?>" 
                     alt="<?= e($item['name']) ?>" 
                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
            </a>
            <div class="p-4">
                <h3 class="font-semibold text-gray-900 text-sm leading-snug group-hover:text-amber-600 transition-colors line-clamp-2">
                    <a href="/product/<?= e($item['slug']) ?>"><?= e($item['name']) ?></a>
                </h3>
                <div class="flex items-center justify-between mt-3">
                    <div>
                        <span class="font-bold text-gray-900 text-sm"><?= formatMoney($item['discount_price'] ?? $item['price']) ?></span>
                        <?php if (!empty($item['discount_price']) && $item['discount_price'] < $item['price']): ?>
                        <span class="text-xs text-gray-400 line-through ml-1"><?= formatMoney($item['price']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex gap-2 mt-3">
                    <?php if (!empty($item['stock_quantity']) && $item['stock_quantity'] > 0): ?>
                    <form action="/cart/add" method="POST" class="flex-1">
                        <?= csrf() ?>
                        <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                        <input type="hidden" name="quantity" value="1">
                        <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 bg-amber-600 text-white text-xs font-medium px-3 py-2 rounded-lg hover:bg-amber-700 transition-colors">
                            <i data-lucide="shopping-cart" class="w-3.5 h-3.5"></i> Add to Cart
                        </button>
                    </form>
                    <?php else: ?>
                    <button disabled class="flex-1 inline-flex items-center justify-center gap-1.5 bg-gray-100 text-gray-400 text-xs font-medium px-3 py-2 rounded-lg cursor-not-allowed">
                        Out of Stock
                    </button>
                    <?php endif; ?>
                    <form action="/wishlist/remove" method="POST">
                        <?= csrf() ?>
                        <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                        <button type="submit" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Remove from Wishlist">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <!-- Empty State -->
    <div class="text-center py-20 bg-white border border-gray-200 rounded-2xl">
        <div class="w-24 h-24 mx-auto mb-6 bg-red-50 rounded-3xl flex items-center justify-center">
            <i data-lucide="heart" class="w-12 h-12 text-red-300"></i>
        </div>
        <h2 class="font-heading text-xl font-bold text-gray-900 mb-2">Your wishlist is empty</h2>
        <p class="text-gray-500 mb-8 max-w-sm mx-auto">Save items you love to your wishlist. Review them anytime and easily move them to your cart.</p>
        <a href="/products" class="inline-flex items-center gap-2 bg-amber-600 text-white font-semibold px-8 py-3.5 rounded-xl hover:bg-amber-700 transition-colors">
            <i data-lucide="shopping-bag" class="w-5 h-5"></i> Explore Products
        </a>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>