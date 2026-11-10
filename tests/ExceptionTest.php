<?php

declare(strict_types=1);

namespace Dcd\Storage\Tests;

use Dcd\Storage\Exceptions\AuthenticationException;
use Dcd\Storage\Exceptions\NetworkTimeoutException;
use Dcd\Storage\Exceptions\NotFoundException;
use Dcd\Storage\Exceptions\PayloadTooLargeException;
use Dcd\Storage\Exceptions\StorageException;
use Dcd\Storage\Exceptions\UploadException;
use Exception;
use PHPUnit\Framework\TestCase;

class ExceptionTest extends TestCase
{
    public function testStorageExceptionBaseProperties(): void
    {
        $ex = new StorageException('General storage error', 500, 'INTERNAL_ERROR', ['trace_id' => '12345']);

        $this->assertInstanceOf(Exception::class, $ex);
        $this->assertEquals('General storage error', $ex->getMessage());
        $this->assertEquals(500, $ex->getStatusCode());
        $this->assertEquals('INTERNAL_ERROR', $ex->getErrorCode());
        $this->assertEquals(['trace_id' => '12345'], $ex->getDetails());
    }

    public function testAuthenticationExceptionDefaults(): void
    {
        $ex = new AuthenticationException();

        $this->assertInstanceOf(StorageException::class, $ex);
        $this->assertEquals(401, $ex->getStatusCode());
        $this->assertEquals('AUTH_ERROR', $ex->getErrorCode());
        $this->assertStringContainsString('Authentication failed', $ex->getMessage());
    }

    public function testNotFoundExceptionDefaults(): void
    {
        $ex = new NotFoundException('Asset photo.png not found');

        $this->assertInstanceOf(StorageException::class, $ex);
        $this->assertEquals(404, $ex->getStatusCode());
        $this->assertEquals('NOT_FOUND', $ex->getErrorCode());
        $this->assertEquals('Asset photo.png not found', $ex->getMessage());
    }

    public function testPayloadTooLargeExceptionDefaults(): void
    {
        $ex = new PayloadTooLargeException('Max file size is 10MB');

        $this->assertInstanceOf(StorageException::class, $ex);
        $this->assertEquals(413, $ex->getStatusCode());
        $this->assertEquals('PAYLOAD_TOO_LARGE', $ex->getErrorCode());
        $this->assertEquals('Max file size is 10MB', $ex->getMessage());
    }

    public function testNetworkTimeoutExceptionDefaults(): void
    {
        $ex = new NetworkTimeoutException('Connection timed out after 30s');

        $this->assertInstanceOf(StorageException::class, $ex);
        $this->assertEquals(408, $ex->getStatusCode());
        $this->assertEquals('REQUEST_TIMEOUT', $ex->getErrorCode());
    }

    public function testUploadExceptionDefaults(): void
    {
        $ex = new UploadException('Upload stream terminated unexpectedly', 502);

        $this->assertInstanceOf(StorageException::class, $ex);
        $this->assertEquals(502, $ex->getStatusCode());
        $this->assertEquals('UPLOAD_ERROR', $ex->getErrorCode());
    }
}

