<?php

use Blaaiz\LaravelSdk\BlaaizClient;
use Blaaiz\LaravelSdk\Exceptions\BlaaizException;
use Blaaiz\LaravelSdk\Services\SwapService;
use Mockery;

describe('SwapService', function () {
    beforeEach(function () {
        $this->mockClient = Mockery::mock(BlaaizClient::class);
        $this->service = new SwapService($this->mockClient);
    });

    afterEach(function () {
        Mockery::close();
    });

    it('validates required fields for swap', function () {
        expect(fn() => $this->service->swap([]))
            ->toThrow(BlaaizException::class, 'from_business_wallet_id is required');

        expect(fn() => $this->service->swap(['from_business_wallet_id' => 'w1']))
            ->toThrow(BlaaizException::class, 'to_business_wallet_id is required');

        expect(fn() => $this->service->swap([
            'from_business_wallet_id' => 'w1',
            'to_business_wallet_id' => 'w2',
        ]))->toThrow(BlaaizException::class, 'amount is required');
    });

    it('calls makeRequest for swap', function () {
        $swapData = [
            'from_business_wallet_id' => 'w1',
            'to_business_wallet_id' => 'w2',
            'amount' => 100,
            'amount_type' => 'from',
        ];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/swap', $swapData)
            ->andReturn(['data' => ['message' => 'Money swap successful!']]);

        $result = $this->service->swap($swapData);
        expect($result)->toBe(['data' => ['message' => 'Money swap successful!']]);
    });

    it('calls makeRequest for swap with amount_type to', function () {
        $swapData = [
            'from_business_wallet_id' => 'w1',
            'to_business_wallet_id' => 'w2',
            'amount' => 160000,
            'amount_type' => 'to',
        ];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/swap', $swapData)
            ->andReturn(['data' => ['message' => 'Money swap successful!']]);

        $result = $this->service->swap($swapData);
        expect($result)->toBe(['data' => ['message' => 'Money swap successful!']]);
    });
});
