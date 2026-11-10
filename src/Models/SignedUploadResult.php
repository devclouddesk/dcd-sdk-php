<?php

declare(strict_types=1);

namespace Dcd\Storage\Models;

use ArrayAccess;
use JsonSerializable;

class SignedUploadResult implements ArrayAccess, JsonSerializable
{
    public bool $success;
    public string $token;
    public string $uploadUrl;
    public int $expiresInSeconds;
    public ?string $folder;
    public ?int $maxBytes;
    public ?string $access;
    public ?string $webhookUrl;
    protected array $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
        $this->success = (bool)($data['success'] ?? true);
        $this->token = (string)($data['token'] ?? '');
        $this->uploadUrl = (string)($data['uploadUrl'] ?? $data['upload_url'] ?? '');
        $this->expiresInSeconds = (int)($data['expiresInSeconds'] ?? $data['expires_in_seconds'] ?? 300);
        $this->folder = $data['folder'] ?? null;
        $this->maxBytes = isset($data['maxBytes']) ? (int)$data['maxBytes'] : (isset($data['max_bytes']) ? (int)$data['max_bytes'] : null);
        $this->access = $data['access'] ?? null;
        $this->webhookUrl = $data['webhookUrl'] ?? $data['webhook_url'] ?? null;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function jsonSerialize(): array
    {
        return $this->data;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->data[$offset]) || property_exists($this, (string)$offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[$offset] ?? ($this->{(string)$offset} ?? null);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->data[$offset]);
    }
}

