<?php

namespace App\Services\Permissions;

use App\Support\Enums\AccessLevel;
use App\Support\Enums\MenuType;

/** Một node menu của user hiện tại (đã tính quyền). */
final class NavigationNodeDto
{
    /** @var list<NavigationNodeDto> */
    public array $children = [];

    public function __construct(
        public readonly int $menuId,
        public readonly ?int $parentMenuId,
        public readonly string $menuCode,
        public readonly MenuType $menuType,
        public readonly string $displayName,
        public readonly ?string $url,
        public readonly ?string $iconClass,
        public readonly AccessLevel $accessLevel,
        public readonly bool $isVisibleInNavigation,
    ) {
    }

    /** URL Page đầu tiên (theo thứ tự) trong nhánh - link của tab Module. */
    public function findFirstPageUrl(): ?string
    {
        if (! empty($this->url)) {
            return $this->url;
        }

        foreach ($this->children as $childNode) {
            $pageUrl = $childNode->findFirstPageUrl();
            if ($pageUrl !== null) {
                return $pageUrl;
            }
        }

        return null;
    }

    /** Mọi Page ở bất kỳ độ sâu nào (lối tắt ở trang chủ). @return list<NavigationNodeDto> */
    public function listPages(): array
    {
        if ($this->menuType === MenuType::Page) {
            return [$this];
        }

        return array_merge([], ...array_map(fn (NavigationNodeDto $childNode) => $childNode->listPages(), $this->children));
    }
}
