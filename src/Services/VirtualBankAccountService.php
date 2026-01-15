<?php

namespace Blaaiz\LaravelSdk\Services;

use Blaaiz\LaravelSdk\Exceptions\BlaaizException;

class VirtualBankAccountService extends BaseService
{
    public function create(array $vbaData): array
    {
        $this->validateRequiredFields($vbaData, ['wallet_id']);

        return $this->client->makeRequest('POST', '/api/external/virtual-bank-account', $vbaData);
    }

    public function list(?string $walletId = null, ?string $customerId = null): array
    {
        $endpoint = '/api/external/virtual-bank-account';
        $params = [];

        if ($walletId) {
            $params['wallet_id'] = $walletId;
        }
        if ($customerId) {
            $params['customer_id'] = $customerId;
        }

        if (!empty($params)) {
            $endpoint .= '?' . http_build_query($params);
        }

        return $this->client->makeRequest('GET', $endpoint);
    }

    public function get(string $vbaId): array
    {
        if (empty($vbaId)) {
            throw new BlaaizException('Virtual bank account ID is required');
        }

        return $this->client->makeRequest('GET', "/api/external/virtual-bank-account/{$vbaId}");
    }

    public function close(string $vbaId, ?string $reason = null): array
    {
        if (empty($vbaId)) {
            throw new BlaaizException('Virtual bank account ID is required');
        }

        $data = [];
        if ($reason !== null) {
            $data['reason'] = $reason;
        }

        return $this->client->makeRequest('POST', "/api/external/virtual-bank-account/{$vbaId}/close", $data);
    }
}