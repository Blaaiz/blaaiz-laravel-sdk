<?php

namespace Blaaiz\LaravelSdk\Exceptions;

use Exception;

class BlaaizException extends Exception
{
    protected ?int $status;
    protected ?string $errorCode;

    public function __construct(string $message = "", ?int $status = null, ?string $errorCode = null, Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
        
        $this->status = $status;
        $this->errorCode = $errorCode;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function isClientError(): bool
    {
        return $this->status >= 400 && $this->status < 500;
    }

    public function isServerError(): bool
    {
        return $this->status >= 500;
    }

    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'status' => $this->status,
            'error_code' => $this->errorCode,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];
    }
}