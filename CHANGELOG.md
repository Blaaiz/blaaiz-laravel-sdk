# Changelog

## [1.6.0](https://github.com/Blaaiz/blaaiz-laravel-sdk/compare/v1.5.0...v1.6.0) (2026-10-09)


### Features

* support mobile money payouts and list mobile money operators ([0c7f913](https://github.com/Blaaiz/blaaiz-laravel-sdk/commit/0c7f913ca546c5db5fc37bf45dd84a247632587d))

## [1.5.0](https://github.com/Blaaiz/blaaiz-laravel-sdk/compare/v1.4.0...v1.5.0) (2026-09-28)


### Features

* **oauth:** request the compliance-kyc scopes by default ([da229b4](https://github.com/Blaaiz/blaaiz-laravel-sdk/commit/da229b42da29af48a657a9271046251f7cd5a202))
* **oauth:** request the compliance-kyc:pii:read scope by default ([05dcc3d](https://github.com/Blaaiz/blaaiz-laravel-sdk/commit/05dcc3d721cd9b2711986eedc53e3dd06f6e2f10))
* **signa:** add Signa merchant KYC session service ([2b22106](https://github.com/Blaaiz/blaaiz-laravel-sdk/commit/2b221066a89dbc3828b3bec963c1648bc78f4a82))
* **signa:** read captured applicant data and documents ([cd33837](https://github.com/Blaaiz/blaaiz-laravel-sdk/commit/cd33837458447172d45f6c79c39a4e10f9d36027))

## 1.4.0 - 2026-08-28

This release brings the SDK up to date with the current Blaaiz API. All Blaaiz SDKs move to 1.4.0 together, so the same version means the same features in every language.

### Added
- Merchant reference on payouts and collections. Blaaiz saves it, returns it, and lets you find the transaction by it.
- Swaps: move money between two of your business wallets.
- Refunds: start a refund and get a refund.
- Rates: list the exchange rates for your business.
- Bank checks: verify a GBP account (payee) and a EUR IBAN.
- Interac money request: ask a payer for money by email.
- Business customer KYB: add and remove owners, upload owner ID files, upgrade to full KYB, and submit for review.
- Business customer documents: upload, list, get, update, and delete.

### Fixed
- Create a business customer without personal ID fields. The API does not allow them for a business.
- Upload customer files with the correct request method.
- Start a collection without the old, unused `currency` field.
- Update and replay a webhook on the correct address.

### Deprecated
- The swap method is now `initiate()`. The old `swap()` name still works, but it will be removed in a future major version.
