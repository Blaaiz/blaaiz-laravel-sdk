<?php

use Blaaiz\LaravelSdk\BlaaizClient;
use Blaaiz\LaravelSdk\Exceptions\BlaaizException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

function makeQueuedBlaaizClient(array $options, array $queuedClients): BlaaizClient
{
    return new class($options, $queuedClients) extends BlaaizClient {
        public function __construct(array $options, private array $queuedClients)
        {
            parent::__construct($options);
        }

        protected function createHttpClient(array $config): Client
        {
            return array_shift($this->queuedClients) ?? parent::createHttpClient($config);
        }
    };
}

describe('BlaaizClient.makeRequest', function () {
    it('resolves on successful request', function () {
        $mockHandler = new MockHandler([
            new Response(200, [
                'Content-Type' => 'application/json',
                'custom' => '1'
            ], json_encode(['ok' => true]))
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new BlaaizClient(['api_key' => 'test-key']);
        
        $reflection = new ReflectionClass($client);
        $property = $reflection->getProperty('httpClient');
        $property->setAccessible(true);
        $property->setValue($client, $httpClient);

        $result = $client->makeRequest('GET', '/test');

        expect($result['data'])->toBe(['ok' => true]);
        expect($result['status'])->toBe(200);
        expect($result['headers']['custom'])->toBe(['1']);
    });

    it('rejects on non-2xx status', function () {
        $mockHandler = new MockHandler([
            new Response(400, ['Content-Type' => 'application/json'], json_encode([
                'message' => 'bad request',
                'code' => 'ERR'
            ]))
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new BlaaizClient(['api_key' => 'test-key']);
        
        $reflection = new ReflectionClass($client);
        $property = $reflection->getProperty('httpClient');
        $property->setAccessible(true);
        $property->setValue($client, $httpClient);

        expect(fn() => $client->makeRequest('GET', '/bad'))
            ->toThrow(BlaaizException::class, 'bad request');
    });

    it('rejects on invalid JSON', function () {
        $mockHandler = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], 'invalid json')
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new BlaaizClient(['api_key' => 'test-key']);
        
        $reflection = new ReflectionClass($client);
        $property = $reflection->getProperty('httpClient');
        $property->setAccessible(true);
        $property->setValue($client, $httpClient);

        expect(fn() => $client->makeRequest('GET', '/bad'))
            ->toThrow(BlaaizException::class, 'Failed to parse API response');
    });

    it('handles request errors', function () {
        $mockHandler = new MockHandler([
            new RequestException(
                'Connection timeout',
                new Request('GET', '/test'),
                new Response(500, [], json_encode(['message' => 'Internal Server Error', 'code' => 'SERVER_ERROR']))
            )
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new BlaaizClient(['api_key' => 'test-key']);
        
        $reflection = new ReflectionClass($client);
        $property = $reflection->getProperty('httpClient');
        $property->setAccessible(true);
        $property->setValue($client, $httpClient);

        expect(fn() => $client->makeRequest('GET', '/test'))
            ->toThrow(BlaaizException::class);
    });

    it('uses non-2xx response body when http errors are disabled', function () {
        $mockHandler = new MockHandler([
            new Response(500, ['Content-Type' => 'application/json'], json_encode([
                'message' => 'server exploded',
                'code' => 'SERVER_ERROR',
            ])),
        ]);

        $client = new Client([
            'handler' => HandlerStack::create($mockHandler),
            'http_errors' => false,
        ]);

        $blaaizClient = new BlaaizClient(['api_key' => 'test-key']);

        $reflection = new ReflectionClass($blaaizClient);
        $property = $reflection->getProperty('httpClient');
        $property->setAccessible(true);
        $property->setValue($blaaizClient, $client);

        expect(fn() => $blaaizClient->makeRequest('GET', '/test'))
            ->toThrow(BlaaizException::class, 'server exploded');
    });

    it('falls back to request exception message when response body is empty', function () {
        $mockHandler = new MockHandler([
            new RequestException('Connection timeout', new Request('GET', '/test'))
        ]);

        $client = new Client(['handler' => HandlerStack::create($mockHandler)]);
        $blaaizClient = new BlaaizClient(['api_key' => 'test-key']);

        $reflection = new ReflectionClass($blaaizClient);
        $property = $reflection->getProperty('httpClient');
        $property->setAccessible(true);
        $property->setValue($blaaizClient, $client);

        expect(fn() => $blaaizClient->makeRequest('GET', '/test'))
            ->toThrow(BlaaizException::class, 'Connection timeout');
    });

    it('wraps unexpected exceptions from handlers', function () {
        $handler = static function (): void {
            throw new RuntimeException('boom');
        };

        $client = new Client(['handler' => $handler]);
        $blaaizClient = new BlaaizClient(['api_key' => 'test-key']);

        $reflection = new ReflectionClass($blaaizClient);
        $property = $reflection->getProperty('httpClient');
        $property->setAccessible(true);
        $property->setValue($blaaizClient, $client);

        expect(fn() => $blaaizClient->makeRequest('GET', '/test'))
            ->toThrow(BlaaizException::class, 'Unexpected error: boom');
    });

    it('sends correct headers', function () {
        $mockHandler = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['ok' => true]))
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new BlaaizClient(['api_key' => 'test-key']);
        
        $reflection = new ReflectionClass($client);
        $property = $reflection->getProperty('httpClient');
        $property->setAccessible(true);
        $property->setValue($client, $httpClient);

        $client->makeRequest('GET', '/test');

        $lastRequest = $mockHandler->getLastRequest();
        expect($lastRequest->hasHeader('x-blaaiz-api-key'))->toBeTrue();
        expect($lastRequest->getHeader('x-blaaiz-api-key')[0])->toBe('test-key');
        expect($lastRequest->getHeader('Accept')[0])->toBe('application/json');
        expect($lastRequest->getHeader('User-Agent')[0])->toBe('Blaaiz-Laravel-SDK/1.0.0');
    });

    it('sends JSON data for POST requests', function () {
        $mockHandler = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['ok' => true]))
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new BlaaizClient(['api_key' => 'test-key']);
        
        $reflection = new ReflectionClass($client);
        $property = $reflection->getProperty('httpClient');
        $property->setAccessible(true);
        $property->setValue($client, $httpClient);

        $testData = ['name' => 'test', 'value' => 123];
        $client->makeRequest('POST', '/test', $testData);

        $lastRequest = $mockHandler->getLastRequest();
        $requestBody = json_decode($lastRequest->getBody()->getContents(), true);
        expect($requestBody)->toBe($testData);
    });

    it('sends data as query params for GET requests', function () {
        $mockHandler = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['ok' => true]))
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $client = new BlaaizClient(['api_key' => 'test-key']);

        $reflection = new ReflectionClass($client);
        $property = $reflection->getProperty('httpClient');
        $property->setAccessible(true);
        $property->setValue($client, $httpClient);

        $client->makeRequest('GET', '/test', ['search_term' => 'USD']);

        $lastRequest = $mockHandler->getLastRequest();
        expect($lastRequest->getBody()->getContents())->toBe('');
        expect($lastRequest->getUri()->getQuery())->toContain('search_term=USD');
    });
});

