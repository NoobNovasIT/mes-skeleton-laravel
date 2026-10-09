<?php

namespace App\Http\ViewModels\System;

use App\Http\ViewModels\FormViewModel;
use App\Models\Role;

/** = Areas/System/Models/RoleViewModels.cs (RoleSaveViewModel). */
final class RoleSaveViewModel extends FormViewModel
{
    public ?int $RoleId = null;

    public string $RoleCode = '';

    public string $RoleName = '';

    public ?string $Description = null;

    public bool $IsSystemRole = false;

    public function isCreateMode(): bool
    {
        return $this->RoleId === null;
    }

    public static function fromInput(array $input, ?int $roleId = null): self
    {
        $roleSaveViewModel = new self();
        $roleSaveViewModel->RoleId = $roleId;
        $roleSaveViewModel->RoleCode = (string) ($input['RoleCode'] ?? '');
        $roleSaveViewModel->RoleName = (string) ($input['RoleName'] ?? '');
        $roleSaveViewModel->Description = $input['Description'] ?? null;

        return $roleSaveViewModel;
    }

    public static function fromModel(Role $role): self
    {
        $roleSaveViewModel = self::fromInput($role->only(['RoleCode', 'RoleName', 'Description']), $role->Id);
        $roleSaveViewModel->IsSystemRole = $role->IsSystemRole;

        return $roleSaveViewModel;
    }

    /** = SaveRoleRequest gửi xuống Service. */
    public function toRequest(): array
    {
        return ['RoleCode' => $this->RoleCode, 'RoleName' => $this->RoleName, 'Description' => $this->Description];
    }

    protected static function rules(): array
    {
        return [
            'RoleCode' => ['required', 'regex:/^[A-Za-z0-9_]{2,50}$/'],
            'RoleName' => ['required', 'string', 'max:100'],
            'Description' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected static function displayNames(): array
    {
        return ['RoleCode' => 'Role code', 'RoleName' => 'Role name', 'Description' => 'Description'];
    }
}
