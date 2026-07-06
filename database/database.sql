-- ==========================================
-- ShopSmart E-Commerce Database - MySQL Dump
-- Converted from SQLite
-- ==========================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------
-- Table: audit_logs
-- --------------------------------------------------

CREATE TABLE `audit_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED,
  `action` VARCHAR(255) NOT NULL,
  `model` VARCHAR(255),
  `model_id` INT UNSIGNED,
  `old_values` TEXT,
  `new_values` TEXT,
  `ip_address` VARCHAR(255),
  `user_agent` TEXT,
  `created_at` VARCHAR(255),
  CONSTRAINT `fk_audit_logs_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------
-- Table: branches
-- --------------------------------------------------

CREATE TABLE `branches` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `address` TEXT,
  `phone` VARCHAR(255),
  `is_main` TINYINT(1) DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` VARCHAR(255),
  `updated_at` VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for table `branches` (2 rows)
INSERT INTO `branches` (`id`, `name`, `address`, `phone`, `is_main`, `is_active`, `created_at`, `updated_at`) VALUES (1, 'Main Store - Nairobi CBD', 'Kenyatta Avenue, Nairobi', '+254700000100', 1, 1, '2026-06-23 18:14:25', '2026-06-23 18:14:25');
INSERT INTO `branches` (`id`, `name`, `address`, `phone`, `is_main`, `is_active`, `created_at`, `updated_at`) VALUES (2, 'Westlands Branch', 'Westlands, Nairobi', '+254700000101', 0, 1, '2026-06-23 18:14:25', '2026-06-23 18:14:25');

-- --------------------------------------------------
-- Table: brands
-- --------------------------------------------------

CREATE TABLE `brands` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `description` TEXT,
  `logo` VARCHAR(255),
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` VARCHAR(255),
  `updated_at` VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for table `brands` (8 rows)
INSERT INTO `brands` (`id`, `name`, `slug`, `description`, `logo`, `is_active`, `created_at`, `updated_at`) VALUES (1, 'Samsung', 'samsung', NULL, '/uploads/brands/samsung.jpg', 1, '2026-06-23 18:14:25', '2026-06-23 18:14:25');
INSERT INTO `brands` (`id`, `name`, `slug`, `description`, `logo`, `is_active`, `created_at`, `updated_at`) VALUES (2, 'Apple', 'apple', NULL, '/uploads/brands/apple.jpg', 1, '2026-06-23 18:14:25', '2026-06-23 18:14:25');
INSERT INTO `brands` (`id`, `name`, `slug`, `description`, `logo`, `is_active`, `created_at`, `updated_at`) VALUES (3, 'Nike', 'nike', NULL, '/uploads/brands/nike.jpg', 1, '2026-06-23 18:14:25', '2026-06-23 18:14:25');
INSERT INTO `brands` (`id`, `name`, `slug`, `description`, `logo`, `is_active`, `created_at`, `updated_at`) VALUES (4, 'Sony', 'sony', NULL, '/uploads/brands/sony.jpg', 1, '2026-06-23 18:14:25', '2026-06-23 18:14:25');
INSERT INTO `brands` (`id`, `name`, `slug`, `description`, `logo`, `is_active`, `created_at`, `updated_at`) VALUES (5, 'HP', 'hp', NULL, '/uploads/brands/hp.jpg', 1, '2026-06-23 18:14:25', '2026-06-23 18:14:25');
INSERT INTO `brands` (`id`, `name`, `slug`, `description`, `logo`, `is_active`, `created_at`, `updated_at`) VALUES (6, 'Lenovo', 'lenovo', NULL, '/uploads/brands/lenovo.jpg', 1, '2026-06-23 18:14:25', '2026-06-23 18:14:25');
INSERT INTO `brands` (`id`, `name`, `slug`, `description`, `logo`, `is_active`, `created_at`, `updated_at`) VALUES (7, 'Adidas', 'adidas', NULL, '/uploads/brands/adidas.jpg', 1, '2026-06-23 18:14:25', '2026-06-23 18:14:25');
INSERT INTO `brands` (`id`, `name`, `slug`, `description`, `logo`, `is_active`, `created_at`, `updated_at`) VALUES (8, 'LG', 'lg', NULL, '/uploads/brands/lg.jpg', 1, '2026-06-23 18:14:25', '2026-06-23 18:14:25');

-- --------------------------------------------------
-- Table: cart
-- --------------------------------------------------

CREATE TABLE `cart` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED,
  `session_id` VARCHAR(255),
  `product_id` INT UNSIGNED NOT NULL,
  `quantity` INT DEFAULT 1,
  `created_at` VARCHAR(255),
  `updated_at` VARCHAR(255),
  CONSTRAINT `fk_cart_products` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cart_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------
-- Table: categories
-- --------------------------------------------------

CREATE TABLE `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `description` TEXT,
  `parent_id` INT UNSIGNED,
  `image` VARCHAR(255),
  `is_active` TINYINT(1) DEFAULT 1,
  `sort_order` INT DEFAULT 0,
  `created_at` VARCHAR(255),
  `updated_at` VARCHAR(255),
  CONSTRAINT `fk_categories_categories` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for table `categories` (13 rows)
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `parent_id`, `image`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES (1, 'Electronics', 'electronics', 'Phones, laptops, and gadgets', NULL, '/uploads/categories/electronics.jpg', 1, 1, '2026-06-23 18:14:25', '2026-06-23 18:14:25');
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `parent_id`, `image`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES (2, 'Clothing', 'clothing', 'Fashion and apparel', NULL, '/uploads/categories/clothing.jpg', 1, 2, '2026-06-23 18:14:25', '2026-06-23 18:14:25');
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `parent_id`, `image`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES (3, 'Home & Kitchen', 'home-kitchen', 'Home appliances and kitchen items', NULL, '/uploads/categories/home-kitchen.jpg', 1, 3, '2026-06-23 18:14:25', '2026-06-23 18:14:25');
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `parent_id`, `image`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES (4, 'Beauty & Health', 'beauty-health', 'Cosmetics and health products', NULL, '/uploads/categories/beauty-health.jpg', 1, 4, '2026-06-23 18:14:25', '2026-06-23 18:14:25');
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `parent_id`, `image`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES (5, 'Sports & Outdoor', 'sports-outdoor', 'Sports equipment and outdoor gear', NULL, '/uploads/categories/sports-outdoor.jpg', 1, 5, '2026-06-23 18:14:25', '2026-06-23 18:14:25');
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `parent_id`, `image`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES (6, 'Books & Stationery', 'books-stationery', 'Books, notebooks, and office supplies', NULL, '/uploads/categories/books-stationery.jpg', 1, 6, '2026-06-23 18:14:25', '2026-06-23 18:14:25');
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `parent_id`, `image`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES (7, 'Toys & Games', 'toys-games', 'Toys and gaming accessories', NULL, '/uploads/categories/toys-games.jpg', 1, 7, '2026-06-23 18:14:25', '2026-06-23 18:14:25');
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `parent_id`, `image`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES (8, 'Groceries', 'groceries', 'Food and household items', NULL, '/uploads/categories/groceries.jpg', 1, 8, '2026-06-23 18:14:25', '2026-06-23 18:14:25');
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `parent_id`, `image`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES (9, 'Smartphones', 'smartphones', 'Mobile phones', 1, NULL, 1, 1, '2026-06-23 18:14:25', '2026-06-23 18:14:25');
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `parent_id`, `image`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES (10, 'Laptops', 'laptops', 'Laptop computers', 1, NULL, 1, 2, '2026-06-23 18:14:25', '2026-06-23 18:14:25');
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `parent_id`, `image`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES (11, 'Accessories', 'accessories', 'Phone and laptop accessories', 1, NULL, 1, 3, '2026-06-23 18:14:25', '2026-06-23 18:14:25');
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `parent_id`, `image`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES (12, 'Men\'s Wear', 'mens-wear', 'Men\'s clothing', 2, NULL, 1, 1, '2026-06-23 18:14:25', '2026-06-23 18:14:25');
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `parent_id`, `image`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES (13, 'Women\'s Wear', 'womens-wear', 'Women\'s clothing', 2, NULL, 1, 2, '2026-06-23 18:14:25', '2026-06-23 18:14:25');

-- --------------------------------------------------
-- Table: coupons
-- --------------------------------------------------

CREATE TABLE `coupons` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(255) NOT NULL UNIQUE,
  `type` VARCHAR(255) NOT NULL DEFAULT 'percentage',
  `value` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `min_order_amount` DECIMAL(12,2) DEFAULT 0,
  `max_discount_amount` DECIMAL(12,2) DEFAULT 0,
  `usage_limit` INT DEFAULT 0,
  `used_count` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `valid_from` VARCHAR(255),
  `valid_until` VARCHAR(255),
  `created_at` VARCHAR(255),
  `updated_at` VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------
-- Table: hero_slides
-- --------------------------------------------------

CREATE TABLE `hero_slides` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `subtitle` VARCHAR(255),
  `description` TEXT,
  `cta_text` VARCHAR(255) DEFAULT 'Shop Now',
  `cta_link` VARCHAR(255) DEFAULT '/products',
  `image_url` VARCHAR(255),
  `bg_gradient` VARCHAR(255) DEFAULT 'from-amber-600 to-orange-700',
  `text_position` VARCHAR(255) DEFAULT 'left',
  `overlay` VARCHAR(255) DEFAULT 'dark',
  `sort_order` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` VARCHAR(255),
  `updated_at` VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for table `hero_slides` (4 rows)
