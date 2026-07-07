<?php if (!Auth::check()): ?>
<div class="min-h-[60vh] flex items-center justify-center px-4">
    <div class="text-center">
        <div class="w-20 h-20 mx-auto mb-4 bg-gray-100 rounded-2xl flex items-center justify-center">
            <i data-lucide="user" class="w-10 h-10 text-gray-300"></i>
        </div>
        <h2 class="font-heading text-xl font-bold text-gray-900 mb-2">Please Sign In</h2>
        <p class="text-gray-500 mb-6">Log in to view and manage your reviews.</p>
        <a href="/login" class="inline-flex items-center gap-2 bg-amber-600 text-white font-semibold px-8 py-3 rounded-xl hover:bg-amber-700 transition-colors">
            <i data-lucide="log-in" class="w-5 h-5"></i> Sign In
        </a>
    </div>
</div>
<?php else: ?>
<?php $user = Auth::user(); ?>
<!-- Breadcrumbs -->
<div class="bg-gray-50 border-b border-gray-100">
    <div class="w-full px-4 sm:px-6 xl:px-4 py-3">
        <nav class="flex items-center gap-2 text-sm text-gray-500">
            <a href="/" class="hover:text-amber-600 transition-colors">Home</a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <a href="/account" class="hover:text-amber-600 transition-colors">Account</a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <span class="text-gray-900 font-medium">My Reviews</span>
        </nav>
    </div>
</div>