describe('BlaaizClient constructor', function () {
    it('throws exception when no auth credentials provided', function () {
        expect(fn() => new BlaaizClient([]))
            ->toThrow(BlaaizException::class, 'Authentication required');
    });

    it('sets default configuration', function () {
        $client = new BlaaizClient(['api_key' => 'test-key']);

        $reflection = new ReflectionClass($client);
        
        $apiKeyProperty = $reflection->getProperty('apiKey');
        $apiKeyProperty->setAccessible(true);
        expect($apiKeyProperty->getValue($client))->toBe('test-key');

        $baseUrlProperty = $reflection->getProperty('baseUrl');
        $baseUrlProperty->setAccessible(true);
        expect($baseUrlProperty->getValue($client))->toBe('https://api-dev.blaaiz.com');

        $timeoutProperty = $reflection->getProperty('timeout');
        $timeoutProperty->setAccessible(true);
        expect($timeoutProperty->getValue($client))->toBe(30);
    });

    it('accepts custom configuration', function () {
        $options = [
            'base_url' => 'https://api.custom.com',
            'timeout' => 60
        ];

        $client = new BlaaizClient(array_merge(['api_key' => 'test-key'], $options));

        $reflection = new ReflectionClass($client);
        
        $baseUrlProperty = $reflection->getProperty('baseUrl');
        $baseUrlProperty->setAccessible(true);
        expect($baseUrlProperty->getValue($client))->toBe('https://api.custom.com');

        $timeoutProperty = $reflection->getProperty('timeout');
        $timeoutProperty->setAccessible(true);
        expect($timeoutProperty->getValue($client))->toBe(60);
    });
});

