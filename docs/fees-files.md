# Fees And Files

## Fees

### `getBreakdown(array $feeData)`

Provide:

- `from_currency_id`
- `to_currency_id`
- one of `from_amount` or `to_amount`

```php
$fees = $blaaiz->fees()->getBreakdown([
    'from_currency_id' => 'USD',
    'to_currency_id' => 'NGN',
    'from_amount' => 100,
]);
```

You can also quote by target amount:

```php
$fees = $blaaiz->fees()->getBreakdown([
    'from_currency_id' => 'USD',
    'to_currency_id' => 'NGN',
    'to_amount' => 150000,
]);
```

## Files

### `getPresignedUrl(array $fileData)`

Gets a presigned URL for manual file uploads.

```php
$upload = $blaaiz->files()->getPresignedUrl([
    'customer_id' => 'customer-id',
    'file_category' => 'identity',
]);
```

Typical manual flow:

1. Call `getPresignedUrl()`
2. Upload the file contents to the returned URL
3. Associate the returned `file_id` with `customers()->uploadFiles()`

If you want the SDK to do all 3 steps for you, use `customers()->uploadFileComplete()` instead.
