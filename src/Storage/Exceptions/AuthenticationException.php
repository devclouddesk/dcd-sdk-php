<?php

declare(strict_types=1);

namespace Dcd\Storage\Exceptions;

use Throwable;

class AuthenticationException extends StorageException
{
    public function __construct(
        string $message = 'Authentication failed: Invalid or missing API key',
        int $statusCode = 401,
        mixed $details = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, 'AUTH_ERROR', $details, $previous);
    }
}

