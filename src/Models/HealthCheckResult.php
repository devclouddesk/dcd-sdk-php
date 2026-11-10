<?php

declare(strict_types=1);

namespace Dcd\Storage\Models;

use JsonSerializable;

class HealthCheckResult implements JsonSerializable
{
    public string $status;
    public ?int $uptime;
    public mixed $timestamp;
    public ?string $version;
    protected array $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
        $this->status = (string)($data['status'] ?? 'unknown');
        $this->uptime = isset($data['uptime']) ? (int)$data['uptime'] : null;
        $this->timestamp = $data['timestamp'] ?? null;
        $this->version = $data['version'] ?? null;
    }

    public function isHealthy(): bool
    {
        return in_array(strtolower($this->status), ['ok', 'healthy'], true);
    }

    public function jsonSerialize(): array
    {
        return $this->data;
    }
}

