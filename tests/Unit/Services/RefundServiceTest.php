<?php

use Blaaiz\LaravelSdk\BlaaizClient;
use Blaaiz\LaravelSdk\Exceptions\BlaaizException;
use Blaaiz\LaravelSdk\Services\RefundService;

describe('RefundService', function () {
    beforeEach(function () {
        $this->mockClient = Mockery::mock(BlaaizClient::class);
        $this->service = new RefundService($this->mockClient);
    });

    afterEach(function () {
        Mockery::close();
    });

    it('validates transaction_id for initiate', function () {
        expect(fn () => $this->service->initiate([]))
            ->toThrow(BlaaizException::class, 'transaction_id is required');
    });

    it('calls makeRequest for initiate', function () {
        $refundData = ['transaction_id' => 'txn1', 'reason' => 'duplicate'];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/refund', $refundData)
            ->andReturn(['data' => ['id' => 'refund-1', 'status' => 'PENDING']]);

        $result = $this->service->initiate($refundData);
        expect($result)->toBe(['data' => ['id' => 'refund-1', 'status' => 'PENDING']]);
    });

    it('validates refund ID for get', function () {
        expect(fn () => $this->service->get(''))
            ->toThrow(BlaaizException::class, 'Refund ID is required');
    });

    it('calls makeRequest for get', function () {
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('GET', '/api/external/refund/refund-1')
            ->andReturn(['data' => ['id' => 'refund-1']]);

        $result = $this->service->get('refund-1');
        expect($result)->toBe(['data' => ['id' => 'refund-1']]);
    });
});
