# Wallets And Virtual Bank Accounts

## Wallets

### `list()`

```php
$wallets = $blaaiz->wallets()->list();
```

### `get(string $walletId)`

```php
$wallet = $blaaiz->wallets()->get('wallet-id');
```

## Virtual bank accounts

### `create(array $vbaData)`

```php
$vba = $blaaiz->virtualBankAccounts()->create([
    'wallet_id' => 'wallet-id',
    'account_name' => 'John Doe',
]);
```

Required:

- `wallet_id`

### `list(?string $walletId = null, ?string $customerId = null)`

```php
$allVbas = $blaaiz->virtualBankAccounts()->list();

$walletVbas = $blaaiz->virtualBankAccounts()->list('wallet-id');

$customerVbas = $blaaiz->virtualBankAccounts()->list(null, 'customer-id');
```

### `get(string $vbaId)`

```php
$vba = $blaaiz->virtualBankAccounts()->get('vba-id');
```

### `close(string $vbaId, ?string $reason = null)`

```php
$closed = $blaaiz->virtualBankAccounts()->close('vba-id', 'No longer needed');
```

### `getIdentificationType(?string $customerId = null, ?string $country = null, ?string $type = null)`

Provide either:

- `customerId`

or both:

- `country`
- `type`

```php
$byCustomer = $blaaiz->virtualBankAccounts()->getIdentificationType('customer-id');

$byCountryAndType = $blaaiz->virtualBankAccounts()->getIdentificationType(
    null,
    'NG',
    'individual'
);
```
