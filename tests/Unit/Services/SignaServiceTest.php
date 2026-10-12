<?php

use Blaaiz\LaravelSdk\BlaaizClient;
use Blaaiz\LaravelSdk\Exceptions\BlaaizException;
use Blaaiz\LaravelSdk\Services\SignaService;

describe('SignaService', function () {
    beforeEach(function () {
        $this->mockClient = Mockery::mock(BlaaizClient::class);
        $this->service = new SignaService($this->mockClient);
    });

    afterEach(function () {
        Mockery::close();
    });

    it('creates a session with the Signa endpoint', function () {
        $data = [
            'customer_reference' => 'customer-123',
            'idempotency_key' => 'request-123',
            'requirements' => ['DOCUMENTS', 'SELFIE'],
            'fulfilment_mode' => 'HOSTED',
            'applicant' => ['first_name' => 'Ada', 'country' => 'GBR'],
        ];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/compliance/kyc/sessions', $data)
            ->andReturn(['data' => ['id' => 'session-1']]);

        $result = $this->service->createSession($data);
        expect($result)->toBe(['data' => ['id' => 'session-1']]);
    });

    it('lists sessions with limit and offset filters', function () {
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('GET', '/api/external/compliance/kyc/sessions', ['limit' => 25, 'offset' => 50])
            ->andReturn(['data' => ['sessions' => []]]);

        $result = $this->service->listSessions(['limit' => 25, 'offset' => 50]);
        expect($result)->toBe(['data' => ['sessions' => []]]);
    });

    it('lists sessions with no filters producing no query string', function () {
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('GET', '/api/external/compliance/kyc/sessions', null)
            ->andReturn(['data' => ['sessions' => []]]);

        $result = $this->service->listSessions();
        expect($result)->toBe(['data' => ['sessions' => []]]);
    });

    it('drops null filters when listing sessions', function () {
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('GET', '/api/external/compliance/kyc/sessions', ['limit' => 10])
            ->andReturn(['data' => ['sessions' => []]]);

        $result = $this->service->listSessions(['limit' => 10, 'offset' => null]);
        expect($result)->toBe(['data' => ['sessions' => []]]);
    });

    it('gets, submits, and cancels a session with an id that needs encoding', function () {
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('GET', '/api/external/compliance/kyc/sessions/session%2F123')
            ->andReturn(['data' => ['id' => 'session/123']]);
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/compliance/kyc/sessions/session%2F123/submit')
            ->andReturn(['data' => ['id' => 'session/123']]);
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/compliance/kyc/sessions/session%2F123/cancel')
            ->andReturn(['data' => ['id' => 'session/123']]);

        $this->service->getSession('session/123');
        $this->service->submitSession('session/123');
        $this->service->cancelSession('session/123');
    });

    it('creates a document upload URL', function () {
        $data = ['file_name' => 'passport.jpg', 'id_doc_type' => 'PASSPORT'];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/compliance/kyc/sessions/session-123/documents/upload-url', $data)
            ->andReturn(['data' => ['url' => 'https://s3.example.com', 'file_name' => 'x_passport.jpg', 'headers' => []]]);

        $result = $this->service->createDocumentUploadUrl('session-123', $data);
        expect($result)->toBe(['data' => ['url' => 'https://s3.example.com', 'file_name' => 'x_passport.jpg', 'headers' => []]]);
    });

    it('uploads an inline session document', function () {
        $data = [
            'filename' => 'passport.jpg',
            'content_type' => 'image/jpeg',
            'id_doc_type' => 'PASSPORT',
            'country' => 'GBR',
            'content_base64' => 'aGVsbG8=',
        ];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/compliance/kyc/sessions/session-123/documents', $data)
            ->andReturn(['data' => ['id' => 'session-123']]);

        $result = $this->service->uploadSessionDocument('session-123', $data);
        expect($result)->toBe(['data' => ['id' => 'session-123']]);
    });

    it('uploads a staged session document via the uploadDocument alias', function () {
        $data = [
            'filename' => 'passport.jpg',
            'content_type' => 'image/jpeg',
            'id_doc_type' => 'PASSPORT',
            'country' => 'GBR',
            'file_name' => 'a1b2c3_passport.jpg',
        ];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/compliance/kyc/sessions/session-123/documents', $data)
            ->andReturn(['data' => ['id' => 'session-123']]);

        $result = $this->service->uploadDocument('session-123', $data);
        expect($result)->toBe(['data' => ['id' => 'session-123']]);
    });

    it('issues the hosted verification link exposed by the API', function () {
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/compliance/kyc/sessions/session-123/verification-link')
            ->andReturn(['data' => ['verification_link' => 'https://kyc.example.com/session-123']]);

        $result = $this->service->issueVerificationLink('session-123');
        expect($result)->toBe(['data' => ['verification_link' => 'https://kyc.example.com/session-123']]);
    });

    it('issues a web SDK access token with an id that needs encoding', function () {
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/compliance/kyc/sessions/session%2F123/access-token')
            ->andReturn(['data' => ['access_token' => 'token']]);

        $result = $this->service->issueAccessToken('session/123');
        expect($result)->toBe(['data' => ['access_token' => 'token']]);
    });

    it('validates the session id for issueAccessToken and makes no HTTP call', function () {
        $this->mockClient->shouldNotReceive('makeRequest');

        expect(fn () => $this->service->issueAccessToken(''))
            ->toThrow(BlaaizException::class, 'Session ID is required');
    });

    it('sends redirect_url on createSession', function () {
        $data = [
            'customer_reference' => 'customer-123',
            'idempotency_key' => 'request-123',
            'requirements' => ['DOCUMENTS', 'SELFIE', 'FACE_MATCH'],
            'redirect_url' => 'https://shop.example/kyc/done',
        ];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/compliance/kyc/sessions', $data)
            ->andReturn(['data' => ['id' => 'session-1']]);

        $this->service->createSession($data);
    });

    describe('PII reads', function () {
        it('gets applicant data with the Signa endpoint', function () {
            $this->mockClient
                ->shouldReceive('makeRequest')
                ->once()
                ->with('GET', '/api/external/compliance/kyc/sessions/session-123/applicant-data')
                ->andReturn(['data' => null]);

            $result = $this->service->getSessionApplicantData('session-123');
            expect($result)->toBe(['data' => null]);
        });

        it('lists session documents with the Signa endpoint', function () {
            $this->mockClient
                ->shouldReceive('makeRequest')
                ->once()
                ->with('GET', '/api/external/compliance/kyc/sessions/session-123/documents')
                ->andReturn(['data' => ['documents' => []]]);

            $result = $this->service->listSessionDocuments('session-123');
            expect($result)->toBe(['data' => ['documents' => []]]);
        });

        it('gets a session document with the Signa endpoint', function () {
            $this->mockClient
                ->shouldReceive('makeRequest')
                ->once()
                ->with('GET', '/api/external/compliance/kyc/sessions/session-123/documents/doc-456')
                ->andReturn(['data' => ['url' => 'https://s3.example.com/doc-456']]);

            $result = $this->service->getSessionDocument('session-123', 'doc-456');
            expect($result)->toBe(['data' => ['url' => 'https://s3.example.com/doc-456']]);
        });

        it('encodes a session id and document id that need encoding', function () {
            $this->mockClient
                ->shouldReceive('makeRequest')
                ->once()
                ->with('GET', '/api/external/compliance/kyc/sessions/session%2F123/applicant-data')
                ->andReturn(['data' => null]);
            $this->mockClient
                ->shouldReceive('makeRequest')
                ->once()
                ->with('GET', '/api/external/compliance/kyc/sessions/session%2F123/documents')
                ->andReturn(['data' => ['documents' => []]]);
            $this->mockClient
                ->shouldReceive('makeRequest')
                ->once()
                ->with('GET', '/api/external/compliance/kyc/sessions/session%2F123/documents/doc%2F1')
                ->andReturn(['data' => ['url' => 'https://s3.example.com/doc']]);

            $this->service->getSessionApplicantData('session/123');
            $this->service->listSessionDocuments('session/123');
            $this->service->getSessionDocument('session/123', 'doc/1');
        });

        it('requires a session id for every PII method and makes no HTTP call', function () {
            $this->mockClient->shouldNotReceive('makeRequest');

            expect(fn () => $this->service->getSessionApplicantData(''))
                ->toThrow(BlaaizException::class, 'Session ID is required');
            expect(fn () => $this->service->listSessionDocuments(''))
                ->toThrow(BlaaizException::class, 'Session ID is required');
            expect(fn () => $this->service->getSessionDocument('', 'doc-456'))
                ->toThrow(BlaaizException::class, 'Session ID is required');
        });

        it('requires a document id for getSessionDocument and makes no HTTP call', function () {
            $this->mockClient->shouldNotReceive('makeRequest');

            expect(fn () => $this->service->getSessionDocument('session-123', ''))
                ->toThrow(BlaaizException::class, 'Document ID is required');
        });
    });

    it('accepts case-insensitive vocabulary and sends the payload unchanged', function () {
        $data = [
            'customer_reference' => 'customer-123',
            'idempotency_key' => 'request-123',
            'requirements' => ['documents', 'Selfie'],
        ];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/compliance/kyc/sessions', $data)
            ->andReturn(['data' => ['id' => 'session-1']]);

        $this->service->createSession($data);
    });

    it('accepts a lower-case id_doc_type for an upload URL and sends it unchanged', function () {
        $data = ['file_name' => 'passport.jpg', 'id_doc_type' => 'passport'];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/compliance/kyc/sessions/session-123/documents/upload-url', $data)
            ->andReturn(['data' => []]);

        $this->service->createDocumentUploadUrl('session-123', $data);
    });

    it('accepts a mixed-case content_type and id_doc_type for a document and sends them unchanged', function () {
        $data = [
            'filename' => 'passport.jpg',
            'content_type' => 'IMAGE/JPEG',
            'id_doc_type' => 'passport',
            'country' => 'GBR',
            'content_base64' => 'aGVsbG8=',
        ];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/compliance/kyc/sessions/session-123/documents', $data)
            ->andReturn(['data' => []]);

        $this->service->uploadSessionDocument('session-123', $data);
    });

    describe('aliases', function () {
        it('create delegates to createSession', function () {
            $data = ['customer_reference' => 'c1', 'idempotency_key' => 'k1', 'requirements' => ['DOCUMENTS']];

            $this->mockClient
                ->shouldReceive('makeRequest')
                ->once()
                ->with('POST', '/api/external/compliance/kyc/sessions', $data)
                ->andReturn(['data' => ['id' => 's1']]);

            $this->service->create($data);
        });

        it('list delegates to listSessions', function () {
            $this->mockClient
                ->shouldReceive('makeRequest')
                ->once()
                ->with('GET', '/api/external/compliance/kyc/sessions', ['limit' => 5])
                ->andReturn(['data' => ['sessions' => []]]);

            $this->service->list(['limit' => 5]);
        });

        it('get delegates to getSession', function () {
            $this->mockClient
                ->shouldReceive('makeRequest')
                ->once()
                ->with('GET', '/api/external/compliance/kyc/sessions/session-123')
                ->andReturn(['data' => ['id' => 'session-123']]);

            $this->service->get('session-123');
        });

        it('submit delegates to submitSession', function () {
            $this->mockClient
                ->shouldReceive('makeRequest')
                ->once()
                ->with('POST', '/api/external/compliance/kyc/sessions/session-123/submit')
                ->andReturn(['data' => ['id' => 'session-123']]);

            $this->service->submit('session-123');
        });

        it('cancel delegates to cancelSession', function () {
            $this->mockClient
                ->shouldReceive('makeRequest')
                ->once()
                ->with('POST', '/api/external/compliance/kyc/sessions/session-123/cancel')
                ->andReturn(['data' => ['id' => 'session-123']]);

            $this->service->cancel('session-123');
        });

        it('uploadDocument delegates to uploadSessionDocument', function () {
            $data = [
                'filename' => 'passport.jpg',
                'content_type' => 'image/jpeg',
                'id_doc_type' => 'PASSPORT',
                'country' => 'GBR',
                'content_base64' => 'aGVsbG8=',
            ];

            $this->mockClient
                ->shouldReceive('makeRequest')
                ->once()
                ->with('POST', '/api/external/compliance/kyc/sessions/session-123/documents', $data)
                ->andReturn(['data' => ['id' => 'session-123']]);

            $this->service->uploadDocument('session-123', $data);
        });
    });

    describe('validation', function () {
        it('requires a session id for every id-based method', function () {
            $uploadData = ['file_name' => 'a.jpg', 'id_doc_type' => 'PASSPORT'];
            $documentData = [
                'filename' => 'a.jpg',
                'content_type' => 'image/jpeg',
                'id_doc_type' => 'PASSPORT',
                'country' => 'GBR',
                'content_base64' => 'aGVsbG8=',
            ];

            expect(fn () => $this->service->getSession(''))
                ->toThrow(BlaaizException::class, 'Session ID is required');
            expect(fn () => $this->service->submitSession(''))
                ->toThrow(BlaaizException::class, 'Session ID is required');
            expect(fn () => $this->service->cancelSession(''))
                ->toThrow(BlaaizException::class, 'Session ID is required');
            expect(fn () => $this->service->createDocumentUploadUrl('', $uploadData))
                ->toThrow(BlaaizException::class, 'Session ID is required');
            expect(fn () => $this->service->uploadSessionDocument('', $documentData))
                ->toThrow(BlaaizException::class, 'Session ID is required');
            expect(fn () => $this->service->issueVerificationLink(''))
                ->toThrow(BlaaizException::class, 'Session ID is required');
        });

        it('validates createSession required fields', function () {
            expect(fn () => $this->service->createSession([]))
                ->toThrow(BlaaizException::class, 'customer_reference is required');

            expect(fn () => $this->service->createSession(['customer_reference' => 'c1']))
                ->toThrow(BlaaizException::class, 'idempotency_key is required');

            expect(fn () => $this->service->createSession([
                'customer_reference' => 'c1',
                'idempotency_key' => 'k1',
            ]))->toThrow(BlaaizException::class, 'requirements is required');
        });

        it('validates requirements is a non-empty array', function () {
            expect(fn () => $this->service->createSession([
                'customer_reference' => 'c1',
                'idempotency_key' => 'k1',
                'requirements' => [],
            ]))->toThrow(BlaaizException::class, 'requirements must be a non-empty array');

            expect(fn () => $this->service->createSession([
                'customer_reference' => 'c1',
                'idempotency_key' => 'k1',
                'requirements' => 'DOCUMENTS',
            ]))->toThrow(BlaaizException::class, 'requirements must be a non-empty array');
        });

        it('validates requirements only contains known values', function () {
            expect(fn () => $this->service->createSession([
                'customer_reference' => 'customer-123',
                'idempotency_key' => 'request-123',
                'requirements' => ['UNKNOWN'],
            ]))->toThrow(BlaaizException::class, 'requirements must contain only: DOCUMENTS, SELFIE, FACE_MATCH, PROOF_OF_ADDRESS');

            expect(fn () => $this->service->createSession([
                'customer_reference' => 'customer-123',
                'idempotency_key' => 'request-123',
                'requirements' => [123],
            ]))->toThrow(BlaaizException::class, 'requirements must contain only: DOCUMENTS, SELFIE, FACE_MATCH, PROOF_OF_ADDRESS');
        });

        it('validates createDocumentUploadUrl required fields', function () {
            expect(fn () => $this->service->createDocumentUploadUrl('session-123', []))
                ->toThrow(BlaaizException::class, 'file_name is required');

            expect(fn () => $this->service->createDocumentUploadUrl('session-123', ['file_name' => 'a.jpg']))
                ->toThrow(BlaaizException::class, 'id_doc_type is required');

            expect(fn () => $this->service->createDocumentUploadUrl('session-123', [
                'file_name' => 'a.jpg',
                'id_doc_type' => 'UNKNOWN',
            ]))->toThrow(
                BlaaizException::class,
                'id_doc_type must be one of: PASSPORT, ID_CARD, DRIVERS, RESIDENCE_PERMIT, UTILITY_BILL, BANK_STATEMENT, SELFIE'
            );
        });

        it('validates uploadSessionDocument required fields', function () {
            expect(fn () => $this->service->uploadSessionDocument('session-123', []))
                ->toThrow(BlaaizException::class, 'filename is required');

            expect(fn () => $this->service->uploadSessionDocument('session-123', ['filename' => 'a.jpg']))
                ->toThrow(BlaaizException::class, 'content_type is required');

            expect(fn () => $this->service->uploadSessionDocument('session-123', [
                'filename' => 'a.jpg',
                'content_type' => 'image/jpeg',
            ]))->toThrow(BlaaizException::class, 'id_doc_type is required');

            expect(fn () => $this->service->uploadSessionDocument('session-123', [
                'filename' => 'a.jpg',
                'content_type' => 'image/jpeg',
                'id_doc_type' => 'PASSPORT',
            ]))->toThrow(BlaaizException::class, 'country is required');
        });

        it('validates content_type is one of the known values', function () {
            expect(fn () => $this->service->uploadSessionDocument('session-123', [
                'filename' => 'a.jpg',
                'content_type' => 'application/zip',
                'id_doc_type' => 'PASSPORT',
                'country' => 'GBR',
                'content_base64' => 'aGVsbG8=',
            ]))->toThrow(BlaaizException::class, 'content_type must be one of: image/jpeg, image/png, image/webp, application/pdf');
        });

        it('validates id_doc_type is one of the known values for uploadSessionDocument', function () {
            expect(fn () => $this->service->uploadSessionDocument('session-123', [
                'filename' => 'a.jpg',
                'content_type' => 'image/jpeg',
                'id_doc_type' => 'UNKNOWN',
                'country' => 'GBR',
                'content_base64' => 'aGVsbG8=',
            ]))->toThrow(
                BlaaizException::class,
                'id_doc_type must be one of: PASSPORT, ID_CARD, DRIVERS, RESIDENCE_PERMIT, UTILITY_BILL, BANK_STATEMENT, SELFIE'
            );
        });

        it('requires exactly one of file_name or content_base64', function () {
            expect(fn () => $this->service->uploadSessionDocument('session-123', [
                'filename' => 'a.jpg',
                'content_type' => 'image/jpeg',
                'id_doc_type' => 'PASSPORT',
                'country' => 'GBR',
            ]))->toThrow(BlaaizException::class, 'Provide exactly one of file_name or content_base64');

            expect(fn () => $this->service->uploadSessionDocument('session-123', [
                'filename' => 'a.jpg',
                'content_type' => 'image/jpeg',
                'id_doc_type' => 'PASSPORT',
                'country' => 'GBR',
                'file_name' => 'staged.jpg',
                'content_base64' => 'aGVsbG8=',
            ]))->toThrow(BlaaizException::class, 'Provide exactly one of file_name or content_base64');
        });
    });
});
