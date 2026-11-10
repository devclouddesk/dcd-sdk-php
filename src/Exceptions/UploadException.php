<?php

declare(strict_types=1);

namespace Dcd\Storage\Exceptions;

use Throwable;

class UploadException extends StorageException
{
    public function __construct(
        string $message = 'Upload failed',
        ?int $statusCode = null,
        mixed $details = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, 'UPLOAD_ERROR', $details, $previous);
    }
}

