<?php

namespace Blaaiz\LaravelSdk\Services;

class BankService extends BaseService
{
    public function list(): array
    {
        return $this->client->makeRequest('GET', '/api/external/bank');
    }

    public function lookupAccount(array $lookupData): array
    {
        $this->validateRequiredFields($lookupData, ['account_number', 'bank_id']);

        return $this->client->makeRequest('POST', '/api/external/bank/account-lookup', $lookupData);
    }
}