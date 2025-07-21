<?php

namespace Blaaiz\LaravelSdk\Tests;

use Blaaiz\LaravelSdk\BlaaizServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Get package providers.
     */
    protected function getPackageProviders($app): array
    {
        return [
            BlaaizServiceProvider::class,
        ];
    }

    /**
     * Get package aliases.
     */
    protected function getPackageAliases($app): array
    {
        return [
            'Blaaiz' => \Blaaiz\LaravelSdk\Facades\Blaaiz::class,
        ];
    }

    /**
     * Define environment setup.
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('blaaiz.api_key', env('BLAAIZ_API_KEY', 'test-key'));
        $app['config']->set('blaaiz.base_url', env('BLAAIZ_API_URL', 'https://api-dev.blaaiz.com'));
        $app['config']->set('blaaiz.timeout', 30);
    }

    /**
     * Create a mock HTTP response
     */
    protected function mockResponse(int $status = 200, array $data = [], array $headers = []): \GuzzleHttp\Psr7\Response
    {
        return new \GuzzleHttp\Psr7\Response(
            $status,
            array_merge(['Content-Type' => 'application/json'], $headers),
            json_encode($data)
        );
    }

    /**
     * Create a mock Guzzle client
     */
    protected function mockGuzzleClient(array $responses = []): \GuzzleHttp\Client
    {
        $mock = new \GuzzleHttp\Handler\MockHandler($responses);
        $handlerStack = \GuzzleHttp\HandlerStack::create($mock);
        
        return new \GuzzleHttp\Client(['handler' => $handlerStack]);
    }
}