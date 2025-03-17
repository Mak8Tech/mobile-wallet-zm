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
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    */
    'rate_limit_decay_minutes' => env('MOBILE_WALLET_RATE_LIMIT_DECAY_MINUTES', 1),
    
    'rate_limits' => [
        'default' => env('MOBILE_WALLET_RATE_LIMIT_DEFAULT', 60),
        'payment' => env('MOBILE_WALLET_RATE_LIMIT_PAYMENT', 30),
        'status' => env('MOBILE_WALLET_RATE_LIMIT_STATUS', 120),
        'webhook' => env('MOBILE_WALLET_RATE_LIMIT_WEBHOOK', 200),
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
    ],

    /*
    |--------------------------------------------------------------------------
    | Transaction Database Settings
    |--------------------------------------------------------------------------
    */
    'database' => [
        'connection' => env('MOBILE_WALLET_DB_CONNECTION', config('database.default')),
        'table' => env('MOBILE_WALLET_TABLE', 'mobile_wallet_transactions'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Callback/Webhook URLs
    |--------------------------------------------------------------------------
    */
    'callback_url' => env('MOBILE_WALLET_CALLBACK_URL', 'http://your-app.com/api/mobile-wallet/callback'),
];
