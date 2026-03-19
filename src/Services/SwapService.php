<?php

namespace Blaaiz\LaravelSdk\Services;

class SwapService extends BaseService
{
    public function swap(array $data): array
    {
        $this->validateRequiredFields($data, [
            'from_business_wallet_id',
            'to_business_wallet_id',
            'amount',
        ]);

        return $this->client->makeRequest('POST', '/api/external/swap', $data);
    }
}
