# Blaaiz Laravel SDK

A comprehensive Laravel SDK for the Blaaiz RaaS (Remittance as a Service) API. This SDK provides easy-to-use methods for payment processing, collections, payouts, customer management, and more.

## Installation

```bash
composer require blaaiz/blaaiz-laravel-sdk
```

## Quick Start

```php
// Add to your .env file
BLAAIZ_API_KEY=your-api-key-here
BLAAIZ_BASE_URL=https://api-dev.blaaiz.com  // For development
// BLAAIZ_BASE_URL=https://api.blaaiz.com  // For production

// Publish configuration (optional)
php artisan vendor:publish --tag=blaaiz-config

// Test the connection
use Blaaiz\LaravelSdk\Facades\Blaaiz;

$isConnected = Blaaiz::testConnection();
echo $isConnected ? 'API Connected' : 'Connection Failed';
```

## Features

- **Customer Management**: Create, update, and manage customers with KYC verification
- **Collections**: Support for multiple collection methods (Open Banking, Card, Crypto, Bank Transfer)
- **Payouts**: Bank transfers and Interac payouts across multiple currencies
- **Virtual Bank Accounts**: Create and manage virtual accounts for NGN collections
- **Wallets**: Multi-currency wallet management
- **Transactions**: Transaction history and status tracking
- **Webhooks**: Webhook configuration and management with signature verification
- **Files**: Document upload with pre-signed URLs
- **Fees**: Real-time fee calculations and breakdowns
- **Banks & Currencies**: Access to supported banks and currencies
- **Laravel Integration**: Native service provider, facade, and configuration

## Supported Currencies & Methods

### Collections
- **CAD**: Interac (push mechanism)
- **NGN**: Bank Transfer (VBA) and Card Payment
- **USD**: Card Payment
- **EUR/GBP**: Open Banking

### Payouts
- **Bank Transfer**: NGN, GBP, EUR
- **Interac**: CAD transactions
- **ACH**: USD transactions
- **Wire**: USD transactions
- **Crypto**: USDT, USDC on multiple networks

## API Reference

### Customer Management

#### Create a Customer

```php
use Blaaiz\LaravelSdk\Facades\Blaaiz;

$customer = Blaaiz::customers()->create([
    'first_name' => 'John',
    'last_name' => 'Doe',
    'type' => 'individual', // or 'business'
    'email' => 'john.doe@example.com',
    'country' => 'NG',
    'id_type' => 'passport', // drivers_license, passport, id_card, resident_permit
    'id_number' => 'A12345678',
    // 'business_name' => 'Company Name' // Required if type is 'business'
]);

echo 'Customer ID: ' . $customer['data']['data']['id'];
```

#### Get Customer

```php
$customer = Blaaiz::customers()->get('customer-id');
echo 'Customer: ' . json_encode($customer['data']);
```

#### List All Customers

```php
$customers = Blaaiz::customers()->list();
echo 'Customers: ' . json_encode($customers['data']);
```

#### Update Customer

```php
$updatedCustomer = Blaaiz::customers()->update('customer-id', [
    'first_name' => 'Jane',
    'email' => 'jane.doe@example.com'
]);
```

### File Management & KYC

#### Upload Customer Documents

**Method 1: Complete File Upload (Recommended)**
```php
// Option A: Upload from file path
$result = Blaaiz::customers()->uploadFileComplete('customer-id', [
    'file' => file_get_contents('/path/to/passport.jpg'),
    'file_category' => 'identity', // identity, proof_of_address, liveness_check
    'filename' => 'passport.jpg', // Optional
    'content_type' => 'image/jpeg' // Optional
]);

// Option B: Upload from Base64 string
$result = Blaaiz::customers()->uploadFileComplete('customer-id', [
    'file' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
    'file_category' => 'identity'
]);

// Option C: Upload from Data URL (with automatic content type detection)
$result = Blaaiz::customers()->uploadFileComplete('customer-id', [
    'file' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
    'file_category' => 'identity'
]);

// Option D: Upload from Public URL (automatically downloads and uploads)
$result = Blaaiz::customers()->uploadFileComplete('customer-id', [
    'file' => 'https://example.com/documents/passport.jpg',
    'file_category' => 'identity'
]);

echo 'Upload complete: ' . json_encode($result['data']);
echo 'File ID: ' . $result['file_id'];
```