INSERT INTO `hero_slides` (`id`, `title`, `subtitle`, `description`, `cta_text`, `cta_link`, `image_url`, `bg_gradient`, `text_position`, `overlay`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (1, 'Discover Premium Electronics', 'Latest Gadgets & Devices', 'Explore cutting-edge smartphones, laptops, and accessories from top brands at unbeatable prices.', 'Shop Electronics', '/products?category=electronics', '/uploads/categories/electronics.jpg', 'from-amber-700 via-orange-800 to-red-900', 'left', 'dark', 1, 1, '2026-06-26 12:24:54', '2026-06-26 12:24:54');
INSERT INTO `hero_slides` (`id`, `title`, `subtitle`, `description`, `cta_text`, `cta_link`, `image_url`, `bg_gradient`, `text_position`, `overlay`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (2, 'Fresh Fashion Arrivals', 'Nike, Adidas & More', 'Step into style with the latest footwear and apparel from world-renowned brands.', 'Shop Clothing', '/products?category=clothing', '/uploads/categories/clothing.jpg', 'from-slate-800 via-slate-900 to-gray-950', 'center', 'dark', 2, 1, '2026-06-26 12:24:54', '2026-06-26 12:24:54');
INSERT INTO `hero_slides` (`id`, `title`, `subtitle`, `description`, `cta_text`, `cta_link`, `image_url`, `bg_gradient`, `text_position`, `overlay`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (3, 'Home & Kitchen Essentials', 'Transform Your Space', 'Upgrade your home with premium appliances, decor, and kitchen essentials.', 'Explore Now', '/products?category=home-kitchen', '/uploads/categories/home-kitchen.jpg', 'from-teal-700 via-emerald-800 to-green-900', 'right', 'dark', 3, 1, '2026-06-26 12:24:54', '2026-06-26 12:24:54');
INSERT INTO `hero_slides` (`id`, `title`, `subtitle`, `description`, `cta_text`, `cta_link`, `image_url`, `bg_gradient`, `text_position`, `overlay`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (4, 'Up to 50% Off This Weekend', 'Mega Flash Sale', 'Limited-time deals on thousands of products. Grab them before they are gone!', 'View Deals', '/products?sale=1', '/uploads/products/samsung-galaxy-s24-ultra.jpg', 'from-rose-700 via-pink-800 to-fuchsia-900', 'left', 'dark', 4, 1, '2026-06-26 12:24:54', '2026-06-26 12:24:54');

-- --------------------------------------------------
-- Table: marketing_campaigns
-- --------------------------------------------------

CREATE TABLE `marketing_campaigns` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `platform` VARCHAR(255) NOT NULL,
  `type` VARCHAR(255) NOT NULL,
  `content` TEXT,
  `subject` VARCHAR(255),
  `status` VARCHAR(255) DEFAULT 'draft',
  `audience` VARCHAR(255),
  `schedule_date` VARCHAR(255),
  `sent_at` VARCHAR(255),
  `total_sent` INT DEFAULT 0,
  `total_opened` INT DEFAULT 0,
  `total_clicked` INT DEFAULT 0,
  `created_by` INT,
  `created_at` VARCHAR(255),
  `updated_at` VARCHAR(255),
  CONSTRAINT `fk_marketing_campaigns_users` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for table `marketing_campaigns` (4 rows)
INSERT INTO `marketing_campaigns` (`id`, `name`, `platform`, `type`, `content`, `subject`, `status`, `audience`, `schedule_date`, `sent_at`, `total_sent`, `total_opened`, `total_clicked`, `created_by`, `created_at`, `updated_at`) VALUES (1, 'Holiday Season Sale', 'email', 'newsletter', NULL, '🎄 Holiday Deals - Up to 50% Off!', 'sent', NULL, NULL, NULL, 1500, 890, 0, 1, '2026-06-23 18:14:25', '2026-06-23 18:14:25');
INSERT INTO `marketing_campaigns` (`id`, `name`, `platform`, `type`, `content`, `subject`, `status`, `audience`, `schedule_date`, `sent_at`, `total_sent`, `total_opened`, `total_clicked`, `created_by`, `created_at`, `updated_at`) VALUES (2, 'New Arrivals Promo', 'whatsapp', 'promotion', NULL, '', 'sent', NULL, NULL, NULL, 2300, 1800, 0, 1, '2026-06-23 18:14:25', '2026-06-23 18:14:25');
INSERT INTO `marketing_campaigns` (`id`, `name`, `platform`, `type`, `content`, `subject`, `status`, `audience`, `schedule_date`, `sent_at`, `total_sent`, `total_opened`, `total_clicked`, `created_by`, `created_at`, `updated_at`) VALUES (3, 'Flash Sale Friday', 'facebook', 'advertisement', NULL, '', 'active', NULL, NULL, NULL, 5000, 2100, 0, 1, '2026-06-23 18:14:25', '2026-06-23 18:14:25');
INSERT INTO `marketing_campaigns` (`id`, `name`, `platform`, `type`, `content`, `subject`, `status`, `audience`, `schedule_date`, `sent_at`, `total_sent`, `total_opened`, `total_clicked`, `created_by`, `created_at`, `updated_at`) VALUES (4, 'Customer Appreciation', 'email', 'newsletter', NULL, 'Thank You for Being a Loyal Customer', 'draft', NULL, NULL, NULL, 0, 0, 0, 1, '2026-06-23 18:14:25', '2026-06-23 18:14:25');

-- --------------------------------------------------
-- Table: migrations
-- --------------------------------------------------

CREATE TABLE `migrations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `file` VARCHAR(255) NOT NULL UNIQUE,
  `batch` INT NOT NULL DEFAULT 1,
  `executed_at` VARCHAR(255) NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for table `migrations` (9 rows)
INSERT INTO `migrations` (`id`, `file`, `batch`, `executed_at`) VALUES (1, 'add_cms_pages.php', 1, '2026-06-26 14:54:54');
INSERT INTO `migrations` (`id`, `file`, `batch`, `executed_at`) VALUES (2, 'add_coupons.php', 1, '2026-06-26 14:54:54');
INSERT INTO `migrations` (`id`, `file`, `batch`, `executed_at`) VALUES (3, 'add_frontend_content.php', 1, '2026-06-26 14:54:54');
INSERT INTO `migrations` (`id`, `file`, `batch`, `executed_at`) VALUES (4, 'add_payment_methods.php', 1, '2026-06-26 14:54:54');
INSERT INTO `migrations` (`id`, `file`, `batch`, `executed_at`) VALUES (5, 'add_pos_columns.php', 1, '2026-06-26 14:54:54');
INSERT INTO `migrations` (`id`, `file`, `batch`, `executed_at`) VALUES (6, 'add_pos_holds.php', 1, '2026-06-26 14:54:54');
INSERT INTO `migrations` (`id`, `file`, `batch`, `executed_at`) VALUES (7, 'add_product_status.php', 1, '2026-06-26 14:54:54');
INSERT INTO `migrations` (`id`, `file`, `batch`, `executed_at`) VALUES (8, 'migrate.php', 1, '2026-06-26 14:54:54');
INSERT INTO `migrations` (`id`, `file`, `batch`, `executed_at`) VALUES (9, 'fix_payment_settings_keys.php', 2, '2026-06-26 19:01:58');

-- --------------------------------------------------
-- Table: newsletter_subscribers
-- --------------------------------------------------

CREATE TABLE `newsletter_subscribers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------
-- Table: notifications
-- --------------------------------------------------

CREATE TABLE `notifications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED,
  `type` VARCHAR(255) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT,
  `data` TEXT,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` VARCHAR(255),
  CONSTRAINT `fk_notifications_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------
-- Table: order_items
-- --------------------------------------------------

CREATE TABLE `order_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED,
  `product_name` VARCHAR(255),
  `quantity` INT NOT NULL,
  `price` DECIMAL(12,2) NOT NULL,
  `cost_price` DECIMAL(12,2),
  `total` DECIMAL(12,2) NOT NULL,
  `created_at` VARCHAR(255),
  CONSTRAINT `fk_order_items_products` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_order_items_orders` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------
-- Table: orders
-- --------------------------------------------------

CREATE TABLE `orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `order_number` VARCHAR(255) NOT NULL UNIQUE,
  `customer_id` INT UNSIGNED,
  `customer_name` VARCHAR(255),
  `customer_email` VARCHAR(255),
  `customer_phone` VARCHAR(255),
  `customer_address` VARCHAR(255),
  `status` VARCHAR(255) DEFAULT 'pending',
  `payment_method` VARCHAR(255),
  `payment_status` VARCHAR(255) DEFAULT 'pending',
  `payment_reference` VARCHAR(255),
  `subtotal` DECIMAL(12,2) DEFAULT 0,
  `tax` DECIMAL(12,2) DEFAULT 0,
  `discount` DECIMAL(12,2) DEFAULT 0,
  `shipping_cost` DECIMAL(12,2) DEFAULT 0,
  `total` DECIMAL(12,2) DEFAULT 0,
  `notes` TEXT,
  `is_pos` TINYINT(1) DEFAULT 0,
  `created_at` VARCHAR(255),
  `updated_at` VARCHAR(255),
  `amount_received` DECIMAL(12,2) DEFAULT 0,
  `change_amount` DECIMAL(12,2) DEFAULT 0,
  CONSTRAINT `fk_orders_users` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for table `orders` (15 rows)
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `customer_name`, `customer_email`, `customer_phone`, `customer_address`, `status`, `payment_method`, `payment_status`, `payment_reference`, `subtotal`, `tax`, `discount`, `shipping_cost`, `total`, `notes`, `is_pos`, `created_at`, `updated_at`, `amount_received`, `change_amount`) VALUES (1, 'ORD-001001', 4, 'Customer 1', 'customer1@example.com', '+254760243169', NULL, 'processing', 'card', 'paid', NULL, 156381.0, 25020.96, 0.0, 0.0, 181401.96, NULL, 0, '2026-06-22 18:14:25', '2026-06-23 18:14:25', 0.0, 0.0);
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `customer_name`, `customer_email`, `customer_phone`, `customer_address`, `status`, `payment_method`, `payment_status`, `payment_reference`, `subtotal`, `tax`, `discount`, `shipping_cost`, `total`, `notes`, `is_pos`, `created_at`, `updated_at`, `amount_received`, `change_amount`) VALUES (2, 'ORD-001002', 4, 'Customer 2', 'customer2@example.com', '+254732779406', NULL, 'delivered', 'card', 'paid', NULL, 48660.0, 7785.6, 0.0, 0.0, 56445.6, NULL, 0, '2026-06-21 18:14:25', '2026-06-23 18:14:25', 0.0, 0.0);
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `customer_name`, `customer_email`, `customer_phone`, `customer_address`, `status`, `payment_method`, `payment_status`, `payment_reference`, `subtotal`, `tax`, `discount`, `shipping_cost`, `total`, `notes`, `is_pos`, `created_at`, `updated_at`, `amount_received`, `change_amount`) VALUES (3, 'ORD-001003', 4, 'Customer 3', 'customer3@example.com', '+254774040478', NULL, 'paid', 'card', 'paid', NULL, 86941.0, 13910.56, 0.0, 0.0, 100851.56, NULL, 0, '2026-06-20 18:14:25', '2026-06-23 18:14:25', 0.0, 0.0);
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `customer_name`, `customer_email`, `customer_phone`, `customer_address`, `status`, `payment_method`, `payment_status`, `payment_reference`, `subtotal`, `tax`, `discount`, `shipping_cost`, `total`, `notes`, `is_pos`, `created_at`, `updated_at`, `amount_received`, `change_amount`) VALUES (4, 'ORD-001004', 4, 'Customer 4', 'customer4@example.com', '+254766152636', NULL, 'delivered', 'mpesa', 'paid', NULL, 65571.0, 10491.36, 0.0, 0.0, 76062.36, NULL, 0, '2026-06-19 18:14:25', '2026-06-23 18:14:25', 0.0, 0.0);
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `customer_name`, `customer_email`, `customer_phone`, `customer_address`, `status`, `payment_method`, `payment_status`, `payment_reference`, `subtotal`, `tax`, `discount`, `shipping_cost`, `total`, `notes`, `is_pos`, `created_at`, `updated_at`, `amount_received`, `change_amount`) VALUES (5, 'ORD-001005', 4, 'Customer 5', 'customer5@example.com', '+254739891373', NULL, 'processing', 'card', 'paid', NULL, 157540.0, 25206.4, 0.0, 0.0, 182746.4, NULL, 0, '2026-06-18 18:14:25', '2026-06-23 18:14:25', 0.0, 0.0);
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `customer_name`, `customer_email`, `customer_phone`, `customer_address`, `status`, `payment_method`, `payment_status`, `payment_reference`, `subtotal`, `tax`, `discount`, `shipping_cost`, `total`, `notes`, `is_pos`, `created_at`, `updated_at`, `amount_received`, `change_amount`) VALUES (6, 'ORD-001006', 4, 'Customer 6', 'customer6@example.com', '+254775826170', NULL, 'processing', 'cash', 'paid', NULL, 43088.0, 6894.08, 0.0, 0.0, 49982.08, NULL, 0, '2026-06-17 18:14:25', '2026-06-23 18:14:25', 0.0, 0.0);
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `customer_name`, `customer_email`, `customer_phone`, `customer_address`, `status`, `payment_method`, `payment_status`, `payment_reference`, `subtotal`, `tax`, `discount`, `shipping_cost`, `total`, `notes`, `is_pos`, `created_at`, `updated_at`, `amount_received`, `change_amount`) VALUES (7, 'ORD-001007', 4, 'Customer 7', 'customer7@example.com', '+254112148712', NULL, 'pending', 'mpesa', 'pending', NULL, 165357.0, 26457.12, 0.0, 0.0, 191814.12, NULL, 0, '2026-06-16 18:14:25', '2026-06-26 16:33:23', 0.0, 0.0);
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `customer_name`, `customer_email`, `customer_phone`, `customer_address`, `status`, `payment_method`, `payment_status`, `payment_reference`, `subtotal`, `tax`, `discount`, `shipping_cost`, `total`, `notes`, `is_pos`, `created_at`, `updated_at`, `amount_received`, `change_amount`) VALUES (8, 'ORD-001008', 4, 'Customer 8', 'customer8@example.com', '+254735417854', NULL, 'pending', 'cash', 'pending', NULL, 65244.0, 10439.04, 0.0, 0.0, 75683.04, NULL, 0, '2026-06-15 18:14:25', '2026-06-23 18:14:25', 0.0, 0.0);
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `customer_name`, `customer_email`, `customer_phone`, `customer_address`, `status`, `payment_method`, `payment_status`, `payment_reference`, `subtotal`, `tax`, `discount`, `shipping_cost`, `total`, `notes`, `is_pos`, `created_at`, `updated_at`, `amount_received`, `change_amount`) VALUES (9, 'ORD-001009', 4, 'Customer 9', 'customer9@example.com', '+254739837728', NULL, 'paid', 'card', 'paid', NULL, 53399.0, 8543.84, 0.0, 0.0, 61942.84, NULL, 0, '2026-06-14 18:14:25', '2026-06-23 18:14:25', 0.0, 0.0);
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `customer_name`, `customer_email`, `customer_phone`, `customer_address`, `status`, `payment_method`, `payment_status`, `payment_reference`, `subtotal`, `tax`, `discount`, `shipping_cost`, `total`, `notes`, `is_pos`, `created_at`, `updated_at`, `amount_received`, `change_amount`) VALUES (10, 'ORD-001010', 4, 'Customer 10', 'customer10@example.com', '+254750753050', NULL, 'paid', 'mpesa', 'paid', NULL, 66769.0, 10683.04, 0.0, 0.0, 77452.04, NULL, 0, '2026-06-13 18:14:25', '2026-06-23 18:14:25', 0.0, 0.0);
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `customer_name`, `customer_email`, `customer_phone`, `customer_address`, `status`, `payment_method`, `payment_status`, `payment_reference`, `subtotal`, `tax`, `discount`, `shipping_cost`, `total`, `notes`, `is_pos`, `created_at`, `updated_at`, `amount_received`, `change_amount`) VALUES (11, 'ORD-001011', NULL, 'Customer 11', 'customer11@example.com', '+254731867327', NULL, 'shipped', 'card', 'paid', NULL, 155287.0, 24845.92, 0.0, 0.0, 180132.92, NULL, 1, '2026-06-12 18:14:25', '2026-06-23 18:14:25', 0.0, 0.0);
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `customer_name`, `customer_email`, `customer_phone`, `customer_address`, `status`, `payment_method`, `payment_status`, `payment_reference`, `subtotal`, `tax`, `discount`, `shipping_cost`, `total`, `notes`, `is_pos`, `created_at`, `updated_at`, `amount_received`, `change_amount`) VALUES (12, 'ORD-001012', NULL, 'Customer 12', 'customer12@example.com', '+254778869490', NULL, 'shipped', 'card', 'paid', NULL, 59418.0, 9506.88, 0.0, 0.0, 68924.88, NULL, 1, '2026-06-11 18:14:25', '2026-06-23 18:14:25', 0.0, 0.0);
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `customer_name`, `customer_email`, `customer_phone`, `customer_address`, `status`, `payment_method`, `payment_status`, `payment_reference`, `subtotal`, `tax`, `discount`, `shipping_cost`, `total`, `notes`, `is_pos`, `created_at`, `updated_at`, `amount_received`, `change_amount`) VALUES (13, 'ORD-001013', NULL, 'Customer 13', 'customer13@example.com', '+254759467045', NULL, 'pending', 'mpesa', 'pending', NULL, 21835.0, 3493.6, 0.0, 0.0, 25328.6, NULL, 1, '2026-06-10 18:14:25', '2026-06-23 18:14:25', 0.0, 0.0);
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `customer_name`, `customer_email`, `customer_phone`, `customer_address`, `status`, `payment_method`, `payment_status`, `payment_reference`, `subtotal`, `tax`, `discount`, `shipping_cost`, `total`, `notes`, `is_pos`, `created_at`, `updated_at`, `amount_received`, `change_amount`) VALUES (14, 'ORD-001014', NULL, 'Customer 14', 'customer14@example.com', '+254752930618', NULL, 'pending', 'cash', 'pending', NULL, 10851.0, 1736.16, 0.0, 0.0, 12587.16, NULL, 1, '2026-06-09 18:14:25', '2026-06-23 18:14:25', 0.0, 0.0);
INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `customer_name`, `customer_email`, `customer_phone`, `customer_address`, `status`, `payment_method`, `payment_status`, `payment_reference`, `subtotal`, `tax`, `discount`, `shipping_cost`, `total`, `notes`, `is_pos`, `created_at`, `updated_at`, `amount_received`, `change_amount`) VALUES (15, 'ORD-001015', NULL, 'Customer 15', 'customer15@example.com', '+254727129169', NULL, 'paid', 'cash', 'paid', NULL, 164904.0, 26384.64, 0.0, 0.0, 191288.64, NULL, 1, '2026-06-08 18:14:25', '2026-06-23 18:14:25', 0.0, 0.0);

