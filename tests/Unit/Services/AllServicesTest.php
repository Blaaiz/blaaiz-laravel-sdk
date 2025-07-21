<?php

use Blaaiz\LaravelSdk\Services\CollectionService;
use Blaaiz\LaravelSdk\Services\WalletService;
use Blaaiz\LaravelSdk\Services\VirtualBankAccountService;
use Blaaiz\LaravelSdk\Services\TransactionService;
use Blaaiz\LaravelSdk\Services\BankService;
use Blaaiz\LaravelSdk\Services\CurrencyService;
use Blaaiz\LaravelSdk\Services\FeesService;
use Blaaiz\LaravelSdk\Services\FileService;
use Blaaiz\LaravelSdk\Exceptions\BlaaizException;
use Blaaiz\LaravelSdk\BlaaizClient;
use Mockery;

describe('CollectionService', function () {
    beforeEach(function () {
        $this->mockClient = Mockery::mock(BlaaizClient::class);
        $this->service = new CollectionService($this->mockClient);
    });

    afterEach(function () {
        Mockery::close();
    });

    it('validates required fields for initiate', function () {
        expect(fn() => $this->service->initiate([]))
            ->toThrow(BlaaizException::class, 'method is required');

        expect(fn() => $this->service->initiate(['method' => 'bank_transfer']))
            ->toThrow(BlaaizException::class, 'amount is required');

        expect(fn() => $this->service->initiate(['method' => 'bank_transfer', 'amount' => 100]))
            ->toThrow(BlaaizException::class, 'wallet_id is required');
    });

    it('calls makeRequest for initiate', function () {
        $collectionData = [
            'method' => 'bank_transfer',
            'amount' => 100,
            'wallet_id' => 'w1'
        ];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/collection', $collectionData)
            ->andReturn(['data' => ['id' => 'collection-123']]);

        $result = $this->service->initiate($collectionData);
        expect($result)->toBe(['data' => ['id' => 'collection-123']]);
    });

    it('validates customer_id for attachCustomer', function () {
        expect(fn() => $this->service->attachCustomer([]))
            ->toThrow(BlaaizException::class, 'customer_id is required');

        expect(fn() => $this->service->attachCustomer(['customer_id' => 'c1']))
            ->toThrow(BlaaizException::class, 'transaction_id is required');
    });

    it('calls makeRequest for attachCustomer', function () {
        $attachData = ['customer_id' => 'c1', 'transaction_id' => 'txn1'];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/collection/attach-customer', $attachData)
            ->andReturn(['data' => ['success' => true]]);

        $result = $this->service->attachCustomer($attachData);
        expect($result)->toBe(['data' => ['success' => true]]);
    });
});

describe('WalletService', function () {
    beforeEach(function () {
        $this->mockClient = Mockery::mock(BlaaizClient::class);
        $this->service = new WalletService($this->mockClient);
    });

    afterEach(function () {
        Mockery::close();
    });

    it('calls makeRequest for list', function () {
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('GET', '/api/external/wallet')
            ->andReturn(['data' => []]);

        $result = $this->service->list();
        expect($result)->toBe(['data' => []]);
    });

    it('validates wallet ID for get', function () {
        expect(fn() => $this->service->get(''))
            ->toThrow(BlaaizException::class, 'Wallet ID is required');
    });

    it('calls makeRequest for get', function () {
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('GET', '/api/external/wallet/w1')
            ->andReturn(['data' => ['id' => 'w1']]);

        $result = $this->service->get('w1');
        expect($result)->toBe(['data' => ['id' => 'w1']]);
    });
});

describe('VirtualBankAccountService', function () {
    beforeEach(function () {
        $this->mockClient = Mockery::mock(BlaaizClient::class);
        $this->service = new VirtualBankAccountService($this->mockClient);
    });

    afterEach(function () {
        Mockery::close();
    });

    it('validates wallet_id for create', function () {
        expect(fn() => $this->service->create([]))
            ->toThrow(BlaaizException::class, 'wallet_id is required');
    });

    it('calls makeRequest for create', function () {
        $vbaData = ['wallet_id' => 'w1', 'account_name' => 'Test Account'];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/virtual-bank-account', $vbaData)
            ->andReturn(['data' => ['account_number' => '123456789']]);

        $result = $this->service->create($vbaData);
        expect($result)->toBe(['data' => ['account_number' => '123456789']]);
    });

    it('calls makeRequest for list', function () {
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('GET', '/api/external/virtual-bank-account')
            ->andReturn(['data' => []]);

        $result = $this->service->list();
        expect($result)->toBe(['data' => []]);
    });

    it('validates ID for get', function () {
        expect(fn() => $this->service->get(''))
            ->toThrow(BlaaizException::class, 'Virtual bank account ID is required');
    });

    it('calls makeRequest for get', function () {
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('GET', '/api/external/virtual-bank-account/vba1')
            ->andReturn(['data' => ['id' => 'vba1']]);

        $result = $this->service->get('vba1');
        expect($result)->toBe(['data' => ['id' => 'vba1']]);
    });
});

