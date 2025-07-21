<?php

namespace Blaaiz\LaravelSdk\Services;

use Blaaiz\LaravelSdk\Exceptions\BlaaizException;

class TransactionService extends BaseService
{
    public function list(array $filters = []): array
    {
        return $this->client->makeRequest('POST', '/api/external/transaction', $filters);
    }

    public function get(string $transactionId): array
    {
        if (empty($transactionId)) {
            throw new BlaaizException('Transaction ID is required');
        }

        return $this->client->makeRequest('GET', "/api/external/transaction/{$transactionId}");
    }
}