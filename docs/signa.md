# Signa Merchant KYC/KYB Sessions

Signa manages verification sessions for your business's own customers. A session collects one or more requirements: `DOCUMENTS`, `SELFIE`, `FACE_MATCH`, and `PROOF_OF_ADDRESS`.

## `createSession(array $sessionData)`

Creates a verification session.

```php
$session = $blaaiz->signa()->createSession([
    'customer_reference' => 'customer-123',
    'idempotency_key' => 'signa-request-123',
    'requirements' => ['DOCUMENTS', 'SELFIE', 'FACE_MATCH'],
    'fulfilment_mode' => 'HOSTED',
    'applicant' => [
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'country' => 'GBR',
    ],
]);

$sessionId = $session['data']['data']['id'];
```

Required:

- `customer_reference` — max 100 characters
- `idempotency_key` — max 100 characters. A repeated key returns the same session.
- `requirements` — a supported set. See [Supported requirement sets](#supported-requirement-sets).

Optional:

- `fulfilment_mode` — `HOSTED` or `HEADLESS`
- `applicant` — `first_name`, `last_name` (max 100 characters each), `dob` (`YYYY-MM-DD`), and `country` (an ISO 3166-1 alpha-3 code, for example `GBR`)

## `create(array $sessionData)`

Alias for `createSession()`.

## `listSessions(array $filters = [])`

Lists sessions for the business.

```php
$sessions = $blaaiz->signa()->listSessions(['limit' => 20, 'offset' => 0]);
```

Accepted filters:

- `limit` — 1 to 100. The default is 20.
- `offset` — 0 or more. The default is 0.

With no filters, the API returns the first 20 sessions.

## `list(array $filters = [])`

Alias for `listSessions()`.

## `getSession(string $sessionId)`

```php
$session = $blaaiz->signa()->getSession($sessionId);
```

## `get(string $sessionId)`

Alias for `getSession()`.

## `submitSession(string $sessionId)`

Submits a `HEADLESS` session for review.

```php
$blaaiz->signa()->submitSession($sessionId);
```

## `submit(string $sessionId)`

Alias for `submitSession()`.

## `cancelSession(string $sessionId)`

```php
$blaaiz->signa()->cancelSession($sessionId);
```

## `cancel(string $sessionId)`

Alias for `cancelSession()`.

## `createDocumentUploadUrl(string $sessionId, array $uploadData)`

Requests a short-lived URL for a direct upload.

```php
$upload = $blaaiz->signa()->createDocumentUploadUrl($sessionId, [
    'file_name' => 'passport.jpg',
    'id_doc_type' => 'PASSPORT',
]);
```

Required:

- `file_name`
- `id_doc_type` — one of `PASSPORT`, `ID_CARD`, `DRIVERS`, `RESIDENCE_PERMIT`, `UTILITY_BILL`, `BANK_STATEMENT`, `SELFIE`

The response data has `url`, `file_name`, and `headers`. Send a `PUT` request with the file bytes to `url`. Send exactly the `headers` from the response, because they are part of the URL signature. The URL is short-lived.

## `uploadSessionDocument(string $sessionId, array $documentData)`

Registers a document against the session. Use one transport per call: a staged `file_name` from `createDocumentUploadUrl()`, or inline `content_base64`. Use `content_base64` only for a very small file. The API can reject a request body that is larger than approximately 8 KB.

```php
// Inline upload (very small files only)
$blaaiz->signa()->uploadSessionDocument($sessionId, [
    'filename' => 'passport.jpg',
    'content_type' => 'image/jpeg',
    'id_doc_type' => 'PASSPORT',
    'country' => 'GBR',
    'content_base64' => '...base64 document bytes...',
]);

// Staged upload: PUT the bytes to $upload['data']['data']['url'] first, then register the file_name
$blaaiz->signa()->uploadSessionDocument($sessionId, [
    'filename' => 'passport.jpg',
    'content_type' => 'image/jpeg',
    'id_doc_type' => 'PASSPORT',
    'country' => 'GBR',
    'file_name' => $upload['data']['data']['file_name'],
]);
```

Required:

- `filename`
- `content_type` — one of `image/jpeg`, `image/png`, `image/webp`, `application/pdf`
- `id_doc_type`
- `country` — an ISO 3166-1 alpha-3 code
- exactly one of `file_name` or `content_base64`

## `uploadDocument(string $sessionId, array $documentData)`

Alias for `uploadSessionDocument()`.

## `issueVerificationLink(string $sessionId)`

Issues or rotates the customer-facing link for a `HOSTED` session.

```php
$link = $blaaiz->signa()->issueVerificationLink($sessionId);
$verificationLink = $link['data']['data']['verification_link'];
```

## Fulfilment mode rules

- `createDocumentUploadUrl()`, `uploadSessionDocument()`, and `submitSession()` work only on `HEADLESS` sessions.
- `issueVerificationLink()` works only on `HOSTED` sessions.

## Supported requirement sets

The API currently accepts only these combinations of `requirements` and `fulfilment_mode`. It refuses any other combination when you create the session.

| `requirements` | `fulfilment_mode` |
| --- | --- |
| `DOCUMENTS` | `HEADLESS` |
| `DOCUMENTS`, `SELFIE`, `FACE_MATCH` | `HOSTED` |
| `DOCUMENTS`, `SELFIE`, `FACE_MATCH`, `PROOF_OF_ADDRESS` | `HOSTED` |
| `DOCUMENTS`, `PROOF_OF_ADDRESS` | `HOSTED` or `HEADLESS` |
| `PROOF_OF_ADDRESS` | `HOSTED` or `HEADLESS` |

If you do not send `fulfilment_mode`, the API uses the first mode in the row for your set. The settings of your business can limit the modes that you can use.

## Webhooks

Signa sends session callbacks to the `kyc_url` of your webhook configuration. To verify a callback, see [Verify Signa webhooks](webhooks.md#verify-signa-webhooks).

## Errors

A validation failure returns HTTP 422. An unknown session returns HTTP 404. Both raise a `BlaaizException` — see [Error Handling](../README.md#error-handling).

## OAuth scopes

Signa uses three scopes: `compliance-kyc:read`, `compliance-kyc:create`, and `compliance-kyc:cancel`. The SDK includes them in its default scope list. A business without Signa access can still get a token, because the API ignores requested scopes that the client does not hold.
