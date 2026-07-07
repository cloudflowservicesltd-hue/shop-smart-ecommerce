<?php
// Load all site settings once for the entire layout
$siteSettings = [];
try {
    foreach (Database::select("SELECT * FROM settings") as $s) {
        $siteSettings[$s['key']] = $s['value'];
    }
} catch (\Throwable $e) {}

$siteLogo = !empty($siteSettings['site_logo']) ? $siteSettings['site_logo'] : '';
$siteFavicon = !empty($siteSettings['site_favicon']) ? $siteSettings['site_favicon'] : '';
$storeName = $siteSettings['store_name'] ?? 'ShopSmart';
$storeEmail = $siteSettings['store_email'] ?? 'info@shopsmart.co.ke';
$storePhone = $siteSettings['store_phone'] ?? '+254 700 000 000';
$storeAddress = $siteSettings['store_address'] ?? 'Kenyatta Avenue, Nairobi CBD, Kenya';

// Currency & shipping from database
$currencySymbol = !empty($siteSettings['currency_symbol']) ? $siteSettings['currency_symbol'] : 'KSh';
$shippingThreshold = !empty($siteSettings['shipping_threshold']) ? (float)$siteSettings['shipping_threshold'] : 5000;

// Logo display size
$logoHeight = (int)($siteSettings['logo_height'] ?? 40);
if ($logoHeight < 20) $logoHeight = 20;
if ($logoHeight > 300) $logoHeight = 300;

// Color settings with defaults
$primaryColor = $siteSettings['primary_color'] ?? '#d97706';
$primaryHoverColor = $siteSettings['primary_hover_color'] ?? '#b45309';
$headerBgColor = $siteSettings['header_bg_color'] ?? '#ffffff';
$footerBgColor = $siteSettings['footer_bg_color'] ?? '#111827';

