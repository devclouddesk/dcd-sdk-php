<?php

declare(strict_types=1);

namespace Dcd\Storage\Exceptions;

use Exception;
use Throwable;

class StorageException extends Exception
{
    protected ?int $statusCode;
    protected ?string $errorCode;
    protected mixed $details;

    public function __construct(
        string $message = '',
        ?int $statusCode = null,
        ?string $errorCode = null,
        mixed $details = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode ?? 0, $previous);
        $this->statusCode = $statusCode;
        $this->errorCode = $errorCode;
        $this->details = $details;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function getDetails(): mixed
    {
        return $this->details;
    }
}

