# Collections

## `initiate(array $collectionData)`

```php
$collection = $blaaiz->collections()->initiate([
    'customer_id' => 'customer-id',
    'wallet_id' => 'wallet-id',
    'amount' => 100,
    'currency' => 'EUR',
    'method' => 'open_banking',
    'redirect_url' => 'https://example.com/callback',
]);
```

Required fields:

- `customer_id`
- `wallet_id`
- `amount`
- `currency`
- `method`

## `initiateCrypto(array $cryptoData)`

```php
$cryptoCollection = $blaaiz->collections()->initiateCrypto([
    'wallet_id' => 'wallet-id',
    'currency' => 'USDT',
    'network' => 'TRON',
]);
```

## `attachCustomer(array $attachData)`

Associates a customer with an existing collection transaction.

```php
$attached = $blaaiz->collections()->attachCustomer([
    'customer_id' => 'customer-id',
    'transaction_id' => 'transaction-id',
]);
```

## `getCryptoNetworks()`

```php
$networks = $blaaiz->collections()->getCryptoNetworks();
```

## `acceptInteracMoneyRequest(array $interacData)`

```php
$result = $blaaiz->collections()->acceptInteracMoneyRequest([
    'reference_number' => 'interac-reference',
    'security_answer' => 'answer',
    'email' => 'sender@example.com',
]);
```

Only `reference_number` is enforced by the SDK. Other fields depend on the flow you are using.
