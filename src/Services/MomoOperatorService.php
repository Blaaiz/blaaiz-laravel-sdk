<?php

namespace Blaaiz\LaravelSdk\Services;

class MomoOperatorService extends BaseService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): array
    {
        $endpoint = '/api/external/momo-operator';
        $params = [];

        foreach ($filters as $key => $value) {
            if ($value === null) {
                continue;
            }
            $params[$key] = is_bool($value) ? ($value ? 'true' : 'false') : $value;
        }

        $query = http_build_query($params);
        if ($query !== '') {
            $endpoint .= '?'.$query;
        }

        return $this->client->makeRequest('GET', $endpoint);
    }
}
