# Webhooks

## `register(array $webhookData)`

```php
$webhook = $blaaiz->webhooks()->register([
    'collection_url' => 'https://example.com/webhooks/collections',
    'payout_url' => 'https://example.com/webhooks/payouts',
]);
```

Required:

- `collection_url`
- `payout_url`

## `get()`

```php
$webhook = $blaaiz->webhooks()->get();
```

## `update(array $webhookData)`

```php
$updated = $blaaiz->webhooks()->update([
    'collection_url' => 'https://example.com/webhooks/new-collections',
]);
```

## `replay(array $replayData)`

```php
$replay = $blaaiz->webhooks()->replay([
    'transaction_id' => 'transaction-id',
]);
```

Required:

- `transaction_id`

## `simulateInteracWebhook(array $simulateData)`

```php
$simulation = $blaaiz->webhooks()->simulateInteracWebhook([
    'interac_email' => 'customer@example.com',
]);
```

## `verifySignature(string $rawBody, string $signature, string $timestamp, string $secret)`

```php
$isValid = $blaaiz->webhooks()->verifySignature(
    $payload,
    $signature,
    $timestamp,
    $webhookSecret
);
```

The signature is computed from:

```text
{timestamp}.{rawBody}
```

using `HMAC-SHA256`.

## `constructEvent(string $payload, string $signature, string $timestamp, string $secret)`

Verifies the signature and parses the JSON payload.

```php
$event = $blaaiz->webhooks()->constructEvent(
    $payload,
    $signature,
    $timestamp,
    $webhookSecret
);
```

The returned array contains the webhook payload plus:

- `verified => true`
- `timestamp`
