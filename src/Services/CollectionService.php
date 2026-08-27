<?php

namespace Blaaiz\LaravelSdk\Services;

use Blaaiz\LaravelSdk\Exceptions\BlaaizException;

class CollectionService extends BaseService
{
    public function initiate(array $collectionData): array
    {
        $this->validateRequiredFields($collectionData, ['method', 'amount', 'wallet_id']);

        // Card collections need the customer and the card details up front.
        if (($collectionData['method'] ?? null) === 'card') {
            foreach (['customer_id', 'card_holder_name', 'card_number', 'expiry', 'cvc'] as $field) {
                if (empty($collectionData[$field])) {
                    throw new BlaaizException("{$field} is required for card method");
                }
            }
        }

        return $this->client->makeRequest('POST', '/api/external/collection', $collectionData);
    }

    public function initiateCrypto(array $cryptoData): array
    {
        $this->validateRequiredFields($cryptoData, ['amount', 'wallet_id', 'network', 'token']);

        return $this->client->makeRequest('POST', '/api/external/collection/crypto', $cryptoData);
    }

    public function getCryptoNetworks(array $filters = []): array
    {
        $endpoint = '/api/external/collection/crypto/networks';
        $query = $this->buildQuery($filters);

        if ($query !== '') {
            $endpoint .= '?'.$query;
        }

        return $this->client->makeRequest('GET', $endpoint);
    }

    public function attachCustomer(array $attachData): array
    {
        $this->validateRequiredFields($attachData, ['customer_id', 'transaction_id']);

        return $this->client->makeRequest('POST', '/api/external/collection/attach-customer', $attachData);
    }

    public function initiateInteracMoneyRequest(array $interacData): array
    {
        $this->validateRequiredFields($interacData, ['amount', 'email']);

        return $this->client->makeRequest('POST', '/api/external/collection/interac-money-request', $interacData);
    }

    public function acceptInteracMoneyRequest(array $interacData): array
    {
        $this->validateRequiredFields($interacData, ['reference_number']);

        return $this->client->makeRequest('POST', '/api/external/collection/accept-interac-money-request', $interacData);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function buildQuery(array $filters): string
    {
        $params = [];
        foreach ($filters as $key => $value) {
            if ($value === null) {
                continue;
            }
            $params[$key] = is_bool($value) ? ($value ? 'true' : 'false') : $value;
        }

        return http_build_query($params);
    }
}
