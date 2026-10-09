<?php

namespace Blaaiz\LaravelSdk\Services;

use Blaaiz\LaravelSdk\Exceptions\BlaaizException;

class SignaIdService extends BaseService
{
    private const BASE_PATH = '/api/external/signa-id';

    private const RELEASE_SCOPES = ['identity', 'id_document', 'address', 'document_images'];

    public function createReleaseRequest(array $requestData): array
    {
        $this->validateReleaseRequestData($requestData);

        // A gapped array, for example from array_unique(), encodes as a JSON object and the API wants a list.
        $requestData['scopes'] = array_values($requestData['scopes']);

        return $this->client->makeRequest('POST', self::BASE_PATH.'/release-requests', $requestData);
    }

    public function exchangeReleaseCode(string $code): array
    {
        if ($code === '') {
            throw new BlaaizException('Release code is required');
        }

        return $this->client->makeRequest('POST', self::BASE_PATH.'/releases/exchange', ['code' => $code]);
    }

    public function getRelease(string $releaseId): array
    {
        $this->validateReleaseId($releaseId);

        return $this->client->makeRequest('GET', self::BASE_PATH.'/releases/'.rawurlencode($releaseId));
    }

    public function getReleaseDocument(string $releaseId, string $documentId): array
    {
        $this->validateReleaseId($releaseId);

        if ($documentId === '') {
            throw new BlaaizException('Document ID is required');
        }

        return $this->client->makeRequest(
            'GET',
            self::BASE_PATH.'/releases/'.rawurlencode($releaseId).'/documents/'.rawurlencode($documentId)
        );
    }

    public function getWalletStatus(string $address, ?int $chainId = null): array
    {
        if ($address === '') {
            throw new BlaaizException('Wallet address is required');
        }

        $path = '/api/v1/signa-id/public/wallets/'.rawurlencode($address).'/status';

        if ($chainId !== null) {
            $path .= '?chain_id='.$chainId;
        }

        return $this->client->makeRequest('GET', $path);
    }

    private function validateReleaseRequestData(array $requestData): void
    {
        foreach (['idempotency_key', 'purpose', 'scopes', 'origin'] as $field) {
            // Not validateRequiredFields(): empty() treats [] as missing, which would hide the non-empty-array message.
            if (! array_key_exists($field, $requestData) || $requestData[$field] === null || $requestData[$field] === '') {
                throw new BlaaizException("{$field} is required");
            }
        }

        if (! is_array($requestData['scopes']) || $requestData['scopes'] === []) {
            throw new BlaaizException('scopes must be a non-empty array');
        }

        foreach ($requestData['scopes'] as $scope) {
            if (! is_string($scope) || ! in_array($scope, self::RELEASE_SCOPES, true)) {
                throw new BlaaizException('scopes must contain only: '.implode(', ', self::RELEASE_SCOPES));
            }
        }
    }

    private function validateReleaseId(string $releaseId): void
    {
        if ($releaseId === '') {
            throw new BlaaizException('Release ID is required');
        }
    }
}
