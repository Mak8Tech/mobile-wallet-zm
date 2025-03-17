# Mobile Wallet ZM - API Documentation

This document provides detailed information about the Mobile Wallet ZM package API, including available methods, request/response formats, and error handling.

## Table of Contents

- [Facade Methods](#facade-methods)
- [Payment Processing](#payment-processing)
- [Transaction Status](#transaction-status)
- [Webhook Handling](#webhook-handling)
- [Error Handling](#error-handling)
- [API Endpoints](#api-endpoints)

## Facade Methods

The package provides a convenient facade `MobileWallet` that you can use to interact with the mobile wallet services.

### Basic Usage

```php
use Mak8Tech\MobileWalletZm\Facades\MobileWallet;

// Create a payment request
$transaction = MobileWallet::pay('260971234567', 100.00, [
    'reference' => 'INV-123',
    'narration' => 'Payment for Invoice #123',
]);

// Check transaction status
$status = MobileWallet::checkStatus($transaction->transaction_id);

// Get transaction by ID
$transaction = MobileWallet::getTransaction($transactionId);

// Get all transactions
$transactions = MobileWallet::getTransactions();
```

### Available Methods

#### `pay(string $phoneNumber, float $amount, array $options = []): WalletTransaction`

Initiates a payment request to the specified phone number.

**Parameters:**
- `$phoneNumber`: The recipient's phone number (with country code)
- `$amount`: The payment amount
- `$options`: Additional options for the payment
  - `provider`: The payment provider (mtn, airtel, zamtel)
  - `currency`: The currency code (default: ZMW)
  - `reference`: A reference for the transaction
  - `narration`: A description of the transaction
  - `callback_url`: A URL to receive the payment notification
  - `customer_name`: The name of the customer
  - `transactionable_id`: ID of the related model
  - `transactionable_type`: Class of the related model

**Returns:**
- A `WalletTransaction` model instance

#### `checkStatus(string $transactionId): array`

Checks the status of a transaction.

**Parameters:**
- `$transactionId`: The transaction ID

**Returns:**
- An array containing the transaction status information

#### `getTransaction(string $transactionId): ?WalletTransaction`

Gets a transaction by its ID.

**Parameters:**
- `$transactionId`: The transaction ID

**Returns:**
- A `WalletTransaction` model instance or null if not found

#### `getTransactions(array $filters = []): \Illuminate\Database\Eloquent\Collection`

Gets all transactions, optionally filtered.

**Parameters:**
- `$filters`: An array of filters to apply
  - `provider`: Filter by provider
  - `status`: Filter by status
  - `phone_number`: Filter by phone number
  - `reference`: Filter by reference
  - `from_date`: Filter by date range (start)
  - `to_date`: Filter by date range (end)

**Returns:**
- A collection of `WalletTransaction` model instances

#### `setProvider(string $provider): self`

Sets the payment provider to use.

**Parameters:**
- `$provider`: The provider name (mtn, airtel, zamtel)

**Returns:**
- The `MobileWallet` instance for method chaining

#### `getDefaultProvider(): string`

Gets the default payment provider.

**Returns:**
- The default provider name

## Payment Processing

### Creating a Payment

```php
// Using the facade
$transaction = MobileWallet::pay('260971234567', 100.00, [
    'reference' => 'INV-123',
    'narration' => 'Payment for Invoice #123',
]);

// Using the service directly
$mtnService = app(Mak8Tech\MobileWalletZm\Services\MTNService::class);
$transaction = $mtnService->pay('260971234567', 100.00, [
    'reference' => 'INV-123',
    'narration' => 'Payment for Invoice #123',
]);
```

### Payment Response

The payment method returns a `WalletTransaction` model instance with the following properties:

```php
[
    'id' => 1,
    'transaction_id' => 'uuid-string',
    'provider' => 'mtn',
    'provider_transaction_id' => 'provider-reference',
    'phone_number' => '260971234567',
    'amount' => 100.00,
    'currency' => 'ZMW',
    'status' => 'pending',
    'message' => null,
    'payment_url' => 'https://payment-url.com',
    'customer_name' => null,
    'reference' => 'INV-123',
    'narration' => 'Payment for Invoice #123',
    'transactionable_id' => null,
    'transactionable_type' => null,
    'paid_at' => null,
    'failed_at' => null,
    'created_at' => '2023-03-17 12:00:00',
    'updated_at' => '2023-03-17 12:00:00',
]
```

## Transaction Status

### Checking Transaction Status

```php
// Using the facade
$status = MobileWallet::checkStatus($transactionId);

// Using the service directly
$mtnService = app(Mak8Tech\MobileWalletZm\Services\MTNService::class);
$status = $mtnService->checkStatus($transactionId);
```

### Status Response

The status check returns an array with the following structure:

```php
[
    'success' => true,
    'transaction_id' => 'uuid-string',
    'provider_transaction_id' => 'provider-reference',
    'status' => 'completed', // pending, completed, failed
    'message' => 'Payment completed successfully',
    'amount' => 100.00,
    'currency' => 'ZMW',
    'phone_number' => '260971234567',
    'paid_at' => '2023-03-17 12:05:00',
]
```

## Webhook Handling

The package automatically handles webhooks from the payment providers. The webhook URLs are:

- MTN: `https://your-app.com/api/mobile-wallet/webhook/mtn`
- Airtel: `https://your-app.com/api/mobile-wallet/webhook/airtel`
- Zamtel: `https://your-app.com/api/mobile-wallet/webhook/zamtel`

### Webhook Security

Webhooks are secured using signature verification. Each provider has its own signature verification method:

- MTN: Uses a signature header and the API secret
- Airtel: Uses a combination of API key and secret
- Zamtel: Uses a signature header and the API secret

### Webhook Events

When a webhook is received, the package will:

1. Verify the signature
2. Update the transaction status
3. Fire a Laravel event `TransactionStatusUpdated`

You can listen for this event to perform additional actions:

```php
use Mak8Tech\MobileWalletZm\Events\TransactionStatusUpdated;

class TransactionStatusListener
{
    public function handle(TransactionStatusUpdated $event)
    {
        $transaction = $event->transaction;
        
        if ($transaction->status === 'completed') {
            // Process the completed payment
        } elseif ($transaction->status === 'failed') {
            // Handle the failed payment
        }
    }
}
```

## Error Handling

The package uses custom exceptions to handle errors. All exceptions extend the base `MobileWalletException` class.

### Exception Types

- `MobileWalletException`: Base exception class
- `InvalidProviderException`: Thrown when an invalid provider is specified
- `PaymentFailedException`: Thrown when a payment fails
- `TransactionNotFoundException`: Thrown when a transaction is not found
- `ApiRequestException`: Thrown when an API request fails
- `WebhookVerificationException`: Thrown when webhook signature verification fails

### Handling Exceptions

```php
use Mak8Tech\MobileWalletZm\Exceptions\MobileWalletException;
use Mak8Tech\MobileWalletZm\Exceptions\PaymentFailedException;

try {
    $transaction = MobileWallet::pay('260971234567', 100.00);
} catch (PaymentFailedException $e) {
    // Handle payment failure
    $errorMessage = $e->getMessage();
    $errorCode = $e->getCode();
    $errorContext = $e->getContext();
} catch (MobileWalletException $e) {
    // Handle other mobile wallet exceptions
} catch (\Exception $e) {
    // Handle general exceptions
}
```

## API Endpoints

The package provides several API endpoints for interacting with the mobile wallet services.

### Payment Endpoint

**URL:** `POST /api/mobile-wallet/pay`

**Request Body:**
```json
{
    "phone_number": "260971234567",
    "amount": 100.00,
    "provider": "mtn", // Optional
    "currency": "ZMW", // Optional
    "reference": "INV-123", // Optional
    "narration": "Payment for Invoice #123", // Optional
    "customer_name": "John Doe", // Optional
    "callback_url": "https://your-app.com/callback" // Optional
}
```

**Response:**
```json
{
    "success": true,
    "message": "Payment initiated successfully",
    "data": {
        "transaction_id": "uuid-string",
        "provider": "mtn",
        "phone_number": "260971234567",
        "amount": 100.00,
        "currency": "ZMW",
        "status": "pending",
        "payment_url": "https://payment-url.com",
        "reference": "INV-123"
    }
}
```

### Status Check Endpoint

**URL:** `GET /api/mobile-wallet/status/{transactionId}`

**Response:**
```json
{
    "success": true,
    "message": "Transaction status retrieved successfully",
    "data": {
        "transaction_id": "uuid-string",
        "provider": "mtn",
        "provider_transaction_id": "provider-reference",
        "status": "completed",
        "message": "Payment completed successfully",
        "amount": 100.00,
        "currency": "ZMW",
        "phone_number": "260971234567",
        "paid_at": "2023-03-17 12:05:00"
    }
}
```

### Webhook Endpoints

**URL:** `POST /api/mobile-wallet/webhook/{provider}`

These endpoints are used by the payment providers to send payment notifications. They are not meant to be called directly by your application. 