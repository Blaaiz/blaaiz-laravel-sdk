# Payouts

## `initiate(array $payoutData)`

```php
$payout = $blaaiz->payouts()->initiate([
    'wallet_id' => 'wallet-id',
    'customer_id' => 'customer-id',
    'method' => 'bank_transfer',
    'from_currency_id' => 'USD',
    'to_currency_id' => 'NGN',
    'from_amount' => 100,
    'bank_id' => 'bank-id',
    'account_number' => '0123456789',
    'note' => 'Acme Ltd', // optional
    'merchant_reference' => 'order-12345', // optional
]);
```

`note` is optional. When set, it appears in the transaction description; if empty, it defaults to the business name.

`merchant_reference` is optional. It is a string of maximum 255 characters. The value must be unique for each business. See [merchant reference](#merchant-reference).

Always required:

- `wallet_id`
- `customer_id`
- `method`
- `from_currency_id`
- `to_currency_id`
- one of `from_amount` or `to_amount`

### `bank_transfer`

Extra requirements depend on `to_currency_id`.

For `NGN`:

- `bank_id`
- `account_number`

For `GBP`:

- `sort_code`
- `account_number`
- `account_name`

For `EUR`:

- `iban`
- `bic_code`
- `account_name`

### `interac`

```php
$payout = $blaaiz->payouts()->initiate([
    'wallet_id' => 'wallet-id',
    'customer_id' => 'customer-id',
    'method' => 'interac',
    'from_currency_id' => 'USD',
    'to_currency_id' => 'CAD',
    'from_amount' => 100,
    'email' => 'recipient@example.com',
    'interac_first_name' => 'John',
    'interac_last_name' => 'Doe',
]);
```

Required:

- `email`
- `interac_first_name`
- `interac_last_name`

### `ach`

```php
$payout = $blaaiz->payouts()->initiate([
    'wallet_id' => 'wallet-id',
    'customer_id' => 'customer-id',
    'method' => 'ach',
    'from_currency_id' => 'USD',
    'to_currency_id' => 'USD',
    'from_amount' => 100,
    'type' => 'individual',
    'account_number' => '1234567890',
    'account_name' => 'John Doe',
    'account_type' => 'checking',
    'bank_name' => 'Test Bank',
    'routing_number' => '021000021',
]);
```

Required:

- `type`
- `account_number`
- `account_name`
- `account_type`
- `bank_name`
- `routing_number`

### `wire`

```php
$payout = $blaaiz->payouts()->initiate([
    'wallet_id' => 'wallet-id',
    'customer_id' => 'customer-id',
    'method' => 'wire',
    'from_currency_id' => 'USD',
    'to_currency_id' => 'USD',
    'from_amount' => 100,
    'type' => 'business',
    'account_number' => '1234567890',
    'account_name' => 'Example Ltd',
    'account_type' => 'checking',
    'bank_name' => 'Test Bank',
    'routing_number' => '021000021',
    'swift_code' => 'BOFAUS3N',
]);
```

Adds one more required field:

- `swift_code`

### `crypto`

```php
$payout = $blaaiz->payouts()->initiate([
    'wallet_id' => 'wallet-id',
    'customer_id' => 'customer-id',
    'method' => 'crypto',
    'from_currency_id' => 'USD',
    'to_currency_id' => 'USDT',
    'from_amount' => 100,
    'wallet_address' => 'TExampleAddress',
    'wallet_token' => 'USDT',
    'wallet_network' => 'TRON',
]);
```

Required:

- `wallet_address`
- `wallet_token`
- `wallet_network`

## Passing additional fields

The payout payload is forwarded to the API as-is, so any field documented in the [API reference](https://docs.business.blaaiz.com) can be included even if it is not listed here (for example, `note`). The SDK only validates the fields it knows about and does not reject extra keys.

## merchant reference

`merchant_reference` is an optional string of maximum 255 characters on `initiate`. It lets you attach your own reference to a payout.

The value must be unique for each business. If you send a duplicate value, the API returns HTTP 422 with this error:

```json
{
    "message": "...",
    "errors": {
        "merchant_reference": ["Could not proceed. Kindly check your merchant reference and try again"]
    }
}
```

Two different businesses can use the same value. The API returns `merchant_reference` on the payout transaction, on transaction list items, and on the payout webhook. To find a transaction by this value, see [Transactions](transactions-banks-currencies-rates.md).
