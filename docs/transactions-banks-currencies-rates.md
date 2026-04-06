# Transactions, Banks, Currencies, And Rates

## Transactions

### `list(array $filters = [])`

```php
$transactions = $blaaiz->transactions()->list([
    'status' => 'completed',
    'page' => 1,
]);
```

### `get(string $transactionId)`

```php
$transaction = $blaaiz->transactions()->get('transaction-id');
```

## Banks

### `list()`

```php
$banks = $blaaiz->banks()->list();
```

### `lookupAccount(array $lookupData)`

```php
$account = $blaaiz->banks()->lookupAccount([
    'account_number' => '0123456789',
    'bank_id' => 'bank-id',
]);
```

Required:

- `account_number`
- `bank_id`

## Currencies

### `list()`

```php
$currencies = $blaaiz->currencies()->list();
```

## Rates

### `list(?string $searchTerm = null)`

```php
$allRates = $blaaiz->rates()->list();

$usdRates = $blaaiz->rates()->list('USD');
```
