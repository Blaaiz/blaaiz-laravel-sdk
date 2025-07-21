<?php

namespace Blaaiz\LaravelSdk\Services;

use Blaaiz\LaravelSdk\Exceptions\BlaaizException;

class WebhookService extends BaseService
{
    public function register(array $webhookData): array
    {
        $this->validateRequiredFields($webhookData, ['collection_url', 'payout_url']);

        return $this->client->makeRequest('POST', '/api/external/webhook', $webhookData);
    }

    public function get(): array
    {
        return $this->client->makeRequest('GET', '/api/external/webhook');
    }

    public function update(array $webhookData): array
    {
        return $this->client->makeRequest('PUT', '/api/external/webhook', $webhookData);
    }

    public function replay(array $replayData): array
    {
        $this->validateRequiredFields($replayData, ['transaction_id']);

        return $this->client->makeRequest('POST', '/api/external/webhook/replay', $replayData);
    }

    public function verifySignature(mixed $payload, string $signature, string $secret): bool
    {
        if (empty($payload)) {
            throw new BlaaizException('Payload is required for signature verification');
        }

        if (empty($signature)) {
            throw new BlaaizException('Signature is required for signature verification');
        }

        if (empty($secret)) {
            throw new BlaaizException('Webhook secret is required for signature verification');
        }

        $payloadString = is_string($payload) ? $payload : json_encode($payload);
        if ($payloadString === false) {
            throw new BlaaizException('Failed to encode payload to JSON');
        }
        $cleanSignature = str_replace('sha256=', '', $signature);
        $expectedSignature = hash_hmac('sha256', $payloadString, $secret);

        return hash_equals($expectedSignature, $cleanSignature);
    }

    public function constructEvent(mixed $payload, string $signature, string $secret): array
    {
        if (!$this->verifySignature($payload, $signature, $secret)) {
            throw new BlaaizException('Invalid webhook signature');
        }

        try {
            $event = is_string($payload) ? json_decode($payload, true) : $payload;

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new BlaaizException('Invalid webhook payload: unable to parse JSON');
            }

            return array_merge($event, [
                'verified' => true,
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\JsonException $e) {
            throw new BlaaizException('Invalid webhook payload: unable to parse JSON');
        }
    }
}