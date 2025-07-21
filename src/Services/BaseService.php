<?php

namespace Blaaiz\LaravelSdk\Services;

use Blaaiz\LaravelSdk\BlaaizClient;
use Blaaiz\LaravelSdk\Exceptions\BlaaizException;

abstract class BaseService
{
    protected BlaaizClient $client;

    public function __construct(BlaaizClient $client)
    {
        $this->client = $client;
    }

    protected function validateRequiredFields(array $data, array $requiredFields): void
    {
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new BlaaizException("{$field} is required");
            }
        }
    }
}