describe('TransactionService', function () {
    beforeEach(function () {
        $this->mockClient = Mockery::mock(BlaaizClient::class);
        $this->service = new TransactionService($this->mockClient);
    });

    afterEach(function () {
        Mockery::close();
    });

    it('calls makeRequest for list', function () {
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/transaction', [])
            ->andReturn(['data' => []]);

        $result = $this->service->list();
        expect($result)->toBe(['data' => []]);
    });

    it('validates transaction ID for get', function () {
        expect(fn() => $this->service->get(''))
            ->toThrow(BlaaizException::class, 'Transaction ID is required');
    });

    it('calls makeRequest for get', function () {
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('GET', '/api/external/transaction/txn1')
            ->andReturn(['data' => ['id' => 'txn1']]);

        $result = $this->service->get('txn1');
        expect($result)->toBe(['data' => ['id' => 'txn1']]);
    });

});

describe('BankService', function () {
    beforeEach(function () {
        $this->mockClient = Mockery::mock(BlaaizClient::class);
        $this->service = new BankService($this->mockClient);
    });

    afterEach(function () {
        Mockery::close();
    });

    it('calls makeRequest for list', function () {
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('GET', '/api/external/bank')
            ->andReturn(['data' => []]);

        $result = $this->service->list();
        expect($result)->toBe(['data' => []]);
    });

    it('validates required fields for lookupAccount', function () {
        expect(fn() => $this->service->lookupAccount([]))
            ->toThrow(BlaaizException::class, 'account_number is required');

        expect(fn() => $this->service->lookupAccount(['account_number' => '123']))
            ->toThrow(BlaaizException::class, 'bank_id is required');
    });

    it('calls makeRequest for lookupAccount', function () {
        $lookupData = ['account_number' => '1234567890', 'bank_id' => 'bank1'];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/bank/account-lookup', $lookupData)
            ->andReturn(['data' => ['account_name' => 'John Doe']]);

        $result = $this->service->lookupAccount($lookupData);
        expect($result)->toBe(['data' => ['account_name' => 'John Doe']]);
    });
});

describe('CurrencyService', function () {
    beforeEach(function () {
        $this->mockClient = Mockery::mock(BlaaizClient::class);
        $this->service = new CurrencyService($this->mockClient);
    });

    afterEach(function () {
        Mockery::close();
    });

    it('calls makeRequest for list', function () {
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('GET', '/api/external/currency')
            ->andReturn(['data' => []]);

        $result = $this->service->list();
        expect($result)->toBe(['data' => []]);
    });

});

describe('FeesService', function () {
    beforeEach(function () {
        $this->mockClient = Mockery::mock(BlaaizClient::class);
        $this->service = new FeesService($this->mockClient);
    });

    afterEach(function () {
        Mockery::close();
    });

    it('validates required fields for getBreakdown', function () {
        expect(fn() => $this->service->getBreakdown([]))
            ->toThrow(BlaaizException::class, 'from_currency_id is required');

        expect(fn() => $this->service->getBreakdown(['from_currency_id' => 'USD']))
            ->toThrow(BlaaizException::class, 'to_currency_id is required');

        expect(fn() => $this->service->getBreakdown([
            'from_currency_id' => 'USD',
            'to_currency_id' => 'NGN'
        ]))->toThrow(BlaaizException::class, 'from_amount is required');
    });

    it('calls makeRequest for getBreakdown', function () {
        $feeData = [
            'from_currency_id' => 'USD',
            'to_currency_id' => 'NGN',
            'from_amount' => 100
        ];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/fees/breakdown', $feeData)
            ->andReturn(['data' => ['total_fees' => 5.50]]);

        $result = $this->service->getBreakdown($feeData);
        expect($result)->toBe(['data' => ['total_fees' => 5.50]]);
    });
});

describe('FileService', function () {
    beforeEach(function () {
        $this->mockClient = Mockery::mock(BlaaizClient::class);
        $this->service = new FileService($this->mockClient);
    });

    afterEach(function () {
        Mockery::close();
    });

    it('validates required fields for getPresignedUrl', function () {
        expect(fn() => $this->service->getPresignedUrl([]))
            ->toThrow(BlaaizException::class, 'customer_id is required');

        expect(fn() => $this->service->getPresignedUrl(['customer_id' => 'c1']))
            ->toThrow(BlaaizException::class, 'file_category is required');
    });

    it('calls makeRequest for getPresignedUrl', function () {
        $fileData = ['customer_id' => 'c1', 'file_category' => 'identity'];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/file/get-presigned-url', $fileData)
            ->andReturn(['data' => ['url' => 'https://s3.amazonaws.com/bucket/file']]);

        $result = $this->service->getPresignedUrl($fileData);
        expect($result)->toBe(['data' => ['url' => 'https://s3.amazonaws.com/bucket/file']]);
    });

});