<?php

use Blaaiz\LaravelSdk\BlaaizClient;
use Blaaiz\LaravelSdk\Services\MomoOperatorService;
use Mockery;

describe('MomoOperatorService', function () {
    beforeEach(function () {
        $this->mockClient = Mockery::mock(BlaaizClient::class);
        $this->service = new MomoOperatorService($this->mockClient);
    });

    afterEach(function () {
        Mockery::close();
    });

    it('calls makeRequest for list', function () {
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('GET', '/api/external/momo-operator')
            ->andReturn(['data' => []]);

        $result = $this->service->list();
        expect($result)->toBe(['data' => []]);
    });

    it('calls makeRequest for list with filters', function () {
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('GET', '/api/external/momo-operator?currency_id=cur1&country_id=5')
            ->andReturn(['data' => []]);

        $result = $this->service->list(['currency_id' => 'cur1', 'country_id' => 5]);
        expect($result)->toBe(['data' => []]);
    });

    it('skips null filters', function () {
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('GET', '/api/external/momo-operator?currency_id=cur1')
            ->andReturn(['data' => []]);

        $result = $this->service->list(['currency_id' => 'cur1', 'country_id' => null]);
        expect($result)->toBe(['data' => []]);
    });
});
