<?php

namespace App\Services\Accounts;

use App\Support\Enums\AccountStatus;

/** Trạng thái phiên đọc lại mỗi request (cache theo user) để đẩy user ra ngay khi bị khóa / đổi mật khẩu. */
final class UserSessionStateDto
{
    /** @param list<string> $roleNames */
    public function __construct(
        public readonly int $userAccountId,
        public readonly string $userName,
        public readonly string $fullName,
        public readonly string $securityStamp,
        public readonly AccountStatus $accountStatus,
        public readonly bool $mustChangePassword,
        public readonly ?string $preferredLanguageCode,
        public readonly array $roleNames,
    ) {
    }
}
