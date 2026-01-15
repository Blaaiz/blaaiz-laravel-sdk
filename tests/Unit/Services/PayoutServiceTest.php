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
            ->toThrow(BlaaizException::class, 'customer_id is required');

        expect(fn() => $this->service->initiate(['wallet_id' => 'w1', 'customer_id' => 'c1']))
            ->toThrow(BlaaizException::class, 'method is required');

        expect(fn() => $this->service->initiate([
            'wallet_id' => 'w1',
            'customer_id' => 'c1',
            'method' => 'bank_transfer'
        ]))->toThrow(BlaaizException::class, 'from_currency_id is required');

        expect(fn() => $this->service->initiate([
            'wallet_id' => 'w1',
            'customer_id' => 'c1',
            'method' => 'bank_transfer',
            'from_currency_id' => 'USD'
        ]))->toThrow(BlaaizException::class, 'to_currency_id is required');

        expect(fn() => $this->service->initiate([
            'wallet_id' => 'w1',
            'customer_id' => 'c1',
            'method' => 'bank_transfer',
            'from_currency_id' => 'USD',
            'to_currency_id' => 'NGN'
        ]))->toThrow(BlaaizException::class, 'Either from_amount or to_amount is required');
    });

    it('requires bank_id and account_number for NGN bank_transfer in initiate', function () {
        $payoutData = [
            'wallet_id' => 'w1',
            'customer_id' => 'c1',
            'method' => 'bank_transfer',
            'from_amount' => 100,
            'from_currency_id' => 'USD',
            'to_currency_id' => 'NGN'
        ];

        expect(fn() => $this->service->initiate($payoutData))
            ->toThrow(BlaaizException::class, 'bank_id is required');

        expect(fn() => $this->service->initiate(array_merge($payoutData, ['bank_id' => 'bank1'])))
            ->toThrow(BlaaizException::class, 'account_number is required');
    });

    it('requires sort_code and account_number for GBP bank_transfer in initiate', function () {
        $payoutData = [
            'wallet_id' => 'w1',
            'customer_id' => 'c1',
            'method' => 'bank_transfer',
            'from_amount' => 100,
            'from_currency_id' => 'USD',
            'to_currency_id' => 'GBP'
        ];

        expect(fn() => $this->service->initiate($payoutData))
            ->toThrow(BlaaizException::class, 'sort_code is required');
    });

    it('requires iban and bic_code for EUR bank_transfer in initiate', function () {
        $payoutData = [
            'wallet_id' => 'w1',
            'customer_id' => 'c1',
            'method' => 'bank_transfer',
            'from_amount' => 100,
            'from_currency_id' => 'USD',
            'to_currency_id' => 'EUR'
        ];

        expect(fn() => $this->service->initiate($payoutData))
            ->toThrow(BlaaizException::class, 'iban is required');
    });

    it('requires extra fields for interac method in initiate', function () {
        $basePayoutData = [
            'wallet_id' => 'w1',
            'customer_id' => 'c1',
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

    it('requires crypto fields for crypto method in initiate', function () {
        $basePayoutData = [
            'wallet_id' => 'w1',
            'customer_id' => 'c1',
            'method' => 'crypto',
            'from_amount' => 100,
            'from_currency_id' => 'USD',
            'to_currency_id' => 'USDT'
        ];

        expect(fn() => $this->service->initiate($basePayoutData))
            ->toThrow(BlaaizException::class, 'wallet_address is required');
    });

    it('requires ach/wire fields for ach method in initiate', function () {
        $basePayoutData = [
            'wallet_id' => 'w1',
            'customer_id' => 'c1',
            'method' => 'ach',
            'from_amount' => 100,
            'from_currency_id' => 'USD',
            'to_currency_id' => 'USD'
        ];

        expect(fn() => $this->service->initiate($basePayoutData))
            ->toThrow(BlaaizException::class, 'type is required');
    });

    it('successfully initiates NGN bank_transfer payout', function () {
        $payoutData = [
            'wallet_id' => 'w1',
            'customer_id' => 'c1',
            'method' => 'bank_transfer',
            'from_amount' => 100,
            'from_currency_id' => 'USD',
            'to_currency_id' => 'NGN',
            'bank_id' => 'bank1',
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
            'customer_id' => 'c1',
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

    it('accepts to_amount instead of from_amount', function () {
        $payoutData = [
            'wallet_id' => 'w1',
            'customer_id' => 'c1',
            'method' => 'bank_transfer',
            'to_amount' => 50000,
            'from_currency_id' => 'USD',
            'to_currency_id' => 'NGN',
            'bank_id' => 'bank1',
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
});