-- --------------------------------------------------
-- Table: pages
-- --------------------------------------------------

CREATE TABLE `pages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL DEFAULT '',
  `meta_title` VARCHAR(255),
  `meta_description` TEXT,
  `sort_order` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` VARCHAR(255),
  `updated_at` VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for table `pages` (5 rows)
INSERT INTO `pages` (`id`, `slug`, `title`, `content`, `meta_title`, `meta_description`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (1, 'contact-us', 'Contact Us', '<h2>Get in Touch</h2><p>We\'d love to hear from you. Reach out through any of the channels below.</p><h3>Address</h3><p>Kenyatta Avenue, Nairobi CBD, Kenya</p><h3>Phone</h3><p>+254 700 000 000</p><h3>Email</h3><p>info@shopsmart.co.ke</p><h3>Business Hours</h3><p>Monday - Saturday: 8:00 AM - 8:00 PM<br>Sunday: Closed</p>', NULL, NULL, 0, 1, '2026-06-26 12:24:54', '2026-06-26 12:24:54');
INSERT INTO `pages` (`id`, `slug`, `title`, `content`, `meta_title`, `meta_description`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (2, 'shipping-policy', 'Shipping Policy', '<h2>Shipping Policy</h2><p>At ShopSmart, we strive to deliver your orders as quickly and safely as possible.</p><h3>Delivery Areas</h3><p>We deliver across Kenya including Nairobi, Mombasa, Kisumu, Nakuru, and all major towns.</p><h3>Delivery Times</h3><ul><li><strong>Nairobi:</strong> Same-day or next-day delivery</li><li><strong>Major Towns:</strong> 2-3 business days</li><li><strong>Remote Areas:</strong> 3-5 business days</li></ul><h3>Shipping Fees</h3><p>Free shipping on orders over KSh 5,000. Standard delivery fee of KSh 300 applies to orders below this amount.</p><h3>Order Tracking</h3><p>You can track your order status through your account dashboard or by using your order number on our tracking page.</p>', NULL, NULL, 0, 1, '2026-06-26 12:24:54', '2026-06-26 12:24:54');
INSERT INTO `pages` (`id`, `slug`, `title`, `content`, `meta_title`, `meta_description`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (3, 'returns-refunds', 'Returns & Refunds', '<h2>Returns & Refunds Policy</h2><p>We want you to be completely satisfied with your purchase. If you\'re not, we\'re here to help.</p><h3>Returns Window</h3><p>You may return most items within 30 days of delivery for a full refund.</p><h3>Conditions for Return</h3><ul><li>Item must be unused and in its original packaging</li><li>Item must not be damaged beyond the original condition</li><li>Proof of purchase (order number or receipt) is required</li></ul><h3>Non-Returnable Items</h3><ul><li>Personalized or custom-made products</li><li>Perishable goods</li><li>Software licenses</li><li>Items marked as final sale</li></ul><h3>Refund Process</h3><p>Once we receive your returned item, we\'ll inspect it and notify you of the approval or rejection of your refund. If approved, your refund will be processed within 5-7 business days.</p>', NULL, NULL, 0, 1, '2026-06-26 12:24:54', '2026-06-26 12:24:54');
INSERT INTO `pages` (`id`, `slug`, `title`, `content`, `meta_title`, `meta_description`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (4, 'faq', 'Frequently Asked Questions', '<h2>Frequently Asked Questions</h2><h3>How do I create an account?</h3><p>Click the "Register" button on the top right of our website and fill in your details. You\'ll receive a confirmation email once your account is set up.</p><h3>What payment methods do you accept?</h3><p>We accept M-Pesa, Visa, Mastercard, and bank transfers.</p><h3>How long does delivery take?</h3><p>Nairobi deliveries are same-day or next-day. Other major towns take 2-3 business days.</p><h3>Can I track my order?</h3><p>Yes! You can track your order through your account dashboard or by entering your order number on our order tracking page.</p><h3>How do I return an item?</h3><p>Contact our support team within 30 days of delivery. We\'ll guide you through the return process. See our Returns & Refunds policy for full details.</p><h3>Is my payment information secure?</h3><p>Absolutely. We use industry-standard encryption to protect your payment information.</p>', NULL, NULL, 0, 1, '2026-06-26 12:24:54', '2026-06-26 12:24:54');
INSERT INTO `pages` (`id`, `slug`, `title`, `content`, `meta_title`, `meta_description`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (5, 'privacy-policy', 'Privacy Policy', '<h2>Privacy Policy</h2><p>Your privacy is important to us. This policy explains how we collect, use, and protect your personal information.</p><h3>Information We Collect</h3><ul><li>Personal information (name, email, phone, address) when you create an account</li><li>Order history and preferences</li><li>Browser data and cookies for site functionality</li></ul><h3>How We Use Your Information</h3><ul><li>Process and fulfill your orders</li><li>Communicate order updates and promotions</li><li>Improve our services and user experience</li><li>Comply with legal obligations</li></ul><h3>Data Protection</h3><p>We implement appropriate security measures to protect your personal data against unauthorized access, alteration, or disclosure.</p><h3>Contact Us</h3><p>If you have questions about this privacy policy, contact us at privacy@shopsmart.co.ke.</p>', NULL, NULL, 0, 1, '2026-06-26 12:24:54', '2026-06-26 12:24:54');

-- --------------------------------------------------
-- Table: pos_holds
-- --------------------------------------------------

CREATE TABLE `pos_holds` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `session_id` VARCHAR(255) NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `price` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `held_at` VARCHAR(255) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` VARCHAR(255),
  `released` TINYINT(1) NOT NULL DEFAULT 0,
  `released_at` VARCHAR(255),
  `notes` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------
-- Table: product_images
-- --------------------------------------------------

CREATE TABLE `product_images` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT UNSIGNED NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `is_primary` TINYINT(1) DEFAULT 0,
  `sort_order` INT DEFAULT 0,
  `created_at` VARCHAR(255),
  CONSTRAINT `fk_product_images_products` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for table `product_images` (12 rows)
INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `is_primary`, `sort_order`, `created_at`) VALUES (1, 1, '/uploads/products/samsung-galaxy-s24-ultra.jpg', 1, 0, '2026-06-23 18:14:25');
INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `is_primary`, `sort_order`, `created_at`) VALUES (2, 2, '/uploads/products/iphone-15-pro-max.jpg', 1, 0, '2026-06-23 18:14:25');
INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `is_primary`, `sort_order`, `created_at`) VALUES (3, 3, '/uploads/products/samsung-galaxy-a54-5g.jpg', 1, 0, '2026-06-23 18:14:25');
INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `is_primary`, `sort_order`, `created_at`) VALUES (4, 4, '/uploads/products/hp-pavilion-15.jpg', 1, 0, '2026-06-23 18:14:25');
INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `is_primary`, `sort_order`, `created_at`) VALUES (5, 5, '/uploads/products/sony-wh-1000xm5.jpg', 1, 0, '2026-06-23 18:14:25');
INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `is_primary`, `sort_order`, `created_at`) VALUES (6, 6, '/uploads/products/nike-air-max-270.jpg', 1, 0, '2026-06-23 18:14:25');
INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `is_primary`, `sort_order`, `created_at`) VALUES (7, 7, '/uploads/products/adidas-ultraboost-23.jpg', 1, 0, '2026-06-23 18:14:25');
INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `is_primary`, `sort_order`, `created_at`) VALUES (8, 8, '/uploads/products/lenovo-ideapad-3.jpg', 1, 0, '2026-06-23 18:14:25');
INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `is_primary`, `sort_order`, `created_at`) VALUES (9, 9, '/uploads/products/lg-55-oled-c3.jpg', 1, 0, '2026-06-23 18:14:25');
INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `is_primary`, `sort_order`, `created_at`) VALUES (10, 10, '/uploads/products/samsung-253l-fridge.jpg', 1, 0, '2026-06-23 18:14:25');
INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `is_primary`, `sort_order`, `created_at`) VALUES (11, 11, '/uploads/products/nike-dri-fit-tshirt.jpg', 1, 0, '2026-06-23 18:14:25');
INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `is_primary`, `sort_order`, `created_at`) VALUES (12, 12, '/uploads/products/samsung-galaxy-buds-fe.jpg', 1, 0, '2026-06-23 18:14:25');

-- --------------------------------------------------
-- Table: product_variants
-- --------------------------------------------------

CREATE TABLE `product_variants` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT UNSIGNED NOT NULL,
  `variant_name` VARCHAR(255) NOT NULL,
  `sku` VARCHAR(255),
  `price` DECIMAL(12,2),
  `stock_quantity` INT DEFAULT 0,
  `created_at` VARCHAR(255),
  CONSTRAINT `fk_product_variants_products` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------
-- Table: products
-- --------------------------------------------------

CREATE TABLE `products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `description` TEXT,
  `short_description` VARCHAR(255),
  `sku` VARCHAR(255) UNIQUE,
  `barcode` VARCHAR(255) UNIQUE,
  `category_id` INT UNSIGNED,
  `brand_id` INT UNSIGNED,
  `price` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `cost_price` DECIMAL(12,2) DEFAULT 0,
  `discount_price` DECIMAL(12,2),
  `discount_type` VARCHAR(255) DEFAULT 'fixed',
  `stock_quantity` INT DEFAULT 0,
  `low_stock_threshold` INT DEFAULT 10,
  `weight` DECIMAL(12,2),
  `is_featured` TINYINT(1) DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `has_variants` TINYINT(1) DEFAULT 0,
  `supplier` VARCHAR(255),
  `shipping_info` TEXT,
  `meta_title` VARCHAR(255),
  `meta_description` TEXT,
  `created_at` VARCHAR(255),
  `updated_at` VARCHAR(255),
  `product_status` VARCHAR(255) DEFAULT 'active',
  CONSTRAINT `fk_products_brands` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_products_categories` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for table `products` (12 rows)
INSERT INTO `products` (`id`, `name`, `slug`, `description`, `short_description`, `sku`, `barcode`, `category_id`, `brand_id`, `price`, `cost_price`, `discount_price`, `discount_type`, `stock_quantity`, `low_stock_threshold`, `weight`, `is_featured`, `is_active`, `has_variants`, `supplier`, `shipping_info`, `meta_title`, `meta_description`, `created_at`, `updated_at`, `product_status`) VALUES (1, 'Samsung Galaxy S24 Ultra', 'samsung-galaxy-s24-ultra', 'Experience the next level of mobile innovation with the Samsung Galaxy S24 Ultra. Featuring a stunning 6.8-inch Dynamic AMOLED display, powerful Snapdragon 8 Gen 3 processor, and an advanced AI camera system that captures professional-grade photos and videos.', 'The ultimate Galaxy experience with AI features', 'SAM-S24U-256', '8806090999999', 1, 1, 164999.0, 140000.0, NULL, 'fixed', 25, 10, 0.232, 1, 1, 0, NULL, NULL, NULL, NULL, '2026-06-23 18:14:25', '2026-06-23 18:14:25', 'active');
INSERT INTO `products` (`id`, `name`, `slug`, `description`, `short_description`, `sku`, `barcode`, `category_id`, `brand_id`, `price`, `cost_price`, `discount_price`, `discount_type`, `stock_quantity`, `low_stock_threshold`, `weight`, `is_featured`, `is_active`, `has_variants`, `supplier`, `shipping_info`, `meta_title`, `meta_description`, `created_at`, `updated_at`, `product_status`) VALUES (2, 'iPhone 15 Pro Max', 'iphone-15-pro-max', 'iPhone 15 Pro Max features a strong and light titanium design with the A17 Pro chip, a customizable Action button, and the most powerful iPhone camera system ever.', 'Titanium design. A17 Pro chip.', 'APL-15PM-256', '0194253999999', 1, 2, 199999.0, 175000.0, NULL, 'fixed', 15, 10, 0.221, 1, 1, 0, NULL, NULL, NULL, NULL, '2026-06-23 18:14:25', '2026-06-23 18:14:25', 'active');
INSERT INTO `products` (`id`, `name`, `slug`, `description`, `short_description`, `sku`, `barcode`, `category_id`, `brand_id`, `price`, `cost_price`, `discount_price`, `discount_type`, `stock_quantity`, `low_stock_threshold`, `weight`, `is_featured`, `is_active`, `has_variants`, `supplier`, `shipping_info`, `meta_title`, `meta_description`, `created_at`, `updated_at`, `product_status`) VALUES (3, 'Samsung Galaxy A54 5G', 'samsung-galaxy-a54-5g', 'Samsung Galaxy A54 5G brings flagship features to the mid-range segment with a 6.4-inch Super AMOLED display, 50MP triple camera, and 5000mAh battery.', 'Awesome Galaxy experience at a great price', 'SAM-A54-128', '8806090888888', 1, 1, 42999.0, 35000.0, NULL, 'fixed', 50, 10, 0.202, 0, 1, 0, NULL, NULL, NULL, NULL, '2026-06-23 18:14:25', '2026-06-23 18:14:25', 'active');
INSERT INTO `products` (`id`, `name`, `slug`, `description`, `short_description`, `sku`, `barcode`, `category_id`, `brand_id`, `price`, `cost_price`, `discount_price`, `discount_type`, `stock_quantity`, `low_stock_threshold`, `weight`, `is_featured`, `is_active`, `has_variants`, `supplier`, `shipping_info`, `meta_title`, `meta_description`, `created_at`, `updated_at`, `product_status`) VALUES (4, 'HP Pavilion Laptop 15', 'hp-pavilion-15', 'HP Pavilion 15 delivers reliable performance with Intel Core i5, 8GB RAM, 512GB SSD, and a 15.6-inch FHD display perfect for both work and entertainment.', 'Powerful performance for work and play', 'HP-PAV15-I5', '1958760333333', 1, 5, 89999.0, 72000.0, NULL, 'fixed', 20, 10, 1.75, 1, 1, 0, NULL, NULL, NULL, NULL, '2026-06-23 18:14:25', '2026-06-23 18:14:25', 'active');
INSERT INTO `products` (`id`, `name`, `slug`, `description`, `short_description`, `sku`, `barcode`, `category_id`, `brand_id`, `price`, `cost_price`, `discount_price`, `discount_type`, `stock_quantity`, `low_stock_threshold`, `weight`, `is_featured`, `is_active`, `has_variants`, `supplier`, `shipping_info`, `meta_title`, `meta_description`, `created_at`, `updated_at`, `product_status`) VALUES (5, 'Sony WH-1000XM5 Headphones', 'sony-wh-1000xm5', 'Experience unparalleled noise cancellation with the Sony WH-1000XM5. Premium comfort, exceptional sound quality, and up to 30 hours of battery life.', 'Industry-leading noise cancellation', 'SNY-XM5-BLK', '4905524977777', 1, 4, 34999.0, 28000.0, NULL, 'fixed', 35, 10, 0.25, 1, 1, 0, NULL, NULL, NULL, NULL, '2026-06-23 18:14:25', '2026-06-23 18:14:25', 'active');
INSERT INTO `products` (`id`, `name`, `slug`, `description`, `short_description`, `sku`, `barcode`, `category_id`, `brand_id`, `price`, `cost_price`, `discount_price`, `discount_type`, `stock_quantity`, `low_stock_threshold`, `weight`, `is_featured`, `is_active`, `has_variants`, `supplier`, `shipping_info`, `meta_title`, `meta_description`, `created_at`, `updated_at`, `product_status`) VALUES (6, 'Nike Air Max 270', 'nike-air-max-270', 'Nike Air Max 270 features the largest Max Air unit yet for a super soft ride that feels as impossible as it looks. Available in multiple colorways.', 'The lifestyle silhouette with big Air', 'NK-AM270-42', '0196236555555', 2, 3, 14999.0, 9000.0, NULL, 'fixed', 80, 10, 0.35, 1, 1, 0, NULL, NULL, NULL, NULL, '2026-06-23 18:14:25', '2026-06-23 18:14:25', 'active');
INSERT INTO `products` (`id`, `name`, `slug`, `description`, `short_description`, `sku`, `barcode`, `category_id`, `brand_id`, `price`, `cost_price`, `discount_price`, `discount_type`, `stock_quantity`, `low_stock_threshold`, `weight`, `is_featured`, `is_active`, `has_variants`, `supplier`, `shipping_info`, `meta_title`, `meta_description`, `created_at`, `updated_at`, `product_status`) VALUES (7, 'Adidas Ultraboost 23', 'adidas-ultraboost-23', 'The Adidas Ultraboost 23 delivers incredible energy return with BOOST technology, a Primeknit+ upper, and a Continental rubber outsole for superior grip.', 'Energy return for every stride', 'AD-UB23-43', '4065428666666', 2, 7, 16999.0, 11000.0, NULL, 'fixed', 60, 10, 0.32, 0, 1, 0, NULL, NULL, NULL, NULL, '2026-06-23 18:14:25', '2026-06-23 18:14:25', 'active');
INSERT INTO `products` (`id`, `name`, `slug`, `description`, `short_description`, `sku`, `barcode`, `category_id`, `brand_id`, `price`, `cost_price`, `discount_price`, `discount_type`, `stock_quantity`, `low_stock_threshold`, `weight`, `is_featured`, `is_active`, `has_variants`, `supplier`, `shipping_info`, `meta_title`, `meta_description`, `created_at`, `updated_at`, `product_status`) VALUES (8, 'Lenovo IdeaPad 3', 'lenovo-ideapad-3', 'Lenovo IdeaPad 3 is perfect for everyday tasks with its AMD Ryzen 5 processor, 8GB RAM, 256GB SSD, and 15.6-inch anti-glare display.', 'Everyday computing made easy', 'LNV-IP3-R5', '1958760444444', 1, 6, 64999.0, 52000.0, NULL, 'fixed', 30, 10, 1.6, 0, 1, 0, NULL, NULL, NULL, NULL, '2026-06-23 18:14:25', '2026-06-23 18:14:25', 'active');
INSERT INTO `products` (`id`, `name`, `slug`, `description`, `short_description`, `sku`, `barcode`, `category_id`, `brand_id`, `price`, `cost_price`, `discount_price`, `discount_type`, `stock_quantity`, `low_stock_threshold`, `weight`, `is_featured`, `is_active`, `has_variants`, `supplier`, `shipping_info`, `meta_title`, `meta_description`, `created_at`, `updated_at`, `product_status`) VALUES (9, 'LG 55" OLED TV', 'lg-55-oled-c3', 'LG OLED C3 delivers perfect blacks, infinite contrast, and over a billion colors with α9 Gen6 AI Processor 4K for an immersive viewing experience.', 'Infinite contrast with self-lit pixels', 'LG-OLED55-C3', '8806091222222', 1, 8, 189999.0, 155000.0, NULL, 'fixed', 8, 10, 18.5, 1, 1, 0, NULL, NULL, NULL, NULL, '2026-06-23 18:14:25', '2026-06-23 18:14:25', 'active');
INSERT INTO `products` (`id`, `name`, `slug`, `description`, `short_description`, `sku`, `barcode`, `category_id`, `brand_id`, `price`, `cost_price`, `discount_price`, `discount_type`, `stock_quantity`, `low_stock_threshold`, `weight`, `is_featured`, `is_active`, `has_variants`, `supplier`, `shipping_info`, `meta_title`, `meta_description`, `created_at`, `updated_at`, `product_status`) VALUES (10, 'Samsung 253L Refrigerator', 'samsung-253l-fridge', 'Samsung 253L refrigerator with Digital Inverter Compressor, twin cooling plus technology, and a sleek design that fits any modern kitchen.', 'Digital Inverter technology for energy efficiency', 'SAM-RF253-SS', '8806091333333', 3, 1, 109999.0, 85000.0, NULL, 'fixed', 12, 10, 65.0, 0, 1, 0, NULL, NULL, NULL, NULL, '2026-06-23 18:14:25', '2026-06-23 18:14:25', 'active');
INSERT INTO `products` (`id`, `name`, `slug`, `description`, `short_description`, `sku`, `barcode`, `category_id`, `brand_id`, `price`, `cost_price`, `discount_price`, `discount_type`, `stock_quantity`, `low_stock_threshold`, `weight`, `is_featured`, `is_active`, `has_variants`, `supplier`, `shipping_info`, `meta_title`, `meta_description`, `created_at`, `updated_at`, `product_status`) VALUES (11, 'Nike Dri-FIT T-Shirt', 'nike-dri-fit-tshirt', 'Nike Dri-FIT technology moves sweat away from your body for quicker evaporation. Made with 100% recycled polyester fibers.', 'Stay dry and comfortable', 'NK-DFT-M', '0196236777777', 2, 3, 3999.0, 1800.0, NULL, 'fixed', 200, 10, 0.15, 0, 1, 0, NULL, NULL, NULL, NULL, '2026-06-23 18:14:25', '2026-06-23 18:14:25', 'active');
INSERT INTO `products` (`id`, `name`, `slug`, `description`, `short_description`, `sku`, `barcode`, `category_id`, `brand_id`, `price`, `cost_price`, `discount_price`, `discount_type`, `stock_quantity`, `low_stock_threshold`, `weight`, `is_featured`, `is_active`, `has_variants`, `supplier`, `shipping_info`, `meta_title`, `meta_description`, `created_at`, `updated_at`, `product_status`) VALUES (12, 'Samsung Galaxy Buds FE', 'samsung-galaxy-buds-fe', 'Samsung Galaxy Buds FE offer premium AKG sound, active noise cancellation, and up to 30 hours of battery life with the charging case.', 'Premium sound, accessible price', 'SAM-GBFE-BK', '8806091444444', 1, 1, 8999.0, 5500.0, NULL, 'fixed', 100, 10, 0.055, 1, 1, 0, NULL, NULL, NULL, NULL, '2026-06-23 18:14:25', '2026-06-23 18:14:25', 'active');

-- --------------------------------------------------
-- Table: promo_banners
-- --------------------------------------------------

CREATE TABLE `promo_banners` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `subtitle` VARCHAR(255),
  `cta_text` VARCHAR(255) DEFAULT 'Shop Now',
  `cta_link` VARCHAR(255) DEFAULT '/products',
  `bg_gradient` VARCHAR(255) DEFAULT 'from-amber-500 to-orange-600',
  `icon` VARCHAR(255),
  `position` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` VARCHAR(255),
  `updated_at` VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for table `promo_banners` (2 rows)
INSERT INTO `promo_banners` (`id`, `title`, `subtitle`, `cta_text`, `cta_link`, `bg_gradient`, `icon`, `position`, `is_active`, `created_at`, `updated_at`) VALUES (1, 'Flash Sale', 'Up to 50% off on selected electronics. Don\'t miss out!', 'Shop Sale', '/products?sale=1', 'from-orange-500 to-red-600', 'zap', 1, 1, '2026-06-26 12:24:54', '2026-06-26 12:24:54');
INSERT INTO `promo_banners` (`id`, `title`, `subtitle`, `cta_text`, `cta_link`, `bg_gradient`, `icon`, `position`, `is_active`, `created_at`, `updated_at`) VALUES (2, 'New Arrivals', 'Check out the latest additions to our collection.', 'Explore Now', '/products?sort=newest', 'from-amber-600 to-yellow-700', 'sparkles', 2, 1, '2026-06-26 12:24:54', '2026-06-26 12:24:54');

-- --------------------------------------------------
-- Table: purchase_orders
-- --------------------------------------------------

CREATE TABLE `purchase_orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `supplier_id` INT UNSIGNED,
  `po_number` VARCHAR(255) NOT NULL UNIQUE,
  `status` VARCHAR(255) DEFAULT 'pending',
  `total` DECIMAL(12,2) DEFAULT 0,
  `expected_date` VARCHAR(255),
  `notes` TEXT,
  `created_by` INT,
  `created_at` VARCHAR(255),
  `updated_at` VARCHAR(255),
  CONSTRAINT `fk_purchase_orders_users` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_purchase_orders_suppliers` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------
-- Table: reviews
-- --------------------------------------------------

CREATE TABLE `reviews` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `rating` INT NOT NULL DEFAULT 5,
  `title` VARCHAR(255),
  `review` TEXT,
  `is_approved` TINYINT(1) DEFAULT 1,
  `created_at` VARCHAR(255),
  CONSTRAINT `fk_reviews_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reviews_products` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for table `reviews` (20 rows)
INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `rating`, `title`, `review`, `is_approved`, `created_at`) VALUES (1, 8, 4, 4, 'Best purchase ever', 'This product exceeded my expectations. The quality is outstanding and delivery was fast. I would definitely recommend it to others.', 1, '2026-06-22 18:14:25');
INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `rating`, `title`, `review`, `is_approved`, `created_at`) VALUES (2, 7, 4, 3, 'Excellent quality', 'This product exceeded my expectations. The quality is outstanding and delivery was fast. I would definitely recommend it to others.', 1, '2026-06-21 18:14:25');
INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `rating`, `title`, `review`, `is_approved`, `created_at`) VALUES (3, 1, 4, 5, 'Very satisfied', 'This product exceeded my expectations. The quality is outstanding and delivery was fast. I would definitely recommend it to others.', 1, '2026-06-20 18:14:25');
INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `rating`, `title`, `review`, `is_approved`, `created_at`) VALUES (4, 12, 4, 3, 'Highly recommend', 'This product exceeded my expectations. The quality is outstanding and delivery was fast. I would definitely recommend it to others.', 1, '2026-06-19 18:14:25');
INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `rating`, `title`, `review`, `is_approved`, `created_at`) VALUES (5, 8, 4, 4, 'Very satisfied', 'This product exceeded my expectations. The quality is outstanding and delivery was fast. I would definitely recommend it to others.', 1, '2026-06-18 18:14:25');
INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `rating`, `title`, `review`, `is_approved`, `created_at`) VALUES (6, 2, 4, 4, 'Love it', 'This product exceeded my expectations. The quality is outstanding and delivery was fast. I would definitely recommend it to others.', 1, '2026-06-17 18:14:25');
INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `rating`, `title`, `review`, `is_approved`, `created_at`) VALUES (7, 2, 4, 4, 'Could be better', 'This product exceeded my expectations. The quality is outstanding and delivery was fast. I would definitely recommend it to others.', 1, '2026-06-16 18:14:25');
INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `rating`, `title`, `review`, `is_approved`, `created_at`) VALUES (8, 12, 4, 4, 'Amazing!', 'This product exceeded my expectations. The quality is outstanding and delivery was fast. I would definitely recommend it to others.', 1, '2026-06-15 18:14:25');
INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `rating`, `title`, `review`, `is_approved`, `created_at`) VALUES (9, 1, 4, 4, 'Worth the price', 'This product exceeded my expectations. The quality is outstanding and delivery was fast. I would definitely recommend it to others.', 1, '2026-06-14 18:14:25');
INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `rating`, `title`, `review`, `is_approved`, `created_at`) VALUES (10, 9, 4, 5, 'Could be better', 'This product exceeded my expectations. The quality is outstanding and delivery was fast. I would definitely recommend it to others.', 1, '2026-06-13 18:14:25');
INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `rating`, `title`, `review`, `is_approved`, `created_at`) VALUES (11, 2, 4, 3, 'Could be better', 'This product exceeded my expectations. The quality is outstanding and delivery was fast. I would definitely recommend it to others.', 1, '2026-06-12 18:14:25');
INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `rating`, `title`, `review`, `is_approved`, `created_at`) VALUES (12, 1, 4, 4, 'Best purchase ever', 'This product exceeded my expectations. The quality is outstanding and delivery was fast. I would definitely recommend it to others.', 1, '2026-06-11 18:14:25');
INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `rating`, `title`, `review`, `is_approved`, `created_at`) VALUES (13, 4, 4, 4, 'Great product!', 'This product exceeded my expectations. The quality is outstanding and delivery was fast. I would definitely recommend it to others.', 1, '2026-06-10 18:14:25');
INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `rating`, `title`, `review`, `is_approved`, `created_at`) VALUES (14, 1, 4, 5, 'Great product!', 'This product exceeded my expectations. The quality is outstanding and delivery was fast. I would definitely recommend it to others.', 1, '2026-06-09 18:14:25');
INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `rating`, `title`, `review`, `is_approved`, `created_at`) VALUES (15, 2, 4, 4, 'Amazing!', 'This product exceeded my expectations. The quality is outstanding and delivery was fast. I would definitely recommend it to others.', 1, '2026-06-08 18:14:25');
INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `rating`, `title`, `review`, `is_approved`, `created_at`) VALUES (16, 1, 4, 5, 'Very satisfied', 'This product exceeded my expectations. The quality is outstanding and delivery was fast. I would definitely recommend it to others.', 1, '2026-06-07 18:14:25');
INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `rating`, `title`, `review`, `is_approved`, `created_at`) VALUES (17, 1, 4, 3, 'Great product!', 'This product exceeded my expectations. The quality is outstanding and delivery was fast. I would definitely recommend it to others.', 1, '2026-06-06 18:14:25');
INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `rating`, `title`, `review`, `is_approved`, `created_at`) VALUES (18, 8, 4, 4, 'Great product!', 'This product exceeded my expectations. The quality is outstanding and delivery was fast. I would definitely recommend it to others.', 1, '2026-06-05 18:14:25');
INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `rating`, `title`, `review`, `is_approved`, `created_at`) VALUES (19, 4, 4, 3, 'Love it', 'This product exceeded my expectations. The quality is outstanding and delivery was fast. I would definitely recommend it to others.', 1, '2026-06-04 18:14:25');
INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `rating`, `title`, `review`, `is_approved`, `created_at`) VALUES (20, 8, 4, 4, 'Great product!', 'This product exceeded my expectations. The quality is outstanding and delivery was fast. I would definitely recommend it to others.', 1, '2026-06-03 18:14:25');

