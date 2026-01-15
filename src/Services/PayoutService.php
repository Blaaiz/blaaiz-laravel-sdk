<?php

namespace Blaaiz\LaravelSdk\Services;

use Blaaiz\LaravelSdk\Exceptions\BlaaizException;

class PayoutService extends BaseService
{
    public function initiate(array $payoutData): array
    {
        $this->validateRequiredFields($payoutData, [
            'wallet_id', 'customer_id', 'method', 'from_currency_id', 'to_currency_id'
        ]);

        // Either from_amount or to_amount must be provided
        if (empty($payoutData['from_amount']) && empty($payoutData['to_amount'])) {
            throw new BlaaizException('Either from_amount or to_amount is required');
        }

        $method = $payoutData['method'];
        $toCurrency = $payoutData['to_currency_id'] ?? null;

        // Method-specific validations
        if ($method === 'bank_transfer') {
            $this->validateBankTransferFields($payoutData, $toCurrency);
        } elseif ($method === 'interac') {
            $this->validateRequiredFields($payoutData, ['email', 'interac_first_name', 'interac_last_name']);
        } elseif (in_array($method, ['ach', 'wire'])) {
            $this->validateAchWireFields($payoutData, $method);
        } elseif ($method === 'crypto') {
            $this->validateRequiredFields($payoutData, ['wallet_address', 'wallet_token', 'wallet_network']);
        }

        return $this->client->makeRequest('POST', '/api/external/payout', $payoutData);
    }

    private function validateBankTransferFields(array $payoutData, ?string $toCurrency): void
    {
        // NGN bank transfers require bank_id and account_number
        if ($toCurrency === 'NGN') {
            $this->validateRequiredFields($payoutData, ['bank_id', 'account_number']);
        }
        // GBP bank transfers require sort_code and account_number
        elseif ($toCurrency === 'GBP') {
            $this->validateRequiredFields($payoutData, ['sort_code', 'account_number', 'account_name']);
        }
        // EUR bank transfers require IBAN and BIC code
        elseif ($toCurrency === 'EUR') {
            $this->validateRequiredFields($payoutData, ['iban', 'bic_code', 'account_name']);
        }
    }

    private function validateAchWireFields(array $payoutData, string $method): void
    {
        $this->validateRequiredFields($payoutData, ['type', 'account_number', 'account_name', 'account_type', 'bank_name', 'routing_number']);

        if ($method === 'wire') {
            $this->validateRequiredFields($payoutData, ['swift_code']);
        }
    }
}