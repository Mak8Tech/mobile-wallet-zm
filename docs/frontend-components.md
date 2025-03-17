# Mobile Wallet ZM - Frontend Components

This document provides detailed information about the React components included in the Mobile Wallet ZM package, including their props, usage examples, and customization options.

## Table of Contents

- [Installation](#installation)
- [PaymentForm Component](#paymentform-component)
- [PaymentStatus Component](#paymentstatus-component)
- [PaymentReceipt Component](#paymentreceipt-component)
- [Customization](#customization)
- [TypeScript Support](#typescript-support)

## Installation

Before using the components, make sure you have installed the required dependencies:

```bash
npm install react react-dom @types/react @types/react-dom
```

Then, publish the frontend assets:

```bash
php artisan vendor:publish --tag=mobile-wallet-assets
```

This will copy the React components to `resources/js/vendor/mobile-wallet-zm`.

## PaymentForm Component

The `PaymentForm` component provides a complete payment form for collecting mobile money payments.

### Basic Usage

```jsx
import React from 'react';
import { PaymentForm } from '../vendor/mobile-wallet-zm/components/PaymentForm';

function PaymentPage() {
    const handleSuccess = (transaction) => {
        console.log('Payment successful:', transaction);
    };

    const handleError = (error) => {
        console.error('Payment failed:', error);
    };

    return (
        <div className="container">
            <h1>Make a Payment</h1>
            <PaymentForm
                amount={100}
                onSuccess={handleSuccess}
                onError={handleError}
            />
        </div>
    );
}

export default PaymentPage;
```

### Props

| Prop | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `amount` | `number` | No | - | The payment amount. If provided, the amount field will be disabled. |
| `phoneNumber` | `string` | No | - | The phone number to pre-fill. |
| `provider` | `string` | No | - | The default provider (mtn, airtel, zamtel). |
| `providers` | `string[]` | No | `['mtn', 'airtel', 'zamtel']` | The available providers. |
| `currency` | `string` | No | `'ZMW'` | The currency code. |
| `reference` | `string` | No | - | A reference for the transaction. |
| `narration` | `string` | No | - | A description of the transaction. |
| `customerName` | `string` | No | - | The name of the customer. |
| `title` | `string` | No | `'Mobile Money Payment'` | The title of the form. |
| `submitButtonText` | `string` | No | `'Pay Now'` | The text for the submit button. |
| `onSuccess` | `function` | Yes | - | Callback function called when payment is successful. |
| `onError` | `function` | Yes | - | Callback function called when payment fails. |
| `className` | `string` | No | - | Additional CSS class for the form. |
| `apiUrl` | `string` | No | `/api/mobile-wallet/pay` | The API endpoint for payment processing. |
| `redirectUrl` | `string` | No | - | URL to redirect after successful payment. |
| `showProviderLogos` | `boolean` | No | `true` | Whether to show provider logos. |
| `theme` | `object` | No | - | Theme customization options. |

### Example with Custom Providers

```jsx
<PaymentForm
    amount={50}
    providers={['mtn', 'airtel']} // Only show MTN and Airtel
    provider="airtel" // Default to Airtel
    title="Pay for Your Order"
    submitButtonText="Complete Payment"
    onSuccess={handleSuccess}
    onError={handleError}
/>
```

## PaymentStatus Component

The `PaymentStatus` component displays the status of a payment transaction.

### Basic Usage

```jsx
import React from 'react';
import { PaymentStatus } from '../vendor/mobile-wallet-zm/components/PaymentStatus';

function PaymentStatusPage() {
    return (
        <div className="container">
            <h1>Payment Status</h1>
            <PaymentStatus
                transactionId="transaction-id-here"
                pollInterval={5000}
                onStatusChange={(status) => console.log('Status changed:', status)}
            />
        </div>
    );
}

export default PaymentStatusPage;
```

### Props

| Prop | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `transactionId` | `string` | Yes | - | The transaction ID to check. |
| `pollInterval` | `number` | No | `5000` | The interval in milliseconds to poll for status updates. |
| `onStatusChange` | `function` | No | - | Callback function called when the status changes. |
| `onCompleted` | `function` | No | - | Callback function called when the payment is completed. |
| `onFailed` | `function` | No | - | Callback function called when the payment fails. |
| `className` | `string` | No | - | Additional CSS class for the component. |
| `apiUrl` | `string` | No | `/api/mobile-wallet/status` | The API endpoint for status checking. |
| `showDetails` | `boolean` | No | `true` | Whether to show transaction details. |
| `theme` | `object` | No | - | Theme customization options. |

## PaymentReceipt Component

The `PaymentReceipt` component displays a receipt for a completed payment.

### Basic Usage

```jsx
import React from 'react';
import { PaymentReceipt } from '../vendor/mobile-wallet-zm/components/PaymentReceipt';

function PaymentReceiptPage() {
    return (
        <div className="container">
            <h1>Payment Receipt</h1>
            <PaymentReceipt
                transaction={{
                    transaction_id: 'tx-123',
                    provider: 'mtn',
                    phone_number: '260971234567',
                    amount: 100,
                    currency: 'ZMW',
                    status: 'completed',
                    paid_at: '2023-03-17 12:05:00',
                    reference: 'INV-123',
                }}
                showPrintButton={true}
            />
        </div>
    );
}

export default PaymentReceiptPage;
```

### Props

| Prop | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `transaction` | `object` | Yes | - | The transaction object to display. |
| `title` | `string` | No | `'Payment Receipt'` | The title of the receipt. |
| `showPrintButton` | `boolean` | No | `true` | Whether to show the print button. |
| `onPrint` | `function` | No | - | Callback function called when the print button is clicked. |
| `className` | `string` | No | - | Additional CSS class for the component. |
| `theme` | `object` | No | - | Theme customization options. |

## Customization

### Theme Customization

You can customize the appearance of the components by providing a `theme` prop:

```jsx
<PaymentForm
    amount={100}
    onSuccess={handleSuccess}
    onError={handleError}
    theme={{
        colors: {
            primary: '#3490dc',
            secondary: '#38c172',
            error: '#e3342f',
            success: '#38c172',
            background: '#ffffff',
            text: '#333333',
        },
        borderRadius: '0.5rem',
        fontFamily: 'Arial, sans-serif',
    }}
/>
```

### CSS Customization

You can also customize the components using CSS by targeting the following classes:

```css
/* PaymentForm */
.mobile-wallet-form { /* Form container */ }
.mobile-wallet-form__title { /* Form title */ }
.mobile-wallet-form__provider-selector { /* Provider selector */ }
.mobile-wallet-form__input { /* Form inputs */ }
.mobile-wallet-form__submit-button { /* Submit button */ }

/* PaymentStatus */
.mobile-wallet-status { /* Status container */ }
.mobile-wallet-status__title { /* Status title */ }
.mobile-wallet-status__details { /* Status details */ }
.mobile-wallet-status--pending { /* Pending status */ }
.mobile-wallet-status--completed { /* Completed status */ }
.mobile-wallet-status--failed { /* Failed status */ }

/* PaymentReceipt */
.mobile-wallet-receipt { /* Receipt container */ }
.mobile-wallet-receipt__title { /* Receipt title */ }
.mobile-wallet-receipt__details { /* Receipt details */ }
.mobile-wallet-receipt__print-button { /* Print button */ }
```

## TypeScript Support

The components include TypeScript definitions for better type safety and developer experience.

### Type Definitions

```typescript
// Transaction type
interface Transaction {
    transaction_id: string;
    provider: string;
    provider_transaction_id?: string;
    phone_number: string;
    amount: number;
    currency: string;
    status: 'pending' | 'completed' | 'failed';
    message?: string;
    payment_url?: string;
    customer_name?: string;
    reference?: string;
    narration?: string;
    paid_at?: string;
    failed_at?: string;
    created_at: string;
    updated_at: string;
}

// PaymentForm props
interface PaymentFormProps {
    amount?: number;
    phoneNumber?: string;
    provider?: string;
    providers?: string[];
    currency?: string;
    reference?: string;
    narration?: string;
    customerName?: string;
    title?: string;
    submitButtonText?: string;
    onSuccess: (transaction: Transaction) => void;
    onError: (error: any) => void;
    className?: string;
    apiUrl?: string;
    redirectUrl?: string;
    showProviderLogos?: boolean;
    theme?: ThemeOptions;
}

// PaymentStatus props
interface PaymentStatusProps {
    transactionId: string;
    pollInterval?: number;
    onStatusChange?: (status: string) => void;
    onCompleted?: (transaction: Transaction) => void;
    onFailed?: (transaction: Transaction) => void;
    className?: string;
    apiUrl?: string;
    showDetails?: boolean;
    theme?: ThemeOptions;
}

// PaymentReceipt props
interface PaymentReceiptProps {
    transaction: Transaction;
    title?: string;
    showPrintButton?: boolean;
    onPrint?: () => void;
    className?: string;
    theme?: ThemeOptions;
}

// Theme options
interface ThemeOptions {
    colors?: {
        primary?: string;
        secondary?: string;
        error?: string;
        success?: string;
        background?: string;
        text?: string;
    };
    borderRadius?: string;
    fontFamily?: string;
}
```

### Usage with TypeScript

```tsx
import React from 'react';
import { PaymentForm, Transaction } from '../vendor/mobile-wallet-zm/components/PaymentForm';

function PaymentPage() {
    const handleSuccess = (transaction: Transaction) => {
        console.log('Payment successful:', transaction);
    };

    const handleError = (error: any) => {
        console.error('Payment failed:', error);
    };

    return (
        <div className="container">
            <h1>Make a Payment</h1>
            <PaymentForm
                amount={100}
                onSuccess={handleSuccess}
                onError={handleError}
            />
        </div>
    );
}

export default PaymentPage;
``` 