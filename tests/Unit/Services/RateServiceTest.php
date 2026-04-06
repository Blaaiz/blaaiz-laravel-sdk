<?php

use Blaaiz\LaravelSdk\BlaaizClient;
use Blaaiz\LaravelSdk\Services\RateService;
use Mockery;

describe('RateService', function () {
    beforeEach(function () {
        $this->mockClient = Mockery::mock(BlaaizClient::class);
        $this->service = new RateService($this->mockClient);
    });

    afterEach(function () {
        Mockery::close();
    });

    it('calls makeRequest for list without search term', function () {
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('GET', '/api/external/rate', null)
            ->andReturn(['data' => ['data' => []]]);

        $result = $this->service->list();
        expect($result)->toBe(['data' => ['data' => []]]);
    });

    it('calls makeRequest for list with search term', function () {
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('GET', '/api/external/rate', ['search_term' => 'USD'])
            ->andReturn(['data' => ['data' => [['pair' => 'USD/NGN', 'value' => '1600']]]]);

        $result = $this->service->list('USD');
        expect($result)->toBe(['data' => ['data' => [['pair' => 'USD/NGN', 'value' => '1600']]]]);
    });
});