describe('BlaaizClient.uploadFile', function () {
    it('uploads file successfully', function () {
        $uploadClient = new Client([
            'handler' => HandlerStack::create(new MockHandler([
                new Response(200, ['ETag' => '"etag-123"']),
            ])),
        ]);

        $client = makeQueuedBlaaizClient(['api_key' => 'test-key'], [
            new Client(['handler' => HandlerStack::create(new MockHandler([]))]),
            $uploadClient,
        ]);

        $result = $client->uploadFile('https://example.com/upload', 'file-content', 'application/pdf', 'test.pdf');

        expect($result)->toBe([
            'status' => 200,
            'etag' => '"etag-123"',
        ]);
    });

    it('uploads file without content type and filename', function () {
        $uploadClient = new Client([
            'handler' => HandlerStack::create(new MockHandler([
                new Response(200, ['etag' => '"etag-456"']),
            ])),
        ]);

        $client = makeQueuedBlaaizClient(['api_key' => 'test-key'], [
            new Client(['handler' => HandlerStack::create(new MockHandler([]))]),
            $uploadClient,
        ]);

        $result = $client->uploadFile('https://example.com/upload', 'file-content');

        expect($result['etag'])->toBe('"etag-456"');
    });

    it('throws exception when no ETag received', function () {
        $uploadClient = new Client([
            'handler' => HandlerStack::create(new MockHandler([
                new Response(200, []),
            ])),
        ]);

        $client = makeQueuedBlaaizClient(['api_key' => 'test-key'], [
            new Client(['handler' => HandlerStack::create(new MockHandler([]))]),
            $uploadClient,
        ]);

        expect(fn() => $client->uploadFile('https://example.com/upload', 'file-content'))
            ->toThrow(BlaaizException::class, 'S3 upload failed: No ETag received from S3');
    });

    it('handles S3 upload request exceptions', function () {
        $uploadClient = new Client([
            'handler' => HandlerStack::create(new MockHandler([
                new RequestException(
                    'Upload failed',
                    new Request('PUT', 'https://example.com/upload'),
                    new Response(403, [], 'forbidden')
                ),
            ])),
        ]);

        $client = makeQueuedBlaaizClient(['api_key' => 'test-key'], [
            new Client(['handler' => HandlerStack::create(new MockHandler([]))]),
            $uploadClient,
        ]);

        expect(fn() => $client->uploadFile('https://example.com/upload', 'file-content'))
            ->toThrow(BlaaizException::class, 'S3 upload failed with status 403: forbidden');
    });

    it('handles S3 upload guzzle exceptions', function () {
        $handler = static function (): never {
            throw new class('Network failed') extends RuntimeException implements GuzzleException {};
        };

        $uploadClient = new Client(['handler' => $handler]);

        $client = makeQueuedBlaaizClient(['api_key' => 'test-key'], [
            new Client(['handler' => HandlerStack::create(new MockHandler([]))]),
            $uploadClient,
        ]);

        expect(fn() => $client->uploadFile('https://example.com/upload', 'file-content'))
            ->toThrow(BlaaizException::class, 'S3 upload request failed: Network failed');
    });
});

