<?php

declare(strict_types=1);

namespace Dcd\Storage\Models;

use JsonSerializable;

class FileListResult implements JsonSerializable
{
    public bool $success;
    /** @var AssetResult[] */
    public array $files = [];
    public ?Pagination $pagination = null;

    public function __construct(array $data = [])
    {
        $this->success = (bool)($data['success'] ?? true);

        $rawFiles = $data['data']['files'] ?? $data['files'] ?? [];
        foreach ($rawFiles as $file) {
            $this->files[] = $file instanceof AssetResult ? $file : new AssetResult((array)$file);
        }

        $rawPagination = $data['data']['pagination'] ?? $data['pagination'] ?? null;
        if ($rawPagination) {
            $this->pagination = new Pagination((array)$rawPagination);
        }
    }

    public function jsonSerialize(): array
    {
        return [
            'success' => $this->success,
            'files' => $this->files,
            'pagination' => $this->pagination,
        ];
    }
}

