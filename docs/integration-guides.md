 # Mobile Wallet ZM - Integration Guides

This document provides detailed information on how to integrate with the various mobile money providers in Zambia, including how to register as a merchant and obtain API credentials.

## Table of Contents

- [MTN Mobile Money](#mtn-mobile-money)
- [Airtel Money](#airtel-money)
- [Zamtel Kwacha](#zamtel-kwacha)
- [Testing Your Integration](#testing-your-integration)
- [Going Live](#going-live)

## MTN Mobile Money

### Registration Process

To integrate with MTN Mobile Money, you need to follow these steps:

1. **Register as a Merchant**:
   - Visit the [MTN Developer Portal](https://momodeveloper.mtn.com/)
   - Create an account and log in
   - Navigate to the "Become a Merchant" section
   - Fill out the application form with your business details
   - Submit the required documentation (business registration, tax clearance, etc.)

2. **Wait for Approval**:
   - MTN will review your application
   - This process typically takes 5-10 business days
   - You will receive an email notification once approved

3. **Create API Credentials**:
   - Log in to the MTN Developer Portal
   - Navigate to the "My Subscriptions" section
   - Create a new subscription for the Collection API
   - Generate your API Key and Secret
   - Note down your Collection Subscription Key

### API Credentials

You will need the following credentials for MTN integration:

- **API Key**: A unique identifier for your application
- **API Secret**: A secret key used for authentication
- **Collection Subscription Key**: A key specific to the Collection API
- **Disbursement Subscription Key** (optional): Required only if you need to send money to users

### Configuration

Add the following to your `.env` file:

```
MTN_API_BASE_URL=https://sandbox.momodeveloper.mtn.com
MTN_API_KEY=your-mtn-api-key
MTN_API_SECRET=your-mtn-api-secret
MTN_COLLECTION_SUBSCRIPTION_KEY=your-mtn-collection-subscription-key
MTN_DISBURSEMENT_SUBSCRIPTION_KEY=your-mtn-disbursement-subscription-key
MTN_ENVIRONMENT=sandbox
```

Replace the placeholder values with your actual credentials. Change `MTN_ENVIRONMENT` to `production` when going live.

### Webhook Setup

MTN requires a webhook URL to notify your application of payment events. Configure your webhook URL in the MTN Developer Portal:

1. Log in to the MTN Developer Portal
2. Navigate to the "API Configuration" section
3. Enter your webhook URL: `https://your-app.com/api/mobile-wallet/webhook/mtn`
4. Save the configuration

## Airtel Money

### Registration Process

To integrate with Airtel Money, you need to follow these steps:

1. **Register as a Merchant**:
   - Visit the [Airtel Africa Developer Portal](https://developers.airtel.africa/)
   - Create an account and log in
   - Navigate to the "Become a Merchant" section
   - Fill out the application form with your business details
   - Submit the required documentation (business registration, tax clearance, etc.)

2. **Wait for Approval**:
   - Airtel will review your application
   - This process typically takes 3-7 business days
   - You will receive an email notification once approved

3. **Create API Credentials**:
   - Log in to the Airtel Developer Portal
   - Navigate to the "My Apps" section
   - Create a new application
   - Select the Collection API
   - Generate your API Key and Secret

### API Credentials

You will need the following credentials for Airtel integration:

- **API Key**: A unique identifier for your application
- **API Secret**: A secret key used for authentication

### Configuration

Add the following to your `.env` file:

```
AIRTEL_API_BASE_URL=https://openapi.airtel.africa
AIRTEL_API_KEY=your-airtel-api-key
AIRTEL_API_SECRET=your-airtel-api-secret
AIRTEL_ENVIRONMENT=sandbox
```

Replace the placeholder values with your actual credentials. Change `AIRTEL_ENVIRONMENT` to `production` when going live.

### Webhook Setup

Airtel requires a webhook URL to notify your application of payment events. Configure your webhook URL in the Airtel Developer Portal:

1. Log in to the Airtel Developer Portal
2. Navigate to the "My Apps" section
3. Select your application
4. Enter your webhook URL: `https://your-app.com/api/mobile-wallet/webhook/airtel`
5. Save the configuration

## Zamtel Kwacha

### Registration Process

To integrate with Zamtel Kwacha, you need to follow these steps:

1. **Register as a Merchant**:
   - Contact Zamtel Business Sales at business@zamtel.co.zm
   - Request an application form for Zamtel Kwacha API integration
   - Fill out the application form with your business details
   - Submit the required documentation (business registration, tax clearance, etc.)

2. **Wait for Approval**:
   - Zamtel will review your application
   - This process typically takes 7-14 business days
   - You will be contacted by a Zamtel representative once approved

3. **Receive API Credentials**:
   - Zamtel will provide you with API credentials
   - You will receive an API Key and Secret
   - You will also receive documentation for the API

### API Credentials

You will need the following credentials for Zamtel integration:

- **API Key**: A unique identifier for your application
- **API Secret**: A secret key used for authentication

### Configuration

Add the following to your `.env` file:

```
ZAMTEL_API_BASE_URL=https://api.zamtel.com/kwacha
ZAMTEL_API_KEY=your-zamtel-api-key
ZAMTEL_API_SECRET=your-zamtel-api-secret
ZAMTEL_ENVIRONMENT=sandbox
```

Replace the placeholder values with your actual credentials. Change `ZAMTEL_ENVIRONMENT` to `production` when going live.

### Webhook Setup

Zamtel requires a webhook URL to notify your application of payment events. Provide your webhook URL to your Zamtel representative:

- Webhook URL: `https://your-app.com/api/mobile-wallet/webhook/zamtel`

## Testing Your Integration

### Sandbox Environment

All providers offer a sandbox environment for testing your integration before going live. Use the following test credentials:

#### MTN Test Credentials

- **Test Phone Numbers**: 260970000001, 260970000002, 260970000003
- **Test PIN**: 1234
- **Test Amount**: Any amount (preferably small amounts for testing)

#### Airtel Test Credentials

- **Test Phone Numbers**: 260971000001, 260971000002, 260971000003
- **Test PIN**: 1234
- **Test Amount**: Any amount (preferably small amounts for testing)

#### Zamtel Test Credentials

- **Test Phone Numbers**: 260979000001, 260979000002, 260979000003
- **Test PIN**: 1234
- **Test Amount**: Any amount (preferably small amounts for testing)

### Testing Process

1. **Configure Sandbox Credentials**:
   - Make sure your `.env` file has the sandbox credentials
   - Set the environment to `sandbox` for all providers

2. **Create a Test Payment**:
   - Use the `MobileWallet::pay()` method or the payment form component
   - Use one of the test phone numbers
   - Use a small amount (e.g., 10 ZMW)

3. **Simulate Payment Approval**:
   - For MTN: Use the MTN Developer Portal to simulate payment approval
   - For Airtel: Use the Airtel Developer Portal to simulate payment approval
   - For Zamtel: Contact your Zamtel representative for instructions

4. **Verify Webhook Reception**:
   - Check that your application receives the webhook notification
   - Verify that the transaction status is updated correctly

## Going Live

### Production Checklist

Before going live with your integration, ensure that you have:

1. **Completed Testing**:
   - Thoroughly tested all payment flows in the sandbox environment
   - Verified webhook handling and transaction status updates
   - Tested error scenarios and edge cases

2. **Updated Configuration**:
   - Changed the environment to `production` for all providers
   - Updated API credentials to production credentials
   - Configured production webhook URLs

3. **Implemented Security Measures**:
   - Enabled webhook signature verification
   - Implemented rate limiting for API endpoints
   - Added proper error handling and logging

4. **Prepared User Support**:
   - Created user documentation for payment processes
   - Set up customer support channels for payment issues
   - Trained support staff on common payment scenarios

### Production Configuration

Update your `.env` file with production credentials:

```
# MTN Production
MTN_API_BASE_URL=https://api.mtn.com
MTN_API_KEY=your-production-mtn-api-key
MTN_API_SECRET=your-production-mtn-api-secret
MTN_COLLECTION_SUBSCRIPTION_KEY=your-production-mtn-collection-subscription-key
MTN_ENVIRONMENT=production

# Airtel Production
AIRTEL_API_BASE_URL=https://openapi.airtel.africa
AIRTEL_API_KEY=your-production-airtel-api-key
AIRTEL_API_SECRET=your-production-airtel-api-secret
AIRTEL_ENVIRONMENT=production

# Zamtel Production
ZAMTEL_API_BASE_URL=https://api.zamtel.com/kwacha
ZAMTEL_API_KEY=your-production-zamtel-api-key
ZAMTEL_API_SECRET=your-production-zamtel-api-secret
ZAMTEL_ENVIRONMENT=production
```

### Monitoring and Maintenance

Once live, regularly monitor your integration:

1. **Transaction Monitoring**:
   - Set up alerts for failed transactions
   - Monitor transaction volumes and success rates
   - Check for unusual patterns or potential fraud

2. **API Health Checks**:
   - Implement regular health checks for the API connections
   - Set up monitoring for API response times
   - Create alerts for API downtime or degraded performance

3. **Regular Updates**:
   - Keep the package updated to the latest version
   - Stay informed about API changes from the providers
   - Implement new features and security updates promptly