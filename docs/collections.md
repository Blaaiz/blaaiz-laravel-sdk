# Collections

## `initiate(array $collectionData)`

```php
$collection = $blaaiz->collections()->initiate([
    'method' => 'open_banking',
    'amount' => 100,
    'wallet_id' => 'wallet-id',
    'redirect_url' => 'https://example.com/callback',
    'merchant_reference' => 'order-12345', // optional
]);
```

Always required:

- `method` (`open_banking` or `card`)
- `amount`
- `wallet_id`

The `card` method also requires these fields:

- `customer_id`
- `card_holder_name`
- `card_number`
- `expiry`
- `cvc`

`merchant_reference` is optional. It is a string of maximum 255 characters. The value must be unique for each business. See [merchant reference](#merchant-reference).

## `initiateCrypto(array $cryptoData)`

```php
$cryptoCollection = $blaaiz->collections()->initiateCrypto([
    'amount' => 100,
    'wallet_id' => 'wallet-id',
    'network' => 'TRON',
    'token' => 'USDT',
]);
```

Required fields:

- `amount`
- `wallet_id`
- `network`
- `token`

## `getCryptoNetworks(array $filters = [])`

```php
$networks = $blaaiz->collections()->getCryptoNetworks();

// Filter by transaction type (collection or payout).
$payoutNetworks = $blaaiz->collections()->getCryptoNetworks([
    'transaction_type' => 'payout',
]);
```

## `attachCustomer(array $attachData)`

Associates a customer with an existing collection transaction.

```php
$attached = $blaaiz->collections()->attachCustomer([
    'customer_id' => 'customer-id',
    'transaction_id' => 'transaction-id',
]);
```

## `initiateInteracMoneyRequest(array $interacData)`

Sends an Interac money request to a payer.

```php
$request = $blaaiz->collections()->initiateInteracMoneyRequest([
    'amount' => 100,
    'email' => 'payer@example.com',
    'customer_name' => 'John Doe', // optional
    'expiry_hours' => 24,          // optional, 1 to 120
    'note' => 'Invoice 42',        // optional
]);
```

Required fields:

- `amount`
- `email`

## `acceptInteracMoneyRequest(array $interacData)`

```php
$result = $blaaiz->collections()->acceptInteracMoneyRequest([
    'reference_number' => 'interac-reference',
    'security_answer' => 'answer',
    'email' => 'sender@example.com',
]);
```

The SDK enforces only `reference_number`. The other fields depend on the flow that you use.

## merchant reference

`merchant_reference` is an optional string of maximum 255 characters on `initiate`. It lets you attach your own reference to a collection.

The value must be unique for each business. If you send a duplicate value, the API returns HTTP 422 with this error:

```json
{
    "message": "...",
    "errors": {
        "merchant_reference": ["Could not proceed. Kindly check your merchant reference and try again"]
    }
}
```

Two different businesses can use the same value. The API returns `merchant_reference` on the transaction, on transaction list items, and on the collection webhook. To find a transaction by this value, see [Transactions](transactions-banks-currencies-rates.md).
