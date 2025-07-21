<?php

namespace Blaaiz\LaravelSdk\Services;

class FeesService extends BaseService
{
    public function getBreakdown(array $feeData): array
    {
        $this->validateRequiredFields($feeData, ['from_currency_id', 'to_currency_id', 'from_amount']);

        return $this->client->makeRequest('POST', '/api/external/fees/breakdown', $feeData);
    }
}