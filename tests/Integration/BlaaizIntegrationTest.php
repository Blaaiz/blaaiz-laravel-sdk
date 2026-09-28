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
        test()->markTestSkipped('OAuth credentials lack required scope: '.$e->getMessage());
    }

    throw $e;
}

function createSignaSession(Blaaiz $blaaiz, string $runId, string $suffix, array $overrides = []): array
{
    return $blaaiz->signa->createSession(array_merge([
        'customer_reference' => "sdk-it-{$runId}-{$suffix}",
        'idempotency_key' => "sdk-it-{$runId}-{$suffix}",
        'requirements' => ['DOCUMENTS'],
        'fulfilment_mode' => 'HEADLESS',
        'applicant' => ['first_name' => 'Ada', 'last_name' => 'Lovelace', 'country' => 'GBR'],
    ], $overrides));
}

function putToSignaUploadUrl(string $url, array $headers, string $body): \GuzzleHttp\Psr7\Response
{
    return (new \GuzzleHttp\Client)->request('PUT', $url, [
        'headers' => array_merge($headers, ['Content-Length' => (string) strlen($body)]),
        'body' => $body,
    ]);
}

it('should connect to API', function () {
    $blaaiz = getBlaaizInstance();
    if (! $blaaiz) {
        $this->markTestSkipped('No Blaaiz credentials set');
    }

    $isConnected = $blaaiz->testConnection();
    expect($isConnected)->toBe(true);
});

