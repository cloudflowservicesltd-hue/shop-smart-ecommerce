<?php
/**
 * Migration: Fix pos_holds table schema, create commissions & custom_payment_methods tables
 */
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(dirname(__DIR__)));
if (!class_exists('Database')) require_once ROOT_PATH . '/app/Core/Database.php';

$db = Database::getConnection();

// --- Fix pos_holds table (drop old, recreate with correct schema) ---
$db->exec("DROP TABLE IF EXISTS `pos_holds`");
$db->exec("CREATE TABLE IF NOT EXISTS `pos_holds` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `cashier_name` VARCHAR(255) NOT NULL DEFAULT '',
    `cart_data` LONGTEXT NOT NULL,
    `items_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `subtotal` DOUBLE NOT NULL DEFAULT 0,
    `tax` DOUBLE NOT NULL DEFAULT 0,
    `total` DOUBLE NOT NULL DEFAULT 0,
    `customer_name` VARCHAR(255) NOT NULL DEFAULT '',
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// --- Create commissions table ---
$db->exec("CREATE TABLE IF NOT EXISTS `commissions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `order_id` INT UNSIGNED DEFAULT NULL,
    `order_number` VARCHAR(100) NOT NULL DEFAULT '',
    `amount` DOUBLE NOT NULL DEFAULT 0,
    `percentage` DOUBLE NOT NULL DEFAULT 0,
    `order_total` DOUBLE NOT NULL DEFAULT 0,
    `status` ENUM('pending','paid') NOT NULL DEFAULT 'pending',
    `paid_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// --- Create custom_payment_methods table ---
$db->exec("CREATE TABLE IF NOT EXISTS `custom_payment_methods` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `icon` VARCHAR(100) NOT NULL DEFAULT 'credit-card',
    `color` VARCHAR(50) NOT NULL DEFAULT 'gray',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// --- Ensure commission & POS settings exist ---
foreach ([
    ['commission_enabled', '0', 'pos'],
    ['commission_percentage', '5', 'pos'],
    ['card_enabled', '1', 'payments'],
] as $s) {
    try {
        $existing = Database::selectOne("SELECT id FROM settings WHERE `key` = ?", [$s[0]]);
        if (!$existing) {
            Database::insert('settings', [
                'key' => $s[0], 'value' => $s[1], 'group_name' => $s[2],
                'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    } catch (\Throwable $e) {}
}

echo "Migration fix_pos_holds_and_commissions completed.\n";