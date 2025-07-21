<?php

use Blaaiz\LaravelSdk\Blaaiz;
use Blaaiz\LaravelSdk\Exceptions\BlaaizException;

/**
 * Integration Tests for Blaaiz Laravel SDK
 *
 * These tests require a valid API key and should be run against a test environment.
 * Set BLAAIZ_API_KEY environment variable to run these tests.
 */

function getBlaaizInstance(): ?Blaaiz
{
    $apiKey = env('BLAAIZ_API_KEY');
    if (!$apiKey) {
        return null;
    }
    
    $baseURL = env('BLAAIZ_API_URL', 'https://api-dev.blaaiz.com');
    return new Blaaiz($apiKey, ['base_url' => $baseURL]);
}

it('should connect to API', function () {
    $blaaiz = getBlaaizInstance();
    if (!$blaaiz) {
        $this->markTestSkipped('BLAAIZ_API_KEY not set');
    }
    
    $isConnected = $blaaiz->testConnection();
    expect($isConnected)->toBe(true);
});

it('should list currencies', function () {
    $blaaiz = getBlaaizInstance();
    if (!$blaaiz) {
        $this->markTestSkipped('BLAAIZ_API_KEY not set');
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
        $this->markTestSkipped('BLAAIZ_API_KEY not set');
    }
    
    $wallets = $blaaiz->wallets->list();
    expect($wallets)->toHaveKey('data');
    expect($wallets['data'])->toBeArray();
});

it('should create and retrieve customer', function () {
    $blaaiz = getBlaaizInstance();
    if (!$blaaiz) {
        $this->markTestSkipped('BLAAIZ_API_KEY not set');
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
        $this->markTestSkipped('BLAAIZ_API_KEY not set');
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
        $this->markTestSkipped('BLAAIZ_API_KEY not set');
    }

    $payload = '{"transaction_id":"test-123","status":"completed"}';
    $secret = 'test-webhook-secret';
    $validSignature = hash_hmac('sha256', $payload, $secret);

    $isValid = $blaaiz->webhooks->verifySignature($payload, $validSignature, $secret);
    expect($isValid)->toBe(true);

    $isInvalid = $blaaiz->webhooks->verifySignature($payload, 'invalid-signature', $secret);
    expect($isInvalid)->toBe(false);
});

it('should construct webhook event', function () {
    $blaaiz = getBlaaizInstance();
    if (!$blaaiz) {
        $this->markTestSkipped('BLAAIZ_API_KEY not set');
    }

    $payload = '{"transaction_id":"test-123","status":"completed"}';
    $secret = 'test-webhook-secret';
    $validSignature = hash_hmac('sha256', $payload, $secret);

    $event = $blaaiz->webhooks->constructEvent($payload, $validSignature, $secret);
    expect($event['transaction_id'])->toBe('test-123');
    expect($event['status'])->toBe('completed');
    expect($event['verified'])->toBe(true);
    expect($event)->toHaveKey('timestamp');
});

it('should handle invalid API key gracefully', function () {
    $invalidBlaaiz = new Blaaiz('invalid-key');

    expect(fn() => $invalidBlaaiz->currencies->list())
        ->toThrow(BlaaizException::class);
});

it('should handle invalid customer creation', function () {
    $blaaiz = getBlaaizInstance();
    if (!$blaaiz) {
        $this->markTestSkipped('BLAAIZ_API_KEY not set');
    }

    expect(fn() => $blaaiz->customers->create([])) // Missing required fields
        ->toThrow(BlaaizException::class);
});