it('should list currencies', function () {
    $blaaiz = getBlaaizInstance();
    if (! $blaaiz) {
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
    if (! $blaaiz) {
        $this->markTestSkipped('No Blaaiz credentials set');
    }

    $wallets = $blaaiz->wallets->list();
    expect($wallets)->toHaveKey('data');
    expect($wallets['data'])->toBeArray();
});

it('should create and retrieve customer', function () {
    $blaaiz = getBlaaizInstance();
    if (! $blaaiz) {
        $this->markTestSkipped('No Blaaiz credentials set');
    }

    $customerData = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'type' => 'individual',
        'email' => 'john.doe.'.bin2hex(random_bytes(4)).'@example.com',
        'country' => 'NG',
        'id_type' => 'passport',
        'id_number' => 'A'.strtoupper(bin2hex(random_bytes(4))),
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
    if (! $blaaiz) {
        $this->markTestSkipped('No Blaaiz credentials set');
    }

    // Create a test customer
    $customerData = [
        'first_name' => 'FileTest',
        'last_name' => 'User',
        'email' => 'filetest.'.bin2hex(random_bytes(4)).'@example.com',
        'type' => 'individual',
        'country' => 'NG',
        'id_type' => 'passport',
        'id_number' => 'A'.strtoupper(bin2hex(random_bytes(4))),
    ];

    $customer = $blaaiz->customers->create($customerData);
    $testCustomerId = $customer['data']['data']['id'];

    $fileOptions = [
        'file' => 'Test passport document content',
        'file_category' => 'identity',
        'filename' => 'test_passport.pdf',
        'content_type' => 'application/pdf',
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
    if (! $blaaiz) {
        $this->markTestSkipped('No Blaaiz credentials set');
    }

    $payload = '{"transaction_id":"test-123","status":"completed"}';
    $secret = 'test-webhook-secret';
    $timestamp = '1234567890';
    $signed = $timestamp.'.'.$payload;
    $validSignature = hash_hmac('sha256', $signed, $secret);

    $isValid = $blaaiz->webhooks->verifySignature($payload, $validSignature, $timestamp, $secret);
    expect($isValid)->toBe(true);

    $isInvalid = $blaaiz->webhooks->verifySignature($payload, 'invalid-signature', $timestamp, $secret);
    expect($isInvalid)->toBe(false);
});

it('should construct webhook event', function () {
    $blaaiz = getBlaaizInstance();
    if (! $blaaiz) {
        $this->markTestSkipped('No Blaaiz credentials set');
    }

    $payload = '{"transaction_id":"test-123","status":"completed"}';
    $secret = 'test-webhook-secret';
    $timestamp = '1234567890';
    $signed = $timestamp.'.'.$payload;
    $validSignature = hash_hmac('sha256', $signed, $secret);

    $event = $blaaiz->webhooks->constructEvent($payload, $validSignature, $timestamp, $secret);
    expect($event['transaction_id'])->toBe('test-123');
    expect($event['status'])->toBe('completed');
    expect($event['verified'])->toBe(true);
    expect($event)->toHaveKey('timestamp');
});

it('should handle invalid API key gracefully', function () {
    $invalidBlaaiz = new Blaaiz(['api_key' => 'invalid-key']);

    expect(fn () => $invalidBlaaiz->currencies->list())
        ->toThrow(BlaaizException::class);
});

it('should handle invalid customer creation', function () {
    $blaaiz = getBlaaizInstance();
    if (! $blaaiz) {
        $this->markTestSkipped('No Blaaiz credentials set');
    }

    expect(fn () => $blaaiz->customers->create([])) // Missing required fields
        ->toThrow(BlaaizException::class);
});

it('should list rates', function () {
    $blaaiz = getBlaaizInstance();
    if (! $blaaiz) {
        $this->markTestSkipped('No Blaaiz credentials set');
    }

    $result = $blaaiz->rates->list();

    expect($result)->toBeArray();
    expect($result)->toHaveKey('data');
    expect($result['data'])->toHaveKey('data');
});

it('should list rates with search term', function () {
    $blaaiz = getBlaaizInstance();
    if (! $blaaiz) {
        $this->markTestSkipped('No Blaaiz credentials set');
    }

    $result = $blaaiz->rates->list('USD');

    expect($result)->toBeArray();
    expect($result)->toHaveKey('data');
});

it('should validate swap requires all fields', function () {
    expect(fn () => (new Blaaiz(['api_key' => 'test']))->swaps->initiate([]))
        ->toThrow(BlaaizException::class, 'from_business_wallet_id is required');

    expect(fn () => (new Blaaiz(['api_key' => 'test']))->swaps->initiate([
        'from_business_wallet_id' => 'w1',
    ]))->toThrow(BlaaizException::class, 'to_business_wallet_id is required');

    expect(fn () => (new Blaaiz(['api_key' => 'test']))->swaps->initiate([
        'from_business_wallet_id' => 'w1',
        'to_business_wallet_id' => 'w2',
    ]))->toThrow(BlaaizException::class, 'amount is required');
});

it('should authenticate with OAuth and list rates', function () {
    $clientId = env('BLAAIZ_CLIENT_ID');
    $clientSecret = env('BLAAIZ_CLIENT_SECRET');
    if (! $clientId || ! $clientSecret) {
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

    expect(fn () => $blaaiz->rates->list())
        ->toThrow(BlaaizException::class);
});

describe('Signa KYC Sessions', function () {
    // Requires the API key to hold the compliance-kyc:read/create/cancel scopes.
    // The API only accepts server-side uploads and submit on HEADLESS sessions,
    // and only issues verification links for HOSTED ones, so each flow gets its own session.
    // depends() keeps the headless chain in order even when phpunit.xml reorders defects first.
    $runId = time().'-'.bin2hex(random_bytes(4));

    it('should create a headless session', function () use ($runId) {
        $blaaiz = getBlaaizInstance();
        if (! $blaaiz) {
            $this->markTestSkipped('No Blaaiz credentials set');
        }

        try {
            $response = createSignaSession($blaaiz, $runId, 'main');
        } catch (BlaaizException $e) {
            skipOnScopeError($e);
        }

        $session = $response['data']['data'];

        expect($response['status'])->toBeGreaterThanOrEqual(200);
        expect($response['status'])->toBeLessThan(300);
        expect($session['id'])->toBeString();
        expect($session['status'])->toBeString();
        expect($session['customer_reference'])->toBe("sdk-it-{$runId}-main");

        return $session['id'];
    });

    it('should replay the same session for a repeated idempotency key', function (string $sessionId) use ($runId) {
        $response = createSignaSession(getBlaaizInstance(), $runId, 'main');

        expect($response['data']['data']['id'])->toBe($sessionId);
    })->depends('it should create a headless session');

    it('should list and get the session', function (string $sessionId) {
        $blaaiz = getBlaaizInstance();

        $list = $blaaiz->signa->listSessions(['limit' => 50, 'offset' => 0]);
        expect($list['data']['data']['sessions'])->toBeArray();

        $found = $blaaiz->signa->getSession($sessionId);
        expect($found['data']['data']['id'])->toBe($sessionId);
    })->depends('it should create a headless session');

    it('should upload a staged document through the upload URL', function (string $sessionId) {
        $blaaiz = getBlaaizInstance();

        $upload = $blaaiz->signa->createDocumentUploadUrl($sessionId, [
            'file_name' => 'blank.pdf',
            'id_doc_type' => 'PASSPORT',
        ]);
        $uploadData = $upload['data']['data'];

        expect($uploadData['url'])->toMatch('/^https:\/\//');
        expect($uploadData['file_name'])->toBeString();
        expect($uploadData['headers'])->toBeArray();

        $pdfContent = file_get_contents(__DIR__.'/../blank.pdf');
        $put = putToSignaUploadUrl($uploadData['url'], $uploadData['headers'], $pdfContent);
        expect($put->getStatusCode())->toBeGreaterThanOrEqual(200);
        expect($put->getStatusCode())->toBeLessThan(300);

        $registered = $blaaiz->signa->uploadSessionDocument($sessionId, [
            'filename' => 'blank.pdf',
            'content_type' => 'application/pdf',
            'id_doc_type' => 'PASSPORT',
            'country' => 'GBR',
            'file_name' => $uploadData['file_name'],
        ]);
        expect($registered['data']['data']['id'])->toBe($sessionId);

        return $sessionId;
    })->depends('it should create a headless session');

    it('should submit the session', function (string $sessionId) {
        $submitted = getBlaaizInstance()->signa->submitSession($sessionId);

        expect($submitted['data']['data']['id'])->toBe($sessionId);
    })->depends('it should upload a staged document through the upload URL');

    it('should upload an inline base64 document', function () use ($runId) {
        $blaaiz = getBlaaizInstance();
        if (! $blaaiz) {
            $this->markTestSkipped('No Blaaiz credentials set');
        }

        try {
            $created = createSignaSession($blaaiz, $runId, 'inline');
        } catch (BlaaizException $e) {
            skipOnScopeError($e);
        }
        $sessionId = $created['data']['data']['id'];

        $pdfContent = file_get_contents(__DIR__.'/../blank.pdf');
        $registered = $blaaiz->signa->uploadSessionDocument($sessionId, [
            'filename' => 'blank.pdf',
            'content_type' => 'application/pdf',
            'id_doc_type' => 'PASSPORT',
            'country' => 'GBR',
            'content_base64' => base64_encode($pdfContent),
        ]);

        expect($registered['data']['data']['id'])->toBe($sessionId);
    });

    it('should issue a hosted verification link', function () use ($runId) {
        $blaaiz = getBlaaizInstance();
        if (! $blaaiz) {
            $this->markTestSkipped('No Blaaiz credentials set');
        }

        try {
            $hosted = createSignaSession($blaaiz, $runId, 'hosted', [
                'requirements' => ['DOCUMENTS', 'SELFIE', 'FACE_MATCH'],
                'fulfilment_mode' => 'HOSTED',
            ]);
        } catch (BlaaizException $e) {
            skipOnScopeError($e);
        }

        $link = $blaaiz->signa->issueVerificationLink($hosted['data']['data']['id']);

        expect($link['data']['data']['verification_link'])->toMatch('/^https:\/\//');
    });

    it('should cancel a separate session', function () use ($runId) {
        $blaaiz = getBlaaizInstance();
        if (! $blaaiz) {
            $this->markTestSkipped('No Blaaiz credentials set');
        }

        try {
            $created = createSignaSession($blaaiz, $runId, 'cancel');
        } catch (BlaaizException $e) {
            skipOnScopeError($e);
        }

        $cancelled = $blaaiz->signa->cancelSession($created['data']['data']['id']);

        expect($cancelled['data']['data']['id'])->toBe($created['data']['data']['id']);
        expect($cancelled['data']['data']['status'])->not->toBe($created['data']['data']['status']);
    });

    it('should return 404 for an unknown session', function () {
        $blaaiz = getBlaaizInstance();
        if (! $blaaiz) {
            $this->markTestSkipped('No Blaaiz credentials set');
        }

        try {
            $blaaiz->signa->getSession('00000000-0000-0000-0000-000000000000');
            $this->fail('Expected a BlaaizException for an unknown session');
        } catch (BlaaizException $e) {
            if ($e->getStatus() !== 404) {
                skipOnScopeError($e);
            }
            expect($e->getStatus())->toBe(404);
        }
    });
});
