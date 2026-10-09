<?php

namespace App\Services\Menus;

use App\Support\Enums\MenuType;

final class MenuTreeNodeDto
{
    /** @var list<MenuTreeNodeDto> */
    public array $children = [];

    public function __construct(
        public readonly int $menuId,
        public readonly ?int $parentMenuId,
        public readonly string $menuCode,
        public readonly MenuType $menuType,
        public readonly string $displayName,
        public readonly ?string $url,
        public readonly ?string $iconClass,
        public readonly int $sortOrder,
        public readonly bool $isActive,
        public readonly bool $isVisibleInNavigation,
    ) {
    }
}