// Social links
$socialFb = $siteSettings['social_facebook'] ?? '';
$socialTw = $siteSettings['social_twitter'] ?? '';
$socialIg = $siteSettings['social_instagram'] ?? '';
$socialYt = $siteSettings['social_youtube'] ?? '';
$socialTk = $siteSettings['social_tiktok'] ?? '';
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? $storeName . ' - AI-Powered Ecommerce') ?></title>
    <?php if ($siteFavicon): ?>
        <link rel="icon" type="image/x-icon" href="<?= e($siteFavicon) ?>">
        <link rel="icon" type="image/png" sizes="32x32" href="<?= e($siteFavicon) ?>">
        <link rel="icon" type="image/png" sizes="16x16" href="<?= e($siteFavicon) ?>">
    <?php else: ?>
        <link rel="icon" type="image/x-icon" href="/favicon.ico">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <?php endif; ?>
    <meta name="description" content="<?= e($metaDescription ?? 'Shop smarter with AI-powered ecommerce. Browse thousands of products with intelligent recommendations.') ?>">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        heading: ['Poppins', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#fffbeb',100: '#fef3c7',200: '#fde68a',300: '#fcd34d',400: '#fbbf24',
                            500: '#f59e0b',600: '<?= $primaryColor ?>',700: '<?= $primaryHoverColor ?>',800: '#92400e',900: '#78350f'
                        },
                    }
                }
            }
        }
    </script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

    <style>
        :root {
            --color-primary: <?= $primaryColor ?>;
            --color-primary-hover: <?= $primaryHoverColor ?>;
            --color-header-bg: <?= $headerBgColor ?>;
            --color-footer-bg: <?= $footerBgColor ?>;
        }
        [x-cloak] { display: none !important; }
        .scrollbar-thin::-webkit-scrollbar { width: 6px; }
        .scrollbar-thin::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 3px; }
        .scrollbar-thin::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .scrollbar-thin::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        body { font-family: 'Inter', system-ui, sans-serif; }
        h1,h2,h3,h4,h5,h6,.font-heading { font-family: 'Poppins', sans-serif; }
        @keyframes fadeIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
        .animate-fade-in { animation: fadeIn 0.3s ease-out; }
        @keyframes slideIn { from { transform:translateX(-100%); } to { transform:translateX(0); } }
        .animate-slide-in { animation: slideIn 0.3s ease-out; }
        @keyframes pulse-dot { 0%,100% { opacity:1; } 50% { opacity:0.5; } }
        .pulse-dot { animation: pulse-dot 2s ease-in-out infinite; }

        /* Dynamic color overrides */
        .site-header { background-color: var(--color-header-bg) !important; }
        .site-footer { background-color: var(--color-footer-bg) !important; }
        .btn-primary { background-color: var(--color-primary) !important; }
        .btn-primary:hover { background-color: var(--color-primary-hover) !important; }
        .text-primary-site { color: var(--color-primary) !important; }
        .bg-primary-site { background-color: var(--color-primary) !important; }
        .border-primary-site { border-color: var(--color-primary) !important; }
        .ring-primary-site:focus { --tw-ring-color: var(--color-primary) !important; }
        a.text-primary-site:hover { color: var(--color-primary-hover) !important; }

        /* Apply primary color to common interactive elements */
        .site-header .hover\:text-amber-600:hover { color: var(--color-primary) !important; }
        .site-header .hover\:bg-amber-600:hover { background-color: var(--color-primary) !important; }
        .site-header .hover\:bg-amber-50:hover { background-color: color-mix(in srgb, var(--color-primary) 10%, white) !important; }
        .site-header .focus\:ring-amber-500:focus { --tw-ring-color: var(--color-primary) !important; }
        .site-footer .hover\:text-amber-400:hover { color: var(--color-primary) !important; }
        .site-footer .hover\:bg-amber-600:hover { background-color: var(--color-primary) !important; }
        .site-footer .text-amber-400 { color: var(--color-primary) !important; }
        .site-footer .bg-amber-600 { background-color: var(--color-primary) !important; }

        /* Flash message color override */
        .flash-success { background-color: color-mix(in srgb, var(--color-primary) 10%, white) !important; border-color: color-mix(in srgb, var(--color-primary) 30%, white) !important; color: var(--color-primary) !important; }
        .flash-success .text-amber-700 { color: var(--color-primary) !important; }
        .flash-success .text-amber-400:hover { color: var(--color-primary-hover) !important; }

        /* Social icon hover uses primary */
        .social-icon:hover { background-color: var(--color-primary) !important; }

        /* Badge / cart count */
        .cart-badge, .wishlist-badge { background-color: var(--color-primary) !important; }

        /* Top bar */
        .top-bar { background-color: color-mix(in srgb, var(--color-primary) 85%, black) !important; }
    </style>
