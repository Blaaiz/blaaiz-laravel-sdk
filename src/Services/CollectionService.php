<?php

namespace Blaaiz\LaravelSdk\Services;

use Blaaiz\LaravelSdk\Exceptions\BlaaizException;

class CollectionService extends BaseService
{
    public function initiate(array $collectionData): array
    {
        $this->validateRequiredFields($collectionData, ['customer_id', 'wallet_id', 'amount', 'currency', 'method']);

        return $this->client->makeRequest('POST', '/api/external/collection', $collectionData);
    }

    public function initiateCrypto(array $cryptoData): array
    {
        return $this->client->makeRequest('POST', '/api/external/collection/crypto', $cryptoData);
    }

    public function attachCustomer(array $attachData): array
    {
        $this->validateRequiredFields($attachData, ['customer_id', 'transaction_id']);

        return $this->client->makeRequest('POST', '/api/external/collection/attach-customer', $attachData);
    }

    public function getCryptoNetworks(): array
    {
        return $this->client->makeRequest('GET', '/api/external/collection/crypto/networks');
    }

    public function acceptInteracMoneyRequest(array $interacData): array
    {
        $this->validateRequiredFields($interacData, ['reference_number']);

        return $this->client->makeRequest('POST', '/api/external/collection/accept-interac-money-request', $interacData);
    }
}