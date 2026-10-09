<?php

namespace App\Http\ViewModels\Account;

use App\Http\ViewModels\FormViewModel;

final class LoginViewModel extends FormViewModel
{
    public string $LoginName = '';

    public string $ReturnUrl = '';

    public bool $IsEmailVerificationRequired = false;

    public static function fromInput(array $input): self
    {
        $loginViewModel = new self();
        $loginViewModel->LoginName = (string) ($input['LoginName'] ?? '');
        $loginViewModel->ReturnUrl = (string) ($input['ReturnUrl'] ?? '');

        return $loginViewModel;
    }

    protected static function rules(): array
    {
        return [
            'LoginName' => ['required', 'string', 'max:256'],
            'Password' => ['required', 'string'],
        ];
    }

    protected static function displayNames(): array
    {
        return ['LoginName' => 'User name or email', 'Password' => 'Password'];
    }
}
