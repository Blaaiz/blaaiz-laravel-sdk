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

    /**
     * @deprecated Use initiate(). Kept for backward compatibility; it will be removed in a future major version.
     */
    public function swap(array $swapData): array
    {
        return $this->initiate($swapData);
    }
}
