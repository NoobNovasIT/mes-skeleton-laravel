<?php

namespace App\Http\Navigation;

use App\Services\Permissions\NavigationDto;
use App\Services\Permissions\NavigationNodeDto;

/** = Mes.Web/Infrastructure/Navigation/PageNavigationContext.cs: Module / Page đang mở + đường dẫn tới gốc. */
final class PageNavigationContext
{
    /** @param list<NavigationNodeDto> $activePath  Từ Module xuống Page đang mở */
    public function __construct(
        public readonly NavigationDto $navigation,
        public readonly ?NavigationNodeDto $activeModule,
        public readonly array $activePath,
    ) {
    }

    public function activePage(): ?NavigationNodeDto
    {
        return $this->activePath === [] ? null : $this->activePath[array_key_last($this->activePath)];
    }

    public function isInActivePath(int $menuId): bool
    {
        foreach ($this->activePath as $node) {
            if ($node->menuId === $menuId) {
                return true;
            }
        }

        return false;
    }
}
