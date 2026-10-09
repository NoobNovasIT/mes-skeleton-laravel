<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\MesController;
use App\Http\Dialogs\ClientAfterSuccess;
use App\Http\ViewModels\System\MenuSaveViewModel;
use App\Services\Languages\LanguageService;
use App\Services\Menus\MenuManagementService;
use App\Services\Menus\MenuTreeNodeDto;
use App\Support\Enums\MenuType;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** = Areas/System/Controllers/MenuController.cs (System > Menus). */
final class MenuController extends MesController
{
    private const SaveViewName = 'system.menu.save';

    public function __construct(
        private readonly MenuManagementService $menuManagementService,
        private readonly LanguageService $languageService,
    ) {
    }

    public function index(): mixed
    {
        return view('system.menu.index', ['menuTree' => $this->menuManagementService->getTree($this->currentLanguageCode())]);
    }

    public function create(Request $request): mixed
    {
        $menuSaveViewModel = new MenuSaveViewModel();
        $menuSaveViewModel->ParentMenuId = $request->integer('parentMenuId') ?: null;
        $menuSaveViewModel->MenuType = $menuSaveViewModel->ParentMenuId === null ? MenuType::Module : MenuType::Page;
        $this->fillSelectionData($menuSaveViewModel);

        return view(self::SaveViewName, ['menuSaveViewModel' => $menuSaveViewModel, 'title' => localize('Create menu')]);
    }

    public function store(Request $request): mixed
    {
        $menuSaveViewModel = MenuSaveViewModel::fromInput($request->all());

        return $this->save($request, $menuSaveViewModel, localize('Create menu'),
            fn () => $this->menuManagementService->create($menuSaveViewModel->toRequest()));
    }

    public function edit(int $id): mixed
    {
        $menu = $this->menuManagementService->getDetail($id);
        abort_if($menu === null, 404);

        $menuSaveViewModel = MenuSaveViewModel::fromModel($menu);
        $this->fillSelectionData($menuSaveViewModel, $menu->translations->pluck('DisplayName', 'LanguageCode')->all());

        return view(self::SaveViewName, ['menuSaveViewModel' => $menuSaveViewModel, 'title' => localize('Edit menu')]);
    }

    public function update(Request $request, int $id): mixed
    {
        $menuSaveViewModel = MenuSaveViewModel::fromInput($request->all(), $id);

        return $this->save($request, $menuSaveViewModel, localize('Edit menu'),
            fn () => $this->menuManagementService->update($id, $menuSaveViewModel->toRequest()));
    }

    public function destroy(int $id): Response
    {
        $deleteResult = $this->menuManagementService->delete($id);

        return $this->operationResponse($deleteResult, 'Deleted successfully.', route('system.menu.index'), ClientAfterSuccess::ReloadPage);
    }

    /** Mes.postJson gửi JSON: [{ menuId, parentMenuId, sortOrder }, ...] */
    public function savePositions(Request $request): Response
    {
        $menuPositionItems = array_map(fn (array $item) => [
            'menuId' => (int) ($item['menuId'] ?? 0),
            'parentMenuId' => isset($item['parentMenuId']) ? (int) $item['parentMenuId'] : null,
            'sortOrder' => (int) ($item['sortOrder'] ?? 0),
        ], array_filter((array) $request->json()->all(), 'is_array'));

        return $this->operationJson($this->menuManagementService->savePositions($menuPositionItems), 'Menu order has been saved.');
    }

    /** Create / Edit dùng chung: validate -> gọi Service -> lỗi thì trả lại form, thành công thì tải lại trang (sidebar đổi). */
    private function save(Request $request, MenuSaveViewModel $menuSaveViewModel, string $title, callable $saveAction): mixed
    {
        $this->fillSelectionData($menuSaveViewModel);
        $saveView = view(self::SaveViewName, ['menuSaveViewModel' => $menuSaveViewModel, 'title' => $title]);

        $validator = MenuSaveViewModel::validator($request->all());
        if ($validator->fails()) {
            return $this->viewWithValidationErrors($saveView, $validator);
        }

        $saveResult = $saveAction();
        if ($saveResult->isFailure()) {
            return $this->viewWithOperationError($saveView, $saveResult);
        }

        return $this->operationResponse($saveResult, 'Saved successfully.', route('system.menu.index'), ClientAfterSuccess::ReloadPage);
    }

    /** @param array<string, string> $existingDisplayNameByLanguageCode */
    private function fillSelectionData(MenuSaveViewModel $menuSaveViewModel, array $existingDisplayNameByLanguageCode = []): void
    {
        $parentOptions = [];
        $this->appendParentOptions($this->menuManagementService->getTree($this->currentLanguageCode()), 0, $menuSaveViewModel->MenuId, $parentOptions);
        $menuSaveViewModel->ParentOptions = $parentOptions;

        $postedDisplayNameByLanguageCode = array_column($menuSaveViewModel->Translations, 'DisplayName', 'LanguageCode');
        $menuSaveViewModel->Translations = array_map(fn ($language) => [
            'LanguageCode' => $language->languageCode,
            'LanguageName' => $language->languageName,
            'DisplayName' => $postedDisplayNameByLanguageCode[$language->languageCode]
                ?? $existingDisplayNameByLanguageCode[$language->languageCode]
                ?? null,
        ], $this->languageService->listActive());
    }

    /** Chỉ Module / Group làm cha được; bỏ chính menu đang sửa (và nhánh con của nó). @param list<MenuTreeNodeDto> $nodes */
    private function appendParentOptions(array $nodes, int $depth, ?int $editingMenuId, array &$parentOptions): void
    {
        foreach ($nodes as $node) {
            if ($node->menuType === MenuType::Page || $node->menuId === $editingMenuId) {
                continue;
            }

            $parentOptions[] = ['MenuId' => $node->menuId, 'DisplayName' => str_repeat("\u{00A0}", $depth * 4).$node->displayName];
            $this->appendParentOptions($node->children, $depth + 1, $editingMenuId, $parentOptions);
        }
    }
}
