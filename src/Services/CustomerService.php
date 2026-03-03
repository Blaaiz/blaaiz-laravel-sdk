<?php

namespace Blaaiz\LaravelSdk\Services;

use Blaaiz\LaravelSdk\Exceptions\BlaaizException;

class CustomerService extends BaseService
{
    public function create(array $customerData): array
    {
        $this->validateRequiredFields($customerData, [
            'type', 'email', 'country', 'id_type', 'id_number'
        ]);

        if ($customerData['type'] === 'individual') {
            if (empty($customerData['first_name'])) {
                throw new BlaaizException('first_name is required when type is individual');
            }
            if (empty($customerData['last_name'])) {
                throw new BlaaizException('last_name is required when type is individual');
            }
        } elseif ($customerData['type'] === 'business' && empty($customerData['business_name'])) {
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

            // Auto-detect content type if not provided
            if (!$fileBuffer['content_type']) {
                $fileBuffer['content_type'] = $this->detectContentTypeFromBytes($fileBuffer['content']);
            }
            if (!$fileBuffer['content_type'] && $fileBuffer['filename']) {
                $fileBuffer['content_type'] = $this->getContentTypeFromFilename($fileBuffer['filename']);
            }
            if (!$fileBuffer['content_type']) {
                throw new BlaaizException(
                    'Could not determine file content type. Please provide a content_type (e.g., "image/jpeg", "image/png", "application/pdf") in fileOptions.'
                );
            }

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

    public function listBeneficiaries(string $customerId): array
    {
        if (empty($customerId)) {
            throw new BlaaizException('Customer ID is required');
        }

        return $this->client->makeRequest('GET', "/api/external/customer/{$customerId}/beneficiary");
    }

    public function getBeneficiary(string $customerId, string $beneficiaryId): array
    {
        if (empty($customerId)) {
            throw new BlaaizException('Customer ID is required');
        }

        if (empty($beneficiaryId)) {
            throw new BlaaizException('Beneficiary ID is required');
        }

        return $this->client->makeRequest('GET', "/api/external/customer/{$customerId}/beneficiary/{$beneficiaryId}");
    }

    private function detectContentTypeFromBytes(string $content): ?string
    {
        if (strlen($content) < 4) {
            return null;
        }

        $bytes = array_values(unpack('C*', substr($content, 0, 12)));

        // JPEG: FF D8 FF
        if ($bytes[0] === 0xFF && $bytes[1] === 0xD8 && $bytes[2] === 0xFF) {
            return 'image/jpeg';
        }
        // PNG: 89 50 4E 47
        if ($bytes[0] === 0x89 && $bytes[1] === 0x50 && $bytes[2] === 0x4E && $bytes[3] === 0x47) {
            return 'image/png';
        }
        // GIF: 47 49 46 38
        if ($bytes[0] === 0x47 && $bytes[1] === 0x49 && $bytes[2] === 0x46 && $bytes[3] === 0x38) {
            return 'image/gif';
        }
        // PDF: 25 50 44 46 (%PDF)
        if ($bytes[0] === 0x25 && $bytes[1] === 0x50 && $bytes[2] === 0x44 && $bytes[3] === 0x46) {
            return 'application/pdf';
        }
        // WEBP: RIFF....WEBP
        if (count($bytes) >= 12 &&
            $bytes[0] === 0x52 && $bytes[1] === 0x49 && $bytes[2] === 0x46 && $bytes[3] === 0x46 &&
            $bytes[8] === 0x57 && $bytes[9] === 0x45 && $bytes[10] === 0x42 && $bytes[11] === 0x50) {
            return 'image/webp';
        }
        // BMP: 42 4D
        if ($bytes[0] === 0x42 && $bytes[1] === 0x4D) {
            return 'image/bmp';
        }
        // TIFF: 49 49 2A 00 (little-endian) or 4D 4D 00 2A (big-endian)
        if (($bytes[0] === 0x49 && $bytes[1] === 0x49 && $bytes[2] === 0x2A && $bytes[3] === 0x00) ||
            ($bytes[0] === 0x4D && $bytes[1] === 0x4D && $bytes[2] === 0x00 && $bytes[3] === 0x2A)) {
            return 'image/tiff';
        }

        return null;
    }

    private function getContentTypeFromFilename(string $filename): ?string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        $extToMime = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'bmp' => 'image/bmp',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];

        return $extToMime[$ext] ?? null;
    }

    private function processFileInput(mixed $file, ?string &$contentType, ?string &$filename): array
    {
        if (is_string($file)) {
            if (str_starts_with($file, 'data:')) {
                $parts = explode(',', $file);
                $base64Part = $parts[1] ?? '';
                if ($base64Part === '') {
                    throw new BlaaizException('Invalid data URL: no base64 data found after the comma');
                }
                $content = base64_decode($base64Part, true);
                if ($content === false) {
                    throw new BlaaizException(
                        'The base64 portion of the data URL does not appear to be valid base64. ' .
                        'Ensure the string after the comma contains only valid base64 characters.'
                    );
                }

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

            $content = base64_decode($file, true);
            if ($content === false) {
                throw new BlaaizException(
                    'The file string does not appear to be valid base64. ' .
                    'If you meant to pass a file path or URL, use the appropriate format instead.'
                );
            }
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