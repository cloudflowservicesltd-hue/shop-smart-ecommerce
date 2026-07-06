<?php
// Sliding Hero Section
$heroSlides = Database::select("SELECT * FROM hero_slides WHERE is_active = 1 ORDER BY sort_order ASC");
$promoBanners = Database::select("SELECT * FROM promo_banners WHERE is_active = 1 ORDER BY position ASC");
$trustBadges = Database::select("SELECT * FROM trust_badges WHERE is_active = 1 ORDER BY sort_order ASC");

// Get appearance settings
$appearance = [];
foreach (Database::select("SELECT * FROM settings WHERE group_name = 'appearance'") as $s) {
    $appearance[$s['key']] = $s['value'];
}
$heroAutoplay = ($appearance['hero_autoplay'] ?? '1') === '1';
$heroInterval = (int)($appearance['hero_interval'] ?? 5000);
$showCategories = ($appearance['show_categories'] ?? '1') === '1';
$showFeatured = ($appearance['show_featured'] ?? '1') === '1';
$showNewArrivals = ($appearance['show_new_arrivals'] ?? '1') === '1';
$showPromoBanners = ($appearance['show_promo_banners'] ?? '1') === '1';
$showTrustBadges = ($appearance['show_trust_badges'] ?? '1') === '1';
$showNewsletter = ($appearance['show_newsletter'] ?? '1') === '1';
?>

