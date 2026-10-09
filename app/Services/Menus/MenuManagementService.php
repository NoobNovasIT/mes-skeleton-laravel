<?php

namespace App\Services\Menus;

use App\Models\Menu;
use App\Models\MenuTranslation;
use App\Models\Role;
use App\Models\RoleMenuPermission;
use App\Services\Caching\UserScopedCache;
use App\Support\Constants\ErrorMessageKeys;
use App\Support\Enums\AccessLevel;
use App\Support\Enums\MenuType;
use App\Support\Results\OperationResult;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/** = Mes.Service/Menus/MenuManagementService.cs - quản trị cây menu (System > Menus). */
final class MenuManagementService
{
    private const SortOrderStep = 10;

    public const MenuCodeFormatMessage = 'Menu code must be 2-100 characters: uppercase letters, digits or underscore.';
    public const DefaultNameRequiredMessage = 'Default name is required (maximum 100 characters).';
    public const UrlFormatMessage = 'URL must start with "/" and not exceed 300 characters.';
    public const IconClassTooLongMessage = 'Icon class must not exceed 100 characters.';

    public function __construct(
        private readonly UserScopedCache $userScopedCache,
    ) {
    }

    /** @return list<MenuTreeNodeDto> */
    public function getTree(string $languageCode): array
    {
        $flatNodes = $this->listAllMenus()
            ->map(fn (Menu $menu) => new MenuTreeNodeDto(
                $menu->Id,
                $menu->ParentMenuId,
                $menu->MenuCode,
                $menu->MenuType,
                $menu->displayName($languageCode),
                $menu->Url,
                $menu->IconClass,
                $menu->SortOrder,
                $menu->IsActive,
                $menu->IsVisibleInNavigation,
            ))
            ->all();

        return TreeBuilder::build($flatNodes);
    }

    public function getDetail(int $menuId): ?Menu
    {
        return Menu::query()->with('translations')->find($menuId);
    }

    /**
     * @param array{ParentMenuId: ?int, MenuCode: string, MenuType: MenuType, DefaultName: string, Url: ?string,
     *              IconClass: ?string, IsActive: bool, IsVisibleInNavigation: bool, DisplayNameByLanguageCode: array<string, ?string>} $saveMenuRequest
     */
    public function create(array $saveMenuRequest): OperationResult
    {
        $normalizedRequest = $this->normalize($saveMenuRequest);
        $allMenus = $this->listAllMenus();
        $validationFailure = $this->validate($normalizedRequest, null, $allMenus);
        if ($validationFailure !== null) {
            return OperationResult::failure($validationFailure);
        }

        $lastSiblingSortOrder = (int) $allMenus->where('ParentMenuId', $normalizedRequest['ParentMenuId'])->max('SortOrder');

        $newMenu = DB::transaction(function () use ($normalizedRequest, $lastSiblingSortOrder) {
            $newMenu = new Menu(['SortOrder' => $lastSiblingSortOrder + self::SortOrderStep]);
            $this->applyRequest($newMenu, $normalizedRequest);
            $newMenu->save();
            $this->applyTranslations($newMenu, $normalizedRequest['DisplayNameByLanguageCode']);

            if ($newMenu->MenuType === MenuType::Page) {
                $this->grantFullAccessToSystemRoles($newMenu);
            }

            return $newMenu;
        });
        $this->userScopedCache->invalidateAllUsers();

        return OperationResult::success($newMenu->Id);
    }

    public function update(int $menuId, array $saveMenuRequest): OperationResult
    {
        $menu = Menu::query()->with('translations')->find($menuId);
        if ($menu === null) {
            return OperationResult::failure(ErrorMessageKeys::RecordNotFound);
        }

        $normalizedRequest = $this->normalize($saveMenuRequest);
        $allMenus = $this->listAllMenus();
        $validationFailure = $this->validate($normalizedRequest, $menuId, $allMenus);
        if ($validationFailure !== null) {
            return OperationResult::failure($validationFailure);
        }

        $hasChildMenus = $allMenus->contains('ParentMenuId', $menuId);
        if ($normalizedRequest['MenuType'] === MenuType::Page && $hasChildMenus) {
            return OperationResult::failure(ErrorMessageKeys::MenuHasChildren);
        }

        $parentMenuIdByMenuId = $allMenus->pluck('ParentMenuId', 'Id')->all();
        $parentMenuIdByMenuId[$menuId] = $normalizedRequest['ParentMenuId'];
        if ($this->hasCycle($parentMenuIdByMenuId)) {
            return OperationResult::failure(ErrorMessageKeys::MenuParentInvalid);
        }

        $isBecomingPage = $menu->MenuType !== MenuType::Page && $normalizedRequest['MenuType'] === MenuType::Page;

        DB::transaction(function () use ($menu, $normalizedRequest, $isBecomingPage) {
            $this->applyRequest($menu, $normalizedRequest);
            $menu->save();
            $this->applyTranslations($menu, $normalizedRequest['DisplayNameByLanguageCode']);

            if ($isBecomingPage) {
                $this->grantFullAccessToSystemRoles($menu);
            }
        });
        $this->userScopedCache->invalidateAllUsers();

        return OperationResult::success();
    }

