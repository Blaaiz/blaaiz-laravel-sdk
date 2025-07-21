<?php

use Blaaiz\LaravelSdk\Services\PayoutService;
use Blaaiz\LaravelSdk\Exceptions\BlaaizException;
use Blaaiz\LaravelSdk\BlaaizClient;
use Mockery;

describe('PayoutService', function () {
    beforeEach(function () {
        $this->mockClient = Mockery::mock(BlaaizClient::class);
        $this->service = new PayoutService($this->mockClient);
    });

    afterEach(function () {
        Mockery::close();
    });

    it('validates required fields for initiate', function () {
        expect(fn() => $this->service->initiate([]))
            ->toThrow(BlaaizException::class, 'wallet_id is required');

        expect(fn() => $this->service->initiate(['wallet_id' => 'w1']))
            ->toThrow(BlaaizException::class, 'method is required');

        expect(fn() => $this->service->initiate([
            'wallet_id' => 'w1',
            'method' => 'bank_transfer'
        ]))->toThrow(BlaaizException::class, 'from_amount is required');

        expect(fn() => $this->service->initiate([
            'wallet_id' => 'w1',
            'method' => 'bank_transfer',
            'from_amount' => 100
        ]))->toThrow(BlaaizException::class, 'from_currency_id is required');

        expect(fn() => $this->service->initiate([
            'wallet_id' => 'w1',
            'method' => 'bank_transfer',
            'from_amount' => 100,
            'from_currency_id' => 'USD'
        ]))->toThrow(BlaaizException::class, 'to_currency_id is required');
    });

    it('requires account_number for bank_transfer method in initiate', function () {
        $payoutData = [
            'wallet_id' => 'w1',
            'method' => 'bank_transfer',
            'from_amount' => 100,
            'from_currency_id' => 'USD',
            'to_currency_id' => 'NGN'
        ];

        expect(fn() => $this->service->initiate($payoutData))
            ->toThrow(BlaaizException::class, 'account_number is required for bank_transfer method');
    });

    it('requires extra fields for interac method in initiate', function () {
        $basePayoutData = [
            'wallet_id' => 'w1',
            'method' => 'interac',
            'from_amount' => 100,
            'from_currency_id' => 'USD',
            'to_currency_id' => 'CAD'
        ];

        expect(fn() => $this->service->initiate($basePayoutData))
            ->toThrow(BlaaizException::class, 'email is required');

        expect(fn() => $this->service->initiate(array_merge($basePayoutData, ['email' => 'test@example.com'])))
            ->toThrow(BlaaizException::class, 'interac_first_name is required');

        expect(fn() => $this->service->initiate(array_merge($basePayoutData, [
            'email' => 'test@example.com',
            'interac_first_name' => 'John'
        ])))->toThrow(BlaaizException::class, 'interac_last_name is required');
    });

    it('successfully initiates bank_transfer payout', function () {
        $payoutData = [
            'wallet_id' => 'w1',
            'method' => 'bank_transfer',
            'from_amount' => 100,
            'from_currency_id' => 'USD',
            'to_currency_id' => 'NGN',
            'account_number' => '1234567890'
        ];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/payout', $payoutData)
            ->andReturn(['data' => ['id' => 'payout-123']]);

        $result = $this->service->initiate($payoutData);

        expect($result)->toBe(['data' => ['id' => 'payout-123']]);
    });

    it('successfully initiates interac payout', function () {
        $payoutData = [
            'wallet_id' => 'w1',
            'method' => 'interac',
            'from_amount' => 100,
            'from_currency_id' => 'USD',
            'to_currency_id' => 'CAD',
            'email' => 'test@example.com',
            'interac_first_name' => 'John',
            'interac_last_name' => 'Doe'
        ];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/payout', $payoutData)
            ->andReturn(['data' => ['id' => 'payout-123']]);

        $result = $this->service->initiate($payoutData);

        expect($result)->toBe(['data' => ['id' => 'payout-123']]);
    });
});