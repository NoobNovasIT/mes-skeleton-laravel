<?php

namespace App\Services\Permissions;

use App\Services\Caching\UserScopedCache;
use App\Services\Database\StoredProcedureExecutor;
use App\Support\Enums\AccessLevel;
use App\Support\Enums\MenuType;

/**
 * = Mes.Service/Permissions/PermissionService.cs: kiểm quyền lúc chạy + dựng cây menu của user.
 * Quyền hiệu lực tính trong SP usp_Security_Menu_GetNavigationByUserAccountId (override > MAX Role > NoAccess).
 */
final class PermissionService
{
    private const AccessLevelLookupLanguageCode = 'en';

    public function __construct(
        private readonly UserScopedCache $userScopedCache,
    ) {
    }

    public function getAccessLevel(int $userAccountId, string $menuCode): AccessLevel
    {
        /** @var array<string, int> $accessLevelByMenuCode */
        $accessLevelByMenuCode = $this->userScopedCache->remember($userAccountId, 'AccessLevelByMenuCode', function () use ($userAccountId) {
            $accessLevels = [];
            foreach ($this->listNavigationRecords($userAccountId, self::AccessLevelLookupLanguageCode) as $record) {
                if ((int) $record->MenuType === MenuType::Page->value) {
                    $accessLevels[strtoupper($record->MenuCode)] = (int) $record->EffectiveAccessLevel;
                }
            }

            return $accessLevels;
        });

        return AccessLevel::tryFrom($accessLevelByMenuCode[strtoupper($menuCode)] ?? 0) ?? AccessLevel::NoAccess;
    }

    public function getNavigation(int $userAccountId, string $languageCode): NavigationDto
    {
        return $this->userScopedCache->remember(
            $userAccountId,
            'Navigation:'.$languageCode,
            fn () => $this->buildNavigation($this->listNavigationRecords($userAccountId, $languageCode)));
    }

    /** @return list<\stdClass> */
    private function listNavigationRecords(int $userAccountId, string $languageCode): array
    {
        return StoredProcedureExecutor::query(
            StoredProcedureExecutor::SecurityMenuGetNavigationByUserAccountId,
            ['UserAccountId' => $userAccountId, 'LanguageCode' => $languageCode]);
    }

    /** @param list<\stdClass> $navigationRecords */
    private function buildNavigation(array $navigationRecords): NavigationDto
    {
        if ($navigationRecords === []) {
            return NavigationDto::empty();
        }

        $allNodeByMenuId = [];
        foreach ($navigationRecords as $record) {
            $allNodeByMenuId[(int) $record->Id] = new NavigationNodeDto(
                menuId: (int) $record->Id,
                parentMenuId: $record->ParentMenuId === null ? null : (int) $record->ParentMenuId,
                menuCode: $record->MenuCode,
                menuType: MenuType::from((int) $record->MenuType),
                displayName: $record->DisplayName,
                url: $record->Url,
                iconClass: $record->IconClass,
                accessLevel: AccessLevel::from((int) $record->EffectiveAccessLevel),
                isVisibleInNavigation: (bool) $record->IsVisibleInNavigation,
            );
        }

        $rootNodes = [];
        foreach ($allNodeByMenuId as $node) {
            if ($node->parentMenuId === null) {
                $rootNodes[] = $node;
            } elseif (isset($allNodeByMenuId[$node->parentMenuId])) {
                $allNodeByMenuId[$node->parentMenuId]->children[] = $node;
            }
            // Cha không active -> cả nhánh con bị ẩn theo.
        }

        $accessibleNodeByMenuId = [];
        $visibleModules = [];
        foreach ($rootNodes as $rootNode) {
            if ($rootNode->menuType === MenuType::Module) {
                $visibleModule = $this->pruneNode($rootNode, $accessibleNodeByMenuId);
                if ($visibleModule !== null) {
                    $visibleModules[] = $visibleModule;
                }
            }
        }

        return new NavigationDto($visibleModules, $accessibleNodeByMenuId);
    }

    /**
     * Bỏ Page NoAccess, bỏ Module / Group không còn Page con nào được phép.
     * Page ẩn khỏi menu (IsVisibleInNavigation = 0) vẫn nằm trong $accessibleNodeByMenuId để có breadcrumb.
     *
     * @param array<int, NavigationNodeDto> $accessibleNodeByMenuId
     */
    private function pruneNode(NavigationNodeDto $node, array &$accessibleNodeByMenuId): ?NavigationNodeDto
    {
        if ($node->menuType === MenuType::Page) {
            if ($node->accessLevel === AccessLevel::NoAccess) {
                return null;
            }
            $accessibleNodeByMenuId[$node->menuId] = $node;

            return $node->isVisibleInNavigation ? $node : null;
        }

        $visibleChildren = [];
        $hasAccessibleDescendant = false;
        foreach ($node->children as $childNode) {
            $visibleChild = $this->pruneNode($childNode, $accessibleNodeByMenuId);
            if ($visibleChild !== null) {
                $visibleChildren[] = $visibleChild;
            }
            $hasAccessibleDescendant = $hasAccessibleDescendant || isset($accessibleNodeByMenuId[$childNode->menuId]);
        }

        if (! $hasAccessibleDescendant) {
            return null;
        }

        $accessibleNodeByMenuId[$node->menuId] = $node;
        $node->children = $visibleChildren;

        return $visibleChildren !== [] && $node->isVisibleInNavigation ? $node : null;
    }
}
