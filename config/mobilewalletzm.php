<?php

return [
    'mtn' => [
        'api_url' => env('MTN_API_URL', 'https://sandbox.momodeveloper.mtn.com'),
        'api_key' => env('MTN_API_KEY'),
        'api_secret' => env('MTN_API_SECRET'),
        'environment' => env('MTN_ENVIRONMENT', 'sandbox'),
        'currency' => 'ZMW', // Default currency
    ],

    'airtel' => [
        'api_url' => env('AIRTEL_API_URL', 'https://openapi.airtel.africa'),
        'api_key' => env('AIRTEL_API_KEY'),
        'api_secret' => env('AIRTEL_API_SECRET'),
        'currency' => 'ZMW', // Default currency
    ],

    'zamtel' => [
        'api_url' => env('ZAMTEL_API_URL', 'https://api.zamtel.com/kwacha'),
        'api_key' => env('ZAMTEL_API_KEY'),
        'api_secret' => env('ZAMTEL_API_SECRET'),
        'currency' => 'ZMW', // Default currency
    ],
];
