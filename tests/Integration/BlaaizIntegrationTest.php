<?php

use Blaaiz\LaravelSdk\Blaaiz;
use Blaaiz\LaravelSdk\Exceptions\BlaaizException;

/**
 * Integration Tests for Blaaiz Laravel SDK
 *
 * These tests require valid credentials and should be run against a test environment.
 * Set BLAAIZ_CLIENT_ID + BLAAIZ_CLIENT_SECRET (OAuth) or BLAAIZ_API_KEY (legacy) to run.
 */

function getBlaaizInstance(): ?Blaaiz
{
    $baseURL = env('BLAAIZ_API_URL', 'https://api-dev.blaaiz.com');

    $clientId = env('BLAAIZ_CLIENT_ID');
    $clientSecret = env('BLAAIZ_CLIENT_SECRET');
    if ($clientId && $clientSecret) {
        $options = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'base_url' => $baseURL,
        ];
        $scope = env('BLAAIZ_OAUTH_SCOPE');
        if ($scope) {
            $options['oauth_scope'] = $scope;
        }
        return new Blaaiz($options);
    }

    $apiKey = env('BLAAIZ_API_KEY');
    if ($apiKey) {
        return new Blaaiz(['api_key' => $apiKey, 'base_url' => $baseURL]);
    }

    return null;
}

function skipOnScopeError(BlaaizException $e): void
{
    if (str_contains($e->getMessage(), 'scope') || str_contains($e->getMessage(), 'Scope')) {
        test()->markTestSkipped('OAuth credentials lack required scope: ' . $e->getMessage());
    }

    throw $e;
}

it('should connect to API', function () {
    $blaaiz = getBlaaizInstance();
    if (!$blaaiz) {
        $this->markTestSkipped('No Blaaiz credentials set');
    }
    
    $isConnected = $blaaiz->testConnection();
    expect($isConnected)->toBe(true);
});

it('should list currencies', function () {
    $blaaiz = getBlaaizInstance();
    if (!$blaaiz) {
        $this->markTestSkipped('No Blaaiz credentials set');
    }
    
    try {
        $currencies = $blaaiz->currencies->list();
        expect($currencies)->toHaveKey('data');
        expect($currencies['data'])->toBeArray();
    } catch (BlaaizException $e) {
        if (str_contains($e->getMessage(), 'Column not found') || $e->getStatus() === 500) {
            $this->markTestSkipped("Server-side error: {$e->getMessage()}");
        } else {
            throw $e;
        }
    }
});

it('should list wallets', function () {
    $blaaiz = getBlaaizInstance();
    if (!$blaaiz) {
        $this->markTestSkipped('No Blaaiz credentials set');
    }
    
    $wallets = $blaaiz->wallets->list();
    expect($wallets)->toHaveKey('data');
    expect($wallets['data'])->toBeArray();
});

it('should create and retrieve customer', function () {
    $blaaiz = getBlaaizInstance();
    if (!$blaaiz) {
        $this->markTestSkipped('No Blaaiz credentials set');
    }

    $customerData = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'type' => 'individual',
        'email' => 'john.doe.' . bin2hex(random_bytes(4)) . '@example.com',
        'country' => 'NG',
        'id_type' => 'passport',
        'id_number' => 'A' . strtoupper(bin2hex(random_bytes(4)))
    ];

    $customer = $blaaiz->customers->create($customerData);
    expect($customer)->toHaveKey('data');
    expect($customer['data'])->toHaveKey('data');
    expect($customer['data']['data'])->toHaveKey('id');

    $customerId = $customer['data']['data']['id'];
    $retrievedCustomer = $blaaiz->customers->get($customerId);

    // Handle different response structures
    $actualCustomerId = isset($retrievedCustomer['data']['data']) 
        ? $retrievedCustomer['data']['data']['id'] 
        : $retrievedCustomer['data']['id'];

    expect($actualCustomerId)->toBe($customerId);
});

it('should upload a file', function () {
    $blaaiz = getBlaaizInstance();
    if (!$blaaiz) {
        $this->markTestSkipped('No Blaaiz credentials set');
    }

    // Create a test customer
    $customerData = [
        'first_name' => 'FileTest',
        'last_name' => 'User',
        'email' => 'filetest.' . bin2hex(random_bytes(4)) . '@example.com',
        'type' => 'individual',
        'country' => 'NG',
        'id_type' => 'passport',
        'id_number' => 'A' . strtoupper(bin2hex(random_bytes(4)))
    ];

    $customer = $blaaiz->customers->create($customerData);
    $testCustomerId = $customer['data']['data']['id'];

    $fileOptions = [
        'file' => 'Test passport document content',
        'file_category' => 'identity',
        'filename' => 'test_passport.pdf',
        'content_type' => 'application/pdf'
    ];

    $uploadResult = $blaaiz->customers->uploadFileComplete($testCustomerId, $fileOptions);

    expect($uploadResult)->toHaveKey('file_id');
    expect($uploadResult)->toHaveKey('presigned_url');
    expect($uploadResult['file_id'])->toBeString();
    expect(strlen($uploadResult['file_id']))->toBeGreaterThan(10);
    expect($uploadResult['presigned_url'])->toMatch('/^https:\/\//');
});

