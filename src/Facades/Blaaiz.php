<?php

namespace Blaaiz\LaravelSdk\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Blaaiz\LaravelSdk\Services\CustomerService customers()
 * @method static \Blaaiz\LaravelSdk\Services\CollectionService collections()
 * @method static \Blaaiz\LaravelSdk\Services\PayoutService payouts()
 * @method static \Blaaiz\LaravelSdk\Services\WalletService wallets()
 * @method static \Blaaiz\LaravelSdk\Services\VirtualBankAccountService virtualBankAccounts()
 * @method static \Blaaiz\LaravelSdk\Services\TransactionService transactions()
 * @method static \Blaaiz\LaravelSdk\Services\BankService banks()
 * @method static \Blaaiz\LaravelSdk\Services\CurrencyService currencies()
 * @method static \Blaaiz\LaravelSdk\Services\FeesService fees()
 * @method static \Blaaiz\LaravelSdk\Services\FileService files()
 * @method static \Blaaiz\LaravelSdk\Services\WebhookService webhooks()
 * @method static bool testConnection()
 * @method static array createCompletePayout(array $payoutConfig)
 * @method static array createCompleteCollection(array $collectionConfig)
 */
class Blaaiz extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'blaaiz';
    }
}