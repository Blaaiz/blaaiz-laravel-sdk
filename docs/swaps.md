# Swaps

## `initiate(array $swapData)`

```php
$swap = $blaaiz->swaps()->initiate([
    'from_business_wallet_id' => 'wallet-id-usd',
    'to_business_wallet_id' => 'wallet-id-ngn',
    'amount' => 100,
    'amount_type' => 'from', // optional: 'from' (default) or 'to'
]);
```

Required:

- `from_business_wallet_id`
- `to_business_wallet_id`
- `amount`
