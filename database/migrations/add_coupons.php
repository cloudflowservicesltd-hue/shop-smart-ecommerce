<?php
// Migration: Add coupons table

if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(dirname(__DIR__)));
if (!class_exists('Database')) require_once ROOT_PATH . '/app/Core/Database.php';

// Idempotency: skip if already migrated
try {
    $db = Database::getConnection();
    $db->exec("CREATE TABLE IF NOT EXISTS `migrations` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `name` VARCHAR(255) NOT NULL UNIQUE, `ran_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $ran = Database::selectOne("SELECT id FROM migrations WHERE name = ?", ['add_coupons']);
    if ($ran) { echo "Already migrated: add_coupons\n"; return; }
} catch (\Throwable $e) {}

$db = Database::getConnection();

$db->exec("CREATE TABLE IF NOT EXISTS `coupons` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `code` TEXT NOT NULL UNIQUE,
    `type` TEXT NOT NULL DEFAULT 'percentage',
    `value` DOUBLE NOT NULL DEFAULT 0,
    `min_order_amount` DOUBLE DEFAULT 0,
    `max_discount_amount` DOUBLE DEFAULT 0,
    `usage_limit` INT DEFAULT 0,
    `used_count` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `valid_from` TEXT,
    `valid_until` TEXT,
    `created_at` TEXT,
    `updated_at` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Register migration
try {
    Database::insert('migrations', ['name' => 'add_coupons', 'ran_at' => date('Y-m-d H:i:s')]);
} catch (\Throwable $e) {}

echo "Migration add_coupons completed.\n";