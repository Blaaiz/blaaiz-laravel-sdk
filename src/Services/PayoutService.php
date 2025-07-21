<?php

namespace Blaaiz\LaravelSdk\Services;

use Blaaiz\LaravelSdk\Exceptions\BlaaizException;

class PayoutService extends BaseService
{
    public function initiate(array $payoutData): array
    {
        $this->validateRequiredFields($payoutData, [
            'wallet_id', 'method', 'from_amount', 'from_currency_id', 'to_currency_id'
        ]);

        if ($payoutData['method'] === 'bank_transfer' && empty($payoutData['account_number'])) {
            throw new BlaaizException('account_number is required for bank_transfer method');
        }

        if ($payoutData['method'] === 'interac') {
            $interacFields = ['email', 'interac_first_name', 'interac_last_name'];
            $this->validateRequiredFields($payoutData, $interacFields);
        }

        return $this->client->makeRequest('POST', '/api/external/payout', $payoutData);
    }
}