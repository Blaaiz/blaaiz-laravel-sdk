<?php

namespace Blaaiz\LaravelSdk\Services;

class FileService extends BaseService
{
    public function getPresignedUrl(array $fileData): array
    {
        $this->validateRequiredFields($fileData, ['customer_id', 'file_category']);

        return $this->client->makeRequest('POST', '/api/external/file/get-presigned-url', $fileData);
    }
}