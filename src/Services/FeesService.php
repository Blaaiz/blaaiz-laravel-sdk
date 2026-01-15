<?php

namespace Blaaiz\LaravelSdk\Services;

use Blaaiz\LaravelSdk\Exceptions\BlaaizException;

class FeesService extends BaseService
{
    public function getBreakdown(array $feeData): array
    {
        $this->validateRequiredFields($feeData, ['from_currency_id', 'to_currency_id']);

        // Either from_amount or to_amount must be provided
        if (empty($feeData['from_amount']) && empty($feeData['to_amount'])) {
            throw new BlaaizException('Either from_amount or to_amount is required');
        }

        return $this->client->makeRequest('POST', '/api/external/fees/breakdown', $feeData);
    }
}