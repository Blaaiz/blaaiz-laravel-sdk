<?php

namespace Blaaiz\LaravelSdk\Services;

class SwapService extends BaseService
{
    public function initiate(array $swapData): array
    {
        $this->validateRequiredFields($swapData, [
            'from_business_wallet_id',
            'to_business_wallet_id',
            'amount',
        ]);

        return $this->client->makeRequest('POST', '/api/external/swap', $swapData);
    }
}