describe('BlaaizClient.downloadFile', function () {
    it('downloads file successfully', function () {
        $downloadClient = new Client([
            'handler' => HandlerStack::create(new MockHandler([
                new Response(200, [
                    'Content-Type' => 'image/jpeg',
                    'Content-Disposition' => 'attachment; filename="passport.jpg"',
                ], 'image-bytes'),
            ])),
        ]);

        $client = makeQueuedBlaaizClient(['api_key' => 'test-key'], [
            new Client(['handler' => HandlerStack::create(new MockHandler([]))]),
            $downloadClient,
        ]);

        $result = $client->downloadFile('https://example.com/image.jpg');

        expect($result)->toBe([
            'content' => 'image-bytes',
            'content_type' => 'image/jpeg',
            'filename' => 'passport.jpg',
        ]);
    });

    it('extracts filename from URL when not in headers', function () {
        $downloadClient = new Client([
            'handler' => HandlerStack::create(new MockHandler([
                new Response(200, ['Content-Type' => 'image/jpeg'], 'image-bytes'),
            ])),
        ]);

        $client = makeQueuedBlaaizClient(['api_key' => 'test-key'], [
            new Client(['handler' => HandlerStack::create(new MockHandler([]))]),
            $downloadClient,
        ]);

        $result = $client->downloadFile('https://example.com/documents/passport.jpg');

        expect($result['filename'])->toBe('passport.jpg');
    });

    it('adds extension when filename has none and content type is known', function () {
        $downloadClient = new Client([
            'handler' => HandlerStack::create(new MockHandler([
                new Response(200, ['Content-Type' => 'application/pdf'], 'pdf-bytes'),
            ])),
        ]);

        $client = makeQueuedBlaaizClient(['api_key' => 'test-key'], [
            new Client(['handler' => HandlerStack::create(new MockHandler([]))]),
            $downloadClient,
        ]);

        $result = $client->downloadFile('https://example.com/download');

        expect($result['filename'])->toBe('download.pdf');
    });

    it('handles download errors', function () {
        $downloadClient = new Client([
            'handler' => HandlerStack::create(new MockHandler([
                new RequestException(
                    'Not found',
                    new Request('GET', 'https://example.com/file'),
                    new Response(404, [], 'missing')
                ),
            ])),
        ]);

        $client = makeQueuedBlaaizClient(['api_key' => 'test-key'], [
            new Client(['handler' => HandlerStack::create(new MockHandler([]))]),
            $downloadClient,
        ]);

        expect(fn() => $client->downloadFile('https://example.com/file'))
            ->toThrow(BlaaizException::class, 'File download failed: Not found');
    });

    it('throws when download returns a non-success status without request exceptions', function () {
        $downloadClient = new Client([
            'handler' => HandlerStack::create(new MockHandler([
                new Response(500, ['Content-Type' => 'text/plain'], 'server-error'),
            ])),
            'http_errors' => false,
        ]);

        $client = makeQueuedBlaaizClient(['api_key' => 'test-key'], [
            new Client(['handler' => HandlerStack::create(new MockHandler([]))]),
            $downloadClient,
        ]);

        expect(fn() => $client->downloadFile('https://example.com/file'))
            ->toThrow(BlaaizException::class, 'Failed to download file: HTTP 500');
    });

    it('handles network errors', function () {
        $handler = static function (): never {
            throw new class('DNS failed') extends RuntimeException implements GuzzleException {};
        };

        $downloadClient = new Client(['handler' => $handler]);

        $client = makeQueuedBlaaizClient(['api_key' => 'test-key'], [
            new Client(['handler' => HandlerStack::create(new MockHandler([]))]),
            $downloadClient,
        ]);

        expect(fn() => $client->downloadFile('https://example.com/file'))
            ->toThrow(BlaaizException::class, 'File download failed: DNS failed');
    });
});

describe('BlaaizClient private methods', function () {
    it('maps content types to extensions correctly', function () {
        $client = new BlaaizClient(['api_key' => 'test-key']);
        $reflection = new ReflectionClass($client);
        $method = $reflection->getMethod('getExtensionFromContentType');
        $method->setAccessible(true);

        expect($method->invoke($client, 'image/jpeg'))->toBe('.jpg');
        expect($method->invoke($client, 'image/png'))->toBe('.png');
        expect($method->invoke($client, 'application/pdf'))->toBe('.pdf');
        expect($method->invoke($client, 'text/plain'))->toBe('.txt');
        expect($method->invoke($client, 'unknown/type'))->toBeNull();
    });

    it('handles content type with charset', function () {
        $client = new BlaaizClient(['api_key' => 'test-key']);
        $reflection = new ReflectionClass($client);
        $method = $reflection->getMethod('getExtensionFromContentType');
        $method->setAccessible(true);

        expect($method->invoke($client, 'text/plain; charset=utf-8'))->toBe('.txt');
        expect($method->invoke($client, 'image/jpeg; charset=binary'))->toBe('.jpg');
    });
});
