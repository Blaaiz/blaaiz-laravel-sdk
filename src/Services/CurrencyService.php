<?php

namespace Blaaiz\LaravelSdk\Services;

class CurrencyService extends BaseService
{
    public function list(): array
    {
        return $this->client->makeRequest('GET', '/api/external/currency');
    }
}