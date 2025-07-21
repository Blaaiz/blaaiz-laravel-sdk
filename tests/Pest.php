<?php

use Blaaiz\LaravelSdk\Tests\TestCase;

uses(TestCase::class)->in('Feature');
uses(TestCase::class)->in('Unit');
uses(TestCase::class)->in('Integration');

/**
 * Helper function to skip tests if API key is not set
 */
function skipIfNoApiKey(): void
{
    if (! env('BLAAIZ_API_KEY')) {
        test()->markTestSkipped('BLAAIZ_API_KEY not set, skipping integration tests');
    }
}

/**
 * Helper function to create a test HTTP server
 */
function createTestServer(callable $handler): array
{
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    socket_bind($socket, '127.0.0.1', 0);
    socket_getsockname($socket, $address, $port);
    socket_close($socket);
    
    $server = new \React\Socket\Server("127.0.0.1:$port");
    $http = new \React\Http\Server(function (\Psr\Http\Message\ServerRequestInterface $request) use ($handler) {
        return $handler($request);
    });
    $http->listen($server);
    
    return [$server, $port];
}