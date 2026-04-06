<?php

namespace Blaaiz\LaravelSdk;

use Blaaiz\LaravelSdk\Exceptions\BlaaizException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

class BlaaizClient
{
    protected string $baseUrl;
    protected int $timeout;
    protected Client $httpClient;
    protected array $defaultHeaders;

    protected string $apiKey;
    protected string $clientId;
    protected string $clientSecret;
    protected string $oauthScope;
    protected bool $useOAuth;

    protected const ALL_SCOPES = [
        'wallet:read', 'currency:read', 'bank:read', 'customer:read', 'customer:write',
        'beneficiary:read', 'virtual-account:read', 'virtual-account:create', 'virtual-account:close',
        'collection:create', 'collection:crypto:create', 'collection:interac:accept',
        'payout:create', 'swap:create', 'transaction:read', 'fees:read', 'file:upload',
        'webhook:read', 'webhook:write', 'webhook:replay', 'rates:read',
    ];

    protected ?string $accessToken = null;
    protected ?int $tokenExpiresAt = null;

    public function __construct(array $options = [])
    {
        $this->clientId = $options['client_id'] ?? '';
        $this->clientSecret = $options['client_secret'] ?? '';
        $this->oauthScope = $options['oauth_scope'] ?? implode(' ', self::ALL_SCOPES);
        $this->apiKey = $options['api_key'] ?? '';

        $this->useOAuth = !empty($this->clientId) && !empty($this->clientSecret);

        if (!$this->useOAuth && empty($this->apiKey)) {
            throw new BlaaizException(
                'Authentication required: provide either BLAAIZ_CLIENT_ID and BLAAIZ_CLIENT_SECRET for OAuth, or BLAAIZ_API_KEY for legacy authentication'
            );
        }

        $this->baseUrl = $options['base_url'] ?? 'https://api-dev.blaaiz.com';
        $this->timeout = $options['timeout'] ?? 30;

        $this->defaultHeaders = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => 'Blaaiz-Laravel-SDK/1.0.0',
        ];

        if (!$this->useOAuth) {
            $this->defaultHeaders['x-blaaiz-api-key'] = $this->apiKey;
        }

        $this->httpClient = $this->createHttpClient([
            'base_uri' => $this->baseUrl,
            'timeout' => $this->timeout,
            'headers' => $this->defaultHeaders,
        ]);
    }

    protected function getOAuthToken(): string
    {
        if ($this->accessToken && $this->tokenExpiresAt && time() < $this->tokenExpiresAt) {
            return $this->accessToken;
        }

        try {
            $client = $this->createHttpClient([
                'base_uri' => $this->baseUrl,
                'timeout' => $this->timeout,
            ]);

            $response = $client->request('POST', '/oauth/token', [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'scope' => $this->oauthScope,
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE || empty($body['access_token'])) {
                throw new BlaaizException(
                    'Failed to parse OAuth token response',
                    $response->getStatusCode(),
                    'OAUTH_PARSE_ERROR'
                );
            }

            $this->accessToken = $body['access_token'];
            // Refresh 60 seconds before expiry to avoid edge cases
            $this->tokenExpiresAt = time() + ($body['expires_in'] ?? 900) - 60;

            return $this->accessToken;

        } catch (RequestException $e) {
            $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : null;
            $responseBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : null;
            $errorData = $responseBody ? json_decode($responseBody, true) : null;

            throw new BlaaizException(
                $errorData['error_description'] ?? $errorData['message'] ?? 'OAuth token request failed: ' . $e->getMessage(),
                $statusCode,
                $errorData['error'] ?? 'OAUTH_ERROR'
            );

        } catch (GuzzleException $e) {
            throw new BlaaizException(
                "OAuth token request failed: {$e->getMessage()}",
                null,
                'OAUTH_ERROR'
            );
        }
    }

    protected function getAuthHeaders(): array
    {
        if ($this->useOAuth) {
            $token = $this->getOAuthToken();
            return ['Authorization' => "Bearer {$token}"];
        }

        return ['x-blaaiz-api-key' => $this->apiKey];
    }

    public function makeRequest(string $method, string $endpoint, ?array $data = null, array $headers = []): array
    {
        try {
            $requestHeaders = array_merge($this->defaultHeaders, $this->getAuthHeaders(), $headers);

            $options = [
                'headers' => $requestHeaders,
            ];

            if ($data !== null && strtoupper($method) === 'GET') {
                $options['query'] = $data;
            } elseif ($data !== null) {
                $options['json'] = $data;
            }

            $response = $this->httpClient->request(strtoupper($method), $endpoint, $options);

            $responseBody = $response->getBody()->getContents();
            $responseData = json_decode($responseBody, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new BlaaizException(
                    'Failed to parse API response: ' . json_last_error_msg(),
                    $response->getStatusCode(),
                    'PARSE_ERROR'
                );
            }

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
            if ($e instanceof BlaaizException) {
                throw $e;
            }

            throw new BlaaizException(
                "Unexpected error: {$e->getMessage()}",
                null,
                'UNEXPECTED_ERROR'
            );
        }
    }

    public function uploadFile(string $presignedUrl, string $fileContent, ?string $contentType = null, ?string $filename = null): array
    {
        try {
            $headers = [];

            if ($contentType) {
                $headers['Content-Type'] = $contentType;
            }

            if ($filename) {
                $headers['Content-Disposition'] = "attachment; filename=\"{$filename}\"";
            }

            $client = $this->createHttpClient(['timeout' => $this->timeout]);

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
            $client = $this->createHttpClient(['timeout' => $this->timeout]);

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
                $urlPath = parse_url($url, PHP_URL_PATH);
                $filename = $urlPath ? basename($urlPath) : 'download';

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

    protected function createHttpClient(array $config): Client
    {
        return new Client($config);
    }
}
