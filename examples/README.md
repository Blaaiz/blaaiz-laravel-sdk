# Examples

These examples are Laravel-oriented snippets you can drop into an application that has installed `blaaiz/blaaiz-laravel-sdk`.

## What is included

- `Console/CheckRatesCommand.php`: example Artisan command using dependency injection
- `Http/Controllers/CustomerController.php`: create a customer from a controller
- `Http/Controllers/PayoutController.php`: initiate a payout from a controller
- `Http/Controllers/UploadKycController.php`: upload a document with `uploadFileComplete()`
- `Http/Controllers/UploadKycFromS3Controller.php`: upload a document from an S3 disk object
- `Webhooks/BlaaizWebhookController.php`: verify and parse incoming webhooks

## Usage styles

You can use the package in Laravel through:

- the `Blaaiz` facade
- dependency injection with `Blaaiz\LaravelSdk\Blaaiz`

## Setup

1. Install the package with Composer.
2. Configure `BLAAIZ_CLIENT_ID` and `BLAAIZ_CLIENT_SECRET`, or `BLAAIZ_API_KEY`.
3. Optionally publish config with `php artisan vendor:publish --tag=blaaiz-config`.
4. Set `BLAAIZ_WEBHOOK_SECRET` if you are verifying webhook signatures.
