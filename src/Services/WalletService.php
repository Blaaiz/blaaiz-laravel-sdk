<?php

namespace Blaaiz\LaravelSdk\Services;

use Blaaiz\LaravelSdk\Exceptions\BlaaizException;

class WalletService extends BaseService
{
    public function list(): array
    {
        return $this->client->makeRequest('GET', '/api/external/wallet');
    }

    public function get(string $walletId): array
    {
        if (empty($walletId)) {
            throw new BlaaizException('Wallet ID is required');
        }

        return $this->client->makeRequest('GET', "/api/external/wallet/{$walletId}");
    }
}