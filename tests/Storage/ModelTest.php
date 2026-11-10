<?php

declare(strict_types=1);

namespace Dcd\Storage\Tests;

use Dcd\Storage\Models\AssetResult;
use Dcd\Storage\Models\FileListResult;
use Dcd\Storage\Models\HealthCheckResult;
use Dcd\Storage\Models\Pagination;
use Dcd\Storage\Models\ServerStats;
use Dcd\Storage\Models\SignedUploadResult;
use PHPUnit\Framework\TestCase;

class ModelTest extends TestCase
{
    public function testAssetResultInstantiationAndFieldAccess(): void
    {
        $raw = [
            'id' => 'ast_123',
            'public_id' => 'avatars/bob.png',
            'folder' => 'avatars',
            'version' => 2,
            'filename' => 'bob.png',
            'original_filename' => 'original-bob.png',
            'format' => 'png',
            'mime_type' => 'image/png',
            'resource_type' => 'image',
            'width' => 600,
            'height' => 600,
            'bytes' => 102400,
            'etag' => 'tag-abc',
            'tags' => ['avatar', 'profile'],
            'access' => 'public',
            'url' => 'http://cdn.example.com/image/upload/avatars/bob.png',
            'secure_url' => 'https://cdn.example.com/image/upload/avatars/bob.png',
        ];

        $asset = new AssetResult($raw);

        $this->assertEquals('ast_123', $asset->id);
        $this->assertEquals('avatars/bob.png', $asset->publicId);
        $this->assertEquals('avatars', $asset->folder);
        $this->assertEquals(2, $asset->version);
        $this->assertEquals('bob.png', $asset->filename);
        $this->assertEquals('original-bob.png', $asset->originalFilename);
        $this->assertEquals('png', $asset->format);
        $this->assertEquals('image/png', $asset->mimeType);
        $this->assertEquals(600, $asset->width);
        $this->assertEquals(600, $asset->height);
        $this->assertEquals(102400, $asset->bytes);
        $this->assertEquals(['avatar', 'profile'], $asset->tags);
        $this->assertEquals('https://cdn.example.com/image/upload/avatars/bob.png', $asset->secureUrl);
    }

    public function testAssetResultArrayAccessAndSerialization(): void
    {
        $asset = new AssetResult([
            'publicId' => 'docs/sheet.xlsx',
            'bytes' => 50000,
        ]);

        // ArrayAccess
        $this->assertTrue(isset($asset['publicId']));
        $this->assertEquals('docs/sheet.xlsx', $asset['publicId']);
        $this->assertEquals(50000, $asset['bytes']);

        $asset['custom'] = 'custom_val';
        $this->assertEquals('custom_val', $asset['custom']);

        unset($asset['custom']);
        $this->assertFalse(isset($asset['custom']));

        // JsonSerializable
        $json = json_encode($asset);
        $this->assertIsString($json);
        $this->assertStringContainsString('docs/sheet.xlsx', $json);
    }

    public function testHealthCheckResult(): void
    {
        $ok = new HealthCheckResult(['status' => 'ok', 'uptime' => 12345, 'version' => '1.2.0']);
        $this->assertTrue($ok->isHealthy());
        $this->assertEquals('ok', $ok->status);
        $this->assertEquals(12345, $ok->uptime);
        $this->assertEquals('1.2.0', $ok->version);

        $healthy = new HealthCheckResult(['status' => 'healthy']);
        $this->assertTrue($healthy->isHealthy());

        $degraded = new HealthCheckResult(['status' => 'degraded']);
        $this->assertFalse($degraded->isHealthy());
    }

    public function testPagination(): void
    {
        $pag = new Pagination([
            'page' => 2,
            'limit' => 25,
            'total' => 100,
            'total_pages' => 4,
            'has_next' => true,
            'has_prev' => true,
        ]);

        $this->assertEquals(2, $pag->page);
        $this->assertEquals(25, $pag->limit);
        $this->assertEquals(100, $pag->total);
        $this->assertEquals(4, $pag->totalPages);
        $this->assertTrue($pag->hasNext);
        $this->assertTrue($pag->hasPrev);

        $array = $pag->jsonSerialize();
        $this->assertEquals(2, $array['page']);
        $this->assertEquals(100, $array['total']);
    }

    public function testFileListResult(): void
    {
        $data = [
            'success' => true,
            'files' => [
                ['publicId' => 'file1.jpg', 'bytes' => 1000],
                ['publicId' => 'file2.jpg', 'bytes' => 2000],
            ],
            'pagination' => [
                'page' => 1,
                'limit' => 10,
                'total' => 2,
            ],
        ];

        $result = new FileListResult($data);

        $this->assertTrue($result->success);
        $this->assertCount(2, $result->files);
        $this->assertInstanceOf(AssetResult::class, $result->files[0]);
        $this->assertEquals('file1.jpg', $result->files[0]->publicId);
        $this->assertInstanceOf(Pagination::class, $result->pagination);
        $this->assertEquals(1, $result->pagination->page);
        $this->assertEquals(2, $result->pagination->total);
    }

    public function testServerStats(): void
    {
        $stats = new ServerStats([
            'files_count' => 1500,
            'total_storage_bytes' => 1073741824,
            'cache_variants_count' => 450,
            'cache_size_bytes' => 52428800,
            'uptime_seconds' => 86400,
            'memory_usage_mb' => 128.5,
            'storage_driver' => 's3',
        ]);

        $this->assertEquals(1500, $stats->filesCount);
        $this->assertEquals(1073741824, $stats->totalStorageBytes);
        $this->assertEquals(450, $stats->cacheVariantsCount);
        $this->assertEquals(52428800, $stats->cacheSizeBytes);
        $this->assertEquals(86400, $stats->uptimeSeconds);
        $this->assertEquals(128.5, $stats->memoryUsageMb);
        $this->assertEquals('s3', $stats->storageDriver);
    }

    public function testSignedUploadResult(): void
    {
        $signed = new SignedUploadResult([
            'success' => true,
            'token' => 'tok_secure_123',
            'upload_url' => 'https://storage.devclouddesk.com/v1/upload',
            'expires_in_seconds' => 900,
            'folder' => 'invoices',
            'max_bytes' => 10485760,
            'access' => 'private',
        ]);

        $this->assertTrue($signed->success);
        $this->assertEquals('tok_secure_123', $signed->token);
        $this->assertEquals('https://storage.devclouddesk.com/v1/upload', $signed->uploadUrl);
        $this->assertEquals(900, $signed->expiresInSeconds);
        $this->assertEquals('invoices', $signed->folder);
        $this->assertEquals(10485760, $signed->maxBytes);
        $this->assertEquals('private', $signed->access);

        // ArrayAccess
        $this->assertEquals('tok_secure_123', $signed['token']);
        $this->assertTrue(isset($signed['upload_url']));
    }
}

