<?php

namespace App\Http\ViewModels\System;

use App\Http\ViewModels\FormViewModel;
use App\Models\Menu;
use App\Support\Enums\MenuType;

/** = Areas/System/Models/MenuViewModels.cs (MenuSaveViewModel). */
final class MenuSaveViewModel extends FormViewModel
{
    public ?int $MenuId = null;

    public ?int $ParentMenuId = null;

    public string $MenuCode = '';

    public MenuType $MenuType = MenuType::Page;

    public string $DefaultName = '';

    public ?string $Url = null;

    public ?string $IconClass = null;

    public bool $IsActive = true;

    public bool $IsVisibleInNavigation = true;

    /** @var list<array{LanguageCode: string, LanguageName: string, DisplayName: ?string}> */
    public array $Translations = [];

    /** @var list<array{MenuId: int, DisplayName: string}> Danh sách Module / Group làm menu cha (thụt lề theo cấp). */
    public array $ParentOptions = [];

    public function isCreateMode(): bool
    {
        return $this->MenuId === null;
    }

    public static function fromInput(array $input, ?int $menuId = null): self
    {
        $menuSaveViewModel = new self();
        $menuSaveViewModel->MenuId = $menuId;
        $menuSaveViewModel->ParentMenuId = isset($input['ParentMenuId']) && $input['ParentMenuId'] !== '' ? (int) $input['ParentMenuId'] : null;
        $menuSaveViewModel->MenuCode = (string) ($input['MenuCode'] ?? '');
        $menuSaveViewModel->MenuType = MenuType::parse($input['MenuType'] ?? null) ?? MenuType::Page;
        $menuSaveViewModel->DefaultName = (string) ($input['DefaultName'] ?? '');
        $menuSaveViewModel->Url = $input['Url'] ?? null;
        $menuSaveViewModel->IconClass = $input['IconClass'] ?? null;
        // Checkbox không tick thì trình duyệt không gửi -> false.
        $menuSaveViewModel->IsActive = filter_var($input['IsActive'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $menuSaveViewModel->IsVisibleInNavigation = filter_var($input['IsVisibleInNavigation'] ?? false, FILTER_VALIDATE_BOOLEAN);

        foreach ((array) ($input['Translations'] ?? []) as $translation) {
            $menuSaveViewModel->Translations[] = [
                'LanguageCode' => (string) ($translation['LanguageCode'] ?? ''),
                'LanguageName' => (string) ($translation['LanguageName'] ?? ''),
                'DisplayName' => $translation['DisplayName'] ?? null,
            ];
        }

        return $menuSaveViewModel;
    }

    public static function fromModel(Menu $menu): self
    {
        $menuSaveViewModel = new self();
        $menuSaveViewModel->MenuId = $menu->Id;
        $menuSaveViewModel->ParentMenuId = $menu->ParentMenuId;
        $menuSaveViewModel->MenuCode = $menu->MenuCode;
        $menuSaveViewModel->MenuType = $menu->MenuType;
        $menuSaveViewModel->DefaultName = $menu->DefaultName;
        $menuSaveViewModel->Url = $menu->Url;
        $menuSaveViewModel->IconClass = $menu->IconClass;
        $menuSaveViewModel->IsActive = $menu->IsActive;
        $menuSaveViewModel->IsVisibleInNavigation = $menu->IsVisibleInNavigation;

        return $menuSaveViewModel;
    }

    /** = SaveMenuRequest gửi xuống Service. */
    public function toRequest(): array
    {
        $displayNameByLanguageCode = [];
        foreach ($this->Translations as $translation) {
            $displayNameByLanguageCode[$translation['LanguageCode']] = $translation['DisplayName'];
        }

        return [
            'ParentMenuId' => $this->MenuType === MenuType::Module ? null : $this->ParentMenuId,
            'MenuCode' => $this->MenuCode,
            'MenuType' => $this->MenuType,
            'DefaultName' => $this->DefaultName,
            'Url' => $this->Url,
            'IconClass' => $this->IconClass,
            'IsActive' => $this->IsActive,
            'IsVisibleInNavigation' => $this->IsVisibleInNavigation,
            'DisplayNameByLanguageCode' => $displayNameByLanguageCode,
        ];
    }

    protected static function rules(): array
    {
        return [
            'MenuCode' => ['required', 'regex:/^[A-Za-z0-9_]{2,100}$/'],
            'DefaultName' => ['required', 'string', 'max:100'],
            'Url' => ['nullable', 'string', 'max:300'],
            'IconClass' => ['nullable', 'string', 'max:100'],
            'Translations.*.DisplayName' => ['nullable', 'string', 'max:100'],
        ];
    }

    protected static function displayNames(): array
    {
        return [
            'MenuCode' => 'Menu code',
            'DefaultName' => 'Default name',
            'Url' => 'URL',
            'IconClass' => 'Icon',
        ];
    }
}