<div class="w-full px-4 sm:px-6 xl:px-4 py-8">
    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Sidebar Navigation -->
        <aside class="lg:w-64 shrink-0">
            <div class="bg-white border border-gray-200 rounded-2xl p-6 sticky top-36">
                <!-- Profile Card -->
                <div class="text-center mb-6 pb-6 border-b border-gray-100">
                    <div class="w-16 h-16 mx-auto mb-3 bg-amber-100 rounded-2xl flex items-center justify-center">
                        <span class="text-2xl font-bold text-amber-600"><?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?></span>
                    </div>
                    <h3 class="font-semibold text-gray-900"><?= e($user['name']) ?></h3>
                    <p class="text-sm text-gray-400"><?= e($user['email']) ?></p>
                </div>

                <nav class="space-y-1">
                    <a href="/account" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                        <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Dashboard
                    </a>
                    <a href="/account/orders" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                        <i data-lucide="package" class="w-4 h-4"></i> My Orders
                    </a>
                    <a href="/account/wishlist" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                        <i data-lucide="heart" class="w-4 h-4"></i> Wishlist
                    </a>
                    <a href="/account/reviews" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium bg-amber-50 text-amber-700 transition-colors">
                        <i data-lucide="star" class="w-4 h-4"></i> My Reviews
                    </a>
                    <a href="/account/profile" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                        <i data-lucide="user" class="w-4 h-4"></i> Edit Profile
                    </a>
                    <a href="/account/addresses" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                        <i data-lucide="map-pin" class="w-4 h-4"></i> Addresses
                    </a>
                    <hr class="border-gray-100 my-2">
                    <a href="/logout" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-red-600 hover:bg-red-50 transition-colors">
                        <i data-lucide="log-out" class="w-4 h-4"></i> Logout
                    </a>
                </nav>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between mb-6">
                <h1 class="font-heading text-2xl font-bold text-gray-900">My Reviews</h1>
                <p class="text-sm text-gray-500"><?= count($reviews ?? []) ?> review<?= count($reviews ?? []) !== 1 ? 's' : '' ?></p>
            </div>

            <?php if (!empty($reviews ?? [])): ?>
            <div class="space-y-4">
                <?php foreach ($reviews as $r): ?>
                <?php
                    $approved = (int)($r['is_approved'] ?? 0) === 1;
                    $statusBg = $approved ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700';
                    $statusLabel = $approved ? 'Approved' : 'Pending';
                ?>
                <div class="bg-white border border-gray-200 rounded-2xl p-5 sm:p-6 hover:shadow-md transition-shadow">
                    <div class="flex gap-4">
                        <!-- Product Image -->
                        <a href="/product/<?= e($r['product_slug']) ?>" class="shrink-0">
                            <img src="<?= e($r['product_image'] ?? '/uploads/no-image.jpg') ?>"
                                 alt="<?= e($r['product_name']) ?>"
                                 class="w-20 h-20 sm:w-24 sm:h-24 object-cover rounded-xl border border-gray-100 hover:border-amber-300 transition-colors" loading="lazy">
                        </a>

                        <!-- Review Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-start justify-between gap-3 mb-2">
                                <div class="min-w-0">
                                    <!-- Product Name -->
                                    <a href="/product/<?= e($r['product_slug']) ?>" class="font-semibold text-gray-900 hover:text-amber-600 transition-colors text-sm sm:text-base leading-snug block truncate">
                                        <?= e($r['product_name']) ?>
                                    </a>
                                    <!-- Star Rating -->
                                    <div class="flex items-center gap-0.5 mt-1">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= (int)$r['rating']): ?>
                                        <i data-lucide="star" class="w-4 h-4 text-amber-500 fill-amber-500"></i>
                                        <?php else: ?>
                                        <i data-lucide="star" class="w-4 h-4 text-gray-200"></i>
                                        <?php endif; ?>
                                        <?php endfor; ?>
                                        <span class="text-xs text-gray-400 ml-1.5"><?= (float)$r['rating'] ?>/5</span>
                                    </div>
                                </div>

                                <!-- Status Badge -->
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold shrink-0 <?= $statusBg ?>">
                                    <?= $statusLabel ?>
                                </span>
                            </div>

                            <!-- Review Title -->
                            <?php if (!empty($r['title'])): ?>
                            <h3 class="font-semibold text-gray-900 text-sm mb-1"><?= e($r['title']) ?></h3>
                            <?php endif; ?>

                            <!-- Review Text -->
                            <?php if (!empty($r['review'])): ?>
                            <p class="text-gray-600 text-sm leading-relaxed mb-3"><?= e($r['review']) ?></p>
                            <?php endif; ?>

                            <!-- Footer: Date + Delete -->
                            <div class="flex items-center justify-between">
                                <time class="text-xs text-gray-400" title="<?= formatDate($r['created_at']) ?>">
                                    <?= timeAgo($r['created_at']) ?>
                                </time>

                                <form action="/account/reviews/<?= (int)$r['id'] ?>/delete" method="POST"
                                      onsubmit="return confirm('Are you sure you want to delete this review? This action cannot be undone.');">
                                    <?= csrf() ?>
                                    <button type="submit"
                                            class="inline-flex items-center gap-1.5 text-xs text-gray-400 hover:text-red-600 hover:bg-red-50 px-2.5 py-1.5 rounded-lg transition-colors">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if (!empty($pagination) && ($pagination['last_page'] ?? 1) > 1): ?>
            <div class="mt-8 flex items-center justify-center gap-1">
                <?php if (($pagination['current_page'] ?? 1) > 1): ?>
                <a href="?page=<?= $pagination['current_page'] - 1 ?>" class="px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-100 transition-colors">
                    <i data-lucide="chevron-left" class="w-4 h-4"></i>
                </a>
                <?php endif; ?>
                <?php for ($p = 1; $p <= $pagination['last_page']; $p++): ?>
                <a href="?page=<?= $p ?>" class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= $p == $pagination['current_page'] ? 'bg-amber-600 text-white' : 'text-gray-600 hover:bg-gray-100' ?>"><?= $p ?></a>
                <?php endfor; ?>
                <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
                <a href="?page=<?= $pagination['current_page'] + 1 ?>" class="px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-100 transition-colors">
                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <!-- Empty State -->
            <div class="text-center py-20 bg-white border border-gray-200 rounded-2xl">
                <div class="w-20 h-20 mx-auto mb-4 bg-amber-50 rounded-2xl flex items-center justify-center">
                    <i data-lucide="star" class="w-10 h-10 text-amber-300"></i>
                </div>
                <h3 class="font-heading text-lg font-semibold text-gray-900 mb-2">No reviews yet</h3>
                <p class="text-gray-500 mb-6 max-w-sm mx-auto">You haven't submitted any product reviews. Share your experience to help other shoppers decide.</p>
                <a href="/products" class="inline-flex items-center gap-2 bg-amber-600 text-white font-medium px-6 py-3 rounded-xl hover:bg-amber-700 transition-colors">
                    <i data-lucide="shopping-bag" class="w-4 h-4"></i> Browse Products
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>