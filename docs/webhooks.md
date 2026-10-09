# Webhooks

## `register(array $webhookData)`

```php
$webhook = $blaaiz->webhooks()->register([
    'collection_url' => 'https://example.com/webhooks/collections',
    'payout_url' => 'https://example.com/webhooks/payouts',
    'kyc_url' => 'https://example.com/webhooks/kyc', // optional, for Signa callbacks
]);
```

Required:

- `collection_url`
- `payout_url`

Optional:

- `kyc_url` — receives Signa and Signa ID callbacks

## `get()`

```php
$webhook = $blaaiz->webhooks()->get();
```

## `update(string $webhookId, array $webhookData)`

```php
$updated = $blaaiz->webhooks()->update('webhook-id', [
    'collection_url' => 'https://example.com/webhooks/new-collections',
    'payout_url' => 'https://example.com/webhooks/new-payouts',
]);
```

`kyc_url` is optional. If you do not send `kyc_url`, the API keeps the current value. To remove the value, send `'kyc_url' => null`.

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

### Verify Signa webhooks

Signa and Signa ID callbacks go to your `kyc_url`. The events are `merchant.kyc.session.completed`, `merchant.kyc.session.expired`, and `signa_id.grant.revoked`. After `signa_id.grant.revoked`, the release methods return no data for `data.release_id`.

Signa sends `kyc_url` callbacks with the same `x-blaaiz-timestamp` and `x-blaaiz-signature` headers and HMAC-SHA256 scheme as collection and payout webhooks. Use `verifySignature()` or `constructEvent()` for a Signa callback the same way you use them for other webhooks.
