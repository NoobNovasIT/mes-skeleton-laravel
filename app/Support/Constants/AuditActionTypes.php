<?php

namespace App\Support\Constants;

/** = Mes.Common.Constants.AuditActionTypes. Giá trị cột AuditLog.ActionType. */
final class AuditActionTypes
{
    public const Create = 'Create';
    public const Update = 'Update';
    public const Delete = 'Delete';

    public const Login = 'Login';
    public const LoginFailed = 'LoginFailed';
    public const Logout = 'Logout';
    public const Register = 'Register';
    public const EmailVerified = 'EmailVerified';
    public const PasswordResetRequested = 'PasswordResetRequested';
    public const PasswordReset = 'PasswordReset';
    public const PasswordChanged = 'PasswordChanged';

    public const AccountApproved = 'AccountApproved';
    public const AccountLocked = 'AccountLocked';
    public const AccountUnlocked = 'AccountUnlocked';
    public const AccountDisabled = 'AccountDisabled';
    public const PermissionChange = 'PermissionChange';

    /** Danh sách hiện trong bộ lọc trang Audit Log. */
    public const All = [
        self::Create, self::Update, self::Delete, self::Login, self::LoginFailed, self::Logout, self::Register, self::EmailVerified,
        self::PasswordResetRequested, self::PasswordReset, self::PasswordChanged,
        self::AccountApproved, self::AccountLocked, self::AccountUnlocked, self::AccountDisabled, self::PermissionChange,
    ];

    public static function badgeClass(string $actionType): string
    {
        return match ($actionType) {
            self::Delete, self::LoginFailed, self::AccountLocked, self::AccountDisabled => 'mes-badge-danger',
            self::Create, self::Login, self::AccountApproved => 'mes-badge-ok',
            self::PermissionChange, self::PasswordReset, self::PasswordChanged => 'mes-badge-warn',
            default => 'mes-badge-info',
        };
    }
}
