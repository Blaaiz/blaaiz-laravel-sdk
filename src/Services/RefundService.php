<?php

namespace Blaaiz\LaravelSdk\Services;

use Blaaiz\LaravelSdk\Exceptions\BlaaizException;

class RefundService extends BaseService
{
    public function initiate(array $refundData): array
    {
        $this->validateRequiredFields($refundData, ['transaction_id']);

        return $this->client->makeRequest('POST', '/api/external/refund', $refundData);
    }

    public function get(string $refundId): array
    {
        if (empty($refundId)) {
            throw new BlaaizException('Refund ID is required');
        }

        return $this->client->makeRequest('GET', "/api/external/refund/{$refundId}");
    }
}
