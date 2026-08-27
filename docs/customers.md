# Customers

## `create(array $customerData)`

```php
$customer = $blaaiz->customers()->create([
    'type' => 'individual',
    'email' => 'john@example.com',
    'country' => 'NG',
    'first_name' => 'John',
    'last_name' => 'Doe',
    'id_type' => 'passport',
    'id_number' => 'A12345678',
]);
```

Always required:

- `type` (`individual` or `business`)
- `email`
- `country`

An `individual` customer also requires these fields:

- `first_name`
- `last_name`
- `id_type`
- `id_number`

A `business` customer also requires these fields:

- `business_name`
- `registration_number`
- `incorporation_country`

Do not send `id_type` or `id_number` for a business customer. The API rejects these fields for a business.

```php
$business = $blaaiz->customers()->create([
    'type' => 'business',
    'email' => 'ops@acme.example.com',
    'country' => 'NG',
    'business_name' => 'Acme Corp',
    'registration_number' => 'RC12345',
    'incorporation_country' => 'NG',
]);
```

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

## `submit(string $customerId)`

Submits the customer for verification. This call sends no body.

```php
$result = $blaaiz->customers()->submit('customer-id');
```

## `upgradeKybScope(string $customerId, array $upgradeData)`

Upgrades a business customer from a minimal KYB scope to a full KYB scope.

```php
$result = $blaaiz->customers()->upgradeKybScope('customer-id', [
    'owners' => [
        ['first_name' => 'Jane', 'last_name' => 'Doe', 'ownership_percentage' => 100],
    ],
]);
```

Required:

- `owners` (array with at least one owner; the ownership must sum to 100)

## `deleteOwner(string $customerId, string $ownerId)`

```php
$result = $blaaiz->customers()->deleteOwner('customer-id', 'owner-id');
```

## `getOwnerFilePresignedUrl(string $customerId, string $ownerId, array $presignedData)`

Gets a presigned URL to upload one owner identity file.

```php
$result = $blaaiz->customers()->getOwnerFilePresignedUrl('customer-id', 'owner-id', [
    'file_category' => 'id_document_front',
]);
```

Required:

- `file_category` (`id_document_front` or `id_document_back`)

## `uploadOwnerFiles(string $customerId, string $ownerId, array $fileData)`

Associates uploaded file ids with an owner.

```php
$result = $blaaiz->customers()->uploadOwnerFiles('customer-id', 'owner-id', [
    'id_document_front' => 'file-id',
    'id_document_back' => 'file-id', // optional
]);
```

Required:

- `id_document_front`

## Customer documents

Use these methods to manage business KYB documents for a customer.

### `listDocuments(string $customerId)`

```php
$documents = $blaaiz->customers()->listDocuments('customer-id');
```

### `getDocument(string $customerId, string $documentId)`

```php
$document = $blaaiz->customers()->getDocument('customer-id', 'document-id');
```

### `getDocumentPresignedUrl(string $customerId)`

Gets a presigned URL to upload one document file. This call sends no body.

```php
$result = $blaaiz->customers()->getDocumentPresignedUrl('customer-id');
```

### `createDocument(string $customerId, array $documentData)`

```php
$document = $blaaiz->customers()->createDocument('customer-id', [
    'type' => 'PROOF_OF_ADDRESS',
    'name' => 'Utility bill',
    'file_id' => 'file-id',
    'description' => 'April 2026', // optional
]);
```

Required:

- `type`
- `name`
- `file_id`

### `updateDocument(string $customerId, string $documentId, array $documentData)`

```php
$document = $blaaiz->customers()->updateDocument('customer-id', 'document-id', [
    'name' => 'Renamed document',
]);
```

### `deleteDocument(string $customerId, string $documentId)`

```php
$result = $blaaiz->customers()->deleteDocument('customer-id', 'document-id');
```
