# Transactions, Banks, Currencies, And Rates

## Transactions

### `list(array $filters = [])`

```php
$transactions = $blaaiz->transactions()->list([
    'status' => 'SUCCESSFUL',
    'merchant_reference' => 'order-12345',
]);
```

Optional filters:

- `start_date`
- `end_date`
- `wallet_id`
- `customer_id`
- `type` (`SEND_MONEY`, `FUND_WALLET`, or `SWAP`)
- `status` (`FAILED`, `SUCCESSFUL`, or `EXPIRED`)
- `merchant_reference`

Transaction list items include `merchant_reference`. The value is `null` when you did not set it.

### Payer details in `source_information`

For a collection, `source_information` carries these payer keys for a bank transfer: `account_name`, `account_number`, `bank_name`, `sort_code`, `bank_swift_code`, `description`, and `narration`. Each key is always present. The value is `null` when the collection method does not supply it. Only NGN collections set `narration`. All of these keys are `null` for payouts and swaps. For an Interac collection, `collection_email` and `collection_name` hold the payer.

### `get(string $transactionId)`

```php
$transaction = $blaaiz->transactions()->get('transaction-id');
```

The `$transactionId` argument accepts a transaction id, a reference, or a `merchant_reference`. The API resolves the value in that order. The response includes `merchant_reference`, which is `null` when you did not set it.

## Banks

### `list(array $filters = [])`

```php
$banks = $blaaiz->banks()->list();

// Filter the bank list.
$ngnBanks = $blaaiz->banks()->list([
    'currency' => 'NGN',
    'country' => 'NG',
]);
```

Optional filters:

- `currency`
- `country`
- `country_id`

### `lookupAccount(array $lookupData)`

```php
$account = $blaaiz->banks()->lookupAccount([
    'account_number' => '0123456789',
    'bank_id' => 'bank-id',
]);
```

Required:

- `account_number`
- `bank_id`

### `verifyPayee(array $payeeData)`

Verifies a UK payee with Confirmation of Payee.

```php
$result = $blaaiz->banks()->verifyPayee([
    'sort_code' => '12-34-56',
    'account_number' => '12345678',
    'account_name' => 'John Doe',
]);
```

Required:

- `sort_code`
- `account_number`
- `account_name`

### `verifyIban(array $ibanData)`

Checks whether an IBAN is reachable through SEPA.

```php
$result = $blaaiz->banks()->verifyIban([
    'iban' => 'DE89370400440532013000',
]);
```

Required:

- `iban`

## Mobile money operators

### `list(array $filters = [])`

Lists the mobile money operators. Use the `id` of an operator as `mobile_money_operator_id` in a [mobile money payout](payouts.md#mobile_money).

```php
$operators = $blaaiz->momoOperators()->list([
    'currency_id' => 'currency-id',
]);
```

Optional filters:

- `currency_id` (the destination currency id; use this one when you can)
- `country_id`

Each operator has `id`, `name`, `code`, and `country_id`.

## Currencies

### `list()`

```php
$currencies = $blaaiz->currencies()->list();
```

Each currency also has `country_id` and a `country` object with `id`, `name`, `short_name`, and `alt_short_name`. Use them to tell apart currencies that exist for more than one country, such as `XOF`.

## Rates

### `list(?string $searchTerm = null)`

```php
$allRates = $blaaiz->rates()->list();

$usdRates = $blaaiz->rates()->list('USD');
```
