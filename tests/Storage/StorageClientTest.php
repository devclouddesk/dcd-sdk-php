<?php

declare(strict_types=1);

namespace Dcd\Storage\Tests;

use Dcd\Storage\Exceptions\AuthenticationException;
use Dcd\Storage\StorageClient;
use Dcd\Storage\TransformationBuilder;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class StorageClientTest extends TestCase
{
    public function testInstantiationWithArrayConfig(): void
    {
        $client = new StorageClient([
            'endpoint' => 'https://storage.devclouddesk.com',
            'apiKey' => 'dcd_test_key',
            'timeout' => 45,
            'deliveryPrefix' => 'media/view',
        ]);

        $this->assertEquals(
            'https://storage.devclouddesk.com/media/view/banner.png',
            $client->getPublicUrl('banner.png')
        );
    }

    public function testInstantiationWithSeparateArgs(): void
    {
        $client = new StorageClient('https://storage.devclouddesk.com', 'dcd_test_key', 60, 'custom/prefix');

        $this->assertEquals(
            'https://storage.devclouddesk.com/custom/prefix/logo.svg',
            $client->getPublicUrl('/logo.svg')
        );
    }

    public function testInstantiationWithoutEndpointThrowsException(): void
    {
        // Ensure env var is not set during test
        $oldEnv = getenv('DCD_STORAGE_ENDPOINT');
        putenv('DCD_STORAGE_ENDPOINT');

        try {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('StorageClient: endpoint is required');
            new StorageClient();
        } finally {
            if ($oldEnv !== false) {
                putenv("DCD_STORAGE_ENDPOINT={$oldEnv}");
            }
        }
    }

    public function testGetPublicUrl(): void
    {
        $client = new StorageClient('https://storage.example.com', 'dcd_key');

        $this->assertEquals(
            'https://storage.example.com/image/upload/docs/report.pdf',
            $client->getPublicUrl('docs/report.pdf')
        );
    }

    public function testGetPublicUrlStripsLeadingSlashes(): void
    {
        $client = new StorageClient('https://storage.example.com', 'dcd_key');

        $this->assertEquals(
            'https://storage.example.com/image/upload/deep/nested/path/file.jpg',
            $client->getPublicUrl('///deep/nested/path/file.jpg')
        );
    }

    public function testUrlReturnsTransformationBuilder(): void
    {
        $client = new StorageClient('https://cdn.example.com', 'test_key');
        $builder = $client->url('avatars/u1.jpg');

        $this->assertInstanceOf(TransformationBuilder::class, $builder);
        $this->assertEquals(
            'https://cdn.example.com/image/upload/w_100/avatars/u1.jpg',
            $builder->width(100)->build()
        );
    }

    public function testUrlWithDirectOptionsReturnsString(): void
    {
        $client = new StorageClient('https://cdn.example.com', 'test_key');
        $url = $client->url('products/item.jpg', [
            'width' => 200,
            'height' => 200,
            'crop' => 'thumb',
            'format' => 'png',
        ]);

        $this->assertEquals(
            'https://cdn.example.com/image/upload/w_200,h_200,c_thumb,f_png/products/item.png',
            $url
        );
    }

    public function testCloudinaryCompatibleNamespaces(): void
    {
        $client = new StorageClient('https://cdn.example.com', 'test_key');

        $this->assertIsObject($client->uploader);
        $this->assertIsObject($client->api);
        $this->assertTrue(method_exists($client->uploader, 'upload'));
        $this->assertTrue(method_exists($client->uploader, 'destroy'));
        $this->assertTrue(method_exists($client->uploader, 'delete'));
        $this->assertTrue(method_exists($client->api, 'resource'));
        $this->assertTrue(method_exists($client->api, 'resources'));
        $this->assertTrue(method_exists($client->api, 'usage'));
    }

    public function testGetSignedUrlWithoutApiKeyThrowsAuthenticationException(): void
    {
        $oldKey = getenv('DCD_STORAGE_API_KEY');
        $oldApiKey = getenv('DCD_API_KEY');
        putenv('DCD_STORAGE_API_KEY');
        putenv('DCD_API_KEY');

        try {
            $client = new StorageClient('https://cdn.example.com', '');
            $this->expectException(AuthenticationException::class);
            $this->expectExceptionMessage('API key is required to generate signed download URLs.');
            $client->getSignedUrl('invoices/inv-001.pdf');
        } finally {
            if ($oldKey !== false) putenv("DCD_STORAGE_API_KEY={$oldKey}");
            if ($oldApiKey !== false) putenv("DCD_API_KEY={$oldApiKey}");
        }
    }

    public function testCreateSignedUploadTokenWithoutApiKeyThrowsAuthenticationException(): void
    {
        $oldKey = getenv('DCD_STORAGE_API_KEY');
        $oldApiKey = getenv('DCD_API_KEY');
        putenv('DCD_STORAGE_API_KEY');
        putenv('DCD_API_KEY');

        try {
            $client = new StorageClient('https://cdn.example.com', '');
            $this->expectException(AuthenticationException::class);
            $this->expectExceptionMessage('API key is required on backend to generate signed upload tokens.');
            $client->createSignedUploadToken(['folder' => 'uploads']);
        } finally {
            if ($oldKey !== false) putenv("DCD_STORAGE_API_KEY={$oldKey}");
            if ($oldApiKey !== false) putenv("DCD_API_KEY={$oldApiKey}");
        }
    }

    public function testUploadWithoutApiKeyOrTokenThrowsAuthenticationException(): void
    {
        $oldKey = getenv('DCD_STORAGE_API_KEY');
        $oldApiKey = getenv('DCD_API_KEY');
        putenv('DCD_STORAGE_API_KEY');
        putenv('DCD_API_KEY');

        try {
            $client = new StorageClient('https://cdn.example.com', '');
            $this->expectException(AuthenticationException::class);
            $this->expectExceptionMessage('Either apiKey or a signed upload token must be provided to upload.');
            $client->upload('fake-file.jpg');
        } finally {
            if ($oldKey !== false) putenv("DCD_STORAGE_API_KEY={$oldKey}");
            if ($oldApiKey !== false) putenv("DCD_API_KEY={$oldApiKey}");
        }
    }
}
