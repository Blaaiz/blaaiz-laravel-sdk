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

## Read captured data

**Warning:** Applicant data and documents are personal data. Do not log this data. Do not cache this data. Every response in this section carries a `Cache-Control: no-store` header.

These three methods need the `compliance-kyc:pii:read` scope. Blaaiz grants this scope to a credential only on request. With OAuth, a token without the scope gets HTTP 403. Each method returns HTTP 409 until the session status is `APPROVED` or `REJECTED`.

### `getSessionApplicantData(string $sessionId)`

Gets the applicant data that Signa captured for an approved or rejected session.

```php
$applicantData = $blaaiz->signa()->getSessionApplicantData($sessionId);
$applicant = $applicantData['data']['data'];
```

The response data is `null` when the session has a verdict but Signa captured no applicant data. Every field except `session_id` and `extracted_at` can be `null`. The field `document.number` is `null` when the live provider read failed.

### `listSessionDocuments(string $sessionId)`

Lists the documents that Signa captured for an approved or rejected session.

```php
$documents = $blaaiz->signa()->listSessionDocuments($sessionId);
```

Each item has a `kind` of `DOCUMENT`, `SELFIE`, `PROOF_OF_ADDRESS`, `LIVENESS_REFERENCE`, or `OTHER`. An item's `unavailable_reason` is `NOT_RETAINED`, `RETRIEVAL_FAILED`, or `null`.

### `getSessionDocument(string $sessionId, string $documentId)`

Gets a download link for one document. The response never contains the document bytes.

```php
$document = $blaaiz->signa()->getSessionDocument($sessionId, $documentId);
$downloadUrl = $document['data']['data']['url'];
```

The link expires after 15 minutes. Call `getSessionDocument()` again for a new link after it expires. Anyone who has the download link can download the document until the link expires. Do not log the link. Do not send it to a client that you do not control.

This method returns HTTP 410 when Blaaiz no longer retains the document. This method also has its own rate limit: 30 requests per minute and 600 requests per hour, per business. Above these limits, the API returns HTTP 429.

## Webhooks

Signa sends session callbacks to the `kyc_url` of your webhook configuration. To verify a callback, see [Verify Signa webhooks](webhooks.md#verify-signa-webhooks).

## Errors

A validation failure returns HTTP 422. An unknown session or an unknown document returns HTTP 404. Both raise a `BlaaizException` — see [Error Handling](../README.md#error-handling).

## OAuth scopes

Signa uses four scopes: `compliance-kyc:read`, `compliance-kyc:create`, `compliance-kyc:cancel`, and `compliance-kyc:pii:read`. The SDK includes all four in its default scope list. The API drops any scope that your credential does not hold, so a business without Signa access, or without the PII scope, can still get a token.