    public function delete(int $menuId): OperationResult
    {
        $menu = Menu::query()->find($menuId);
        if ($menu === null) {
            return OperationResult::failure(ErrorMessageKeys::RecordNotFound);
        }

        if (Menu::query()->where('ParentMenuId', $menuId)->exists()) {
            return OperationResult::failure(ErrorMessageKeys::MenuHasChildren);
        }

        DB::transaction(fn () => $menu->delete());
        $this->userScopedCache->invalidateAllUsers();

        return OperationResult::success();
    }

    /**
     * Lưu vị trí sau khi kéo-thả / bấm mũi tên ở System > Menus.
     *
     * @param list<array{menuId: int, parentMenuId: ?int, sortOrder: int}> $menuPositionItems
     */
    public function savePositions(array $menuPositionItems): OperationResult
    {
        $allMenus = $this->listAllMenus();
        $menuById = $allMenus->keyBy('Id');
        $parentMenuIdByMenuId = $allMenus->pluck('ParentMenuId', 'Id')->all();

        foreach ($menuPositionItems as $menuPositionItem) {
            $menu = $menuById->get($menuPositionItem['menuId']);
            if ($menu === null) {
                return OperationResult::failure(ErrorMessageKeys::RecordNotFound);
            }

            $parentMenu = $menuPositionItem['parentMenuId'] === null ? null : $menuById->get($menuPositionItem['parentMenuId']);
            $hierarchyFailure = $this->validateHierarchy($menu->MenuType, $menuPositionItem['parentMenuId'], $parentMenu);
            if ($hierarchyFailure !== null) {
                return OperationResult::failure($hierarchyFailure);
            }

            $parentMenuIdByMenuId[$menuPositionItem['menuId']] = $menuPositionItem['parentMenuId'];
        }

        if ($this->hasCycle($parentMenuIdByMenuId)) {
            return OperationResult::failure(ErrorMessageKeys::MenuParentInvalid);
        }

        DB::transaction(function () use ($menuPositionItems, $menuById) {
            foreach ($menuPositionItems as $menuPositionItem) {
                $menu = $menuById->get($menuPositionItem['menuId']);
                $menu->ParentMenuId = $menuPositionItem['parentMenuId'];
                $menu->SortOrder = $menuPositionItem['sortOrder'];
                $menu->save();     // Eloquent chỉ UPDATE khi giá trị thật sự đổi
            }
        });
        $this->userScopedCache->invalidateAllUsers();

        return OperationResult::success();
    }

    // ------------------------------------------------------------------ Helpers

    /** @return Collection<int, Menu> */
    private function listAllMenus(): Collection
    {
        return Menu::query()->with('translations')->orderBy('SortOrder')->orderBy('Id')->get();
    }

    private function normalize(array $saveMenuRequest): array
    {
        $url = trim((string) ($saveMenuRequest['Url'] ?? ''));
        $iconClass = trim((string) ($saveMenuRequest['IconClass'] ?? ''));

        return [
            'ParentMenuId' => $saveMenuRequest['ParentMenuId'],
            'MenuCode' => strtoupper(trim((string) $saveMenuRequest['MenuCode'])),
            'MenuType' => $saveMenuRequest['MenuType'],
            'DefaultName' => trim((string) $saveMenuRequest['DefaultName']),
            'Url' => $url === '' ? null : $url,
            'IconClass' => $iconClass === '' ? null : $iconClass,
            'IsActive' => (bool) $saveMenuRequest['IsActive'],
            'IsVisibleInNavigation' => (bool) $saveMenuRequest['IsVisibleInNavigation'],
            'DisplayNameByLanguageCode' => $saveMenuRequest['DisplayNameByLanguageCode'] ?? [],
        ];
    }

