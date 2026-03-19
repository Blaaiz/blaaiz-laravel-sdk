<?php

namespace Blaaiz\LaravelSdk\Services;

class RateService extends BaseService
{
    public function list(?string $searchTerm = null): array
    {
        $params = [];

        if ($searchTerm !== null) {
            $params['search_term'] = $searchTerm;
        }

        return $this->client->makeRequest('GET', '/api/external/rate', $params ?: null);
    }
}
