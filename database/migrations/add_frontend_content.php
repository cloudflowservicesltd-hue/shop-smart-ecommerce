<?php
// Migration: Add hero_slides, promo_banners, trust_badges tables and seed data
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(dirname(__DIR__)));
if (!class_exists('Database')) require_once ROOT_PATH . '/app/Core/Database.php';

// Idempotency: skip if already migrated
try {
    $db = Database::getConnection();
    $db->exec("CREATE TABLE IF NOT EXISTS `migrations` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `name` VARCHAR(255) NOT NULL UNIQUE, `ran_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $ran = Database::selectOne("SELECT id FROM migrations WHERE name = ?", ['add_frontend_content']);
    if ($ran) { echo "Already migrated: add_frontend_content\n"; return; }
} catch (\Throwable $e) {}

$db = Database::getConnection();

$statements = [
    // Hero Slides
    "CREATE TABLE IF NOT EXISTS `hero_slides` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `title` TEXT NOT NULL,
        `subtitle` TEXT,
        `description` TEXT,
        `cta_text` TEXT DEFAULT 'Shop Now',
        `cta_link` TEXT DEFAULT '/products',
        `image_url` TEXT,
        `bg_gradient` TEXT DEFAULT 'from-amber-600 to-orange-700',
        `text_position` TEXT DEFAULT 'left',
        `overlay` TEXT DEFAULT 'dark',
        `sort_order` INT DEFAULT 0,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TEXT,
        `updated_at` TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Promo Banners
    "CREATE TABLE IF NOT EXISTS `promo_banners` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `title` TEXT NOT NULL,
        `subtitle` TEXT,
        `cta_text` TEXT DEFAULT 'Shop Now',
        `cta_link` TEXT DEFAULT '/products',
        `bg_gradient` TEXT DEFAULT 'from-amber-500 to-orange-600',
        `icon` TEXT,
        `position` INT DEFAULT 0,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TEXT,
        `updated_at` TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Trust Badges
    "CREATE TABLE IF NOT EXISTS `trust_badges` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `title` TEXT NOT NULL,
        `subtitle` TEXT,
        `icon_name` TEXT DEFAULT 'truck',
        `sort_order` INT DEFAULT 0,
        `is_active` TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Newsletter Subscribers
    "CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `email` TEXT NOT NULL UNIQUE,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];

foreach ($statements as $sql) {
    $db->exec($sql);
}

// Seed hero slides — only insert if table is empty (prevents duplicates)
$slideCount = Database::selectOne("SELECT COUNT(*) as cnt FROM hero_slides")['cnt'] ?? 0;
if ($slideCount == 0) {
    $slides = [
        [
            'title' => 'Discover Premium Electronics',
            'subtitle' => 'Latest Gadgets & Devices',
            'description' => 'Explore cutting-edge smartphones, laptops, and accessories from top brands at unbeatable prices.',
            'cta_text' => 'Shop Electronics',
            'cta_link' => '/products?category=electronics',
            'image_url' => '/uploads/categories/electronics.jpg',
            'bg_gradient' => 'from-amber-700 via-orange-800 to-red-900',
            'text_position' => 'left',
            'overlay' => 'dark',
            'sort_order' => 1,
            'is_active' => 1,
        ],
        [
            'title' => 'Fresh Fashion Arrivals',
            'subtitle' => 'Nike, Adidas & More',
            'description' => 'Step into style with the latest footwear and apparel from world-renowned brands.',
            'cta_text' => 'Shop Clothing',
            'cta_link' => '/products?category=clothing',
            'image_url' => '/uploads/categories/clothing.jpg',
            'bg_gradient' => 'from-slate-800 via-slate-900 to-gray-950',
            'text_position' => 'center',
            'overlay' => 'dark',
            'sort_order' => 2,
            'is_active' => 1,
        ],
        [
            'title' => 'Home & Kitchen Essentials',
            'subtitle' => 'Transform Your Space',
            'description' => 'Upgrade your home with premium appliances, decor, and kitchen essentials.',
            'cta_text' => 'Explore Now',
            'cta_link' => '/products?category=home-kitchen',
            'image_url' => '/uploads/categories/home-kitchen.jpg',
            'bg_gradient' => 'from-teal-700 via-emerald-800 to-green-900',
            'text_position' => 'right',
            'overlay' => 'dark',
            'sort_order' => 3,
            'is_active' => 1,
        ],
        [
            'title' => 'Up to 50% Off This Weekend',
            'subtitle' => 'Mega Flash Sale',
            'description' => 'Limited-time deals on thousands of products. Grab them before they are gone!',
            'cta_text' => 'View Deals',
            'cta_link' => '/products?sale=1',
            'image_url' => '/uploads/products/samsung-galaxy-s24-ultra.jpg',
            'bg_gradient' => 'from-rose-700 via-pink-800 to-fuchsia-900',
            'text_position' => 'left',
            'overlay' => 'dark',
            'sort_order' => 4,
            'is_active' => 1,
        ],
    ];

    foreach ($slides as $slide) {
        $slide['created_at'] = date('Y-m-d H:i:s');
        $slide['updated_at'] = date('Y-m-d H:i:s');
        Database::insert('hero_slides', $slide);
    }
}

// Seed promo banners — only insert if table is empty
$bannerCount = Database::selectOne("SELECT COUNT(*) as cnt FROM promo_banners")['cnt'] ?? 0;
if ($bannerCount == 0) {
    $banners = [
        [
            'title' => 'Flash Sale',
            'subtitle' => 'Up to 50% off on selected electronics. Don\'t miss out!',
            'cta_text' => 'Shop Sale',
            'cta_link' => '/products?sale=1',
            'bg_gradient' => 'from-orange-500 to-red-600',
            'icon' => 'zap',
            'position' => 1,
            'is_active' => 1,
        ],
        [
            'title' => 'New Arrivals',
            'subtitle' => 'Check out the latest additions to our collection.',
            'cta_text' => 'Explore Now',
            'cta_link' => '/products?sort=newest',
            'bg_gradient' => 'from-amber-600 to-yellow-700',
            'icon' => 'sparkles',
            'position' => 2,
            'is_active' => 1,
        ],
    ];

    foreach ($banners as $banner) {
        $banner['created_at'] = date('Y-m-d H:i:s');
        $banner['updated_at'] = date('Y-m-d H:i:s');
        Database::insert('promo_banners', $banner);
    }
}

// Seed trust badges — only insert if table is empty
$badgeCount = Database::selectOne("SELECT COUNT(*) as cnt FROM trust_badges")['cnt'] ?? 0;
if ($badgeCount == 0) {
    $badges = [
        ['title' => 'Free Shipping', 'subtitle' => 'On orders over KSh 5,000', 'icon_name' => 'truck', 'sort_order' => 1, 'is_active' => 1],
        ['title' => 'Secure Payment', 'subtitle' => 'M-Pesa, Card & more', 'icon_name' => 'shield-check', 'sort_order' => 2, 'is_active' => 1],
        ['title' => '24/7 Support', 'subtitle' => 'We\'re here to help', 'icon_name' => 'headphones', 'sort_order' => 3, 'is_active' => 1],
        ['title' => 'Easy Returns', 'subtitle' => '30-day return policy', 'icon_name' => 'refresh-cw', 'sort_order' => 4, 'is_active' => 1],
    ];

    foreach ($badges as $badge) {
        Database::insert('trust_badges', $badge);
    }
}

// Add appearance settings (already idempotent — checks per key)
$appearanceSettings = [
    ['hero_autoplay', '1', 'appearance'],
    ['hero_interval', '5000', 'appearance'],
    ['hero_animation', 'slide', 'appearance'],
    ['show_categories', '1', 'appearance'],
    ['show_featured', '1', 'appearance'],
    ['show_new_arrivals', '1', 'appearance'],
    ['show_promo_banners', '1', 'appearance'],
    ['show_trust_badges', '1', 'appearance'],
    ['show_newsletter', '1', 'appearance'],
    ['newsletter_heading', 'Stay in the Loop', 'appearance'],
    ['newsletter_subheading', 'Subscribe to our newsletter for exclusive deals and new arrivals.', 'appearance'],
];

foreach ($appearanceSettings as $s) {
    $exists = Database::selectOne("SELECT id FROM settings WHERE `key` = ?", [$s[0]]);
    if (!$exists) {
        Database::insert('settings', [
            'key' => $s[0],
            'value' => $s[1],
            'group_name' => $s[2],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}

// Register migration
try {
    Database::insert('migrations', ['name' => 'add_frontend_content', 'ran_at' => date('Y-m-d H:i:s')]);
} catch (\Throwable $e) {}

echo "Migration add_frontend_content completed.\n";