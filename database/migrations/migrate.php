<?php

// Database Migration - Run this once to set up the database
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(dirname(__DIR__)));
require_once ROOT_PATH . '/app/Core/Database.php';

$db = Database::getConnection();

$statements = [
    // Users table
    "CREATE TABLE IF NOT EXISTS `users` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `name` TEXT NOT NULL,
        `email` TEXT NOT NULL UNIQUE,
        `password` TEXT NOT NULL,
        `role` TEXT NOT NULL DEFAULT 'customer',
        `phone` TEXT,
        `address` TEXT,
        `city` TEXT,
        `country` TEXT DEFAULT 'Kenya',
        `avatar` TEXT,
        `is_active` TINYINT(1) DEFAULT 1,
        `email_verified_at` TEXT,
        `last_login` TEXT,
        `two_factor_secret` TEXT,
        `created_at` TEXT,
        `updated_at` TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Categories table
    "CREATE TABLE IF NOT EXISTS `categories` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `name` TEXT NOT NULL,
        `slug` TEXT NOT NULL UNIQUE,
        `description` TEXT,
        `parent_id` INT,
        `image` TEXT,
        `is_active` TINYINT(1) DEFAULT 1,
        `sort_order` INT DEFAULT 0,
        `created_at` TEXT,
        `updated_at` TEXT,
        FOREIGN KEY (`parent_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Brands table
    "CREATE TABLE IF NOT EXISTS `brands` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `name` TEXT NOT NULL,
        `slug` TEXT NOT NULL UNIQUE,
        `description` TEXT,
        `logo` TEXT,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TEXT,
        `updated_at` TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Products table
    "CREATE TABLE IF NOT EXISTS `products` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `name` TEXT NOT NULL,
        `slug` TEXT NOT NULL UNIQUE,
        `description` TEXT,
        `short_description` TEXT,
        `sku` TEXT UNIQUE,
        `barcode` TEXT UNIQUE,
        `category_id` INT,
        `brand_id` INT,
        `price` DOUBLE NOT NULL DEFAULT 0,
        `cost_price` DOUBLE DEFAULT 0,
        `discount_price` DOUBLE,
        `discount_type` TEXT DEFAULT 'fixed',
        `stock_quantity` INT DEFAULT 0,
        `low_stock_threshold` INT DEFAULT 10,
        `weight` DOUBLE,
        `is_featured` TINYINT(1) DEFAULT 0,
        `is_active` TINYINT(1) DEFAULT 1,
        `has_variants` TINYINT(1) DEFAULT 0,
        `supplier` TEXT,
        `shipping_info` TEXT,
        `meta_title` TEXT,
        `meta_description` TEXT,
        `created_at` TEXT,
        `updated_at` TEXT,
        FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
        FOREIGN KEY (`brand_id`) REFERENCES `brands`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Product Images table
    "CREATE TABLE IF NOT EXISTS `product_images` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `product_id` INT NOT NULL,
        `image_path` TEXT NOT NULL,
        `is_primary` TINYINT(1) DEFAULT 0,
        `sort_order` INT DEFAULT 0,
        `created_at` TEXT,
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Product Variants table
    "CREATE TABLE IF NOT EXISTS `product_variants` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `product_id` INT NOT NULL,
        `variant_name` TEXT NOT NULL,
        `sku` TEXT,
        `price` DOUBLE,
        `stock_quantity` INT DEFAULT 0,
        `created_at` TEXT,
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Orders table
    "CREATE TABLE IF NOT EXISTS `orders` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `order_number` TEXT NOT NULL UNIQUE,
        `customer_id` INT,
        `customer_name` TEXT,
        `customer_email` TEXT,
        `customer_phone` TEXT,
        `customer_address` TEXT,
        `status` TEXT DEFAULT 'pending',
        `payment_method` TEXT,
        `payment_status` TEXT DEFAULT 'pending',
        `payment_reference` TEXT,
        `subtotal` DOUBLE DEFAULT 0,
        `tax` DOUBLE DEFAULT 0,
        `discount` DOUBLE DEFAULT 0,
        `shipping_cost` DOUBLE DEFAULT 0,
        `total` DOUBLE DEFAULT 0,
        `notes` TEXT,
        `is_pos` TINYINT(1) DEFAULT 0,
        `amount_received` DOUBLE DEFAULT 0,
        `change_amount` DOUBLE DEFAULT 0,
        `created_at` TEXT,
        `updated_at` TEXT,
        FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Order Items table
    "CREATE TABLE IF NOT EXISTS `order_items` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `order_id` INT NOT NULL,
        `product_id` INT,
        `product_name` TEXT,
        `quantity` INT NOT NULL,
        `price` DOUBLE NOT NULL,
        `cost_price` DOUBLE,
        `total` DOUBLE NOT NULL,
        `created_at` TEXT,
        FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Cart table
    "CREATE TABLE IF NOT EXISTS `cart` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT,
        `session_id` TEXT,
        `product_id` INT NOT NULL,
        `quantity` INT DEFAULT 1,
        `created_at` TEXT,
        `updated_at` TEXT,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Wishlist table
    "CREATE TABLE IF NOT EXISTS `wishlists` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `product_id` INT NOT NULL,
        `created_at` TEXT,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
        UNIQUE(`user_id`, `product_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Product Reviews table
    "CREATE TABLE IF NOT EXISTS `reviews` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `product_id` INT NOT NULL,
        `user_id` INT NOT NULL,
        `rating` INT NOT NULL DEFAULT 5,
        `title` TEXT,
        `review` TEXT,
        `is_approved` TINYINT(1) DEFAULT 1,
        `created_at` TEXT,
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Transactions table
    "CREATE TABLE IF NOT EXISTS `transactions` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `order_id` INT,
        `payment_method` TEXT NOT NULL,
        `amount` DOUBLE NOT NULL,
        `currency` TEXT DEFAULT 'KES',
        `reference` TEXT,
        `status` TEXT DEFAULT 'pending',
        `gateway` TEXT,
        `gateway_response` TEXT,
        `created_at` TEXT,
        FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Marketing Campaigns table
    "CREATE TABLE IF NOT EXISTS `marketing_campaigns` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `name` TEXT NOT NULL,
        `platform` TEXT NOT NULL,
        `type` TEXT NOT NULL,
        `content` TEXT,
        `subject` TEXT,
        `status` TEXT DEFAULT 'draft',
        `audience` TEXT,
        `schedule_date` TEXT,
        `sent_at` TEXT,
        `total_sent` INT DEFAULT 0,
        `total_opened` INT DEFAULT 0,
        `total_clicked` INT DEFAULT 0,
        `created_by` INT,
        `created_at` TEXT,
        `updated_at` TEXT,
        FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Notifications table
    "CREATE TABLE IF NOT EXISTS `notifications` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT,
        `type` TEXT NOT NULL,
        `title` TEXT NOT NULL,
        `message` TEXT,
        `data` TEXT,
        `is_read` TINYINT(1) DEFAULT 0,
        `created_at` TEXT,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Audit Logs table
    "CREATE TABLE IF NOT EXISTS `audit_logs` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT,
        `action` TEXT NOT NULL,
        `model` TEXT,
        `model_id` INT,
        `old_values` TEXT,
        `new_values` TEXT,
        `ip_address` TEXT,
        `user_agent` TEXT,
        `created_at` TEXT,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Settings table
    "CREATE TABLE IF NOT EXISTS `settings` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `key` TEXT NOT NULL UNIQUE,
        `value` TEXT,
        `group_name` TEXT DEFAULT 'general',
        `created_at` TEXT,
        `updated_at` TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Warehouses/Branches
    "CREATE TABLE IF NOT EXISTS `branches` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `name` TEXT NOT NULL,
        `address` TEXT,
        `phone` TEXT,
        `is_main` TINYINT(1) DEFAULT 0,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TEXT,
        `updated_at` TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Suppliers table
    "CREATE TABLE IF NOT EXISTS `suppliers` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `name` TEXT NOT NULL,
        `contact_person` TEXT,
        `email` TEXT,
        `phone` TEXT,
        `address` TEXT,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TEXT,
        `updated_at` TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Purchase Orders table
    "CREATE TABLE IF NOT EXISTS `purchase_orders` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `supplier_id` INT,
        `po_number` TEXT NOT NULL UNIQUE,
        `status` TEXT DEFAULT 'pending',
        `total` DOUBLE DEFAULT 0,
        `expected_date` TEXT,
        `notes` TEXT,
        `created_by` INT,
        `created_at` TEXT,
        `updated_at` TEXT,
        FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE SET NULL,
        FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Stock Adjustments table
    "CREATE TABLE IF NOT EXISTS `stock_adjustments` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `product_id` INT NOT NULL,
        `quantity` INT NOT NULL,
        `reason` TEXT,
        `adjusted_by` INT,
        `created_at` TEXT,
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`adjusted_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];

foreach ($statements as $sql) {
    $db->exec($sql);
}

// Migration tracking table — ensures each migration runs only once
$db->exec("CREATE TABLE IF NOT EXISTS `migrations` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL UNIQUE,
    `ran_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

echo "Database migration completed successfully.\n";