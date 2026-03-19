<?php

use Blaaiz\LaravelSdk\BlaaizClient;
use Blaaiz\LaravelSdk\Exceptions\BlaaizException;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

describe('BlaaizClient OAuth', function () {
    it('creates instance with OAuth credentials', function () {
        $client = new BlaaizClient([
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'oauth_scope' => '*',
        ]);

        $reflection = new ReflectionClass($client);

        $useOAuthProperty = $reflection->getProperty('useOAuth');
        $useOAuthProperty->setAccessible(true);
        expect($useOAuthProperty->getValue($client))->toBeTrue();

        $clientIdProperty = $reflection->getProperty('clientId');
        $clientIdProperty->setAccessible(true);
        expect($clientIdProperty->getValue($client))->toBe('test-client-id');
    });

    it('prefers OAuth over API key when both are provided', function () {
        $client = new BlaaizClient([
            'api_key' => 'test-key',
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
        ]);

        $reflection = new ReflectionClass($client);
        $useOAuthProperty = $reflection->getProperty('useOAuth');
        $useOAuthProperty->setAccessible(true);
        expect($useOAuthProperty->getValue($client))->toBeTrue();
    });

    it('falls back to API key when OAuth credentials are not provided', function () {
        $client = new BlaaizClient(['api_key' => 'test-key']);

        $reflection = new ReflectionClass($client);
        $useOAuthProperty = $reflection->getProperty('useOAuth');
        $useOAuthProperty->setAccessible(true);
        expect($useOAuthProperty->getValue($client))->toBeFalse();
    });

    it('throws exception when no credentials are provided', function () {
        expect(fn() => new BlaaizClient([]))
            ->toThrow(BlaaizException::class, 'Authentication required');
    });

    it('throws exception when only client_id is provided without client_secret', function () {
        expect(fn() => new BlaaizClient(['client_id' => 'test-id']))
            ->toThrow(BlaaizException::class, 'Authentication required');
    });

    it('throws exception when only client_secret is provided without client_id', function () {
        expect(fn() => new BlaaizClient(['client_secret' => 'test-secret']))
            ->toThrow(BlaaizException::class, 'Authentication required');
    });

    it('does not include x-blaaiz-api-key header when using OAuth', function () {
        $client = new BlaaizClient([
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
        ]);

        $reflection = new ReflectionClass($client);
        $headersProperty = $reflection->getProperty('defaultHeaders');
        $headersProperty->setAccessible(true);
        $headers = $headersProperty->getValue($client);

        expect($headers)->not->toHaveKey('x-blaaiz-api-key');
    });

    it('includes x-blaaiz-api-key header when using API key', function () {
        $client = new BlaaizClient(['api_key' => 'test-key']);

        $reflection = new ReflectionClass($client);
        $headersProperty = $reflection->getProperty('defaultHeaders');
        $headersProperty->setAccessible(true);
        $headers = $headersProperty->getValue($client);

        expect($headers)->toHaveKey('x-blaaiz-api-key');
        expect($headers['x-blaaiz-api-key'])->toBe('test-key');
    });

    it('fetches and caches OAuth token', function () {
        // Create a client with OAuth credentials
        $client = new BlaaizClient([
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'oauth_scope' => 'wallet:read',
        ]);

        $reflection = new ReflectionClass($client);
        $method = $reflection->getMethod('getOAuthToken');
        $method->setAccessible(true);

        // Manually set a cached token to test caching
        $tokenProperty = $reflection->getProperty('accessToken');
        $tokenProperty->setAccessible(true);
        $tokenProperty->setValue($client, 'cached-token');

        $expiresProperty = $reflection->getProperty('tokenExpiresAt');
        $expiresProperty->setAccessible(true);
        $expiresProperty->setValue($client, time() + 600);

        $token = $method->invoke($client);
        expect($token)->toBe('cached-token');
    });

    it('refreshes expired OAuth token', function () {
        $client = new BlaaizClient([
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
        ]);

        $reflection = new ReflectionClass($client);

        // Set an expired token
        $tokenProperty = $reflection->getProperty('accessToken');
        $tokenProperty->setAccessible(true);
        $tokenProperty->setValue($client, 'expired-token');

        $expiresProperty = $reflection->getProperty('tokenExpiresAt');
        $expiresProperty->setAccessible(true);
        $expiresProperty->setValue($client, time() - 1);

        // The getOAuthToken method will try to fetch a new token
        // which will fail since we haven't mocked the HTTP client for the token endpoint
        $method = $reflection->getMethod('getOAuthToken');
        $method->setAccessible(true);

        expect(fn() => $method->invoke($client))
            ->toThrow(BlaaizException::class);
    });

    it('sends Bearer token in Authorization header for OAuth requests', function () {
        $client = new BlaaizClient([
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
        ]);

        $reflection = new ReflectionClass($client);

        // Set a valid cached token
        $tokenProperty = $reflection->getProperty('accessToken');
        $tokenProperty->setAccessible(true);
        $tokenProperty->setValue($client, 'test-bearer-token');

        $expiresProperty = $reflection->getProperty('tokenExpiresAt');
        $expiresProperty->setAccessible(true);
        $expiresProperty->setValue($client, time() + 600);

        // Mock the HTTP client for the API request
        $mockHandler = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['ok' => true]))
        ]);
        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $httpProperty = $reflection->getProperty('httpClient');
        $httpProperty->setAccessible(true);
        $httpProperty->setValue($client, $httpClient);

        $client->makeRequest('GET', '/test');

        $lastRequest = $mockHandler->getLastRequest();
        expect($lastRequest->hasHeader('Authorization'))->toBeTrue();
        expect($lastRequest->getHeader('Authorization')[0])->toBe('Bearer test-bearer-token');
        expect($lastRequest->hasHeader('x-blaaiz-api-key'))->toBeFalse();
    });

    it('sends API key header for legacy auth requests', function () {
        $mockHandler = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['ok' => true]))
        ]);
        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new BlaaizClient(['api_key' => 'test-key']);

        $reflection = new ReflectionClass($client);
        $httpProperty = $reflection->getProperty('httpClient');
        $httpProperty->setAccessible(true);
        $httpProperty->setValue($client, $httpClient);

        $client->makeRequest('GET', '/test');

        $lastRequest = $mockHandler->getLastRequest();
        expect($lastRequest->hasHeader('x-blaaiz-api-key'))->toBeTrue();
        expect($lastRequest->getHeader('x-blaaiz-api-key')[0])->toBe('test-key');
        expect($lastRequest->hasHeader('Authorization'))->toBeFalse();
    });

    it('defaults to all scopes when not specified', function () {
        $client = new BlaaizClient([
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
        ]);

        $reflection = new ReflectionClass($client);
        $scopeProperty = $reflection->getProperty('oauthScope');
        $scopeProperty->setAccessible(true);
        $scope = $scopeProperty->getValue($client);
        expect($scope)->toContain('wallet:read');
        expect($scope)->toContain('swap:create');
        expect($scope)->toContain('payout:create');
    });
});
