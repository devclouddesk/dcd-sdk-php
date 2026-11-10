<?php

declare(strict_types=1);

namespace Dcd\Storage\Exceptions;

use Throwable;

class NetworkTimeoutException extends StorageException
{
    public function __construct(
        string $message = 'Storage request timed out',
        int $statusCode = 408,
        mixed $details = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, 'REQUEST_TIMEOUT', $details, $previous);
    }
}

