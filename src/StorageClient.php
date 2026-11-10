<?php

declare(strict_types=1);

namespace Dcd\Storage;

use CURLFile;
use Dcd\Storage\Exceptions\AuthenticationException;
use Dcd\Storage\Exceptions\NetworkTimeoutException;
use Dcd\Storage\Exceptions\NotFoundException;
use Dcd\Storage\Exceptions\PayloadTooLargeException;
use Dcd\Storage\Exceptions\StorageException;
use Dcd\Storage\Exceptions\UploadException;
use Dcd\Storage\Models\AssetResult;
use Dcd\Storage\Models\FileListResult;
use Dcd\Storage\Models\HealthCheckResult;
use Dcd\Storage\Models\ServerStats;
use Dcd\Storage\Models\SignedUploadResult;
use InvalidArgumentException;

/**
 * Official PHP SDK for DevCloudDesk Storage Engine.
 *
 * Provides high-performance object uploads, dynamic image transformation URLs,
 * signed download links, client upload tokens, and asset management.
 */
class StorageClient
{
    protected string $endpoint;
    protected string $apiKey;
    protected int $timeout;
    protected string $deliveryPrefix;

    /**
     * Cloudinary-compatible namespace for upload and destroy operations
     */
    public object $uploader;

    /**
     * Cloudinary-compatible namespace for admin API operations
     */
    public object $api;

    /**
     * @param array<string, mixed>|string|null $configOrEndpoint Array config or endpoint URL string
     * @param string|null $apiKey Public key (dcd_pk_...), Secret key (dcd_sk_...), or Master key
     * @param int $timeout Request timeout in seconds (default: 30)
     * @param string $deliveryPrefix Image delivery path prefix (default: 'image/upload')
     */
    public function __construct(
        mixed $configOrEndpoint = null,
        ?string $apiKey = null,
        int $timeout = 30,
        string $deliveryPrefix = 'image/upload'
    ) {
        $envEndpoint = (string)(getenv('DCD_STORAGE_ENDPOINT') ?: '');
        $envApiKey = (string)(getenv('DCD_STORAGE_API_KEY') ?: (getenv('DCD_API_KEY') ?: ''));

        if (is_array($configOrEndpoint)) {
            $endpoint = $configOrEndpoint['endpoint'] ?? $envEndpoint;
            $apiKey = $configOrEndpoint['apiKey'] ?? ($configOrEndpoint['api_key'] ?? $envApiKey);
            $timeout = (int)($configOrEndpoint['timeout'] ?? ($configOrEndpoint['timeoutMs'] ? (int)($configOrEndpoint['timeoutMs'] / 1000) : 30));
            $deliveryPrefix = $configOrEndpoint['deliveryPrefix'] ?? ($configOrEndpoint['delivery_prefix'] ?? 'image/upload');
        } else {
            $endpoint = $configOrEndpoint ?: $envEndpoint;
            $apiKey = $apiKey ?: $envApiKey;
        }

        if (empty($endpoint)) {
            throw new InvalidArgumentException(
                'StorageClient: endpoint is required (pass in config or set DCD_STORAGE_ENDPOINT environment variable)'
            );
        }

        $this->endpoint = rtrim($endpoint, '/');
        $this->apiKey = trim((string)$apiKey);
        $this->timeout = $timeout > 0 ? $timeout : 30;
        $this->deliveryPrefix = trim($deliveryPrefix, '/');

        // Initialize Cloudinary-compatible namespaces
        $this->initNamespaces();
    }

    protected function initNamespaces(): void
    {
        $client = $this;

        $this->uploader = new class($client) {
            private StorageClient $client;
            public function __construct(StorageClient $client) { $this->client = $client; }
            public function upload(mixed $file, array $options = []): AssetResult {
                return $this->client->upload($file, $options);
            }
            public function destroy(string $publicId): bool {
                return $this->client->delete($publicId);
            }
            public function delete(string $publicId): bool {
                return $this->client->delete($publicId);
            }
        };

        $this->api = new class($client) {
            private StorageClient $client;
            public function __construct(StorageClient $client) { $this->client = $client; }
            public function resource(string $publicId): AssetResult {
                return $this->client->getResource($publicId);
            }
            public function resources(array $options = []): FileListResult {
                return $this->client->listFiles($options);
            }
            public function update(string $publicId, array $options = []): AssetResult {
                return $this->client->updateAccess($publicId, $options['access'] ?? 'public');
            }
            public function delete_resources(array $publicIds): array {
                $results = [];
                foreach ($publicIds as $id) {
                    $results[$id] = $this->client->delete((string)$id);
                }
                return $results;
            }
            public function usage(): ServerStats {
                return $this->client->getStats();
            }
        };
    }