</head>
<body class="bg-white text-gray-900 min-h-screen flex flex-col">
    <!-- Flash Messages -->
    <?php if ($error = Session::flash('error')): ?>
    <div class="fixed top-4 right-4 z-[100] animate-fade-in">
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg shadow-lg flex items-center gap-2 max-w-sm">
            <i data-lucide="alert-circle" class="w-5 h-5 shrink-0"></i>
            <span class="text-sm"><?= e($error) ?></span>
            <button onclick="this.closest('.fixed').remove()" class="ml-auto text-red-400 hover:text-red-600">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    </div>
    <?php endif; ?>
    <?php if ($success = Session::flash('success')): ?>
    <div class="fixed top-4 right-4 z-[100] animate-fade-in">
        <div class="flash-success border px-4 py-3 rounded-lg shadow-lg flex items-center gap-2 max-w-sm">
            <i data-lucide="check-circle" class="w-5 h-5 shrink-0"></i>
            <span class="text-sm"><?= e($success) ?></span>
            <button onclick="this.closest('.fixed').remove()" class="ml-auto text-amber-400 hover:text-amber-600">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Navigation -->
    <header class="sticky top-0 z-50 bg-white border-b border-gray-100 shadow-sm site-header">
        <!-- Top bar -->
        <div class="top-bar text-white text-xs py-1.5 hidden sm:block">
            <div class="w-full px-4 sm:px-6 lg:px-8 flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $storePhone)) ?>" class="flex items-center gap-1 hover:text-amber-300 transition-colors"><i data-lucide="phone" class="w-3 h-3"></i> <?= e($storePhone) ?></a>
                    <a href="mailto:<?= e($storeEmail) ?>" class="flex items-center gap-1 hover:text-amber-300 transition-colors"><i data-lucide="mail" class="w-3 h-3"></i> <?= e($storeEmail) ?></a>
                </div>
                <div class="flex items-center gap-4">
                    <span><?= e(!empty($siteSettings['shipping_banner_text']) ? $siteSettings['shipping_banner_text'] : 'Free shipping on orders over ' . $currencySymbol . ' ' . number_format($shippingThreshold)) ?></span>
                </div>
            </div>
        </div>

        <!-- Main nav -->
        <nav class="w-full px-4 sm:px-6 lg:px-8 py-3">
            <div class="flex items-center justify-between">
                <!-- Logo -->
                <a href="/" class="flex items-center gap-2">
                    <?php if ($siteLogo): ?>
                        <img src="<?= e($siteLogo) ?>" alt="<?= e($storeName) ?>" class="w-auto object-contain" style="height:<?= $logoHeight ?>px;max-width:<?= $logoHeight * 3.5 ?>px;">
                    <?php else: ?>
                        <div class="w-9 h-9 bg-primary-600 rounded-lg flex items-center justify-center">
                            <i data-lucide="shopping-bag" class="w-5 h-5 text-white"></i>
                        </div>
                        <span class="font-heading font-bold text-xl text-gray-900"><?= e($storeName) ?></span>
                    <?php endif; ?>
                </a>

                <!-- Search bar (desktop) -->
                <div class="hidden md:flex flex-1 max-w-2xl mx-8 xl:mx-12">
                    <form action="/search" method="GET" class="w-full relative">
                        <input type="text" name="q" placeholder="Search products, brands, categories..."
                            value="<?= e(Request::query('q', '')) ?>"
                            class="w-full pl-4 pr-12 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent bg-gray-50">
                        <button type="submit" class="absolute right-1 top-1 bottom-1 px-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                            <i data-lucide="search" class="w-4 h-4"></i>
                        </button>
                    </form>
                </div>

                <!-- Right icons -->
                <div class="flex items-center gap-2">
                    <!-- Mobile search toggle -->
                    <button onclick="document.getElementById('mobileSearch').classList.toggle('hidden')" class="md:hidden p-2 text-gray-600 hover:text-amber-600 hover:bg-gray-50 rounded-lg transition-colors">
                        <i data-lucide="search" class="w-5 h-5"></i>
                    </button>

                    <!-- User -->
                    <?php if (Auth::check()): ?>
                        <div class="relative group">
                            <button class="p-2 text-gray-600 hover:text-amber-600 hover:bg-gray-50 rounded-lg transition-colors flex items-center gap-1">
                                <i data-lucide="user" class="w-5 h-5"></i>
                                <span class="hidden sm:inline text-sm font-medium"><?= e(Auth::user()['name'] ?? 'Account') ?></span>
                            </button>
                            <div class="absolute right-0 top-full mt-1 w-48 bg-white border border-gray-100 rounded-xl shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                                <?php if (Auth::isAdmin()): ?>
                                    <a href="/admin" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-amber-50 hover:text-amber-700 rounded-t-xl transition-colors">
                                        <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Admin Dashboard
                                    </a>
                                <?php endif; ?>
                                <?php if (Auth::isCashier() && !Auth::isAdmin()): ?>
                                    <a href="/pos" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-amber-50 hover:text-amber-700 rounded-t-xl transition-colors">
                                        <i data-lucide="monitor" class="w-4 h-4"></i> POS Terminal
                                    </a>
                                <?php endif; ?>
                                <a href="/account" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-amber-50 hover:text-amber-700 transition-colors">
                                    <i data-lucide="package" class="w-4 h-4"></i> My Orders
                                </a>
                                <a href="/account/wishlist" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-amber-50 hover:text-amber-700 transition-colors">
                                    <i data-lucide="heart" class="w-4 h-4"></i> Wishlist
                                </a>
                                <hr class="border-gray-100">
                                <a href="/logout" class="flex items-center gap-2 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 rounded-b-xl transition-colors">
                                    <i data-lucide="log-out" class="w-4 h-4"></i> Logout
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="/login" class="p-2 text-gray-600 hover:text-amber-600 hover:bg-gray-50 rounded-lg transition-colors">
                            <i data-lucide="user" class="w-5 h-5"></i>
                        </a>
                    <?php endif; ?>

                    <!-- Wishlist -->
                    <?php if (Auth::check()): ?>
                    <a href="/account/wishlist" class="relative p-2 text-gray-600 hover:text-amber-600 hover:bg-gray-50 rounded-lg transition-colors">
                        <i data-lucide="heart" class="w-5 h-5"></i>
                        <?php
                        $wishlistCount = Database::count('wishlists', 'user_id = ?', [Auth::id()]);
                        if ($wishlistCount > 0): ?>
                            <span class="wishlist-badge absolute -top-0.5 -right-0.5 w-4.5 h-4.5 bg-rose-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center"><?= $wishlistCount ?></span>
                        <?php endif; ?>
                    </a>
                    <?php endif; ?>

                    <!-- Cart -->
                    <a href="/cart" class="relative p-2 text-gray-600 hover:text-amber-600 hover:bg-gray-50 rounded-lg transition-colors">
                        <i data-lucide="shopping-cart" class="w-5 h-5"></i>
                        <?php
                        $cartCount = Auth::check()
                            ? Database::count('cart', 'user_id = ?', [Auth::id()])
                            : Database::count('cart', 'session_id = ?', [session_id()]);
                        if ($cartCount > 0): ?>
                            <span class="cart-badge absolute -top-0.5 -right-0.5 w-5 h-5 text-white text-[10px] font-bold rounded-full flex items-center justify-center"><?= $cartCount ?></span>
                        <?php endif; ?>
                    </a>

                    <!-- Mobile menu toggle -->
                    <button onclick="document.getElementById('mobileMenu').classList.toggle('hidden')" class="lg:hidden p-2 text-gray-600 hover:text-amber-600 hover:bg-gray-50 rounded-lg transition-colors">
                        <i data-lucide="menu" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>

            <!-- Mobile search -->
            <div id="mobileSearch" class="hidden mt-3 md:hidden">
                <form action="/search" method="GET" class="relative">
                    <input type="text" name="q" placeholder="Search products..."
                        class="w-full pl-4 pr-12 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 bg-gray-50">
                    <button type="submit" class="absolute right-1 top-1 bottom-1 px-3 bg-primary-600 text-white rounded-lg">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </button>
                </form>
            </div>
        </nav>

        <!-- Category nav (desktop) -->
        <div class="hidden lg:block border-t border-gray-50 bg-white">
            <div class="w-full px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-1">
                    <?php
                    $navCategories = Database::select("SELECT * FROM categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY sort_order ASC LIMIT 8");
                    foreach ($navCategories as $cat): ?>
                        <a href="/category/<?= e($cat['slug']) ?>" class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors whitespace-nowrap">
                            <?= e($cat['name']) ?>
                        </a>
                    <?php endforeach; ?>
                    <a href="/categories" class="px-3 py-2 text-sm font-medium text-amber-600 hover:bg-amber-50 rounded-lg transition-colors flex items-center gap-1">
                        All Categories <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                    </a>
                    <div class="w-px h-5 bg-gray-200 mx-1"></div>
                    <a href="/blog" class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors flex items-center gap-1.5 whitespace-nowrap">
                        <i data-lucide="book-open" class="w-4 h-4"></i> Blog
                    </a>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <div id="mobileMenu" class="hidden lg:hidden border-t border-gray-100 bg-white absolute left-0 right-0 shadow-xl z-50">
            <div class="w-full px-4 sm:px-6 lg:px-8 py-4 space-y-1">
                <?php foreach ($navCategories as $cat): ?>
                    <a href="/category/<?= e($cat['slug']) ?>" class="block px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-amber-50 hover:text-amber-600 rounded-lg transition-colors">
                        <?= e($cat['name']) ?>
                    </a>
                <?php endforeach; ?>
                <hr class="border-gray-100 my-2">
                <a href="/categories" class="block px-3 py-2.5 text-sm font-medium text-amber-600 hover:bg-amber-50 rounded-lg">All Categories</a>
                <a href="/blog" class="flex items-center gap-2 px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-amber-50 hover:text-amber-600 rounded-lg transition-colors">
                    <i data-lucide="book-open" class="w-4 h-4"></i> Blog
                </a>
                <?php if (Auth::check()): ?>
                    <?php if (Auth::isAdmin()): ?>
                        <a href="/admin" class="block px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-amber-50 rounded-lg">Admin Dashboard</a>
                    <?php endif; ?>
                    <a href="/account" class="block px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-amber-50 rounded-lg">My Account</a>
                    <a href="/logout" class="block px-3 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50 rounded-lg">Logout</a>
                <?php else: ?>
                    <a href="/login" class="block px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-amber-50 rounded-lg">Login</a>
                    <a href="/register" class="block px-3 py-2.5 text-sm font-medium text-amber-600 hover:bg-amber-50 rounded-lg">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1">
        <?= $content ?? '' ?>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-300 mt-auto site-footer">
        <div class="w-full px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Brand -->
                <div>
                    <a href="/" class="flex items-center gap-2 mb-4">
                        <?php if ($siteLogo): ?>
                            <img src="<?= e($siteLogo) ?>" alt="<?= e($storeName) ?>" class="w-auto object-contain" style="height:<?= $logoHeight ?>px;max-width:<?= $logoHeight * 3.5 ?>px;">
                            <span class="font-heading font-bold text-xl text-white"><?= e($storeName) ?></span>
                        <?php else: ?>
                            <div class="w-9 h-9 bg-primary-600 rounded-lg flex items-center justify-center">
                                <i data-lucide="shopping-bag" class="w-5 h-5 text-white"></i>
                            </div>
                            <span class="font-heading font-bold text-xl text-white"><?= e($storeName) ?></span>
                        <?php endif; ?>
                    </a>
                    <p class="text-sm text-gray-400 mb-4">Your AI-powered shopping destination. Smart recommendations, great prices, and fast delivery across Kenya.</p>
                    <?php if ($socialFb || $socialTw || $socialIg || $socialYt || $socialTk): ?>
                    <div class="flex gap-3">
                        <?php if ($socialFb): ?>
                        <a href="<?= e($socialFb) ?>" target="_blank" rel="noopener noreferrer" class="social-icon w-9 h-9 bg-gray-800 hover:text-white rounded-lg flex items-center justify-center transition-colors" aria-label="Facebook">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        <?php endif; ?>
                        <?php if ($socialTw): ?>
                        <a href="<?= e($socialTw) ?>" target="_blank" rel="noopener noreferrer" class="social-icon w-9 h-9 bg-gray-800 hover:text-white rounded-lg flex items-center justify-center transition-colors" aria-label="Twitter">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        </a>
                        <?php endif; ?>
                        <?php if ($socialIg): ?>
                        <a href="<?= e($socialIg) ?>" target="_blank" rel="noopener noreferrer" class="social-icon w-9 h-9 bg-gray-800 hover:text-white rounded-lg flex items-center justify-center transition-colors" aria-label="Instagram">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                        </a>
                        <?php endif; ?>
                        <?php if ($socialYt): ?>
                        <a href="<?= e($socialYt) ?>" target="_blank" rel="noopener noreferrer" class="social-icon w-9 h-9 bg-gray-800 hover:text-white rounded-lg flex items-center justify-center transition-colors" aria-label="YouTube">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                        </a>
                        <?php endif; ?>
                        <?php if ($socialTk): ?>
                        <a href="<?= e($socialTk) ?>" target="_blank" rel="noopener noreferrer" class="social-icon w-9 h-9 bg-gray-800 hover:text-white rounded-lg flex items-center justify-center transition-colors" aria-label="TikTok">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="font-heading font-semibold text-white mb-4">Quick Links</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="/" class="hover:text-amber-400 transition-colors">Home</a></li>
                        <li><a href="/products" class="hover:text-amber-400 transition-colors">All Products</a></li>
                        <li><a href="/categories" class="hover:text-amber-400 transition-colors">Categories</a></li>
                        <li><a href="/account" class="hover:text-amber-400 transition-colors">My Account</a></li>
                        <li><a href="/cart" class="hover:text-amber-400 transition-colors">Shopping Cart</a></li>
                        <li><a href="/blog" class="hover:text-amber-400 transition-colors">Blog</a></li>
                    </ul>
                </div>

                <!-- Customer Service -->
                <div>
                    <h4 class="font-heading font-semibold text-white mb-4">Customer Service</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="/page/contact-us" class="hover:text-amber-400 transition-colors">Contact Us</a></li>
                        <li><a href="/page/shipping-policy" class="hover:text-amber-400 transition-colors">Shipping Policy</a></li>
                        <li><a href="/page/returns-refunds" class="hover:text-amber-400 transition-colors">Returns & Refunds</a></li>
                        <li><a href="/page/faq" class="hover:text-amber-400 transition-colors">FAQ</a></li>
                        <li><a href="/page/privacy-policy" class="hover:text-amber-400 transition-colors">Privacy Policy</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h4 class="font-heading font-semibold text-white mb-4">Contact Us</h4>
                    <ul class="space-y-3 text-sm">
                        <li class="flex items-start gap-2"><i data-lucide="map-pin" class="w-4 h-4 mt-0.5 text-amber-400 shrink-0"></i> <?= e($storeAddress) ?></li>
                        <li class="flex items-center gap-2"><i data-lucide="phone" class="w-4 h-4 text-amber-400 shrink-0"></i> <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $storePhone)) ?>" class="hover:text-amber-400 transition-colors"><?= e($storePhone) ?></a></li>
                        <li class="flex items-center gap-2"><i data-lucide="mail" class="w-4 h-4 text-amber-400 shrink-0"></i> <a href="mailto:<?= e($storeEmail) ?>" class="hover:text-amber-400 transition-colors"><?= e($storeEmail) ?></a></li>
                        <li class="flex items-center gap-2"><i data-lucide="clock" class="w-4 h-4 text-amber-400 shrink-0"></i> Mon - Sat: 8AM - 8PM</li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 mt-8 pt-8 flex flex-col sm:flex-row justify-between items-center gap-4">
                <p class="text-sm text-gray-500">&copy; <?= date('Y') ?> <?= e($storeName) ?>. All rights reserved.</p>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-gray-500">Powered by</span>
                    <span class="text-xs font-medium text-amber-400">AI Marketing Engine</span>
                    <i data-lucide="sparkles" class="w-3.5 h-3.5 text-amber-400"></i>
                </div>
            </div>
        </div>
    </footer>

    <script>
        lucide.createIcons();

        // Auto-close flash messages
        setTimeout(() => {
            document.querySelectorAll('.fixed.top-4.right-4.animate-fade-in').forEach(el => {
                el.style.transition = 'opacity 0.3s';
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 300);
            });
        }, 4000);
    </script>
</body>
</html>