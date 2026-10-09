<?php

namespace App\Services\Security;

use App\Support\Constants\ErrorMessageKeys;
use App\Support\Results\OperationResult;

/** = Mes.Service/Security/PasswordPolicy.cs: đủ độ dài, có cả chữ lẫn số. */
final class PasswordPolicy
{
    public static function validate(?string $plainPassword, int $minimumLength): OperationResult
    {
        if ($plainPassword === null || mb_strlen($plainPassword) < $minimumLength) {
            return OperationResult::failure(ErrorMessageKeys::PasswordTooShort, $minimumLength);
        }

        $hasLetter = preg_match('/\p{L}/u', $plainPassword) === 1;
        $hasDigit = preg_match('/\p{Nd}/u', $plainPassword) === 1;

        return $hasLetter && $hasDigit
            ? OperationResult::success()
            : OperationResult::failure(ErrorMessageKeys::PasswordRequiresLetterAndDigit);
    }
}