-- --------------------------------------------------
-- Table: settings
-- --------------------------------------------------

CREATE TABLE `settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(191) NOT NULL UNIQUE,
  `value` VARCHAR(255),
  `group_name` VARCHAR(255) DEFAULT 'general',
  `created_at` VARCHAR(255),
  `updated_at` VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for table `settings` (55 rows)
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (1, 'store_name', 'ShopSmart Ecommerce', 'general', '2026-06-23 18:14:25', '2026-06-23 18:14:25');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (2, 'store_tagline', 'AI-Powered Shopping Experience', 'general', '2026-06-23 18:14:25', '2026-06-23 18:14:25');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (3, 'store_email', 'info@shopsmart.co.ke', 'general', '2026-06-23 18:14:25', '2026-06-23 18:14:25');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (4, 'store_phone', '+254700000000', 'general', '2026-06-23 18:14:25', '2026-06-23 18:14:25');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (5, 'store_address', 'Nairobi CBD, Kenya', 'general', '2026-06-23 18:14:25', '2026-06-23 18:14:25');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (6, 'currency', 'KES', 'general', '2026-06-23 18:14:25', '2026-06-23 18:14:25');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (7, 'tax_rate', '16', 'general', '2026-06-23 18:14:25', '2026-06-23 18:14:25');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (8, 'mpesa_enabled', '1', 'payment', '2026-06-23 18:14:25', '2026-06-23 18:14:25');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (9, 'stripe_enabled', '0', 'payment', '2026-06-23 18:14:25', '2026-06-26 16:32:47');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (10, 'mpesa_passkey', '', 'payment', '2026-06-23 18:14:25', '2026-06-23 18:14:25');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (11, 'stripe_key', '', 'payment', '2026-06-23 18:14:25', '2026-06-23 18:14:25');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (12, 'facebook_connected', '0', 'social', '2026-06-23 18:14:25', '2026-06-23 18:14:25');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (13, 'whatsapp_connected', '0', 'social', '2026-06-23 18:14:25', '2026-06-23 18:14:25');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (14, 'smtp_host', '', 'email', '2026-06-23 18:14:25', '2026-06-23 18:14:25');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (15, 'smtp_port', '587', 'email', '2026-06-23 18:14:25', '2026-06-23 18:14:25');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (16, 'smtp_user', '', 'email', '2026-06-23 18:14:25', '2026-06-23 18:14:25');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (17, 'social_facebook', 'https://facebook.com/shopsmart', 'social', '2026-06-26 12:24:54', '2026-06-26 12:24:54');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (18, 'social_twitter', 'https://twitter.com/shopsmart', 'social', '2026-06-26 12:24:54', '2026-06-26 12:24:54');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (19, 'social_instagram', 'https://instagram.com/shopsmart', 'social', '2026-06-26 12:24:54', '2026-06-26 12:24:54');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (20, 'social_youtube', '', 'social', '2026-06-26 12:24:54', '2026-06-26 12:24:54');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (21, 'social_tiktok', '', 'social', '2026-06-26 12:24:54', '2026-06-26 12:24:54');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (22, 'hero_autoplay', '1', 'appearance', '2026-06-26 12:24:54', '2026-06-26 12:24:54');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (23, 'hero_interval', '5000', 'appearance', '2026-06-26 12:24:54', '2026-06-26 12:24:54');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (24, 'hero_animation', 'slide', 'appearance', '2026-06-26 12:24:54', '2026-06-26 12:24:54');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (25, 'show_categories', '1', 'appearance', '2026-06-26 12:24:54', '2026-06-26 12:24:54');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (26, 'show_featured', '1', 'appearance', '2026-06-26 12:24:54', '2026-06-26 12:24:54');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (27, 'show_new_arrivals', '1', 'appearance', '2026-06-26 12:24:54', '2026-06-26 12:24:54');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (28, 'show_promo_banners', '1', 'appearance', '2026-06-26 12:24:54', '2026-06-26 12:24:54');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (29, 'show_trust_badges', '1', 'appearance', '2026-06-26 12:24:54', '2026-06-26 12:24:54');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (30, 'show_newsletter', '1', 'appearance', '2026-06-26 12:24:54', '2026-06-26 12:24:54');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (31, 'newsletter_heading', 'Stay in the Loop', 'appearance', '2026-06-26 12:24:54', '2026-06-26 12:24:54');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (32, 'newsletter_subheading', 'Subscribe to our newsletter for exclusive deals and new arrivals.', 'appearance', '2026-06-26 12:24:54', '2026-06-26 12:24:54');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (33, 'mpesa_consumer_key', '', 'payment', '2026-06-26 12:24:54', '2026-06-26 12:24:54');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (34, 'mpesa_consumer_secret', '', 'payment', '2026-06-26 12:24:54', '2026-06-26 12:24:54');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (35, 'mpesa_shortcode', '174379', 'payment', '2026-06-26 12:24:54', '2026-06-26 12:24:54');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (36, 'mpesa_env', 'sandbox', 'payment', '2026-06-26 12:24:54', '2026-06-26 12:24:54');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (37, 'intasend_enabled', '0', 'payment', '2026-06-26 12:24:54', '2026-06-26 12:24:54');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (38, 'intasend_publishable', '', 'payment', '2026-06-26 12:24:54', '2026-06-26 12:24:54');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (39, 'intasend_secret', '', 'payment', '2026-06-26 12:24:54', '2026-06-26 12:24:54');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (40, 'paypal_enabled', '0', 'payment', '2026-06-26 12:24:54', '2026-06-26 12:24:54');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (41, 'paypal_client_id', 'AfPFXZ61FvxJ5XEgfkWzKzkj7bY7SsPTgs3R3NO2tdAqon2SeG-qb0cDnPtg4aawF5EMEZgvo-4ABjpZ', 'payment', '2026-06-26 12:24:54', '2026-06-26 16:32:36');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (42, 'paypal_secret', 'EJKhJdkdartdNRnJDcAnOjUFDWGB4iYsRQNq_SmNId7HJKb_ra0hiMdzpKVr_YU2Uc13vhUbMCe2tQn-', 'payment', '2026-06-26 12:24:54', '2026-06-26 16:32:36');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (43, 'paypal_env', 'production', 'payment', '2026-06-26 12:24:54', '2026-06-26 16:32:36');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (44, 'pesapal_enabled', '0', 'payment', '2026-06-26 12:24:54', '2026-06-26 12:24:54');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (45, 'pesapal_key', '', 'payment', '2026-06-26 12:24:54', '2026-06-26 12:24:54');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (46, 'pesapal_secret', '', 'payment', '2026-06-26 12:24:54', '2026-06-26 12:24:54');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (47, 'pesapal_env', 'sandbox', 'payment', '2026-06-26 12:24:54', '2026-06-26 12:24:54');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (49, 'stripe_secret', '', 'payment', '2026-06-26 12:24:54', '2026-06-26 12:24:54');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (50, 'stripe_webhook_secret', '', 'payment', '2026-06-26 12:24:54', '2026-06-26 12:24:54');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (51, 'mpesa_callback', '', 'payments', '2026-06-26 16:31:58', '2026-06-26 16:31:58');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (52, 'intasend_key', '', 'payments', '2026-06-26 16:31:58', '2026-06-26 16:31:58');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (53, 'paypal_webhook_id', '', 'payments', '2026-06-26 16:31:58', '2026-06-26 16:32:36');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (54, 'pesapal_ipn_id', '', 'payments', '2026-06-26 16:31:58', '2026-06-26 16:31:58');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (55, 'paypal_currency', 'USD', 'payments', '2026-06-26 16:31:58', '2026-06-26 16:32:36');
INSERT INTO `settings` (`id`, `key`, `value`, `group_name`, `created_at`, `updated_at`) VALUES (56, 'paypal_exchange_rate', '0.001', 'payments', '2026-06-26 16:31:58', '2026-06-26 16:32:36');

-- --------------------------------------------------
-- Table: shipping_zones
-- --------------------------------------------------

CREATE TABLE `shipping_zones` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `region` VARCHAR(255),
  `base_fee` DECIMAL(12,2) DEFAULT 0,
  `free_above` DECIMAL(12,2) DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `sort_order` INT DEFAULT 0,
  `created_at` VARCHAR(255),
  `updated_at` VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for table `shipping_zones` (1 rows)
INSERT INTO `shipping_zones` (`id`, `name`, `region`, `base_fee`, `free_above`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES (1, 'Default', 'Kenya (Nationwide)', 300.0, 5000.0, 1, 1, '2026-06-26 12:24:54', '2026-06-26 12:24:54');

-- --------------------------------------------------
-- Table: stock_adjustments
-- --------------------------------------------------

CREATE TABLE `stock_adjustments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT UNSIGNED NOT NULL,
  `quantity` INT NOT NULL,
  `reason` VARCHAR(255),
  `adjusted_by` INT,
  `created_at` VARCHAR(255),
  CONSTRAINT `fk_stock_adjustments_users` FOREIGN KEY (`adjusted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_stock_adjustments_products` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------
-- Table: suppliers
-- --------------------------------------------------

CREATE TABLE `suppliers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `contact_person` VARCHAR(255),
  `email` VARCHAR(255),
  `phone` VARCHAR(255),
  `address` TEXT,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` VARCHAR(255),
  `updated_at` VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for table `suppliers` (2 rows)
INSERT INTO `suppliers` (`id`, `name`, `contact_person`, `email`, `phone`, `address`, `is_active`, `created_at`, `updated_at`) VALUES (1, 'Samsung East Africa', 'James Mwangi', 'orders@samsung-ea.com', '+254700000200', NULL, 1, '2026-06-23 18:14:25', '2026-06-23 18:14:25');
INSERT INTO `suppliers` (`id`, `name`, `contact_person`, `email`, `phone`, `address`, `is_active`, `created_at`, `updated_at`) VALUES (2, 'Apple Authorized Distributor', 'Sarah Wanjiku', 'supply@apple-dist.co.ke', '+254700000201', NULL, 1, '2026-06-23 18:14:25', '2026-06-23 18:14:25');

-- --------------------------------------------------
-- Table: transactions
-- --------------------------------------------------

CREATE TABLE `transactions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT UNSIGNED,
  `payment_method` VARCHAR(255) NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `currency` VARCHAR(255) DEFAULT 'KES',
  `reference` VARCHAR(255),
  `status` VARCHAR(255) DEFAULT 'pending',
  `gateway` VARCHAR(255),
  `gateway_response` TEXT,
  `created_at` VARCHAR(255),
  CONSTRAINT `fk_transactions_orders` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------
-- Table: trust_badges
-- --------------------------------------------------

CREATE TABLE `trust_badges` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `subtitle` VARCHAR(255),
  `icon_name` VARCHAR(255) DEFAULT 'truck',
  `sort_order` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for table `trust_badges` (4 rows)
INSERT INTO `trust_badges` (`id`, `title`, `subtitle`, `icon_name`, `sort_order`, `is_active`) VALUES (1, 'Free Shipping', 'On orders over KSh 5,000', 'truck', 1, 1);
INSERT INTO `trust_badges` (`id`, `title`, `subtitle`, `icon_name`, `sort_order`, `is_active`) VALUES (2, 'Secure Payment', 'M-Pesa, Card & more', 'shield-check', 2, 1);
INSERT INTO `trust_badges` (`id`, `title`, `subtitle`, `icon_name`, `sort_order`, `is_active`) VALUES (3, '24/7 Support', 'We\'re here to help', 'headphones', 3, 1);
INSERT INTO `trust_badges` (`id`, `title`, `subtitle`, `icon_name`, `sort_order`, `is_active`) VALUES (4, 'Easy Returns', '30-day return policy', 'refresh-cw', 4, 1);

-- --------------------------------------------------
-- Table: users
-- --------------------------------------------------

CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` VARCHAR(255) NOT NULL DEFAULT 'customer',
  `phone` VARCHAR(255),
  `address` TEXT,
  `city` VARCHAR(255),
  `country` VARCHAR(255) DEFAULT 'Kenya',
  `avatar` VARCHAR(255),
  `is_active` TINYINT(1) DEFAULT 1,
  `email_verified_at` VARCHAR(255),
  `last_login` VARCHAR(255),
  `two_factor_secret` VARCHAR(255),
  `created_at` VARCHAR(255),
  `updated_at` VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for table `users` (4 rows)
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `phone`, `address`, `city`, `country`, `avatar`, `is_active`, `email_verified_at`, `last_login`, `two_factor_secret`, `created_at`, `updated_at`) VALUES (1, 'Super Admin', 'admin@ecommerce.com', '$2y$12$1zhkI6AaWWdBU7EppQhgMOHAZCXol0FwwweE1O0Xa8pcGtm28uDDq', 'super_admin', '+254700000001', NULL, NULL, 'Kenya', NULL, 1, NULL, NULL, NULL, '2026-06-23 18:14:24', '2026-06-23 18:14:24');
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `phone`, `address`, `city`, `country`, `avatar`, `is_active`, `email_verified_at`, `last_login`, `two_factor_secret`, `created_at`, `updated_at`) VALUES (2, 'Store Manager', 'manager@ecommerce.com', '$2y$12$SaMQwNY092FEqc.ODugiX.OLhCsle0QgQPGCrdWXe5KFAKtVYcqeS', 'admin', '+254700000002', NULL, NULL, 'Kenya', NULL, 1, NULL, NULL, NULL, '2026-06-23 18:14:24', '2026-06-23 18:14:24');
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `phone`, `address`, `city`, `country`, `avatar`, `is_active`, `email_verified_at`, `last_login`, `two_factor_secret`, `created_at`, `updated_at`) VALUES (3, 'John Cashier', 'cashier@ecommerce.com', '$2y$12$yF3I8e1sszNHWQXVMdYjpusUZAEqQ2lc0ZtHpjcGYEB/QeRs6Pfs6', 'cashier', '+254700000003', NULL, NULL, 'Kenya', NULL, 1, NULL, NULL, NULL, '2026-06-23 18:14:24', '2026-06-23 18:14:24');
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `phone`, `address`, `city`, `country`, `avatar`, `is_active`, `email_verified_at`, `last_login`, `two_factor_secret`, `created_at`, `updated_at`) VALUES (4, 'Jane Customer', 'jane@example.com', '$2y$12$wFO52dZMLqBrfsR4m3Q4XOkdM6gKWq9LiBBRq/ZYJ.x9/m3MsTSR6', 'customer', '+254712345678', NULL, NULL, 'Kenya', NULL, 1, NULL, NULL, NULL, '2026-06-23 18:14:25', '2026-06-23 18:14:25');

-- --------------------------------------------------
-- Table: wishlists
-- --------------------------------------------------

CREATE TABLE `wishlists` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `created_at` VARCHAR(255),
  UNIQUE KEY `uniq_wishlists_user_id_product_id` (`user_id`, `product_id`),
  CONSTRAINT `fk_wishlists_products` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wishlists_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


SET FOREIGN_KEY_CHECKS = 1;
