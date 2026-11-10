<?php

declare(strict_types=1);

namespace Dcd\Storage\Models;

use JsonSerializable;

class ServerStats implements JsonSerializable
{
    public int $filesCount;
    public int $totalStorageBytes;
    public int $cacheVariantsCount;
    public int $cacheSizeBytes;
    public int $uptimeSeconds;
    public float $memoryUsageMb;
    public string $storageDriver;
    protected array $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
        $this->filesCount = (int)($data['filesCount'] ?? $data['files_count'] ?? 0);
        $this->totalStorageBytes = (int)($data['totalStorageBytes'] ?? $data['total_storage_bytes'] ?? 0);
        $this->cacheVariantsCount = (int)($data['cacheVariantsCount'] ?? $data['cache_variants_count'] ?? 0);
        $this->cacheSizeBytes = (int)($data['cacheSizeBytes'] ?? $data['cache_size_bytes'] ?? 0);
        $this->uptimeSeconds = (int)($data['uptimeSeconds'] ?? $data['uptime_seconds'] ?? 0);
        $this->memoryUsageMb = (float)($data['memoryUsageMb'] ?? $data['memory_usage_mb'] ?? 0.0);
        $this->storageDriver = (string)($data['storageDriver'] ?? $data['storage_driver'] ?? 'local');
    }

    public function jsonSerialize(): array
    {
        return $this->data;
    }
}

