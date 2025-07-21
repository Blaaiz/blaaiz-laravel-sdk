<?php

use Blaaiz\LaravelSdk\Exceptions\BlaaizException;

describe('BlaaizException', function () {
    it('creates exception with message only', function () {
        $exception = new BlaaizException('Test error message');

        expect($exception->getMessage())->toBe('Test error message');
        expect($exception->getStatus())->toBeNull();
        expect($exception->getErrorCode())->toBeNull();
    });

    it('creates exception with message and status', function () {
        $exception = new BlaaizException('API error', 400);

        expect($exception->getMessage())->toBe('API error');
        expect($exception->getStatus())->toBe(400);
        expect($exception->getErrorCode())->toBeNull();
    });

    it('creates exception with message, status, and error code', function () {
        $exception = new BlaaizException('Validation failed', 422, 'VALIDATION_ERROR');

        expect($exception->getMessage())->toBe('Validation failed');
        expect($exception->getStatus())->toBe(422);
        expect($exception->getErrorCode())->toBe('VALIDATION_ERROR');
    });

    it('extends standard Exception class', function () {
        $exception = new BlaaizException('Test');

        expect($exception)->toBeInstanceOf(\Exception::class);
        expect($exception)->toBeInstanceOf(BlaaizException::class);
    });

    it('has helper methods for HTTP status checking', function () {
        $clientError = new BlaaizException('Client error', 400);
        $serverError = new BlaaizException('Server error', 500);
        $noStatus = new BlaaizException('No status');

        expect($clientError->isClientError())->toBeTrue();
        expect($clientError->isServerError())->toBeFalse();

        expect($serverError->isServerError())->toBeTrue();
        expect($serverError->isClientError())->toBeFalse();

        expect($noStatus->isClientError())->toBeFalse();
        expect($noStatus->isServerError())->toBeFalse();
    });

    it('converts to array representation', function () {
        $exception = new BlaaizException('Test error', 400, 'TEST_ERROR');

        $array = $exception->toArray();

        expect($array)->toBe([
            'message' => 'Test error',
            'status' => 400,
            'error_code' => 'TEST_ERROR'
        ]);
    });

    it('converts to JSON representation', function () {
        $exception = new BlaaizException('JSON error', 500, 'JSON_ERROR');

        $json = $exception->toJson();
        $decoded = json_decode($json, true);

        expect($decoded)->toBe([
            'message' => 'JSON error',
            'status' => 500,
            'error_code' => 'JSON_ERROR'
        ]);
    });

    it('handles null values in array conversion', function () {
        $exception = new BlaaizException('Test');

        $array = $exception->toArray();

        expect($array)->toBe([
            'message' => 'Test',
            'status' => null,
            'error_code' => null
        ]);
    });

});