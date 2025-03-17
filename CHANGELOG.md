# Changelog

All notable changes to `mobile-wallet-zm` will be documented in this file.

## Unreleased

### Added

-   Provider-specific signature verification for webhooks
    -   Implemented `SignatureVerifier` interface
    -   Created provider-specific verifiers for MTN, Airtel, and Zamtel
    -   Added `SignatureVerifierFactory` for creating appropriate verifiers
    -   Implemented middleware for verifying webhook signatures
    -   Added tests for signature verification
-   Rate limiting for API endpoints
    -   Implemented `RateLimitApiRequests` middleware
    -   Added configuration for different rate limits by endpoint type
    -   Applied rate limiting to payment, status, and webhook endpoints
    -   Added tests for rate limiting middleware
-   Enhanced security measures
    -   Implemented encryption for sensitive data in the database
    -   Added CSRF protection for payment endpoints
    -   Created API token authentication for API requests
    -   Implemented admin authorization middleware
    -   Added tests for data encryption and CSRF protection
-   Admin dashboard functionality
    -   Created admin controller for transaction management
    -   Implemented transaction listing, filtering, and reporting
    -   Added permission-based authorization for admin operations
    -   Configured routes with appropriate middleware protection
-   Detailed logging with different severity levels
-   Robust API request service with retry mechanism
    -   Implemented `ApiRequestService` with configurable retry settings
    -   Added support for handling rate limiting with Retry-After headers
    -   Implemented exponential backoff for failed requests
    -   Created comprehensive tests for API request service
    -   Added exception handling for connection errors and failed requests
-   Database optimizations
    -   Added strategic indexes to transaction table for improved query performance
    -   Implemented composite indexes for common query patterns
    -   Added configuration options for customizing database indexes
-   Comprehensive documentation
    -   Created detailed installation guide with step-by-step instructions
    -   Added API documentation with method descriptions and examples
    -   Included request/response formats for all endpoints
    -   Documented error handling and exception types
    -   Added webhook handling documentation
-   Frontend loading state indicators and improved error feedback
-   Frontend form validation
-   Receipt/success component
-   Comprehensive test suite:
    -   Unit tests for all service classes (MTN, Airtel, Zamtel)
    -   Unit tests for abstract payment service
    -   Unit tests for mobile wallet manager
    -   Unit tests for transaction model
    -   Unit tests for facade implementation
    -   Unit tests for API request service
    -   Feature tests for complete payment flows
    -   Feature tests for controller endpoints
    -   Frontend component tests for PaymentForm component:
        -   Tests for rendering and UI elements
        -   Tests for form validation
        -   Tests for API interactions and error handling
-   Consistent error handling system:
    -   Base exception class with standardized error formatting
    -   Provider-specific exceptions for different error types
    -   API request exceptions for handling connection and response errors
    -   Detailed error logging with context
    -   Standardized JSON error responses

### Improved

-   Security enhancements for API endpoints
    -   Added provider-specific webhook routes with dedicated handlers
    -   Implemented signature verification for each provider
    -   Added encryption for sensitive transaction data
    -   Implemented CSRF protection with API token support
    -   Added role-based access control for admin operations
-   Input sanitization for all API requests
-   Performance optimizations via caching
-   Documentation coverage
-   Test environment setup for easier testing
-   Error handling across all payment providers

## 0.0.1-beta - 2025-03-16

### Added

-   Initial package structure and configuration
-   Mobile money service integrations:
    -   MTN Mobile Money implementation
    -   Airtel Money implementation
    -   Zamtel Kwacha implementation
-   Common payment service interface and abstract class
-   WalletTransaction model and database migrations
-   Webhook handling with basic signature verification
-   Controllers for payment processing and webhooks
-   React payment form component with TypeScript support
-   Basic error handling and custom exception classes
-   Laravel service provider and facade
-   Installation command