**Method 2: Manual 3-Step Process**
```php
// Step 1: Get pre-signed URL
$presignedUrl = Blaaiz::files()->getPresignedUrl([
    'customer_id' => 'customer-id',
    'file_category' => 'identity' // identity, proof_of_address, liveness_check
]);

// Step 2: Upload file to the pre-signed URL (implement your file upload logic)
// Use Guzzle or any HTTP client to upload the file

// Step 3: Associate file with customer
$fileAssociation = Blaaiz::customers()->uploadFiles('customer-id', [
    'id_file' => $presignedUrl['data']['file_id'] // Use the file_id from step 1
]);
```

> **Note**: The `uploadFileComplete` method is recommended as it handles all three steps automatically: getting the pre-signed URL, uploading the file to S3, and associating the file with the customer. It supports multiple file input formats:
> - **String path/content**: Direct binary data or file path
> - **Base64 string**: Plain base64 encoded data
> - **Data URL**: Complete data URL with mime type (e.g., `data:image/jpeg;base64,/9j/4AAQ...`)
> - **Public URL**: HTTP/HTTPS URL that will be downloaded automatically (supports redirects, content-type detection, and filename extraction)

### Collections

#### Initiate Open Banking Collection (EUR/GBP)

```php
$collection = Blaaiz::collections()->initiate([
    'customer_id' => 'customer-id',
    'wallet_id' => 'wallet-id',
    'amount' => 100.00,
    'currency' => 'EUR', // EUR, GBP, NGN, USD
    'method' => 'open_banking',
    'phone_number' => '+1234567890', // Optional
    'email' => 'customer@example.com', // Optional
    'reference' => 'your-reference', // Optional
    'narration' => 'Payment description', // Optional
    'redirect_url' => 'https://your-site.com/callback' // Optional
]);

echo 'Payment URL: ' . $collection['data']['url'];
echo 'Transaction ID: ' . $collection['data']['transaction_id'];
```

#### Initiate Card Collection (NGN/USD)

```php
$collection = Blaaiz::collections()->initiate([
    'customer_id' => 'customer-id',
    'wallet_id' => 'wallet-id',
    'amount' => 5000,
    'currency' => 'NGN',
    'method' => 'card'
]);

echo 'Payment URL: ' . $collection['data']['url'];
```

#### Accept Interac Money Request (CAD)

```php
// With security answer (standard transfer)
$interac = Blaaiz::collections()->acceptInteracMoneyRequest([
    'reference_number' => 'interac-reference',
    'security_answer' => 'answer',
    'email' => 'sender@example.com' // Optional
]);

// Auto deposit (no security answer required)
$interacAutoDeposit = Blaaiz::collections()->acceptInteracMoneyRequest([
    'reference_number' => 'interac-reference'
]);

echo 'Message: ' . $interac['data']['message'];
```

#### Crypto Collection

```php
// Get available networks
$networks = Blaaiz::collections()->getCryptoNetworks();
echo 'Available networks: ' . json_encode($networks['data']);

// Initiate crypto collection
$cryptoCollection = Blaaiz::collections()->initiateCrypto([
    'amount' => 100,
    'network' => 'ethereum',
    'token' => 'USDT',
    'wallet_id' => 'wallet-id'
]);
```

#### Attach Customer to Collection

```php
$attachment = Blaaiz::collections()->attachCustomer([
    'customer_id' => 'customer-id',
    'transaction_id' => 'transaction-id'
]);
```

### Payouts

#### Bank Transfer Payout (NGN)

```php
$payout = Blaaiz::payouts()->initiate([
    'wallet_id' => 'wallet-id',
    'customer_id' => 'customer-id',
    'method' => 'bank_transfer',
    'from_amount' => 1000, // Use from_amount OR to_amount
    'from_currency_id' => 'NGN',
    'to_currency_id' => 'NGN',
    'bank_id' => 'bank-id', // Required for NGN
    'account_number' => '0123456789',
    'phone_number' => '+2348012345678' // Optional
]);

echo 'Payout Status: ' . $payout['data']['transaction']['status'];
```

#### Bank Transfer Payout (GBP)

```php
$gbpPayout = Blaaiz::payouts()->initiate([
    'wallet_id' => 'wallet-id',
    'customer_id' => 'customer-id',
    'method' => 'bank_transfer',
    'from_amount' => 100,
    'from_currency_id' => 'GBP',
    'to_currency_id' => 'GBP',
    'sort_code' => '123456',
    'account_number' => '12345678',
    'account_name' => 'John Doe'
]);
```

