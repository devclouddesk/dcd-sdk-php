<?php

declare(strict_types=1);

require_once __DIR__ . '/../php/src/Exceptions/StorageException.php';
require_once __DIR__ . '/../php/src/Exceptions/AuthenticationException.php';
require_once __DIR__ . '/../php/src/Exceptions/NotFoundException.php';
require_once __DIR__ . '/../php/src/Exceptions/PayloadTooLargeException.php';
require_once __DIR__ . '/../php/src/Exceptions/UploadException.php';
require_once __DIR__ . '/../php/src/Exceptions/NetworkTimeoutException.php';

require_once __DIR__ . '/../php/src/Models/AssetResult.php';
require_once __DIR__ . '/../php/src/Models/Pagination.php';
require_once __DIR__ . '/../php/src/Models/FileListResult.php';
require_once __DIR__ . '/../php/src/Models/SignedUploadResult.php';
require_once __DIR__ . '/../php/src/Models/ServerStats.php';
require_once __DIR__ . '/../php/src/Models/HealthCheckResult.php';

require_once __DIR__ . '/../php/src/TransformationBuilder.php';
require_once __DIR__ . '/../php/src/StorageClient.php';

use Dcd\Storage\StorageClient;

// Initialize DevCloudDesk Storage Client
$endpoint = getenv('DCD_STORAGE_ENDPOINT') ?: 'http://127.0.0.1:7100';
$apiKey = getenv('DCD_STORAGE_API_KEY') ?: 'dcd_sk_sample_key';

$client = new StorageClient($endpoint, $apiKey);

echo "--- DevCloudDesk PHP Storage Client Quickstart ---\n";

// 1. Generate transformed image URL
$cdnUrl = $client->url('avatars/demo-user.jpg')
    ->resize(400, 400, 'fill')
    ->format('webp')
    ->quality(85)
    ->build();

echo "1. Transformed CDN URL: {$cdnUrl}\n";

// 2. Direct public URL
$publicUrl = $client->getPublicUrl('docs/guide.pdf');
echo "2. Direct Public URL: {$publicUrl}\n";

echo "Quickstart completed.\n";

