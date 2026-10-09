<?php

namespace App\Support\Enums;

/**
 * = Mes.Common.Enums.AccessLevel. Giá trị khớp cột TINYINT AccessLevel (RoleMenuPermission, UserMenuPermissionOverride).
 * PHP 8.1+ có "backed enum" giống enum : byte của C#.
 */
enum AccessLevel: int
{
    case NoAccess = 0;
    case ReadOnly = 1;
    case FullAccess = 2;

    /** Khóa dịch hiển thị (AccessLevelDisplay.GetLabelKey bên C#). */
    public function labelKey(): string
    {
        return match ($this) {
            self::ReadOnly => 'Read only',
            self::FullAccess => 'Full access',
            self::NoAccess => 'No access',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::ReadOnly => 'mes-badge-info',
            self::FullAccess => 'mes-badge-ok',
            self::NoAccess => 'mes-badge-muted',
        };
    }

    /** Đọc từ tên ("FullAccess") hoặc số ("2") - dùng cho tham số middleware / form. */
    public static function parse(string|int|null $value): ?self
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return self::tryFrom((int) $value);
        }

        foreach (self::cases() as $accessLevel) {
            if (strcasecmp($accessLevel->name, (string) $value) === 0) {
                return $accessLevel;
            }
        }

        return null;
    }
}
