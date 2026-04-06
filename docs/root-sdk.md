# Root SDK

## `__construct(array $options = [])`

The package registers the SDK in Laravel's service container and facade automatically.
In most Laravel apps you will use either the facade or dependency injection instead of constructing it manually.

### Facade usage

```php
use Blaaiz\LaravelSdk\Facades\Blaaiz;

$rates = Blaaiz::rates()->list();
```

### Dependency injection

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

### Manual construction

```php
use Blaaiz\LaravelSdk\Blaaiz;

$blaaiz = new Blaaiz([
    'client_id' => 'your-client-id',
    'client_secret' => 'your-client-secret',
    'base_url' => 'https://api-dev.blaaiz.com',
    'timeout' => 30,
]);
```

You can also manually construct the SDK with a legacy API key:

```php
use Blaaiz\LaravelSdk\Blaaiz;

$blaaiz = new Blaaiz([
    'api_key' => 'your-api-key',
    'base_url' => 'https://api-dev.blaaiz.com',
    'timeout' => 30,
]);
```

## Service accessors

The root SDK exposes service accessors:

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

These are also available as public properties on the underlying SDK instance.

## `testConnection()`

Performs a lightweight connectivity check by calling the currencies endpoint.

```php
use Blaaiz\LaravelSdk\Facades\Blaaiz;

if (Blaaiz::testConnection()) {
    logger()->info('Blaaiz connected');
}
```

## `createCompletePayout(array $payoutConfig)`

Creates a customer when needed, calculates fees, and initiates the payout.

```php
$result = Blaaiz::createCompletePayout([
    'customer_data' => [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'type' => 'individual',
        'email' => 'john@example.com',
        'country' => 'NG',
        'id_type' => 'passport',
        'id_number' => 'A12345678',
    ],
    'payout_data' => [
        'wallet_id' => 'wallet-id',
        'method' => 'bank_transfer',
        'from_currency_id' => 'USD',
        'to_currency_id' => 'NGN',
        'from_amount' => 100,
        'bank_id' => 'bank-id',
        'account_number' => '0123456789',
    ],
]);
```

You can omit `customer_data` when `payout_data.customer_id` is already available.

## `createCompleteCollection(array $collectionConfig)`

Creates a customer when needed, optionally creates a virtual bank account, and initiates the collection.

```php
$result = Blaaiz::createCompleteCollection([
    'customer_data' => [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'type' => 'individual',
        'email' => 'jane@example.com',
        'country' => 'NG',
        'id_type' => 'passport',
        'id_number' => 'B12345678',
    ],
    'collection_data' => [
        'wallet_id' => 'wallet-id',
        'amount' => 100,
        'currency' => 'NGN',
        'method' => 'bank_transfer',
    ],
    'create_vba' => true,
]);
```
