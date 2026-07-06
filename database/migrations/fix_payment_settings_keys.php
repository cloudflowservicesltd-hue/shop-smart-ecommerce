<?php
// Migration: Fix payment settings key names to match the admin UI and payment API
// This maps old migration keys to the canonical key names used everywhere else.

if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(dirname(__DIR__)));
if (!class_exists('Database')) require_once ROOT_PATH . '/app/Core/Database.php';

// Idempotency: skip if already migrated
try {
    $db = Database::getConnection();
    $db->exec("CREATE TABLE IF NOT EXISTS `migrations` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `name` VARCHAR(255) NOT NULL UNIQUE, `ran_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $ran = Database::selectOne("SELECT id FROM migrations WHERE name = ?", ['fix_payment_settings_keys']);
    if ($ran) { echo "Already migrated: fix_payment_settings_keys\n"; return; }
} catch (\Throwable $e) {}

// Key rename map: old_key => new_key
$keyRenames = [
    'mpesa_business_short_code' => 'mpesa_shortcode',
    'mpesa_environment'         => 'mpesa_env',
    'stripe_publishable_key'    => 'stripe_key',
    'stripe_secret_key'         => 'stripe_secret',
    'intasend_publishable_key'  => 'intasend_publishable',
    'intasend_secret_key'       => 'intasend_secret',
    'paypal_mode'               => 'paypal_env',
    'pesapal_consumer_key'      => 'pesapal_key',
    'pesapal_consumer_secret'   => 'pesapal_secret',
    'pesapal_environment'       => 'pesapal_env',
];

foreach ($keyRenames as $oldKey => $newKey) {
    $oldRow = Database::selectOne("SELECT id, value, group_name FROM settings WHERE `key` = ?", [$oldKey]);
    $newRow = Database::selectOne("SELECT id FROM settings WHERE `key` = ?", [$newKey]);

    if ($oldRow && !$newRow) {
        // Old key exists, new key doesn't — rename it
        Database::update('settings', ['key' => $newKey], 'id = ?', [$oldRow['id']]);
    } elseif ($oldRow && $newRow) {
        // Both exist — delete old, keep new (new might have user-saved values)
        Database::delete('settings', 'id = ?', [$oldRow['id']]);
    }
    // If only new exists, nothing to do
}

// Missing keys that were never in the old migration — insert with defaults
$missingKeys = [
    ['mpesa_callback', '', 'payments'],
    ['intasend_key', '', 'payments'],
    ['paypal_webhook_id', '', 'payments'],
    ['pesapal_ipn_id', '', 'payments'],
];

foreach ($missingKeys as $item) {
    $existing = Database::selectOne("SELECT id FROM settings WHERE `key` = ?", [$item[0]]);
    if (!$existing) {
        Database::insert('settings', [
            'key'        => $item[0],
            'value'      => $item[1],
            'group_name' => $item[2],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}

// Also ensure all standard payment enabled/credential keys exist
// (handles the case where the old migration was never run at all)
$allKeys = [
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

foreach ($allKeys as $item) {
    $existing = Database::selectOne("SELECT id FROM settings WHERE `key` = ?", [$item[0]]);
    if (!$existing) {
        Database::insert('settings', [
            'key'        => $item[0],
            'value'      => $item[1],
            'group_name' => $item[2],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}

// Register migration
try {
    Database::insert('migrations', ['name' => 'fix_payment_settings_keys', 'ran_at' => date('Y-m-d H:i:s')]);
} catch (\Throwable $e) {}

echo "Migration fix_payment_settings_keys completed.\n";