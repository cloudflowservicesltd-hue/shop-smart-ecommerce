<?php
// Migration: Add pages table for CMS content (shipping policy, returns, FAQ, privacy, contact)
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(dirname(__DIR__)));
if (!class_exists('Database')) require_once ROOT_PATH . '/app/Core/Database.php';

// Idempotency: skip if already migrated
try {
    $db = Database::getConnection();
    $db->exec("CREATE TABLE IF NOT EXISTS `migrations` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `name` VARCHAR(255) NOT NULL UNIQUE, `ran_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $ran = Database::selectOne("SELECT id FROM migrations WHERE name = ?", ['add_cms_pages']);
    if ($ran) { echo "Already migrated: add_cms_pages\n"; return; }
} catch (\Throwable $e) {}

$db = Database::getConnection();

$statements = [
    // CMS Pages table
    "CREATE TABLE IF NOT EXISTS `pages` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `slug` TEXT NOT NULL UNIQUE,
        `title` TEXT NOT NULL,
        `content` TEXT NOT NULL DEFAULT '',
        `meta_title` TEXT,
        `meta_description` TEXT,
        `sort_order` INT DEFAULT 0,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TEXT,
        `updated_at` TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Shipping Zones table
    "CREATE TABLE IF NOT EXISTS `shipping_zones` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `name` TEXT NOT NULL,
        `region` TEXT,
        `base_fee` DOUBLE DEFAULT 0,
        `free_above` DOUBLE DEFAULT 0,
        `is_active` TINYINT(1) DEFAULT 1,
        `sort_order` INT DEFAULT 0,
        `created_at` TEXT,
        `updated_at` TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];

foreach ($statements as $sql) {
    $db->exec($sql);
}

// Seed static pages (already idempotent — checks per slug)
$pages = [
    ['slug' => 'contact-us', 'title' => 'Contact Us', 'content' => '<h2>Get in Touch</h2><p>We\'d love to hear from you. Reach out through any of the channels below.</p><h3>Address</h3><p>Kenyatta Avenue, Nairobi CBD, Kenya</p><h3>Phone</h3><p>+254 700 000 000</p><h3>Email</h3><p>info@shopsmart.co.ke</p><h3>Business Hours</h3><p>Monday - Saturday: 8:00 AM - 8:00 PM<br>Sunday: Closed</p>'],
    ['slug' => 'shipping-policy', 'title' => 'Shipping Policy', 'content' => '<h2>Shipping Policy</h2><p>At ShopSmart, we strive to deliver your orders as quickly and safely as possible.</p><h3>Delivery Areas</h3><p>We deliver across Kenya including Nairobi, Mombasa, Kisumu, Nakuru, and all major towns.</p><h3>Delivery Times</h3><ul><li><strong>Nairobi:</strong> Same-day or next-day delivery</li><li><strong>Major Towns:</strong> 2-3 business days</li><li><strong>Remote Areas:</strong> 3-5 business days</li></ul><h3>Shipping Fees</h3><p>Free shipping on orders over KSh 5,000. Standard delivery fee of KSh 300 applies to orders below this amount.</p><h3>Order Tracking</h3><p>You can track your order status through your account dashboard or by using your order number on our tracking page.</p>'],
    ['slug' => 'returns-refunds', 'title' => 'Returns & Refunds', 'content' => '<h2>Returns & Refunds Policy</h2><p>We want you to be completely satisfied with your purchase. If you\'re not, we\'re here to help.</p><h3>Returns Window</h3><p>You may return most items within 30 days of delivery for a full refund.</p><h3>Conditions for Return</h3><ul><li>Item must be unused and in its original packaging</li><li>Item must not be damaged beyond the original condition</li><li>Proof of purchase (order number or receipt) is required</li></ul><h3>Non-Returnable Items</h3><ul><li>Personalized or custom-made products</li><li>Perishable goods</li><li>Software licenses</li><li>Items marked as final sale</li></ul><h3>Refund Process</h3><p>Once we receive your returned item, we\'ll inspect it and notify you of the approval or rejection of your refund. If approved, your refund will be processed within 5-7 business days.</p>'],
    ['slug' => 'faq', 'title' => 'Frequently Asked Questions', 'content' => '<h2>Frequently Asked Questions</h2><h3>How do I create an account?</h3><p>Click the "Register" button on the top right of our website and fill in your details. You\'ll receive a confirmation email once your account is set up.</p><h3>What payment methods do you accept?</h3><p>We accept M-Pesa, Visa, Mastercard, and bank transfers.</p><h3>How long does delivery take?</h3><p>Nairobi deliveries are same-day or next-day. Other major towns take 2-3 business days.</p><h3>Can I track my order?</h3><p>Yes! You can track your order through your account dashboard or by entering your order number on our order tracking page.</p><h3>How do I return an item?</h3><p>Contact our support team within 30 days of delivery. We\'ll guide you through the return process. See our Returns & Refunds policy for full details.</p><h3>Is my payment information secure?</h3><p>Absolutely. We use industry-standard encryption to protect your payment information.</p>'],
    ['slug' => 'privacy-policy', 'title' => 'Privacy Policy', 'content' => '<h2>Privacy Policy</h2><p>Your privacy is important to us. This policy explains how we collect, use, and protect your personal information.</p><h3>Information We Collect</h3><ul><li>Personal information (name, email, phone, address) when you create an account</li><li>Order history and preferences</li><li>Browser data and cookies for site functionality</li></ul><h3>How We Use Your Information</h3><ul><li>Process and fulfill your orders</li><li>Communicate order updates and promotions</li><li>Improve our services and user experience</li><li>Comply with legal obligations</li></ul><h3>Data Protection</h3><p>We implement appropriate security measures to protect your personal data against unauthorized access, alteration, or disclosure.</p><h3>Contact Us</h3><p>If you have questions about this privacy policy, contact us at privacy@shopsmart.co.ke.</p>'],
];

foreach ($pages as $p) {
    $exists = Database::selectOne("SELECT id FROM pages WHERE slug = ?", [$p['slug']]);
    if (!$exists) {
        Database::insert('pages', [
            'slug' => $p['slug'],
            'title' => $p['title'],
            'content' => $p['content'],
            'sort_order' => 0,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}

// Seed default shipping zone (already idempotent)
$exists = Database::selectOne("SELECT id FROM shipping_zones WHERE name = 'Default'");
if (!$exists) {
    Database::insert('shipping_zones', [
        'name' => 'Default',
        'region' => 'Kenya (Nationwide)',
        'base_fee' => 300,
        'free_above' => 5000,
        'is_active' => 1,
        'sort_order' => 1,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
}

// Seed social media settings (already idempotent — checks per key)
$socialSettings = [
    ['social_facebook', 'https://facebook.com/shopsmart'],
    ['social_twitter', 'https://twitter.com/shopsmart'],
    ['social_instagram', 'https://instagram.com/shopsmart'],
    ['social_youtube', ''],
    ['social_tiktok', ''],
];
foreach ($socialSettings as $s) {
    $exists = Database::selectOne("SELECT id FROM settings WHERE `key` = ?", [$s[0]]);
    if (!$exists) {
        Database::insert('settings', [
            'key' => $s[0],
            'value' => $s[1],
            'group_name' => 'social',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}

// Register migration
try {
    Database::insert('migrations', ['name' => 'add_cms_pages', 'ran_at' => date('Y-m-d H:i:s')]);
} catch (\Throwable $e) {}

echo "Migration add_cms_pages completed.\n";