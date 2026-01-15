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

    public function getIdentificationType(?string $customerId = null, ?string $country = null, ?string $type = null): array
    {
        if (empty($customerId) && (empty($country) || empty($type))) {
            throw new BlaaizException('Either customer_id or both country and type are required');
        }

        $endpoint = '/api/external/virtual-bank-account/identification-type';
        $params = [];

        if ($customerId) {
            $params['customer_id'] = $customerId;
        } else {
            $params['country'] = $country;
            $params['type'] = $type;
        }

        $endpoint .= '?' . http_build_query($params);

        return $this->client->makeRequest('GET', $endpoint);
    }
}