#### Bank Transfer Payout (EUR)

```php
$eurPayout = Blaaiz::payouts()->initiate([
    'wallet_id' => 'wallet-id',
    'customer_id' => 'customer-id',
    'method' => 'bank_transfer',
    'from_amount' => 100,
    'from_currency_id' => 'EUR',
    'to_currency_id' => 'EUR',
    'iban' => 'DE89370400440532013000',
    'bic_code' => 'COBADEFFXXX',
    'account_name' => 'John Doe'
]);
```

#### Interac Payout (CAD)

```php
$interacPayout = Blaaiz::payouts()->initiate([
    'wallet_id' => 'wallet-id',
    'customer_id' => 'customer-id',
    'method' => 'interac',
    'from_amount' => 100,
    'from_currency_id' => 'CAD',
    'to_currency_id' => 'CAD',
    'email' => 'recipient@example.com',
    'interac_first_name' => 'John',
    'interac_last_name' => 'Doe'
]);
```

#### ACH Payout (USD)

```php
$achPayout = Blaaiz::payouts()->initiate([
    'wallet_id' => 'wallet-id',
    'customer_id' => 'customer-id',
    'method' => 'ach',
    'from_amount' => 100,
    'from_currency_id' => 'USD',
    'to_currency_id' => 'USD',
    'type' => 'individual', // individual or business
    'account_number' => '123456789',
    'account_name' => 'John Doe',
    'account_type' => 'checking', // checking or savings
    'bank_name' => 'Chase Bank',
    'routing_number' => '021000021'
]);
```

#### Wire Payout (USD)

```php
$wirePayout = Blaaiz::payouts()->initiate([
    'wallet_id' => 'wallet-id',
    'customer_id' => 'customer-id',
    'method' => 'wire',
    'from_amount' => 1000,
    'from_currency_id' => 'USD',
    'to_currency_id' => 'USD',
    'type' => 'individual',
    'account_number' => '123456789',
    'account_name' => 'John Doe',
    'account_type' => 'checking',
    'bank_name' => 'Chase Bank',
    'routing_number' => '021000021',
    'swift_code' => 'CHASUS33'
]);
```

#### Crypto Payout

```php
$cryptoPayout = Blaaiz::payouts()->initiate([
    'wallet_id' => 'wallet-id',
    'customer_id' => 'customer-id',
    'method' => 'crypto',
    'from_amount' => 100,
    'from_currency_id' => 'USD',
    'to_currency_id' => 'USDT',
    'wallet_address' => '0x1234567890abcdef...',
    'wallet_token' => 'USDT', // USDT or USDC
    'wallet_network' => 'ETHEREUM_MAINNET' // BSC_MAINNET, ETHEREUM_MAINNET, TRON_MAINNET, MATIC_MAINNET
]);
```

#### Using to_amount Instead of from_amount

You can specify the exact amount the recipient should receive:

```php
$payout = Blaaiz::payouts()->initiate([
    'wallet_id' => 'wallet-id',
    'customer_id' => 'customer-id',
    'method' => 'bank_transfer',
    'to_amount' => 50000, // Recipient gets exactly this amount
    'from_currency_id' => 'USD',
    'to_currency_id' => 'NGN',
    'bank_id' => 'bank-id',
    'account_number' => '0123456789'
]);
```

### Virtual Bank Accounts

#### Create Virtual Bank Account

```php
$vba = Blaaiz::virtualBankAccounts()->create([
    'wallet_id' => 'wallet-id',
    'account_name' => 'John Doe'
]);

echo 'Account Number: ' . $vba['data']['account_number'];
echo 'Bank Name: ' . $vba['data']['bank_name'];
```

#### List Virtual Bank Accounts

You can optionally filter by `wallet_id`, `customer_id`, or both.

```php
// All virtual bank accounts
$vbas = Blaaiz::virtualBankAccounts()->list();

// Filter by wallet
$vbasByWallet = Blaaiz::virtualBankAccounts()->list('wallet-id');

// Filter by customer
$vbasByCustomer = Blaaiz::virtualBankAccounts()->list(null, 'customer-id');

// Filter by both wallet and customer
$vbasByBoth = Blaaiz::virtualBankAccounts()->list('wallet-id', 'customer-id');

echo 'All: ' . json_encode($vbas['data']);
echo 'By Wallet: ' . json_encode($vbasByWallet['data']);
echo 'By Customer: ' . json_encode($vbasByCustomer['data']);
echo 'By Both: ' . json_encode($vbasByBoth['data']);
```

