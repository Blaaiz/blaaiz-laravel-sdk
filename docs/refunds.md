# Refunds

## `initiate(array $refundData)`

Starts a refund for a transaction.

```php
$refund = $blaaiz->refunds()->initiate([
    'transaction_id' => 'transaction-id',
    'reason' => 'Customer request', // optional, max 250 characters
    'reference' => 'ref-12345',     // optional, max 100 characters
]);
```

Required:

- `transaction_id`

## `get(string $refundId)`

```php
$refund = $blaaiz->refunds()->get('refund-id');
```

The refund object includes these fields: `id`, `status`, `type`, `amount`, `currency`, `transaction_id`, `reference`, `business_customer_id`, `refund_reference`, `failure_reason`, `created_at`, and `updated_at`.
