# Mobile Wallet ZM - Installation Guide

This guide will walk you through the process of installing and configuring the Mobile Wallet ZM package for your Laravel application.

## Requirements

- PHP 8.1 or higher
- Laravel 9.0 or higher
- Composer
- Database (MySQL, PostgreSQL, SQLite)
- Node.js and NPM (for frontend components)

## Installation

### 1. Install the Package

You can install the package via Composer:

```bash
composer require mak8tech/mobile-wallet-zm
```

### 2. Publish the Assets

After installing the package, you need to publish the configuration file, migrations, and frontend assets:

```bash
php artisan mobile-wallet:install
```

This command will:
- Publish the configuration file to `config/mobile_wallet.php`
- Publish the migrations to `database/migrations`
- Publish the frontend assets to `resources/js/vendor/mobile-wallet-zm`
- Publish the routes to `routes/mobile-wallet.php` and `routes/mobile-wallet-admin.php`

Alternatively, you can publish the assets manually:

```bash
# Publish only the configuration
php artisan vendor:publish --tag=mobile-wallet-config

# Publish only the migrations
php artisan vendor:publish --tag=mobile-wallet-migrations

# Publish only the frontend assets
php artisan vendor:publish --tag=mobile-wallet-assets

# Publish only the routes
php artisan vendor:publish --tag=mobile-wallet-routes
php artisan vendor:publish --tag=mobile-wallet-admin-routes
```

### 3. Run the Migrations

Run the migrations to create the necessary database tables:

```bash
php artisan migrate
```

### 4. Configure Environment Variables

Add the following environment variables to your `.env` file:

```
# General Configuration
MOBILE_WALLET_PROVIDER=mtn
MOBILE_WALLET_CURRENCY=ZMW
MOBILE_WALLET_COUNTRY_CODE=ZM
MOBILE_WALLET_CALLBACK_URL=https://your-app.com/api/mobile-wallet/callback

# Security Configuration
MOBILE_WALLET_VERIFY_SIGNATURES=true
MOBILE_WALLET_API_TOKEN=your-api-token
MOBILE_WALLET_WEBHOOK_SECRET=your-webhook-secret

# MTN Configuration
MTN_API_BASE_URL=https://sandbox.momodeveloper.mtn.com
MTN_API_KEY=your-mtn-api-key
MTN_API_SECRET=your-mtn-api-secret
MTN_COLLECTION_SUBSCRIPTION_KEY=your-mtn-collection-subscription-key
MTN_DISBURSEMENT_SUBSCRIPTION_KEY=your-mtn-disbursement-subscription-key
MTN_ENVIRONMENT=sandbox

# Airtel Configuration
AIRTEL_API_BASE_URL=https://openapi.airtel.africa
AIRTEL_API_KEY=your-airtel-api-key
AIRTEL_API_SECRET=your-airtel-api-secret
AIRTEL_ENVIRONMENT=sandbox

# Zamtel Configuration
ZAMTEL_API_BASE_URL=https://api.zamtel.com/kwacha
ZAMTEL_API_KEY=your-zamtel-api-key
ZAMTEL_API_SECRET=your-zamtel-api-secret
ZAMTEL_ENVIRONMENT=sandbox

# API Request Configuration
MOBILE_WALLET_REQUEST_TIMEOUT=30
MOBILE_WALLET_REQUEST_RETRIES=3
MOBILE_WALLET_REQUEST_RETRY_DELAY=1000
```

Replace the placeholder values with your actual API credentials.

### 5. Register the Service Provider (Optional)

The package uses Laravel's auto-discovery feature, so the service provider should be registered automatically. However, if you need to register it manually, add the following to the `providers` array in `config/app.php`:

```php
'providers' => [
    // ...
    Mak8Tech\MobileWalletZm\Providers\MobileWalletServiceProvider::class,
],
```

### 6. Register the Facade (Optional)

If you want to use the facade, add the following to the `aliases` array in `config/app.php`:

```php
'aliases' => [
    // ...
    'MobileWallet' => Mak8Tech\MobileWalletZm\Facades\MobileWallet::class,
],
```

### 7. Include the Routes (Optional)

The package routes are loaded automatically by the service provider. However, if you published the routes and want to include them manually, add the following to your `routes/web.php` file:

```php
require base_path('routes/mobile-wallet.php');
require base_path('routes/mobile-wallet-admin.php');
```

### 8. Install Frontend Dependencies (Optional)

If you want to use the React components, you need to install the required dependencies:

```bash
npm install react react-dom @types/react @types/react-dom
```

Then, import the components in your JavaScript/TypeScript files:

```js
import { PaymentForm } from '../vendor/mobile-wallet-zm/components/PaymentForm';
```

## Configuration

The package comes with a comprehensive configuration file that allows you to customize various aspects of the package. You can find the configuration file at `config/mobile_wallet.php`.

### Key Configuration Options

- **Default Provider**: Set the default mobile money provider.
- **Security Settings**: Configure webhook signature verification, API tokens, and CSRF protection.
- **Rate Limiting**: Set rate limits for different endpoints.
- **Caching**: Configure caching for API tokens and other data.
- **Database**: Configure the database connection and table name.
- **Provider-specific Settings**: Configure the API credentials for each provider.
- **API Request Settings**: Configure timeout, retries, and retry delay for API requests.

## Next Steps

After installation, you can:

1. [Read the API documentation](./api.md) to learn how to use the package.
2. [Check the integration guides](./integration-guides.md) for provider-specific setup instructions.
3. [Explore the frontend components](./frontend-components.md) to learn how to use the React components.

## Troubleshooting

If you encounter any issues during installation, please check the following:

1. Make sure you have the correct PHP and Laravel versions.
2. Ensure that your database credentials are correct.
3. Check that you have set the correct API credentials in your `.env` file.
4. If you're using the frontend components, make sure you have installed the required dependencies.

If you still have issues, please [open an issue](https://github.com/mak8tech/mobile-wallet-zm/issues) on GitHub. 