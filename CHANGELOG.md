# Changelog

All notable changes to `mobile-wallet-zm` will be documented in this file.

## Unreleased

### Added
- Provider-specific signature verification for webhooks
- Detailed logging with different severity levels
- Retry mechanism for API calls
- Frontend loading state indicators and improved error feedback
- Frontend form validation
- Receipt/success component
- Unit and integration tests

### Improved
- Security enhancements for API endpoints
- Input sanitization for all API requests
- Performance optimizations via caching
- Documentation coverage

## 0.1.0 - 2024-04-15

### Added
- Initial package structure and configuration
- Mobile money service integrations:
  - MTN Mobile Money implementation
  - Airtel Money implementation
  - Zamtel Kwacha implementation
- Common payment service interface and abstract class
- Transaction model and database migrations
- Webhook handling with basic signature verification
- Controllers for payment processing and webhooks
- React payment form component with TypeScript support
- Basic error handling and custom exception classes
- Laravel service provider and facade
- Installation command
