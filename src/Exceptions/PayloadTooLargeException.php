<?php

declare(strict_types=1);

namespace Dcd\Storage\Exceptions;

use Throwable;

class PayloadTooLargeException extends StorageException
{
    public function __construct(
        string $message = 'File payload exceeds maximum permitted size',
        int $statusCode = 413,
        mixed $details = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, 'PAYLOAD_TOO_LARGE', $details, $previous);
    }
}

