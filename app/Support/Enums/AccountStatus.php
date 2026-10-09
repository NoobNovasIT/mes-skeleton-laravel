<?php

namespace App\Support\Enums;

/** = Mes.Common.Enums.AccountStatus. Giá trị khớp cột UserAccount.AccountStatus. */
enum AccountStatus: int
{
    case PendingApproval = 1;
    case Active = 2;
    case Locked = 3;
    case Disabled = 4;

    public function labelKey(): string
    {
        return match ($this) {
            self::PendingApproval => 'Pending approval',
            self::Active => 'Active',
            self::Locked => 'Locked',
            self::Disabled => 'Disabled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PendingApproval => 'mes-badge-warn',
            self::Active => 'mes-badge-ok',
            self::Locked => 'mes-badge-danger',
            self::Disabled => 'mes-badge-muted',
        };
    }

    public static function parse(string|int|null $value): ?self
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return self::tryFrom((int) $value);
        }

        foreach (self::cases() as $accountStatus) {
            if (strcasecmp($accountStatus->name, (string) $value) === 0) {
                return $accountStatus;
            }
        }

        return null;
    }
}
