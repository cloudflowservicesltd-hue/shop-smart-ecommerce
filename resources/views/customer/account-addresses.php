<?php if (!Auth::check()): ?>
<div class="min-h-[60vh] flex items-center justify-center px-4">
    <div class="text-center">
        <div class="w-20 h-20 mx-auto mb-4 bg-gray-100 rounded-2xl flex items-center justify-center">
            <i data-lucide="user" class="w-10 h-10 text-gray-300"></i>
        </div>
        <h2 class="font-heading text-xl font-bold text-gray-900 mb-2">Please Sign In</h2>
        <p class="text-gray-500 mb-6">Log in to access your account dashboard.</p>
        <a href="/login" class="inline-flex items-center gap-2 bg-amber-600 text-white font-semibold px-8 py-3 rounded-xl hover:bg-amber-700 transition-colors">
            <i data-lucide="log-in" class="w-5 h-5"></i> Sign In
        </a>
    </div>
</div>
<?php else: ?>
<?php $user = Auth::user(); ?>
<!-- Breadcrumbs -->
<div class="bg-gray-50 border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 py-3">
        <nav class="flex items-center gap-2 text-sm text-gray-500">
            <a href="/" class="hover:text-amber-600 transition-colors">Home</a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <a href="/account" class="hover:text-amber-600 transition-colors">Account</a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <span class="text-gray-900 font-medium">My Addresses</span>
        </nav>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 py-8">
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
                        <?php if (($orderCount ?? 0) > 0): ?>
                        <span class="ml-auto bg-gray-100 text-gray-500 text-xs font-medium px-2 py-0.5 rounded-full"><?= $orderCount ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="/account/wishlist" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                        <i data-lucide="heart" class="w-4 h-4"></i> Wishlist
                        <?php if (($wishlistCount ?? 0) > 0): ?>
                        <span class="ml-auto bg-gray-100 text-gray-500 text-xs font-medium px-2 py-0.5 rounded-full"><?= $wishlistCount ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="/account/reviews" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                        <i data-lucide="star" class="w-4 h-4"></i> My Reviews
                        <?php if (($reviewCount ?? 0) > 0): ?>
                        <span class="ml-auto bg-gray-100 text-gray-500 text-xs font-medium px-2 py-0.5 rounded-full"><?= $reviewCount ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="/account/profile" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                        <i data-lucide="user" class="w-4 h-4"></i> Edit Profile
                    </a>
                    <a href="/account/addresses" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium bg-amber-50 text-amber-700 transition-colors">
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
            <h1 class="font-heading text-2xl font-bold text-gray-900 mb-6">My Addresses</h1>

            <!-- Address Form -->
            <div class="bg-white border border-gray-200 rounded-2xl p-6">
                <div class="flex items-center gap-2 mb-6">
                    <i data-lucide="map-pin" class="w-5 h-5 text-amber-600"></i>
                    <h2 class="font-semibold text-gray-900">Delivery Address</h2>
                </div>

                <form method="POST" action="/account/addresses">
                    <?= csrf() ?>

                    <div class="grid sm:grid-cols-2 gap-5">
                        <!-- Phone -->
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1.5">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?= e($user['phone'] ?? '') ?>"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-colors"
                                placeholder="Enter your phone number">
                        </div>

                        <!-- City -->
                        <div>
                            <label for="city" class="block text-sm font-medium text-gray-700 mb-1.5">City</label>
                            <input type="text" id="city" name="city" value="<?= e($user['city'] ?? '') ?>"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-colors"
                                placeholder="Enter your city">
                        </div>

                        <!-- Address (full width) -->
                        <div class="sm:col-span-2">
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-1.5">Address</label>
                            <textarea id="address" name="address" rows="3"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-colors resize-none"
                                placeholder="Enter your street address"><?= e($user['address'] ?? '') ?></textarea>
                        </div>

                        <!-- Country (disabled) -->
                        <div>
                            <label for="country" class="block text-sm font-medium text-gray-700 mb-1.5">Country</label>
                            <input type="text" id="country" name="country" value="Kenya" disabled readonly
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-400 bg-gray-50 cursor-not-allowed">
                            <p class="text-xs text-gray-400 mt-1">Currently serving Kenya only.</p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-3 mt-8 pt-6 border-t border-gray-100">
                        <button type="submit" class="inline-flex items-center gap-2 bg-amber-600 text-white font-semibold px-6 py-2.5 rounded-xl hover:bg-amber-700 transition-colors text-sm">
                            <i data-lucide="check" class="w-4 h-4"></i> Save Changes
                        </button>
                        <a href="/account" class="inline-flex items-center gap-2 text-gray-600 font-medium px-6 py-2.5 rounded-xl hover:bg-gray-50 transition-colors text-sm">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>