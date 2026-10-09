<?php

use Blaaiz\LaravelSdk\BlaaizClient;
use Blaaiz\LaravelSdk\Exceptions\BlaaizException;
use Blaaiz\LaravelSdk\Services\SignaIdService;

describe('SignaIdService', function () {
    beforeEach(function () {
        $this->mockClient = Mockery::mock(BlaaizClient::class);
        $this->service = new SignaIdService($this->mockClient);
        $this->releaseRequest = [
            'idempotency_key' => 'release-123',
            'purpose' => 'Open your trading account',
            'scopes' => ['identity', 'id_document', 'document_images'],
            'origin' => 'https://yourapp.com',
            'reference' => 'user_10482',
        ];
    });

    afterEach(function () {
        Mockery::close();
    });

    it('creates a release request', function () {
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/signa-id/release-requests', $this->releaseRequest)
            ->andReturn(['data' => ['id' => 'release-1']]);

        expect($this->service->createReleaseRequest($this->releaseRequest))
            ->toBe(['data' => ['id' => 'release-1']]);
    });

    it('exchanges a release code', function () {
        $code = str_repeat('a', 43);

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/signa-id/releases/exchange', ['code' => $code])
            ->andReturn(['data' => ['release' => []]]);

        expect($this->service->exchangeReleaseCode($code))->toBe(['data' => ['release' => []]]);
    });

    it('gets a release and a release document, encoding both ids', function () {
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('GET', '/api/external/signa-id/releases/release%2F1')
            ->andReturn(['data' => ['release' => []]]);
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('GET', '/api/external/signa-id/releases/release%2F1/documents/doc%2F1')
            ->andReturn(['data' => ['url' => 'https://files.example/doc']]);

        expect($this->service->getRelease('release/1'))->toBe(['data' => ['release' => []]]);
        expect($this->service->getReleaseDocument('release/1', 'doc/1'))
            ->toBe(['data' => ['url' => 'https://files.example/doc']]);
    });

    it('gets a wallet status with and without chain_id', function () {
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('GET', '/api/v1/signa-id/public/wallets/0xabc/status')
            ->andReturn(['verified' => true]);
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('GET', '/api/v1/signa-id/public/wallets/0xabc/status?chain_id=8453')
            ->andReturn(['verified' => true]);

        $this->service->getWalletStatus('0xabc');
        $this->service->getWalletStatus('0xabc', 8453);
    });

    it('validates the release request and makes no HTTP call', function () {
        $this->mockClient->shouldNotReceive('makeRequest');

        expect(fn () => $this->service->createReleaseRequest([]))
            ->toThrow(BlaaizException::class, 'idempotency_key is required');
        expect(fn () => $this->service->createReleaseRequest(array_merge($this->releaseRequest, ['purpose' => ''])))
            ->toThrow(BlaaizException::class, 'purpose is required');
        expect(fn () => $this->service->createReleaseRequest(array_merge($this->releaseRequest, ['origin' => ''])))
            ->toThrow(BlaaizException::class, 'origin is required');
        expect(fn () => $this->service->createReleaseRequest(array_diff_key($this->releaseRequest, ['scopes' => 1])))
            ->toThrow(BlaaizException::class, 'scopes is required');
        expect(fn () => $this->service->createReleaseRequest(array_merge($this->releaseRequest, ['scopes' => []])))
            ->toThrow(BlaaizException::class, 'scopes must be a non-empty array');
        expect(fn () => $this->service->createReleaseRequest(array_merge($this->releaseRequest, ['scopes' => 'identity'])))
            ->toThrow(BlaaizException::class, 'scopes must be a non-empty array');
        expect(fn () => $this->service->createReleaseRequest(array_merge($this->releaseRequest, ['scopes' => ['selfie']])))
            ->toThrow(BlaaizException::class, 'scopes must contain only: identity, id_document, address, document_images');
        expect(fn () => $this->service->createReleaseRequest(array_merge($this->releaseRequest, ['scopes' => ['Identity']])))
            ->toThrow(BlaaizException::class, 'scopes must contain only');
    });

    it('validates the ids, the code and the address and makes no HTTP call', function () {
        $this->mockClient->shouldNotReceive('makeRequest');

        expect(fn () => $this->service->exchangeReleaseCode(''))
            ->toThrow(BlaaizException::class, 'Release code is required');
        expect(fn () => $this->service->getRelease(''))
            ->toThrow(BlaaizException::class, 'Release ID is required');
        expect(fn () => $this->service->getReleaseDocument('', 'doc-1'))
            ->toThrow(BlaaizException::class, 'Release ID is required');
        expect(fn () => $this->service->getReleaseDocument('release-1', ''))
            ->toThrow(BlaaizException::class, 'Document ID is required');
        expect(fn () => $this->service->getWalletStatus(''))
            ->toThrow(BlaaizException::class, 'Wallet address is required');
    });
});
