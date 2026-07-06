<!-- Breadcrumbs -->
<div class="bg-gray-50 border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 py-3">
        <nav class="flex items-center gap-2 text-sm text-gray-500">
            <a href="/" class="hover:text-amber-600 transition-colors">Home</a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <a href="/account" class="hover:text-amber-600 transition-colors">Account</a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <span class="text-gray-900 font-medium">Change Password</span>
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
                    </a>
                    <a href="/account/wishlist" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                        <i data-lucide="heart" class="w-4 h-4"></i> Wishlist
                    </a>
                    <a href="/account/reviews" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors">
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
            <h1 class="font-heading text-2xl font-bold text-gray-900 mb-6">Change Password</h1>

            <?php if ($error = Session::flash('error')): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm mb-6 flex items-center gap-2">
                    <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i><?= e($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success = Session::flash('success')): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm mb-6 flex items-center gap-2">
                    <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i><?= e($success) ?>
                </div>
            <?php endif; ?>

            <div class="bg-white border border-gray-200 rounded-2xl p-6">
                <div class="flex items-center gap-2 mb-6">
                    <i data-lucide="shield" class="w-5 h-5 text-amber-600"></i>
                    <h2 class="font-semibold text-gray-900">Update Password</h2>
                </div>

                <form method="POST" action="/account/change-password">
                    <?= csrf() ?>

                    <div class="space-y-5 max-w-lg">
                        <!-- Current Password -->
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1.5">Current Password</label>
                            <div class="relative">
                                <i data-lucide="lock" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input type="password" id="current_password" name="current_password" required
                                    class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-colors"
                                    placeholder="Enter your current password">
                            </div>
                        </div>

                        <!-- New Password -->
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1.5">New Password</label>
                            <div class="relative">
                                <i data-lucide="key" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input type="password" id="new_password" name="new_password" required
                                    class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-colors"
                                    placeholder="Enter your new password">
                            </div>
                        </div>

                        <!-- Confirm New Password -->
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1.5">Confirm New Password</label>
                            <div class="relative">
                                <i data-lucide="lock" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input type="password" id="password_confirmation" name="password_confirmation" required
                                    class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-colors"
                                    placeholder="Confirm your new password">
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-3 mt-8 pt-6 border-t border-gray-100">
                        <button type="submit" class="inline-flex items-center gap-2 bg-amber-600 text-white font-semibold px-6 py-2.5 rounded-xl hover:bg-amber-700 transition-colors text-sm">
                            <i data-lucide="check" class="w-4 h-4"></i> Update Password
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