<?php

namespace Blaaiz\LaravelSdk;

use Blaaiz\LaravelSdk\Exceptions\BlaaizException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

class BlaaizClient
{
    protected string $apiKey;
    protected string $baseUrl;
    protected int $timeout;
    protected Client $httpClient;
    protected array $defaultHeaders;

    public function __construct(string $apiKey, array $options = [])
    {
        if (empty($apiKey)) {
            throw new BlaaizException('API key is required');
        }

        $this->apiKey = $apiKey;
        $this->baseUrl = $options['base_url'] ?? 'https://api-dev.blaaiz.com';
        $this->timeout = $options['timeout'] ?? 30;

        $this->defaultHeaders = [
            'x-blaaiz-api-key' => $this->apiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => 'Blaaiz-Laravel-SDK/1.0.0',
        ];

        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => $this->timeout,
            'headers' => $this->defaultHeaders,
        ]);
    }

    public function makeRequest(string $method, string $endpoint, ?array $data = null, array $headers = []): array
    {
        try {
            $options = [
                'headers' => array_merge($this->defaultHeaders, $headers),
            ];

            if ($data !== null && strtoupper($method) !== 'GET') {
                $options['json'] = $data;
            }

            $response = $this->httpClient->request(strtoupper($method), $endpoint, $options);

            $responseData = json_decode($response->getBody()->getContents(), true);

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                return [
                    'data' => $responseData,
                    'status' => $response->getStatusCode(),
                    'headers' => $response->getHeaders(),
                ];
            }

            throw new BlaaizException(
                $responseData['message'] ?? 'API request failed',
                $response->getStatusCode(),
                $responseData['code'] ?? null
            );

        } catch (RequestException $e) {
            $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : null;
            $responseBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : null;

            $errorData = null;
            if ($responseBody) {
                $errorData = json_decode($responseBody, true);
            }

            throw new BlaaizException(
                $errorData['message'] ?? $e->getMessage(),
                $statusCode,
                $errorData['code'] ?? 'REQUEST_ERROR'
            );

        } catch (GuzzleException $e) {
            throw new BlaaizException(
                "Request failed: {$e->getMessage()}",
                null,
                'GUZZLE_ERROR'
            );
        } catch (\Exception $e) {
            throw new BlaaizException(
                "Unexpected error: {$e->getMessage()}",
                null,
                'UNEXPECTED_ERROR'
            );
        }
    }

    public function uploadFile(string $presignedUrl, $fileContent, ?string $contentType = null, ?string $filename = null): array
    {
        try {
            $headers = [];

            if ($contentType) {
                $headers['Content-Type'] = $contentType;
            }

            if ($filename) {
                $headers['Content-Disposition'] = "attachment; filename=\"{$filename}\"";
            }

            $client = new Client(['timeout' => $this->timeout]);

            $response = $client->request('PUT', $presignedUrl, [
                'headers' => $headers,
                'body' => $fileContent,
            ]);

            $etag = $response->getHeader('ETag')[0] ?? $response->getHeader('etag')[0] ?? null;

            if (!$etag) {
                throw new BlaaizException('S3 upload failed: No ETag received from S3');
            }

            return [
                'status' => $response->getStatusCode(),
                'etag' => $etag,
            ];

        } catch (RequestException $e) {
            $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : null;
            $responseBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : null;

            throw new BlaaizException(
                "S3 upload failed with status {$statusCode}: {$responseBody}",
                $statusCode,
                'S3_UPLOAD_ERROR'
            );

        } catch (GuzzleException $e) {
            throw new BlaaizException(
                "S3 upload request failed: {$e->getMessage()}",
                null,
                'S3_REQUEST_ERROR'
            );
        }
    }

    public function downloadFile(string $url): array
    {
        try {
            $client = new Client(['timeout' => $this->timeout]);

            $response = $client->request('GET', $url, [
                'headers' => [
                    'User-Agent' => 'Blaaiz-Laravel-SDK/1.0.0',
                ],
            ]);

            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                throw new BlaaizException("Failed to download file: HTTP {$response->getStatusCode()}");
            }

            $content = $response->getBody()->getContents();
            $contentType = $response->getHeader('Content-Type')[0] ?? null;

            $filename = null;
            $contentDisposition = $response->getHeader('Content-Disposition')[0] ?? null;
            if ($contentDisposition && preg_match('/filename[^;=\n]*=(([\'"]).*?\2|[^;\n]*)/', $contentDisposition, $matches)) {
                $filename = trim($matches[1], '"\'');
            }

            if (!$filename) {
                $filename = basename(parse_url($url, PHP_URL_PATH));

                if (!pathinfo($filename, PATHINFO_EXTENSION) && $contentType) {
                    $extension = $this->getExtensionFromContentType($contentType);
                    if ($extension) {
                        $filename .= $extension;
                    }
                }
            }

            return [
                'content' => $content,
                'content_type' => $contentType,
                'filename' => $filename,
            ];

        } catch (RequestException $e) {
            throw new BlaaizException(
                "File download failed: {$e->getMessage()}",
                $e->getResponse() ? $e->getResponse()->getStatusCode() : null,
                'DOWNLOAD_ERROR'
            );

        } catch (GuzzleException $e) {
            throw new BlaaizException(
                "File download failed: {$e->getMessage()}",
                null,
                'DOWNLOAD_ERROR'
            );
        }
    }

    private function getExtensionFromContentType(string $contentType): ?string
    {
        $mimeToExt = [
            'image/jpeg' => '.jpg',
            'image/jpg' => '.jpg',
            'image/png' => '.png',
            'image/gif' => '.gif',
            'image/webp' => '.webp',
            'image/bmp' => '.bmp',
            'image/tiff' => '.tiff',
            'application/pdf' => '.pdf',
            'text/plain' => '.txt',
            'application/msword' => '.doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => '.docx',
        ];

        $contentType = explode(';', $contentType)[0];

        return $mimeToExt[$contentType] ?? null;
    }
}