#### Close Virtual Bank Account

```php
// Close without reason
$closed = Blaaiz::virtualBankAccounts()->close('vba-id');

// Close with reason
$closed = Blaaiz::virtualBankAccounts()->close('vba-id', 'No longer needed');

echo 'Status: ' . $closed['data']['status'];
```

### Wallets

#### List All Wallets

```php
$wallets = Blaaiz::wallets()->list();
echo 'Wallets: ' . json_encode($wallets['data']);
```

#### Get Specific Wallet

```php
$wallet = Blaaiz::wallets()->get('wallet-id');
echo 'Wallet Balance: ' . $wallet['data']['balance'];
```

### Transactions

#### List Transactions

```php
$transactions = Blaaiz::transactions()->list([
    'page' => 1,
    'limit' => 10,
    'status' => 'SUCCESSFUL' // Optional filter
]);

echo 'Transactions: ' . json_encode($transactions['data']);
```

#### Get Transaction Details

```php
$transaction = Blaaiz::transactions()->get('transaction-id');
echo 'Transaction: ' . json_encode($transaction['data']);
```

### Banks & Currencies

#### List Banks

```php
$banks = Blaaiz::banks()->list();
echo 'Available Banks: ' . json_encode($banks['data']);
```

#### Bank Account Lookup

```php
$accountInfo = Blaaiz::banks()->lookupAccount([
    'account_number' => '0123456789',
    'bank_id' => '1'
]);

echo 'Account Name: ' . $accountInfo['data']['account_name'];
```

#### List Currencies

```php
$currencies = Blaaiz::currencies()->list();
echo 'Supported Currencies: ' . json_encode($currencies['data']);
```

### Fees

#### Get Fee Breakdown

```php
// Using from_amount (calculate what recipient gets)
$feeBreakdown = Blaaiz::fees()->getBreakdown([
    'from_currency_id' => 'NGN',
    'to_currency_id' => 'CAD',
    'from_amount' => 100000
]);

echo 'You send: ' . $feeBreakdown['data']['you_send'];
echo 'Recipient gets: ' . $feeBreakdown['data']['recipient_gets'];
echo 'Total fees: ' . $feeBreakdown['data']['total_fees'];

// Using to_amount (calculate what you need to send)
$feeBreakdown = Blaaiz::fees()->getBreakdown([
    'from_currency_id' => 'USD',
    'to_currency_id' => 'NGN',
    'to_amount' => 50000 // Recipient should get exactly this
]);

echo 'You send: ' . $feeBreakdown['data']['you_send'];
```

### Webhooks

#### Register Webhooks

```php
$webhook = Blaaiz::webhooks()->register([
    'collection_url' => 'https://your-domain.com/webhooks/collection',
    'payout_url' => 'https://your-domain.com/webhooks/payout'
]);
```

#### Get Webhook Configuration

```php
$webhookConfig = Blaaiz::webhooks()->get();
echo 'Webhook URLs: ' . json_encode($webhookConfig['data']);
```

#### Replay Webhook

```php
$replay = Blaaiz::webhooks()->replay([
    'transaction_id' => 'transaction-id'
]);
```

#### Simulate Interac Webhook (Non-Production Only)

```php
// For testing Interac webhooks in development/sandbox environment
$simulate = Blaaiz::webhooks()->simulateInteracWebhook([
    'interac_email' => 'sender@example.com'
]);

echo 'Message: ' . $simulate['message'];
```

## Advanced Usage

### Complete Payout Workflow

```php
$completePayoutResult = Blaaiz::createCompletePayout([
    'customer_data' => [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'type' => 'individual',
        'email' => 'john@example.com',
        'country' => 'NG',
        'id_type' => 'passport',
        'id_number' => 'A12345678'
    ],
    'payout_data' => [
        'wallet_id' => 'wallet-id',
        'method' => 'bank_transfer',
        'from_amount' => 1000,
        'from_currency_id' => '1',
        'to_currency_id' => '1',
        'account_number' => '0123456789',
        'bank_id' => '1',
        'phone_number' => '+2348012345678'
    ]
]);

echo 'Customer ID: ' . $completePayoutResult['customer_id'];
echo 'Payout: ' . json_encode($completePayoutResult['payout']);
echo 'Fees: ' . json_encode($completePayoutResult['fees']);
```

