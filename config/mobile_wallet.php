<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mobile Wallet General Configuration
    |--------------------------------------------------------------------------
    */
    'default' => env('MOBILE_WALLET_PROVIDER', 'mtn'),

    'currency' => env('MOBILE_WALLET_CURRENCY', 'ZMW'),

    'country_code' => env('MOBILE_WALLET_COUNTRY_CODE', 'ZM'),

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    */
    'verify_webhook_signatures' => env('MOBILE_WALLET_VERIFY_SIGNATURES', true),

    'bypass_signature_verification_in_testing' => env('MOBILE_WALLET_BYPASS_SIGNATURES_IN_TESTING', true),

    'api_token' => env('MOBILE_WALLET_API_TOKEN'),

    'webhook' => [
        'secret' => env('MOBILE_WALLET_WEBHOOK_SECRET'),
        'url_path' => env('MOBILE_WALLET_WEBHOOK_PATH', 'api/mobile-wallet/webhook'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Authorization Configuration
    |--------------------------------------------------------------------------
    */
    'admin' => [
        'disable_authorization' => env('MOBILE_WALLET_DISABLE_ADMIN_AUTH', false),
        'login_route' => 'login',
        'permissions' => [
            'mobile-wallet.admin.access',
            'mobile-wallet.transactions.view',
            'mobile-wallet.transactions.manage',
            'mobile-wallet.settings.view',
            'mobile-wallet.settings.manage',
        ],
        // Optional callbacks that can be defined by the application
        'super_admin_check' => null,  // function($user) { return $user->isAdmin(); }
        'permission_check' => null,   // function($user, $permission) { return $user->can($permission); }
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    */
    'rate_limit_decay_minutes' => env('MOBILE_WALLET_RATE_LIMIT_DECAY_MINUTES', 1),

    'rate_limits' => [
        'default' => env('MOBILE_WALLET_RATE_LIMIT_DEFAULT', 60),
        'payment' => env('MOBILE_WALLET_RATE_LIMIT_PAYMENT', 30),
        'status' => env('MOBILE_WALLET_RATE_LIMIT_STATUS', 120),
        'webhook' => env('MOBILE_WALLET_RATE_LIMIT_WEBHOOK', 200),
        'admin' => env('MOBILE_WALLET_RATE_LIMIT_ADMIN', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => env('MOBILE_WALLET_CACHE_ENABLED', true),
        'store' => env('MOBILE_WALLET_CACHE_STORE', null), // null means the default cache store
        'ttl' => env('MOBILE_WALLET_CACHE_TTL', 3600), // Default: 1 hour
        'prefix' => env('MOBILE_WALLET_CACHE_PREFIX', 'mobile_wallet_'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    */
    'database' => [
        'connection' => env('MOBILE_WALLET_DB_CONNECTION', config('database.default')),
        'table' => env('MOBILE_WALLET_TABLE', 'mobile_wallet_transactions'),
        'indexes' => [
            'provider_index' => true,
            'status_index' => true,
            'transaction_id_index' => true,
            'phone_number_index' => true,
            'created_at_index' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | MTN MoMo Configuration
    |--------------------------------------------------------------------------
    */
    'mtn' => [
        'base_url' => env('MTN_API_BASE_URL', 'https://sandbox.momodeveloper.mtn.com'),
        'api_key' => env('MTN_API_KEY'),
        'api_secret' => env('MTN_API_SECRET'),
        'collection_subscription_key' => env('MTN_COLLECTION_SUBSCRIPTION_KEY'),
        'disbursement_subscription_key' => env('MTN_DISBURSEMENT_SUBSCRIPTION_KEY'),
        'environment' => env('MTN_ENVIRONMENT', 'sandbox'),
        'token_ttl' => env('MTN_TOKEN_TTL', 3600), // Default: 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Airtel Money Configuration
    |--------------------------------------------------------------------------
    */
    'airtel' => [
        'base_url' => env('AIRTEL_API_BASE_URL', 'https://openapi.airtel.africa'),
        'api_key' => env('AIRTEL_API_KEY'),
        'api_secret' => env('AIRTEL_API_SECRET'),
        'environment' => env('AIRTEL_ENVIRONMENT', 'sandbox'),
        'token_ttl' => env('AIRTEL_TOKEN_TTL', 3600), // Default: 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Zamtel Kwacha Configuration
    |--------------------------------------------------------------------------
    */
    'zamtel' => [
        'base_url' => env('ZAMTEL_API_BASE_URL', 'https://api.zamtel.com/kwacha'),
        'api_key' => env('ZAMTEL_API_KEY'),
        'api_secret' => env('ZAMTEL_API_SECRET'),
        'environment' => env('ZAMTEL_ENVIRONMENT', 'sandbox'),
        'token_ttl' => env('ZAMTEL_TOKEN_TTL', 3600), // Default: 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | API Request Settings
    |--------------------------------------------------------------------------
    */
    'request' => [
        'timeout' => env('MOBILE_WALLET_REQUEST_TIMEOUT', 30),
        'retries' => env('MOBILE_WALLET_REQUEST_RETRIES', 3),
        'retry_delay' => env('MOBILE_WALLET_REQUEST_RETRY_DELAY', 1000), // in milliseconds
        'backoff_multiplier' => env('MOBILE_WALLET_REQUEST_BACKOFF_MULTIPLIER', 2),
        'max_retry_delay' => env('MOBILE_WALLET_REQUEST_MAX_RETRY_DELAY', 10000), // 10 seconds max
    ],

    /*
    |--------------------------------------------------------------------------
    | Callback/Webhook URLs
    |--------------------------------------------------------------------------
    */
    'callback_url' => env('MOBILE_WALLET_CALLBACK_URL', 'http://your-app.com/api/mobile-wallet/callback'),
];
