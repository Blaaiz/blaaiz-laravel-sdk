<?php

namespace Blaaiz\LaravelSdk\Services;

use Blaaiz\LaravelSdk\Exceptions\BlaaizException;

class CustomerService extends BaseService
{
    public function create(array $customerData): array
    {
        $this->validateRequiredFields($customerData, [
            'first_name', 'last_name', 'type', 'email', 'country', 'id_type', 'id_number'
        ]);

        if ($customerData['type'] === 'business' && empty($customerData['business_name'])) {
            throw new BlaaizException('business_name is required when type is business');
        }

        return $this->client->makeRequest('POST', '/api/external/customer', $customerData);
    }

    public function list(): array
    {
        return $this->client->makeRequest('GET', '/api/external/customer');
    }

    public function get(string $customerId): array
    {
        if (empty($customerId)) {
            throw new BlaaizException('Customer ID is required');
        }

        return $this->client->makeRequest('GET', "/api/external/customer/{$customerId}");
    }

    public function update(string $customerId, array $updateData): array
    {
        if (empty($customerId)) {
            throw new BlaaizException('Customer ID is required');
        }

        return $this->client->makeRequest('PUT', "/api/external/customer/{$customerId}", $updateData);
    }

    public function addKyc(string $customerId, array $kycData): array
    {
        if (empty($customerId)) {
            throw new BlaaizException('Customer ID is required');
        }

        return $this->client->makeRequest('POST', "/api/external/customer/{$customerId}/kyc-data", $kycData);
    }

    public function uploadFiles(string $customerId, array $fileData): array
    {
        if (empty($customerId)) {
            throw new BlaaizException('Customer ID is required');
        }

        return $this->client->makeRequest('PUT', "/api/external/customer/{$customerId}/files", $fileData);
    }

    public function uploadFileComplete(string $customerId, array $fileOptions): array
    {
        if (empty($customerId)) {
            throw new BlaaizException('Customer ID is required');
        }

        if (empty($fileOptions)) {
            throw new BlaaizException('File options are required');
        }

        $file = $fileOptions['file'] ?? null;
        $fileCategory = $fileOptions['file_category'] ?? null;
        $filename = $fileOptions['filename'] ?? null;
        $contentType = $fileOptions['content_type'] ?? null;

        if (!$file) {
            throw new BlaaizException('File is required');
        }

        if (!$fileCategory) {
            throw new BlaaizException('file_category is required');
        }

        if (!in_array($fileCategory, ['identity', 'proof_of_address', 'liveness_check'])) {
            throw new BlaaizException('file_category must be one of: identity, proof_of_address, liveness_check');
        }

        try {
            $presignedResponse = $this->client->makeRequest('POST', '/api/external/file/get-presigned-url', [
                'customer_id' => $customerId,
                'file_category' => $fileCategory,
            ]);

            $presignedUrl = null;
            $fileId = null;

            if (isset($presignedResponse['data']['url']) && isset($presignedResponse['data']['file_id'])) {
                $presignedUrl = $presignedResponse['data']['url'];
                $fileId = $presignedResponse['data']['file_id'];
            } elseif (isset($presignedResponse['data']['data']['url']) && isset($presignedResponse['data']['data']['file_id'])) {
                $presignedUrl = $presignedResponse['data']['data']['url'];
                $fileId = $presignedResponse['data']['data']['file_id'];
            } else {
                throw new BlaaizException("Invalid presigned URL response structure. Expected 'url' and 'file_id' keys. Got: " . json_encode($presignedResponse));
            }

            $fileBuffer = $this->processFileInput($file, $contentType, $filename);

            $this->client->uploadFile($presignedUrl, $fileBuffer['content'], $fileBuffer['content_type'], $fileBuffer['filename']);

            $fileFieldMapping = [
                'identity' => 'id_file',
                'liveness_check' => 'liveness_check_file',
                'proof_of_address' => 'proof_of_address_file',
            ];

            $fileFieldName = $fileFieldMapping[$fileCategory] ?? null;
            if (!$fileFieldName) {
                throw new BlaaizException("Unknown file category: {$fileCategory}");
            }

            $fileAssociation = $this->client->makeRequest('POST', "/api/external/customer/{$customerId}/files", [
                $fileFieldName => $fileId,
            ]);

            return array_merge($fileAssociation, [
                'file_id' => $fileId,
                'presigned_url' => $presignedUrl,
            ]);

        } catch (BlaaizException $e) {
            if (str_contains($e->getMessage(), 'File upload failed:')) {
                throw $e;
            }

            throw new BlaaizException("File upload failed: {$e->getMessage()}", $e->getStatus(), $e->getErrorCode());
        }
    }

    private function processFileInput(mixed $file, ?string &$contentType, ?string &$filename): array
    {
        if (is_string($file)) {
            if (str_starts_with($file, 'data:')) {
                $parts = explode(',', $file);
                $content = base64_decode($parts[1]);

                if (!$contentType && preg_match('/data:([^;]+)/', $file, $matches)) {
                    $contentType = $matches[1];
                }

                return [
                    'content' => $content,
                    'content_type' => $contentType,
                    'filename' => $filename,
                ];
            }

            if (str_starts_with($file, 'http://') || str_starts_with($file, 'https://')) {
                $downloadResult = $this->client->downloadFile($file);

                if (!$contentType && $downloadResult['content_type']) {
                    $contentType = $downloadResult['content_type'];
                }

                if (!$filename && $downloadResult['filename']) {
                    $filename = $downloadResult['filename'];
                }

                return [
                    'content' => $downloadResult['content'],
                    'content_type' => $contentType,
                    'filename' => $filename,
                ];
            }

            $content = base64_decode($file);
        } else {
            $content = $file;
        }

        return [
            'content' => $content,
            'content_type' => $contentType,
            'filename' => $filename,
        ];
    }
}