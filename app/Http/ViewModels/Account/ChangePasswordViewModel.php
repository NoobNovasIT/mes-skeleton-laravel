<?php

namespace App\Http\ViewModels\Account;

use App\Http\ViewModels\FormViewModel;

final class ChangePasswordViewModel extends FormViewModel
{
    public int $PasswordMinimumLength = 8;

    public bool $IsPasswordChangeRequired = false;

    protected static function rules(): array
    {
        return [
            'CurrentPassword' => ['required', 'string'],
            'NewPassword' => ['required', 'string', 'max:100'],
            'ConfirmNewPassword' => ['required', 'string', 'same:NewPassword'],
        ];
    }

    protected static function displayNames(): array
    {
        return [
            'CurrentPassword' => 'Current password',
            'NewPassword' => 'New password',
            'ConfirmNewPassword' => 'Confirm new password',
        ];
    }
}
