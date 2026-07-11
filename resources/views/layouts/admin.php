<?php
// Load key settings for admin layout
$adminSettings = [];
try {
    foreach (Database::select("SELECT * FROM settings") as $s) {
        $adminSettings[$s['key']] = $s['value'];
    }
} catch (\Throwable $e) {}
$adminLogo = !empty($adminSettings['site_logo']) ? $adminSettings['site_logo'] : '';
$adminFavicon = !empty($adminSettings['site_favicon']) ? $adminSettings['site_favicon'] : '';
$adminStoreName = $adminSettings['store_name'] ?? 'ShopSmart';
$adminSidebarColor = $adminSettings['login_bg_color'] ?? '#111827';

// Manager menu permissions: determine which restricted menus a non-super_admin user can see
$currentUser = Auth::user();
$currentUserRole = $currentUser['role'] ?? '';
$restrictedMenus = ['pages','settings','api-integrations','commissions','shipping','social-media','seo','sitemap','cities','payments','marketing'];
$grantedMenus = [];
if ($currentUserRole !== 'super_admin') {
    $perms = json_decode($currentUser['menu_permissions'] ?? '[]', true) ?: [];
    $grantedMenus = $perms;
}
function canSeeMenu($menuSlug) {
    global $currentUserRole, $grantedMenus;
    if ($currentUserRole === 'super_admin') return true;
    if ($currentUserRole === 'cashier') return false;
    // admin/manager role - check if granted
    return in_array($menuSlug, $grantedMenus);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Admin Dashboard - ' . $adminStoreName) ?></title>
    <?php if ($adminFavicon): ?>
        <link rel="icon" type="image/x-icon" href="<?= e($adminFavicon) ?>">
    <?php else: ?>
        <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans:['Inter','system-ui','sans-serif'], heading:['Poppins','sans-serif'] },
                    colors: { primary:{50:'#fffbeb',100:'#fef3c7',200:'#fde68a',300:'#fcd34d',400:'#fbbf24',500:'#f59e0b',600:'#d97706',700:'#b45309',800:'#92400e',900:'#78350f'} }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <style>
        body{font-family:'Inter',system-ui,sans-serif}
        h1,h2,h3,h4,h5,h6,.font-heading{font-family:'Poppins',sans-serif}
        .sidebar-link{transition:all .15s ease}
        .sidebar-link:hover,.sidebar-link.active{background:rgba(245,158,11,0.15);color:#fbbf24}
        .sidebar-link.active{border-right:3px solid #f59e0b}
        .scrollbar-thin::-webkit-scrollbar{width:5px}
        .scrollbar-thin::-webkit-scrollbar-track{background:rgba(0,0,0,0.2)}
        .scrollbar-thin::-webkit-scrollbar-thumb{background:rgba(255,255,255,0.2);border-radius:3px}
        .submenu{max-height:0;overflow:hidden;transition:max-height .2s ease}
        .submenu.open{max-height:300px}
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex">
    <!-- Mobile overlay -->
    <div id="sidebarOverlay" class="hidden fixed inset-0 bg-black/50 z-40 lg:hidden" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed lg:static inset-y-0 left-0 z-50 w-64 text-gray-300 transform -translate-x-full lg:translate-x-0 transition-transform duration-200 flex flex-col" style="background-color:<?= e($adminSidebarColor) ?>">
        <!-- Logo -->
        <div class="flex items-center gap-2 px-5 py-4 border-b border-white/10">
            <?php if ($adminLogo): ?>
                <img src="<?= e($adminLogo) ?>" alt="<?= e($adminStoreName) ?>" class="h-8 w-auto max-w-[120px] object-contain brightness-0 invert">
            <?php else: ?>
                <div class="w-8 h-8 bg-amber-600 rounded-lg flex items-center justify-center">
                    <i data-lucide="shopping-bag" class="w-4 h-4 text-white"></i>
                </div>
            <?php endif; ?>
            <span class="font-heading font-bold text-white truncate"><?= e($adminStoreName) ?></span>
            <span class="text-[10px] bg-amber-600/20 text-amber-400 px-1.5 py-0.5 rounded-full ml-auto shrink-0">Admin</span>
        </div>

        <!-- Nav -->
        <nav class="flex-1 overflow-y-auto scrollbar-thin py-4 px-3 space-y-1">
            <a href="/admin" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?= isActive('/admin') && !str_contains(Request::uri(), '/admin/') ? 'active text-amber-400' : 'hover:text-white' ?>">
                <i data-lucide="layout-dashboard" class="w-4.5 h-4.5"></i> Dashboard
            </a>

            <!-- Frontend Content (NEW) -->
            <div>
                <button onclick="this.nextElementSibling.classList.toggle('open');this.querySelector('.chevron').classList.toggle('rotate-90')" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm hover:text-white hover:bg-white/5 transition-colors">
                    <i data-lucide="palette" class="w-4.5 h-4.5"></i> Frontend Content
                    <i data-lucide="chevron-right" class="w-3.5 h-3.5 ml-auto chevron transition-transform"></i>
                </button>
                <div class="submenu <?= str_contains(Request::uri(), '/admin/hero-slides') || str_contains(Request::uri(), '/admin/promo-banners') || str_contains(Request::uri(), '/admin/trust-badges') || str_contains(Request::uri(), '/admin/appearance') || str_contains(Request::uri(), '/admin/testimonials') ? 'open' : '' ?>">
                    <a href="/admin/hero-slides" class="sidebar-link flex items-center gap-3 pl-11 pr-3 py-2 rounded-lg text-sm <?= str_contains(Request::uri(), '/admin/hero-slides') ? 'active text-amber-400' : '' ?>">
                        <i data-lucide="image" class="w-4 h-4"></i> Hero Slides
                    </a>
                    <a href="/admin/promo-banners" class="sidebar-link flex items-center gap-3 pl-11 pr-3 py-2 rounded-lg text-sm <?= str_contains(Request::uri(), '/admin/promo-banners') ? 'active text-amber-400' : '' ?>">
                        <i data-lucide="megaphone" class="w-4 h-4"></i> Promo Banners
                    </a>
                    <a href="/admin/trust-badges" class="sidebar-link flex items-center gap-3 pl-11 pr-3 py-2 rounded-lg text-sm <?= str_contains(Request::uri(), '/admin/trust-badges') ? 'active text-amber-400' : '' ?>">
                        <i data-lucide="shield-check" class="w-4 h-4"></i> Trust Badges
                    </a>
                    <a href="/admin/testimonials" class="sidebar-link flex items-center gap-3 pl-11 pr-3 py-2 rounded-lg text-sm <?= str_contains(Request::uri(), '/admin/testimonials') ? 'active text-amber-400' : '' ?>">
                        <i data-lucide="message-square-quote" class="w-4 h-4"></i> Testimonials
                    </a>
                    <?php if (canSeeMenu('pages')): ?>
                    <a href="/admin/pages" class="sidebar-link flex items-center gap-3 pl-11 pr-3 py-2 rounded-lg text-sm <?= str_contains(Request::uri(), '/admin/pages') ? 'active text-amber-400' : '' ?>">
                        <i data-lucide="file-text" class="w-4 h-4"></i> CMS Pages
                    </a>
                    <?php endif; ?>
                    <a href="/admin/appearance" class="sidebar-link flex items-center gap-3 pl-11 pr-3 py-2 rounded-lg text-sm <?= str_contains(Request::uri(), '/admin/appearance') ? 'active text-amber-400' : '' ?>">
                        <i data-lucide="settings-2" class="w-4 h-4"></i> Appearance
                    </a>
                </div>
            </div>

            <!-- Products -->
            <div>
                <button onclick="this.nextElementSibling.classList.toggle('open');this.querySelector('.chevron').classList.toggle('rotate-90')" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm hover:text-white hover:bg-white/5 transition-colors">
                    <i data-lucide="package" class="w-4.5 h-4.5"></i> Products
                    <i data-lucide="chevron-right" class="w-3.5 h-3.5 ml-auto chevron transition-transform"></i>
                </button>
                <div class="submenu <?= str_contains(Request::uri(), '/admin/products') || str_contains(Request::uri(), '/admin/categories') || str_contains(Request::uri(), '/admin/brands') || str_contains(Request::uri(), '/admin/inventory') ? 'open' : '' ?>">
                    <a href="/admin/products/create" class="sidebar-link flex items-center gap-3 pl-11 pr-3 py-2 rounded-lg text-sm <?= isActive('/admin/products/create') ? 'active text-amber-400' : '' ?>">
                        <i data-lucide="plus-circle" class="w-4 h-4"></i> Add Product
                    </a>
                    <a href="/admin/products" class="sidebar-link flex items-center gap-3 pl-11 pr-3 py-2 rounded-lg text-sm <?= isActive('/admin/products') && !str_contains(Request::uri(), 'create') ? 'active text-amber-400' : '' ?>">
                        <i data-lucide="list" class="w-4 h-4"></i> All Products
                    </a>
                    <a href="/admin/categories" class="sidebar-link flex items-center gap-3 pl-11 pr-3 py-2 rounded-lg text-sm <?= isActive('/admin/categories') ? 'active text-amber-400' : '' ?>">
                        <i data-lucide="grid-3x3" class="w-4 h-4"></i> Categories
                    </a>
                    <a href="/admin/brands" class="sidebar-link flex items-center gap-3 pl-11 pr-3 py-2 rounded-lg text-sm <?= isActive('/admin/brands') ? 'active text-amber-400' : '' ?>">
                        <i data-lucide="tag" class="w-4 h-4"></i> Brands
                    </a>
                    <a href="/admin/inventory" class="sidebar-link flex items-center gap-3 pl-11 pr-3 py-2 rounded-lg text-sm <?= isActive('/admin/inventory') ? 'active text-amber-400' : '' ?>">
                        <i data-lucide="warehouse" class="w-4 h-4"></i> Inventory
                    </a>
                </div>
            </div>

            <!-- Orders -->
            <div>
                <button onclick="this.nextElementSibling.classList.toggle('open');this.querySelector('.chevron').classList.toggle('rotate-90')" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm hover:text-white hover:bg-white/5 transition-colors">
                    <i data-lucide="shopping-bag" class="w-4.5 h-4.5"></i> Orders
                    <i data-lucide="chevron-right" class="w-3.5 h-3.5 ml-auto chevron transition-transform"></i>
                </button>
                <div class="submenu <?= str_contains(Request::uri(), '/admin/orders') || str_contains(Request::uri(), '/admin/pos-sales') ? 'open' : '' ?>">
                    <a href="/admin/orders" class="sidebar-link flex items-center gap-3 pl-11 pr-3 py-2 rounded-lg text-sm <?= isActive('/admin/orders') ? 'active text-amber-400' : '' ?>">
                        <i data-lucide="globe" class="w-4 h-4"></i> Online Orders
                    </a>
                    <a href="/admin/pos-sales" class="sidebar-link flex items-center gap-3 pl-11 pr-3 py-2 rounded-lg text-sm <?= isActive('/admin/pos-sales') ? 'active text-amber-400' : '' ?>">
                        <i data-lucide="monitor" class="w-4 h-4"></i> POS Sales
                    </a>
                </div>
            </div>

            <a href="/admin/customers" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?= isActive('/admin/customers') ? 'active text-amber-400' : 'hover:text-white' ?>">
                <i data-lucide="users" class="w-4.5 h-4.5"></i> Customers
            </a>

            <a href="/pos" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?= isActive('/pos') ? 'active text-amber-400' : 'hover:text-white' ?>">
                <i data-lucide="monitor" class="w-4.5 h-4.5"></i> POS Terminal
            </a>

            <?php if (canSeeMenu('marketing')): ?>
            <!-- Marketing -->
            <div>
                <button onclick="this.nextElementSibling.classList.toggle('open');this.querySelector('.chevron').classList.toggle('rotate-90')" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm hover:text-white hover:bg-white/5 transition-colors">
                    <i data-lucide="megaphone" class="w-4.5 h-4.5"></i> Marketing
                    <i data-lucide="chevron-right" class="w-3.5 h-3.5 ml-auto chevron transition-transform"></i>
                </button>
                <div class="submenu <?= str_contains(Request::uri(), '/admin/marketing') || str_contains(Request::uri(), '/admin/newsletter') ? 'open' : '' ?>">
                    <a href="/admin/newsletter/subscribers" class="sidebar-link flex items-center gap-3 pl-11 pr-3 py-2 rounded-lg text-sm <?= str_contains(Request::uri(), '/admin/newsletter/subscribers') ? 'active text-amber-400' : '' ?>">
                        <i data-lucide="users" class="w-4 h-4"></i> Subscribers
                    </a>
                    <a href="/admin/newsletter/compose" class="sidebar-link flex items-center gap-3 pl-11 pr-3 py-2 rounded-lg text-sm <?= str_contains(Request::uri(), '/admin/newsletter/compose') || str_contains(Request::uri(), '/admin/newsletter/send') ? 'active text-amber-400' : '' ?>">
                        <i data-lucide="send" class="w-4 h-4"></i> Compose Email
                    </a>
                    <a href="/admin/newsletter/settings" class="sidebar-link flex items-center gap-3 pl-11 pr-3 py-2 rounded-lg text-sm <?= str_contains(Request::uri(), '/admin/newsletter/settings') ? 'active text-amber-400' : '' ?>">
                        <i data-lucide="settings" class="w-4 h-4"></i> Mail Settings
                    </a>
                    <a href="/admin/marketing/social" class="sidebar-link flex items-center gap-3 pl-11 pr-3 py-2 rounded-lg text-sm <?= isActive('/admin/marketing/social') ? 'active text-amber-400' : '' ?>">
                        <i data-lucide="share-2" class="w-4 h-4"></i> Social Publishing
                    </a>
                    <a href="/admin/marketing/product-publish" class="sidebar-link flex items-center gap-3 pl-11 pr-3 py-2 rounded-lg text-sm <?= isActive('/admin/marketing/product-publish') ? 'active text-amber-400' : '' ?>">
                        <i data-lucide="package" class="w-4 h-4"></i> Product Publishing
                    </a>
                    <a href="/admin/marketing/whatsapp" class="sidebar-link flex items-center gap-3 pl-11 pr-3 py-2 rounded-lg text-sm <?= isActive('/admin/marketing/whatsapp') ? 'active text-amber-400' : '' ?>">
                        <i data-lucide="message-circle" class="w-4 h-4"></i> WhatsApp
                    </a>
                    <a href="/admin/marketing/email" class="sidebar-link flex items-center gap-3 pl-11 pr-3 py-2 rounded-lg text-sm <?= isActive('/admin/marketing/email') ? 'active text-amber-400' : '' ?>">
                        <i data-lucide="mail" class="w-4 h-4"></i> Email Campaigns
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Coupons -->
            <a href="/admin/coupons" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?= isActive('/admin/coupons') ? 'active text-amber-400' : 'hover:text-white' ?>">
                <i data-lucide="ticket" class="w-4.5 h-4.5"></i> Coupons
            </a>

            <?php if (canSeeMenu('payments')): ?>
            <!-- Payments -->
            <div>
                <button onclick="this.nextElementSibling.classList.toggle('open');this.querySelector('.chevron').classList.toggle('rotate-90')" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm hover:text-white hover:bg-white/5 transition-colors">
                    <i data-lucide="credit-card" class="w-4.5 h-4.5"></i> Payments
                    <i data-lucide="chevron-right" class="w-3.5 h-3.5 ml-auto chevron transition-transform"></i>
                </button>
                <div class="submenu <?= str_contains(Request::uri(), '/admin/payments') ? 'open' : '' ?>">
                    <a href="/admin/payments" class="sidebar-link flex items-center gap-3 pl-11 pr-3 py-2 rounded-lg text-sm <?= isActive('/admin/payments') && !str_contains(Request::uri(), 'settings') ? 'active text-amber-400' : '' ?>">
                        <i data-lucide="wallet" class="w-4 h-4"></i> Overview
                    </a>
                    <a href="/admin/payments/settings" class="sidebar-link flex items-center gap-3 pl-11 pr-3 py-2 rounded-lg text-sm <?= str_contains(Request::uri(), 'payments/settings') ? 'active text-amber-400' : '' ?>">
                        <i data-lucide="settings" class="w-4 h-4"></i> Settings
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <a href="/admin/reports" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?= isActive('/admin/reports') ? 'active text-amber-400' : 'hover:text-white' ?>">
                <i data-lucide="bar-chart-3" class="w-4.5 h-4.5"></i> Reports
            </a>

            <a href="/admin/analytics" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?= isActive('/admin/analytics') ? 'active text-amber-400' : 'hover:text-white' ?>">
                <i data-lucide="bar-chart-2" class="w-4.5 h-4.5"></i> Analytics
            </a>

            <a href="/admin/users" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?= isActive('/admin/users') ? 'active text-amber-400' : 'hover:text-white' ?>">
                <i data-lucide="shield" class="w-4.5 h-4.5"></i> Users & Roles
            </a>

            <?php if (canSeeMenu('settings')): ?>
            <a href="/admin/settings" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?= isActive('/admin/settings') && !str_contains(Request::uri(), '/admin/settings/cities') ? 'active text-amber-400' : 'hover:text-white' ?>">
                <i data-lucide="settings" class="w-4.5 h-4.5"></i> Settings
            </a>
            <?php endif; ?>
            <?php if (canSeeMenu('cities')): ?>
            <a href="/admin/settings/cities" class="sidebar-link flex items-center gap-3 pl-11 pr-3 py-2 rounded-lg text-sm <?= str_contains(Request::uri(), '/admin/settings/cities') ? 'active text-amber-400' : 'hover:text-white' ?>">
                <i data-lucide="map-pin" class="w-4 h-4"></i> Cities
            </a>
            <?php endif; ?>
            <a href="/admin/settings/make-logs" class="sidebar-link flex items-center gap-3 pl-11 pr-3 py-2 rounded-lg text-sm <?= str_contains(Request::uri(), '/admin/settings/make-logs') ? 'active text-amber-400' : 'hover:text-white' ?>">
                <i data-lucide="workflow" class="w-4 h-4"></i> Make.com Logs
            </a>
            <?php if (canSeeMenu('seo')): ?>
            <a href="/admin/seo" class="sidebar-link flex items-center gap-3 pl-11 pr-3 py-2 rounded-lg text-sm <?= str_contains(Request::uri(), '/admin/seo') ? 'active text-amber-400' : 'hover:text-white' ?>">
                <i data-lucide="search" class="w-4 h-4"></i> SEO & Meta
            </a>
            <?php endif; ?>
            <?php if (canSeeMenu('sitemap')): ?>
            <a href="/admin/sitemap" class="sidebar-link flex items-center gap-3 pl-11 pr-3 py-2 rounded-lg text-sm <?= str_contains(Request::uri(), '/admin/sitemap') ? 'active text-amber-400' : 'hover:text-white' ?>">
                <i data-lucide="file-code" class="w-4 h-4"></i> Sitemap
            </a>
            <?php endif; ?>

            <a href="/admin/blogs" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?= isActive('/admin/blogs') ? 'active text-amber-400' : 'hover:text-white' ?>">
                <i data-lucide="pen-line" class="w-4.5 h-4.5"></i> Blog
            </a>

            <?php if (canSeeMenu('shipping')): ?>
            <a href="/admin/shipping" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?= isActive('/admin/shipping') ? 'active text-amber-400' : 'hover:text-white' ?>">
                <i data-lucide="truck" class="w-4.5 h-4.5"></i> Shipping Fees
            </a>
            <?php endif; ?>

            <?php if (canSeeMenu('social-media')): ?>
            <a href="/admin/social-media" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?= isActive('/admin/social-media') ? 'active text-amber-400' : 'hover:text-white' ?>">
                <i data-lucide="share-2" class="w-4.5 h-4.5"></i> Social Media
            </a>
            <?php endif; ?>

            <?php if (canSeeMenu('api-integrations')): ?>
            <a href="/admin/api-integrations" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?= isActive('/admin/api-integrations') ? 'active text-amber-400' : 'hover:text-white' ?>">
                <i data-lucide="plug" class="w-4.5 h-4.5"></i> API Integrations
            </a>
            <?php endif; ?>

            <?php if (canSeeMenu('commissions')): ?>
            <a href="/admin/commissions" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?= isActive('/admin/commissions') ? 'active text-amber-400' : 'hover:text-white' ?>">
                <i data-lucide="coins" class="w-4.5 h-4.5"></i> Commissions
            </a>
            <?php endif; ?>

            <a href="/admin/referrals" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?= isActive('/admin/referrals') ? 'active text-amber-400' : 'hover:text-white' ?>">
                <i data-lucide="users-round" class="w-4.5 h-4.5"></i> Affiliates
            </a>
            <a href="/admin/referrals/withdrawals" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm pl-9 <?= isActive('/admin/referrals/withdrawals') ? 'active text-amber-400' : 'hover:text-white' ?>">
                <i data-lucide="wallet" class="w-4 h-4"></i> Withdrawals
            </a>
        </nav>

        <!-- User -->
        <div class="border-t border-white/10 px-4 py-3">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-amber-600 rounded-full flex items-center justify-center text-white text-sm font-bold">
                    <?= strtoupper(substr(Auth::user()['name'] ?? 'A', 0, 1)) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate"><?= e(Auth::user()['name'] ?? '') ?></p>
                    <p class="text-xs text-gray-500 capitalize"><?= e(Auth::user()['role'] ?? '') ?></p>
                </div>
                <a href="/logout" class="text-gray-500 hover:text-red-400 transition-colors" title="Logout">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Top bar -->
        <header class="bg-white border-b border-gray-200 px-4 lg:px-6 py-3 flex items-center justify-between sticky top-0 z-30">
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" class="lg:hidden p-2 text-gray-600 hover:bg-gray-100 rounded-lg">
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>
                <?php if (!empty($breadcrumbs)): ?>
                <nav class="hidden sm:flex items-center gap-1 text-sm text-gray-500">
                    <a href="/admin" class="hover:text-amber-600">Dashboard</a>
                    <?php foreach ($breadcrumbs ?? [] as $i => $crumb): ?>
                        <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                        <?php if ($i === count($breadcrumbs) - 1): ?>
                            <span class="text-gray-900 font-medium"><?= e($crumb[0]) ?></span>
                        <?php else: ?>
                            <a href="<?= e($crumb[1]) ?>" class="hover:text-amber-600"><?= e($crumb[0]) ?></a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </nav>
                <?php endif; ?>
            </div>

            <div class="flex items-center gap-2">
                <!-- Search -->
                <div class="hidden md:block relative">
                    <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" placeholder="Search..." class="pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg w-64 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent bg-gray-50">
                </div>

                <!-- Notifications -->
                <button class="relative p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                    <i data-lucide="bell" class="w-5 h-5"></i>
                    <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                </button>

                <!-- Store link -->
                <a href="/" target="_blank" class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors" title="View Store">
                    <i data-lucide="external-link" class="w-5 h-5"></i>
                </a>
            </div>
        </header>

        <!-- Flash Messages -->
        <?php if ($error = Session::flash('error')): ?>
        <div class="mx-4 lg:mx-6 mt-4 animate-[fadeIn_0.3s_ease-out]">
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center gap-2 text-sm">
                <i data-lucide="alert-circle" class="w-5 h-5 shrink-0"></i>
                <?= e($error) ?>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($success = Session::flash('success')): ?>
        <div class="mx-4 lg:mx-6 mt-4 animate-[fadeIn_0.3s_ease-out]">
            <div class="bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 rounded-lg flex items-center gap-2 text-sm">
                <i data-lucide="check-circle" class="w-5 h-5 shrink-0"></i>
                <?= e($success) ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Page Content -->
        <main class="flex-1 p-4 lg:p-6">
            <?= $content ?? '' ?>
        </main>

        <!-- Footer -->
        <footer class="border-t border-gray-200 bg-white px-6 py-3 text-center text-xs text-gray-500">
            &copy; <?= date('Y') ?> <?= e($adminStoreName) ?> Admin Panel. All rights reserved.
        </footer>
    </div>

    <script>
        lucide.createIcons();
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }
    </script>
</body>
</html>