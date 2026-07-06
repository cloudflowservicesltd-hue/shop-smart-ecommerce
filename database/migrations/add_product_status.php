<?php
// Migration: Add product_status column to products table
// Values: active, discontinued, returned, draft

if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(dirname(__DIR__)));
if (!class_exists('Database')) require_once ROOT_PATH . '/app/Core/Database.php';

// Idempotency: skip if already migrated
try {
    $db = Database::getConnection();
    $db->exec("CREATE TABLE IF NOT EXISTS `migrations` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `name` VARCHAR(255) NOT NULL UNIQUE, `ran_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $ran = Database::selectOne("SELECT id FROM migrations WHERE name = ?", ['add_product_status']);
    if ($ran) { echo "Already migrated: add_product_status\n"; return; }
} catch (\Throwable $e) {
    // migrations table may not exist yet, continue
}

$colNames = array_column(Database::select("SHOW COLUMNS FROM `products`"), 'Field');

if (!in_array('product_status', $colNames)) {
    Database::query("ALTER TABLE `products` ADD COLUMN `product_status` TEXT DEFAULT 'active'");
}

// Register migration
try {
    Database::insert('migrations', ['name' => 'add_product_status', 'ran_at' => date('Y-m-d H:i:s')]);
} catch (\Throwable $e) {}

echo "Migration add_product_status completed.\n";