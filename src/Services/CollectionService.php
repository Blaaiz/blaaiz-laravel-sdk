<?php

namespace Blaaiz\LaravelSdk\Services;

class CollectionService extends BaseService
{
    public function initiate(array $collectionData): array
    {
        $this->validateRequiredFields($collectionData, ['method', 'amount', 'wallet_id']);

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
}