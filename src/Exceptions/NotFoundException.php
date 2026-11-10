<?php

declare(strict_types=1);

namespace Dcd\Storage\Exceptions;

use Throwable;

class NotFoundException extends StorageException
{
    public function __construct(
        string $message = 'Resource not found',
        int $statusCode = 404,
        mixed $details = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, 'NOT_FOUND', $details, $previous);
    }
}

