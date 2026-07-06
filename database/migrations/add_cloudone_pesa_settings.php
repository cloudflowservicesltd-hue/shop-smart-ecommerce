<?php
/**
 * Migration: Add CloudOne Pesa API settings
 */
return function ($db) {
    $settings = [
        ['cloudone_public_key', 'pk_e0da32787ef75753d83e2d73930f5d1a781c2fad987518b0', 'payment'],
        ['cloudone_secret_key', 'sk_632fdb502bc16febecb23550152c3ef02ad6f1ce416960d39197dffc50c1a40e', 'payment'],
        ['cloudone_api_url', 'https://pesa.cloudonehost.top/api/v1/stk/push', 'payment'],
        ['cloudone_callback_url', '', 'payment'],
        ['cloudone_enabled', '1', 'payment'],
    ];

    foreach ($settings as [$key, $value, $group]) {
        $exists = $db->query("SELECT COUNT(*) FROM settings WHERE `key` = '{$key}'")->fetchColumn();
        if (!$exists) {
            $db->exec("INSERT INTO settings (`key`, value, group_name, created_at, updated_at) VALUES ('{$key}', '{$value}', '{$group}', datetime('now'), datetime('now'))");
        }
    }
};