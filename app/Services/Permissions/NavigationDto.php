<?php

namespace App\Services\Permissions;

final class NavigationDto
{
    /**
     * @param list<NavigationNodeDto> $modules              Module hiển thị được (đã lọc theo quyền)
     * @param array<int, NavigationNodeDto> $nodeByMenuId   Mọi node user truy cập được, kể cả Page ẩn khỏi menu
     */
    public function __construct(
        public readonly array $modules,
        public readonly array $nodeByMenuId,
    ) {
    }

    public static function empty(): self
    {
        return new self([], []);
    }
}
