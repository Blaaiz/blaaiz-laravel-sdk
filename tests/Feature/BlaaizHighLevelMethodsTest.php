<?php

use Blaaiz\LaravelSdk\Blaaiz;
use Blaaiz\LaravelSdk\Exceptions\BlaaizException;
use Blaaiz\LaravelSdk\Services\CustomerService;
use Blaaiz\LaravelSdk\Services\CurrencyService;
use Blaaiz\LaravelSdk\Services\FeesService;
use Blaaiz\LaravelSdk\Services\PayoutService;
use Blaaiz\LaravelSdk\Services\CollectionService;
use Blaaiz\LaravelSdk\Services\VirtualBankAccountService;
use Mockery;

describe('Blaaiz high level methods', function () {
    afterEach(function () {
        Mockery::close();
    });

    it('testConnection returns true on success', function () {
        $mockClient = Mockery::mock(\Blaaiz\LaravelSdk\BlaaizClient::class);
        $sdk = new Blaaiz('key');
        
        // Replace the currencies service with a mock
        $mockCurrencyService = Mockery::mock(CurrencyService::class);
        $mockCurrencyService->shouldReceive('list')->once()->andReturn([]);
        
        $reflection = new ReflectionClass($sdk);
        $property = $reflection->getProperty('currencies');
        $property->setAccessible(true);
        $property->setValue($sdk, $mockCurrencyService);

        $result = $sdk->testConnection();

        expect($result)->toBe(true);
    });

    it('testConnection returns false on error', function () {
        $sdk = new Blaaiz('key');
        
        // Replace the currencies service with a mock that throws exception
        $mockCurrencyService = Mockery::mock(CurrencyService::class);
        $mockCurrencyService->shouldReceive('list')->once()->andThrow(new BlaaizException('Connection failed'));
        
        $reflection = new ReflectionClass($sdk);
        $property = $reflection->getProperty('currencies');
        $property->setAccessible(true);
        $property->setValue($sdk, $mockCurrencyService);

        $result = $sdk->testConnection();

        expect($result)->toBe(false);
    });

    it('createCompletePayout full flow', function () {
        $sdk = new Blaaiz('key');
        
        // Mock all required services
        $mockCustomerService = Mockery::mock(CustomerService::class);
        $mockFeesService = Mockery::mock(FeesService::class);
        $mockPayoutService = Mockery::mock(PayoutService::class);

        $mockCustomerService->shouldReceive('create')->once()
            ->with([
                'first_name' => 'a',
                'last_name' => 'b',
                'type' => 'individual',
                'email' => 'e@e.com',
                'country' => 'NG',
                'id_type' => 'passport',
                'id_number' => '1'
            ])
            ->andReturn(['data' => ['data' => ['id' => 'c1']]]);

        $mockFeesService->shouldReceive('getBreakdown')->once()
            ->with([
                'from_currency_id' => 'USD',
                'to_currency_id' => 'NGN',
                'from_amount' => 1
            ])
            ->andReturn(['data' => 'fee']);

        $mockPayoutService->shouldReceive('initiate')->once()
            ->with([
                'wallet_id' => 'w',
                'method' => 'bank_transfer',
                'from_amount' => 1,
                'from_currency_id' => 'USD',
                'to_currency_id' => 'NGN',
                'account_number' => '123',
                'customer_id' => 'c1'
            ])
            ->andReturn(['data' => 'payout']);

        // Inject mocked services
        $reflection = new ReflectionClass($sdk);
        
        $customerProperty = $reflection->getProperty('customers');
        $customerProperty->setAccessible(true);
        $customerProperty->setValue($sdk, $mockCustomerService);
        
        $feesProperty = $reflection->getProperty('fees');
        $feesProperty->setAccessible(true);
        $feesProperty->setValue($sdk, $mockFeesService);
        
        $payoutProperty = $reflection->getProperty('payouts');
        $payoutProperty->setAccessible(true);
        $payoutProperty->setValue($sdk, $mockPayoutService);

        $payoutConfig = [
            'customer_data' => [
                'first_name' => 'a',
                'last_name' => 'b',
                'type' => 'individual',
                'email' => 'e@e.com',
                'country' => 'NG',
                'id_type' => 'passport',
                'id_number' => '1'
            ],
            'payout_data' => [
                'wallet_id' => 'w',
                'method' => 'bank_transfer',
                'from_amount' => 1,
                'from_currency_id' => 'USD',
                'to_currency_id' => 'NGN',
                'account_number' => '123'
            ]
        ];

        $result = $sdk->createCompletePayout($payoutConfig);

        expect($result)->toBe([
            'customer_id' => 'c1',
            'payout' => 'payout',
            'fees' => 'fee'
        ]);
    });

    it('createCompletePayout with existing customer', function () {
        $sdk = new Blaaiz('key');
        
        // Mock required services (no customer creation needed)
        $mockFeesService = Mockery::mock(FeesService::class);
        $mockPayoutService = Mockery::mock(PayoutService::class);

        $mockFeesService->shouldReceive('getBreakdown')->once()
            ->andReturn(['data' => 'fee']);

        $mockPayoutService->shouldReceive('initiate')->once()
            ->with([
                'wallet_id' => 'w',
                'method' => 'bank_transfer',
                'from_amount' => 1,
                'from_currency_id' => 'USD',
                'to_currency_id' => 'NGN',
                'account_number' => '123',
                'customer_id' => 'existing-customer'
            ])
            ->andReturn(['data' => 'payout']);

        // Inject mocked services
        $reflection = new ReflectionClass($sdk);
        
        $feesProperty = $reflection->getProperty('fees');
        $feesProperty->setAccessible(true);
        $feesProperty->setValue($sdk, $mockFeesService);
        
        $payoutProperty = $reflection->getProperty('payouts');
        $payoutProperty->setAccessible(true);
        $payoutProperty->setValue($sdk, $mockPayoutService);

        $payoutConfig = [
            'payout_data' => [
                'wallet_id' => 'w',
                'method' => 'bank_transfer',
                'from_amount' => 1,
                'from_currency_id' => 'USD',
                'to_currency_id' => 'NGN',
                'account_number' => '123',
                'customer_id' => 'existing-customer'
            ]
        ];

        $result = $sdk->createCompletePayout($payoutConfig);

        expect($result['customer_id'])->toBe('existing-customer');
        expect($result['payout'])->toBe('payout');
        expect($result['fees'])->toBe('fee');
    });

    it('createCompletePayout propagates errors', function () {
        $sdk = new Blaaiz('key');
        
        $mockFeesService = Mockery::mock(FeesService::class);
        $mockPayoutService = Mockery::mock(PayoutService::class);

        $mockFeesService->shouldReceive('getBreakdown')->once()
            ->andReturn(['data' => 'fee']);

        $mockPayoutService->shouldReceive('initiate')->once()
            ->andThrow(new BlaaizException('Payout failed', 400, 'PAYOUT_ERROR'));

        // Inject mocked services
        $reflection = new ReflectionClass($sdk);
        
        $feesProperty = $reflection->getProperty('fees');
        $feesProperty->setAccessible(true);
        $feesProperty->setValue($sdk, $mockFeesService);
        
        $payoutProperty = $reflection->getProperty('payouts');
        $payoutProperty->setAccessible(true);
        $payoutProperty->setValue($sdk, $mockPayoutService);

        $payoutConfig = [
            'payout_data' => [
                'wallet_id' => 'w',
                'method' => 'bank_transfer',
                'from_amount' => 1,
                'from_currency_id' => 'USD',
                'to_currency_id' => 'NGN',
                'account_number' => '123',
                'customer_id' => 'c1'
            ]
        ];

        expect(fn() => $sdk->createCompletePayout($payoutConfig))
            ->toThrow(BlaaizException::class, 'Complete payout failed: Payout failed');
    });

    it('createCompleteCollection with VBA', function () {
        $sdk = new Blaaiz('key');
        
        // Mock all required services
        $mockCustomerService = Mockery::mock(CustomerService::class);
        $mockVBAService = Mockery::mock(VirtualBankAccountService::class);
        $mockCollectionService = Mockery::mock(CollectionService::class);

        $mockCustomerService->shouldReceive('create')->once()
            ->with([
                'first_name' => 'a',
                'last_name' => 'b',
                'type' => 'individual',
                'email' => 'e@e.com',
                'country' => 'NG',
                'id_type' => 'passport',
                'id_number' => '1'
            ])
            ->andReturn(['data' => ['data' => ['id' => 'c2']]]);

        $mockVBAService->shouldReceive('create')->once()
            ->with([
                'wallet_id' => 'w',
                'account_name' => 'a b'
            ])
            ->andReturn(['data' => 'vba']);

        $mockCollectionService->shouldReceive('initiate')->once()
            ->with([
                'method' => 'bank_transfer',
                'amount' => 1,
                'wallet_id' => 'w',
                'customer_id' => 'c2'
            ])
            ->andReturn(['data' => 'collection']);

        // Inject mocked services
        $reflection = new ReflectionClass($sdk);
        
        $customerProperty = $reflection->getProperty('customers');
        $customerProperty->setAccessible(true);
        $customerProperty->setValue($sdk, $mockCustomerService);
        
        $vbaProperty = $reflection->getProperty('virtualBankAccounts');
        $vbaProperty->setAccessible(true);
        $vbaProperty->setValue($sdk, $mockVBAService);
        
        $collectionProperty = $reflection->getProperty('collections');
        $collectionProperty->setAccessible(true);
        $collectionProperty->setValue($sdk, $mockCollectionService);

        $config = [
            'customer_data' => [
                'first_name' => 'a',
                'last_name' => 'b',
                'type' => 'individual',
                'email' => 'e@e.com',
                'country' => 'NG',
                'id_type' => 'passport',
                'id_number' => '1'
            ],
            'collection_data' => [
                'method' => 'bank_transfer',
                'amount' => 1,
                'wallet_id' => 'w'
            ],
            'create_vba' => true
        ];

        $result = $sdk->createCompleteCollection($config);

        expect($result)->toBe([
            'customer_id' => 'c2',
            'collection' => 'collection',
            'virtual_account' => 'vba'
        ]);
    });

    it('createCompleteCollection without VBA', function () {
        $sdk = new Blaaiz('key');
        
        $mockCustomerService = Mockery::mock(CustomerService::class);
        $mockCollectionService = Mockery::mock(CollectionService::class);

        $mockCustomerService->shouldReceive('create')->once()
            ->andReturn(['data' => ['data' => ['id' => 'c3']]]);

        $mockCollectionService->shouldReceive('initiate')->once()
            ->andReturn(['data' => 'collection']);

        // Inject mocked services
        $reflection = new ReflectionClass($sdk);
        
        $customerProperty = $reflection->getProperty('customers');
        $customerProperty->setAccessible(true);
        $customerProperty->setValue($sdk, $mockCustomerService);
        
        $collectionProperty = $reflection->getProperty('collections');
        $collectionProperty->setAccessible(true);
        $collectionProperty->setValue($sdk, $mockCollectionService);

        $config = [
            'customer_data' => [
                'first_name' => 'a',
                'last_name' => 'b',
                'type' => 'individual',
                'email' => 'e@e.com',
                'country' => 'NG',
                'id_type' => 'passport',
                'id_number' => '1'
            ],
            'collection_data' => [
                'method' => 'bank_transfer',
                'amount' => 1,
                'wallet_id' => 'w'
            ],
            'create_vba' => false
        ];

        $result = $sdk->createCompleteCollection($config);

        expect($result['customer_id'])->toBe('c3');
        expect($result['collection'])->toBe('collection');
        expect($result['virtual_account'])->toBeNull();
    });

    it('createCompleteCollection propagates errors', function () {
        $sdk = new Blaaiz('key');
        
        $mockCustomerService = Mockery::mock(CustomerService::class);
        $mockCollectionService = Mockery::mock(CollectionService::class);

        $mockCustomerService->shouldReceive('create')->once()
            ->andReturn(['data' => ['data' => ['id' => 'c4']]]);

        $mockCollectionService->shouldReceive('initiate')->once()
            ->andThrow(new BlaaizException('Collection failed', 500, 'COLLECTION_ERROR'));

        // Inject mocked services
        $reflection = new ReflectionClass($sdk);
        
        $customerProperty = $reflection->getProperty('customers');
        $customerProperty->setAccessible(true);
        $customerProperty->setValue($sdk, $mockCustomerService);
        
        $collectionProperty = $reflection->getProperty('collections');
        $collectionProperty->setAccessible(true);
        $collectionProperty->setValue($sdk, $mockCollectionService);

        $config = [
            'customer_data' => [
                'first_name' => 'a',
                'last_name' => 'b',
                'type' => 'individual',
                'email' => 'e@e.com',
                'country' => 'NG',
                'id_type' => 'passport',
                'id_number' => '1'
            ],
            'collection_data' => [
                'method' => 'bank_transfer',
                'amount' => 1,
                'wallet_id' => 'w'
            ]
        ];

        expect(fn() => $sdk->createCompleteCollection($config))
            ->toThrow(BlaaizException::class, 'Complete collection failed: Collection failed');
    });
});