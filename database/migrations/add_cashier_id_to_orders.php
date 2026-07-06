<?php
/**
 * Migration: Add cashier_id to orders table
 */
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(dirname(__DIR__)));
if (!class_exists('Database')) require_once ROOT_PATH . '/app/Core/Database.php';

$db = Database::getConnection();

try {
    $db->exec("ALTER TABLE `orders` ADD COLUMN `cashier_id` INT UNSIGNED DEFAULT NULL AFTER `is_pos`");
    echo "Added cashier_id column to orders table.\n";
} catch (\PDOException $e) {
    if (str_contains($e->getMessage(), 'Duplicate column')) {
        echo "cashier_id column already exists.\n";
    } else {
        throw $e;
    }
}

echo "Migration add_cashier_id_to_orders completed.\n";