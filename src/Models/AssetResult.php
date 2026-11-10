<?php

declare(strict_types=1);

namespace Dcd\Storage\Models;

use ArrayAccess;
use JsonSerializable;

/**
 * Represents a storage asset result returned by upload, getFile, and listFiles.
 * Implements ArrayAccess & JsonSerializable for intuitive $asset->publicId or $asset['publicId'] usage.
 */
class AssetResult implements ArrayAccess, JsonSerializable
{
    public ?string $id;
    public string $publicId;
    public ?string $folder;
    public ?int $version;
    public ?string $filename;
    public ?string $originalFilename;
    public string $format;
    public ?string $mimeType;
    public ?string $resourceType;
    public ?int $width;
    public ?int $height;
    public int $bytes;
    public ?string $etag;
    /** @var string[] */
    public array $tags = [];
    public string $access;
    public string $url;
    public string $secureUrl;
    public ?string $rawUrl;
    public mixed $createdAt;
    public mixed $updatedAt;
    /** @var array<string, mixed> Raw server response */
    protected array $data = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
        $this->id = $data['id'] ?? $data['asset_id'] ?? null;
        $this->publicId = (string)($data['publicId'] ?? $data['public_id'] ?? '');
        $this->folder = $data['folder'] ?? null;
        $this->version = isset($data['version']) ? (int)$data['version'] : null;
        $this->filename = $data['filename'] ?? null;
        $this->originalFilename = $data['originalFilename'] ?? $data['original_filename'] ?? null;
        $this->format = (string)($data['format'] ?? '');
        $this->mimeType = $data['mimeType'] ?? $data['mime_type'] ?? null;
        $this->resourceType = $data['resourceType'] ?? $data['resource_type'] ?? 'image';
        $this->width = isset($data['width']) ? (int)$data['width'] : null;
        $this->height = isset($data['height']) ? (int)$data['height'] : null;
        $this->bytes = (int)($data['bytes'] ?? 0);
        $this->etag = $data['etag'] ?? null;
        $this->tags = (array)($data['tags'] ?? []);
        $this->access = (string)($data['access'] ?? 'public');
        $this->url = (string)($data['url'] ?? '');
        $this->secureUrl = (string)($data['secureUrl'] ?? $data['secure_url'] ?? $this->url);
        $this->rawUrl = $data['rawUrl'] ?? $data['raw_url'] ?? null;
        $this->createdAt = $data['createdAt'] ?? $data['created_at'] ?? null;
        $this->updatedAt = $data['updatedAt'] ?? $data['updated_at'] ?? null;
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
        if (isset($this->data[$offset])) {
            return $this->data[$offset];
        }
        $prop = (string)$offset;
        if (property_exists($this, $prop)) {
            return $this->$prop;
        }
        return null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->data[$offset] = $value;
        $prop = (string)$offset;
        if (property_exists($this, $prop)) {
            $this->$prop = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->data[$offset]);
    }
}

