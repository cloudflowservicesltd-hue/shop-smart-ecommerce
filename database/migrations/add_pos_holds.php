<?php
/**
 * Migration: Add pos_holds table for temporarily holding items during POS transactions.
 */
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__, 2));
if (!class_exists('Database')) require_once ROOT_PATH . '/app/Core/Database.php';

// Idempotency: skip if already migrated
try {
    $db = Database::getConnection();
    $db->exec("CREATE TABLE IF NOT EXISTS `migrations` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `name` VARCHAR(255) NOT NULL UNIQUE, `ran_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $ran = Database::selectOne("SELECT id FROM migrations WHERE name = ?", ['add_pos_holds']);
    if ($ran) { echo "Already migrated: add_pos_holds\n"; return; }
} catch (\Throwable $e) {}

$db = Database::getConnection();

$db->exec("CREATE TABLE IF NOT EXISTS `pos_holds` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `session_id` TEXT NOT NULL,
    `product_id` INT NOT NULL,
    `product_name` TEXT NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `price` DOUBLE NOT NULL DEFAULT 0,
    `held_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at` TEXT,
    `released` TINYINT(1) NOT NULL DEFAULT 0,
    `released_at` TEXT,
    `notes` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Register migration
try {
    Database::insert('migrations', ['name' => 'add_pos_holds', 'ran_at' => date('Y-m-d H:i:s')]);
} catch (\Throwable $e) {}

echo "Migration add_pos_holds completed.\n";