<?php

use Blaaiz\LaravelSdk\Services\CustomerService;
use Blaaiz\LaravelSdk\Exceptions\BlaaizException;
use Blaaiz\LaravelSdk\BlaaizClient;
use Mockery;

describe('CustomerService', function () {
    beforeEach(function () {
        $this->mockClient = Mockery::mock(BlaaizClient::class);
        $this->service = new CustomerService($this->mockClient);
    });

    afterEach(function () {
        Mockery::close();
    });

    it('validates required fields for create', function () {
        expect(fn() => $this->service->create([]))
            ->toThrow(BlaaizException::class, 'first_name is required');

        expect(fn() => $this->service->create(['first_name' => 'John']))
            ->toThrow(BlaaizException::class, 'last_name is required');

        expect(fn() => $this->service->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'type' => 'individual',
            'email' => 'john@example.com',
            'country' => 'NG'
        ]))->toThrow(BlaaizException::class, 'id_type is required');
    });

    it('requires business_name for business type in create', function () {
        $customerData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'type' => 'business',
            'email' => 'john@example.com',
            'country' => 'NG',
            'id_type' => 'passport',
            'id_number' => '12345'
        ];

        expect(fn() => $this->service->create($customerData))
            ->toThrow(BlaaizException::class, 'business_name is required when type is business');
    });

    it('calls makeRequest with correct parameters for create', function () {
        $customerData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'type' => 'individual',
            'email' => 'john@example.com',
            'country' => 'NG',
            'id_type' => 'passport',
            'id_number' => '12345'
        ];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/customer', $customerData)
            ->andReturn(['data' => ['id' => 'customer-123']]);

        $result = $this->service->create($customerData);

        expect($result)->toBe(['data' => ['id' => 'customer-123']]);
    });

    it('accepts business customers with business_name for create', function () {
        $customerData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'type' => 'business',
            'business_name' => 'Acme Corp',
            'email' => 'john@example.com',
            'country' => 'NG',
            'id_type' => 'passport',
            'id_number' => '12345'
        ];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/customer', $customerData)
            ->andReturn(['data' => ['id' => 'customer-123']]);

        $result = $this->service->create($customerData);

        expect($result)->toBe(['data' => ['id' => 'customer-123']]);
    });

    it('calls makeRequest with correct parameters for list', function () {
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('GET', '/api/external/customer')
            ->andReturn(['data' => []]);

        $result = $this->service->list();

        expect($result)->toBe(['data' => []]);
    });

    it('throws exception for empty customer ID in get', function () {
        expect(fn() => $this->service->get(''))
            ->toThrow(BlaaizException::class, 'Customer ID is required');
    });

    it('calls makeRequest with correct parameters for get', function () {
        $customerId = 'customer-123';

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('GET', "/api/external/customer/{$customerId}")
            ->andReturn(['data' => ['id' => $customerId]]);

        $result = $this->service->get($customerId);

        expect($result)->toBe(['data' => ['id' => $customerId]]);
    });

    it('throws exception for empty customer ID in update', function () {
        expect(fn() => $this->service->update('', []))
            ->toThrow(BlaaizException::class, 'Customer ID is required');
    });

    it('calls makeRequest with correct parameters for update', function () {
        $customerId = 'customer-123';
        $updateData = ['email' => 'newemail@example.com'];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('PUT', "/api/external/customer/{$customerId}", $updateData)
            ->andReturn(['data' => ['id' => $customerId]]);

        $result = $this->service->update($customerId, $updateData);

        expect($result)->toBe(['data' => ['id' => $customerId]]);
    });

    it('throws exception for empty customer ID in addKyc', function () {
        expect(fn() => $this->service->addKyc('', []))
            ->toThrow(BlaaizException::class, 'Customer ID is required');
    });

    it('calls makeRequest with correct parameters for addKyc', function () {
        $customerId = 'customer-123';
        $kycData = ['document_type' => 'passport'];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', "/api/external/customer/{$customerId}/kyc-data", $kycData)
            ->andReturn(['data' => ['status' => 'submitted']]);

        $result = $this->service->addKyc($customerId, $kycData);

        expect($result)->toBe(['data' => ['status' => 'submitted']]);
    });

    it('throws exception for empty customer ID in uploadFiles', function () {
        expect(fn() => $this->service->uploadFiles('', []))
            ->toThrow(BlaaizException::class, 'Customer ID is required');
    });

    it('calls makeRequest with correct parameters for uploadFiles', function () {
        $customerId = 'customer-123';
        $fileData = ['id_file' => 'file-123'];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('PUT', "/api/external/customer/{$customerId}/files", $fileData)
            ->andReturn(['data' => ['status' => 'uploaded']]);

        $result = $this->service->uploadFiles($customerId, $fileData);

        expect($result)->toBe(['data' => ['status' => 'uploaded']]);
    });

    it('validates required parameters for uploadFileComplete', function () {
        expect(fn() => $this->service->uploadFileComplete('', []))
            ->toThrow(BlaaizException::class, 'Customer ID is required');

        expect(fn() => $this->service->uploadFileComplete('customer-123', []))
            ->toThrow(BlaaizException::class, 'File options are required');

        expect(fn() => $this->service->uploadFileComplete('customer-123', []))
            ->toThrow(BlaaizException::class, 'File options are required');

        expect(fn() => $this->service->uploadFileComplete('customer-123', ['file_category' => 'identity']))
            ->toThrow(BlaaizException::class, 'File is required');

        expect(fn() => $this->service->uploadFileComplete('customer-123', ['file' => 'content']))
            ->toThrow(BlaaizException::class, 'file_category is required');

        expect(fn() => $this->service->uploadFileComplete('customer-123', [
            'file' => 'content',
            'file_category' => 'invalid'
        ]))->toThrow(BlaaizException::class, 'file_category must be one of: identity, proof_of_address, liveness_check');
    });

    it('successfully uploads a file with Buffer content for uploadFileComplete', function () {
        $customerId = 'customer-123';
        $fileOptions = [
            'file' => 'test file content',
            'file_category' => 'identity',
            'filename' => 'test.pdf',
            'content_type' => 'application/pdf'
        ];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', '/api/external/file/get-presigned-url', [
                'customer_id' => $customerId,
                'file_category' => 'identity',
            ])
            ->andReturn([
                'data' => [
                    'url' => 'https://s3.amazonaws.com/bucket/file',
                    'file_id' => 'file-123',
                    'headers' => []
                ]
            ]);

        $this->mockClient
            ->shouldReceive('uploadFile')
            ->once()
            ->with('https://s3.amazonaws.com/bucket/file', Mockery::type('string'), 'application/pdf', 'test.pdf')
            ->andReturn(['status' => 200]);

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->with('POST', "/api/external/customer/{$customerId}/files", [
                'id_file' => 'file-123'
            ])
            ->andReturn(['data' => ['success' => true]]);

        $result = $this->service->uploadFileComplete($customerId, $fileOptions);

        expect($result['data'])->toBe(['success' => true]);
        expect($result['file_id'])->toBe('file-123');
        expect($result['presigned_url'])->toBe('https://s3.amazonaws.com/bucket/file');
    });

    it('handles alternative presigned response structure for uploadFileComplete', function () {
        $customerId = 'customer-123';
        $fileOptions = [
            'file' => 'test content',
            'file_category' => 'identity'
        ];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->andReturn([
                'data' => [
                    'data' => [
                        'url' => 'https://s3.amazonaws.com/bucket/file',
                        'file_id' => 'file-123'
                    ]
                ]
            ]);

        $this->mockClient
            ->shouldReceive('uploadFile')
            ->once()
            ->andReturn(['status' => 200]);

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->andReturn(['data' => ['success' => true]]);

        $result = $this->service->uploadFileComplete($customerId, $fileOptions);

        expect($result['file_id'])->toBe('file-123');
    });

    it('handles base64 string input for uploadFileComplete', function () {
        $customerId = 'customer-123';
        $base64String = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
        
        $fileOptions = [
            'file' => $base64String,
            'file_category' => 'identity'
        ];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->andReturn([
                'data' => [
                    'url' => 'https://s3.amazonaws.com/bucket/file',
                    'file_id' => 'file-123'
                ]
            ]);

        $this->mockClient
            ->shouldReceive('uploadFile')
            ->once()
            ->with('https://s3.amazonaws.com/bucket/file', Mockery::type('string'), null, null)
            ->andReturn(['status' => 200]);

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->andReturn(['data' => ['success' => true]]);

        $result = $this->service->uploadFileComplete($customerId, $fileOptions);

        expect($result['file_id'])->toBe('file-123');
    });

    it('handles data URL with content type extraction for uploadFileComplete', function () {
        $customerId = 'customer-123';
        $dataUrl = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
        
        $fileOptions = [
            'file' => $dataUrl,
            'file_category' => 'identity'
        ];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->andReturn([
                'data' => [
                    'url' => 'https://s3.amazonaws.com/bucket/file',
                    'file_id' => 'file-123'
                ]
            ]);

        $this->mockClient
            ->shouldReceive('uploadFile')
            ->once()
            ->with('https://s3.amazonaws.com/bucket/file', Mockery::type('string'), 'image/png', null)
            ->andReturn(['status' => 200]);

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->andReturn(['data' => ['success' => true]]);

        $result = $this->service->uploadFileComplete($customerId, $fileOptions);

        expect($result['file_id'])->toBe('file-123');
    });

    it('handles URL download for uploadFileComplete', function () {
        $customerId = 'customer-123';
        $fileUrl = 'https://example.com/image.jpg';
        
        $fileOptions = [
            'file' => $fileUrl,
            'file_category' => 'identity'
        ];

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->andReturn([
                'data' => [
                    'url' => 'https://s3.amazonaws.com/bucket/file',
                    'file_id' => 'file-123'
                ]
            ]);

        $this->mockClient
            ->shouldReceive('downloadFile')
            ->once()
            ->andReturn([
                'content' => 'downloaded image content',
                'content_type' => 'image/jpeg',
                'filename' => 'image.jpg'
            ]);

        $this->mockClient
            ->shouldReceive('uploadFile')
            ->once()
            ->with('https://s3.amazonaws.com/bucket/file', 'downloaded image content', 'image/jpeg', 'image.jpg')
            ->andReturn(['status' => 200]);

        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->andReturn(['data' => ['success' => true]]);

        $result = $this->service->uploadFileComplete($customerId, $fileOptions);

        expect($result['file_id'])->toBe('file-123');
    });

    it('handles different file categories for uploadFileComplete', function () {
        $categories = [
            'identity' => 'id_file',
            'liveness_check' => 'liveness_check_file',
            'proof_of_address' => 'proof_of_address_file'
        ];

        foreach ($categories as $category => $fieldName) {
            $mockClient = Mockery::mock(\Blaaiz\LaravelSdk\BlaaizClient::class);
            $service = new CustomerService($mockClient);

            $mockClient
                ->shouldReceive('makeRequest')
                ->once()
                ->andReturn([
                    'data' => [
                        'url' => 'https://s3.amazonaws.com/bucket/file',
                        'file_id' => 'file-123'
                    ]
                ]);

            $mockClient
                ->shouldReceive('uploadFile')
                ->once()
                ->andReturn(['status' => 200]);

            $mockClient
                ->shouldReceive('makeRequest')
                ->once()
                ->with('POST', "/api/external/customer/customer-123/files", [
                    $fieldName => 'file-123'
                ])
                ->andReturn(['data' => ['success' => true]]);

            $result = $service->uploadFileComplete('customer-123', [
                'file' => 'content',
                'file_category' => $category
            ]);

            expect($result['file_id'])->toBe('file-123');
        }
    });

    it('throws exception for invalid presigned URL response in uploadFileComplete', function () {
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->andReturn(['data' => ['invalid' => 'response']]);

        expect(fn() => $this->service->uploadFileComplete('customer-123', [
            'file' => 'content',
            'file_category' => 'identity'
        ]))->toThrow(BlaaizException::class, 'Invalid presigned URL response structure');
    });

    it('propagates BlaaizException from file operations in uploadFileComplete', function () {
        $this->mockClient
            ->shouldReceive('makeRequest')
            ->once()
            ->andThrow(new BlaaizException('API Error', 400, 'API_ERROR'));

        expect(fn() => $this->service->uploadFileComplete('customer-123', [
            'file' => 'content',
            'file_category' => 'identity'
        ]))->toThrow(BlaaizException::class, 'File upload failed: API Error');
    });
});

