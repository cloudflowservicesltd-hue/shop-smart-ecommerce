<?php

return [
    'name'            => getenv('APP_NAME') ?: 'AI-Powered Ecommerce & POS',
    'env'             => getenv('APP_ENV') ?: 'development',
    'debug'           => filter_var(getenv('APP_DEBUG') ?: true, FILTER_VALIDATE_BOOLEAN),
    'url'             => getenv('APP_URL') ?: '',
    'timezone'        => getenv('APP_TIMEZONE') ?: 'Africa/Nairobi',
    'currency'        => getenv('APP_CURRENCY') ?: 'KES',
    'currency_symbol' => getenv('APP_CURRENCY_SYMBOL') ?: 'KSh',

    // Database
    'database' => [
        'driver'    => 'mysql',
        'host'      => getenv('DB_HOST') ?: '127.0.0.1',
        'port'      => getenv('DB_PORT') ?: '3306',
        'database'  => getenv('DB_DATABASE') ?: 'shopsmart',
        'username'  => getenv('DB_USERNAME') ?: 'root',
        'password'  => getenv('DB_PASSWORD') ?: '',
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix'    => '',
    ],

    // Session
    'session' => [
        'lifetime'  => (int)(getenv('SESSION_LIFETIME') ?: 7200),
        'name'      => getenv('SESSION_NAME') ?: 'ecommerce_session',
        'save_path' => dirname(__DIR__) . '/storage/sessions',
    ],

    // Upload
    'upload' => [
        'path'          => dirname(__DIR__) . '/public/uploads/',
        'max_size'      => (int)(getenv('UPLOAD_MAX_SIZE') ?: (5 * 1024 * 1024)),
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
    ],

    // Pagination
    'per_page' => 12,

    // Mail (SMTP)
    'mail' => [
        'host'       => getenv('MAIL_HOST') ?: '',
        'port'       => (int)(getenv('MAIL_PORT') ?: 587),
        'username'   => getenv('MAIL_USERNAME') ?: '',
        'password'   => getenv('MAIL_PASSWORD') ?: '',
        'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',
        'from_name'  => getenv('MAIL_FROM_NAME') ?: 'ShopSmart',
        'from_email' => getenv('MAIL_FROM_EMAIL') ?: '',
    ],

    // AI
    'ai' => [
        'enabled' => filter_var(getenv('AI_ENABLED') ?: true, FILTER_VALIDATE_BOOLEAN),
        'model'   => getenv('AI_MODEL') ?: 'gpt-4',
    ],
];