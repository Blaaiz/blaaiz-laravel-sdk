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

    public function list(?string $walletId = null): array
    {
        $endpoint = '/api/external/virtual-bank-account';
        if ($walletId) {
            $endpoint .= "?wallet_id={$walletId}";
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
}