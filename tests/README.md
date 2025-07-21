# Blaaiz Laravel SDK Tests

This directory contains comprehensive tests for the Blaaiz Laravel SDK using PestPHP.

## Test Structure

### Unit Tests (`tests/Unit/`)
- **`BlaaizClientTest.php`** - Tests for the HTTP client (equivalent to Node.js SDK's BlaaizAPIClient tests)
- **`BlaaizTest.php`** - Tests for the main SDK class constructor and service initialization
- **`BlaaizExceptionTest.php`** - Tests for custom exception handling
- **`Services/`** - Individual service class tests with validation logic
  - `CustomerServiceTest.php` - Customer management with complex file upload workflows
  - `PayoutServiceTest.php` - Payout initiation with different payment methods
  - `WebhookServiceTest.php` - Webhook verification and signature validation
  - `AllServicesTest.php` - Tests for all remaining services (Collection, Wallet, VBA, etc.)

### Feature Tests (`tests/Feature/`)
- **`BlaaizHighLevelMethodsTest.php`** - High-level SDK methods (equivalent to Node.js SDK's high-level tests)
- **`LaravelIntegrationTest.php`** - Laravel-specific integration (ServiceProvider, Facade, Configuration)

### Integration Tests (`tests/Integration/`)
- **`BlaaizIntegrationTest.php`** - Complete workflow tests requiring valid API credentials

## Test Coverage

### Core Functionality
✅ **HTTP Client** - Request handling, file uploads/downloads, error handling  
✅ **Service Layer** - All 11 services with validation logic  
✅ **Exception Handling** - Custom BlaaizException with status codes  
✅ **High-Level Methods** - Complete payout/collection workflows  

### Laravel Integration
✅ **ServiceProvider** - Service registration and configuration  
✅ **Facade** - Laravel facade functionality  
✅ **Configuration** - Environment variables and config files  
✅ **Container Binding** - Dependency injection  

### File Upload Workflows
✅ **Multiple Formats** - Buffer, base64, data URLs, file downloads  
✅ **Validation** - File categories, required fields  
✅ **Error Handling** - Upload failures, invalid parameters  
✅ **S3 Integration** - Presigned URL workflow  

### Webhook Handling
✅ **Signature Verification** - HMAC-SHA256 validation  
✅ **Event Construction** - Payload parsing and validation  
✅ **Error Scenarios** - Invalid signatures, malformed JSON  

## Running Tests

### Prerequisites

1. **Install Dependencies**
   ```bash
   composer install
   ```

2. **Environment Setup** (for integration tests)
   ```bash
   # Copy and configure environment
   cp .env.example .env
   
   # Set required variables for integration tests
   BLAAIZ_API_KEY=your_test_api_key
   BLAAIZ_API_URL=https://api-dev.blaaiz.com
   BLAAIZ_TEST_WALLET_ID=your_test_wallet_id  # Optional, for VBA tests
   ```

3. **Test Files** (for file upload tests)
   ```bash
   # Ensure blank.pdf exists in tests/ directory
   ls tests/blank.pdf
   ```

### Test Commands

**Run All Tests**
```bash
composer test
```

**Run Specific Test Suites**
```bash
# Unit tests only
./vendor/bin/pest tests/Unit

# Feature tests only  
./vendor/bin/pest tests/Feature

# Integration tests only (requires API key)
./vendor/bin/pest tests/Integration
```

**Run Individual Test Files**
```bash
# Specific service tests
./vendor/bin/pest tests/Unit/Services/CustomerServiceTest.php
./vendor/bin/pest tests/Unit/BlaaizClientTest.php

# Laravel integration
./vendor/bin/pest tests/Feature/LaravelIntegrationTest.php
```

**Test Coverage**
```bash
composer test-coverage
```

**Parallel Testing**
```bash
# Run tests in parallel for faster execution
./vendor/bin/pest --parallel
```

### Test Configuration

**PHPUnit Configuration** (`phpunit.xml`)
- Defines test suites (Unit, Feature, Integration)
- Sets environment variables for testing
- Configures coverage reporting

**Pest Configuration** (`tests/Pest.php`)
- Sets up test case inheritance
- Provides helper functions
- Configures test environment

## Test Categories

### 🟢 Unit Tests (No External Dependencies)
- Mock all HTTP requests and external services
- Fast execution, no network calls
- Test individual components in isolation

### 🟡 Feature Tests (Laravel Integration)
- Test Laravel-specific functionality
- Use Laravel's testing helpers and container
- Mock external API calls

### 🔴 Integration Tests (Requires API Key)
- Test against real Blaaiz API endpoints
- Require valid API credentials
- May create actual resources (customers, files)
- Slower execution due to network calls

## Key Test Features

### Comprehensive Service Coverage
```php
// All 11 services tested with validation logic
- CustomerService (with complex file upload workflows)
- PayoutService (bank_transfer, interac methods)
- CollectionService (including VBA creation)
- WalletService, TransactionService, BankService
- CurrencyService, FeesService, FileService
- VirtualBankAccountService, WebhookService
```

### File Upload Testing
```php
// Multiple file input types supported
- Buffer content
- Base64 strings  
- Data URLs with content type detection
- HTTP URL downloads
- Real PDF file uploads
```

### Error Handling
```php
// Comprehensive error scenarios
- Invalid API keys
- Missing required fields
- Invalid file categories
- Network timeouts
- Malformed responses
```

### Laravel Integration
```php
// Full Laravel ecosystem support
- ServiceProvider registration
- Facade functionality
- Configuration management
- Container binding
- Environment variable support
```

## Test Patterns

### Service Test Pattern
```php
// Consistent pattern for all service tests
class ServiceNameTest extends ServiceTestCase
{
    // Setup with mocked client
    // Validation tests for required fields
    // Success scenarios with proper mocking
    // Error handling tests
}
```

### HTTP Client Mocking
```php
// Guzzle MockHandler for HTTP requests
$mockHandler = new MockHandler([
    new Response(200, ['Content-Type' => 'application/json'], $responseBody)
]);
```

### Laravel Testing Helpers
```php
// Use Orchestra Testbench for Laravel integration
class LaravelTest extends TestCase
{
    // Full Laravel application context
    // Configuration testing
    // Service provider testing
}
```

## Troubleshooting

### Common Issues

1. **Integration Tests Failing**
   - Ensure `BLAAIZ_API_KEY` is set and valid
   - Check network connectivity to API endpoints
   - Verify test environment has proper permissions

2. **File Upload Tests Failing**
   - Ensure `tests/blank.pdf` file exists
   - Check file permissions
   - Verify S3 upload endpoints are accessible

3. **Laravel Integration Issues**
   - Clear Laravel config cache: `php artisan config:clear`
   - Ensure proper service provider registration
   - Check facade configuration

### Debug Output
```bash
# Run tests with verbose output
./vendor/bin/pest --verbose

# Show test coverage details
./vendor/bin/pest --coverage-text

# Debug specific test
./vendor/bin/pest tests/Unit/Services/CustomerServiceTest.php --verbose
```

## Continuous Integration

The test suite is designed to work in CI environments:

```yaml
# Example GitHub Actions workflow
- name: Run Tests
  run: |
    composer install --no-dev
    ./vendor/bin/pest --parallel
  env:
    BLAAIZ_API_KEY: ${{ secrets.BLAAIZ_API_KEY }}
    BLAAIZ_API_URL: https://api-dev.blaaiz.com
```

For complete CI setup, unit and feature tests can run without API credentials, while integration tests require proper environment setup.