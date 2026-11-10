# 🐘 DCD Storage Client for PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/dcd-sdk/storage-client.svg?style=flat-square)](https://packagist.org/packages/dcd-sdk/storage-client)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.0-blue.svg?style=flat-square)](https://php.net)

Official PHP client library for [DevCloudDesk (DCD) Storage Buckets](https://devclouddesk.com). Supports ultra-fast media uploads, dynamic on-the-fly image transformations, cryptographically signed URLs, client-side upload tokens, and asset management.

Zero heavy dependencies required — built entirely on PHP 8's native cURL and JSON extensions for maximum speed and compatibility across Laravel, Symfony, WordPress, and vanilla PHP.

---

## 📦 Installation

```bash
composer require dcd-sdk/storage-client
```

---

## 🚀 Quickstart

### 1. Initialize Client

```php
use Dcd\Storage\StorageClient;

// Configure via constructor or auto-load from DCD_STORAGE_ENDPOINT and DCD_STORAGE_API_KEY environment variables
$client = new StorageClient([
    'endpoint' => 'https://storage.yourdomain.com', // or 'http://192.168.1.50:7100'
    'apiKey'   => 'dcd_sk_live_...',
]);
```

---

### 2. Uploading Assets

```php
// Upload from file path
$asset = $client->upload('/path/to/banner.png', [
    'folder'    => 'marketing',
    'overwrite' => true,
]);

echo "Uploaded: " . $asset->publicId . "\n";
echo "CDN URL:   " . $asset->url . "\n";

// Upload from raw binary string (e.g. from file_get_contents or memory)
$rawImage = file_get_contents('https://example.com/logo.png');
$asset = $client->upload($rawImage, [
    'folder'   => 'logos',
    'filename' => 'partner.png',
]);

// Upload from stream / file resource
$stream = fopen('/path/to/archive.zip', 'rb');
$asset = $client->upload($stream, [
    'folder' => 'backups',
    'access' => 'private', // private access
]);
```

---

### 3. Dynamic On-the-Fly Image CDN URLs

Generate CDN transformation URLs without server re-encoding:

```php
// Fluent Builder Style
$avatarUrl = $client->url('avatars/user-42.jpg')
    ->resize(300, 300, 'fill')
    ->format('webp')
    ->quality(85)
    ->blur(2)
    ->build();

// Direct Options Style
$thumbUrl = $client->url('products/shoe.jpg', [
    'width'   => 400,
    'height'  => 400,
    'crop'    => 'thumb',
    'format'  => 'avif',
    'quality' => 80,
]);

// Fast direct public URL without transformations
$publicUrl = $client->getPublicUrl('docs/whitepaper.pdf');
```

---

### 4. Signed Download URLs for Private Files

```php
// 5-minute temporary link for KYC documents or private invoices
$signedUrl = $client->getSignedUrl('invoices/inv_2026.pdf', [
    'expiresInSeconds' => 300,
    'resourceType'     => 'raw',
]);
```

---

### 5. Client-Side Upload Tokens (Browser / Mobile)

Safely allow frontend browser uploads directly to the VPS storage container without exposing secret API keys:

```php
// In your backend API controller (e.g. Laravel):
$tokenInfo = $client->createSignedUploadToken([
    'folder'           => 'user-uploads',
    'maxBytes'         => 10 * 1024 * 1024, // 10MB
    'expiresInSeconds' => 600,
]);

return response()->json([
    'uploadUrl' => $tokenInfo->uploadUrl,
    'token'     => $tokenInfo->token,
]);
```

---

### 6. Asset Management & Cloudinary-Compatible Namespaces

```php
// Get metadata
$asset = $client->getResource('avatars/user-42.jpg');

// List files with pagination
$result = $client->listFiles([
    'folder' => 'avatars',
    'limit'  => 25,
    'page'   => 1,
]);

foreach ($result->files as $file) {
    echo "{$file->publicId} ({$file->bytes} bytes)\n";
}

// Delete asset
$client->delete('avatars/old-avatar.jpg');

// Cloudinary-compatible syntax:
$client->uploader->upload('/path/to/img.png', ['folder' => 'test']);
$client->uploader->destroy('test/img');
$client->api->usage();
```

---

## 📄 License

MIT License. See [LICENSE](LICENSE) for details.

