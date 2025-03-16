# Mobile Wallet ZM

[![Latest Version on Packagist](https://img.shields.io/packagist/v/Mak8Tech/mobile-wallet-zm.svg?style=flat-square)](https://packagist.org/packages/Mak8Tech/mobile-wallet-zm)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/Mak8Tech/mobile-wallet-zm/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/Mak8Tech/mobile-wallet-zm/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/Mak8Tech/mobile-wallet-zm/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/Mak8Tech/mobile-wallet-zm/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/Mak8Tech/mobile-wallet-zm.svg?style=flat-square)](https://packagist.org/packages/Mak8Tech/mobile-wallet-zm)

A comprehensive Laravel package for integrating mobile money payment services in Zambia. This package supports MTN Mobile Money, Airtel Money, and Zamtel Kwacha, providing a unified API for all three providers.

## Features

- **Multi-provider Support**: Seamlessly integrate with MTN, Airtel, and Zamtel
- **Laravel Integration**: Works with Laravel 12+ and includes a service provider, facade, and middleware
- **Inertia.js & React Components**: Ready-to-use TypeScript React components for payment forms
- **Transaction Management**: Complete lifecycle management for payment transactions
- **Webhook Handling**: Process payment notifications from all providers
- **Configuration**: Flexible configuration options for each provider

## Installation

You can install the package via composer:

```bash
composer require mak8tech/mobile-wallet-zm
```

After installation, run the package installation command:

```bash
php artisan mobile-wallet:install
```

This will publish the necessary configuration files, migrations, and frontend components.

### Manual Setup

You can also publish and run the migrations manually:

```bash
php artisan vendor:publish --tag="mobile-wallet-migrations"
php artisan migrate
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="mobile-wallet-config"
```

Publish the frontend components:

```bash
php artisan vendor:publish --tag="mobile-wallet-assets"
```

## Configuration

After publishing the configuration file, you can find it at `config/mobile_wallet.php`. You'll need to set up your API credentials for each provider:

```php
return [
    // Default mobile money provider
    'default' => env('MOBILE_WALLET_PROVIDER', 'mtn'),

    // Default currency
    'currency' => env('MOBILE_WALLET_CURRENCY', 'ZMW'),

    // Default country code
    'country_code' => env('MOBILE_WALLET_COUNTRY_CODE', 'ZM'),

    // MTN MoMo Configuration
    'mtn' => [
        'base_url' => env('MTN_API_BASE_URL', 'https://sandbox.momodeveloper.mtn.com'),
        'api_key' => env('MTN_API_KEY'),
        'api_secret' => env('MTN_API_SECRET'),
        'collection_subscription_key' => env('MTN_COLLECTION_SUBSCRIPTION_KEY'),
        'disbursement_subscription_key' => env('MTN_DISBURSEMENT_SUBSCRIPTION_KEY'),
        'environment' => env('MTN_ENVIRONMENT', 'sandbox'),
    ],

    // Airtel Money Configuration
    'airtel' => [
        'base_url' => env('AIRTEL_API_BASE_URL', 'https://openapi.airtel.africa'),
        'api_key' => env('AIRTEL_API_KEY'),
        'api_secret' => env('AIRTEL_API_SECRET'),
        'environment' => env('AIRTEL_ENVIRONMENT', 'sandbox'),
    ],

    // Zamtel Kwacha Configuration
    'zamtel' => [
        'base_url' => env('ZAMTEL_API_BASE_URL', 'https://api.zamtel.com/kwacha'),
        'api_key' => env('ZAMTEL_API_KEY'),
        'api_secret' => env('ZAMTEL_API_SECRET'),
        'environment' => env('ZAMTEL_ENVIRONMENT', 'sandbox'),
    ],

    // Webhook Configuration
    'webhook' => [
        'secret' => env('MOBILE_WALLET_WEBHOOK_SECRET'),
        'url_path' => env('MOBILE_WALLET_WEBHOOK_PATH', 'api/mobile-wallet/webhook'),
    ],
];
```

Add the corresponding environment variables to your `.env` file:

```
# Mobile Wallet General Config
MOBILE_WALLET_PROVIDER=mtn
MOBILE_WALLET_CURRENCY=ZMW
MOBILE_WALLET_COUNTRY_CODE=ZM
MOBILE_WALLET_WEBHOOK_SECRET=your-webhook-secret

# MTN MoMo Config
MTN_API_BASE_URL=https://sandbox.momodeveloper.mtn.com
MTN_API_KEY=your-mtn-api-key
MTN_API_SECRET=your-mtn-api-secret
MTN_COLLECTION_SUBSCRIPTION_KEY=your-mtn-collection-key
MTN_ENVIRONMENT=sandbox

# Airtel Money Config
AIRTEL_API_BASE_URL=https://openapi.airtel.africa
AIRTEL_API_KEY=your-airtel-api-key
AIRTEL_API_SECRET=your-airtel-api-secret
AIRTEL_ENVIRONMENT=sandbox

# Zamtel Kwacha Config
ZAMTEL_API_BASE_URL=https://api.zamtel.com/kwacha
ZAMTEL_API_KEY=your-zamtel-api-key
ZAMTEL_API_SECRET=your-zamtel-api-secret
ZAMTEL_ENVIRONMENT=sandbox
```

## Usage

### Backend Usage

You can use the facade to interact with the default provider:

```php
use Mak8Tech\MobileWalletZm\Facades\MobileWallet;

// Request a payment
$result = MobileWallet::requestPayment(
    '0977123456',  // Phone number
    100.00,        // Amount
    'REF123',      // Reference (optional)
    'Payment for order #123' // Narration (optional)
);

// Check transaction status
$status = MobileWallet::checkTransactionStatus($transactionId);
```

To use a specific provider:

```php
// Using a specific provider
$result = MobileWallet::provider('airtel')->requestPayment(
    '0977123456',
    100.00
);
```

### Frontend Usage

The package includes a React component for payment forms. After publishing the assets, you can import and use the component:

```tsx
import { PaymentForm } from '@/vendor/mobile-wallet-zm/Components/PaymentForm';

function CheckoutPage() {
    const handleSuccess = (data) => {
        console.log('Payment initiated', data);
        // Handle successful payment initiation
    };

    const handleError = (error) => {
        console.error('Payment failed', error);
        // Handle payment error
    };

    return (
        <div>
            <h1>Checkout</h1>
            <PaymentForm 
                amount={100.00}
                onSuccess={handleSuccess}
                onError={handleError}
                providers={[
                    { id: 'mtn', name: 'MTN Mobile Money' },
                    { id: 'airtel', name: 'Airtel Money' },
                    { id: 'zamtel', name: 'Zamtel Kwacha' },
                ]}
            />
        </div>
    );
}
```

### Handling Webhooks

The package automatically registers routes to handle webhooks from all providers. Make sure your webhook URL is properly configured in your provider dashboard:

```
https://your-app.com/api/mobile-wallet/webhook/mtn
https://your-app.com/api/mobile-wallet/webhook/airtel
https://your-app.com/api/mobile-wallet/webhook/zamtel
```

You can also use the provider-agnostic endpoint:

```
https://your-app.com/api/mobile-wallet/webhook
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

-   [Innocent Makusa](https://github.com/makusa-the)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
