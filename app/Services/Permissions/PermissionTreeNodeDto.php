<?php

namespace App\Services\Permissions;

use App\Support\Enums\AccessLevel;
use App\Support\Enums\MenuType;

/** Node trên cây phân quyền (Role Permissions / Permission overrides). */
final class PermissionTreeNodeDto
{
    /** @var list<PermissionTreeNodeDto> */
    public array $children = [];

    public function __construct(
        public readonly int $menuId,
        public readonly ?int $parentMenuId,
        public readonly string $menuCode,
        public readonly MenuType $menuType,
        public readonly string $displayName,
        public readonly ?string $iconClass,
        public readonly bool $isActive,
        public readonly AccessLevel $roleAccessLevel,
        public readonly ?AccessLevel $overrideAccessLevel = null,
        public readonly ?string $overrideReason = null,
    ) {
    }
}
