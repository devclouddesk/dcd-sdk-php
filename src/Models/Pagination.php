<?php

declare(strict_types=1);

namespace Dcd\Storage\Models;

use JsonSerializable;

class Pagination implements JsonSerializable
{
    public int $page;
    public int $limit;
    public int $total;
    public int $totalPages;
    public bool $hasNext;
    public bool $hasPrev;

    public function __construct(array $data = [])
    {
        $this->page = (int)($data['page'] ?? 1);
        $this->limit = (int)($data['limit'] ?? 20);
        $this->total = (int)($data['total'] ?? 0);
        $this->totalPages = (int)($data['totalPages'] ?? $data['total_pages'] ?? 1);
        $this->hasNext = (bool)($data['hasNext'] ?? $data['has_next'] ?? false);
        $this->hasPrev = (bool)($data['hasPrev'] ?? $data['has_prev'] ?? false);
    }

    public function jsonSerialize(): array
    {
        return [
            'page' => $this->page,
            'limit' => $this->limit,
            'total' => $this->total,
            'totalPages' => $this->totalPages,
            'hasNext' => $this->hasNext,
            'hasPrev' => $this->hasPrev,
        ];
    }
}

