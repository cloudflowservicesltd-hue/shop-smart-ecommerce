<?php
/**
 * Migration: Add amount_received and change_amount columns to orders table
 */

if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__, 2));
if (!class_exists('Database')) require_once ROOT_PATH . '/app/Core/Database.php';

// Idempotency: skip if already migrated
try {
    $db = Database::getConnection();
    $db->exec("CREATE TABLE IF NOT EXISTS `migrations` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `name` VARCHAR(255) NOT NULL UNIQUE, `ran_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $ran = Database::selectOne("SELECT id FROM migrations WHERE name = ?", ['add_pos_columns']);
    if ($ran) { echo "Already migrated: add_pos_columns\n"; return; }
} catch (\Throwable $e) {}

$db = Database::getConnection();

// Check existing columns
$cols = $db->query("SHOW COLUMNS FROM `orders`")->fetchAll(PDO::FETCH_ASSOC);
$colNames = array_column($cols, 'Field');

echo "Current orders columns (" . count($colNames) . "): " . implode(', ', $colNames) . "\n\n";

$added = 0;

if (!in_array('amount_received', $colNames)) {
    $db->exec("ALTER TABLE `orders` ADD COLUMN `amount_received` DOUBLE DEFAULT 0");
    echo "[OK] Added column: amount_received\n";
    $added++;
} else {
    echo "[SKIP] Column amount_received already exists\n";
}

if (!in_array('change_amount', $colNames)) {
    $db->exec("ALTER TABLE `orders` ADD COLUMN `change_amount` DOUBLE DEFAULT 0");
    echo "[OK] Added column: change_amount\n";
    $added++;
} else {
    echo "[SKIP] Column change_amount already exists\n";
}

echo "\nDone! {$added} column(s) added.\n";

// Verify
$cols = $db->query("SHOW COLUMNS FROM `orders`")->fetchAll(PDO::FETCH_ASSOC);
$colNames = array_column($cols, 'Field');
echo "Final columns (" . count($colNames) . "): " . implode(', ', $colNames) . "\n";

// Register migration
try {
    Database::insert('migrations', ['name' => 'add_pos_columns', 'ran_at' => date('Y-m-d H:i:s')]);
} catch (\Throwable $e) {}

echo "Migration add_pos_columns completed.\n";