<!-- ==================== HERO SLIDER ==================== -->
<?php if (!empty($heroSlides)): ?>
<section class="relative w-full overflow-hidden" style="height: clamp(400px, 60vh, 650px);">
    <!-- Slides Container -->
    <div id="heroSlider" class="relative w-full h-full">
        <?php foreach ($heroSlides as $index => $slide): ?>
        <div class="hero-slide absolute inset-0 <?= $index === 0 ? 'active' : '' ?>" data-index="<?= $index ?>">
            <!-- Background Image -->
            <?php if (!empty($slide['image_url'])): ?>
            <div class="absolute inset-0">
                <img src="<?= e($slide['image_url']) ?>" alt="<?= e($slide['title']) ?>"
                     class="w-full h-full object-cover object-top scale-110 transition-transform duration-[8000ms] ease-linear
                            <?php echo $index === 0 ? 'hero-zoom-active' : ''; ?>">
            </div>
            <?php endif; ?>

            <!-- Gradient Overlay -->
            <div class="absolute inset-0 bg-gradient-to-r <?= e($slide['bg_gradient'] ?? 'from-amber-700 to-orange-800') ?> opacity-85 mix-blend-multiply"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-black/20"></div>

            <!-- Decorative Floating Elements -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div class="hero-float-shape shape-1 absolute w-64 h-64 bg-white/5 rounded-full blur-3xl" style="top: 10%; left: 60%;"></div>
                <div class="hero-float-shape shape-2 absolute w-48 h-48 bg-white/5 rounded-full blur-2xl" style="bottom: 15%; right: 20%;"></div>
                <div class="hero-float-shape shape-3 absolute w-32 h-32 bg-white/8 rounded-full blur-xl" style="top: 40%; left: 10%;"></div>
            </div>

            <!-- Slide Content -->
            <div class="relative h-full flex items-center">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 w-full">
                    <div class="max-w-2xl <?= $slide['text_position'] === 'center' ? 'mx-auto text-center' : ($slide['text_position'] === 'right' ? 'ml-auto text-right' : '') ?>">
                        
                        <!-- Badge / Subtitle -->
                        <?php if (!empty($slide['subtitle'])): ?>
                        <div class="slide-badge inline-flex items-center gap-2 bg-white/15 backdrop-blur-sm text-white/90 text-sm font-medium px-4 py-1.5 rounded-full mb-5 border border-white/10">
                            <i data-lucide="sparkles" class="w-4 h-4 text-amber-300"></i>
                            <?= e($slide['subtitle']) ?>
                        </div>
                        <?php endif; ?>

                        <!-- Title - Floating animation -->
                        <h1 class="slide-title font-heading text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-extrabold text-white leading-tight mb-5">
                            <?= e($slide['title']) ?>
                        </h1>

                        <!-- Description - Floating animation -->
                        <?php if (!empty($slide['description'])): ?>
                        <p class="slide-subtitle text-white/80 text-base sm:text-lg md:text-xl mb-8 max-w-lg <?= $slide['text_position'] === 'center' ? 'mx-auto' : ($slide['text_position'] === 'right' ? 'ml-auto' : '') ?>">
                            <?= e($slide['description']) ?>
                        </p>
                        <?php endif; ?>

                        <!-- CTA Buttons - Floating animation -->
                        <div class="slide-cta flex flex-col sm:flex-row gap-3 <?= $slide['text_position'] === 'center' ? 'justify-center' : ($slide['text_position'] === 'right' ? 'justify-end' : '') ?>">
                            <a href="<?= e($slide['cta_link']) ?>" class="inline-flex items-center justify-center gap-2 bg-white text-amber-700 font-bold px-7 py-3.5 rounded-xl hover:bg-amber-50 transition-all duration-300 shadow-lg shadow-black/20 hover:shadow-xl hover:shadow-black/30 hover:-translate-y-0.5">
                                <i data-lucide="shopping-bag" class="w-5 h-5"></i>
                                <?= e($slide['cta_text']) ?>
                            </a>
                            <a href="/products" class="inline-flex items-center justify-center gap-2 bg-white/10 backdrop-blur-sm text-white font-semibold px-7 py-3.5 rounded-xl hover:bg-white/20 transition-all duration-300 border border-white/20 hover:-translate-y-0.5">
                                <i data-lucide="grid-3x3" class="w-5 h-5"></i>
                                Browse All
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Slide Navigation Arrows -->
    <?php if (count($heroSlides) > 1): ?>
    <button onclick="heroSlider.prev()" class="hero-nav-btn absolute left-3 sm:left-6 top-1/2 -translate-y-1/2 z-20 w-11 h-11 bg-white/10 backdrop-blur-md text-white rounded-full flex items-center justify-center hover:bg-white/25 transition-all duration-300 border border-white/10 hover:scale-110">
        <i data-lucide="chevron-left" class="w-5 h-5"></i>
    </button>
    <button onclick="heroSlider.next()" class="hero-nav-btn absolute right-3 sm:right-6 top-1/2 -translate-y-1/2 z-20 w-11 h-11 bg-white/10 backdrop-blur-md text-white rounded-full flex items-center justify-center hover:bg-white/25 transition-all duration-300 border border-white/10 hover:scale-110">
        <i data-lucide="chevron-right" class="w-5 h-5"></i>
    </button>
    <?php endif; ?>

    <!-- Progress Bar -->
    <div class="absolute bottom-0 left-0 right-0 z-20 h-1 bg-white/10">
        <div id="heroProgress" class="h-full bg-amber-400 transition-none" style="width: 0%;"></div>
    </div>

    <!-- Dot Indicators -->
    <?php if (count($heroSlides) > 1): ?>
    <div class="absolute bottom-5 left-1/2 -translate-x-1/2 z-20 flex items-center gap-2.5">
        <?php foreach ($heroSlides as $i => $s): ?>
        <button onclick="heroSlider.goTo(<?= $i ?>)" 
                class="hero-dot w-2.5 h-2.5 rounded-full transition-all duration-300 <?= $i === 0 ? 'bg-white w-8' : 'bg-white/40 hover:bg-white/60' ?>"
                data-dot="<?= $i ?>"></button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>
<?php endif; ?>