### Complete Collection Workflow

```php
$completeCollectionResult = Blaaiz::createCompleteCollection([
    'customer_data' => [
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'type' => 'individual',
        'email' => 'jane@example.com',
        'country' => 'NG',
        'id_type' => 'drivers_license',
        'id_number' => 'ABC123456'
    ],
    'collection_data' => [
        'method' => 'card',
        'amount' => 5000,
        'wallet_id' => 'wallet-id'
    ],
    'create_vba' => true // Optionally create a virtual bank account
]);

echo 'Customer ID: ' . $completeCollectionResult['customer_id'];
echo 'Collection: ' . json_encode($completeCollectionResult['collection']);
echo 'Virtual Account: ' . json_encode($completeCollectionResult['virtual_account']);
```

### Using Dependency Injection

```php
<?php

namespace App\Http\Controllers;

use Blaaiz\LaravelSdk\Blaaiz;
use Blaaiz\LaravelSdk\Exceptions\BlaaizException;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private Blaaiz $blaaiz)
    {
    }

    public function createPayout(Request $request)
    {
        try {
            $payout = $this->blaaiz->payouts()->initiate([
                'wallet_id' => $request->input('wallet_id'),
                'method' => 'bank_transfer',
                'from_amount' => $request->input('amount'),
                'from_currency_id' => $request->input('from_currency_id'),
                'to_currency_id' => $request->input('to_currency_id'),
                'account_number' => $request->input('account_number'),
                'bank_id' => $request->input('bank_id')
            ]);

            return response()->json($payout);
        } catch (BlaaizException $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'status' => $e->getStatus(),
                'error_code' => $e->getErrorCode()
            ], $e->getStatus() ?: 500);
        }
    }
}
```

## Error Handling

The SDK uses a custom `BlaaizException` class that provides detailed error information:

```php
use Blaaiz\LaravelSdk\Exceptions\BlaaizException;

try {
    $customer = Blaaiz::customers()->create($invalidData);
} catch (BlaaizException $e) {
    echo 'Blaaiz API Error: ' . $e->getMessage();
    echo 'Status Code: ' . $e->getStatus();
    echo 'Error Code: ' . $e->getErrorCode();
    
    // Check error type
    if ($e->isClientError()) {
        // 4xx errors - client/validation issues
        echo 'Client error detected';
    } elseif ($e->isServerError()) {
        // 5xx errors - server issues
        echo 'Server error detected';
    }
    
    // Get array representation
    $errorArray = $e->toArray();
    Log::error('Blaaiz API Error', $errorArray);
}
```

## Rate Limiting

The Blaaiz API has a rate limit of 100 requests per minute. The SDK automatically includes rate limit headers in responses:

- `X-RateLimit-Limit`: Maximum requests per minute
- `X-RateLimit-Remaining`: Remaining requests in current window
- `X-RateLimit-Reset`: When the rate limit resets

## Webhook Handling

### Webhook Signature Verification

The SDK provides built-in webhook signature verification to ensure webhook authenticity:

```php
use Blaaiz\LaravelSdk\Facades\Blaaiz;

// Method 1: Verify signature manually
$isValid = Blaaiz::webhooks()->verifySignature(
    $payload,        // Raw webhook payload (string or array)
    $signature,      // Signature from webhook headers
    $webhookSecret   // Your webhook secret key
);

if ($isValid) {
    echo 'Webhook signature is valid';
} else {
    echo 'Invalid webhook signature';
}

// Method 2: Construct verified event (recommended)
try {
    $event = Blaaiz::webhooks()->constructEvent(
        $payload,        // Raw webhook payload
        $signature,      // Signature from webhook headers  
        $webhookSecret   // Your webhook secret key
    );
    
    echo 'Verified event: ' . json_encode($event);
    // $event['verified'] will be true
    // $event['timestamp'] will contain verification timestamp
} catch (BlaaizException $e) {
    echo 'Webhook verification failed: ' . $e->getMessage();
}
```

### Complete Laravel Webhook Handler