    /** @param Collection<int, Menu> $allMenus */
    private function validate(array $saveMenuRequest, ?int $menuId, Collection $allMenus): ?string
    {
        if (preg_match('/^[A-Z0-9_]{2,100}$/', $saveMenuRequest['MenuCode']) !== 1) {
            return self::MenuCodeFormatMessage;
        }

        if ($saveMenuRequest['DefaultName'] === '' || mb_strlen($saveMenuRequest['DefaultName']) > 100) {
            return self::DefaultNameRequiredMessage;
        }

        $url = $saveMenuRequest['Url'];
        if ($url !== null && (! str_starts_with($url, '/') || mb_strlen($url) > 300)) {
            return self::UrlFormatMessage;
        }

        if (mb_strlen((string) $saveMenuRequest['IconClass']) > 100) {
            return self::IconClassTooLongMessage;
        }

        if ($saveMenuRequest['MenuType'] === MenuType::Page && $url === null) {
            return ErrorMessageKeys::MenuPageRequiresUrl;
        }

        if ($saveMenuRequest['ParentMenuId'] !== null && $saveMenuRequest['ParentMenuId'] === $menuId) {
            return ErrorMessageKeys::MenuParentInvalid;
        }

        $parentMenu = $saveMenuRequest['ParentMenuId'] === null ? null : $allMenus->firstWhere('Id', $saveMenuRequest['ParentMenuId']);
        $hierarchyFailure = $this->validateHierarchy($saveMenuRequest['MenuType'], $saveMenuRequest['ParentMenuId'], $parentMenu);
        if ($hierarchyFailure !== null) {
            return $hierarchyFailure;
        }

        $isMenuCodeTaken = Menu::query()
            ->where('MenuCode', $saveMenuRequest['MenuCode'])
            ->when($menuId !== null, fn ($query) => $query->where('Id', '!=', $menuId))
            ->exists();

        return $isMenuCodeTaken ? ErrorMessageKeys::MenuCodeAlreadyTaken : null;
    }

    /** Module ở gốc; Group / Page bắt buộc có cha là Module hoặc Group. */
    private function validateHierarchy(MenuType $menuType, ?int $parentMenuId, ?Menu $parentMenu): ?string
    {
        if ($menuType === MenuType::Module) {
            return $parentMenuId === null ? null : ErrorMessageKeys::ModuleCannotHaveParent;
        }

        if ($parentMenuId === null) {
            return ErrorMessageKeys::NonModuleRequiresParent;
        }

        return $parentMenu === null || $parentMenu->MenuType === MenuType::Page ? ErrorMessageKeys::MenuParentInvalid : null;
    }

    /** @param array<int, ?int> $parentMenuIdByMenuId */
    private function hasCycle(array $parentMenuIdByMenuId): bool
    {
        foreach (array_keys($parentMenuIdByMenuId) as $startMenuId) {
            $visitedMenuIds = [];
            $currentMenuId = $startMenuId;
            while ($currentMenuId !== null) {
                if (isset($visitedMenuIds[$currentMenuId])) {
                    return true;
                }
                $visitedMenuIds[$currentMenuId] = true;
                $currentMenuId = $parentMenuIdByMenuId[$currentMenuId] ?? null;
            }
        }

        return false;
    }

    private function applyRequest(Menu $menu, array $saveMenuRequest): void
    {
        $menu->ParentMenuId = $saveMenuRequest['ParentMenuId'];
        $menu->MenuCode = $saveMenuRequest['MenuCode'];
        $menu->MenuType = $saveMenuRequest['MenuType'];
        $menu->DefaultName = $saveMenuRequest['DefaultName'];
        $menu->Url = $saveMenuRequest['MenuType'] === MenuType::Page ? $saveMenuRequest['Url'] : null;
        $menu->IconClass = $saveMenuRequest['IconClass'];
        $menu->IsActive = $saveMenuRequest['IsActive'];
        $menu->IsVisibleInNavigation = $saveMenuRequest['IsVisibleInNavigation'];
    }

    /** Ô trống = xóa bản dịch (dùng DefaultName). @param array<string, ?string> $displayNameByLanguageCode */
    private function applyTranslations(Menu $menu, array $displayNameByLanguageCode): void
    {
        $menu->load('translations');

        foreach ($displayNameByLanguageCode as $languageCode => $displayName) {
            $trimmedDisplayName = mb_substr(trim((string) $displayName), 0, 100);
            $existingTranslation = $menu->translations->firstWhere('LanguageCode', $languageCode);

            if ($trimmedDisplayName === '') {
                $existingTranslation?->delete();
            } elseif ($existingTranslation === null) {
                MenuTranslation::query()->create(['MenuId' => $menu->Id, 'LanguageCode' => $languageCode, 'DisplayName' => $trimmedDisplayName]);
            } elseif ($existingTranslation->DisplayName !== $trimmedDisplayName) {
                $existingTranslation->DisplayName = $trimmedDisplayName;
                $existingTranslation->save();
            }
        }
    }

    /** Role hệ thống (ADMIN) tự nhận FullAccess cho Page mới; bỏ qua Role đã có dòng quyền. */
    private function grantFullAccessToSystemRoles(Menu $menu): void
    {
        $systemRoleIds = Role::query()
            ->where('IsSystemRole', true)
            ->whereDoesntHave('menuPermissions', fn ($query) => $query->where('MenuId', $menu->Id))
            ->pluck('Id');

        foreach ($systemRoleIds as $systemRoleId) {
            RoleMenuPermission::query()->create([
                'RoleId' => $systemRoleId,
                'MenuId' => $menu->Id,
                'AccessLevel' => AccessLevel::FullAccess,
            ]);
        }
    }
}
