<?php

use Blaaiz\LaravelSdk\Services\WebhookService;
use Blaaiz\LaravelSdk\Exceptions\BlaaizException;
use Blaaiz\LaravelSdk\BlaaizClient;
use Carbon\Carbon;
use Mockery;

describe('WebhookService', function () {
    beforeEach(function () {
        $this->mockClient = Mockery::mock(BlaaizClient::class);
        $this->service = new WebhookService($this->mockClient);
    });

    afterEach(function () {
        Mockery::close();
    });

    it('validates required fields for register', function () {
        expect(fn() => $this->service->register([]))
            ->toThrow(BlaaizException::class, 'collection_url is required');

        expect(fn() => $this->service->register(['collection_url' => 'https://example.com/collection']))
            ->toThrow(BlaaizException::class, 'payout_url is required');
    });

    it('calls makeRequest with correct parameters for register', function () {
        $webhookData = [
            'collection_url' => 'https://example.com/collection',
            'payout_url' => 'https://example.com/payout'
        ];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/webhook', $webhookData)
            ->andReturn(['data' => ['id' => 'webhook-123']]);

        $result = $this->service->register($webhookData);

        expect($result)->toBe(['data' => ['id' => 'webhook-123']]);
    });

    it('calls makeRequest with correct parameters for get', function () {
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('GET', '/api/external/webhook')
            ->andReturn(['data' => ['collection_url' => 'https://example.com/collection']]);

        $result = $this->service->get();

        expect($result)->toBe(['data' => ['collection_url' => 'https://example.com/collection']]);
    });

    it('calls makeRequest with correct parameters for update', function () {
        $webhookData = ['collection_url' => 'https://example.com/new-collection'];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('PUT', '/api/external/webhook', $webhookData)
            ->andReturn(['data' => ['id' => 'webhook-123']]);

        $result = $this->service->update($webhookData);

        expect($result)->toBe(['data' => ['id' => 'webhook-123']]);
    });

    it('validates required fields for replay', function () {
        expect(fn() => $this->service->replay([]))
            ->toThrow(BlaaizException::class, 'transaction_id is required');
    });

    it('calls makeRequest with correct parameters for replay', function () {
        $replayData = ['transaction_id' => 'txn-123'];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/webhook/replay', $replayData)
            ->andReturn(['data' => ['status' => 'replayed']]);

        $result = $this->service->replay($replayData);

        expect($result)->toBe(['data' => ['status' => 'replayed']]);
    });

    it('validates required parameters for verifySignature', function () {
        expect(fn() => $this->service->verifySignature('', 'sig', 'secret'))
            ->toThrow(BlaaizException::class, 'Payload is required for signature verification');

        expect(fn() => $this->service->verifySignature('payload', '', 'secret'))
            ->toThrow(BlaaizException::class, 'Signature is required for signature verification');

        expect(fn() => $this->service->verifySignature('payload', 'sig', ''))
            ->toThrow(BlaaizException::class, 'Webhook secret is required for signature verification');
    });

    it('returns true for valid signature', function () {
        $payload = '{"transaction_id":"txn_123","status":"completed"}';
        $secret = 'webhook_secret_key';
        $validSignature = hash_hmac('sha256', $payload, $secret);

        $result = $this->service->verifySignature($payload, $validSignature, $secret);
        expect($result)->toBeTrue();
    });

    it('returns true for valid signature with sha256= prefix', function () {
        $payload = '{"transaction_id":"txn_123","status":"completed"}';
        $secret = 'webhook_secret_key';
        $validSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        $result = $this->service->verifySignature($payload, $validSignature, $secret);
        expect($result)->toBeTrue();
    });

    it('returns false for invalid signature', function () {
        $payload = '{"transaction_id":"txn_123","status":"completed"}';
        $secret = 'webhook_secret_key';
        $invalidSignature = 'invalid_signature';

        $result = $this->service->verifySignature($payload, $invalidSignature, $secret);
        expect($result)->toBeFalse();
    });

    it('works with object payload for verifySignature', function () {
        $payload = ['transaction_id' => 'txn_123', 'status' => 'completed'];
        $secret = 'webhook_secret_key';
        $validSignature = hash_hmac('sha256', json_encode($payload), $secret);

        $result = $this->service->verifySignature($payload, $validSignature, $secret);
        expect($result)->toBeTrue();
    });

    it('validates signature and returns event for constructEvent', function () {
        // Mock Laravel's now() helper
        Carbon::setTestNow(Carbon::create(2023, 1, 1, 12, 0, 0));

        $payload = '{"transaction_id":"txn_123","status":"completed"}';
        $secret = 'webhook_secret_key';
        $validSignature = hash_hmac('sha256', $payload, $secret);

        $event = $this->service->constructEvent($payload, $validSignature, $secret);

        expect($event['transaction_id'])->toBe('txn_123');
        expect($event['status'])->toBe('completed');
        expect($event['verified'])->toBeTrue();
        expect($event['timestamp'])->toBeString();

        // Clean up
        Carbon::setTestNow();
    });

    it('throws error for invalid signature in constructEvent', function () {
        $payload = '{"transaction_id":"txn_123","status":"completed"}';
        $secret = 'webhook_secret_key';
        $invalidSignature = 'invalid_signature';

        expect(fn() => $this->service->constructEvent($payload, $invalidSignature, $secret))
            ->toThrow(BlaaizException::class, 'Invalid webhook signature');
    });

    it('throws error for invalid JSON in constructEvent', function () {
        $payload = 'invalid json';
        $secret = 'webhook_secret_key';
        $validSignature = hash_hmac('sha256', $payload, $secret);

        expect(fn() => $this->service->constructEvent($payload, $validSignature, $secret))
            ->toThrow(BlaaizException::class, 'Invalid webhook payload: unable to parse JSON');
    });

    it('works with object payload for constructEvent', function () {
        // Mock Laravel's now() helper
        Carbon::setTestNow(Carbon::create(2023, 1, 1, 12, 0, 0));

        $payload = ['transaction_id' => 'txn_123', 'status' => 'completed'];
        $secret = 'webhook_secret_key';
        $validSignature = hash_hmac('sha256', json_encode($payload), $secret);

        $event = $this->service->constructEvent($payload, $validSignature, $secret);

        expect($event['transaction_id'])->toBe('txn_123');
        expect($event['status'])->toBe('completed');
        expect($event['verified'])->toBeTrue();

        // Clean up
        Carbon::setTestNow();
    });
});