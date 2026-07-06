<?php
// Migration: Add payment method settings for all 5 gateways
// Uses canonical key names matching admin UI and payment API

if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(dirname(__DIR__)));
if (!class_exists('Database')) require_once ROOT_PATH . '/app/Core/Database.php';

// Idempotency: skip if already migrated
try {
    $db = Database::getConnection();
    $db->exec("CREATE TABLE IF NOT EXISTS `migrations` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `name` VARCHAR(255) NOT NULL UNIQUE, `ran_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $ran = Database::selectOne("SELECT id FROM migrations WHERE name = ?", ['add_payment_methods']);
    if ($ran) { echo "Already migrated: add_payment_methods\n"; return; }
} catch (\Throwable $e) {}

$paymentSettings = [
    // M-Pesa
    ['mpesa_enabled', '1', 'payments'],
    ['mpesa_env', 'sandbox', 'payments'],
    ['mpesa_consumer_key', '', 'payments'],
    ['mpesa_consumer_secret', '', 'payments'],
    ['mpesa_shortcode', '', 'payments'],
    ['mpesa_passkey', '', 'payments'],
    ['mpesa_callback', '', 'payments'],
    // Stripe
    ['stripe_enabled', '0', 'payments'],
    ['stripe_key', '', 'payments'],
    ['stripe_secret', '', 'payments'],
    ['stripe_webhook_secret', '', 'payments'],
    // IntaSend
    ['intasend_enabled', '0', 'payments'],
    ['intasend_key', '', 'payments'],
    ['intasend_secret', '', 'payments'],
    ['intasend_publishable', '', 'payments'],
    // PesaPal
    ['pesapal_enabled', '0', 'payments'],
    ['pesapal_env', 'sandbox', 'payments'],
    ['pesapal_key', '', 'payments'],
    ['pesapal_secret', '', 'payments'],
    ['pesapal_ipn_id', '', 'payments'],
    // PayPal
    ['paypal_enabled', '0', 'payments'],
    ['paypal_env', 'sandbox', 'payments'],
    ['paypal_currency', 'USD', 'payments'],
    ['paypal_exchange_rate', '0.0077', 'payments'],
    ['paypal_client_id', '', 'payments'],
    ['paypal_secret', '', 'payments'],
    ['paypal_webhook_id', '', 'payments'],
];

foreach ($paymentSettings as $s) {
    $existing = Database::selectOne("SELECT id FROM settings WHERE `key` = ?", [$s[0]]);
    if (!$existing) {
        Database::insert('settings', [
            'key'        => $s[0],
            'value'      => $s[1],
            'group_name' => $s[2],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}

// Register migration
try {
    Database::insert('migrations', ['name' => 'add_payment_methods', 'ran_at' => date('Y-m-d H:i:s')]);
} catch (\Throwable $e) {}

echo "Migration add_payment_methods completed.\n";