<!-- ==================== CATEGORIES ==================== -->
<?php if ($showCategories && !empty($categories)): ?>
<section class="py-12 md:py-16 bg-stone-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="font-heading text-2xl md:text-3xl font-bold text-gray-900">Shop by Category</h2>
                <p class="text-gray-500 mt-1">Find what you're looking for</p>
            </div>
            <a href="/categories" class="hidden sm:inline-flex items-center gap-1 text-amber-600 hover:text-amber-700 font-medium text-sm transition-colors">
                View All <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
            <?php foreach ($categories as $index => $cat): ?>
            <a href="/category/<?= e($cat['slug']) ?>" 
               class="group bg-white rounded-2xl p-5 text-center hover:shadow-xl hover:shadow-amber-100/50 transition-all duration-300 border border-gray-100 hover:border-amber-200 hover:-translate-y-1">
                <div class="w-16 h-16 mx-auto mb-3 rounded-xl bg-amber-50 group-hover:bg-amber-100 flex items-center justify-center transition-all duration-300 group-hover:scale-110">
                    <img src="<?= $cat['image'] ?? '/uploads/no-image-sm.jpg' ?>" alt="<?= e($cat['name']) ?>" class="w-12 h-12 rounded-lg object-cover">
                </div>
                <h3 class="font-semibold text-gray-900 text-sm group-hover:text-amber-600 transition-colors"><?= e($cat['name']) ?></h3>
                <p class="text-xs text-gray-400 mt-1"><?= number_format($cat['product_count'] ?? 0) ?> items</p>
            </a>
            <?php endforeach; ?>
        </div>
        <div class="mt-4 text-center sm:hidden">
            <a href="/categories" class="inline-flex items-center gap-1 text-amber-600 font-medium text-sm">
                View All Categories <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ==================== FEATURED PRODUCTS ==================== -->
<?php if ($showFeatured && !empty($featuredProducts)): ?>
<section class="py-12 md:py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="font-heading text-2xl md:text-3xl font-bold text-gray-900">Featured Products</h2>
                <p class="text-gray-500 mt-1">Hand-picked just for you</p>
            </div>
            <a href="/products?featured=1" class="inline-flex items-center gap-1 text-amber-600 hover:text-amber-700 font-medium text-sm transition-colors">
                View All <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
            <?php foreach ($featuredProducts as $product): ?>
            <div class="group bg-white rounded-2xl border border-gray-100 overflow-hidden hover:shadow-xl hover:shadow-gray-200/50 transition-all duration-300 hover:-translate-y-1">
                <a href="/product/<?= e($product['slug']) ?>" class="block relative aspect-square bg-gray-50 overflow-hidden">
                    <?php if ($product['discount_price'] && $product['discount_price'] < $product['price']): ?>
                    <span class="absolute top-3 left-3 bg-rose-500 text-white text-xs font-bold px-2.5 py-1 rounded-lg z-10">
                        -<?= number_format((1 - $product['discount_price'] / $product['price']) * 100, 0) ?>%
                    </span>
                    <?php endif; ?>
                    <?php if ($product['is_featured']): ?>
                    <span class="absolute top-3 right-3 bg-amber-500 text-white text-xs font-bold px-2.5 py-1 rounded-lg z-10">Featured</span>
                    <?php endif; ?>
                    <img src="<?= $product['image'] ?? '/uploads/no-image.jpg' ?>" 
                         alt="<?= e($product['name']) ?>" 
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                </a>
                <div class="p-4">
                    <p class="text-xs text-gray-400 mb-1"><?= e($product['brand_name'] ?? '') ?></p>
                    <a href="/product/<?= e($product['slug']) ?>" class="block">
                        <h3 class="font-semibold text-gray-900 text-sm leading-snug group-hover:text-amber-600 transition-colors line-clamp-2"><?= e($product['name']) ?></h3>
                    </a>
                    <div class="flex items-center gap-1 mt-2">
                        <?php $avgRating = $product['avg_rating'] ?? 0; ?>
                        <?php for ($s = 1; $s <= 5; $s++): ?>
                            <i data-lucide="star" class="w-3.5 h-3.5 <?= $s <= round($avgRating) ? 'text-amber-400 fill-amber-400' : 'text-gray-200' ?>"></i>
                        <?php endfor; ?>
                        <span class="text-xs text-gray-400 ml-1">(<?= $product['review_count'] ?? 0 ?>)</span>
                    </div>
                    <div class="flex items-center justify-between mt-3">
                        <div>
                            <span class="font-bold text-gray-900"><?= formatMoney($product['discount_price'] ?? $product['price']) ?></span>
                            <?php if ($product['discount_price'] && $product['discount_price'] < $product['price']): ?>
                            <span class="text-xs text-gray-400 line-through ml-1.5"><?= formatMoney($product['price']) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($product['stock_quantity'] > 0): ?>
                        <form action="/cart/add" method="POST" class="inline-flex">
                            <?= csrf() ?>
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="p-2 bg-amber-50 text-amber-600 rounded-lg hover:bg-amber-600 hover:text-white transition-all duration-300" title="Add to Cart">
                                <i data-lucide="shopping-cart" class="w-4 h-4"></i>
                            </button>
                        </form>
                        <?php else: ?>
                        <span class="text-xs text-rose-500 font-medium">Out of Stock</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ==================== PROMO BANNERS ==================== -->
