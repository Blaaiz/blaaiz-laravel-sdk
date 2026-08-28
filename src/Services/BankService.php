<?php

namespace Blaaiz\LaravelSdk\Services;

class BankService extends BaseService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = []): array
    {
        $endpoint = '/api/external/bank';
        $params = [];

        foreach ($filters as $key => $value) {
            if ($value === null) {
                continue;
            }
            $params[$key] = is_bool($value) ? ($value ? 'true' : 'false') : $value;
        }

        $query = http_build_query($params);
        if ($query !== '') {
            $endpoint .= '?'.$query;
        }

        return $this->client->makeRequest('GET', $endpoint);
    }

    public function lookupAccount(array $lookupData): array
    {
        $this->validateRequiredFields($lookupData, ['account_number', 'bank_id']);

        return $this->client->makeRequest('POST', '/api/external/bank/account-lookup', $lookupData);
    }

    public function verifyPayee(array $payeeData): array
    {
        $this->validateRequiredFields($payeeData, ['sort_code', 'account_number', 'account_name']);

        return $this->client->makeRequest('POST', '/api/external/bank/payee-verification', $payeeData);
    }

    public function verifyIban(array $ibanData): array
    {
        $this->validateRequiredFields($ibanData, ['iban']);

        return $this->client->makeRequest('POST', '/api/external/bank/iban-verification', $ibanData);
    }
}