    /**
     * Directly returns the public CDN URL for an asset without transformation boilerplate.
     */
    public function getPublicUrl(string $publicId): string
    {
        $clean = ltrim($publicId, '/');
        return "{$this->endpoint}/{$this->deliveryPrefix}/{$clean}";
    }

    /**
     * Generates a time-limited cryptographically signed URL to access private assets
     * (e.g. invoices, KYC documents, private backups).
     *
     * @param array{expiresInSeconds?: int, resourceType?: string, transform?: string} $options
     */
    public function getSignedUrl(string $publicId, array $options = []): string
    {
        if (empty($this->apiKey)) {
            throw new AuthenticationException('API key is required to generate signed download URLs.');
        }

        $clean = ltrim($publicId, '/');
        $signUrl = "{$this->endpoint}/v1/download/sign";

        $payload = [
            'publicId' => $clean,
            'expiresInSeconds' => (int)($options['expiresInSeconds'] ?? ($options['expires_in_seconds'] ?? 300)),
            'resourceType' => (string)($options['resourceType'] ?? ($options['resource_type'] ?? 'image')),
        ];

        if (!empty($options['transform'])) {
            $payload['transform'] = (string)$options['transform'];
        }

        $response = $this->request('POST', $signUrl, [
            'json' => $payload,
            'headers' => $this->getAuthHeaders(['Content-Type: application/json']),
        ]);

        return (string)($response['signedUrl'] ?? $response['signed_url'] ?? '');
    }

    /**
     * Initializes a URL builder or immediately generates a transformed URL string
     *
     * Example (Fluent builder style):
     *   $url = $client->url('avatars/user-1')->width(300)->height(300)->format('webp')->build();
     *
     * Example (Direct options style):
     *   $url = $client->url('avatars/user-1', ['width' => 300, 'height' => 300, 'format' => 'webp']);
     */
    public function url(string $publicId, array $options = []): TransformationBuilder|string
    {
        $builder = new TransformationBuilder($this->endpoint, $publicId, $options, $this->deliveryPrefix);
        if (!empty($options)) {
            return $builder->build();
        }
        return $builder;
    }

    /**
     * Directly uploads a file to the storage server.
     * Supports:
     *  - Local file path (string)
     *  - Raw binary string (e.g. file_get_contents)
     *  - PHP resource handle (stream)
     *
     * @param mixed $file File path, binary content, or stream resource
     * @param array<string, mixed> $options Upload options
     */
    public function upload(mixed $file, array $options = []): AssetResult
    {
        $uploadUrl = "{$this->endpoint}/v1/upload";

        $postFields = [];
        $tempFilePath = null;

        if (is_string($file) && file_exists($file)) {
            $mime = mime_content_type($file) ?: 'application/octet-stream';
            $filename = $options['filename'] ?? basename($file);
            $postFields['file'] = new CURLFile($file, $mime, $filename);
        } elseif (is_resource($file)) {
            $tempFilePath = tempnam(sys_get_temp_dir(), 'dcd_upload_');
            $dest = fopen($tempFilePath, 'wb');
            stream_copy_to_stream($file, $dest);
            fclose($dest);

            $filename = $options['filename'] ?? 'upload.bin';
            $mime = mime_content_type($tempFilePath) ?: 'application/octet-stream';
            $postFields['file'] = new CURLFile($tempFilePath, $mime, $filename);
        } elseif (is_string($file)) {
            $tempFilePath = tempnam(sys_get_temp_dir(), 'dcd_upload_');
            file_put_contents($tempFilePath, $file);

            $filename = $options['filename'] ?? 'upload.bin';
            $mime = mime_content_type($tempFilePath) ?: 'application/octet-stream';
            $postFields['file'] = new CURLFile($tempFilePath, $mime, $filename);
        } else {
            throw new InvalidArgumentException('Unsupported file parameter. Pass a file path, binary string, or stream resource.');
        }

        // Add additional form fields
        if (!empty($options['folder'])) $postFields['folder'] = (string)$options['folder'];
        $publicId = $options['publicId'] ?? ($options['public_id'] ?? null);
        if ($publicId) $postFields['public_id'] = (string)$publicId;
        if (isset($options['overwrite'])) $postFields['overwrite'] = $options['overwrite'] ? 'true' : 'false';
        $uniqueFilename = $options['uniqueFilename'] ?? ($options['unique_filename'] ?? null);
        if ($uniqueFilename !== null) $postFields['unique_filename'] = $uniqueFilename ? 'true' : 'false';
        if (!empty($options['access'])) $postFields['access'] = (string)$options['access'];
        $webhook = $options['webhookUrl'] ?? ($options['webhook_url'] ?? null);
        if ($webhook) $postFields['webhook_url'] = (string)$webhook;

        // Auth headers
        $headers = [];
        if (!empty($options['token'])) {
            $headers[] = 'X-Upload-Token: ' . $options['token'];
        } elseif (!empty($this->apiKey)) {
            $headers[] = 'Authorization: Bearer ' . $this->apiKey;
        } else {
            if ($tempFilePath && file_exists($tempFilePath)) @unlink($tempFilePath);
            throw new AuthenticationException('Either apiKey or a signed upload token must be provided to upload.');
        }

        try {
            $response = $this->request('POST', $uploadUrl, [
                'body' => $postFields,
                'headers' => $headers,
                'timeout' => $options['timeout'] ?? $this->timeout,
            ]);

            $assetData = $response['asset'] ?? $response;
            return new AssetResult((array)$assetData);
        } finally {
            if ($tempFilePath && file_exists($tempFilePath)) {
                @unlink($tempFilePath);
            }
        }
    }

