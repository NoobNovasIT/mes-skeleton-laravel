<?php

namespace App\Services\Roles;

final class RoleListItemDto
{
    public function __construct(
        public readonly int $roleId,
        public readonly string $roleCode,
        public readonly string $roleName,
        public readonly ?string $description,
        public readonly bool $isSystemRole,
        public readonly int $userCount,
    ) {
    }
}
