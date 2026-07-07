<?php
// Migration: Add shipping_cities table

if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(dirname(__DIR__)));
if (!class_exists('Database')) require_once ROOT_PATH . '/app/Core/Database.php';

// Idempotency: skip if already migrated
try {
    $db = Database::getConnection();
    $db->exec("CREATE TABLE IF NOT EXISTS `migrations` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `name` VARCHAR(255) NOT NULL UNIQUE, `ran_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $ran = Database::selectOne("SELECT id FROM migrations WHERE name = ?", ['add_shipping_cities']);
    if ($ran) { echo "Already migrated: add_shipping_cities\n"; return; }
} catch (\Throwable $e) {}

$db = Database::getConnection();

$db->exec("CREATE TABLE IF NOT EXISTS `shipping_cities` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `sort_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TEXT,
    `updated_at` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Insert default cities
$defaults = ['Nairobi', 'Mombasa', 'Kisumu', 'Nakuru', 'Eldoret', 'Thika', 'Malindi', 'Kitale', 'Nyeri', 'Nanyuki'];
foreach ($defaults as $i => $city) {
    try {
        Database::insert('shipping_cities', [
            'name' => $city,
            'sort_order' => $i,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    } catch (\Throwable $e) {
        // Ignore duplicate inserts
    }
}

// Register migration
try {
    Database::insert('migrations', ['name' => 'add_shipping_cities', 'ran_at' => date('Y-m-d H:i:s')]);
} catch (\Throwable $e) {}

echo "Migration add_shipping_cities completed.\n";