    /**
     * Upload helper using client-side signed upload token (zero master API key required).
     */
    public function uploadWithToken(mixed $file, string $token, array $options = []): AssetResult
    {
        $options['token'] = $token;
        return $this->upload($file, $options);
    }

    /**
     * Permanently deletes a storage asset.
     */
    public function delete(string $publicId): bool
    {
        $clean = ltrim($publicId, '/');
        $deleteUrl = "{$this->endpoint}/v1/admin/files/{$clean}";

        $this->request('DELETE', $deleteUrl, [
            'headers' => $this->getAuthHeaders(),
        ]);

        return true;
    }

    /**
     * Convenient alias for delete()
     */
    public function destroy(string $publicId): bool
    {
        return $this->delete($publicId);
    }

    /**
     * Retrieves full metadata for an asset by publicId.
     */
    public function getResource(string $publicId): AssetResult
    {
        $clean = ltrim($publicId, '/');
        $url = "{$this->endpoint}/v1/admin/files/{$clean}";

        $response = $this->request('GET', $url, [
            'headers' => $this->getAuthHeaders(),
        ]);

        $assetData = $response['asset'] ?? $response;
        return new AssetResult((array)$assetData);
    }

    /**
     * Clean alias for getResource()
     */
    public function getFile(string $publicId): AssetResult
    {
        return $this->getResource($publicId);
    }

    /**
     * Lists files in the storage cluster with pagination and optional folder filter.
     *
     * @param array{folder?: string, page?: int, limit?: int} $options
     */
    public function listFiles(array $options = []): FileListResult
    {
        $query = [];
        if (!empty($options['page'])) $query['page'] = (int)$options['page'];
        if (!empty($options['limit'])) $query['limit'] = (int)$options['limit'];
        if (!empty($options['folder'])) $query['folder'] = (string)$options['folder'];

        $queryString = !empty($query) ? '?' . http_build_query($query) : '';
        $url = "{$this->endpoint}/v1/admin/files{$queryString}";

        $response = $this->request('GET', $url, [
            'headers' => $this->getAuthHeaders(),
        ]);

        return new FileListResult((array)$response);
    }

    /**
     * Clean alias for listFiles()
     */
    public function list(array $options = []): FileListResult
    {
        return $this->listFiles($options);
    }

    /**
     * Generates a short-lived cryptographically signed upload token for client-side uploads.
     *
     * @param array{folder?: string, maxBytes?: int, expiresInSeconds?: int, webhookUrl?: string, access?: string} $options
     */
    public function createSignedUploadToken(array $options = []): SignedUploadResult
    {
        if (empty($this->apiKey)) {
            throw new AuthenticationException('API key is required on backend to generate signed upload tokens.');
        }

        $signUrl = "{$this->endpoint}/v1/upload/sign";

        $response = $this->request('POST', $signUrl, [
            'json' => $options,
            'headers' => $this->getAuthHeaders(['Content-Type: application/json']),
        ]);

        return new SignedUploadResult((array)$response);
    }

