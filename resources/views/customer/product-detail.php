<?php
$product = $product ?? [];
$images = $images ?? [];
$reviews = $reviews ?? [];
$relatedProducts = $relatedProducts ?? [];
$category = $category ?? null;
$brand = $brand ?? null;
$avgRating = $avgRating ?? 0;
$reviewCount = $reviewCount ?? 0;
$inWishlist = $inWishlist ?? false;
$primaryImage = $images[0]['image_path'] ?? "/uploads/no-image.jpg";
$hasDiscount = !empty($product['discount_price']) && $product['discount_price'] < $product['price'];
$finalPrice = $hasDiscount ? $product['discount_price'] : $product['price'];
?>

<!-- Breadcrumbs -->
<div class="bg-gray-50 border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 py-3">
        <nav class="flex items-center gap-2 text-sm text-gray-500 flex-wrap">
            <a href="/" class="hover:text-amber-600 transition-colors">Home</a>
            <i data-lucide="chevron-right" class="w-4 h-4 shrink-0"></i>
            <?php if ($category): ?>
                <a href="/category/<?= e($category['slug']) ?>" class="hover:text-amber-600 transition-colors"><?= e($category['name']) ?></a>
                <i data-lucide="chevron-right" class="w-4 h-4 shrink-0"></i>
            <?php endif; ?>
            <span class="text-gray-900 font-medium truncate"><?= e($product['name'] ?? '') ?></span>
        </nav>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="grid lg:grid-cols-2 gap-8 lg:gap-12">
        <!-- Image Gallery -->
        <div class="space-y-4">
            <div class="relative aspect-square bg-gray-50 rounded-2xl overflow-hidden border border-gray-100" style="cursor:zoom-in">
                <?php if ($hasDiscount): ?>
                <span class="absolute top-4 left-4 bg-red-500 text-white text-sm font-bold px-3 py-1.5 rounded-xl z-10">
                    -<?= number_format((1 - $product['discount_price'] / $product['price']) * 100, 0) ?>% OFF
                </span>
                <?php endif; ?>
                <img id="mainImage" src="<?= e($primaryImage) ?>" alt="<?= e($product['name']) ?>" class="w-full h-full object-cover">
            </div>
            <?php if (count($images) > 1): ?>
            <div class="flex gap-3 overflow-x-auto pb-2">
                <?php foreach ($images as $img): ?>
                <button onclick="document.getElementById('mainImage').src='<?= e($img['image_path']) ?>'; document.querySelectorAll('.thumb-btn').forEach(b=>b.classList.remove('ring-2','ring-amber-500')); this.classList.add('ring-2','ring-amber-500');" 
                        class="thumb-btn w-20 h-20 shrink-0 rounded-xl overflow-hidden border-2 border-gray-200 hover:border-amber-300 transition-colors <?= ($img['is_primary'] ?? 0) ? 'ring-2 ring-amber-500' : '' ?>">
                    <img src="<?= e($img['image_path']) ?>" alt="" class="w-full h-full object-cover">
                </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Product Info -->
        <div>
            <?php if ($brand): ?>
            <a href="/products?brand=<?= e($brand['slug']) ?>" class="text-sm text-amber-600 hover:text-amber-700 font-medium transition-colors"><?= e($brand['name']) ?></a>
            <?php endif; ?>

            <h1 class="font-heading text-2xl md:text-3xl font-bold text-gray-900 mt-2"><?= e($product['name']) ?></h1>

            <!-- Rating -->
            <div class="flex items-center gap-3 mt-3">
                <div class="flex items-center gap-0.5">
                    <?php for ($s = 1; $s <= 5; $s++): ?>
                        <i data-lucide="star" class="w-4.5 h-4.5 <?= $s <= round($avgRating) ? 'text-amber-400 fill-amber-400' : 'text-gray-200' ?>"></i>
                    <?php endfor; ?>
                </div>
                <span class="text-sm text-gray-500"><?= number_format($avgRating, 1) ?> (<?= $reviewCount ?> reviews)</span>
                <?php $pStatus = $product['product_status'] ?? 'active'; ?>
                <?php if ($product['stock_quantity'] > 0 && $pStatus === 'active'): ?>
                <span class="inline-flex items-center gap-1 text-sm text-amber-600 font-medium">
                    <span class="w-2 h-2 bg-amber-500 rounded-full pulse-dot"></span> In Stock
                </span>
                <?php elseif ($pStatus === 'out_of_stock_returning'): ?>
                <span class="inline-flex items-center gap-1 text-sm text-blue-600 font-medium">
                    <i data-lucide="clock" class="w-4 h-4"></i> Returning Soon
                </span>
                <?php else: ?>
                <span class="text-sm text-red-500 font-medium">Out of Stock</span>
                <?php endif; ?>
            </div>

            <!-- Price -->
            <div class="mt-6 flex items-baseline gap-3">
                <span class="text-3xl font-bold text-gray-900"><?= formatMoney($finalPrice) ?></span>
                <?php if ($hasDiscount): ?>
                <span class="text-lg text-gray-400 line-through"><?= formatMoney($product['price']) ?></span>
                <span class="bg-red-50 text-red-600 text-sm font-semibold px-2.5 py-1 rounded-lg">Save <?= formatMoney($product['price'] - $product['discount_price']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Short Description -->
            <?php if (!empty($product['short_description'])): ?>
            <p class="mt-4 text-gray-600 leading-relaxed"><?= e($product['short_description']) ?></p>
            <?php endif; ?>

            <!-- Add to Cart / Actions -->
            <?php if ($product['stock_quantity'] > 0 && $pStatus === 'active'): ?>
            <form action="/cart/add" method="POST" class="mt-8 space-y-5">
                <?= csrf() ?>
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

                <!-- Quantity -->
                <div>
                    <label class="text-sm font-medium text-gray-700 mb-2 block">Quantity</label>
                    <div class="inline-flex items-center border border-gray-200 rounded-xl overflow-hidden">
                        <button type="button" onclick="let i=document.getElementById('qty');if(i.value>1)i.value=parseInt(i.value)-1;document.getElementById('qtyHidden').value=i.value" class="px-4 py-2.5 text-gray-500 hover:bg-gray-50 transition-colors">
                            <i data-lucide="minus" class="w-4 h-4"></i>
                        </button>
                        <input type="number" id="qty" name="quantity" value="1" min="1" max="<?= $product['stock_quantity'] ?>" 
                               class="w-16 text-center border-x border-gray-200 py-2.5 text-sm font-medium focus:outline-none" 
                               onchange="document.getElementById('qtyHidden').value=this.value">
                        <button type="button" onclick="let i=document.getElementById('qty');let m=parseInt(i.max);if(parseInt(i.value)<m)i.value=parseInt(i.value)+1;document.getElementById('qtyHidden').value=i.value" class="px-4 py-2.5 text-gray-500 hover:bg-gray-50 transition-colors">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                        </button>
                    </div>
                    <input type="hidden" id="qtyHidden" name="quantity" value="1">
                    <span class="text-xs text-gray-400 ml-3"><?= number_format($product['stock_quantity']) ?> available</span>
                </div>

                <!-- Buttons -->
                <div class="flex flex-col sm:flex-row gap-3">
                    <button type="submit" class="flex-1 inline-flex items-center justify-center gap-2 bg-amber-600 text-white font-semibold px-8 py-3.5 rounded-xl hover:bg-amber-700 transition-colors shadow-lg shadow-amber-600/20">
                        <i data-lucide="shopping-cart" class="w-5 h-5"></i> Add to Cart
                    </button>
                    <button type="submit" formaction="/cart/add?buy_now=1" class="flex-1 inline-flex items-center justify-center gap-2 bg-gray-900 text-white font-semibold px-8 py-3.5 rounded-xl hover:bg-gray-800 transition-colors">
                        <i data-lucide="zap" class="w-5 h-5"></i> Buy Now
                    </button>
                </div>
            </form>

            <!-- Wishlist -->
            <?php if (Auth::check()): ?>
            <button type="button" id="wishlistBtn" onclick="toggleWishlist(<?= $product['id'] ?>)" class="inline-flex items-center gap-2 text-sm font-medium transition-colors <?= $inWishlist ? 'text-red-500 hover:text-red-600' : 'text-gray-500 hover:text-red-500' ?>">
                    <i data-lucide="heart" class="w-4 h-4 <?= $inWishlist ? 'fill-red-500' : '' ?>"></i>
                    <span id="wishlistText"><?= $inWishlist ? 'Remove from Wishlist' : 'Add to Wishlist' ?></span>
                </button>
            <?php else: ?>
            <a href="/login" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-red-500 transition-colors mt-4">
                <i data-lucide="heart" class="w-4 h-4"></i> Login to add to Wishlist
            </a>
            <?php endif; ?>
            <?php elseif ($pStatus === 'out_of_stock_returning'): ?>
            <!-- Returning Soon Box -->
            <div class="mt-8 p-6 bg-blue-50 border border-blue-200 rounded-xl">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center shrink-0 mt-0.5">
                        <i data-lucide="clock" class="w-5 h-5 text-blue-600"></i>
                    </div>
                    <div>
                        <p class="text-blue-800 font-semibold text-base">Returning Soon</p>
                        <p class="text-blue-600 text-sm mt-1">This product is temporarily out of stock but will be restocked soon. Add it to your wishlist to get notified.</p>
                        <?php if (Auth::check()): ?>
                        <button type="button" id="wishlistBtn" onclick="toggleWishlist(<?= $product['id'] ?>)" class="mt-3 inline-flex items-center gap-2 text-sm font-medium transition-colors <?= $inWishlist ? 'text-red-500 hover:text-red-600' : 'text-blue-600 hover:text-blue-700' ?>">
                            <i data-lucide="heart" class="w-4 h-4 <?= $inWishlist ? 'fill-red-500' : '' ?>"></i>
                            <span id="wishlistText"><?= $inWishlist ? 'Remove from Wishlist' : 'Add to Wishlist' ?></span>
                        </button>
                        <?php else: ?>
                        <a href="/login" class="mt-3 inline-flex items-center gap-2 text-sm font-medium text-blue-600 hover:text-blue-700">
                            <i data-lucide="heart" class="w-4 h-4"></i> Login to Wishlist
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="mt-8 p-6 bg-red-50 border border-red-200 rounded-xl">
                <p class="text-red-700 font-medium flex items-center gap-2">
                    <i data-lucide="alert-circle" class="w-5 h-5"></i> This product is currently out of stock
                </p>
                <a href="/" class="inline-flex items-center gap-1 text-sm text-red-600 hover:text-red-700 mt-2">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i> Continue Shopping
                </a>
            </div>
            <?php endif; ?>

            <!-- Meta Info -->
            <div class="mt-8 pt-6 border-t border-gray-100 space-y-3">
                <?php if (!empty($product['sku'])): ?>
                <div class="flex items-center gap-3 text-sm">
                    <span class="text-gray-400 w-24">SKU:</span>
                    <span class="text-gray-700 font-medium"><?= e($product['sku']) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($category): ?>
                <div class="flex items-center gap-3 text-sm">
                    <span class="text-gray-400 w-24">Category:</span>
                    <a href="/category/<?= e($category['slug']) ?>" class="text-amber-600 hover:text-amber-700 font-medium transition-colors"><?= e($category['name']) ?></a>
                </div>
                <?php endif; ?>
                <?php if (!empty($product['shipping_info'])): ?>
                <div class="flex items-center gap-3 text-sm">
                    <span class="text-gray-400 w-24">Shipping:</span>
                    <span class="text-gray-700"><?= e($product['shipping_info']) ?></span>
                </div>
                <?php else: ?>
                <div class="flex items-center gap-3 text-sm">
                    <span class="text-gray-400 w-24">Shipping:</span>
                    <span class="text-amber-600 font-medium flex items-center gap-1"><i data-lucide="truck" class="w-4 h-4"></i> Free over KSh 5,000</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Tabs Section -->
    <div class="mt-12">
        <div class="border-b border-gray-200">
            <nav class="flex gap-6" id="productTabs">
                <button onclick="switchTab('description')" class="tab-btn pb-3 text-sm font-semibold border-b-2 border-amber-600 text-amber-600 transition-colors" data-tab="description">Description</button>
                <button onclick="switchTab('reviews')" class="tab-btn pb-3 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent transition-colors" data-tab="reviews">Reviews (<?= $reviewCount ?>)</button>
                <button onclick="switchTab('specifications')" class="tab-btn pb-3 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent transition-colors" data-tab="specifications">Specifications</button>
            </nav>
        </div>

        <!-- Description Tab -->
        <div id="tab-description" class="tab-content py-8">
            <div class="prose max-w-none text-gray-600 leading-relaxed">
                <?= !empty($product['description']) ? nl2br(e($product['description'])) : '<p class="text-gray-400">No description available for this product.</p>' ?>
            </div>
        </div>

        <!-- Reviews Tab -->
        <div id="tab-reviews" class="tab-content py-8 hidden">
            <!-- Review Form (for logged-in users) -->
            <?php if (Auth::check()): ?>
            <?php $alreadyReviewed = Database::selectOne("SELECT id FROM reviews WHERE user_id = ? AND product_id = ?", [Auth::id(), $product['id']]); ?>
            <?php if (!$alreadyReviewed): ?>
            <div class="bg-gray-50 rounded-2xl p-6 mb-8 border border-gray-100">
                <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <i data-lucide="edit-3" class="w-4 h-4 text-amber-600"></i> Write a Review
                </h3>
                <form action="/reviews/submit" method="POST" class="space-y-4">
                    <?= csrf() ?>
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <!-- Star Rating -->
                    <div>
                        <label class="text-sm font-medium text-gray-700 mb-2 block">Rating</label>
                        <div class="flex gap-1" id="ratingStars">
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                            <button type="button" onclick="setRating(<?= $s ?>)" class="rating-star p-1 text-gray-300 hover:text-amber-400 transition-colors" data-value="<?= $s ?>">
                                <i data-lucide="star" class="w-6 h-6"></i>
                            </button>
                            <?php endfor; ?>
                            <input type="hidden" name="rating" id="ratingInput" value="5">
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700 mb-1 block">Title (optional)</label>
                        <input type="text" name="title" placeholder="Summarize your experience" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700 mb-1 block">Your Review</label>
                        <textarea name="review" rows="3" required placeholder="What did you like or dislike?" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent resize-none"></textarea>
                    </div>
                    <button type="submit" class="inline-flex items-center gap-2 bg-amber-600 text-white px-6 py-2.5 rounded-xl text-sm font-medium hover:bg-amber-700 transition-colors">
                        <i data-lucide="send" class="w-4 h-4"></i> Submit Review
                    </button>
                </form>
            </div>
            <?php else: ?>
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-8 flex items-center gap-3">
                <i data-lucide="check-circle" class="w-5 h-5 text-amber-600 shrink-0"></i>
                <p class="text-sm text-amber-700">You have already reviewed this product. <a href="/account/reviews" class="font-medium underline">View your reviews</a></p>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 mb-8 text-center">
                <p class="text-sm text-gray-600"><a href="/login" class="text-amber-600 font-medium hover:text-amber-700">Sign in</a> to write a review.</p>
            </div>
            <?php endif; ?>

            <?php if (!empty($reviews)): ?>
            <!-- Rating Summary -->
            <div class="flex flex-col md:flex-row gap-8 mb-8 pb-8 border-b border-gray-100">
                <div class="text-center md:text-left">
                    <div class="text-5xl font-bold text-gray-900"><?= number_format($avgRating, 1) ?></div>
                    <div class="flex items-center justify-center md:justify-start gap-0.5 mt-2">
                        <?php for ($s = 1; $s <= 5; $s++): ?>
                            <i data-lucide="star" class="w-5 h-5 <?= $s <= round($avgRating) ? 'text-amber-400 fill-amber-400' : 'text-gray-200' ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <p class="text-sm text-gray-500 mt-1"><?= $reviewCount ?> reviews</p>
                </div>
            </div>

            <!-- Reviews List -->
            <div class="space-y-6 max-h-96 overflow-y-auto scrollbar-thin pr-2">
                <?php foreach ($reviews as $review): ?>
                <div class="bg-gray-50 rounded-xl p-5">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <p class="font-semibold text-gray-900 text-sm"><?= e($review['user_name'] ?? 'Anonymous') ?></p>
                            <div class="flex items-center gap-0.5 mt-1">
                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                    <i data-lucide="star" class="w-3.5 h-3.5 <?= $s <= $review['rating'] ? 'text-amber-400 fill-amber-400' : 'text-gray-200' ?>"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <span class="text-xs text-gray-400"><?= timeAgo($review['created_at']) ?></span>
                    </div>
                    <?php if (!empty($review['title'])): ?>
                    <h4 class="font-medium text-gray-900 text-sm mt-2"><?= e($review['title']) ?></h4>
                    <?php endif; ?>
                    <p class="text-sm text-gray-600 mt-1.5 leading-relaxed"><?= e($review['review']) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-12">
                <i data-lucide="message-square" class="w-12 h-12 text-gray-200 mx-auto mb-3"></i>
                <p class="text-gray-500">No reviews yet. Be the first to review this product!</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Specifications Tab -->
        <div id="tab-specifications" class="tab-content py-8 hidden">
            <?php if (!empty($product['weight'])): ?>
            <table class="w-full max-w-md">
                <tbody class="divide-y divide-gray-100">
                    <?php if (!empty($product['sku'])): ?>
                    <tr><td class="py-3 text-sm text-gray-400 w-40">SKU</td><td class="py-3 text-sm text-gray-900 font-medium"><?= e($product['sku']) ?></td></tr>
                    <?php endif; ?>
                    <?php if ($brand): ?>
                    <tr><td class="py-3 text-sm text-gray-400">Brand</td><td class="py-3 text-sm text-gray-900 font-medium"><?= e($brand['name']) ?></td></tr>
                    <?php endif; ?>
                    <?php if ($category): ?>
                    <tr><td class="py-3 text-sm text-gray-400">Category</td><td class="py-3 text-sm text-gray-900 font-medium"><?= e($category['name']) ?></td></tr>
                    <?php endif; ?>
                    <tr><td class="py-3 text-sm text-gray-400">Weight</td><td class="py-3 text-sm text-gray-900 font-medium"><?= number_format($product['weight'], 2) ?> kg</td></tr>
                    <tr><td class="py-3 text-sm text-gray-400">Availability</td><td class="py-3 text-sm font-medium <?php
                        $specStatus = $product['product_status'] ?? 'active';
                        if ($product['stock_quantity'] > 0 && $specStatus === 'active') echo 'text-amber-600';
                        elseif ($specStatus === 'out_of_stock_returning') echo 'text-blue-600';
                        else echo 'text-red-500';
                    ?>"><?php
                        if ($product['stock_quantity'] > 0 && $specStatus === 'active') echo 'In Stock (' . $product['stock_quantity'] . ' units)';
                        elseif ($specStatus === 'out_of_stock_returning') echo '<span class="inline-flex items-center gap-1"><i data-lucide="clock" class="w-3.5 h-3.5"></i> Returning Soon</span>';
                        else echo 'Out of Stock';
                    ?></td></tr>
                </tbody>
            </table>
            <?php else: ?>
            <p class="text-gray-400 text-sm">No specifications available.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Related Products -->
<?php if (!empty($relatedProducts)): ?>
<section class="bg-gray-50 border-t border-gray-100 py-12">
    <div class="max-w-7xl mx-auto px-4">
        <h2 class="font-heading text-2xl font-bold text-gray-900 mb-6">You May Also Like</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
            <?php foreach ($relatedProducts as $rp): ?>
            <div class="group bg-white rounded-2xl border border-gray-100 overflow-hidden hover:shadow-lg transition-all duration-300">
                <a href="/product/<?= e($rp['slug']) ?>" class="block relative aspect-square bg-gray-50 overflow-hidden">
                    <?php if ($rp['discount_price'] && $rp['discount_price'] < $rp['price']): ?>
                    <span class="absolute top-2 left-2 bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-lg z-10">
                        -<?= number_format((1 - $rp['discount_price'] / $rp['price']) * 100, 0) ?>%
                    </span>
                    <?php endif; ?>
                    <img src="<?= $rp['image'] ?? "/uploads/no-image.jpg" ?>" 
                         alt="<?= e($rp['name']) ?>" 
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
                </a>
                <div class="p-3 md:p-4">
                    <h3 class="font-semibold text-gray-900 text-sm leading-snug group-hover:text-amber-600 transition-colors line-clamp-2"><?= e($rp['name']) ?></h3>
                    <div class="flex items-center justify-between mt-2">
                        <span class="font-bold text-gray-900 text-sm"><?= formatMoney($rp['discount_price'] ?? $rp['price']) ?></span>
                        <form action="/cart/add" method="POST" class="inline-flex">
                            <?= csrf() ?>
                            <input type="hidden" name="product_id" value="<?= $rp['id'] ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="p-1.5 bg-amber-50 text-amber-600 rounded-lg hover:bg-amber-600 hover:text-white transition-colors">
                                <i data-lucide="shopping-cart" class="w-3.5 h-3.5"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<script>
function switchTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(b => {
        b.classList.remove('border-amber-600', 'text-amber-600', 'font-semibold');
        b.classList.add('border-transparent', 'text-gray-500', 'font-medium');
    });
    document.getElementById('tab-' + tabName).classList.remove('hidden');
    const btn = document.querySelector('[data-tab="' + tabName + '"]');
    btn.classList.add('border-amber-600', 'text-amber-600', 'font-semibold');
    btn.classList.remove('border-transparent', 'text-gray-500', 'font-medium');
}

