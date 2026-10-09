<?php

namespace Blaaiz\LaravelSdk\Services;

use Blaaiz\LaravelSdk\Exceptions\BlaaizException;

class SignaService extends BaseService
{
    private const BASE_PATH = '/api/external/compliance/kyc/sessions';

    private const REQUIREMENTS = ['DOCUMENTS', 'SELFIE', 'FACE_MATCH', 'PROOF_OF_ADDRESS'];

    private const DOCUMENT_TYPES = [
        'PASSPORT',
        'ID_CARD',
        'DRIVERS',
        'RESIDENCE_PERMIT',
        'UTILITY_BILL',
        'BANK_STATEMENT',
        'SELFIE',
    ];

    private const CONTENT_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'application/pdf',
    ];

    public function createSession(array $sessionData): array
    {
        $this->validateSessionData($sessionData);

        return $this->client->makeRequest('POST', self::BASE_PATH, $sessionData);
    }

    public function listSessions(array $filters = []): array
    {
        $query = [];
        foreach ($filters as $key => $value) {
            if ($value === null) {
                continue;
            }
            $query[$key] = $value;
        }

        return $this->client->makeRequest('GET', self::BASE_PATH, $query ?: null);
    }

    public function getSession(string $sessionId): array
    {
        $this->validateSessionId($sessionId);

        return $this->client->makeRequest('GET', self::BASE_PATH.'/'.rawurlencode($sessionId));
    }

    public function submitSession(string $sessionId): array
    {
        $this->validateSessionId($sessionId);

        return $this->client->makeRequest('POST', self::BASE_PATH.'/'.rawurlencode($sessionId).'/submit');
    }

    public function cancelSession(string $sessionId): array
    {
        $this->validateSessionId($sessionId);

        return $this->client->makeRequest('POST', self::BASE_PATH.'/'.rawurlencode($sessionId).'/cancel');
    }

    public function createDocumentUploadUrl(string $sessionId, array $uploadData): array
    {
        $this->validateSessionId($sessionId);
        $this->validateDocumentUploadUrlData($uploadData);

        return $this->client->makeRequest(
            'POST',
            self::BASE_PATH.'/'.rawurlencode($sessionId).'/documents/upload-url',
            $uploadData
        );
    }

    public function uploadSessionDocument(string $sessionId, array $documentData): array
    {
        $this->validateSessionId($sessionId);
        $this->validateDocumentData($documentData);

        return $this->client->makeRequest(
            'POST',
            self::BASE_PATH.'/'.rawurlencode($sessionId).'/documents',
            $documentData
        );
    }

    public function issueVerificationLink(string $sessionId): array
    {
        $this->validateSessionId($sessionId);

        return $this->client->makeRequest('POST', self::BASE_PATH.'/'.rawurlencode($sessionId).'/verification-link');
    }

    public function issueAccessToken(string $sessionId): array
    {
        $this->validateSessionId($sessionId);

        return $this->client->makeRequest('POST', self::BASE_PATH.'/'.rawurlencode($sessionId).'/access-token');
    }

    public function getSessionApplicantData(string $sessionId): array
    {
        $this->validateSessionId($sessionId);

        return $this->client->makeRequest('GET', self::BASE_PATH.'/'.rawurlencode($sessionId).'/applicant-data');
    }

    public function listSessionDocuments(string $sessionId): array
    {
        $this->validateSessionId($sessionId);

        return $this->client->makeRequest('GET', self::BASE_PATH.'/'.rawurlencode($sessionId).'/documents');
    }

    public function getSessionDocument(string $sessionId, string $documentId): array
    {
        $this->validateSessionId($sessionId);
        $this->validateDocumentId($documentId);

        return $this->client->makeRequest(
            'GET',
            self::BASE_PATH.'/'.rawurlencode($sessionId).'/documents/'.rawurlencode($documentId)
        );
    }

    // Short aliases mirror the create/list/get style used by the other SDK resources.
    public function create(array $sessionData): array
    {
        return $this->createSession($sessionData);
    }

    public function list(array $filters = []): array
    {
        return $this->listSessions($filters);
    }

    public function get(string $sessionId): array
    {
        return $this->getSession($sessionId);
    }

    public function submit(string $sessionId): array
    {
        return $this->submitSession($sessionId);
    }

    public function cancel(string $sessionId): array
    {
        return $this->cancelSession($sessionId);
    }

    public function uploadDocument(string $sessionId, array $documentData): array
    {
        return $this->uploadSessionDocument($sessionId, $documentData);
    }

    private function validateSessionData(array $sessionData): void
    {
        $this->validateRequiredFields($sessionData, ['customer_reference', 'idempotency_key']);

        // Not validateRequiredFields(): empty() treats [] as missing, which would hide the non-empty-array message.
        if (! array_key_exists('requirements', $sessionData) || $sessionData['requirements'] === null || $sessionData['requirements'] === '') {
            throw new BlaaizException('requirements is required');
        }

        if (! is_array($sessionData['requirements']) || $sessionData['requirements'] === []) {
            throw new BlaaizException('requirements must be a non-empty array');
        }

        foreach ($sessionData['requirements'] as $requirement) {
            if (! is_string($requirement) || ! in_array(strtoupper($requirement), self::REQUIREMENTS, true)) {
                throw new BlaaizException('requirements must contain only: '.implode(', ', self::REQUIREMENTS));
            }
        }
    }

    private function validateDocumentUploadUrlData(array $uploadData): void
    {
        $this->validateRequiredFields($uploadData, ['file_name', 'id_doc_type']);

        if (! is_string($uploadData['id_doc_type']) || ! in_array(strtoupper($uploadData['id_doc_type']), self::DOCUMENT_TYPES, true)) {
            throw new BlaaizException('id_doc_type must be one of: '.implode(', ', self::DOCUMENT_TYPES));
        }
    }

    private function validateDocumentData(array $documentData): void
    {
        $this->validateRequiredFields($documentData, ['filename', 'content_type', 'id_doc_type', 'country']);

        if (! is_string($documentData['content_type']) || ! in_array(strtolower($documentData['content_type']), self::CONTENT_TYPES, true)) {
            throw new BlaaizException('content_type must be one of: '.implode(', ', self::CONTENT_TYPES));
        }

        if (! is_string($documentData['id_doc_type']) || ! in_array(strtoupper($documentData['id_doc_type']), self::DOCUMENT_TYPES, true)) {
            throw new BlaaizException('id_doc_type must be one of: '.implode(', ', self::DOCUMENT_TYPES));
        }

        $hasStagedFile = is_string($documentData['file_name'] ?? null) && $documentData['file_name'] !== '';
        $hasInlineContent = is_string($documentData['content_base64'] ?? null) && $documentData['content_base64'] !== '';

        if ($hasStagedFile === $hasInlineContent) {
            throw new BlaaizException('Provide exactly one of file_name or content_base64');
        }
    }

    private function validateSessionId(string $sessionId): void
    {
        if (empty($sessionId)) {
            throw new BlaaizException('Session ID is required');
        }
    }

    private function validateDocumentId(string $documentId): void
    {
        if (empty($documentId)) {
            throw new BlaaizException('Document ID is required');
        }
    }
}