<?php if ($showPromoBanners && !empty($promoBanners)): ?>
<section class="py-12 md:py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="grid md:grid-cols-2 gap-6">
            <?php foreach ($promoBanners as $banner): ?>
            <div class="relative bg-gradient-to-br <?= e($banner['bg_gradient'] ?? 'from-amber-500 to-orange-600') ?> rounded-2xl p-8 md:p-10 text-white overflow-hidden group hover:shadow-2xl transition-all duration-500 hover:-translate-y-1">
                <!-- Decorative circles -->
                <div class="absolute top-0 right-0 w-40 h-40 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-125 transition-transform duration-700"></div>
                <div class="absolute bottom-0 left-0 w-32 h-32 bg-white/10 rounded-full translate-y-1/2 -translate-x-1/2 group-hover:scale-125 transition-transform duration-700"></div>
                <div class="relative">
                    <?php if (!empty($banner['icon'])): ?>
                    <span class="inline-flex items-center justify-center w-12 h-12 bg-white/20 rounded-xl mb-4 group-hover:scale-110 transition-transform duration-300">
                        <i data-lucide="<?= e($banner['icon']) ?>" class="w-6 h-6"></i>
                    </span>
                    <?php endif; ?>
                    <h3 class="font-heading text-2xl md:text-3xl font-bold mb-2"><?= e($banner['title']) ?></h3>
                    <p class="text-white/80 mb-6"><?= e($banner['subtitle'] ?? '') ?></p>
                    <a href="<?= e($banner['cta_link'] ?? '/products') ?>" class="inline-flex items-center gap-2 bg-white text-gray-900 font-semibold px-6 py-3 rounded-xl hover:bg-gray-50 transition-all duration-300 hover:gap-3 group/btn">
                        <?= e($banner['cta_text'] ?? 'Shop Now') ?> <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ==================== NEW ARRIVALS ==================== -->
<?php if ($showNewArrivals && !empty($newProducts)): ?>
<section class="py-12 md:py-16 bg-stone-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="font-heading text-2xl md:text-3xl font-bold text-gray-900">New Arrivals</h2>
                <p class="text-gray-500 mt-1">Freshly added to our store</p>
            </div>
            <a href="/products?sort=newest" class="inline-flex items-center gap-1 text-amber-600 hover:text-amber-700 font-medium text-sm transition-colors">
                View All <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
            <?php foreach (array_slice($newProducts, 0, 8) as $product): ?>
            <div class="group bg-white rounded-2xl border border-gray-100 overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                <a href="/product/<?= e($product['slug']) ?>" class="block relative aspect-square bg-gray-50 overflow-hidden">
                    <span class="absolute top-3 left-3 bg-gray-900 text-white text-xs font-bold px-2.5 py-1 rounded-lg z-10">New</span>
                    <img src="<?= $product['image'] ?? '/uploads/no-image.jpg' ?>" 
                         alt="<?= e($product['name']) ?>" 
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                </a>
                <div class="p-4">
                    <p class="text-xs text-gray-400 mb-1"><?= e($product['brand_name'] ?? '') ?></p>
                    <a href="/product/<?= e($product['slug']) ?>" class="block">
                        <h3 class="font-semibold text-gray-900 text-sm leading-snug group-hover:text-amber-600 transition-colors line-clamp-2"><?= e($product['name']) ?></h3>
                    </a>
                    <div class="flex items-center justify-between mt-3">
                        <span class="font-bold text-gray-900"><?= formatMoney($product['discount_price'] ?? $product['price']) ?></span>
                        <form action="/cart/add" method="POST" class="inline-flex">
                            <?= csrf() ?>
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="p-2 bg-amber-50 text-amber-600 rounded-lg hover:bg-amber-600 hover:text-white transition-all duration-300">
                                <i data-lucide="shopping-cart" class="w-4 h-4"></i>
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