it('should verify webhook signature', function () {
    $blaaiz = getBlaaizInstance();
    if (!$blaaiz) {
        $this->markTestSkipped('No Blaaiz credentials set');
    }

    $payload = '{"transaction_id":"test-123","status":"completed"}';
    $secret = 'test-webhook-secret';
    $timestamp = '1234567890';
    $signed = $timestamp . '.' . $payload;
    $validSignature = hash_hmac('sha256', $signed, $secret);

    $isValid = $blaaiz->webhooks->verifySignature($payload, $validSignature, $timestamp, $secret);
    expect($isValid)->toBe(true);

    $isInvalid = $blaaiz->webhooks->verifySignature($payload, 'invalid-signature', $timestamp, $secret);
    expect($isInvalid)->toBe(false);
});

it('should construct webhook event', function () {
    $blaaiz = getBlaaizInstance();
    if (!$blaaiz) {
        $this->markTestSkipped('No Blaaiz credentials set');
    }

    $payload = '{"transaction_id":"test-123","status":"completed"}';
    $secret = 'test-webhook-secret';
    $timestamp = '1234567890';
    $signed = $timestamp . '.' . $payload;
    $validSignature = hash_hmac('sha256', $signed, $secret);

    $event = $blaaiz->webhooks->constructEvent($payload, $validSignature, $timestamp, $secret);
    expect($event['transaction_id'])->toBe('test-123');
    expect($event['status'])->toBe('completed');
    expect($event['verified'])->toBe(true);
    expect($event)->toHaveKey('timestamp');
});

it('should handle invalid API key gracefully', function () {
    $invalidBlaaiz = new Blaaiz(['api_key' => 'invalid-key']);

    expect(fn() => $invalidBlaaiz->currencies->list())
        ->toThrow(BlaaizException::class);
});

it('should handle invalid customer creation', function () {
    $blaaiz = getBlaaizInstance();
    if (!$blaaiz) {
        $this->markTestSkipped('No Blaaiz credentials set');
    }

    expect(fn() => $blaaiz->customers->create([])) // Missing required fields
        ->toThrow(BlaaizException::class);
});

it('should list rates', function () {
    $blaaiz = getBlaaizInstance();
    if (!$blaaiz) {
        $this->markTestSkipped('No Blaaiz credentials set');
    }

    $result = $blaaiz->rates->list();

    expect($result)->toBeArray();
    expect($result)->toHaveKey('data');
    expect($result['data'])->toHaveKey('data');
});

it('should list rates with search term', function () {
    $blaaiz = getBlaaizInstance();
    if (!$blaaiz) {
        $this->markTestSkipped('No Blaaiz credentials set');
    }

    $result = $blaaiz->rates->list('USD');

    expect($result)->toBeArray();
    expect($result)->toHaveKey('data');
});

it('should validate swap requires all fields', function () {
    expect(fn() => (new Blaaiz(['api_key' => 'test']))->swaps->swap([]))
        ->toThrow(BlaaizException::class, 'from_business_wallet_id is required');

    expect(fn() => (new Blaaiz(['api_key' => 'test']))->swaps->swap([
        'from_business_wallet_id' => 'w1',
    ]))->toThrow(BlaaizException::class, 'to_business_wallet_id is required');

    expect(fn() => (new Blaaiz(['api_key' => 'test']))->swaps->swap([
        'from_business_wallet_id' => 'w1',
        'to_business_wallet_id' => 'w2',
    ]))->toThrow(BlaaizException::class, 'amount is required');
});

it('should authenticate with OAuth and list rates', function () {
    $clientId = env('BLAAIZ_CLIENT_ID');
    $clientSecret = env('BLAAIZ_CLIENT_SECRET');
    if (!$clientId || !$clientSecret) {
        $this->markTestSkipped('BLAAIZ_CLIENT_ID and BLAAIZ_CLIENT_SECRET not set');
    }

    $baseURL = env('BLAAIZ_API_URL', 'https://api-dev.blaaiz.com');
    $options = [
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'base_url' => $baseURL,
    ];
    $scope = env('BLAAIZ_OAUTH_SCOPE');
    if ($scope) {
        $options['oauth_scope'] = $scope;
    }
    $blaaiz = new Blaaiz($options);

    $result = $blaaiz->rates->list();

    expect($result)->toBeArray();
    expect($result)->toHaveKey('data');
});

it('should fail OAuth with invalid credentials', function () {
    $blaaiz = new Blaaiz([
        'client_id' => 'invalid-client-id',
        'client_secret' => 'invalid-client-secret',
        'base_url' => 'https://api-dev.blaaiz.com',
    ]);

    expect(fn() => $blaaiz->rates->list())
        ->toThrow(BlaaizException::class);
});