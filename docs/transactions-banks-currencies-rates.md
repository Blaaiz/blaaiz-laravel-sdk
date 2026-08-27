# Transactions, Banks, Currencies, And Rates

## Transactions

### `list(array $filters = [])`

```php
$transactions = $blaaiz->transactions()->list([
    'status' => 'SUCCESSFUL',
    'merchant_reference' => 'order-12345',
]);
```

Optional filters:

- `start_date`
- `end_date`
- `wallet_id`
- `customer_id`
- `type` (`SEND_MONEY`, `FUND_WALLET`, or `SWAP`)
- `status` (`FAILED`, `SUCCESSFUL`, or `EXPIRED`)
- `merchant_reference`

Transaction list items include `merchant_reference`. The value is `null` when you did not set it.

### `get(string $transactionId)`

```php
$transaction = $blaaiz->transactions()->get('transaction-id');
```

The `$transactionId` argument accepts a transaction id, a reference, or a `merchant_reference`. The API resolves the value in that order. The response includes `merchant_reference`, which is `null` when you did not set it.

## Banks

### `list(array $filters = [])`

```php
$banks = $blaaiz->banks()->list();

// Filter the bank list.
$ngnBanks = $blaaiz->banks()->list([
    'currency' => 'NGN',
    'country' => 'NG',
]);
```

Optional filters:

- `currency`
- `country`
- `country_id`

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

### `verifyPayee(array $payeeData)`

Verifies a UK payee with Confirmation of Payee.

```php
$result = $blaaiz->banks()->verifyPayee([
    'sort_code' => '12-34-56',
    'account_number' => '12345678',
    'account_name' => 'John Doe',
]);
```

Required:

- `sort_code`
- `account_number`
- `account_name`

### `verifyIban(array $ibanData)`

Checks whether an IBAN is reachable through SEPA.

```php
$result = $blaaiz->banks()->verifyIban([
    'iban' => 'DE89370400440532013000',
]);
```

Required:

- `iban`

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
