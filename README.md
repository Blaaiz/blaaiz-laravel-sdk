# Blaaiz Laravel SDK

Official Laravel SDK for the Blaaiz RaaS (Remittance as a Service) API.

## Installation

```bash
composer require blaaiz/blaaiz-laravel-sdk
```

Laravel package discovery is enabled automatically.

## Quick Start

### OAuth 2.0

OAuth is recommended for new integrations.

```env
BLAAIZ_CLIENT_ID=your-client-id
BLAAIZ_CLIENT_SECRET=your-client-secret
BLAAIZ_API_URL=https://api-dev.blaaiz.com
```

```php
use Blaaiz\LaravelSdk\Facades\Blaaiz;

$isConnected = Blaaiz::testConnection();
$currencies = Blaaiz::currencies()->list();
```

### Legacy API key

You can also authenticate with a legacy API key:

```env
BLAAIZ_API_KEY=your-api-key
BLAAIZ_API_URL=https://api-dev.blaaiz.com
```

```php
use Blaaiz\LaravelSdk\Facades\Blaaiz;

$isConnected = Blaaiz::testConnection();
$wallets = Blaaiz::wallets()->list();
```

When both OAuth credentials and an API key are configured, OAuth is used.

### Publish configuration

```bash
php artisan vendor:publish --tag=blaaiz-config
```

### Basic usage with dependency injection

```php
use Blaaiz\LaravelSdk\Blaaiz;

class RatesController
{
    public function __invoke(Blaaiz $blaaiz)
    {
        return response()->json($blaaiz->rates()->list());
    }
}
```

## Configuration

The published config file exposes these options:

```php
return [
    'api_key' => env('BLAAIZ_API_KEY', ''),
    'client_id' => env('BLAAIZ_CLIENT_ID', ''),
    'client_secret' => env('BLAAIZ_CLIENT_SECRET', ''),
    'oauth_scope' => env('BLAAIZ_OAUTH_SCOPE', ''),
    'base_url' => env('BLAAIZ_API_URL', 'https://api-dev.blaaiz.com'),
    'timeout' => env('BLAAIZ_TIMEOUT', 30),
    'webhook_secret' => env('BLAAIZ_WEBHOOK_SECRET', ''),
];
```

## Available Services

- `customers()`
- `collections()`
- `payouts()`
- `wallets()`
- `virtualBankAccounts()`
- `transactions()`
- `banks()`
- `currencies()`
- `fees()`
- `files()`
- `webhooks()`
- `rates()`
- `swaps()`

These services are also exposed as public properties on the underlying SDK instance.

## Common Examples

### Create a customer

```php
use Blaaiz\LaravelSdk\Facades\Blaaiz;

$customer = Blaaiz::customers()->create([
    'first_name' => 'John',
    'last_name' => 'Doe',
    'type' => 'individual',
    'email' => 'john.doe@example.com',
    'country' => 'NG',
    'id_type' => 'passport',
    'id_number' => 'A12345678',
]);
```

### Initiate a payout

```php
$payout = Blaaiz::payouts()->initiate([
    'wallet_id' => 'wallet-id',
    'customer_id' => 'customer-id',
    'method' => 'bank_transfer',
    'from_currency_id' => 'USD',
    'to_currency_id' => 'NGN',
    'from_amount' => 100,
    'bank_id' => 'bank-id',
    'account_number' => '0123456789',
]);
```

### Upload a KYC document

```php
$result = Blaaiz::customers()->uploadFileComplete('customer-id', [
    'file' => storage_path('app/private/passport.pdf'),
    'file_category' => 'identity',
]);
```

### Verify a webhook

```php
$event = Blaaiz::webhooks()->constructEvent(
    $request->getContent(),
    $request->header('X-Blaaiz-Signature', ''),
    $request->header('X-Blaaiz-Timestamp', ''),
    config('blaaiz.webhook_secret')
);
```

## API Reference

- [Root SDK helpers](docs/root-sdk.md)
- [Customers](docs/customers.md)
- [Collections](docs/collections.md)
- [Payouts](docs/payouts.md)
- [Wallets and virtual bank accounts](docs/wallets-and-vbas.md)
- [Transactions, banks, currencies, and rates](docs/transactions-banks-currencies-rates.md)
- [Fees and files](docs/fees-files.md)
- [Webhooks](docs/webhooks.md)
- [Swaps](docs/swaps.md)

## Runnable Examples

See [examples/README.md](examples/README.md) for Laravel-oriented example classes and snippets.

## High-Level Helpers

The root SDK object includes:

- `testConnection()`
- `createCompletePayout(array $config)`
- `createCompleteCollection(array $config)`

These helpers compose the lower-level services for common workflows.

## Error Handling

```php
use Blaaiz\LaravelSdk\Exceptions\BlaaizException;
use Blaaiz\LaravelSdk\Facades\Blaaiz;

try {
    $rates = Blaaiz::rates()->list();
} catch (BlaaizException $e) {
    report($e);

    return response()->json($e->toArray(), $e->getStatus() ?? 500);
}
```

## Development

```bash
composer test
composer analyse
composer format
```
