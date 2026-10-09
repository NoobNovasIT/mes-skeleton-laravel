<?php

namespace App\Support\Enums;

/** = Mes.Common.Enums.MenuType. Giá trị khớp cột Menu.MenuType. */
enum MenuType: int
{
    case Module = 1;
    case Group = 2;
    case Page = 3;

    public function labelKey(): string
    {
        return $this->name;
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Module => 'mes-badge-info',
            self::Group => 'mes-badge-warn',
            self::Page => 'mes-badge-muted',
        };
    }

    /** Form gửi tên ("Module") giống Razor render enum; system-menu.js cũng so sánh theo tên. */
    public static function parse(string|int|null $value): ?self
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return self::tryFrom((int) $value);
        }

        foreach (self::cases() as $menuType) {
            if (strcasecmp($menuType->name, (string) $value) === 0) {
                return $menuType;
            }
        }

        return null;
    }
}
