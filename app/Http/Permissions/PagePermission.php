<?php

namespace App\Http\Permissions;

use App\Support\Enums\AccessLevel;

/**
 * = Mes.Web/Infrastructure/Permissions/PagePermission.cs.
 * Middleware RequireMenuPermission lưu quyền của page đang mở vào request; View đọc qua
 * PagePermission::current() hoặc directive @requireAccess (thay cho TagHelper mes-require-access).
 */
final class PagePermission
{
    private const RequestAttributeKey = 'mes.page-permission';

    public function __construct(
        public readonly string $menuCode,
        public readonly AccessLevel $accessLevel,
    ) {
    }

    public function canRead(): bool
    {
        return $this->accessLevel->value >= AccessLevel::ReadOnly->value;
    }

    public function canWrite(): bool
    {
        return $this->accessLevel->value >= AccessLevel::FullAccess->value;
    }

    public static function none(): self
    {
        return new self('', AccessLevel::NoAccess);
    }

    /** Page không gắn middleware "mes.permission" trả none(). */
    public static function current(): self
    {
        $pagePermission = request()->attributes->get(self::RequestAttributeKey);

        return $pagePermission instanceof self ? $pagePermission : self::none();
    }

    public static function setCurrent(self $pagePermission): void
    {
        request()->attributes->set(self::RequestAttributeKey, $pagePermission);
    }
}
