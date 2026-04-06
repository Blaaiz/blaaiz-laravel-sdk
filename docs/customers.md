# Customers

## `create(array $customerData)`

```php
$customer = $blaaiz->customers()->create([
    'first_name' => 'John',
    'last_name' => 'Doe',
    'type' => 'individual',
    'email' => 'john@example.com',
    'country' => 'NG',
    'id_type' => 'passport',
    'id_number' => 'A12345678',
]);
```

For business customers, provide `business_name` instead of `first_name` and `last_name`.

## `list()`

```php
$customers = $blaaiz->customers()->list();
```

## `get(string $customerId)`

```php
$customer = $blaaiz->customers()->get('customer-id');
```

## `update(string $customerId, array $updateData)`

```php
$updatedCustomer = $blaaiz->customers()->update('customer-id', [
    'email' => 'updated@example.com',
]);
```

## `addKyc(string $customerId, array $kycData)`

```php
$kyc = $blaaiz->customers()->addKyc('customer-id', [
    'document_type' => 'passport',
    'document_number' => 'A12345678',
]);
```

## `uploadFiles(string $customerId, array $fileData)`

Associates previously uploaded file IDs with a customer.

```php
$association = $blaaiz->customers()->uploadFiles('customer-id', [
    'id_file' => 'file-id',
]);
```

Common file keys:

- `id_file`
- `id_file_back`
- `proof_of_address_file`
- `liveness_check_file`

## `uploadFileComplete(string $customerId, array $fileOptions)`

Handles the full 3-step file flow:

1. Gets a presigned URL
2. Uploads the file
3. Associates the file with the customer

Required fields:

- `file`
- `file_category`

Allowed `file_category` values:

- `identity`
- `identity_back`
- `proof_of_address`
- `liveness_check`

Optional fields:

- `filename`
- `content_type`

### Local file path

```php
$result = $blaaiz->customers()->uploadFileComplete('customer-id', [
    'file' => __DIR__ . '/passport.pdf',
    'file_category' => 'identity',
]);
```

### Raw file contents

```php
$result = $blaaiz->customers()->uploadFileComplete('customer-id', [
    'file' => file_get_contents(__DIR__ . '/bill.pdf'),
    'file_category' => 'proof_of_address',
    'filename' => 'bill.pdf',
    'content_type' => 'application/pdf',
]);
```

### Base64 string

```php
$result = $blaaiz->customers()->uploadFileComplete('customer-id', [
    'file' => $base64Image,
    'file_category' => 'identity',
    'filename' => 'passport.png',
    'content_type' => 'image/png',
]);
```

### Data URL

```php
$result = $blaaiz->customers()->uploadFileComplete('customer-id', [
    'file' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...',
    'file_category' => 'liveness_check',
]);
```

### Public URL

```php
$result = $blaaiz->customers()->uploadFileComplete('customer-id', [
    'file' => 'https://example.com/documents/passport.jpg',
    'file_category' => 'identity',
]);
```

The response includes the API response plus:

- `file_id`
- `presigned_url`

## `listBeneficiaries(string $customerId)`

```php
$beneficiaries = $blaaiz->customers()->listBeneficiaries('customer-id');
```

## `getBeneficiary(string $customerId, string $beneficiaryId)`

```php
$beneficiary = $blaaiz->customers()->getBeneficiary('customer-id', 'beneficiary-id');
```
