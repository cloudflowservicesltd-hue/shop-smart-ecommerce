<?php
// Migration: Add testimonials table
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(dirname(__DIR__)));
if (!class_exists('Database')) require_once ROOT_PATH . '/app/Core/Database.php';

// Idempotency: skip if already migrated
try {
    $db = Database::getConnection();
    $ran = Database::selectOne("SELECT id FROM migrations WHERE name = ?", ['add_testimonials']);
    if ($ran) { echo "Already migrated: add_testimonials\n"; return; }
} catch (\Throwable $e) {}

$db = Database::getConnection();

$db->exec("CREATE TABLE IF NOT EXISTS `testimonials` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `author_name` VARCHAR(255) NOT NULL,
    `author_title` VARCHAR(255) DEFAULT '',
    `author_photo` TEXT DEFAULT '',
    `rating` TINYINT UNSIGNED NOT NULL DEFAULT 5,
    `review_text` TEXT NOT NULL,
    `source` VARCHAR(50) DEFAULT 'google',
    `is_featured` TINYINT(1) DEFAULT 0,
    `sort_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TEXT,
    `updated_at` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Add shipping_banner_text setting if not exists
$exists = Database::selectOne("SELECT id FROM settings WHERE `key` = ?", ['shipping_banner_text']);
if (!$exists) {
    Database::insert('settings', [
        'key' => 'shipping_banner_text',
        'value' => 'Free shipping on orders over KSh 5,000',
        'group_name' => 'general',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
}

// Seed sample testimonials — only if table is empty
$count = Database::selectOne("SELECT COUNT(*) as cnt FROM testimonials")['cnt'] ?? 0;
if ($count == 0) {
    $samples = [
        [
            'author_name' => 'Wanjiku Muthoni',
            'author_title' => 'Verified Buyer',
            'author_photo' => '',
            'rating' => 5,
            'review_text' => 'Amazing quality products and super fast delivery! I ordered a laptop and it arrived the very next day in perfect condition. Will definitely shop here again.',
            'source' => 'google',
            'is_featured' => 1,
            'sort_order' => 1,
            'is_active' => 1,
        ],
        [
            'author_name' => 'James Otieno',
            'author_title' => 'Verified Buyer',
            'author_photo' => '',
            'rating' => 5,
            'review_text' => 'Best online shopping experience in Kenya. The customer support team was incredibly helpful when I had questions about my phone order. Highly recommended!',
            'source' => 'google',
            'is_featured' => 1,
            'sort_order' => 2,
            'is_active' => 1,
        ],
        [
            'author_name' => 'Amina Hassan',
            'author_title' => 'Verified Buyer',
            'author_photo' => '',
            'rating' => 4,
            'review_text' => 'Great prices compared to other stores. The headphones I bought have excellent sound quality. Only wish shipping to Mombasa was a bit faster, but overall very satisfied.',
            'source' => 'google',
            'is_featured' => 0,
            'sort_order' => 3,
            'is_active' => 1,
        ],
        [
            'author_name' => 'David Kamau',
            'author_title' => 'Verified Buyer',
            'author_photo' => '',
            'rating' => 5,
            'review_text' => 'I have been buying electronics from this store for over a year now. Consistent quality, genuine products, and their return policy gives me peace of mind.',
            'source' => 'google',
            'is_featured' => 1,
            'sort_order' => 4,
            'is_active' => 1,
        ],
        [
            'author_name' => 'Grace Wambui',
            'author_title' => 'Verified Buyer',
            'author_photo' => '',
            'rating' => 4,
            'review_text' => 'Found exactly what I was looking for at a great price. The website is easy to navigate and the M-Pesa payment option makes checkout so convenient!',
            'source' => 'google',
            'is_featured' => 0,
            'sort_order' => 5,
            'is_active' => 1,
        ],
        [
            'author_name' => 'Peter Ochieng',
            'author_title' => 'Verified Buyer',
            'author_photo' => '',
            'rating' => 5,
            'review_text' => 'Ordered kitchen appliances and they exceeded my expectations. Authentic brands, well-packaged, and delivered right to my doorstep. Five stars all the way!',
            'source' => 'google',
            'is_featured' => 1,
            'sort_order' => 6,
            'is_active' => 1,
        ],
    ];

    foreach ($samples as $t) {
        $t['created_at'] = date('Y-m-d H:i:s');
        $t['updated_at'] = date('Y-m-d H:i:s');
        Database::insert('testimonials', $t);
    }
}

// Register migration
try {
    Database::insert('migrations', ['name' => 'add_testimonials', 'ran_at' => date('Y-m-d H:i:s')]);
} catch (\Throwable $e) {}

echo "Migration add_testimonials completed.\n";