    /**
     * Clean alias for createSignedUploadToken()
     */
    public function createUploadToken(array $options = []): SignedUploadResult
    {
        return $this->createSignedUploadToken($options);
    }

    /**
     * Updates asset access level between 'public' and 'private'
     */
    public function updateAccess(string $publicId, string $access = 'public'): AssetResult
    {
        $clean = ltrim($publicId, '/');
        $url = "{$this->endpoint}/v1/admin/files/{$clean}";

        $response = $this->request('PATCH', $url, [
            'json' => ['access' => $access],
            'headers' => $this->getAuthHeaders(['Content-Type: application/json']),
        ]);

        $assetData = $response['asset'] ?? $response;
        return new AssetResult((array)$assetData);
    }

    /**
     * Purges all cached image transformation variants from server disk.
     */
    public function purgeCache(): array
    {
        $url = "{$this->endpoint}/v1/admin/purge-cache";

        return $this->request('POST', $url, [
            'headers' => $this->getAuthHeaders(),
        ]);
    }

    /**
     * Retrieves server performance, storage, and cache statistics.
     */
    public function getStats(): ServerStats
    {
        $statsUrl = "{$this->endpoint}/v1/admin/stats";

        $response = $this->request('GET', $statsUrl, [
            'headers' => $this->getAuthHeaders(),
        ]);

        $stats = $response['stats'] ?? $response;
        return new ServerStats((array)$stats);
    }

    /**
     * Checks the health and connectivity of the storage engine server.
     */
    public function healthCheck(): HealthCheckResult
    {
        $healthUrl = "{$this->endpoint}/health";
        try {
            $response = $this->request('GET', $healthUrl);
            return new HealthCheckResult((array)$response);
        } catch (\Throwable) {
            return new HealthCheckResult(['status' => 'error']);
        }
    }

    // --- Protected HTTP Client Core (Zero external vendor dependencies) ---

    /**
     * @param string[] $extraHeaders
     * @return string[]
     */
    protected function getAuthHeaders(array $extraHeaders = []): array
    {
        $headers = $extraHeaders;
        if (!empty($this->apiKey)) {
            $headers[] = 'Authorization: Bearer ' . $this->apiKey;
        }
        return $headers;
    }

    /**
     * Executes HTTP requests with cURL, automatic error translation, and JSON parsing.
     *
     * @param array{headers?: string[], body?: mixed, json?: mixed, timeout?: int} $options
     * @return array<string, mixed>
     */
    protected function request(string $method, string $url, array $options = []): array
    {
        $ch = curl_init();

        $headers = $options['headers'] ?? [];
        $timeout = (int)($options['timeout'] ?? $this->timeout);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));

        if (!empty($options['json'])) {
            $jsonBody = json_encode($options['json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
            if (!in_array('Content-Type: application/json', $headers, true)) {
                $headers[] = 'Content-Type: application/json';
            }
        } elseif (!empty($options['body'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $options['body']);
        }

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $rawBody = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0) {
            if ($errno === CURLE_OPERATION_TIMEDOUT) {
                throw new NetworkTimeoutException("Request timed out after {$timeout} seconds", 408);
            }
            throw new StorageException("cURL error ({$errno}): {$error}", 0, 'NETWORK_ERROR');
        }

        $decoded = null;
        if (is_string($rawBody) && $rawBody !== '') {
            $decoded = json_decode($rawBody, true);
        }

        if ($statusCode >= 200 && $statusCode < 300) {
            return is_array($decoded) ? $decoded : ['raw' => $rawBody];
        }

        $message = $decoded['error']['message'] ?? ($decoded['message'] ?? "Storage request failed with status {$statusCode}");
        $code = $decoded['error']['code'] ?? ($decoded['code'] ?? null);

        if ($statusCode === 401 || $statusCode === 403) {
            throw new AuthenticationException($message, $statusCode, $decoded);
        }
        if ($statusCode === 404) {
            throw new NotFoundException($message, $statusCode, $decoded);
        }
        if ($statusCode === 413) {
            throw new PayloadTooLargeException($message, $statusCode, $decoded);
        }
        if ($statusCode >= 400 && $statusCode < 500 && str_contains($url, '/upload')) {
            throw new UploadException($message, $statusCode, $decoded);
        }

        throw new StorageException($message, $statusCode, $code, $decoded);
    }
}

