<?php

namespace Blaaiz\LaravelSdk;

use Blaaiz\LaravelSdk\Exceptions\BlaaizException;
use Blaaiz\LaravelSdk\Services\BankService;
use Blaaiz\LaravelSdk\Services\CollectionService;
use Blaaiz\LaravelSdk\Services\CurrencyService;
use Blaaiz\LaravelSdk\Services\CustomerService;
use Blaaiz\LaravelSdk\Services\FeesService;
use Blaaiz\LaravelSdk\Services\FileService;
use Blaaiz\LaravelSdk\Services\PayoutService;
use Blaaiz\LaravelSdk\Services\TransactionService;
use Blaaiz\LaravelSdk\Services\VirtualBankAccountService;
use Blaaiz\LaravelSdk\Services\WalletService;
use Blaaiz\LaravelSdk\Services\WebhookService;

class Blaaiz
{
    protected BlaaizClient $client;

    public CustomerService $customers;
    public CollectionService $collections;
    public PayoutService $payouts;
    public WalletService $wallets;
    public VirtualBankAccountService $virtualBankAccounts;
    public TransactionService $transactions;
    public BankService $banks;
    public CurrencyService $currencies;
    public FeesService $fees;
    public FileService $files;
    public WebhookService $webhooks;

    public function __construct(string $apiKey, array $options = [])
    {
        $this->client = new BlaaizClient($apiKey, $options);

        $this->customers = new CustomerService($this->client);
        $this->collections = new CollectionService($this->client);
        $this->payouts = new PayoutService($this->client);
        $this->wallets = new WalletService($this->client);
        $this->virtualBankAccounts = new VirtualBankAccountService($this->client);
        $this->transactions = new TransactionService($this->client);
        $this->banks = new BankService($this->client);
        $this->currencies = new CurrencyService($this->client);
        $this->fees = new FeesService($this->client);
        $this->files = new FileService($this->client);
        $this->webhooks = new WebhookService($this->client);
    }

    public function testConnection(): bool
    {
        try {
            $this->currencies->list();
            return true;
        } catch (BlaaizException $e) {
            return false;
        }
    }

    public function createCompletePayout(array $payoutConfig): array
    {
        $customerData = $payoutConfig['customer_data'] ?? null;
        $payoutData = $payoutConfig['payout_data'] ?? [];

        try {
            $customerId = $payoutData['customer_id'] ?? null;

            if (!$customerId && $customerData) {
                $customerResult = $this->customers->create($customerData);
                $customerId = $customerResult['data']['data']['id'];
            }

            $feeBreakdown = $this->fees->getBreakdown([
                'from_currency_id' => $payoutData['from_currency_id'],
                'to_currency_id' => $payoutData['to_currency_id'],
                'from_amount' => $payoutData['from_amount'],
            ]);

            $payoutResult = $this->payouts->initiate(array_merge($payoutData, [
                'customer_id' => $customerId,
            ]));

            return [
                'customer_id' => $customerId,
                'payout' => $payoutResult['data'],
                'fees' => $feeBreakdown['data'],
            ];

        } catch (BlaaizException $e) {
            throw new BlaaizException(
                "Complete payout failed: {$e->getMessage()}",
                $e->getStatus(),
                $e->getErrorCode()
            );
        }
    }

    public function createCompleteCollection(array $collectionConfig): array
    {
        $customerData = $collectionConfig['customer_data'] ?? null;
        $collectionData = $collectionConfig['collection_data'] ?? [];
        $createVBA = $collectionConfig['create_vba'] ?? false;

        try {
            $customerId = $collectionData['customer_id'] ?? null;

            if (!$customerId && $customerData) {
                $customerResult = $this->customers->create($customerData);
                $customerId = $customerResult['data']['data']['id'];
            }

            $vbaData = null;
            if ($createVBA) {
                $vbaResult = $this->virtualBankAccounts->create([
                    'wallet_id' => $collectionData['wallet_id'],
                    'account_name' => $customerData 
                        ? "{$customerData['first_name']} {$customerData['last_name']}" 
                        : 'Customer Account',
                ]);
                $vbaData = $vbaResult['data'];
            }

            $collectionResult = $this->collections->initiate(array_merge($collectionData, [
                'customer_id' => $customerId,
            ]));

            return [
                'customer_id' => $customerId,
                'collection' => $collectionResult['data'],
                'virtual_account' => $vbaData,
            ];

        } catch (BlaaizException $e) {
            throw new BlaaizException(
                "Complete collection failed: {$e->getMessage()}",
                $e->getStatus(),
                $e->getErrorCode()
            );
        }
    }

    public function customers(): CustomerService
    {
        return $this->customers;
    }

    public function collections(): CollectionService
    {
        return $this->collections;
    }

    public function payouts(): PayoutService
    {
        return $this->payouts;
    }

    public function wallets(): WalletService
    {
        return $this->wallets;
    }

    public function virtualBankAccounts(): VirtualBankAccountService
    {
        return $this->virtualBankAccounts;
    }

    public function transactions(): TransactionService
    {
        return $this->transactions;
    }

    public function banks(): BankService
    {
        return $this->banks;
    }

    public function currencies(): CurrencyService
    {
        return $this->currencies;
    }

    public function fees(): FeesService
    {
        return $this->fees;
    }

    public function files(): FileService
    {
        return $this->files;
    }

    public function webhooks(): WebhookService
    {
        return $this->webhooks;
    }
}