<?php
// Google Reviews — no API key needed
$googleBusinessId = '';
$googleReviewLink = '';
try {
    $gs = Database::selectOne("SELECT value FROM settings WHERE `key` = 'google_place_id'");
    if ($gs && !empty($gs['value'])) {
        $googleBusinessId = $gs['value'];
        $googleReviewLink = 'https://g.page/r/' . $googleBusinessId . '/review';
    }
} catch (\Throwable $e) {}
$showTestimonials = !empty($googleBusinessId);
?>
<?php if ($showTestimonials): ?>
<section class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-8">
            <!-- Google Logo -->
            <div class="flex justify-center mb-4">
                <svg class="h-8" viewBox="0 0 272 92" xmlns="http://www.w3.org/2000/svg">
                    <path d="M115.75 47.18c0 12.77-9.99 22.18-22.25 22.18s-22.25-9.41-22.25-22.18C71.25 34.32 81.24 25 93.5 25s22.25 9.32 22.25 22.18zm-9.74 0c0-7.98-5.79-13.44-12.51-13.44S80.99 39.2 80.99 47.18c0 7.9 5.79 13.44 12.51 13.44s12.51-5.55 12.51-13.44z" fill="#EA4335"/>
                    <path d="M163.75 47.18c0 12.77-9.99 22.18-22.25 22.18s-22.25-9.41-22.25-22.18c0-12.85 9.99-22.18 22.25-22.18s22.25 9.32 22.25 22.18zm-9.74 0c0-7.98-5.79-13.44-12.51-13.44s-12.51 5.46-12.51 13.44c0 7.9 5.79 13.44 12.51 13.44s12.51-5.55 12.51-13.44z" fill="#FBBC05"/>
                    <path d="M209.75 26.34v39.82c0 16.38-9.66 23.07-21.08 23.07-10.75 0-17.22-7.19-19.66-13.07l8.48-3.53c1.51 3.61 5.21 7.87 11.17 7.87 7.31 0 11.84-4.51 11.84-13v-3.19h-.34c-2.18 2.69-6.38 5.04-11.68 5.04-11.09 0-21.25-9.66-21.25-22.09 0-12.52 10.16-22.26 21.25-22.26 5.29 0 9.49 2.35 11.68 4.96h.34v-3.61h9.25zm-8.56 20.92c0-7.81-5.21-13.52-11.84-13.52-6.72 0-12.35 5.71-12.35 13.52 0 7.73 5.63 13.36 12.35 13.36 6.63 0 11.84-5.63 11.84-13.36z" fill="#4285F4"/>
                    <path d="M225 3v65h-9.5V3h9.5z" fill="#34A853"/>
                    <path d="M262.02 54.48l7.56 5.04c-2.44 3.61-8.32 9.83-18.48 9.83-12.6 0-22.01-9.74-22.01-22.18 0-13.19 9.49-22.18 20.92-22.18 11.51 0 17.14 9.16 18.98 14.11l1.01 2.52-29.65 12.28c2.27 4.45 5.8 6.72 10.75 6.72 4.96 0 8.4-2.44 10.92-6.14zm-23.27-7.98l19.82-8.23c-1.09-2.77-4.37-4.7-8.23-4.7-4.95 0-11.84 4.37-11.59 12.93z" fill="#EA4335"/>
                    <path d="M35.29 41.19V32H67c.31 1.64.47 3.58.47 5.68 0 7.06-1.93 15.79-8.15 22.01-6.05 6.3-13.78 9.66-24.02 9.66C16.32 69.35.36 53.89.36 34.91.36 15.93 16.32.47 35.3.47c10.5 0 17.98 4.12 23.6 9.49l-6.64 6.64c-4.03-3.78-9.49-6.72-16.97-6.72-13.86 0-24.7 11.17-24.7 25.03 0 13.86 10.84 25.03 24.7 25.03 8.99 0 14.11-3.61 17.39-6.89 2.66-2.66 4.41-6.46 5.1-11.65l-22.49-.21z" fill="#4285F4"/>
                </svg>
            </div>
            <h2 class="font-heading font-bold text-2xl md:text-3xl text-gray-900 mb-2">What Our Customers Say</h2>
            <p class="text-gray-500 max-w-lg mx-auto">Real reviews from our valued customers on Google</p>
        </div>

        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-lg overflow-hidden">
                <!-- Google Maps Embed — no API key needed -->
                <div class="w-full h-64 bg-gray-100 relative">
                    <iframe
                        src="https://maps.google.com/maps?q=<?= urlencode($googleBusinessId) ?>&t=&z=15&ie=UTF8&iwloc=&output=embed"
                        width="100%"
                        height="100%"
                        style="border:0;"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        class="w-full h-full"
                    ></iframe>
                </div>

                <!-- CTA Section -->
                <div class="p-6 md:p-8 text-center">
                    <div class="flex justify-center mb-3">
                        <div class="flex items-center gap-1">
                            <svg class="w-7 h-7 text-amber-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                            <svg class="w-7 h-7 text-amber-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                            <svg class="w-7 h-7 text-amber-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                            <svg class="w-7 h-7 text-amber-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                            <svg class="w-7 h-7 text-amber-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                        </div>
                    </div>
                    <p class="text-gray-600 text-sm mb-5">We love hearing from our customers. Your feedback helps us improve and grow.</p>
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                        <a href="<?= e($googleReviewLink) ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 bg-white border-2 border-gray-200 hover:border-gray-300 text-gray-800 font-medium px-6 py-3 rounded-xl text-sm transition-all hover:shadow-md">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                            View Google Reviews
                        </a>
                        <a href="<?= e($googleReviewLink) ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 text-white font-medium px-6 py-3 rounded-xl text-sm transition-all hover:shadow-md hover:opacity-90" style="background:#4285F4">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                            Leave a Review
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ==================== NEWSLETTER ==================== -->
<?php if ($showNewsletter): ?>
<section class="py-12 md:py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="bg-gradient-to-br from-amber-600 via-orange-600 to-rose-600 rounded-3xl p-8 md:p-14 text-center text-white relative overflow-hidden">
            <!-- Decorative elements -->
            <div class="absolute top-0 left-1/4 w-64 h-64 bg-white/5 rounded-full blur-2xl animate-pulse-slow"></div>
            <div class="absolute bottom-0 right-1/4 w-48 h-48 bg-white/5 rounded-full blur-2xl animate-pulse-slow" style="animation-delay: 1s;"></div>
            <div class="absolute top-1/2 right-10 w-24 h-24 bg-white/5 rounded-full blur-xl animate-float-subtle"></div>
            
            <div class="relative">
                <span class="inline-flex items-center justify-center w-14 h-14 bg-white/15 backdrop-blur-sm rounded-2xl mb-5 border border-white/20">
                    <i data-lucide="mail" class="w-7 h-7"></i>
                </span>
                <h2 class="font-heading text-2xl md:text-3xl font-bold mb-3"><?= e($appearance['newsletter_heading'] ?? 'Stay in the Loop') ?></h2>
                <p class="text-amber-100 mb-8 max-w-md mx-auto"><?= e($appearance['newsletter_subheading'] ?? 'Subscribe to our newsletter for exclusive deals, new arrivals, and AI-powered recommendations.') ?></p>
                <form action="/newsletter" method="POST" class="flex flex-col sm:flex-row gap-3 max-w-md mx-auto">
                    <?= csrf() ?>
                    <input type="email" name="email" placeholder="Enter your email address" required
                        class="flex-1 px-5 py-3.5 rounded-xl text-gray-900 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300 shadow-lg">
                    <button type="submit" class="bg-gray-900 hover:bg-gray-800 text-white font-semibold px-8 py-3.5 rounded-xl transition-all duration-300 whitespace-nowrap hover:shadow-xl">
                        Subscribe
                    </button>
                </form>
                <p class="text-amber-200/70 text-xs mt-4">No spam. Unsubscribe anytime.</p>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ==================== TRUST BADGES ==================== -->
