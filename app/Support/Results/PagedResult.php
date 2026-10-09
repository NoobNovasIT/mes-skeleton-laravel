<?php

namespace App\Support\Results;

/** = Mes.Common.Results.PagedResult<T>. */
final class PagedResult
{
    /** @param array<int, mixed> $items */
    public function __construct(
        public readonly array $items,
        public readonly int $totalCount,
        public readonly int $pageNumber,
        public readonly int $pageSize,
    ) {
    }

    public function totalPageCount(): int
    {
        return $this->pageSize <= 0 ? 0 : (int) ceil($this->totalCount / $this->pageSize);
    }

    public function map(callable $mapItem): self
    {
        return new self(array_map($mapItem, $this->items), $this->totalCount, $this->pageNumber, $this->pageSize);
    }
}
