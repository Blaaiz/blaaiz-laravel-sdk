<?php

use Blaaiz\LaravelSdk\BlaaizClient;
use Blaaiz\LaravelSdk\Exceptions\BlaaizException;
use Blaaiz\LaravelSdk\Services\BankService;
use Blaaiz\LaravelSdk\Services\CollectionService;
use Blaaiz\LaravelSdk\Services\CurrencyService;
use Blaaiz\LaravelSdk\Services\FeesService;
use Blaaiz\LaravelSdk\Services\FileService;
use Blaaiz\LaravelSdk\Services\TransactionService;
use Blaaiz\LaravelSdk\Services\VirtualBankAccountService;
use Blaaiz\LaravelSdk\Services\WalletService;

describe('CollectionService', function () {
    beforeEach(function () {
        $this->mockClient = Mockery::mock(BlaaizClient::class);
        $this->service = new CollectionService($this->mockClient);
    });

    afterEach(function () {
        Mockery::close();
    });

    it('validates required fields for initiate', function () {
        expect(fn () => $this->service->initiate([]))
            ->toThrow(BlaaizException::class, 'method is required');

        expect(fn () => $this->service->initiate(['method' => 'open_banking']))
            ->toThrow(BlaaizException::class, 'amount is required');

        expect(fn () => $this->service->initiate(['method' => 'open_banking', 'amount' => 100]))
            ->toThrow(BlaaizException::class, 'wallet_id is required');
    });

    it('validates card fields for initiate with card method', function () {
        expect(fn () => $this->service->initiate([
            'method' => 'card',
            'amount' => 100,
            'wallet_id' => 'w1',
        ]))->toThrow(BlaaizException::class, 'customer_id is required for card method');

        expect(fn () => $this->service->initiate([
            'method' => 'card',
            'amount' => 100,
            'wallet_id' => 'w1',
            'customer_id' => 'c1',
            'card_holder_name' => 'John Doe',
            'card_number' => '4111111111111111',
            'expiry' => '12/30',
        ]))->toThrow(BlaaizException::class, 'cvc is required for card method');
    });

    it('calls makeRequest for initiate without requiring customer_id or currency', function () {
        $collectionData = [
            'method' => 'open_banking',
            'amount' => 100,
            'wallet_id' => 'w1',
        ];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/collection', $collectionData)
            ->andReturn(['data' => ['id' => 'collection-123']]);

        $result = $this->service->initiate($collectionData);
        expect($result)->toBe(['data' => ['id' => 'collection-123']]);
    });

    it('forwards merchant_reference for initiate', function () {
        $collectionData = [
            'method' => 'open_banking',
            'amount' => 100,
            'wallet_id' => 'w1',
            'merchant_reference' => 'order-123',
        ];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/collection', $collectionData)
            ->andReturn(['data' => ['id' => 'collection-123', 'merchant_reference' => 'order-123']]);

        $result = $this->service->initiate($collectionData);
        expect($result)->toBe(['data' => ['id' => 'collection-123', 'merchant_reference' => 'order-123']]);
    });

    it('validates required fields for initiateCrypto', function () {
        expect(fn () => $this->service->initiateCrypto(['amount' => 100, 'wallet_id' => 'w1']))
            ->toThrow(BlaaizException::class, 'network is required');
    });

    it('calls makeRequest for initiateCrypto', function () {
        $cryptoData = [
            'amount' => 100,
            'network' => 'ethereum',
            'token' => 'USDT',
            'wallet_id' => 'w1',
        ];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/collection/crypto', $cryptoData)
            ->andReturn(['data' => ['address' => '0x123']]);

        $result = $this->service->initiateCrypto($cryptoData);
        expect($result)->toBe(['data' => ['address' => '0x123']]);
    });

    it('validates required fields for initiateInteracMoneyRequest', function () {
        expect(fn () => $this->service->initiateInteracMoneyRequest([]))
            ->toThrow(BlaaizException::class, 'amount is required');

        expect(fn () => $this->service->initiateInteracMoneyRequest(['amount' => 100]))
            ->toThrow(BlaaizException::class, 'email is required');
    });

    it('calls makeRequest for initiateInteracMoneyRequest', function () {
        $interacData = ['amount' => 100, 'email' => 'sender@example.com'];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/collection/interac-money-request', $interacData)
            ->andReturn(['data' => ['transaction_id' => 'txn1', 'reference' => 'ref1']]);

        $result = $this->service->initiateInteracMoneyRequest($interacData);
        expect($result)->toBe(['data' => ['transaction_id' => 'txn1', 'reference' => 'ref1']]);
    });

    it('validates customer_id for attachCustomer', function () {
        expect(fn () => $this->service->attachCustomer([]))
            ->toThrow(BlaaizException::class, 'customer_id is required');

        expect(fn () => $this->service->attachCustomer(['customer_id' => 'c1']))
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

    it('calls makeRequest for getCryptoNetworks', function () {
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('GET', '/api/external/collection/crypto/networks')
            ->andReturn(['data' => ['ethereum', 'tron']]);

        $result = $this->service->getCryptoNetworks();
        expect($result)->toBe(['data' => ['ethereum', 'tron']]);
    });

    it('calls makeRequest for getCryptoNetworks with filters', function () {
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('GET', '/api/external/collection/crypto/networks?transaction_type=payout')
            ->andReturn(['data' => ['ethereum']]);

        $result = $this->service->getCryptoNetworks(['transaction_type' => 'payout']);
        expect($result)->toBe(['data' => ['ethereum']]);
    });

    it('validates required fields for acceptInteracMoneyRequest', function () {
        expect(fn () => $this->service->acceptInteracMoneyRequest([]))
            ->toThrow(BlaaizException::class, 'reference_number is required');
    });

    it('calls makeRequest for acceptInteracMoneyRequest', function () {
        $interacData = ['reference_number' => 'ref123', 'security_answer' => 'answer'];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/collection/accept-interac-money-request', $interacData)
            ->andReturn(['data' => ['message' => 'Interac money request accepted successfully']]);

        $result = $this->service->acceptInteracMoneyRequest($interacData);
        expect($result)->toBe(['data' => ['message' => 'Interac money request accepted successfully']]);
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
        expect(fn () => $this->service->get(''))
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
        expect(fn () => $this->service->create([]))
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

    it('calls makeRequest for list with no filters', function () {
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('GET', '/api/external/virtual-bank-account')
            ->andReturn(['data' => []]);

        $result = $this->service->list();
        expect($result)->toBe(['data' => []]);
    });

    it('calls makeRequest for list with wallet_id', function () {
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('GET', '/api/external/virtual-bank-account?wallet_id=w1')
            ->andReturn(['data' => []]);

        $result = $this->service->list('w1');
        expect($result)->toBe(['data' => []]);
    });

    it('calls makeRequest for list with customer_id', function () {
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('GET', '/api/external/virtual-bank-account?customer_id=c1')
            ->andReturn(['data' => []]);

        $result = $this->service->list(null, 'c1');
        expect($result)->toBe(['data' => []]);
    });

    it('calls makeRequest for list with wallet_id and customer_id', function () {
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('GET', '/api/external/virtual-bank-account?wallet_id=w1&customer_id=c1')
            ->andReturn(['data' => []]);

        $result = $this->service->list('w1', 'c1');
        expect($result)->toBe(['data' => []]);
    });

    it('validates ID for get', function () {
        expect(fn () => $this->service->get(''))
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

    it('validates ID for close', function () {
        expect(fn () => $this->service->close(''))
            ->toThrow(BlaaizException::class, 'Virtual bank account ID is required');
    });

    it('calls makeRequest for close without reason', function () {
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/virtual-bank-account/vba1/close', [])
            ->andReturn(['data' => ['status' => 'closed']]);

        $result = $this->service->close('vba1');
        expect($result)->toBe(['data' => ['status' => 'closed']]);
    });

    it('calls makeRequest for close with reason', function () {
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/virtual-bank-account/vba1/close', ['reason' => 'No longer needed'])
            ->andReturn(['data' => ['status' => 'closed']]);

        $result = $this->service->close('vba1', 'No longer needed');
        expect($result)->toBe(['data' => ['status' => 'closed']]);
    });

    it('validates required parameters for getIdentificationType', function () {
        expect(fn () => $this->service->getIdentificationType())
            ->toThrow(BlaaizException::class, 'Either customer_id or both country and type are required');

        expect(fn () => $this->service->getIdentificationType(null, 'NG'))
            ->toThrow(BlaaizException::class, 'Either customer_id or both country and type are required');

        expect(fn () => $this->service->getIdentificationType(null, null, 'individual'))
            ->toThrow(BlaaizException::class, 'Either customer_id or both country and type are required');
    });

    it('calls makeRequest for getIdentificationType with customer_id', function () {
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('GET', '/api/external/virtual-bank-account/identification-type?customer_id=c1')
            ->andReturn(['data' => ['label' => 'Bank Verification Number', 'type' => 'bvn']]);

        $result = $this->service->getIdentificationType('c1');
        expect($result)->toBe(['data' => ['label' => 'Bank Verification Number', 'type' => 'bvn']]);
    });

    it('calls makeRequest for getIdentificationType with country and type', function () {
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('GET', '/api/external/virtual-bank-account/identification-type?country=NG&type=individual')
            ->andReturn(['data' => ['label' => 'Bank Verification Number', 'type' => 'bvn']]);

        $result = $this->service->getIdentificationType(null, 'NG', 'individual');
        expect($result)->toBe(['data' => ['label' => 'Bank Verification Number', 'type' => 'bvn']]);
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

    it('forwards merchant_reference as a list filter', function () {
        $filters = ['merchant_reference' => 'order-123'];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/transaction', $filters)
            ->andReturn(['data' => [['id' => 'txn1', 'merchant_reference' => 'order-123']]]);

        $result = $this->service->list($filters);
        expect($result)->toBe(['data' => [['id' => 'txn1', 'merchant_reference' => 'order-123']]]);
    });

    it('validates transaction ID for get', function () {
        expect(fn () => $this->service->get(''))
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
        expect(fn () => $this->service->lookupAccount([]))
            ->toThrow(BlaaizException::class, 'account_number is required');

        expect(fn () => $this->service->lookupAccount(['account_number' => '123']))
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

    it('calls makeRequest for list with filters', function () {
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('GET', '/api/external/bank?currency=NGN&country=NG')
            ->andReturn(['data' => []]);

        $result = $this->service->list(['currency' => 'NGN', 'country' => 'NG']);
        expect($result)->toBe(['data' => []]);
    });

    it('validates required fields for verifyPayee', function () {
        expect(fn () => $this->service->verifyPayee([]))
            ->toThrow(BlaaizException::class, 'sort_code is required');

        expect(fn () => $this->service->verifyPayee(['sort_code' => '12-34-56']))
            ->toThrow(BlaaizException::class, 'account_number is required');
    });

    it('calls makeRequest for verifyPayee', function () {
        $payeeData = [
            'sort_code' => '12-34-56',
            'account_number' => '12345678',
            'account_name' => 'John Doe',
        ];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/bank/payee-verification', $payeeData)
            ->andReturn(['data' => ['matched' => true]]);

        $result = $this->service->verifyPayee($payeeData);
        expect($result)->toBe(['data' => ['matched' => true]]);
    });

    it('validates required fields for verifyIban', function () {
        expect(fn () => $this->service->verifyIban([]))
            ->toThrow(BlaaizException::class, 'iban is required');
    });

    it('calls makeRequest for verifyIban', function () {
        $ibanData = ['iban' => 'DE89370400440532013000'];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/bank/iban-verification', $ibanData)
            ->andReturn(['data' => ['sepa_reachable' => true]]);

        $result = $this->service->verifyIban($ibanData);
        expect($result)->toBe(['data' => ['sepa_reachable' => true]]);
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
        expect(fn () => $this->service->getBreakdown([]))
            ->toThrow(BlaaizException::class, 'from_currency_id is required');

        expect(fn () => $this->service->getBreakdown(['from_currency_id' => 'USD']))
            ->toThrow(BlaaizException::class, 'to_currency_id is required');

        expect(fn () => $this->service->getBreakdown([
            'from_currency_id' => 'USD',
            'to_currency_id' => 'NGN',
        ]))->toThrow(BlaaizException::class, 'Either from_amount or to_amount is required');
    });

    it('calls makeRequest for getBreakdown with from_amount', function () {
        $feeData = [
            'from_currency_id' => 'USD',
            'to_currency_id' => 'NGN',
            'from_amount' => 100,
        ];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/fees/breakdown', $feeData)
            ->andReturn(['data' => ['total_fees' => 5.50]]);

        $result = $this->service->getBreakdown($feeData);
        expect($result)->toBe(['data' => ['total_fees' => 5.50]]);
    });

    it('calls makeRequest for getBreakdown with to_amount', function () {
        $feeData = [
            'from_currency_id' => 'USD',
            'to_currency_id' => 'NGN',
            'to_amount' => 50000,
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
        expect(fn () => $this->service->getPresignedUrl([]))
            ->toThrow(BlaaizException::class, 'customer_id is required');

        expect(fn () => $this->service->getPresignedUrl(['customer_id' => 'c1']))
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
