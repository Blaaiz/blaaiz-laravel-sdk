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

    public function simulateInteracWebhook(array $simulateData): array
    {
        return $this->client->makeRequest('POST', '/api/external/mock/simulate-webhook/interac', $simulateData);
    }

    public function verifySignature(string $rawBody, string $signature, string $timestamp, string $secret): bool
    {
        if (empty($rawBody)) {
            throw new BlaaizException('Payload is required for signature verification');
        }

        if (empty($signature)) {
            throw new BlaaizException('Signature is required for signature verification');
        }

        if (empty($secret)) {
            throw new BlaaizException('Webhook secret is required for signature verification');
        }
        if(empty($timestamp)) {
            throw new BlaaizException('Timestamp is required for signature verification');
        }

        $signed = $timestamp . '.' . $rawBody;
        $expected = hash_hmac('sha256', $signed, $secret);

        return hash_equals($expected, strtolower($signature));
    }

    public function constructEvent(string $payload, string $signature, string $timestamp, string $secret): array
    {
        if (!$this->verifySignature($payload, $signature, $timestamp, $secret)) {
            throw new BlaaizException('Invalid webhook signature');
        }

        try {
            $event = json_decode($payload, true);

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