```php
<?php

namespace App\Http\Controllers;

use Blaaiz\LaravelSdk\Facades\Blaaiz;
use Blaaiz\LaravelSdk\Exceptions\BlaaizException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    private string $webhookSecret;

    public function __construct()
    {
        // Get webhook secret from config or environment
        $this->webhookSecret = config('blaaiz.webhook_secret') ?: env('BLAAIZ_WEBHOOK_SECRET');
    }

    public function collection(Request $request): Response
    {
        $signature = $request->header('x-blaaiz-signature');
        $payload = $request->getContent();

        try {
            // Verify webhook signature and construct event
            $event = Blaaiz::webhooks()->constructEvent($payload, $signature, $this->webhookSecret);

            Log::info('Verified collection event', [
                'transaction_id' => $event['transaction_id'],
                'status' => $event['status'],
                'amount' => $event['amount'],
                'currency' => $event['currency'],
                'verified' => $event['verified']
            ]);

            // Process the collection
            // Update your database, send notifications, etc.

            return response()->json(['received' => true]);
        } catch (BlaaizException $e) {
            Log::error('Webhook verification failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }
    }

    public function payout(Request $request): Response
    {
        $signature = $request->header('x-blaaiz-signature');
        $payload = $request->getContent();

        try {
            // Verify webhook signature and construct event
            $event = Blaaiz::webhooks()->constructEvent($payload, $signature, $this->webhookSecret);

            Log::info('Verified payout event', [
                'transaction_id' => $event['transaction_id'],
                'status' => $event['status'],
                'recipient' => $event['recipient'],
                'verified' => $event['verified']
            ]);

            // Process the payout completion
            // Update your database, send notifications, etc.

            return response()->json(['received' => true]);
        } catch (BlaaizException $e) {
            Log::error('Webhook verification failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }
    }
}
```

### Webhook Routes

Add these routes to your `routes/web.php` or `routes/api.php`:

```php
use App\Http\Controllers\WebhookController;

// Webhook routes (disable CSRF protection for these routes)
Route::post('/webhooks/collection', [WebhookController::class, 'collection'])->name('webhooks.collection');
Route::post('/webhooks/payout', [WebhookController::class, 'payout'])->name('webhooks.payout');
```

### Disable CSRF Protection for Webhooks

Add webhook routes to the CSRF exception list in `app/Http/Middleware/VerifyCsrfToken.php`:

```php
protected $except = [
    'webhooks/*',
];
```

## Environment Configuration

The SDK configuration can be customized via the `.env` file:

```env
# Required
BLAAIZ_API_KEY=your-api-key-here

# Optional - Environment URLs
BLAAIZ_BASE_URL=https://api-dev.blaaiz.com  # Development (default)
# BLAAIZ_BASE_URL=https://api.blaaiz.com    # Production

# Optional - Request timeout
BLAAIZ_TIMEOUT=30

# Optional - Webhook secret for signature verification
BLAAIZ_WEBHOOK_SECRET=your-webhook-secret-here
```

### Configuration File

After publishing the config file with `php artisan vendor:publish --tag=blaaiz-config`, you can modify `config/blaaiz.php`:

```php
<?php

return [
    'api_key' => env('BLAAIZ_API_KEY'),
    'base_url' => env('BLAAIZ_BASE_URL', 'https://api-dev.blaaiz.com'),
    'timeout' => env('BLAAIZ_TIMEOUT', 30),
    'webhook_secret' => env('BLAAIZ_WEBHOOK_SECRET'),
];
```

## Best Practices

1. **Always validate customer data before creating customers**
2. **Use the fees API to calculate and display fees to users**
3. **Always verify webhook signatures using the SDK's built-in methods**
4. **Store customer IDs and transaction IDs for tracking**
5. **Handle rate limiting gracefully with exponential backoff**
6. **Use environment variables for API keys and webhook secrets**
7. **Implement proper error handling and logging**
8. **Test webhook endpoints thoroughly with signature verification**
9. **Disable CSRF protection for webhook endpoints**
10. **Return appropriate HTTP status codes from webhook handlers (200 for success, 400 for invalid signatures)**

## Requirements

- PHP 8.1 or higher
- Laravel 9.0, 10.0, or 11.0
- GuzzleHTTP 7.0 or higher

## Support

For support and additional documentation:
- Email: onboarding@blaaiz.com
- Documentation: https://docs.business.blaaiz.com

## License

This SDK is provided under the MIT License