<?php if ($showTrustBadges && !empty($trustBadges)): ?>
<section class="py-12 md:py-16 border-t border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 md:gap-8">
            <?php foreach ($trustBadges as $badge): ?>
            <div class="text-center group">
                <div class="w-14 h-14 mx-auto mb-3 bg-amber-50 rounded-2xl flex items-center justify-center group-hover:bg-amber-100 group-hover:scale-110 transition-all duration-300">
                    <i data-lucide="<?= e($badge['icon_name'] ?? 'check') ?>" class="w-7 h-7 text-amber-600"></i>
                </div>
                <h3 class="font-semibold text-gray-900 text-sm"><?= e($badge['title']) ?></h3>
                <p class="text-xs text-gray-400 mt-1"><?= e($badge['subtitle'] ?? '') ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ==================== HERO SLIDER SCRIPTS & STYLES ==================== -->
<style>
    /* === HERO SLIDER ANIMATIONS === */
    .hero-slide {
        opacity: 0;
        transition: opacity 1s ease-in-out;
        pointer-events: none;
    }
    .hero-slide.active {
        opacity: 1;
        pointer-events: auto;
    }
    .hero-slide.active img {
        transform: scale(1.15);
    }

    /* Floating text entrance animations */
    .hero-slide .slide-badge {
        opacity: 0;
        transform: translateY(20px) translateX(-20px);
    }
    .hero-slide .slide-title {
        opacity: 0;
        transform: translateY(40px);
    }
    .hero-slide .slide-subtitle {
        opacity: 0;
        transform: translateY(30px);
    }
    .hero-slide .slide-cta {
        opacity: 0;
        transform: translateY(20px);
    }

    .hero-slide.active .slide-badge {
        animation: heroFloatInLeft 0.7s 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    .hero-slide.active .slide-title {
        animation: heroFloatUp 0.9s 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    .hero-slide.active .slide-subtitle {
        animation: heroFloatUp 0.9s 0.7s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    .hero-slide.active .slide-cta {
        animation: heroFloatUp 0.7s 0.9s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    /* Continuous gentle float on title after entrance */
    .hero-slide.active .slide-title {
        animation: heroFloatUp 0.9s 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards,
                   heroFloatGentle 4s 1.5s ease-in-out infinite;
    }

    @keyframes heroFloatUp {
        0% { opacity: 0; transform: translateY(40px); }
        100% { opacity: 1; transform: translateY(0); }
    }
    @keyframes heroFloatInLeft {
        0% { opacity: 0; transform: translateY(20px) translateX(-30px); }
        100% { opacity: 1; transform: translateY(0) translateX(0); }
    }
    @keyframes heroFloatGentle {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-8px); }
    }

    /* Floating decorative shapes */
    @keyframes floatShape1 {
        0%, 100% { transform: translate(0, 0) scale(1); }
        25% { transform: translate(20px, -30px) scale(1.1); }
        50% { transform: translate(-10px, -50px) scale(0.95); }
        75% { transform: translate(30px, -20px) scale(1.05); }
    }
    @keyframes floatShape2 {
        0%, 100% { transform: translate(0, 0) scale(1); }
        33% { transform: translate(-25px, -40px) scale(1.1); }
        66% { transform: translate(15px, -15px) scale(0.9); }
    }
    @keyframes floatShape3 {
        0%, 100% { transform: translate(0, 0); }
        50% { transform: translate(15px, -25px); }
    }
    .hero-float-shape.shape-1 { animation: floatShape1 8s ease-in-out infinite; }
    .hero-float-shape.shape-2 { animation: floatShape2 10s ease-in-out infinite 1s; }
    .hero-float-shape.shape-3 { animation: floatShape3 6s ease-in-out infinite 2s; }

    /* Dot indicators */
    .hero-dot.active {
        background-color: white !important;
        width: 2rem;
    }

    /* Nav buttons */
    .hero-nav-btn {
        opacity: 0;
        transition: all 0.4s ease;
    }
    .hero-slider-area:hover .hero-nav-btn,
    .hero-nav-btn:focus {
        opacity: 1;
    }
    section:hover .hero-nav-btn {
        opacity: 1;
    }

    /* Newsletter pulse */
    @keyframes pulse-slow {
        0%, 100% { opacity: 0.5; transform: scale(1); }
        50% { opacity: 1; transform: scale(1.1); }
    }
    .animate-pulse-slow { animation: pulse-slow 4s ease-in-out infinite; }
    @keyframes float-subtle {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-15px); }
    }
    .animate-float-subtle { animation: float-subtle 5s ease-in-out infinite; }

    /* Progress bar animation */
    @keyframes progressFill {
        from { width: 0%; }
        to { width: 100%; }
    }
    .hero-progress-animate {
        animation: progressFill <?= $heroInterval ?>ms linear;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.hero-dot');
    const progressBar = document.getElementById('heroProgress');
    const totalSlides = slides.length;

    if (totalSlides <= 1) return;

    let current = 0;
    let autoplayTimer = null;
    const interval = <?= $heroInterval ?>;

    const heroSlider = {
        goTo(index) {
            // Deactivate current
            slides[current].classList.remove('active');
            if (dots[current]) dots[current].classList.remove('active');

            current = (index + totalSlides) % totalSlides;

            // Activate new
            slides[current].classList.add('active');
            if (dots[current]) dots[current].classList.add('active');

            // Reset progress
            this.resetProgress();
        },
        next() {
            this.goTo(current + 1);
        },
        prev() {
            this.goTo(current - 1);
        },
        resetProgress() {
            if (progressBar) {
                progressBar.classList.remove('hero-progress-animate');
                void progressBar.offsetWidth; // force reflow
                progressBar.classList.add('hero-progress-animate');
            }
        },
        startAutoplay() {
            <?php if ($heroAutoplay): ?>
            this.stopAutoplay();
            autoplayTimer = setInterval(() => this.next(), interval);
            this.resetProgress();
            <?php endif; ?>
        },
        stopAutoplay() {
            if (autoplayTimer) {
                clearInterval(autoplayTimer);
                autoplayTimer = null;
            }
        }
    };

    // Make global
    window.heroSlider = heroSlider;

    // Touch/swipe support
    let touchStartX = 0;
    let touchEndX = 0;
    const sliderEl = document.getElementById('heroSlider');
    if (sliderEl) {
        sliderEl.addEventListener('touchstart', e => { touchStartX = e.changedTouches[0].screenX; }, { passive: true });
        sliderEl.addEventListener('touchend', e => {
            touchEndX = e.changedTouches[0].screenX;
            const diff = touchStartX - touchEndX;
            if (Math.abs(diff) > 50) {
                if (diff > 0) heroSlider.next();
                else heroSlider.prev();
            }
        }, { passive: true });
    }

    // Pause on hover
    const heroSection = sliderEl?.closest('section');
    if (heroSection) {
        heroSection.addEventListener('mouseenter', () => heroSlider.stopAutoplay());
        heroSection.addEventListener('mouseleave', () => heroSlider.startAutoplay());
    }

    // Start
    heroSlider.startAutoplay();

    // Keyboard support
    document.addEventListener('keydown', e => {
        if (e.key === 'ArrowLeft') heroSlider.prev();
        if (e.key === 'ArrowRight') heroSlider.next();
    });
});
</script>