function setRating(value) {
    document.getElementById('ratingInput').value = value;
    document.querySelectorAll('.rating-star').forEach(s => {
        const v = parseInt(s.dataset.value);
        s.classList.toggle('text-amber-400', v <= value);
        s.classList.toggle('fill-amber-400', v <= value);
        s.classList.toggle('text-gray-300', v > value);
    });
}
// Initialize stars to default rating of 5
document.addEventListener('DOMContentLoaded', () => setRating(5));

function toggleWishlist(productId) {
    const btn = document.getElementById('wishlistBtn');
    const txt = document.getElementById('wishlistText');
    if (!btn || btn.disabled) return;
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
            btn.classList.add('text-red-500');
            if (svg) { svg.setAttribute('fill', 'currentColor'); svg.style.color = '#ef4444'; }
            if (txt) txt.textContent = 'Remove from Wishlist';
            showToast('Added to wishlist', 'success');
        } else {
            btn.classList.remove('text-red-500');
            btn.classList.add('text-gray-500');
            if (svg) { svg.setAttribute('fill', 'none'); svg.style.color = '#6b7280'; }
            if (txt) txt.textContent = 'Add to Wishlist';
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

// Image Zoom Magnifier
(function() {
    const mainImg = document.getElementById('mainImage');
    if (!mainImg) return;
    const container = mainImg.parentElement;
    
    // Create zoom lens
    const lens = document.createElement('div');
    lens.style.cssText = 'position:absolute;width:120px;height:120px;border:2px solid rgba(217,119,6,0.5);border-radius:50%;cursor:none;display:none;pointer-events:none;z-index:10;background-repeat:no-repeat;';
    container.style.position = 'relative';
    container.appendChild(lens);

    mainImg.addEventListener('mouseenter', function() { lens.style.display = 'block'; });
    mainImg.addEventListener('mouseleave', function() { lens.style.display = 'none'; });
    mainImg.addEventListener('mousemove', function(e) {
        const rect = mainImg.getBoundingClientRect();
        let x = e.clientX - rect.left;
        let y = e.clientY - rect.top;
        
        // Prevent lens from going outside image
        let lx = x - 60;
        let ly = y - 60;
        if (lx < 0) lx = 0;
        if (ly < 0) ly = 0;
        if (lx > rect.width - 120) lx = rect.width - 120;
        if (ly > rect.height - 120) ly = rect.height - 120;
        
        lens.style.left = lx + 'px';
        lens.style.top = ly + 'px';
        
        // Zoom factor 2.5x
        const zoomFactor = 2.5;
        const bgW = rect.width * zoomFactor;
        const bgH = rect.height * zoomFactor;
        const bgX = -(x * zoomFactor - 60);
        const bgY = -(y * zoomFactor - 60);
        
        lens.style.backgroundImage = 'url(' + mainImg.src + ')';
        lens.style.backgroundSize = bgW + 'px ' + bgH + 'px';
        lens.style.backgroundPosition = bgX + 'px ' + bgY + 'px';
    });
})();

function showToast(msg, type) {
    const existing = document.querySelector('.toast-notification');
    if (existing) existing.remove();
    const toast = document.createElement('div');
    toast.className = 'toast-notification fixed bottom-6 right-6 z-[9999] px-5 py-3 rounded-xl shadow-xl text-sm font-medium transition-all duration-300 opacity-0 translate-y-2';
    toast.style.cssText = type === 'error' ? 'background:#fef2f2;color:#991b1b;border:1px solid #fecaca;' : 'background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;';
    const icon = type === 'error'
        ? '<svg class="w-4 h-4 shrink-0 inline-block mr-1.5 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
        : '<svg class="w-4 h-4 shrink-0 inline-block mr-1.5 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
    toast.innerHTML = icon + msg;
    document.body.appendChild(toast);
    requestAnimationFrame(() => { toast.style.opacity = '1'; toast.style.transform = 'translateY(0)'; });
    setTimeout(() => { toast.style.opacity = '0'; toast.style.transform = 'translateY(2px)'; setTimeout(() => toast.remove(), 300); }, 2500);
}
</script>