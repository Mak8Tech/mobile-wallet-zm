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

    'webhook' => [
        'secret' => env('MOBILE_WALLET_WEBHOOK_SECRET'),
        'url_path' => env('MOBILE_WALLET_WEBHOOK_PATH', 'api/mobile-wallet/webhook'),
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